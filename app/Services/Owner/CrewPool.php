<?php

namespace App\Services\Owner;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Trip;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * نصيب طاقم القارب من شهره — قاعدة hispa (MonthlyFinancialsService) على
 * بيانات hawat:
 *
 *   الإيراد   = صافي المالك من بيعه المباشر لمصيد القارب + صافيه من بيع
 *               الدلال لمصيد القارب (بعد العمولة والأجور)
 *   المصروفات = سندات القارب في الشهر (ومنها الرواتب الثابتة المرحَّلة)
 *   الإهلاك   = قسط أصول القارب للشهر (+ المؤجَّل من الشهر السابق)، ولا يُحمَّل
 *               منه إلا ما يغطيه الربح — الباقي يؤجَّل فلا يصنع الإهلاك خسارة
 *   الصافي    = الإيراد − المصروفات − الإهلاك المحمَّل
 *   المالك    = الصافي × نسبته، والطاقم الباقي ولا يقل عن صفر (الخسارة على المالك)
 *
 * المؤجَّل الداخل من الشهر السابق يأتي من إغلاقه ({@see MonthLock::broughtForward}).
 * و`$pendingExpenses` رواتب ثابتة ستُرحَّل ولم تُرحَّل بعد (معاينة إغلاق
 * شهر لقارب بلا مسير) فتُحسب كما ستكون.
 */
class CrewPool
{
    public function __construct(private readonly AssetDepreciation $depreciation) {}

    /**
     * @return array{revenue: float, expenses: float, depreciation: float, depreciation_charged: float, depreciation_deferred: float, net_profit: float, owner_share_percent: float, owner_share: float, crew_pool: float}
     */
    public function forBoatMonth(User $owner, int $boatId, int $year, int $month, float $ownerPercent, float $broughtForward = 0.0, float $pendingExpenses = 0.0): array
    {
        $from = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $to = $from->endOfMonth();

        $revenue = $this->revenue($owner, $boatId, $from, $to);
        $expenses = round((float) Expense::forOwner($owner)
            ->where('boat_id', $boatId)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->sum('total') + $pendingExpenses, 2);

        $own = $this->depreciation->forMonth($owner, $year, $month, $boatId)['total'];
        $considered = round($own + $broughtForward, 2);
        $beforeDepreciation = round($revenue - $expenses, 2);
        $charged = round(min($considered, max($beforeDepreciation, 0.0)), 2);
        $net = round($beforeDepreciation - $charged, 2);

        $crew = max(round($net - round($net * $ownerPercent / 100, 2), 2), 0.0);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'depreciation' => $considered,
            'depreciation_charged' => $charged,
            'depreciation_deferred' => round($considered - $charged, 2),
            'net_profit' => $net,
            'owner_share_percent' => round($ownerPercent, 2),
            'owner_share' => round($net - $crew, 2),
            'crew_pool' => $crew,
        ];
    }

    /**
     * توزيع النصيب: صاحب النسبة الخاصة يأخذ نسبته من النصيب كله أوّلًا، والباقي
     * بين البقية بأسهمهم (سهم لكلٍّ = بالتساوي). آخر صاحب أسهم يستوعب فرق
     * التقريب فيساوي المجموع النصيب بالضبط.
     *
     * @param  iterable<array{key: int|string, shares: float, custom_percent: ?float}>  $members
     * @return array<int|string, float>
     */
    public function distribute(float $pool, iterable $members): array
    {
        $dues = [];
        $plain = [];
        $customTotal = 0.0;

        foreach ($members as $member) {
            if (($member['custom_percent'] ?? 0) > 0) {
                $dues[$member['key']] = round($pool * $member['custom_percent'] / 100, 2);
                $customTotal += $dues[$member['key']];
            } elseif ($member['shares'] > 0) {
                $plain[$member['key']] = (float) $member['shares'];
            } else {
                $dues[$member['key']] = 0.0;
            }
        }

        $remaining = max(round($pool - $customTotal, 2), 0.0);
        $totalShares = array_sum($plain);
        $given = 0.0;
        $last = array_key_last($plain);

        foreach ($plain as $key => $shares) {
            $dues[$key] = $key === $last
                ? round($remaining - $given, 2)
                : round($remaining * $shares / $totalShares, 2);
            $given += $dues[$key];
        }

        return $dues;
    }

    private function revenue(User $owner, int $boatId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $tripIds = Trip::where('boat_id', $boatId)->pluck('id');

        $direct = Sale::where('seller_id', $owner->id)
            ->whereIn('trip_id', $tripIds)
            ->whereBetween('sold_at', [$from, $to])
            ->sum('owner_net');

        $viaDalal = SaleItem::where('owner_id', $owner->id)
            ->whereIn('trip_id', $tripIds)
            ->whereHas('sale', fn ($q) => $q->where('seller_id', '!=', $owner->id)->whereBetween('sold_at', [$from, $to]))
            ->sum('owner_net');

        return round((float) $direct + (float) $viaDalal, 2);
    }
}
