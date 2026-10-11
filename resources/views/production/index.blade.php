@extends('layouts.app')

@section('title', 'الإنتاج السمكي')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'fish'])</div>
            <div>
                <h1>الإنتاج السمكي</h1>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الفترة</span>
            <select class="select" name="period" onchange="this.form.submit()">
                @foreach (['اليوم', 'الأسبوع', 'الشهر', 'السنة'] as $p)
                    <option value="{{ $p }}" @selected($period === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>المنطقة</span>
            <select class="select" name="region" onchange="this.form.submit()">
                <option value="">كل المناطق</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->name }}" @selected($selectedRegion === $region->name)>{{ $region->name }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('screens.production') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($totalTons), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'عدد الرحلات (تقديري)', 'value' => number_format(round($totalTons / 2)), 'icon' => 'sailboat', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد القوارب', 'value' => number_format($totalBoats), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'عدد الصيادين', 'value' => number_format($totalFishers), 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط/قارب', 'value' => number_format($avgPerBoat), 'unit' => 'كجم', 'icon' => 'trending-up', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط/رحلة', 'value' => 438, 'unit' => 'كجم', 'icon' => 'trending-up', 'tone' => 'primary'])
    </div>

    <div class="grid-2" style="margin-bottom:1rem">
        <div class="card">
            <p class="card-title">الإنتاج حسب المنطقة</p>
            <p class="card-sub" style="margin-bottom:.75rem">طن</p>
            <div class="chart-wrap" style="height:300px"><canvas id="regionChart"></canvas></div>
        </div>
        <div class="card">
            <p class="card-title">الإنتاج حسب النوع</p>
            <p class="card-sub" style="margin-bottom:.75rem">طن</p>
            <div class="chart-wrap" style="height:300px"><canvas id="speciesChart"></canvas></div>
        </div>
    </div>

    <div class="card">
        <p class="card-title">تطور الإنتاج الشهري</p>
        <p class="card-sub" style="margin-bottom:.75rem">طن</p>
        <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;

    new Chart(document.getElementById('regionChart'), {
        type: 'bar',
        data: { labels: @json($byRegion->keys()), datasets: [{ label: 'المصيد (طن)', data: @json($byRegion->values()), backgroundColor: '#0284c7', borderRadius: 4 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('speciesChart'), {
        type: 'bar',
        data: { labels: @json($bySpecies->keys()), datasets: [{ label: 'الكمية (طن)', data: @json($bySpecies->values()), backgroundColor: '#0c4a6e', borderRadius: 4 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: { labels: @json(collect($monthly)->pluck('m')), datasets: [{ label: 'المصيد', data: @json(collect($monthly)->pluck('value')), borderColor: '#0284c7', borderWidth: 2.5, pointRadius: 4, tension: .35 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>
@endpush