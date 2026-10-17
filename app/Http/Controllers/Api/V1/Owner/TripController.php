<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CancelTripRequest;
use App\Http\Requests\Owner\CatchRequest;
use App\Http\Requests\Owner\TripRequest;
use App\Http\Resources\Api\TripResource;
use App\Models\Trip;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * رحلات المالك بدورتها كلها: الإنشاء والإسناد لكابتن، ثم البدء والإلغاء
 * وإرسال المخرجات — الكابتن سجلّ بلا حساب، والمالك يدير رحلته عنه.
 */
class TripController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['boat', 'captain', 'counter', 'departurePort', 'returnPort', 'tripType'];

    public function __construct(
        private readonly TripService $trips,
        private readonly StockLedger $ledger,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $trips = Trip::forOwner($request->user())->with(self::WITH)
            ->when($request->boolean('active'), fn ($q) => $q->activeForOwner())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('sale_status'), fn ($q) => $q->where('sale_status', $request->query('sale_status')))
            ->when($request->filled('boat_id'), fn ($q) => $q->where('boat_id', $request->query('boat_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return TripResource::collection($trips);
    }

    public function store(TripRequest $request): JsonResponse
    {
        $trip = $this->trips->create($request->user(), $request->validated());

        return (new TripResource($trip->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $trip): TripResource
    {
        return new TripResource($this->detailed($this->ownedTrip($request->user(), $trip)));
    }

    public function update(TripRequest $request, int $trip): TripResource
    {
        $model = $this->trips->update($this->ownedTrip($request->user(), $trip), $request->validated());

        return new TripResource($model->load(self::WITH));
    }

    public function start(Request $request, int $trip): TripResource
    {
        return new TripResource($this->trips->start($this->ownedTrip($request->user(), $trip), $request->user())->load(self::WITH));
    }

    public function cancel(CancelTripRequest $request, int $trip): TripResource
    {
        return new TripResource($this->trips->cancel($this->ownedTrip($request->user(), $trip), $request->validated('reason'), $request->user())->load(self::WITH));
    }

    public function submitCatch(CatchRequest $request, int $trip): TripResource
    {
        $model = $this->trips->submitCatch($this->ownedTrip($request->user(), $trip), $request->validated('items'), $request->user());

        return new TripResource($this->detailed($model));
    }

    /**
     * تفاصيل الرحلة مع سطور المصيد والمتاح للبيع من كل صنف.
     */
    private function detailed(Trip $trip): Trip
    {
        $trip->load(array_merge(self::WITH, ['catchRecords.species', 'catchRecords.addedBy']));

        $trip->available_stock = $this->ledger->availableLines($trip->owner, $trip);

        return $trip;
    }
}
