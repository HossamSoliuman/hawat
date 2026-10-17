<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BoatRequest;
use App\Models\Boat;
use App\Models\BoatCategory;
use App\Models\BoatType;
use App\Models\Fisher;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * قوارب المالك — السجل نفسه الذي تراه صفحة الميناء ومركز المعلومات.
 */
class BoatController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): View
    {
        $owner = $request->user();

        $boats = Boat::forOwner($owner)->with(['port', 'category', 'type', 'captainFisher'])->withCount('trips')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('boat_number', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.boats.index', [
            'boats' => $boats,
            'counts' => [
                'total' => Boat::forOwner($owner)->count(),
                'active' => Boat::forOwner($owner)->where('status', 'نشط')->count(),
                'at_sea' => Boat::forOwner($owner)->where('status', 'في البحر')->count(),
                'maintenance' => Boat::forOwner($owner)->where('status', 'صيانة')->count(),
            ],
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'categories' => BoatCategory::options(),
            'types' => BoatType::options(),
            'captains' => Fisher::forOwner($owner)->captains()->orderBy('name')->get(['id', 'name']),
            'statuses' => Boat::STATUSES,
        ]);
    }

    public function store(BoatRequest $request): RedirectResponse
    {
        Boat::create($request->validated() + ['status' => 'نشط', 'owner_id' => $request->user()->id]);

        return redirect()->route('panel.owner.boats')->with('status', 'تمت إضافة القارب.');
    }

    public function update(BoatRequest $request, int $boat): RedirectResponse
    {
        DB::transaction(function () use ($request, $boat) {
            $model = $this->lockedBoat($request->user(), $boat);
            $model->assertVersion($request);
            $model->update($request->validated());
        });

        return redirect()->route('panel.owner.boats')->with('status', 'تم تحديث القارب.');
    }

    public function destroy(Request $request, int $boat): RedirectResponse
    {
        $model = $this->ownedBoat($request->user(), $boat);

        if ($model->trips()->exists()) {
            return redirect()->route('panel.owner.boats')->withErrors(['boat' => 'لا يُحذف قارب له رحلات — عطّله بدل حذفه.']);
        }

        $model->delete();

        return redirect()->route('panel.owner.boats')->with('status', 'تم حذف القارب.');
    }
}
