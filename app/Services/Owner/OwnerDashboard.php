<?php

namespace App\Services\Owner;

use App\Models\Boat;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Services\Stock\StockLedger;

/**
 * أرقام رئيسة المالك — تُقرأ في الويب وفي التطبيق من المكان نفسه.
 */
class OwnerDashboard
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function for(User $owner): array
    {
        $trips = Trip::forOwner($owner);
        $sales = Sale::forSeller($owner);

        $countedKg = (float) (clone $trips)->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])->sum('actual_weight_kg');
        $revenue = (float) (clone $sales)->sum('total');

        return [
            'kpis' => [
                'revenue' => round($revenue, 2),
                'catch_kg' => round($countedKg, 2),
                'active_boats' => Boat::forOwner($owner)->whereIn('status', ['نشط', Trip::AT_SEA])->count(),
                'boats' => Boat::forOwner($owner)->count(),
                'trips_scheduled' => (clone $trips)->scheduled()->count(),
                'trips_at_sea' => (clone $trips)->where('status', Trip::AT_SEA)->count(),
                'trips_awaiting_count' => (clone $trips)->whereIn('status', [Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
                'trips_for_sale' => (clone $trips)->where('sale_status', Trip::SALE_OPEN)->count(),
                'sales_count' => (clone $sales)->count(),
                'unpaid' => round((float) (clone $sales)->selectRaw('COALESCE(SUM(total - paid_amount), 0) AS due')->value('due'), 2),
                'customers' => $owner->customers()->count(),
            ],
            'revenue_by_month' => $this->revenueByMonth($owner),
            'stock' => $this->ledger->stockBySpecies($owner)
                ->map(fn ($row) => ['species_id' => $row->species_id, 'species' => $row->species?->name_ar, 'name_sci' => $row->species?->name_sci, 'weight_kg' => round((float) $row->kg, 2)])
                ->values(),
            // الكابتن لا يبدأ رحلته بنفسه: المالك يبدؤها حين ينطلق القارب.
            'scheduled_trips' => (clone $trips)->scheduled()->with(['boat', 'captain'])->orderBy('departure_time')->orderBy('id')->limit(6)->get(),
            'active_trips' => (clone $trips)->activeForOwner()->with(['boat', 'captain'])->orderByDesc('updated_at')->limit(6)->get(),
            'recent_sales' => (clone $sales)->with(['customer', 'trip'])->latest('sold_at')->limit(6)->get(),
        ];
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
