<?php

namespace App\Services\Owner;

use App\Models\Boat;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Services\Stock\StockLedger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * أرقام رئيسة المالك — تُقرأ في الويب وفي التطبيق من المكان نفسه.
 *
 * الفترة (اليوم، الأسبوع، الشهر، السنة) تحصر ما يُجمع على مدى زمني وحده:
 * الإيرادات وعدد الفواتير والمصيد المعدود وما حُصّل من فواتير الفترة. أمّا ما
 * هو قائم الآن — القوارب والرحلات في طورها والمبلغ غير المحصّل — فلا فترة له:
 * دين الشهر الماضي ما زال مستحقًّا اليوم.
 */
class OwnerDashboard
{
    public const PERIODS = ['today', 'week', 'month', 'year', 'all'];

    public function __construct(private readonly StockLedger $ledger) {}

    public function for(User $owner, string $period = 'all'): array
    {
        $trips = Trip::forOwner($owner);
        $sales = Sale::forSeller($owner);
        $from = $this->periodStart($period);

        $periodSales = (clone $sales)->when($from, fn (Builder $q) => $q->where('sold_at', '>=', $from));
        $revenue = round((float) (clone $periodSales)->sum('total'), 2);
        $uncollected = round((float) (clone $periodSales)->whereColumn('paid_amount', '<', 'total')
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) AS due')->value('due'), 2);
        $previous = $this->previousSpan($period);
        $countedTrips = (clone $trips)->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])
            ->when($from, fn (Builder $q) => $q->where('counted_at', '>=', $from));

        $unpaid = (clone $sales)->whereColumn('paid_amount', '<', 'total');
        $unpaidTotal = round((float) (clone $unpaid)->selectRaw('COALESCE(SUM(total - paid_amount), 0) AS due')->value('due'), 2);

        $scheduled = (clone $trips)->scheduled()->count();
        $forSale = (clone $trips)->where('sale_status', Trip::SALE_OPEN)->count();

        return [
            'period' => $period,
            'kpis' => [
                'revenue' => $revenue,
                // إيرادات الفترة السابقة إلى اللحظة المقابلة منها — null لكل ما مضى.
                'previous_revenue' => $previous
                    ? round((float) (clone $sales)->where('sold_at', '>=', $previous[0])->where('sold_at', '<', $previous[1])->sum('total'), 2)
                    : null,
                // إيرادات الفترة قسمان: ما حُصّل منها وما بقي في فواتيرها.
                'collected' => round($revenue - $uncollected, 2),
                'uncollected' => $uncollected,
                'catch_kg' => round((float) $countedTrips->sum('actual_weight_kg'), 2),
                'active_boats' => Boat::forOwner($owner)->whereIn('status', ['نشط', Trip::AT_SEA])->count(),
                'boats' => Boat::forOwner($owner)->count(),
                'trips_scheduled' => $scheduled,
                'trips_at_sea' => (clone $trips)->where('status', Trip::AT_SEA)->count(),
                'trips_awaiting_count' => (clone $trips)->whereIn('status', [Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
                'trips_for_sale' => $forSale,
                'sales_count' => (clone $periodSales)->count(),
                'unpaid' => $unpaidTotal,
                'customers' => $owner->customers()->count(),
            ],
            // ما ينتظر المالك الآن، في نداء واحد لرئيسة التطبيق: رحلات يبدؤها،
            // ومصيد معدود يبيعه، وفواتير لم تُحصَّل كاملة.
            'action_items' => [
                'trips_to_start' => $scheduled,
                'trips_to_sell' => $forSale,
                'unpaid_sales' => (clone $unpaid)->count(),
                'unpaid_total' => $unpaidTotal,
            ],
            'revenue_by_month' => $this->revenueByMonth($owner),
            'stock' => $this->ledger->stockBySpecies($owner)
                ->map(fn ($row) => ['species_id' => $row->species_id, 'species' => $row->species?->name_ar, 'name_sci' => $row->species?->name_sci, 'weight_kg' => round((float) $row->kg, 2)])
                ->values(),
            // الكابتن لا يبدأ رحلته بنفسه: المالك يبدؤها حين ينطلق القارب.
            'scheduled_trips' => (clone $trips)->scheduled()->with(['boat', 'captain'])->orderBy('departure_time')->orderBy('id')->limit(6)->get(),
            'active_trips' => (clone $trips)->activeForOwner()->with(['boat', 'captain'])->orderByDesc('updated_at')->limit(6)->get(),
            'recent_sales' => (clone $sales)->with(['customer', 'trip'])->latest('sold_at')->limit(6)->get(),
            'fleet' => $this->fleet($owner),
            // مصيد معدود ينتظر البيع، الأقدم عدًّا أولًا (السمك لا ينتظر)، ومعه المتاح من كل صنف.
            'trips_to_sell' => (clone $trips)->where('sale_status', Trip::SALE_OPEN)->with(['boat', 'captain'])
                ->orderBy('counted_at')->orderBy('id')->limit(10)->get()
                ->each(fn (Trip $trip) => $trip->available_stock = $this->ledger->availableLines($owner, $trip)),
            // الفواتير التي بقي فيها مبلغ، الأقدم أولًا.
            'unpaid_sales' => (clone $unpaid)->with(['customer', 'trip'])->oldest('sold_at')->orderBy('id')->limit(5)->get(),
        ];
    }

    /**
     * كل قارب ورحلته القائمة: في البحر، وإلا المجدولة التالية (يبدؤها
     * المالك)، وإلا رحلة عند العدّاد، وإلا لا رحلة (في الميناء). القارب غير
     * النشط لا يظهر إلا وله رحلة قائمة.
     *
     * @return array<int, array{boat: Boat, trip: ?Trip}>
     */
    private function fleet(User $owner): array
    {
        $rank = [Trip::AT_SEA => 0, Trip::SCHEDULED => 1, Trip::RETURNED => 2, Trip::AWAITING_COUNT => 2, Trip::COUNTING => 2];

        $open = Trip::forOwner($owner)
            ->whereIn('status', array_keys($rank))
            ->with(['boat', 'captain', 'departurePort', 'returnPort'])
            ->orderBy('departure_time')->orderBy('id')
            ->get()
            ->groupBy('boat_id');

        return Boat::forOwner($owner)->orderBy('name')->orderBy('id')->get(['id', 'name', 'boat_number', 'status'])
            ->map(fn (Boat $boat) => [
                'boat' => $boat,
                'trip' => ($open[$boat->id] ?? collect())->sortBy(fn (Trip $trip) => $rank[$trip->status])->first(),
            ])
            ->filter(fn (array $row) => $row['trip'] !== null || $row['boat']->status !== 'غير نشط')
            ->values()
            ->all();
    }

    /**
     * المدى نفسه من الفترة السابقة حتى اللحظة المقابلة منها: من أول الشهر
     * الماضي إلى مثل يومنا وساعتنا فيه، فيُقارَن شهر جارٍ بمثله لا بشهر كامل.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function previousSpan(string $period): ?array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->subDay()->startOfDay(), $now->subDay()],
            'week' => [$now->subWeek()->startOfWeek(CarbonImmutable::SUNDAY), $now->subWeek()],
            'month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()],
            'year' => [$now->subYearNoOverflow()->startOfYear(), $now->subYearNoOverflow()],
            default => null,
        };
    }

    /**
     * بداية الفترة بتوقيت التطبيق، أو null لكل ما مضى. الأسبوع يبدأ الأحد
     * كما في أسبوع العمل السعودي.
     */
    private function periodStart(string $period): ?CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => $now->startOfDay(),
            'week' => $now->startOfWeek(CarbonImmutable::SUNDAY),
            'month' => $now->startOfMonth(),
            'year' => $now->startOfYear(),
            default => null,
        };
    }

    /**
     * إيرادات آخر ستة أشهر شهرًا شهرًا — لمخطط الاتجاه.
     */
    private function revenueByMonth(User $owner): array
    {
        $from = now()->startOfMonth()->subMonths(5);

        $rows = Sale::forSeller($owner)
            ->where('sold_at', '>=', $from)
            ->get(['sold_at', 'total'])
            ->groupBy(fn (Sale $sale) => $sale->sold_at->format('Y-m'))
            ->map(fn ($group) => round((float) $group->sum('total'), 2));

        $series = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $from->copy()->addMonths($i);
            $series[] = ['month' => $month->format('Y-m'), 'label' => $month->translatedFormat('M Y'), 'total' => $rows[$month->format('Y-m')] ?? 0];
        }

        return $series;
    }
}
