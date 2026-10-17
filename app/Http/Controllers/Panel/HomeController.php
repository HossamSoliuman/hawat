<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Services\Counter\CounterDashboard;
use App\Services\Counters\CompanyDashboard;
use App\Services\Dalal\DalalDashboard;
use App\Services\Owner\DalalSettlement;
use App\Services\Owner\FleetAlerts;
use App\Services\Owner\OwnerDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * رئيسة لوحة الإدارة — تتفرّع على دور المستخدم.
 *
 * المدير العام يرى حال الأسطول والرحلات والمصيد والمبيعات على مستوى النظام،
 * والمالك يرى مؤشراته ورحلاته التي بانتظار الانطلاق والنشطة، والعدّاد طابور
 * ميناء عمله، والدلال مبيعاته ومخزونه وطلبات الملاك،
 * وبقية الأدوار ترى رئيسة بوابتها حين تُبنى؛ إلى ذلك الحين بطاقة تعريف بالحساب.
 */
class HomeController extends Controller
{
    public function index(Request $request, OwnerDashboard $ownerDashboard, CounterDashboard $counterDashboard, DalalDashboard $dalalDashboard): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return view('panel.home.super-admin', $this->superAdminData());
        }

        if ($user->hasAppRole(Role::OWNER)) {
            return view('panel.owner.home', [
                'user' => $user,
                'fleetAlerts' => app(FleetAlerts::class)->for($user),
                'dalalSettlement' => app(DalalSettlement::class)->summary($user),
            ] + $ownerDashboard->for($user));
        }

        if ($user->hasAppRole(Role::COUNTER)) {
            return view('panel.counter.home', ['user' => $user] + $counterDashboard->for($user));
        }

        if ($user->hasAppRole(Role::COMPANY)) {
            abort_unless($user->operatingCompany?->isActive(), 403, 'حسابك غير مربوط بشركة تشغيل نشطة — راجع المدير العام.');

            return view('panel.company.home', ['user' => $user] + app(CompanyDashboard::class)->for($user->operatingCompany));
        }

        if ($user->hasAppRole(Role::DALAL)) {
            return view('panel.dalal.home', ['user' => $user] + $dalalDashboard->for($user, (string) $request->query('period', 'month'), $request->query('from'), $request->query('to')));
        }

        return view('panel.home.role', ['user' => $user]);
    }

    private function superAdminData(): array
    {
        $from = now()->startOfMonth()->subMonths(5);

        return [
            'stats' => [
                'accounts' => User::whereNotNull('role_id')->count(),
                'active' => User::whereNotNull('role_id')->where('active', true)->count(),
                'boats' => Boat::count(),
                'at_sea' => Trip::where('status', Trip::AT_SEA)->count(),
                'awaiting_count' => Trip::whereIn('status', [Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
            ],
            'catch_by_month' => $this->catchByMonth($from),
            'sales_by_month' => $this->salesByMonth($from),
            'trips_by_status' => collect(Trip::STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => 0])
                ->merge(Trip::select('status', DB::raw('COUNT(*) AS n'))->groupBy('status')->pluck('n', 'status')),
            'top_species' => CatchRecord::query()
                ->join('species', 'species.id', '=', 'catch_records.species_id')
                ->select('species.name_ar', DB::raw('SUM(catch_records.quantity_kg) AS kg'))
                ->groupBy('species.id', 'species.name_ar')
                ->orderByDesc('kg')
                ->limit(6)
                ->pluck('kg', 'name_ar')
                ->map(fn ($kg) => round((float) $kg, 1)),
            'active_trips' => Trip::activeForOwner()->with(['boat', 'owner', 'captain'])->orderByDesc('updated_at')->limit(8)->get(),
        ];
    }

    /**
     * المصيد المعدود آخر ستة أشهر شهرًا شهرًا — بتاريخ العودة للميناء.
     */
    private function catchByMonth(Carbon $from): array
    {
        $rows = Trip::whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])
            ->whereNotNull('actual_weight_kg')
            ->where(fn ($q) => $q->where('return_time', '>=', $from)->orWhere(fn ($q) => $q->whereNull('return_time')->where('created_at', '>=', $from)))
            ->get(['return_time', 'created_at', 'actual_weight_kg'])
            ->groupBy(fn (Trip $trip) => ($trip->return_time ?? $trip->created_at)->format('Y-m'))
            ->map(fn ($group) => round((float) $group->sum('actual_weight_kg'), 1));

        return $this->monthSeries($from, $rows);
    }

    /**
     * إيرادات البيع آخر ستة أشهر شهرًا شهرًا — على مستوى النظام كله.
     */
    private function salesByMonth(Carbon $from): array
    {
        $rows = Sale::where('sold_at', '>=', $from)
            ->get(['sold_at', 'total'])
            ->groupBy(fn (Sale $sale) => $sale->sold_at->format('Y-m'))
            ->map(fn ($group) => round((float) $group->sum('total'), 2));

        return $this->monthSeries($from, $rows);
    }

    /**
     * يملأ الأشهر الستة من البداية بالقيم المتاحة، وصفرًا لما لا سجل له.
     */
    private function monthSeries(Carbon $from, Collection $rows): array
    {
        $series = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $from->copy()->addMonths($i);
            $series[] = ['label' => $month->translatedFormat('M Y'), 'value' => $rows[$month->format('Y-m')] ?? 0];
        }

        return $series;
    }
}
