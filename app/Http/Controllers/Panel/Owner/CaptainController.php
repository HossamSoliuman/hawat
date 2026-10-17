<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\HandlesFormAvatar;
use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CaptainRequest;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\IdType;
use App\Models\Port;
use App\Services\Owner\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * كباتن المالك: سجلات صيادين بلا حساب دخول — المالك يسندهم إلى القوارب
 * والرحلات ويدير الرحلة عنهم.
 */
class CaptainController extends Controller
{
    use HandlesFormAvatar, ResolvesOwnerRecords;

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $captains = Fisher::forOwner($owner)->captains()
            ->with(['boat', 'port', 'idType'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')
                ->orWhere('national_id', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.captains.index', [
            'captains' => $captains,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'idTypes' => IdType::options(),
        ]);
    }

    public function store(CaptainRequest $request): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $captain = $this->staff->createCaptain($request->user(), $request->validated());
        $this->syncAvatar($request, $captain);

        return redirect()->route('panel.owner.captains')->with('status', 'تمت إضافة الكابتن.');
    }

    public function update(CaptainRequest $request, int $captain): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $model = DB::transaction(function () use ($request, $captain) {
            $model = $this->lockedCaptain($request->user(), $captain);
            $model->assertVersion($request);

            return $this->staff->updateCaptain($model, $request->validated());
        });
        $this->syncAvatar($request, $model);

        return redirect()->route('panel.owner.captains')->with('status', 'تم تحديث بيانات الكابتن.');
    }

    public function toggle(Request $request, int $captain): RedirectResponse
    {
        $model = $this->ownedCaptain($request->user(), $captain);
        $active = $model->status !== Fisher::ACTIVE;
        $this->staff->setCaptainStatus($model, $active ? Fisher::ACTIVE : 'غير نشط');

        return redirect()->route('panel.owner.captains')->with('status', $active ? 'تم تفعيل الكابتن.' : 'تم تعطيل الكابتن.');
    }

    public function destroy(Request $request, int $captain): RedirectResponse
    {
        $this->staff->deleteCaptain($this->ownedCaptain($request->user(), $captain));

        return redirect()->route('panel.owner.captains')->with('status', 'تم حذف الكابتن.');
    }

    private function syncAvatar(Request $request, Fisher $captain): void
    {
        $this->syncFormAvatar(
            $request,
            fn ($file) => $this->staff->storeAvatar($captain, $file),
            fn () => $this->staff->removeAvatar($captain),
        );
    }
}
