<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\DalalInvoiceReview;
use App\Models\Expense;
use App\Models\Fisher;
use App\Models\MonthClosing;
use App\Models\MonthClosingBoat;
use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\PayType;
use App\Models\Trip;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إغلاق الشهر — MonthClosingService في hispa على بيانات hawat:
 *
 * - معاينة ثم إغلاق: أرقام كل قارب من `CrewPool` (الإيراد، المصروفات،
 *   الإهلاك ومؤجَّله الداخل والخارج، الصافي، نصيب المالك والطاقم)، وما لا
 *   قارب له (المصروفات العامة وإهلاك الأصول غير المربوطة) يُنقص صافي المالك
 *   وحده. الإغلاق يثبّتها لقطةً لا تتغير بتعديل لاحق.
 * - مستحقات الطاقم هي سطور مسيرات O3: الإغلاق يُنشئ مسير كل قارب له أفراد
 *   ولا مسير له، ويحدّث المفتوحة بالمؤجَّل الداخل، ثم يجمّدها ({@see MonthLock}).
 * - الإغلاق بالتسلسل (أول إغلاق لأي شهر منتهٍ، ثم الشهر التالي فالتالي)
 *   فينتقل مؤجَّل الإهلاك من شهر إلى الذي يليه بلا ثغرة، ولا يُعاد فتح إلا
 *   آخر شهر مُغلق.
 *
 * خروج عن hispa: إغلاق واحد للأسطول كله لكل شهر بسطر لكل قارب (hispa كان
 * يُغلق قاربًا أو الأسطول كلٌّ على حدة فتتداخل الأرقام)، والقفل فعلي في
 * الخدمات لا إخفاءً من القوائم.
 */
class MonthClosingService
{
    /** أرقام القارب المحفوظة في سطره من الإغلاق. */
    private const BOAT_FIGURES = [
        'revenue', 'expenses', 'depreciation_own', 'depreciation_brought_forward', 'depreciation',
        'depreciation_charged', 'depreciation_deferred', 'net_profit', 'owner_share_percent', 'owner_share', 'crew_pool',
    ];

    public function __construct(
        private readonly CrewPool $pool,
        private readonly PayrollService $payrolls,
        private readonly AssetDepreciation $depreciation,
        private readonly MonthLock $lock,
    ) {}

    /**
     * الشهر الذي يُغلق تاليًا: ما بعد آخر إغلاق، أو الشهر الماضي إن لم يُغلق
     * شيء بعد. null = لم ينتهِ الشهر التالي بعد.
     */
    public function nextPeriod(User $owner): ?CarbonImmutable
    {
        $latest = $this->lock->latest($owner->id);
        $next = $latest ? $latest->period_start->addMonth() : CarbonImmutable::now()->startOfMonth()->subMonth();

        return $next->endOfMonth()->isPast() ? $next : null;
    }

    public function assertClosable(User $owner, int $year, int $month): void
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $label = MonthClosing::label($year, $month);

        if (! $start->endOfMonth()->isPast()) {
            throw ValidationException::withMessages(['period' => "لا يُغلق {$label} قبل أن ينتهي."]);
        }

        if ($this->lock->isClosed($owner->id, $year, $month)) {
            throw ValidationException::withMessages(['period' => "{$label} مُغلق من قبل."]);
        }

        $latest = $this->lock->latest($owner->id);

        if ($latest && ! $start->equalTo($latest->period_start->addMonth())) {
            $next = $latest->period_start->addMonth();

            throw ValidationException::withMessages(['period' => 'الإغلاق بالتسلسل: آخر شهر مُغلق '.$latest->period_label.'، فالتالي '.MonthClosing::label($next->year, $next->month).'.']);
        }
    }

    /**
     * أرقام الشهر كما ستُغلق (لا يُحفظ شيء سوى تحديث المسيرات المفتوحة، كما
     * تفعل صفحة المسير عند فتحها). التقارير تمرّر `$refreshPayrolls = false`
     * فتقرأ الشهر ولا تكتب شيئًا.
     *
     * @return array{year: int, month: int, label: string, boats: array<int, array<string, mixed>>, general: array{expenses: float, depreciation: float, assets: array<int, array<string, mixed>>}, totals: array<string, float>, warnings: array<int, string>}
     */
    public function preview(User $owner, int $year, int $month, bool $refreshPayrolls = true): array
    {
        $from = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $to = $from->endOfMonth();

        $assets = collect($this->depreciation->forMonth($owner, $year, $month)['assets'])
            ->groupBy(fn (array $asset) => $asset['boat_id'] ?? 0);

        $boats = [];
        $warnings = [];

        foreach (Boat::forOwner($owner)->orderBy('name')->get() as $boat) {
            $row = $this->boatRow($owner, $boat, $year, $month, $assets->get($boat->id, collect())->values()->all(), $warnings, $refreshPayrolls);

            if ($row !== null) {
                $boats[] = $row;
            }
        }

        $generalAssets = $assets->get(0, collect())->values()->all();
        $general = [
            'expenses' => round((float) Expense::forOwner($owner)->whereNull('boat_id')
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString())
                ->sum('total'), 2),
            'depreciation' => round(array_sum(array_column($generalAssets, 'amount')), 2),
            'assets' => $generalAssets,
        ];

        return [
            'year' => $year,
            'month' => $month,
            'label' => MonthClosing::label($year, $month),
            'boats' => $boats,
            'general' => $general,
            'totals' => $this->totals($boats, $general),
            'warnings' => array_merge($warnings, $this->monthWarnings($owner, $from, $to)),
        ];
    }

    public function close(User $owner, int $year, int $month, ?string $notes = null): MonthClosing
    {
        $this->assertClosable($owner, $year, $month);

        return DB::transaction(function () use ($owner, $year, $month, $notes) {
            // مسير لكل قارب له أفراد في الشهر ولم يُنشأ مسيره — مستحقات طاقمه.
            foreach ($this->preview($owner, $year, $month)['boats'] as $row) {
                if ($row['payroll'] === null && $row['dues'] !== []) {
                    $this->payrolls->generate($owner, Boat::findOrFail($row['boat_id']), $year, $month);
                }
            }

            $preview = $this->preview($owner, $year, $month);
            $totals = $preview['totals'];

            $closing = MonthClosing::create([
                'owner_id' => $owner->id,
                'year' => $year,
                'month' => $month,
                'revenue' => $totals['revenue'],
                'boat_expenses' => $totals['expenses'],
                'depreciation' => $totals['depreciation'],
                'depreciation_charged' => $totals['depreciation_charged'],
                'depreciation_deferred' => $totals['depreciation_deferred'],
                'net_profit' => $totals['net_profit'],
                'owner_share' => $totals['owner_share'],
                'crew_pool' => $totals['crew_pool'],
                'general_expenses' => $preview['general']['expenses'],
                'general_depreciation' => $preview['general']['depreciation'],
                'general_assets' => $preview['general']['assets'],
                'owner_net' => $totals['owner_net'],
                'notes' => $notes,
                'closed_by' => $owner->id,
                'closed_at' => now(),
            ]);

            foreach ($preview['boats'] as $row) {
                $closing->boats()->create([
                    'boat_id' => $row['boat_id'],
                    'boat_name' => $row['boat_name'],
                    'payroll_id' => $row['payroll']?->id,
                    'assets' => $row['assets'],
                ] + array_intersect_key($row, array_flip(self::BOAT_FIGURES)));
            }

            $this->log('إغلاق شهر', $owner, $closing, "صافي المالك {$closing->owner_net} — نصيب الطاقم {$closing->crew_pool} — مؤجَّل الإهلاك {$closing->depreciation_deferred}");

            return $closing;
        });
    }

    /**
     * إعادة فتح آخر شهر مُغلق: تُحذف اللقطة فيُعاد حساب الشهر ومسيراته غير
     * المسدَّدة، وما سُدِّد يبقى مسدَّدًا.
     */
    public function reopen(MonthClosing $closing, User $by): void
    {
        $latest = $this->lock->latest($closing->owner_id);

        if ($latest && $latest->id !== $closing->id) {
            throw ValidationException::withMessages(['closing' => 'يُعاد فتح آخر شهر مُغلق فقط ('.$latest->period_label.') — الأشهر بعده بُنيت على أرقامه.']);
        }

        DB::transaction(function () use ($closing, $by) {
            $closing->delete();
            $this->log('إعادة فتح شهر', $by, $closing, 'حُذفت لقطة الإغلاق');
        });
    }

    /**
     * الشهر المُغلق بصيغة المعاينة نفسها: الأرقام من اللقطة، والمستحقات من
     * سطور المسيرات (سدادها حيّ).
     *
     * @return array<string, mixed>
     */
    public function present(MonthClosing $closing): array
    {
        $closing->loadMissing('boats.payroll');

        $boats = $closing->boats->sortBy('boat_name')->map(fn (MonthClosingBoat $boat) => [
            'boat_id' => $boat->boat_id,
            'boat_name' => $boat->boat_name,
            'payroll' => $boat->payroll,
            'pending_fixed' => 0.0,
            'assets' => $boat->assets ?? [],
            'dues' => $boat->payroll ? $this->payrollDues($boat->payroll) : [],
        ] + $boat->only(self::BOAT_FIGURES))->values()->all();

        $general = [
            'expenses' => $closing->general_expenses,
            'depreciation' => $closing->general_depreciation,
            'assets' => $closing->general_assets ?? [],
        ];

        return [
            'year' => $closing->year,
            'month' => $closing->month,
            'label' => $closing->period_label,
            'boats' => $boats,
            'general' => $general,
            'totals' => [
                'revenue' => $closing->revenue,
                'expenses' => $closing->boat_expenses,
                'depreciation' => $closing->depreciation,
                'depreciation_charged' => $closing->depreciation_charged,
                'depreciation_deferred' => $closing->depreciation_deferred,
                'net_profit' => $closing->net_profit,
                'owner_share' => $closing->owner_share,
                'crew_pool' => $closing->crew_pool,
                'owner_net' => $closing->owner_net,
            ],
            'warnings' => [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $assets  أقساط أصول القارب في الشهر
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null null = لا نشاط للقارب في الشهر
     */
    private function boatRow(User $owner, Boat $boat, int $year, int $month, array $assets, array &$warnings, bool $refreshPayroll = true): ?array
    {
        $payroll = Payroll::forOwner($owner)->where('boat_id', $boat->id)->where('year', $year)->where('month', $month)->first();

        if ($payroll && $refreshPayroll) {
            $payroll = $this->payrolls->refresh($payroll, $owner);
        }

        $members = $payroll ? collect() : $this->payrolls->members($owner, $boat->id)->load('payType');
        $pending = round($members->reject(fn (Fisher $fisher) => $fisher->payType?->isShare())->sum(fn (Fisher $fisher) => (float) $fisher->fixed_salary), 2);

        $broughtForward = $this->lock->broughtForward($owner->id, $boat->id, $year, $month);
        $percent = $payroll ? (float) $payroll->owner_share_percent : (float) $boat->owner_share_percent;
        $figures = $this->pool->forBoatMonth($owner, $boat->id, $year, $month, $percent, $broughtForward, $pending);

        if ($payroll === null && $pending <= 0 && $figures['revenue'] == 0 && $figures['expenses'] == 0 && $figures['depreciation'] == 0) {
            return null;
        }

        if ($payroll) {
            $dues = $this->payrollDues($payroll);
            $hasShareMembers = $payroll->lines->contains(fn (PayrollLine $line) => $line->payType?->isShare());

            if ($payroll->is_fully_paid && abs($payroll->crew_pool - $figures['crew_pool']) > 0.009) {
                $warnings[] = "مسير {$boat->name} ({$payroll->payroll_number}) سُدِّد كاملًا على نصيب طاقم ".number_format($payroll->crew_pool, 2).' ر.س، والنصيب الآن '.number_format($figures['crew_pool'], 2).' ر.س — تغيّرت أرقام الشهر بعد السداد.';
            }
        } else {
            $shares = $members->filter(fn (Fisher $fisher) => $fisher->payType?->isShare());
            $amounts = $this->pool->distribute($figures['crew_pool'], $shares->map(fn (Fisher $fisher) => [
                'key' => $fisher->id,
                'shares' => (float) $fisher->profit_shares,
                'custom_percent' => $fisher->custom_share_percent > 0 ? (float) $fisher->custom_share_percent : null,
            ]));
            $hasShareMembers = $shares->isNotEmpty();

            $dues = $members->map(fn (Fisher $fisher) => [
                'fisher_id' => $fisher->id,
                'name' => $fisher->name,
                'is_captain' => $fisher->is_captain,
                'is_share' => (bool) $fisher->payType?->isShare(),
                'shares' => (float) $fisher->profit_shares,
                'custom_percent' => $fisher->custom_share_percent > 0 ? (float) $fisher->custom_share_percent : null,
                'pay' => $this->payLabel($fisher->payType, (float) $fisher->fixed_salary, (float) $fisher->profit_shares, (float) $fisher->custom_share_percent),
                'gross' => $fisher->payType?->isShare() ? ($amounts[$fisher->id] ?? 0.0) : (float) $fisher->fixed_salary,
                'advances' => null,
                'net' => null,
                'paid' => null,
            ])->sortByDesc('is_captain')->values()->all();
        }

        if ($figures['crew_pool'] > 0 && ! $hasShareMembers) {
            $warnings[] = "نصيب طاقم {$boat->name} (".number_format($figures['crew_pool'], 2).' ر.س) بلا مستحق — لا أفراد بالنسبة عليه. اضبط نسبة المالك أو أجور الطاقم قبل الإغلاق.';
        }

        return [
            'boat_id' => $boat->id,
            'boat_name' => $boat->name,
            'payroll' => $payroll,
            'depreciation_own' => round(array_sum(array_column($assets, 'amount')), 2),
            'depreciation_brought_forward' => $broughtForward,
            'pending_fixed' => $pending,
            'assets' => $assets,
            'dues' => $dues,
        ] + $figures;
    }

    /**
     * @return array<int, array{fisher_id: ?int, name: string, is_captain: bool, is_share: bool, shares: float, custom_percent: ?float, pay: string, gross: float, advances: ?float, net: ?float, paid: ?bool}>
     */
    private function payrollDues(Payroll $payroll): array
    {
        return $payroll->lines()->with('payType')->get()
            ->sortBy([['is_captain', 'desc'], ['member_name', 'asc']])
            ->map(fn (PayrollLine $line) => [
                'fisher_id' => $line->fisher_id,
                'name' => $line->member_name,
                'is_captain' => $line->is_captain,
                'is_share' => (bool) $line->payType?->isShare(),
                'shares' => (float) $line->profit_shares,
                'custom_percent' => $line->custom_share_percent > 0 ? (float) $line->custom_share_percent : null,
                'pay' => $this->payLabel($line->payType, (float) $line->fixed_salary, (float) $line->profit_shares, (float) $line->custom_share_percent),
                'gross' => $line->gross,
                'advances' => (float) $line->advances,
                'net' => (float) $line->net,
                'paid' => $line->is_paid,
            ])->values()->all();
    }

    private function payLabel(?PayType $type, float $fixed, float $shares, float $custom): string
    {
        $trim = fn (float $value) => rtrim(rtrim(number_format($value, 2), '0'), '.');

        return match (true) {
            ! $type?->isShare() => 'راتب ثابت '.number_format($fixed, 2),
            $custom > 0 => 'نسبة خاصة '.$trim($custom).'%',
            default => $trim($shares).' سهم',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $boats
     * @param  array{expenses: float, depreciation: float}  $general
     * @return array<string, float>
     */
    private function totals(array $boats, array $general): array
    {
        $sum = fn (string $key) => round(array_sum(array_column($boats, $key)), 2);

        $totals = [];
        foreach (['revenue', 'expenses', 'depreciation', 'depreciation_charged', 'depreciation_deferred', 'net_profit', 'owner_share', 'crew_pool'] as $key) {
            $totals[$key] = $sum($key);
        }

        $totals['owner_net'] = round($totals['owner_share'] - $general['expenses'] - $general['depreciation'], 2);

        return $totals;
    }

    /**
     * @return array<int, string>
     */
    private function monthWarnings(User $owner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $warnings = [];

        $unsold = Trip::forOwner($owner)
            ->whereBetween('departure_time', [$from, $to])
            ->where('status', '!=', Trip::CANCELLED)
            ->where('sale_status', '!=', Trip::SALE_DONE)
            ->count();

        if ($unsold > 0) {
            $warnings[] = "{$unsold} من رحلات الشهر لم يُبع مصيدها كله — ما يُباع بعد الإغلاق يدخل شهر بيعه.";
        }

        $unpaid = (float) Expense::forOwner($owner)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->whereColumn('paid_amount', '<', 'total')
            ->sum(DB::raw('total - paid_amount'));

        if ($unpaid > 0) {
            $warnings[] = 'مصروفات الشهر غير المسدَّدة '.number_format($unpaid, 2).' ر.س — سدادها يبقى متاحًا بعد الإغلاق.';
        }

        // فواتير الدلال تدخل إيراد الشهر بصافيها قُبلت أو لم تُقبل؛ التحذير ليراجعها قبل تثبيت الأرقام.
        $unreviewed = DalalInvoiceReview::forOwner($owner)
            ->whereIn('status', [DalalInvoiceReview::PENDING, DalalInvoiceReview::REJECTED])
            ->whereHas('sale', fn ($q) => $q->whereBetween('sold_at', [$from, $to]))
            ->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');

        if ($unreviewed->sum() > 0) {
            $warnings[] = 'فواتير دلالين في الشهر لم تُقبل بعد: '.(int) ($unreviewed[DalalInvoiceReview::PENDING] ?? 0).' قيد المراجعة و'.(int) ($unreviewed[DalalInvoiceReview::REJECTED] ?? 0).' مرفوضة — صافيها داخل في الإيراد كما سجّله الدلال.';
        }

        return $warnings;
    }

    private function log(string $action, User $by, MonthClosing $closing, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'MonthClosing',
            'record_label' => $closing->period_label,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
