<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\CrewAdvance;
use App\Models\Fisher;
use App\Models\PaymentStatus;
use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مسيرات رواتب الطاقم — منطق PayrollController/PayrollService في hispa:
 *
 * - مسير لكل قارب × شهر، أفراده صيّادو القارب النشطون ذوو إعداد أجر.
 * - صاحب الراتب الثابت يأخذ راتبه، ورواتب المسير الثابتة تُرحَّل مصروفًا على
 *   القارب فتنقص ربحه قبل التوزيع (كما في calculateBoatPayroll).
 * - أصحاب النسبة يتقاسمون نصيب الطاقم ({@see CrewPool}).
 * - السلف تُخصم من أول مسير غير مسدَّد للفرد لشهرها أو بعده، ولا يُخصم أكثر
 *   من مستحقه — الباقي ينتقل للمسير التالي.
 * - السطر غير المسدَّد يُعاد حسابه كلما فُتح المسير (بيع أو مصروف أو سلفة
 *   أُضيفت بعده)، والمسدَّد يُجمَّد.
 * - مسير الشهر المُغلق (O4) مجمَّد الأرقام كله: لا إعادة حساب ولا تعديل ولا
 *   حذف — يبقى سداده وخصم السلف من سطوره غير المسدَّدة.
 */
class PayrollService
{
    public function __construct(
        private readonly CrewPool $pool,
        private readonly ExpenseService $expenses,
        private readonly MonthLock $lock,
    ) {}

    public function generate(User $owner, Boat $boat, int $year, int $month): Payroll
    {
        if (CarbonImmutable::create($year, $month, 1)->isAfter(now()->startOfMonth())) {
            throw ValidationException::withMessages(['period' => 'لا يُنشأ مسير لشهر لم يبدأ بعد.']);
        }

        $this->lock->ensureOpen($owner->id, CarbonImmutable::create($year, $month, 1), 'period');

        if (Payroll::forOwner($owner)->where('boat_id', $boat->id)->where('year', $year)->where('month', $month)->exists()) {
            throw ValidationException::withMessages(['period' => "مسير {$boat->name} لهذا الشهر موجود."]);
        }

        if ($this->members($owner, $boat->id)->isEmpty()) {
            throw ValidationException::withMessages(['boat_id' => "لا أفراد على {$boat->name} بإعداد أجر — اضبطه من صفحة أجور الطاقم."]);
        }

        return DB::transaction(function () use ($owner, $boat, $year, $month) {
            $payroll = Payroll::create([
                'payroll_number' => Payroll::nextNumber(),
                'owner_id' => $owner->id,
                'boat_id' => $boat->id,
                'boat_name' => $boat->name,
                'year' => $year,
                'month' => $month,
                'owner_share_percent' => $boat->owner_share_percent ?? $boat->fresh()->owner_share_percent,
                'payment_status_id' => PaymentStatus::named(ExpenseService::UNPAID)->id,
                'created_by' => $owner->id,
            ]);

            $this->refresh($payroll, $owner);

            $this->log('إنشاء مسير', $owner, $payroll, "{$payroll->boat_name} — {$payroll->period_label}: نصيب الطاقم {$payroll->crew_pool}");

            return $payroll;
        });
    }

    /**
     * يعيد حساب المسير ما دام فيه سطر غير مسدَّد: أفراده، رواتبه الثابتة
     * وترحيلها، أرقام الشهر ونصيب الطاقم، الحصص، ثم السلف.
     */
    public function refresh(Payroll $payroll, User $by): Payroll
    {
        $payroll->unsetRelation('lines');

        if ($payroll->boat_id === null || $payroll->is_fully_paid || $this->isClosed($payroll)) {
            return $payroll;
        }

        DB::transaction(function () use ($payroll, $by) {
            $owner = $payroll->owner;
            $this->syncMembers($payroll, $owner);

            $lines = $payroll->lines()->with('payType')->get();

            foreach ($lines->reject(fn (PayrollLine $line) => $line->is_paid || $line->payType->isShare()) as $line) {
                $line->update(['base_amount' => (float) $line->fixed_salary]);
            }

            // الرواتب الثابتة تُرحَّل أولًا فيحسبها ربح الشهر قبل التوزيع.
            $this->expenses->syncSource($payroll, $by);

            if (! $payroll->has_payments) {
                $payroll->owner_share_percent = (float) ($payroll->boat?->owner_share_percent ?? $payroll->owner_share_percent);
            }

            $broughtForward = $this->lock->broughtForward($owner->id, $payroll->boat_id, $payroll->year, $payroll->month);
            $figures = $this->pool->forBoatMonth($owner, $payroll->boat_id, $payroll->year, $payroll->month, (float) $payroll->owner_share_percent, $broughtForward);
            $payroll->fill($figures)->save();

            $shareLines = $lines->filter(fn (PayrollLine $line) => $line->payType->isShare());
            $dues = $this->pool->distribute($figures['crew_pool'], $shareLines->map(fn (PayrollLine $line) => [
                'key' => $line->id,
                'shares' => (float) $line->profit_shares,
                'custom_percent' => $line->custom_share_percent,
            ]));

            foreach ($shareLines->reject(fn (PayrollLine $line) => $line->is_paid) as $line) {
                $line->update(['base_amount' => $dues[$line->id] ?? 0]);
            }

            $lines->pluck('fisher_id')->filter()->unique()->each(fn (int $fisherId) => $this->planAdvances($fisherId));

            $this->syncStatus($payroll);
        });

        return $payroll->refresh();
    }

    /**
     * زيادة / خصم / ملاحظة على سطر غير مسدَّد.
     */
    public function updateLine(PayrollLine $line, array $data, User $by): PayrollLine
    {
        if ($line->is_paid) {
            throw ValidationException::withMessages(['line' => 'سُدِّد هذا السطر — لا يُعدَّل.']);
        }

        $this->lock->ensureOpen($line->payroll->owner_id, $line->payroll->period_start, 'line');

        $bonus = round((float) ($data['bonus'] ?? 0), 2);
        $deduction = round((float) ($data['deduction'] ?? 0), 2);

        if ($deduction > $line->base_amount + $bonus) {
            throw ValidationException::withMessages(['deduction' => 'الخصم لا يتجاوز المستحق ('.number_format($line->base_amount + $bonus, 2).' ر.س).']);
        }

        $line->update(['bonus' => $bonus, 'deduction' => $deduction, 'notes' => $data['notes'] ?? null]);

        $this->refresh($line->payroll, $by);
        $this->log('تعديل سطر مسير', $by, $line->payroll, "{$line->member_name}: زيادة {$bonus} — خصم {$deduction}");

        return $line->refresh();
    }

    /**
     * سداد سطر: يُعاد حساب المسير أولًا ثم يُجمَّد صافيه وسلفه المخصومة.
     */
    public function payLine(PayrollLine $line, ?int $paymentMethodId, User $by): PayrollLine
    {
        $this->refresh($line->payroll, $by);
        $line->refresh();

        if ($line->is_paid) {
            throw ValidationException::withMessages(['line' => "سُدِّد {$line->member_name} من قبل."]);
        }

        DB::transaction(function () use ($line, $paymentMethodId, $by) {
            $line->update([
                'paid_at' => now(),
                'paid_amount' => max($line->net, 0),
                'payment_method_id' => $paymentMethodId,
            ]);

            $payroll = $line->payroll;
            $this->expenses->syncSource($payroll, $by);
            $this->syncStatus($payroll);

            if ($line->fisher_id) {
                $this->planAdvances($line->fisher_id);
            }

            $this->log('سداد راتب', $by, $payroll, "{$line->member_name}: {$line->paid_amount} ر.س (سلف مخصومة {$line->advances})");
        });

        return $line;
    }

    /**
     * سداد كل السطور المتبقية بطريقة دفع واحدة.
     */
    public function payAll(Payroll $payroll, ?int $paymentMethodId, User $by): int
    {
        $this->refresh($payroll, $by);
        $paid = 0;

        foreach ($payroll->lines()->whereNull('paid_at')->get() as $line) {
            $this->payLine($line, $paymentMethodId, $by);
            $paid++;
        }

        return $paid;
    }

    public function delete(Payroll $payroll, User $by): void
    {
        if ($payroll->has_payments) {
            throw ValidationException::withMessages(['payroll' => 'سُدِّد جزء من هذا المسير — لا يُحذف.']);
        }

        $this->lock->ensureOpen($payroll->owner_id, $payroll->period_start, 'payroll');

        DB::transaction(function () use ($payroll, $by) {
            $fisherIds = $payroll->lines()->pluck('fisher_id')->filter();

            $this->expenses->releaseSource($payroll, $by);
            $payroll->delete();

            $fisherIds->each(fn (int $fisherId) => $this->planAdvances($fisherId));

            $this->log('حذف مسير', $by, $payroll, "{$payroll->boat_name} — {$payroll->period_label}");
        });
    }

    /**
     * يوزّع سلف الفرد على سطوره غير المسدَّدة من الأقدم: كل سطر يخصم ما
     * تبقّى من سلف مؤرخة حتى نهاية شهره، بحد مستحقه.
     */
    public function planAdvances(int $fisherId): void
    {
        $advances = CrewAdvance::where('fisher_id', $fisherId)->get(['date', 'amount']);
        $deducted = (float) PayrollLine::where('fisher_id', $fisherId)->whereNotNull('paid_at')->sum('advances');

        $open = PayrollLine::where('fisher_id', $fisherId)->whereNull('paid_at')->with('payroll')->get()
            ->sortBy(fn (PayrollLine $line) => $line->payroll->period_key);

        foreach ($open as $line) {
            $end = $line->payroll->period_end;
            $available = $advances->filter(fn (CrewAdvance $advance) => $advance->date->lte($end))->sum('amount') - $deducted;
            $take = round(min(max($available, 0), max($line->gross, 0)), 2);

            $line->update(['advances' => $take, 'net' => round($line->gross - $take, 2)]);
            $deducted += $take;
        }
    }

    /**
     * أفراد القارب الذين يدخلون مسيره: صيّادو المالك النشطون عليه بإعداد أجر.
     *
     * @return Collection<int, Fisher>
     */
    public function members(User $owner, int $boatId): Collection
    {
        return Fisher::forOwner($owner)
            ->where('boat_id', $boatId)
            ->where('status', 'نشط')
            ->whereNotNull('pay_type_id')
            ->orderByDesc('is_captain')
            ->orderBy('name')
            ->get();
    }

    /**
     * السطور غير المسدَّدة تتبع أفراد القارب الآن وإعداد أجرهم.
     */
    private function syncMembers(Payroll $payroll, User $owner): void
    {
        $members = $this->members($owner, $payroll->boat_id)->keyBy('id');
        $lines = $payroll->lines()->get()->keyBy('fisher_id');

        foreach ($lines as $fisherId => $line) {
            if (! $line->is_paid && ! $members->has($fisherId)) {
                $line->delete();
            }
        }

        foreach ($members as $fisher) {
            $line = $lines->get($fisher->id);

            if ($line?->is_paid) {
                continue;
            }

            $settings = [
                'member_name' => $fisher->name,
                'is_captain' => $fisher->is_captain,
                'pay_type_id' => $fisher->pay_type_id,
                'fixed_salary' => $fisher->fixed_salary,
                'profit_shares' => $fisher->profit_shares,
                'custom_share_percent' => $fisher->custom_share_percent > 0 ? $fisher->custom_share_percent : null,
            ];

            $line ? $line->update($settings) : $payroll->lines()->create(['fisher_id' => $fisher->id] + $settings);
        }
    }

    public function isClosed(Payroll $payroll): bool
    {
        return $this->lock->isClosed($payroll->owner_id, $payroll->year, $payroll->month);
    }

    private function syncStatus(Payroll $payroll): void
    {
        $lines = $payroll->lines()->get();
        $paid = $lines->filter(fn (PayrollLine $line) => $line->is_paid)->count();

        $status = match (true) {
            $lines->isNotEmpty() && $paid === $lines->count() => ExpenseService::PAID,
            $paid > 0 => ExpenseService::PARTIAL,
            default => ExpenseService::UNPAID,
        };

        $payroll->update(['payment_status_id' => PaymentStatus::named($status)->id]);
        $payroll->unsetRelation('lines');
    }

    private function log(string $action, User $by, Payroll $payroll, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'Payroll',
            'record_label' => $payroll->payroll_number,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
