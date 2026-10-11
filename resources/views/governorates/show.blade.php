@extends('layouts.app')

@section('title', $governorate->name)

@php
    $statusColors = ['نشط' => '#10b981', 'في البحر' => '#0ea5e9', 'عاد للميناء' => '#6366f1', 'غير نشط' => '#94a3b8'];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'building'])</div>
            <div>
                <h1>{{ $governorate->name }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('governorates') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) كل المحافظات</a>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الموانئ', 'value' => $governorate->ports_count ?: $ports->count(), 'icon' => 'anchor', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => $activeBoats, 'icon' => 'ship', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'إجمالي القوارب', 'value' => $boats->count(), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الصيادون النشطون', 'value' => number_format($governorate->active_fishers), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($governorate->total_catch_tons), 'unit' => 'طن', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مصيد اليوم', 'value' => number_format($todayCatch), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => $todayCatch > 0 ? 'success' : 'warning'])
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card span-2">
            <p class="card-title" style="margin-bottom:.75rem">حجم المصيد اليومي (آخر ٧ أيام متاحة)</p>
            @if ($trend->isNotEmpty())
                <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
            @else
                <div style="display:flex;height:16rem;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;color:hsl(var(--muted-foreground))">
                    @include('partials.icon', ['name' => 'waves'])
                    <p style="font-size:.875rem">لا توجد سجلات مصيد لهذه المحافظة</p>
                </div>
            @endif
        </div>
        <div class="card">
            <p class="card-title" style="margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem">@include('partials.icon', ['name' => 'activity']) حالة القوارب</p>
            @foreach ($statusColors as $status => $color)
                @php
                    $count = $statusCounts[$status] ?? 0;
                    $pct = $boats->count() ? $count / $boats->count() * 100 : 0;
                @endphp
                <div style="margin-bottom:.75rem">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem"><span style="font-weight:500">{{ $status }}</span><span style="color:hsl(var(--muted-foreground))">{{ $count }}</span></div>
                    <div class="progress"><div style="width:{{ $pct }}%;background:{{ $color }}"></div></div>
                </div>
            @endforeach
            <p style="margin-top:1rem;font-size:.72rem;color:hsl(var(--muted-foreground))">إجمالي القوارب: <strong style="color:hsl(var(--foreground))">{{ $boats->count() }}</strong></p>
        </div>
    </div>

    <h3 style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:700;margin-bottom:.75rem;color:hsl(var(--primary))">@include('partials.icon', ['name' => 'anchor']) <span style="color:hsl(var(--foreground))">موانئ المحافظة</span></h3>
    @if ($ports->isNotEmpty())
        <div class="cards-grid cols-3" style="margin-bottom:1.25rem">
            @foreach ($ports as $port)
                @php $pct = $port->boats_count ? round($port->active_boats / $port->boats_count * 100) : 0; @endphp
                <a href="{{ route('ports.show', $port) }}" class="entity-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between">
                        <h4 style="display:flex;align-items:center;gap:.5rem;font-weight:700">@include('partials.icon', ['name' => 'map-pin']) {{ $port->name }}</h4>
                        <span class="badge badge-ok">{{ $port->status }}</span>
                    </div>
                    <div class="mini-grid">
                        <div class="mini"><div><p class="m-label">القوارب</p><p class="m-value">{{ $port->boats_count }}</p></div></div>
                        <div class="mini"><div><p class="m-label">النشطة</p><p class="m-value">{{ $port->active_boats }}</p></div></div>
                        <div class="mini"><div><p class="m-label">الصيادون</p><p class="m-value">{{ number_format($port->fishers_count) }}</p></div></div>
                        <div class="mini"><div><p class="m-label">رحلات/يوم</p><p class="m-value">{{ $port->daily_trips }}</p></div></div>
                    </div>
                    <div style="margin-top:.75rem">
                        <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem"><span style="color:hsl(var(--muted-foreground))">نسبة النشاط</span><span style="font-weight:500">{{ $pct }}%</span></div>
                        <div class="progress"><div style="width:{{ $pct }}%;background:#10b981"></div></div>
                    </div>
                    <div style="margin-top:.75rem;display:flex;justify-content:space-between;border-top:1px solid hsl(var(--border));padding-top:.5rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                        <span>المصيد: <strong style="color:hsl(var(--foreground))">{{ number_format($port->total_catch_tons) }}</strong> طن</span>
                        <span>موظفو الإحصاء: <strong style="color:hsl(var(--foreground))">{{ $port->statistics_staff }}</strong></span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="card" style="padding:2rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground));margin-bottom:1.25rem">لا توجد موانئ مسجلة في هذه المحافظة</div>
    @endif

    @if ($boats->isNotEmpty())
        <h3 style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:700;margin-bottom:.75rem;color:hsl(var(--primary))">@include('partials.icon', ['name' => 'ship']) <span style="color:hsl(var(--foreground))">القوارب النشطة ({{ $activeBoats }})</span></h3>
        <div class="cards-grid cols-4">
            @foreach ($boats->whereIn('status', ['نشط', 'في البحر'])->take(12) as $boat)
                <div class="card" style="padding:.75rem">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                        <span style="font-weight:500">{{ $boat->name }}</span>
                        <span class="badge {{ $boat->status === 'في البحر' ? 'badge-info' : 'badge-ok' }}">{{ $boat->status }}</span>
                    </div>
                    <div style="margin-top:.5rem;font-size:.72rem;color:hsl(var(--muted-foreground));line-height:2">
                        <p>رقم القارب: <strong style="color:hsl(var(--foreground))">{{ $boat->boat_number }}</strong></p>
                        <p>المالك: <strong style="color:hsl(var(--foreground))">{{ $boat->owner ?? '—' }}</strong></p>
                        <p>المصيد: <strong style="color:hsl(var(--foreground))">{{ number_format($boat->total_catch_kg) }}</strong> كجم</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;
    @if ($trend->isNotEmpty())
    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: @json($trend->keys()->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
            datasets: [{ label: 'المصيد (كجم)', data: @json($trend->values()), backgroundColor: '#0284c7', borderRadius: 4 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    @endif
</script>
@endpush