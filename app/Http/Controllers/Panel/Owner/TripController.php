<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CancelTripRequest;
use App\Http\Requests\Owner\CatchRequest;
use App\Http\Requests\Owner\TripRequest;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\GearType;
use App\Models\Port;
use App\Models\Species;
use App\Models\Trip;
use App\Models\TripType;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * رحلات المالك بدورتها كلها: إنشاء وإسناد لكابتن، ثم البدء والإلغاء
 * وإرسال المخرجات — الكابتن سجلّ بلا حساب، والمالك يدير رحلته عنه.
 */
class TripController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(
        private readonly TripService $trips,
        private readonly StockLedger $ledger,
    ) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $trips = Trip::forOwner($owner)->with(['boat', 'captain', 'departurePort'])
            ->when($request->query('view') === 'active', fn ($q) => $q->activeForOwner())
            ->when($request->query('view') === 'for-sale', fn ($q) => $q->where('sale_status', Trip::SALE_OPEN))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('panel.owner.trips.index', [
            'trips' => $trips,
            'counts' => [
                'total' => Trip::forOwner($owner)->count(),
                'scheduled' => Trip::forOwner($owner)->where('status', Trip::SCHEDULED)->count(),
                'at_sea' => Trip::forOwner($owner)->where('status', Trip::AT_SEA)->count(),
                'counting' => Trip::forOwner($owner)->whereIn('status', [Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
                'for_sale' => Trip::forOwner($owner)->where('sale_status', Trip::SALE_OPEN)->count(),
            ],
            'boats' => Boat::forOwner($owner)->with('captainFisher:id,name')->orderBy('name')->get(['id', 'name', 'port_id', 'captain_id', 'crew_count', 'license_number']),
            'captains' => Fisher::forOwner($owner)->captains()->active()->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'tripTypes' => TripType::options(),
            'gearTypes' => GearType::where('status', 'نشط')->orderBy('name')->pluck('name'),
            'statuses' => Trip::STATUSES,
        ]);
    }

    public function store(TripRequest $request): RedirectResponse
    {
        $trip = $this->trips->create($request->user(), $request->validated());

        return redirect()->route('panel.owner.trips.show', $trip)->with('status', "أُنشئت الرحلة {$trip->trip_number} — ابدأها حين ينطلق القارب.");
    }

    public function update(TripRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->update($this->ownedTrip($request->user(), $trip), $request->validated());

        return redirect()->route('panel.owner.trips.show', $model)->with('status', 'تم تحديث الرحلة.');
    }

    public function show(Request $request, int $trip): View
    {
        $owner = $request->user();
        $model = $this->ownedTrip($owner, $trip)->load([
            'boat', 'captain', 'counter', 'departurePort', 'returnPort', 'tripType',
            'catchRecords.species', 'catchRecords.addedBy',
            'sales.customer', 'sales.items', 'consignments.dalal', 'consignments.items',
        ]);

        return view('panel.owner.trips.show', [
            'trip' => $model,
            'available' => $this->ledger->availableByTrip($owner, $model),
            'species' => Species::where('directory_status', 'نشط')->orderBy('name_ar')->get(['id', 'name_ar']),
            'captains' => Fisher::forOwner($owner)->captains()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function start(Request $request, int $trip): RedirectResponse
    {
        $model = $this->trips->start($this->ownedTrip($request->user(), $trip), $request->user());

        return redirect()->route('panel.owner.trips.show', $model)->with('status', 'انطلقت الرحلة — القارب الآن في البحر.');
    }

    public function cancel(CancelTripRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->cancel($this->ownedTrip($request->user(), $trip), $request->validated('reason'), $request->user());

        return redirect()->route('panel.owner.trips.show', $model)->with('status', 'أُلغيت الرحلة.');
    }

    public function submitCatch(CatchRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->submitCatch($this->ownedTrip($request->user(), $trip), $request->validated('items'), $request->user());

        return redirect()->route('panel.owner.trips.show', $model)->with('status', 'أُرسلت مخرجات المصيد — الرحلة الآن في طابور الإحصاء.');
    }
}
