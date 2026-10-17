<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BoatShareRequest;
use App\Http\Requests\Owner\PaySettingsRequest;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\PayType;
use App\Services\Owner\CrewPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * أجور الطاقم: إعداد أجر كل كابتن وفرد طاقم مجمّعين حسب القارب، ونسبة
 * المالك من كل قارب، وكشف حساب الفرد.
 */
class CrewPayController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly CrewPayService $crewPay) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $fishers = Fisher::forOwner($owner)->with(['payType', 'fisherRole'])
            ->orderByDesc('is_captain')->orderBy('name')
            ->get();

        return view('panel.owner.crew-pay.index', [
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name', 'owner_share_percent']),
            'byBoat' => $fishers->groupBy(fn (Fisher $fisher) => $fisher->boat_id ?? 0),
            'balances' => $this->crewPay->advanceBalances($fishers->pluck('id')),
            'payTypes' => PayType::options(),
            'shareTypeId' => PayType::named(PayType::SHARE)->id,
        ]);
    }

    public function update(PaySettingsRequest $request, int $fisher): RedirectResponse
    {
        $record = $this->ownedFisher($request->user(), $fisher);
        $this->crewPay->saveSettings($record, $request->validated(), $request->user());

        return redirect()->route('panel.owner.crew-pay')->with('status', "تم حفظ أجر {$record->name}.");
    }

    public function boatShare(BoatShareRequest $request, int $boat): RedirectResponse
    {
        $record = $this->ownedBoat($request->user(), $boat);
        $this->crewPay->setOwnerShare($record, (float) $request->validated('owner_share_percent'), $request->user());

        return redirect()->route('panel.owner.crew-pay')->with('status', "تم حفظ نسبة المالك من {$record->name}.");
    }

    public function statement(Request $request, int $fisher): View
    {
        $record = $this->ownedFisher($request->user(), $fisher)->load(['boat', 'payType']);

        return view('panel.owner.crew-pay.statement', ['owner' => $request->user(), 'fisher' => $record] + $this->crewPay->statement($record));
    }
}
