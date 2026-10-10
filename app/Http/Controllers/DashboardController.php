<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\MarketAuction;
use App\Models\Trip;
use App\Models\Violation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * شاشة اختيار لوحات القاعة: مربّعات تبويبات القائمة الجانبية، بلا بيانات —
     * تُبنى من config/hawat.php → nav_screens داخل العرض نفسه.
     */
    public function screens(): View
    {
        return view('dashboard.screens');
    }

    public function index(): View
    {
        $trips = Trip::with('departurePort.governorate.region')->get();
        $catches = CatchRecord::with('species')->get();

        $approvedKg = (float) $trips->sum('approved_kg');
        $pendingStats = $trips->whereIn('status', ['بانتظار الإحصاء', 'تحت الإحصاء'])->count();
        $pendingApproval = $trips->where('status', 'بانتظار الاعتماد')->count();
        $withDiff = $trips->filter(fn ($t) => $t->actual_weight_kg > 0);
        $avgDiff = $withDiff->count()
            ? round($withDiff->avg(fn ($t) => abs((float) $t->diff_kg) / max((float) $t->actual_weight_kg, 1) * 100), 1)
            : 0;
        $captainTotal = (float) $trips->sum('captain_input_kg');
        $topRegion = $trips->groupBy(fn ($t) => $t->departurePort?->governorate?->region?->name ?? 'غير محدد')
            ->map(fn ($g) => $g->sum('approved_kg'))
            ->sortDesc()
            ->keys()
            ->first();

        $kpis = [
            ['label' => 'إجمالي المصيد المعتمد', 'value' => number_format($approvedKg / 1000, 1), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'],
            ['label' => 'إجمالي الرحلات', 'value' => number_format($trips->count()), 'unit' => 'رحلة', 'icon' => 'sailboat', 'tone' => 'info'],
            ['label' => 'القوارب النشطة', 'value' => number_format(Boat::where('status', 'نشط')->count()), 'unit' => 'قارب', 'icon' => 'ship', 'tone' => 'primary'],
            ['label' => 'قوارب في البحر', 'value' => number_format($trips->where('status', 'في البحر')->count()), 'unit' => 'قارب', 'icon' => 'ship', 'tone' => 'info'],
            ['label' => 'قوارب عادت للميناء', 'value' => number_format($trips->where('status', 'عادت للميناء')->count()), 'unit' => 'قارب', 'icon' => 'anchor', 'tone' => 'success'],
            ['label' => 'الصيادون النشطون', 'value' => number_format(Fisher::where('status', 'نشط')->count()), 'unit' => 'صياد', 'icon' => 'users', 'tone' => 'primary'],
            ['label' => 'رحلات بانتظار الإحصاء', 'value' => number_format($pendingStats), 'unit' => 'رحلة', 'icon' => 'clipboard', 'tone' => 'warning'],
            ['label' => 'رحلات بانتظار الاعتماد', 'value' => number_format($pendingApproval), 'unit' => 'رحلة', 'icon' => 'badge-check', 'tone' => 'warning'],
            ['label' => 'متوسط فرق الإحصاء', 'value' => $avgDiff, 'unit' => '%', 'icon' => 'alert-triangle', 'tone' => 'danger'],
            ['label' => 'نسبة المصيد المعتمد', 'value' => $captainTotal > 0 ? round($approvedKg / $captainTotal * 100, 1) : 0, 'unit' => '%', 'icon' => 'badge-check', 'tone' => 'success'],
            ['label' => 'متوسط سعر السمك', 'value' => number_format((float) MarketAuction::avg('avg_price_per_kg'), 2), 'unit' => 'ر.س/كجم', 'icon' => 'store', 'tone' => 'primary'],
            ['label' => 'المخالفات المسجلة', 'value' => number_format(Violation::count()), 'unit' => 'مخالفة', 'icon' => 'alert-triangle', 'tone' => 'danger'],
            ['label' => 'اكتمال التتبع', 'value' => $trips->count() ? round($trips->whereNotNull('approved_kg')->count() / $trips->count() * 100, 1) : 0, 'unit' => '%', 'icon' => 'link', 'tone' => 'success'],
            ['label' => 'جودة البيانات', 'value' => 96.4, 'unit' => '%', 'icon' => 'database', 'tone' => 'success'],
            ['label' => 'أعلى منطقة إنتاجًا', 'value' => $topRegion ?? '—', 'unit' => '', 'icon' => 'trending-up', 'tone' => 'primary'],
        ];

        $monthly = collect(range(11, 0))->map(function ($i) use ($catches) {
            $month = now()->subMonths($i);
            return [
                'label' => $month->translatedFormat('M y'),
                'tons' => round($catches->filter(fn ($c) => $c->recorded_at->isSameMonth($month))->sum('quantity_kg') / 1000, 1),
            ];
        });

        $byRegion = $trips->groupBy(fn ($t) => $t->departurePort?->governorate?->region?->name ?? 'غير محدد')
            ->map(fn ($g) => round($g->sum('approved_kg') / 1000, 1))
            ->sortDesc();

        $byPort = $trips->groupBy(fn ($t) => $t->departurePort?->name ?? 'غير محدد')
            ->map(fn ($g) => round($g->sum('approved_kg') / 1000, 1))
            ->sortDesc()
            ->take(6);

        $bySpecies = $catches->groupBy(fn ($c) => $c->species?->name_ar ?? 'غير محدد')
            ->map(fn ($g) => round($g->sum('quantity_kg') / 1000, 1))
            ->sortDesc()
            ->take(6);

        $alerts = Alert::where('status', 'جديدة')->latest('date')->take(5)->get();

        return view('dashboard.index', compact('kpis', 'monthly', 'byRegion', 'byPort', 'bySpecies', 'alerts'));
    }
}