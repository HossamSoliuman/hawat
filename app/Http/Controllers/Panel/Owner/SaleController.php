<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\SalePaymentRequest;
use App\Http\Requests\Owner\SaleRequest;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Sale;
use App\Models\Trip;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(
        private readonly SaleService $sales,
        private readonly StockLedger $ledger,
    ) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $sales = Sale::forSeller($owner)->with(['trip', 'customer', 'paymentStatus'])->withSum('items', 'weight_kg')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->query('to')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', '%'.$request->query('search').'%')
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->query('search').'%'))))
            ->orderByDesc('sold_at')
            ->paginate(25)->withQueryString();

        return view('panel.owner.sales.index', [
            'sales' => $sales,
            'totals' => [
                'count' => Sale::forSeller($owner)->count(),
                'revenue' => (float) Sale::forSeller($owner)->sum('total'),
                'due' => (float) Sale::forSeller($owner)->selectRaw('COALESCE(SUM(total - paid_amount), 0) AS due')->value('due'),
            ],
            'pendingTrips' => Trip::forOwner($owner)->where('sale_status', Trip::SALE_OPEN)->with('boat')->orderByDesc('counted_at')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $owner = $request->user();
        $trip = $this->ownedTrip($owner, (int) $request->query('trip'))->load('boat', 'catchRecords.species');

        return view('panel.owner.sales.create', [
            'trip' => $trip,
            'available' => $this->ledger->availableLines($owner, $trip),
            'customers' => Customer::forAccount($owner)->where('status', 'نشط')->orderBy('name')->get(['id', 'name', 'phone']),
            'paymentMethods' => PaymentMethod::options(),
            'paymentStatuses' => PaymentStatus::options(),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $sale = $this->sales->sell($request->user(), $request->validated());

        return redirect()->route('panel.owner.sales.show', $sale)->with('status', "سُجّلت الفاتورة {$sale->invoice_number}.");
    }

    public function show(Request $request, int $sale): View
    {
        return view('panel.owner.sales.show', [
            'sale' => $this->ownedSale($request->user(), $sale)->load(['trip.boat', 'customer', 'paymentMethod', 'paymentStatus', 'items.species']),
        ]);
    }

    public function payment(SalePaymentRequest $request, int $sale): RedirectResponse
    {
        $model = $this->sales->recordPayment($request->user(), $this->ownedSale($request->user(), $sale), (float) $request->validated('amount'));

        return redirect()->route('panel.owner.sales.show', $model)->with('status', 'سُجّلت الدفعة.');
    }
}
