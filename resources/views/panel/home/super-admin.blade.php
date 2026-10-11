@extends('layouts.app')

@section('title', 'المدير العام')

@php
    use App\Models\Trip;

    // مفتاح لون الحالة لمخطط الرحلات: المخطط يقرأ المفتاح ويأخذ اللون من hawatChart.status.
    $statusKeys = [
        Trip::SCHEDULED => 'none',
        Trip::AT_SEA => 'neutral',
        Trip::RETURNED => 'warn',
        Trip::AWAITING_COUNT => 'warn',
        Trip::COUNTING => 'warn',
        Trip::AWAITING_APPROVAL => 'good',
        Trip::APPROVED => 'good',
        Trip::CANCELLED => 'critical',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>المدير العام</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.users') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'user-plus']) حسابات التطبيق</a>
            <a href="{{ route('trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'route']) رحلات الصيد</a>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'حسابات التطبيق', 'value' => number_format($stats['accounts']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'حسابات مفعّلة', 'value' => number_format($stats['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'القوارب', 'value' => number_format($stats['boats']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رحلات في البحر', 'value' => number_format($stats['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار العدّ', 'value' => number_format($stats['awaiting_count']), 'icon' => 'clipboard', 'tone' => 'warning'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'المصيد المعدود'])
            <div class="chart-wrap"><canvas id="catchChart"></canvas></div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'coins', 'title' => 'إيرادات البيع'])
            <div class="chart-wrap"><canvas id="salesChart"></canvas></div>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'activity', 'title' => 'الرحلات حسب الحالة'])
            <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأصناف الأعلى مصيدًا'])
            <div class="chart-wrap"><canvas id="speciesChart"></canvas></div>
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'route', 'title' => 'الرحلات النشطة'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الرحلة</th><th>القارب</th><th>المالك</th><th>الكابتن</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($active_trips as $trip)
                        <tr>
                            <td class="num" style="font-weight:700">{{ $trip->trip_number }}</td>
                            <td>{{ $trip->boat?->name }}</td>
                            <td>{{ $trip->owner?->name ?? '—' }}</td>
                            <td>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }}</td>
                            <td>@include('panel.owner.partials.trip-badges', ['trip' => $trip])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا رحلات نشطة الآن</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const trend = (id, labels, data, label) => new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label, data, borderColor: hawatChart.accent, backgroundColor: hawatChart.accentFill, fill: true, tension: .3 }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });

    trend('catchChart', @json(array_column($catch_by_month, 'label')), @json(array_column($catch_by_month, 'value')), 'المصيد (كجم)');
    trend('salesChart', @json(array_column($sales_by_month, 'label')), @json(array_column($sales_by_month, 'value')), 'الإيرادات (ر.س)');

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: @json($trips_by_status->keys()),
            datasets: [{ data: @json($trips_by_status->values()), backgroundColor: hawatChart.statusColors(@json(array_values($statusKeys))) }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    new Chart(document.getElementById('speciesChart'), {
        type: 'bar',
        data: {
            labels: @json($top_species->keys()),
            datasets: [{ data: @json($top_species->values()), backgroundColor: hawatChart.accent }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
    });
</script>
@endpush
