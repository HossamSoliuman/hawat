<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\Fisher;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\OwnerReports;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * تقارير المالك (O6) بنسق hispa: المركز بمجموعاته، وكل تقرير صفحة ويب
 * (عنوان، ثم تصفية بـ"تحديث" و"طباعة"، ثم جدوله أو قائمته) وورقة A4 للطباعة
 * بالتصفية نفسها. الفترة الافتراضية الشهر الجاري كما في hispa.
 */
class ReportController extends Controller
{
    private OwnerReports $reports;

    public function index(): View
    {
        return view('panel.owner.reports.index', ['reports' => OwnerReports::REPORTS]);
    }

    /**
     * `OwnerReports` يُحقن في الطلب لا في المُنشئ: Laravel يحتفظ بالمتحكم على
     * المسار، فذاكرة أشهره كانت ستبقى من طلب إلى طلب (Octane والاختبارات).
     */
    public function show(Request $request, OwnerReports $reports, string $report, ?string $mode = null): View
    {
        $this->reports = $reports;
        $owner = $request->user();
        $print = $mode === 'print';

        $data = match ($report) {
            'trip-report' => $this->tripReport($request, $owner),
            'sales-report' => $this->salesReport($request, $owner),
            'profit-loss', 'month-summary' => $this->financial($request, $owner, $report),
            'annual-summary' => $this->annual($request, $owner, $print),
            'expenses-by-category' => $this->expenses($request, $owner),
            'boat-profitability' => $this->boatProfitability($request, $owner),
            'trip-profitability' => $this->tripProfitability($request, $owner),
            'production' => $this->production($request),
            'fish-quantity' => $this->fishQuantity($request, $owner),
            'customer-statement' => $this->customer($request, $owner, $print),
            'vendor-statement' => $this->vendor($request, $owner, $print),
            'crew-statement' => $this->crew($request, $owner, $print),
        };

        return view('panel.owner.reports.'.($print ? 'print.' : '').$report, $data + [
            'key' => $report,
            'meta' => OwnerReports::REPORTS[$report],
            'owner' => $owner,
            'query' => $request->query(),
        ]);
    }

    // ───────────────────────────── التقارير ─────────────────────────────

    /**
     * تقرير الرحلات: كل الرحلات ما لم تُحدَّد فترة (كجدول hispa)، وحالة اختيارية.
     */
    private function tripReport(Request $request, User $owner): array
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));
        $status = in_array($request->query('status'), Trip::STATUSES, true) ? $request->query('status') : null;

        return [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'status' => $status,
            'statuses' => Trip::STATUSES,
        ] + $this->reports->tripReport($owner, $from, $to, $status);
    }

    private function salesReport(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request);
        $status = in_array($request->query('status'), [Sale::IN_PROGRESS, Sale::COMPLETED], true) ? $request->query('status') : null;

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'status' => $status,
            'statuses' => [Sale::IN_PROGRESS => 'جارية', Sale::COMPLETED => 'مكتملة'],
        ] + $this->reports->salesReport($owner, $from, $to, $status);
    }

    /**
     * قائمة الأرباح وملخص الشهر: أرقامهما من إغلاق كل شهر، فالفترة أشهر
     * كاملة — من أول شهر "من" إلى آخر شهر "إلى"، وتظهر في التصفية كذلك.
     */
    private function financial(Request $request, User $owner, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        [$from, $to] = [$from->startOfMonth(), $to->endOfMonth()];
        $boat = $this->boat($request, $owner);
        $f = $this->reports->financials($owner, $from, $to, $boat?->id);

        return $this->filters($owner, $from, $to, $boat) + [
            'f' => $f,
            'expenses' => $report === 'month-summary' ? $this->reports->monthExpenses($owner, $from, $to, $f, $boat?->id) : null,
        ];
    }

    private function annual(Request $request, User $owner, bool $print): array
    {
        $boat = $this->boat($request, $owner);
        $base = ['boat' => $boat, 'boatId' => $boat?->id, 'boats' => $this->boats($owner)];

        if (! $print) {
            return $base + ['years' => $this->reports->closedYears($owner, $boat?->id)];
        }

        $year = (int) $request->query('year', (string) now()->year);
        abort_unless($year >= 2000 && $year <= now()->year, 404);
        $summary = $this->reports->annualSummary($owner, $year, $boat?->id);
        // سنة بلا شهر مُغلق لا تقرير لها — وإلا طُبعت "خاسرة" بصافي صفر.
        abort_if($summary['closed_count'] === 0, 404);

        return $base + [
            'year' => $year,
            'summary' => $summary,
            'analysis' => $this->reports->annualAnalysis($owner, $summary, $boat?->id),
        ];
    }

    private function expenses(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request);
        $boat = $this->boat($request, $owner);
        $rows = $this->reports->expenseRows($owner, $from, $to, $boat ? (string) $boat->id : null);

        return $this->filters($owner, $from, $to, $boat) + ['rows' => $rows, 'total' => round(array_sum(array_column($rows, 'amount')), 2)];
    }

    /**
     * ربحية القوارب من إغلاق كل شهر — الفترة أشهر كاملة كقائمة الأرباح.
     */
    private function boatProfitability(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request);
        [$from, $to] = [$from->startOfMonth(), $to->endOfMonth()];

        return $this->filters($owner, $from, $to) + $this->reports->boatProfitability($owner, $from, $to);
    }

    private function tripProfitability(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request);
        $boat = $this->boat($request, $owner);

        return $this->filters($owner, $from, $to, $boat) + $this->reports->tripProfitability($owner, $from, $to, $boat?->id);
    }

    private function production(Request $request): array
    {
        [$from, $to] = $this->dateRange($request);

        return ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'rows' => $this->reports->production($request->user(), $from, $to)];
    }

    private function fishQuantity(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request);
        $boat = $this->boat($request, $owner);
        $trip = $request->filled('trip_id') ? Trip::forOwner($owner)->findOrFail($request->integer('trip_id')) : null;
        $fish = $request->filled('fish_id') ? Species::findOrFail($request->integer('fish_id')) : null;
        $caught = CatchRecord::whereHas('trip', fn ($q) => $q->forOwner($owner))->distinct()->pluck('species_id');

        return $this->filters($owner, $from, $to, $boat) + [
            'trip' => $trip,
            'fish' => $fish,
            'trips' => Trip::forOwner($owner)->orderByDesc('departure_time')->get(['id', 'trip_number']),
            'species' => Species::whereIn('id', $caught)->orderBy('name_ar')->get(['id', 'name_ar']),
            'stocks' => $this->reports->fishQuantity($owner, $from, $to, $boat?->id, $trip?->id, $fish?->id),
        ];
    }

    private function customer(Request $request, User $owner, bool $print): array
    {
        [$from, $to] = $this->optionalRange($request);
        $customer = $request->filled('customer_id') ? Customer::forAccount($owner)->with('customerType')->findOrFail($request->integer('customer_id')) : null;
        abort_if($print && $customer === null, 404);

        return [
            'customers' => Customer::forAccount($owner)->orderBy('name')->get(['id', 'name']),
            'customer' => $customer,
            'from' => $from,
            'to' => $to,
            'statement' => $customer ? $this->reports->customerStatement($owner, $customer, $from, $to) : null,
        ];
    }

    private function vendor(Request $request, User $owner, bool $print): array
    {
        [$from, $to] = $this->optionalRange($request);
        $vendor = $request->filled('vendor_id') ? Vendor::forOwner($owner)->findOrFail($request->integer('vendor_id')) : null;
        abort_if($print && $vendor === null, 404);

        return [
            'vendors' => Vendor::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'vendor' => $vendor,
            'from' => $from,
            'to' => $to,
            'statement' => $vendor ? $this->reports->vendorStatement($owner, $vendor, $from, $to) : null,
        ];
    }

    private function crew(Request $request, User $owner, bool $print): array
    {
        [$from, $to] = $this->optionalRange($request);
        $person = $request->filled('person_id') ? Fisher::forOwner($owner)->with('boat:id,name')->findOrFail($request->integer('person_id')) : null;
        abort_if($print && $person === null, 404);

        return [
            'captains' => Fisher::forOwner($owner)->captains()->orderBy('name')->get(['id', 'name', 'is_captain']),
            'fishers' => Fisher::forOwner($owner)->crew()->orderBy('name')->get(['id', 'name', 'is_captain']),
            'person' => $person,
            'from' => $from,
            'to' => $to,
            'statement' => $person ? $this->reports->crewStatement($person, $from, $to) : null,
        ];
    }

    // ───────────────────────────── التصفية ─────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function filters(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?Boat $boat = null): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'boat' => $boat,
            'boatId' => $boat?->id,
            'boats' => $this->boats($owner),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(Request $request): array
    {
        $from = $this->date($request->query('from')) ?? CarbonImmutable::now()->startOfMonth();
        $to = $this->date($request->query('to')) ?? CarbonImmutable::now()->endOfMonth();

        return $from->greaterThan($to) ? [$to->startOfDay(), $from->endOfDay()] : [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * فترة كشوف الحساب اختيارية الطرفين (كل الفترات إن تُركت).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function optionalRange(Request $request): array
    {
        return [$this->date($request->query('from'))?->toDateString(), $this->date($request->query('to'))?->toDateString()];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        // تاريخ صحيح فقط: "2026-02-31" لا يُقبل (كان يصير 3 مارس بصمت).
        $date = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? CarbonImmutable::createFromFormat('!Y-m-d', $value) : null;

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function boat(Request $request, User $owner): ?Boat
    {
        return $request->filled('boat_id') ? Boat::forOwner($owner)->findOrFail($request->integer('boat_id')) : null;
    }

    /**
     * @return Collection<int, Boat>
     */
    private function boats(User $owner): Collection
    {
        return Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']);
    }
}
