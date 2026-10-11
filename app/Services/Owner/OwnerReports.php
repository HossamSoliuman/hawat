<?php

namespace App\Services\Owner;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Fisher;
use App\Models\MonthClosing;
use App\Models\PayrollLine;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * تقارير المالك (O6) — تقارير hispa بمحتواها وتخطيطها (ReportsHub، ProfitLoss،
 * MonthSummary، AnnualSummary، ProfitabilityReport، AccountStatement، كميات
 * الأسماك) على بيانات hawat. كل دالة تُرجع الحقول التي تعرضها صفحة hispa المقابلة.
 *
 * الأرقام المالية الشهرية (صافي الإيراد، مصروفات القوارب، الإهلاك المحمَّل،
 * نصيب الطاقم، العام) تُقرأ من إغلاق الشهر: لقطته إن كان مُغلقًا، وإلا معاينته
 * الحيّة بلا تحديث المسيرات ({@see MonthClosingService::preview}) — فتتطابق
 * قائمة الأرباح والملخصان وربحية القوارب مع إغلاق كل شهر. إجمالي المبيعات
 * والفئات والأصناف والأوزان من السجلات مباشرة.
 */
class OwnerReports
{
    /** عنوان كل تقرير كما في hispa، و`hub` تسميته في المركز إن اختلفت. */
    public const REPORTS = [
        'trip-report' => ['title' => 'تقرير الرحلات'],
        'sales-report' => ['title' => 'تقارير المبيعات', 'hub' => 'تقرير المبيعات'],
        'trip-profitability' => ['title' => 'ربحية الرحلات'],
        'boat-profitability' => ['title' => 'ربحية القوارب'],
        'production' => ['title' => 'الإنتاج حسب نوع السمك'],
        'month-summary' => ['title' => 'ملخص الشهر المالي'],
        'profit-loss' => ['title' => 'تقرير الأرباح والخسائر'],
        'expenses-by-category' => ['title' => 'مصروفات الرحلات حسب الفئة'],
        'customer-statement' => ['title' => 'كشف حساب العملاء'],
        'vendor-statement' => ['title' => 'كشف حساب الموردين'],
        'crew-statement' => ['title' => 'كشف حساب الطاقم'],
        'annual-summary' => ['title' => 'الإقفال السنوي'],
        'fish-quantity' => ['title' => 'تقرير مخزون الأسماك', 'hub' => 'كميات الأسماك'],
    ];

    public const MONTHS = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    public const QUARTERS = [1 => 'الربع الأول (يناير–مارس)', 'الربع الثاني (أبريل–يونيو)', 'الربع الثالث (يوليو–سبتمبر)', 'الربع الرابع (أكتوبر–ديسمبر)'];

    /** @var array<string, array<string, mixed>|null> */
    private array $months = [];

    /** @var array<int, array{0: ?MonthClosing, 1: ?MonthClosing}> */
    private array $bounds = [];

    public function __construct(
        private readonly MonthClosingService $closings,
        private readonly CrewPool $pool,
    ) {}

    /**
     * مبلغ بعملته للويب والطباعة — معزول LTR حتى لا تنقلب إشارة السالب،
     * و`parens` للمطروح بين قوسين (داخل العزل فلا يلتف القوس على العملة).
     */
    public static function money(float|int|string|null $amount, bool $parens = false): HtmlString
    {
        $number = number_format((float) $amount, 2);

        return new HtmlString('<span class="money"><bdi dir="ltr">'.($parens ? '('.$number.')' : $number).'</bdi> <small>ر.س</small></span>');
    }

    /**
     * نسبة داخل نص عربي ("حصة المالك (50%)") معزولة LTR (LRI … PDI) — بدونها
     * تُعرض "%50".
     */
    public static function percent(float $value): string
    {
        return "\u{2066}".rtrim(rtrim(number_format($value, 2), '0'), '.')."%\u{2069}";
    }

    public static function role(bool $isCaptain): string
    {
        return $isCaptain ? 'كابتن' : 'فرد طاقم';
    }

    // ───────────────────────────── الأشهر ─────────────────────────────

    /**
     * أرقام شهر كما في إغلاقه: لقطته إن كان مُغلقًا، وإلا المعاينة الحيّة.
     * null لشهر لم يبدأ.
     *
     * @return array{status: string, closing: ?MonthClosing, data: array<string, mixed>}|null
     */
    public function month(User $owner, int $year, int $month): ?array
    {
        $key = sprintf('%d-%04d-%02d', $owner->id, $year, $month);

        if (array_key_exists($key, $this->months)) {
            return $this->months[$key];
        }

        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        if ($start->greaterThan(CarbonImmutable::now()->startOfMonth())) {
            return $this->months[$key] = null;
        }

        $closing = MonthClosing::forOwner($owner)->where('year', $year)->where('month', $month)->first();
        $data = $closing ? $this->closings->present($closing) : $this->closings->preview($owner, $year, $month, false);

        [$firstClosed, $latestClosed] = $closing ? [null, null] : $this->closingBounds($owner);

        // شهر قبل أول إغلاق لن يُغلق أبدًا (الإغلاق بالتسلسل بعده)، فرواتبه
        // الثابتة التي "ستُرحَّل" لن تُرحَّل: تُحسب أرقام قواربه بلاها.
        if ($firstClosed && $start->lessThan($firstClosed->period_start)) {
            $data['boats'] = array_map(function (array $b) use ($owner, $year, $month) {
                if ($b['payroll'] !== null || $b['pending_fixed'] <= 0) {
                    return $b;
                }

                return array_merge($b, $this->pool->forBoatMonth($owner, $b['boat_id'], $year, $month, (float) $b['owner_share_percent'], (float) $b['depreciation_brought_forward']), ['pending_fixed' => 0.0]);
            }, $data['boats']);
        }

        // المعاينة تُظهر القارب برواتب أفراده الثابتة وإن لم يكن له في الشهر
        // بيع ولا سند ولا إهلاك (شهر قبل بدء النشاط) — لا يعدّه التقرير نشاطًا،
        // إلا في شهر سيُغلق بالتسلسل فيُرحِّل إغلاقُه تلك الرواتب: ما بعد آخر
        // إغلاق، أو — قبل أي إغلاق — الشهر الماضي فما بعده ({@see MonthClosingService::nextPeriod}).
        $willClose = $closing === null && ($latestClosed !== null
            ? $start->greaterThan($latestClosed->period_start)
            : $start->greaterThanOrEqualTo(CarbonImmutable::now()->startOfMonth()->subMonth()));

        $data['boats'] = array_values(array_filter($data['boats'], fn (array $b) => $closing !== null
            || $willClose
            || $b['payroll'] !== null
            || $b['revenue'] != 0
            || round($b['expenses'] - $b['pending_fixed'], 2) != 0
            || $b['depreciation_own'] != 0
            || $b['depreciation_brought_forward'] != 0));

        return $this->months[$key] = [
            'status' => $closing ? 'closed' : ($start->endOfMonth()->isPast() ? 'open' : 'current'),
            'closing' => $closing,
            'data' => $data,
        ];
    }

    /**
     * أول إغلاق للمالك وآخره (null إن لم يُغلق شيء).
     *
     * @return array{0: ?MonthClosing, 1: ?MonthClosing}
     */
    private function closingBounds(User $owner): array
    {
        return $this->bounds[$owner->id] ??= [
            MonthClosing::forOwner($owner)->orderBy('year')->orderBy('month')->first(),
            MonthClosing::forOwner($owner)->latestFirst()->first(),
        ];
    }

    /**
     * صفة السنة أو الفترة من صافيها — قاعدة واحدة للقائمة والطباعة.
     */
    public static function verdict(float $net): string
    {
        return match (true) {
            $net > 0 => 'رابحة',
            $net < 0 => 'خاسرة',
            default => 'متعادلة',
        };
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    public static function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = [];

        for ($m = $from->startOfMonth(); $m->lessThanOrEqualTo($to); $m = $m->addMonth()) {
            $months[] = $m;
        }

        return $months;
    }

    // ─────────────────── الأرقام المالية (قائمة الأرباح والملخص) ───────────────────

    /**
     * أرقام الفترة (أشهر كاملة) بحقول hispa (MonthlyFinancialsService::compute):
     *
     *   إجمالي المبيعات      = البيع المباشر + إجمالي بيع الدلال لمصيدك
     *   العمولة والعمالة     = إجمالي المبيعات − صافيك منها
     *   صافي الإيرادات       = إيراد القوارب في إغلاق كل شهر
     *   المصروفات التشغيلية  = سندات القوارب (ومنها الرواتب الثابتة)
     *   المصروفات العمومية   = السندات بلا قارب (لا تُحمَّل على قارب واحد)
     *   فرق الإغلاق          = صافي الإيرادات − (إجمالي المبيعات − العمولة)
     *   الإهلاك              = المحمَّل على القوارب + إهلاك الأصول العامة
     *   صافي الربح           = صافي الإيرادات − إجمالي المصروفات
     *   حصة البحارة          = نصيب الطاقم من أرباح القوارب، وحصة المالك الباقي
     *   توزيع الحصة          = نصيب كل قارب مقسومًا بـ{@see CrewPool::distribute}
     *
     * @return array<string, mixed>
     */
    public function financials(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null): array
    {
        $sum = array_fill_keys(['net_owner_revenue', 'trip_expenses', 'pending_fixed', 'general_expenses', 'depreciation', 'crew_share'], 0.0);
        $deferred = 0.0;
        $percents = [];
        $members = [];
        $statuses = [];

        foreach (self::monthsBetween($from, $to) as $m) {
            $month = $this->month($owner, $m->year, $m->month);

            if ($month === null) {
                continue;
            }

            $statuses[] = $month['status'];
            $boats = collect($month['data']['boats'])->when($boatId, fn ($c) => $c->where('boat_id', $boatId));

            $sum['net_owner_revenue'] += $boats->sum('revenue');
            $sum['trip_expenses'] += $boats->sum('expenses');
            $sum['pending_fixed'] += $boats->sum('pending_fixed');
            $sum['depreciation'] += $boats->sum('depreciation_charged');
            $sum['crew_share'] += $boats->sum('crew_pool');
            $deferred = round((float) $boats->sum('depreciation_deferred'), 2);

            if ($boatId === null) {
                $sum['general_expenses'] += $month['data']['general']['expenses'];
                $sum['depreciation'] += $month['data']['general']['depreciation'];
            }

            foreach ($boats as $boat) {
                $percents[] = round((float) $boat['owner_share_percent'], 2);

                // نصيب طاقم القارب مقسومًا بقاعدة المسير على أصحاب الحصة — لا
                // سطر المسير (فيه الزيادة والخصم، ومسير الشهر المفتوح قد يكون
                // قديمًا)، فيساوي مجموع التوزيع حصة البحارة دائمًا.
                $sharers = array_values(array_filter($boat['dues'], fn (array $due) => $due['is_share'] ?? false));
                $split = $this->pool->distribute((float) $boat['crew_pool'], array_map(fn (array $due, int $i) => [
                    'key' => $i,
                    'shares' => (float) $due['shares'],
                    'custom_percent' => $due['custom_percent'],
                ], $sharers, array_keys($sharers)));

                foreach ($sharers as $i => $due) {
                    $key = $due['fisher_id'] ?? 'n:'.$due['name'];
                    $members[$key] ??= [
                        'name' => $due['name'],
                        'role' => self::role($due['is_captain']),
                        'is_captain' => $due['is_captain'],
                        'custom_percent' => $due['custom_percent'],
                        'shares' => $due['shares'],
                        'due' => 0.0,
                    ];
                    $members[$key]['due'] = round($members[$key]['due'] + ($split[$i] ?? 0.0), 2);
                }
            }
        }

        $sum = array_map(fn ($v) => round($v, 2), $sum);
        $sales = $this->salesTotals($owner, $from->startOfMonth(), $to->endOfMonth(), $boatId);
        $total = round($sum['trip_expenses'] + $sum['general_expenses'] + $sum['depreciation'], 2);
        $net = round($sum['net_owner_revenue'] - $total, 2);
        $distribution = collect($members)->sortBy([['is_captain', 'desc'], ['due', 'desc']])->values()->all();
        $percents = array_values(array_unique($percents));

        return $sum + [
            'gross_sales' => $sales['gross'],
            'commission_labor' => $sales['cut'],
            // صافي الإيراد من الإغلاق وإجمالي المبيعات من الفواتير: بيع أُضيف أو
            // عُدِّل بعد إغلاق شهره يظهر فرقًا فتبقى القائمة متطابقة الجمع.
            'revenue_adjustment' => round($sum['net_owner_revenue'] - $sales['net'], 2),
            'total_expenses' => $total,
            'net_profit' => $net,
            'depreciation_deferred' => $deferred,
            'owner_percent' => count($percents) === 1 ? $percents[0] : null,
            'owner_share' => round($net - $sum['crew_share'], 2),
            'crew_count' => count($distribution),
            'per_fisherman' => $distribution ? round($sum['crew_share'] / count($distribution), 2) : 0.0,
            'crew_distribution' => $distribution,
            'months_count' => count($statuses),
            'closed_count' => count(array_filter($statuses, fn ($s) => $s === 'closed')),
        ];
    }

    /**
     * بنود المصروفات لملخص الشهر: التشغيلية (سندات القوارب) والعمومية (بلا
     * قارب) بفئاتها — ومجموع كلٍّ يطابق رقمه في {@see financials()}: الرواتب
     * الثابتة التي لم تُرحَّل بعد وأي فرق عن لقطة الإغلاق يظهران بندين.
     *
     * @return array{operating: array<int, array<string, mixed>>, general: array<int, array<string, mixed>>}
     */
    public function monthExpenses(User $owner, CarbonImmutable $from, CarbonImmutable $to, array $f, ?int $boatId = null): array
    {
        $operating = $this->expenseRows($owner, $from, $to, $boatId === null ? 'boats' : (string) $boatId);
        $general = $boatId === null ? $this->expenseRows($owner, $from, $to, 'general') : [];

        if ($f['pending_fixed'] > 0) {
            $operating[] = ['category_id' => null, 'category' => 'رواتب ثابتة لم تُرحَّل بعد', 'type' => null, 'count' => 0, 'amount' => $f['pending_fixed']];
        }

        $reconcile = function (array $rows, float $expected): array {
            $diff = round($expected - array_sum(array_column($rows, 'amount')), 2);

            if (abs($diff) >= 0.01) {
                $rows[] = ['category_id' => null, 'category' => 'فرق عن لقطة الإغلاق', 'type' => null, 'count' => 0, 'amount' => $diff];
            }

            return $rows;
        };

        return ['operating' => $reconcile($operating, $f['trip_expenses']), 'general' => $reconcile($general, $f['general_expenses'])];
    }

    // ─────────────────────────── المصروفات حسب الفئة ───────────────────────────

    /**
     * المصروفات مجمّعة بالفئة ونوعها (مجموعتها)، الأكبر أولًا.
     *
     * @param  string|null  $boat  رقم قارب، أو "boats" لسندات القوارب، أو "general" لما بلا قارب، أو null للكل
     * @return array<int, array{category_id: ?int, category: string, type: ?string, count: int, amount: float}>
     */
    public function expenseRows(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?string $boat = null): array
    {
        return Expense::forOwner($owner)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->leftJoin('expense_groups', 'expense_groups.id', '=', 'expense_categories.expense_group_id')
            ->whereDate('expenses.date', '>=', $from->toDateString())
            ->whereDate('expenses.date', '<=', $to->toDateString())
            ->when($boat === 'general', fn ($q) => $q->whereNull('expenses.boat_id'))
            ->when($boat === 'boats', fn ($q) => $q->whereNotNull('expenses.boat_id'))
            ->when(is_numeric($boat), fn ($q) => $q->where('expenses.boat_id', (int) $boat))
            ->selectRaw('expense_categories.id AS category_id, expense_categories.name AS category, expense_groups.name AS type, COUNT(*) AS n, SUM(expenses.total) AS amount')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_groups.name')
            ->get()
            ->map(fn ($r) => ['category_id' => $r->category_id ? (int) $r->category_id : null, 'category' => $r->category ?? '—', 'type' => $r->type, 'count' => (int) $r->n, 'amount' => round((float) $r->amount, 2)])
            ->sortByDesc('amount')->values()->all();
    }

    // ─────────────────────────── ربحية القوارب ───────────────────────────

    /**
     * كل قارب: إجمالي مبيعات مصيده، وصافيك منها، ومصروفاته (سنداته والإهلاك
     * المحمَّل) وصافي ربحه من إغلاق كل شهر، والهامش من الصافي.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>, months_count: int, closed_count: int}
     */
    public function boatProfitability(User $owner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $blank = ['gross_sales' => 0.0, 'net_sales' => 0.0, 'expenses' => 0.0, 'net_profit' => 0.0];
        $rows = Boat::forOwner($owner)->orderBy('name')->get(['id', 'name'])
            ->mapWithKeys(fn (Boat $boat) => [$boat->id => ['boat_id' => $boat->id, 'boat_name' => $boat->name] + $blank])->all();

        $statuses = [];

        foreach (self::monthsBetween($from, $to) as $m) {
            $month = $this->month($owner, $m->year, $m->month);

            if ($month === null) {
                continue;
            }

            $statuses[] = $month['status'];

            foreach ($month['data']['boats'] as $b) {
                $rows[$b['boat_id']] ??= ['boat_id' => $b['boat_id'], 'boat_name' => $b['boat_name']] + $blank;
                $rows[$b['boat_id']]['net_sales'] += $b['revenue'];
                $rows[$b['boat_id']]['expenses'] += $b['expenses'] + $b['depreciation_charged'];
                $rows[$b['boat_id']]['net_profit'] += $b['net_profit'];
            }
        }

        $sales = $this->salesByTrip($owner, $from->startOfMonth(), $to->endOfMonth());
        $boatOf = Trip::whereIn('id', $sales->keys())->pluck('boat_id', 'id');

        foreach ($sales as $tripId => $s) {
            $boat = $boatOf[$tripId] ?? null;

            if ($boat !== null && isset($rows[$boat])) {
                $rows[$boat]['gross_sales'] += $s['direct_gross'] + $s['dalal_gross'];
            }
        }

        $rows = array_map(function (array $row) {
            foreach (['gross_sales', 'net_sales', 'expenses', 'net_profit'] as $k) {
                $row[$k] = round($row[$k], 2);
            }

            return $row + ['margin' => $this->margin($row['net_profit'], $row['net_sales'])];
        }, array_values($rows));

        usort($rows, fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);

        return [
            'rows' => $rows,
            'totals' => $this->totals($rows, ['gross_sales', 'net_sales', 'expenses', 'net_profit']),
            'months_count' => count($statuses),
            'closed_count' => count(array_filter($statuses, fn ($s) => $s === 'closed')),
        ];
    }

    // ─────────────────────── تقرير الرحلات والمبيعات ───────────────────────

    /**
     * تقرير الرحلات (TripReport في hispa): الرحلات التي غادرت في الفترة (كلها
     * إن لم تُحدَّد) بحالتها، ومصيد كلٍّ (أصنافه ووزنه)، ومواعيدها ومدتها،
     * وإجمالي مبيعاتها وصافي ربحها (صافيك منها − سنداتها).
     *
     * @return array{rows: array<int, array<string, mixed>>, statistics: array<string, float|int>}
     */
    public function tripReport(User $owner, ?CarbonImmutable $from, ?CarbonImmutable $to, ?string $status = null): array
    {
        $trips = Trip::forOwner($owner)
            ->when($from, fn ($q) => $q->where('departure_time', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('departure_time', '<=', $to->endOfDay()))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['boat:id,name', 'captain:id,name', 'departurePort:id,name'])
            ->orderByDesc('departure_time')->get();

        $ids = $trips->pluck('id');
        $caught = CatchRecord::whereIn('trip_id', $ids)
            ->selectRaw('trip_id, COUNT(*) AS n, SUM(COALESCE(counted_kg, quantity_kg)) AS kg')
            ->groupBy('trip_id')->get()->keyBy('trip_id');
        $sales = $this->salesByTrip($owner, null, null, $ids);
        $expenses = Expense::forOwner($owner)->whereIn('trip_id', $ids)
            ->selectRaw('trip_id, SUM(total) AS total')->groupBy('trip_id')->pluck('total', 'trip_id');

        $rows = $trips->map(function (Trip $trip) use ($owner, $caught, $sales, $expenses) {
            $s = $sales[$trip->id] ?? $this->emptySales();
            $back = $trip->return_time;

            return [
                'trip_id' => $trip->id,
                'number' => $trip->trip_number,
                'boat_name' => $trip->boat?->name ?? '—',
                'license_number' => $trip->license_number,
                'status' => $trip->status,
                'owner_name' => $owner->name,
                'captain_name' => $trip->captain?->name ?? $trip->captain_name ?? '—',
                'items' => (int) ($caught[$trip->id]->n ?? 0),
                'weight' => round((float) ($caught[$trip->id]->kg ?? 0), 2),
                'port' => $trip->departurePort?->name ?? '—',
                'departed' => $trip->departure_time?->format('Y-m-d'),
                'returned' => $back?->format('Y-m-d'),
                'departed_time' => $trip->departure_time?->format('H:i'),
                'returned_time' => $back?->format('H:i'),
                'days' => $trip->departure_time && $back ? (int) abs($trip->departure_time->toImmutable()->startOfDay()->diffInDays($back->toImmutable()->startOfDay())) + 1 : null,
                'sold' => $trip->sale_status === Trip::SALE_DONE,
                'gross_revenue' => round($s['direct_gross'] + $s['dalal_gross'], 2),
                'net_profit' => round($s['direct_net'] + $s['dalal_net'] - (float) ($expenses[$trip->id] ?? 0), 2),
            ];
        })->all();

        return [
            'rows' => $rows,
            'statistics' => [
                'total_trips' => count($rows),
                'completed_trips' => count(array_filter($rows, fn ($r) => $r['sold'])),
                'total_catch' => array_sum(array_column($rows, 'items')),
                'total_weight' => round(array_sum(array_column($rows, 'weight')), 2),
                'total_revenue' => round(array_sum(array_column($rows, 'gross_revenue')), 2),
                'net_profit' => round(array_sum(array_column($rows, 'net_profit')), 2),
            ],
        ];
    }

    /**
     * تقرير المبيعات (SalesReport في hispa): فواتير بيعك المباشر في الفترة،
     * الأحدث أولًا، بوزنها وعمولتها وأجورها وصافيك والمتبقي منها.
     *
     * @return array{rows: array<int, array<string, mixed>>, statistics: array<string, float|int>}
     */
    public function salesReport(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?string $status = null): array
    {
        $rows = Sale::forSeller($owner)
            ->whereDate('sold_at', '>=', $from->toDateString())
            ->whereDate('sold_at', '<=', $to->toDateString())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['customer:id,name', 'paymentMethod:id,name'])
            ->withSum('items', 'weight_kg')
            ->orderByDesc('sold_at')->orderByDesc('id')->get()
            ->map(fn (Sale $sale) => [
                'sale_id' => $sale->id,
                'number' => $sale->invoice_number,
                'status' => $sale->status,
                'completed' => $sale->status === Sale::COMPLETED,
                'customer' => $sale->customer?->name ?? 'عميل نقدي',
                'payment_method' => $sale->paymentMethod?->name ?? '—',
                'weight' => round((float) $sale->items_sum_weight_kg, 2),
                'commission' => round((float) $sale->commission_amount, 2),
                'labor' => round((float) $sale->wage_amount, 2),
                'total' => round((float) $sale->total, 2),
                'net_owner' => round((float) $sale->owner_net, 2),
                'remaining' => $sale->remaining,
                'date' => $sale->sold_at->format('Y-m-d'),
            ])->all();

        return [
            'rows' => $rows,
            'statistics' => [
                'total_sales' => count($rows),
                'total_revenue' => round(array_sum(array_column($rows, 'total')), 2),
                'total_weight' => round(array_sum(array_column($rows, 'weight')), 2),
                'net_owner' => round(array_sum(array_column($rows, 'net_owner')), 2),
            ],
        ];
    }

    // ─────────────────────────── ربحية الرحلات ───────────────────────────

    /**
     * الرحلات التي غادرت في الفترة، الأحدث أولًا: إجمالي مبيعات مصيدها وصافيك
     * منه (في أي تاريخ)، ومصروفاتها — السندات المربوطة بها مباشرة.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function tripProfitability(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null): array
    {
        $trips = Trip::forOwner($owner)
            ->whereBetween('departure_time', [$from->startOfDay(), $to->endOfDay()])
            ->when($boatId, fn ($q) => $q->where('boat_id', $boatId))
            ->with(['boat:id,name', 'captain:id,name'])
            ->orderByDesc('departure_time')->get();

        $ids = $trips->pluck('id');
        $sales = $this->salesByTrip($owner, null, null, $ids);
        $expenses = Expense::forOwner($owner)->whereIn('trip_id', $ids)
            ->selectRaw('trip_id, SUM(total) AS total')->groupBy('trip_id')->pluck('total', 'trip_id');

        $rows = $trips->map(function (Trip $trip) use ($sales, $expenses) {
            $s = $sales[$trip->id] ?? $this->emptySales();
            $net = round($s['direct_net'] + $s['dalal_net'], 2);
            $cost = round((float) ($expenses[$trip->id] ?? 0), 2);

            return [
                'trip_id' => $trip->id,
                'number' => $trip->trip_number,
                'boat_name' => $trip->boat?->name ?? '—',
                'captain_name' => $trip->captain?->name ?? $trip->captain_name ?? '—',
                'start_date' => $trip->departure_time?->format('Y-m-d'),
                'status_label' => $trip->app_status,
                'gross_sales' => round($s['direct_gross'] + $s['dalal_gross'], 2),
                'net_sales' => $net,
                'expenses' => $cost,
                'net_profit' => round($net - $cost, 2),
                'margin' => $this->margin($net - $cost, $net),
            ];
        })->all();

        return ['rows' => $rows, 'totals' => $this->totals($rows, ['gross_sales', 'net_sales', 'expenses', 'net_profit'])];
    }

    // ─────────────────────────── الإنتاج حسب النوع ───────────────────────────

    /**
     * مصيد الرحلات التي غادرت في الفترة (عدا الملغاة) — الوزن المعدود وإلا
     * المعلن، وقيمته — مقابل ما بيع منه في أي تاريخ مباشرة وعبر الدلال.
     *
     * @return array<int, array{fish_id: int, fish_name: string, unit_name: string, caught_weight: float, caught_value: float, sold_weight: float, sold_value: float}>
     */
    public function production(User $owner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $trips = Trip::forOwner($owner)->where('status', '!=', Trip::CANCELLED)
            ->whereBetween('departure_time', [$from->startOfDay(), $to->endOfDay()])
            ->pluck('id');

        $caught = CatchRecord::whereIn('trip_id', $trips)
            ->selectRaw('species_id, SUM(COALESCE(counted_kg, quantity_kg)) AS kg, SUM(CASE WHEN price_per_kg IS NOT NULL THEN COALESCE(counted_kg, quantity_kg) * price_per_kg ELSE COALESCE(total_value, 0) END) AS value')
            ->groupBy('species_id')->get()->keyBy('species_id');
        $sold = collect($this->speciesSold($owner, null, null, $trips))->keyBy('species_id');
        $names = Species::whereIn('id', $caught->keys()->merge($sold->keys())->unique())->pluck('name_ar', 'id');

        return $names->map(fn ($name, $id) => [
            'fish_id' => (int) $id,
            'fish_name' => $name,
            'unit_name' => 'كجم',
            'caught_weight' => round((float) ($caught[$id]->kg ?? 0), 2),
            'caught_value' => round((float) ($caught[$id]->value ?? 0), 2),
            'sold_weight' => round((float) ($sold[$id]['kg'] ?? 0), 2),
            'sold_value' => round((float) ($sold[$id]['gross'] ?? 0), 2),
        ])->sortByDesc('caught_weight')->values()->all();
    }

    // ─────────────────────────── كميات الأسماك ───────────────────────────

    /**
     * سطور المصيد المسجّلة في الفترة بتصفية القارب والرحلة والنوع: الوزن
     * (المعدود وإلا المعلن) وسعر الكيلو والإجمالي.
     *
     * @return Collection<int, array{fish_id: int, fish_name: string, trip: string, weight: float, unit: string, price_per_kg: float, total: float}>
     */
    public function fishQuantity(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null, ?int $tripId = null, ?int $speciesId = null): Collection
    {
        return CatchRecord::query()
            ->whereHas('trip', fn ($q) => $q->forOwner($owner)->when($boatId, fn ($q) => $q->where('boat_id', $boatId)))
            ->when($tripId, fn ($q) => $q->where('trip_id', $tripId))
            ->when($speciesId, fn ($q) => $q->where('species_id', $speciesId))
            ->whereDate('recorded_at', '>=', $from->toDateString())
            ->whereDate('recorded_at', '<=', $to->toDateString())
            ->with(['species:id,name_ar', 'trip:id,trip_number'])
            ->orderBy('recorded_at')->orderBy('id')->get()
            ->map(function (CatchRecord $record) {
                $kg = (float) ($record->counted_kg ?? $record->quantity_kg);

                return [
                    'fish_id' => $record->species_id,
                    'fish_name' => $record->species?->name_ar ?? '—',
                    'trip' => $record->trip?->trip_number ?? '—',
                    'weight' => round($kg, 2),
                    'unit' => 'كجم',
                    'price_per_kg' => round((float) $record->price_per_kg, 2),
                    'total' => $this->catchValue($record, $kg),
                ];
            });
    }

    // ─────────────────────────── كشوف الحساب ───────────────────────────

    /**
     * فواتيرك للعميل في الفترة (الأحدث أولًا) بمدفوعها ومتبقيها.
     *
     * @return array{rows: array<int, array<string, mixed>>, statistics: array<string, float|int>}
     */
    public function customerStatement(User $owner, Customer $customer, ?string $from, ?string $to): array
    {
        $rows = Sale::forSeller($owner)->where('customer_id', $customer->id)
            ->when($from, fn ($q) => $q->whereDate('sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sold_at', '<=', $to))
            ->with(['paymentMethod:id,name', 'paymentStatus:id,name'])
            ->orderByDesc('sold_at')->orderByDesc('id')->get()
            ->map(fn (Sale $sale) => [
                'sale_id' => $sale->id,
                'number' => $sale->invoice_number,
                'date' => $sale->sold_at->format('Y-m-d'),
                'payment_method' => $sale->paymentMethod?->name ?? '—',
                'payment_status' => $sale->paymentStatus?->name ?? $this->paymentStatus((float) $sale->paid_amount, (float) $sale->total),
                'total' => round((float) $sale->total, 2),
                'paid' => round((float) $sale->paid_amount, 2),
                'remaining' => $sale->remaining,
            ])->all();

        return [
            'rows' => $rows,
            'statistics' => [
                'total_orders' => count($rows),
                'total_purchases' => round(array_sum(array_column($rows, 'total')), 2),
                'total_paid' => round(array_sum(array_column($rows, 'paid')), 2),
                'total_remaining' => round(array_sum(array_column($rows, 'remaining')), 2),
            ],
        ];
    }

    /**
     * سنداتك على المورد في الفترة (الأحدث أولًا)، ومجموعها والمستحق منها.
     *
     * @return array{rows: array<int, array<string, mixed>>, total_expenses: float, total_due: float}
     */
    public function vendorStatement(User $owner, Vendor $vendor, ?string $from, ?string $to): array
    {
        $expenses = Expense::forOwner($owner)->where('vendor_id', $vendor->id)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->with(['category:id,name', 'trip:id,trip_number', 'paymentStatus:id,name'])
            ->orderByDesc('date')->orderByDesc('id')->get();

        return [
            'rows' => $expenses->map(fn (Expense $e) => [
                'number' => $e->expense_number,
                'category' => $e->category?->name ?? '—',
                'trip' => $e->trip?->trip_number ?? '—',
                'date' => $e->date->format('Y-m-d'),
                'status' => $e->paymentStatus?->name ?? $this->paymentStatus((float) $e->paid_amount, (float) $e->total),
                'is_paid' => $e->is_paid,
                'amount' => round((float) $e->total, 2),
            ])->all(),
            'total_expenses' => round((float) $expenses->sum('total'), 2),
            'total_due' => round((float) $expenses->sum(fn (Expense $e) => $e->remaining), 2),
        ];
    }

    /**
     * مستحقات الفرد الشهرية من سطور مسيراته (صافيها بعد السلف)، والمدفوع منها
     * وغير المدفوع، بأشهر الفترة.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>}
     */
    public function crewStatement(Fisher $fisher, ?string $from, ?string $to): array
    {
        $fromKey = $from ? substr($from, 0, 7) : null;
        $toKey = $to ? substr($to, 0, 7) : null;

        $rows = $fisher->payrollLines()->with('payroll.boat:id,name')->get()
            ->filter(fn (PayrollLine $line) => (! $fromKey || $line->payroll->period_key >= $fromKey) && (! $toKey || $line->payroll->period_key <= $toKey))
            ->sortBy(fn (PayrollLine $line) => $line->payroll->period_key)
            ->map(fn (PayrollLine $line) => [
                'payroll_id' => $line->payroll_id,
                'period' => sprintf('%02d / %04d', $line->payroll->month, $line->payroll->year),
                'boat' => $line->payroll->boat?->name,
                'due' => round($line->net, 2),
                'paid' => $line->is_paid ? round($line->paid_amount, 2) : 0.0,
                'unpaid' => $line->is_paid ? 0.0 : round($line->net, 2),
                'paid_date' => $line->paid_at?->format('Y-m-d'),
                'is_paid' => $line->is_paid,
                'notes' => $line->notes,
            ])->values()->all();

        return [
            'rows' => $rows,
            'totals' => $this->totals($rows, ['due', 'paid', 'unpaid']) + ['months' => count($rows)],
        ];
    }

    // ─────────────────────────── الإقفال السنوي ───────────────────────────

    /**
     * السنوات التي بها أشهر مُغلقة، الأحدث أولًا، ولكلٍّ ملخصها.
     *
     * @return array<int, array{year: int, summary: array<string, mixed>}>
     */
    public function closedYears(User $owner, ?int $boatId = null): array
    {
        return MonthClosing::forOwner($owner)->distinct()->orderByDesc('year')->pluck('year')
            ->map(fn ($year) => ['year' => (int) $year, 'summary' => $this->annualSummary($owner, (int) $year, $boatId)])
            ->all();
    }

    /**
     * السنة من أشهرها المُغلقة وحدها (لقطة كل إغلاق)، والشهر غير المُغلق null.
     * المؤجَّل في المجموع مؤجَّل آخر شهر مُغلق — ينتقل ولا يُجمع.
     *
     * @return array{year: int, months: array<int, array<string, mixed>|null>, totals: array<string, float>, closed_count: int}
     */
    public function annualSummary(User $owner, int $year, ?int $boatId = null): array
    {
        $months = [];

        foreach (range(1, 12) as $m) {
            $month = $this->month($owner, $year, $m);
            $start = CarbonImmutable::create($year, $m, 1);

            $months[$m] = $month !== null && $month['status'] === 'closed'
                ? $this->financials($owner, $start, $start->endOfMonth(), $boatId) + ['closed_at' => $month['closing']->closed_at]
                : null;
        }

        $closed = array_filter($months);
        $totals = [];

        foreach (['gross_sales', 'net_owner_revenue', 'trip_expenses', 'general_expenses', 'depreciation', 'total_expenses', 'net_profit', 'owner_share', 'crew_share'] as $field) {
            $totals[$field] = round(array_sum(array_column($closed, $field)), 2);
        }

        $totals['depreciation_deferred'] = $closed === [] ? 0.0 : (float) $closed[array_key_last($closed)]['depreciation_deferred'];

        return ['year' => $year, 'months' => $months, 'totals' => $totals, 'closed_count' => count($closed)];
    }

    /**
     * تحليل السنة للتقرير المطبوع (annualAnalysis في hispa): الرحلات، ومسيرات
     * الطاقم، والمصروفات بفئاتها ومجموعاتها، والمبيعات، والمصيد، وأرباح كل فرد،
     * والمؤشرات والتوصيات — كلها مقصورة على أشهر السنة المُغلقة.
     *
     * @param  array<string, mixed>  $summary  {@see annualSummary()}
     * @return array<string, mixed>
     */
    public function annualAnalysis(User $owner, array $summary, ?int $boatId = null): array
    {
        $year = $summary['year'];
        $closedMonths = array_keys(array_filter($summary['months']));

        $lines = MonthClosing::forOwner($owner)->where('year', $year)->whereIn('month', $closedMonths ?: [0])
            ->with('boats.payroll.lines')->get()
            ->flatMap(fn (MonthClosing $closing) => $closing->boats
                ->when($boatId, fn ($boats) => $boats->where('boat_id', $boatId))
                ->pluck('payroll')->filter()->flatMap->lines);

        $trips = Trip::forOwner($owner)->when($boatId, fn ($q) => $q->where('boat_id', $boatId))
            ->where(fn ($q) => $closedMonths ? $this->inMonths($q, 'departure_time', $year, $closedMonths) : $q->whereRaw('1 = 0'));
        $total = (clone $trips)->count();
        $sold = (clone $trips)->where('sale_status', Trip::SALE_DONE)->count();
        $cancelled = (clone $trips)->where('status', Trip::CANCELLED)->count();

        [$byCategory, $byType] = $this->annualExpenses($owner, $year, $closedMonths, $boatId);

        return [
            'trips' => ['total' => $total, 'sold' => $sold, 'cancelled' => $cancelled, 'active' => max($total - $sold - $cancelled, 0)],
            'payroll' => [
                'crew_count' => $lines->pluck('fisher_id')->filter()->unique()->count(),
                'crew_pool' => $summary['totals']['crew_share'],
                'owner_share' => $summary['totals']['owner_share'],
                'advances' => round((float) $lines->sum('advances'), 2),
                'paid' => round((float) $lines->filter(fn (PayrollLine $l) => $l->is_paid)->sum('paid_amount'), 2),
                'remaining' => round((float) $lines->reject(fn (PayrollLine $l) => $l->is_paid)->sum('net'), 2),
            ],
            'expenses_by_category' => $byCategory,
            'expenses_by_type' => $byType,
            'sales' => $this->annualSales($owner, $year, $closedMonths, $boatId),
            'catch' => $this->annualCatch($owner, $year, $closedMonths, $boatId),
            'crew_members' => $lines->groupBy(fn (PayrollLine $l) => $l->fisher_id ?? 'n:'.$l->member_name)
                ->map(fn (Collection $group) => [
                    'name' => $group->first()->member_name,
                    'role' => self::role($group->first()->is_captain),
                    'months' => $group->count(),
                    'earned' => round((float) $group->sum(fn (PayrollLine $l) => $l->gross), 2),
                    'advances' => round((float) $group->sum('advances'), 2),
                    'paid' => round((float) $group->filter(fn (PayrollLine $l) => $l->is_paid)->sum('paid_amount'), 2),
                    'remaining' => round((float) $group->reject(fn (PayrollLine $l) => $l->is_paid)->sum('net'), 2),
                ])->sortByDesc('earned')->values()->all(),
            'analysis' => $this->insights($summary, $byCategory),
        ];
    }

    /**
     * @param  array<int, int>  $months
     * @return array{0: array<int, array{name: string, total: float}>, 1: array<int, array{label: string, total: float}>}
     */
    private function annualExpenses(User $owner, int $year, array $months, ?int $boatId): array
    {
        if ($months === []) {
            return [[], []];
        }

        $rows = Expense::forOwner($owner)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->leftJoin('expense_groups', 'expense_groups.id', '=', 'expense_categories.expense_group_id')
            ->when($boatId, fn ($q) => $q->where('expenses.boat_id', $boatId))
            ->where(fn ($q) => $this->inMonths($q, 'expenses.date', $year, $months))
            ->selectRaw('expense_categories.name AS category, expense_groups.name AS grp, SUM(expenses.total) AS total')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_groups.name')
            ->get();

        $byCategory = $rows->map(fn ($r) => ['name' => $r->category ?? '—', 'total' => round((float) $r->total, 2)])
            ->sortByDesc('total')->values()->all();
        $byType = $rows->groupBy(fn ($r) => $r->grp ?? 'أخرى')
            ->map(fn (Collection $group, $label) => ['label' => (string) $label, 'total' => round((float) $group->sum('total'), 2)])
            ->sortByDesc('total')->values()->all();

        return [$byCategory, $byType];
    }

    /**
     * مبيعات الأشهر المُغلقة: بيعك المباشر وسطور مصيدك في فواتير الدلالين.
     *
     * @param  array<int, int>  $months
     * @return array{totals: array<string, float|int>, by_fish: array<int, array<string, mixed>>, top_customers: array<int, array<string, mixed>>, by_boat: array<int, array<string, mixed>>}
     */
    private function annualSales(User $owner, int $year, array $months, ?int $boatId): array
    {
        $empty = ['totals' => ['gross' => 0.0, 'net_owner' => 0.0, 'invoices' => 0, 'avg_invoice' => 0.0], 'by_fish' => [], 'top_customers' => [], 'by_boat' => []];

        if ($months === []) {
            return $empty;
        }

        $tripIds = $boatId ? Trip::where('boat_id', $boatId)->pluck('id') : null;

        // كإجمالي المبيعات في الملخص: بيع مصيد رحلة فقط.
        $direct = Sale::forSeller($owner)->whereNotNull('trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('trip_id', $tripIds))
            ->where(fn ($q) => $this->inMonths($q, 'sold_at', $year, $months))
            ->with(['customer:id,name', 'trip:id,boat_id', 'trip.boat:id,name', 'items.species:id,name_ar'])
            ->get();

        $dalal = SaleItem::soldByDalalFor($owner)
            ->when($tripIds, fn ($q) => $q->whereIn('sale_items.trip_id', $tripIds))
            ->where(fn ($q) => $this->inMonths($q, 'sales.sold_at', $year, $months))
            ->select('sale_items.*', 'sales.seller_id AS dalal_id')
            ->with(['species:id,name_ar', 'trip:id,boat_id', 'trip.boat:id,name'])
            ->get();

        $gross = round((float) $direct->sum('total') + (float) $dalal->sum('total'), 2);
        $invoices = $direct->count() + $dalal->pluck('sale_id')->unique()->count();

        // سطر البيع المباشر بعد توزيع خصم فاتورته عليه، فيطابق مجموع الأصناف الإجمالي.
        $fish = $direct->flatMap(fn (Sale $s) => $s->items->map(fn (SaleItem $i) => [
            'name' => $i->species?->name_ar ?? '—',
            'weight' => (float) $i->weight_kg,
            'total' => (float) $i->total * ((float) $s->subtotal > 0 ? (float) $s->total / (float) $s->subtotal : 1.0),
        ]))
            ->merge($dalal->map(fn (SaleItem $i) => ['name' => $i->species?->name_ar ?? '—', 'weight' => (float) $i->weight_kg, 'total' => (float) $i->total]))
            ->groupBy('name')
            ->map(fn (Collection $g, $name) => ['name' => $name, 'weight' => round($g->sum('weight'), 2), 'total' => round($g->sum('total'), 2)])
            ->sortByDesc('total')->take(10)->values()->all();

        $dalalNames = User::whereIn('id', $dalal->pluck('dalal_id')->unique())->pluck('name', 'id');
        $customers = $direct->groupBy(fn (Sale $s) => 'c:'.($s->customer_id ?? 0))
            ->map(fn (Collection $g) => ['name' => $g->first()->customer?->name ?? 'عميل نقدي', 'invoices' => $g->count(), 'total' => round((float) $g->sum('total'), 2)])
            ->merge($dalal->groupBy(fn (SaleItem $i) => 'd:'.$i->dalal_id)
                ->map(fn (Collection $g) => ['name' => 'الدلال '.($dalalNames[$g->first()->dalal_id] ?? '—'), 'invoices' => $g->pluck('sale_id')->unique()->count(), 'total' => round((float) $g->sum('total'), 2)]))
            ->sortByDesc('total')->take(10)->values()->all();

        $byBoat = [];

        if ($boatId === null) {
            $byBoat = $direct->map(fn (Sale $s) => ['boat' => $s->trip?->boat?->name ?? 'غير محدد', 'sale' => 's'.$s->id, 'total' => (float) $s->total])
                ->merge($dalal->map(fn (SaleItem $i) => ['boat' => $i->trip?->boat?->name ?? 'غير محدد', 'sale' => 'd'.$i->sale_id, 'total' => (float) $i->total]))
                ->groupBy('boat')
                ->map(fn (Collection $g, $name) => ['name' => $name, 'invoices' => $g->pluck('sale')->unique()->count(), 'total' => round($g->sum('total'), 2)])
                ->sortByDesc('total')->values()->all();
        }

        return [
            'totals' => [
                'gross' => $gross,
                'net_owner' => round((float) $direct->sum('owner_net') + (float) $dalal->sum('owner_net'), 2),
                'invoices' => $invoices,
                'avg_invoice' => $invoices > 0 ? round($gross / $invoices, 2) : 0.0,
            ],
            'by_fish' => $fish,
            'top_customers' => $customers,
            'by_boat' => $byBoat,
        ];
    }

    /**
     * مصيد الأشهر المُغلقة: رحلات لها مصيد، ووزنه وقيمته، وأعلى الأنواع قيمةً.
     *
     * @param  array<int, int>  $months
     * @return array{trips_with_catch: int, total_weight: float, total_amount: float, by_species: array<int, array<string, mixed>>}
     */
    private function annualCatch(User $owner, int $year, array $months, ?int $boatId): array
    {
        if ($months === []) {
            return ['trips_with_catch' => 0, 'total_weight' => 0.0, 'total_amount' => 0.0, 'by_species' => []];
        }

        $records = CatchRecord::query()
            ->whereHas('trip', fn ($q) => $q->forOwner($owner)->when($boatId, fn ($q) => $q->where('boat_id', $boatId)))
            ->where(fn ($q) => $this->inMonths($q, 'recorded_at', $year, $months))
            ->with('species:id,name_ar')->get()
            ->map(function (CatchRecord $r) {
                $kg = (float) ($r->counted_kg ?? $r->quantity_kg);

                return ['trip_id' => $r->trip_id, 'name' => $r->species?->name_ar ?? '—', 'weight' => $kg, 'total' => $this->catchValue($r, $kg)];
            });

        return [
            'trips_with_catch' => $records->pluck('trip_id')->unique()->count(),
            'total_weight' => round($records->sum('weight'), 2),
            'total_amount' => round($records->sum('total'), 2),
            'by_species' => $records->groupBy('name')
                ->map(fn (Collection $g, $name) => ['name' => $name, 'weight' => round($g->sum('weight'), 2), 'total' => round($g->sum('total'), 2)])
                ->sortByDesc('total')->take(10)->values()->all(),
        ];
    }

    /**
     * قراءة المحلل للسنة (buildInsights في hispa): أفضل شهر وأضعفه، والهامش،
     * ونسبة المصروفات، والاتجاه، والحكم، والمؤشرات، والتوصيات.
     *
     * @param  array<int, array{name: string, total: float}>  $expensesByCategory
     * @return array<string, mixed>
     */
    private function insights(array $summary, array $expensesByCategory): array
    {
        $closed = array_filter($summary['months']);
        $count = count($closed);
        $t = $summary['totals'];
        $nets = array_map(fn (array $m) => (float) $m['net_profit'], $closed);

        $bestMonth = $nets ? array_search(max($nets), $nets, true) : null;
        $worstMonth = $nets ? array_search(min($nets), $nets, true) : null;
        $avgNet = $count > 0 ? round($t['net_profit'] / $count, 2) : 0.0;
        $margin = $t['gross_sales'] > 0 ? round($t['net_profit'] / $t['gross_sales'] * 100, 1) : 0.0;
        $expenseRatio = $t['gross_sales'] > 0 ? round($t['total_expenses'] / $t['gross_sales'] * 100, 1) : 0.0;
        $profitable = count(array_filter($nets, fn ($n) => $n > 0));
        $losing = count(array_filter($nets, fn ($n) => $n < 0));
        $isProfitable = $t['net_profit'] > 0;

        $trend = 'flat';
        if ($count >= 2) {
            $ordered = array_values($nets);
            $half = max(intdiv($count, 2), 1);
            $diff = array_sum(array_slice($ordered, -$half)) / $half - array_sum(array_slice($ordered, 0, $half)) / $half;
            $threshold = abs($avgNet) * 0.1;
            $trend = $diff > $threshold ? 'up' : ($diff < -$threshold ? 'down' : 'flat');
        }

        // الأرقام معزولة LTR داخل الجملة العربية: بدونها تُطبع "%12" و"1,800.00-".
        $money = fn (float $v) => "\u{2066}".number_format($v, 2)."\u{2069}";
        $pct = fn (float $v) => "\u{2066}{$v}%\u{2069}";

        $insights = [
            match (self::verdict((float) $t['net_profit'])) {
                'رابحة' => 'السنة رابحة بصافي ربح '.$money($t['net_profit']).' ريال وبهامش ربح '.$pct($margin).'.',
                'خاسرة' => 'السنة خاسرة بصافي '.$money($t['net_profit']).' ريال — الإيرادات لم تغطِّ المصروفات.',
                default => 'السنة متعادلة — الإيرادات غطّت المصروفات دون ربح.',
            },
        ];
        if ($bestMonth !== null) {
            $insights[] = 'أفضل شهر: '.self::MONTHS[$bestMonth].' بصافي ربح '.$money($nets[$bestMonth]).' ريال.';
        }
        if ($worstMonth !== null && $worstMonth !== $bestMonth) {
            $insights[] = 'أضعف شهر: '.self::MONTHS[$worstMonth].' بصافي '.$money($nets[$worstMonth]).' ريال.';
        }
        $insights[] = 'متوسط صافي الربح الشهري '.$money($avgNet).' ريال على مدى '.$count.' شهرًا مقفلًا.';
        $insights[] = 'المصروفات تمثل '.$pct($expenseRatio).' من إجمالي المبيعات.';
        $insights[] = match ($trend) {
            'up' => 'الأداء في تحسّن؛ صافي الربح في النصف الثاني من السنة أعلى من النصف الأول.',
            'down' => 'الأداء في تراجع؛ صافي الربح في النصف الثاني من السنة أقل من النصف الأول.',
            default => 'الأداء مستقر تقريبًا على مدار السنة دون تغيّر كبير.',
        };
        $insights[] = $profitable.' شهر رابح مقابل '.$losing.' شهر خاسر من أصل '.$count.' شهر مقفل.';

        $recommendations = array_values(array_filter([
            $t['net_profit'] < 0 ? 'راجع هيكل المصروفات والأسعار؛ السنة أغلقت على خسارة وتحتاج إجراءً تصحيحيًا.' : null,
            $expenseRatio > 70 ? 'نسبة المصروفات مرتفعة ('.$pct($expenseRatio).')؛ ابحث عن بنود يمكن ترشيدها.' : null,
            $margin > 0 && $margin < 15 ? 'هامش الربح ضعيف ('.$pct($margin).')؛ فكّر في رفع الأسعار أو خفض التكاليف.' : null,
            $trend === 'down' ? 'اتجاه الربح للأسفل؛ حلّل أسباب تراجع النصف الثاني من السنة.' : null,
            $losing > 0 ? 'يوجد '.$losing.' شهر خاسر؛ راجع تفاصيلها لتفادي تكرارها.' : null,
            $expensesByCategory ? 'أكبر بند مصروفات هو «'.$expensesByCategory[0]['name'].'» بمبلغ '.$money($expensesByCategory[0]['total']).' ريال؛ ركّز على ضبطه.' : null,
        ]));

        return [
            'closed_count' => $count,
            'best' => $bestMonth !== null ? ['month' => $bestMonth, 'net' => $nets[$bestMonth]] : null,
            'worst' => $worstMonth !== null ? ['month' => $worstMonth, 'net' => $nets[$worstMonth]] : null,
            'avg_net' => $avgNet,
            'margin' => $margin,
            'expense_ratio' => $expenseRatio,
            'profitable_months' => $profitable,
            'loss_months' => $losing,
            'is_profitable' => $isProfitable,
            'verdict' => self::verdict((float) $t['net_profit']),
            'trend' => $trend,
            'insights' => $insights,
            'recommendations' => $recommendations ?: ['المؤشرات المالية للسنة في وضع جيد؛ حافظ على نفس النهج.'],
        ];
    }

    // ─────────────────────────── مصادر مشتركة ───────────────────────────

    /**
     * إجمالي مبيعات مصيدك (مباشرة وعبر الدلال)، واقتطاعها، وصافيك منها.
     *
     * @return array{gross: float, cut: float, net: float}
     */
    private function salesTotals(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId): array
    {
        $sales = $this->salesByTrip($owner, $from, $to, $boatId ? Trip::where('boat_id', $boatId)->pluck('id') : null);
        $gross = round($sales->sum('direct_gross') + $sales->sum('dalal_gross'), 2);
        $net = round($sales->sum('direct_net') + $sales->sum('dalal_net'), 2);

        return ['gross' => $gross, 'cut' => round($gross - $net, 2), 'net' => $net];
    }

    /**
     * المبيعات لكل رحلة: المباشرة (من الفاتورة) وسطور الدلال (من سطور المالك).
     *
     * @return Collection<int, array<string, float>>
     */
    private function salesByTrip(User $owner, ?CarbonImmutable $from, ?CarbonImmutable $to, ?Collection $tripIds = null): Collection
    {
        $range = fn ($q) => $q
            ->when($from, fn ($q) => $q->where('sales.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.sold_at', '<=', $to));

        $direct = Sale::where('sales.seller_id', $owner->id)->whereNotNull('sales.trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('sales.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sales.trip_id AS trip_id, SUM(sales.total) AS gross, SUM(sales.owner_net) AS net')
            ->groupBy('sales.trip_id')->get()->keyBy('trip_id');

        $dalal = SaleItem::soldByDalalFor($owner)->whereNotNull('sale_items.trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('sale_items.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.trip_id AS trip_id, SUM(sale_items.total) AS gross, SUM(sale_items.owner_net) AS net')
            ->groupBy('sale_items.trip_id')->get()->keyBy('trip_id');

        return $direct->keys()->merge($dalal->keys())->unique()->mapWithKeys(fn ($tripId) => [(int) $tripId => [
            'direct_gross' => round((float) ($direct[$tripId]->gross ?? 0), 2),
            'direct_net' => round((float) ($direct[$tripId]->net ?? 0), 2),
            'dalal_gross' => round((float) ($dalal[$tripId]->gross ?? 0), 2),
            'dalal_net' => round((float) ($dalal[$tripId]->net ?? 0), 2),
        ]]);
    }

    /**
     * المباع لكل نوع: السطور المباشرة بعد توزيع خصم فاتورتها، وسطور الدلال.
     *
     * @return array<int, array{species_id: int, kg: float, gross: float}>
     */
    private function speciesSold(User $owner, ?CarbonImmutable $from, ?CarbonImmutable $to, ?Collection $tripIds = null): array
    {
        $range = fn ($q) => $q
            ->when($from, fn ($q) => $q->where('sales.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.sold_at', '<=', $to));

        $direct = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.seller_id', $owner->id)
            ->when($tripIds, fn ($q) => $q->whereIn('sales.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.species_id AS species_id, SUM(sale_items.weight_kg) AS kg, SUM(CASE WHEN sales.subtotal > 0 THEN sale_items.total * sales.total / sales.subtotal ELSE sale_items.total END) AS gross')
            ->groupBy('sale_items.species_id')->get()->keyBy('species_id');

        $dalal = SaleItem::soldByDalalFor($owner)
            ->when($tripIds, fn ($q) => $q->whereIn('sale_items.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.species_id AS species_id, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS gross')
            ->groupBy('sale_items.species_id')->get()->keyBy('species_id');

        return $direct->keys()->merge($dalal->keys())->unique()->map(fn ($id) => [
            'species_id' => (int) $id,
            'kg' => round((float) ($direct[$id]->kg ?? 0) + (float) ($dalal[$id]->kg ?? 0), 2),
            'gross' => round((float) ($direct[$id]->gross ?? 0) + (float) ($dalal[$id]->gross ?? 0), 2),
        ])->values()->all();
    }

    /**
     * يقصر الاستعلام على أشهر من السنة (whereDate: عمود `expenses.date` يخزّن
     * وقتًا أيضًا).
     *
     * @param  array<int, int>  $months
     */
    private function inMonths(Builder $query, string $column, int $year, array $months): void
    {
        foreach ($months as $m) {
            $start = CarbonImmutable::create($year, $m, 1);
            $query->orWhere(fn ($q) => $q->whereDate($column, '>=', $start->toDateString())->whereDate($column, '<=', $start->endOfMonth()->toDateString()));
        }
    }

    /**
     * قيمة سطر المصيد — قاعدة واحدة لكل التقارير (والإنتاج بـSQL مثلها):
     * الوزن (المعدود وإلا المعلن) × سعر الكيلو، وبلا سعر `total_value` المخزّن.
     */
    private function catchValue(CatchRecord $record, float $kg): float
    {
        return round($record->price_per_kg !== null ? $kg * (float) $record->price_per_kg : (float) ($record->total_value ?? 0), 2);
    }

    private function paymentStatus(float $paid, float $total): string
    {
        return match (true) {
            $total > 0 && $paid >= $total => 'مدفوع',
            $paid > 0 => 'مدفوع جزئيًا',
            default => 'غير مدفوع',
        };
    }

    /**
     * @return array<string, float>
     */
    private function emptySales(): array
    {
        return array_fill_keys(['direct_gross', 'direct_net', 'dalal_gross', 'dalal_net'], 0.0);
    }

    private function margin(float $profit, float $net): float
    {
        return $net > 0 ? round($profit / $net * 100, 2) : 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $keys
     * @return array<string, float>
     */
    private function totals(array $rows, array $keys): array
    {
        return collect($keys)->mapWithKeys(fn ($key) => [$key => round(array_sum(array_column($rows, $key)), 2)])->all();
    }
}
