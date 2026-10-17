<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AdvanceRequest;
use App\Models\Boat;
use App\Models\CrewAdvance;
use App\Models\Fisher;
use App\Models\PaymentMethod;
use App\Services\Owner\CrewPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * سلف الطاقم والكباتن — تُخصم من مسيراتهم التالية.
 */
class AdvanceController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly CrewPayService $crewPay) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $query = CrewAdvance::forOwner($owner)
            ->when($request->filled('fisher'), fn ($q) => $q->where('fisher_id', $request->query('fisher')))
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->query('to')));

        $fishers = Fisher::forOwner($owner)->with('boat:id,name')->orderByDesc('is_captain')->orderBy('name')->get(['id', 'name', 'boat_id', 'is_captain']);
        $balances = $this->crewPay->advanceBalances($fishers->pluck('id'));

        return view('panel.owner.advances.index', [
            'rows' => (clone $query)->with(['boat', 'paymentMethod'])->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString(),
            'total' => round((float) (clone $query)->sum('amount'), 2),
            'count' => (clone $query)->count(),
            'outstanding' => round(collect($balances)->sum('outstanding'), 2),
            'settled' => round(collect($balances)->sum('settled'), 2),
            'debtors' => $fishers->filter(fn (Fisher $f) => $balances[$f->id]['outstanding'] > 0)->map(fn (Fisher $f) => ['fisher' => $f] + $balances[$f->id])->values(),
            'fishers' => $fishers,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(AdvanceRequest $request): RedirectResponse
    {
        $fisher = $this->ownedFisher($request->user(), $request->validated('fisher_id'));
        $this->crewPay->recordAdvance($request->user(), $fisher, $request->validated());

        return redirect()->route('panel.owner.advances')->with('status', "تم تسجيل سلفة {$fisher->name}.");
    }

    public function destroy(Request $request, int $advance): RedirectResponse
    {
        $this->crewPay->deleteAdvance($this->ownedAdvance($request->user(), $advance), $request->user());

        return redirect()->route('panel.owner.advances')->with('status', 'تم حذف السلفة.');
    }
}
