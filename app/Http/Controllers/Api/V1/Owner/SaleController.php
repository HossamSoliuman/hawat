<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\SalePaymentRequest;
use App\Http\Requests\Owner\SaleRequest;
use App\Http\Resources\Api\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['trip', 'customer', 'paymentMethod', 'paymentStatus'];

    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::forSeller($request->user())
            ->when($request->filled('trip_id'), fn ($q) => $q->where('trip_id', $request->query('trip_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->query('customer_id')))
            // غير المحصّلة كاملة: ما بقي فيها مبلغ — مدخل "فواتير غير محصّلة" في الرئيسة.
            ->when($request->boolean('unpaid'), fn ($q) => $q->whereColumn('paid_amount', '<', 'total'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->query('to')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', '%'.$request->query('search').'%')
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->query('search').'%'))));

        // مجاميع كل ما يطابق التصفية، لا الصفحة وحدها — رأس قائمة المبيعات في التطبيق.
        $totals = (clone $query)->toBase()
            ->selectRaw('COUNT(*) AS sales_count, COALESCE(SUM(total), 0) AS sales_total, COALESCE(SUM(total - paid_amount), 0) AS sales_remaining')
            ->first();

        $sales = $query->with(self::WITH)->withCount('items')->withSum('items', 'weight_kg')
            ->orderByDesc('sold_at')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return SaleResource::collection($sales)->additional(['totals' => [
            'count' => (int) $totals->sales_count,
            'total' => round((float) $totals->sales_total, 2),
            'remaining' => round((float) $totals->sales_remaining, 2),
        ]]);
    }

    public function store(SaleRequest $request): JsonResponse
    {
        $sale = $this->sales->sell($request->user(), $request->validated());

        return (new SaleResource($sale->loadCount('items')->loadSum('items', 'weight_kg')))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $sale): SaleResource
    {
        return new SaleResource($this->ownedSale($request->user(), $sale)->load(array_merge(self::WITH, ['items.species']))->loadCount('items')->loadSum('items', 'weight_kg'));
    }

    /**
     * تحصيل ما بقي من فاتورة: دفعة لا تتجاوز المتبقي.
     */
    public function payment(SalePaymentRequest $request, int $sale): SaleResource
    {
        $model = $this->sales->recordPayment($request->user(), $this->ownedSale($request->user(), $sale), (float) $request->validated('amount'));

        return new SaleResource($model->load(array_merge(self::WITH, ['items.species']))->loadCount('items')->loadSum('items', 'weight_kg'));
    }
}
