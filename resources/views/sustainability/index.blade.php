@extends('layouts.app')

@section('title', 'الاستدامة')

@php
    $statusBadge = fn ($s) => ['مستقر' => 'badge-ok', 'مراقبة' => 'badge-warn', 'ضغط صيد مرتفع' => 'badge-danger', 'انخفاض حاد' => 'badge-danger'][$s] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'leaf'])</div>
            <div>
                <h1>الاستدامة</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الأنواع المرصودة', 'value' => $stats['species'], 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مخزون مستقر', 'value' => $stats['stable'], 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'تحت ضغط', 'value' => $stats['pressure'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'مواقع عالية الخطورة', 'value' => $stats['sites_risk'], 'icon' => 'map-pin', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'الصيد العرضي', 'value' => number_format($stats['bycatch']), 'unit' => 'كجم', 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'أُعيد للبحر', 'value' => number_format($stats['released']), 'unit' => 'كجم', 'icon' => 'leaf', 'tone' => 'success'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            <p class="card-title">توزيع حالة المخزون</p>
            <div class="chart-wrap" style="height:280px;margin-top:.75rem"><canvas id="stockChart"></canvas></div>
        </div>
        <div class="card">
            <p class="card-title" style="margin-bottom:.75rem">أعلى مواقع الصيد إنتاجاً</p>
            @foreach ($topSites as $site)
                @php $max = $topSites->max('catch_kg') ?: 1; @endphp
                <div style="margin-bottom:.65rem">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem">
                        <span style="font-weight:500">{{ $site->name }}</span>
                        <span style="color:hsl(var(--muted-foreground))">{{ number_format($site->catch_kg) }} كجم · {{ $site->pressure_level }}</span>
                    </div>
                    <div class="progress"><div style="width:{{ round($site->catch_kg / $max * 100) }}%;background:{{ in_array($site->pressure_level, ['ضغط مرتفع', 'إنذار']) ? '#f43f5e' : ($site->pressure_level === 'مراقبة' ? '#f59e0b' : '#10b981') }}"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <p style="font-size:.82rem;font-weight:700;margin-bottom:.5rem">قائمة المتابعة العلمية</p>
    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>النوع</th><th>الاسم العلمي</th><th>المصيد (كجم)</th><th>الرحلات</th><th>القوارب</th><th>أكثر ميناء إنزالاً</th><th>حالة المخزون</th></tr></thead>
            <tbody>
                @forelse ($watchlist as $sp)
                    <tr>
                        <td style="font-weight:600">{{ $sp->name_ar }}</td>
                        <td style="font-style:italic;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $sp->corrected_name_sci ?: ($sp->name_sci ?: '—') }}</td>
                        <td>{{ number_format($sp->catch_kg) }}</td>
                        <td>{{ number_format($sp->trips_count) }}</td>
                        <td>{{ number_format($sp->boats_count) }}</td>
                        <td>{{ $sp->top_port ?? '—' }}</td>
                        <td><span class="badge {{ $statusBadge($sp->status) }}">{{ $sp->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد أنواع تحت المتابعة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    new Chart(document.getElementById('stockChart'), {
        type: 'doughnut',
        data: {
            labels: @json($statusCounts->keys()),
            datasets: [{ data: @json($statusCounts->values()), backgroundColor: ['#10b981', '#f59e0b', '#f97316', '#f43f5e'], borderWidth: 0 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush