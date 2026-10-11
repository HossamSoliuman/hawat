@extends('layouts.app')

@section('title', $port->name)

@php
    $activityPct = $port->boats_count ? round($port->active_boats / $port->boats_count * 100) : 0;
    $licensedBoats = $boats->where('license_status', 'سارية')->count();
    $licensedFishers = $fishers->where('license_status', 'سارية')->count();
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'anchor'])</div>
            <div>
                <h1>{{ $port->name }}</h1>
            </div>
        </div>
        <div class="actions">
            @if ($port->governorate)
                <a href="{{ route('governorates.show', $port->governorate) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'building']) المحافظة</a>
            @endif
            <a href="{{ route('ports') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) كل الموانئ</a>
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'gauge', 'title' => 'مؤشرات الميناء'])

    <div class="stat-grid cols-6">
        @include('partials.stat-card', ['label' => 'إجمالي القوارب', 'value' => number_format($port->boats_count), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => number_format($port->active_boats), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الصيادون', 'value' => number_format($port->fishers_count), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رحلات/يوم', 'value' => number_format($port->daily_trips), 'icon' => 'sailboat', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رحلات/شهر', 'value' => number_format($port->monthly_trips), 'icon' => 'calendar', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($port->total_catch_tons), 'unit' => 'طن', 'icon' => 'scale', 'tone' => 'primary'])
    </div>

    @include('partials.section-head', ['icon' => 'clipboard-check', 'title' => 'بطاقة الميناء'])

    <div class="grid-3">
        <div class="card">
            <p class="card-title">البيانات الأساسية</p>
            <p class="card-sub" style="margin-bottom:.7rem">ما هو مسجّل في سجل الموانئ</p>
            <div class="mini-grid" style="margin-top:0">
                <div class="mini">@include('partials.icon', ['name' => 'hash'])<div><p class="m-label">الرمز</p><p class="m-value">{{ $port->code ?? '—' }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'activity'])<div><p class="m-label">الحالة</p><p class="m-value">{{ $port->status }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'building'])<div><p class="m-label">المحافظة</p><p class="m-value">{{ $port->governorate?->name ?? '—' }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'map'])<div><p class="m-label">المنطقة</p><p class="m-value">{{ $port->governorate?->region?->name ?? '—' }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'map-pin'])<div><p class="m-label">خط العرض</p><p class="m-value">{{ $port->lat ? number_format($port->lat, 4) : '—' }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'map-pin'])<div><p class="m-label">خط الطول</p><p class="m-value">{{ $port->lng ? number_format($port->lng, 4) : '—' }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'clipboard-check'])<div><p class="m-label">موظفو الإحصاء</p><p class="m-value">{{ number_format($port->statistics_staff) }}</p></div></div>
                <div class="mini">@include('partials.icon', ['name' => 'map-pin'])<div><p class="m-label">مواقع الصيد</p><p class="m-value">{{ $sites->count() }}</p></div></div>
            </div>
            <div style="margin-top:.85rem">
                <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem"><span style="color:hsl(var(--muted-foreground))">نسبة النشاط</span><span style="font-weight:700">{{ $activityPct }}%</span></div>
                <div class="progress"><div id="activityBar" style="width:{{ $activityPct }}%"></div></div>
            </div>
        </div>

        <div class="card span-2">
            <p class="card-title">المصيد المسجّل عبر رحلات الميناء</p>
            <p class="card-sub" style="margin-bottom:.7rem">آخر أربعة عشر يومًا فيها تسجيل — الكمية بالكيلوجرام</p>
            @if ($trend->isNotEmpty())
                <div class="chart-wrap" style="min-height:260px"><canvas id="trendChart"></canvas></div>
            @else
                <div style="display:flex;min-height:260px;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;color:hsl(var(--muted-foreground))">
                    @include('partials.icon', ['name' => 'waves'])
                    <p style="font-size:.875rem">لا توجد سجلات مصيد لهذا الميناء</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid-2" style="margin-top:var(--gap)">
        <div class="card">
            <p class="card-title">حالة أسطول الميناء</p>
            <p class="card-sub" style="margin-bottom:.7rem">{{ $boats->count() }} قاربًا مسجّلًا — منها {{ $licensedBoats }} بترخيص سارٍ</p>
            @forelse ($boatStatuses as $status => $count)
                @php
                    $pct = $boats->count() ? $count / $boats->count() * 100 : 0;
                @endphp
                <div style="margin-bottom:.7rem">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem"><span style="font-weight:600">{{ $status }}</span><span style="color:hsl(var(--muted-foreground))">{{ $count }}</span></div>
                    <div class="progress"><div class="fleet-bar" style="width:{{ $pct }}%"></div></div>
                </div>
            @empty
                <p style="font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد قوارب مسجّلة على هذا الميناء</p>
            @endforelse
        </div>

        <div class="card">
            <p class="card-title">أعلى الأنواع مصيدًا</p>
            <p class="card-sub" style="margin-bottom:.7rem">إجمالي {{ number_format($catchKg) }} كجم من {{ $trips->count() }} رحلة</p>
            @if ($topSpecies->isNotEmpty())
                <div class="chart-wrap" style="min-height:240px"><canvas id="speciesChart"></canvas></div>
            @else
                <p style="font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد سجلات مصيد بعد</p>
            @endif
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'ship', 'title' => 'قوارب الميناء'])

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>القارب</th><th>الرقم</th><th>النوع</th><th>الطول (م)</th><th>المالك</th><th>الربان</th><th>الطاقم</th><th>المصيد (كجم)</th><th>الترخيص</th><th>الحالة</th><th>المخالفات</th></tr></thead>
            <tbody>
                @forelse ($boats as $boat)
                    <tr>
                        <td style="font-weight:600">{{ $boat->name }}</td>
                        <td>{{ $boat->boat_number }}</td>
                        <td>{{ $boat->boat_type ?? '—' }}</td>
                        <td>{{ $boat->length_m ? number_format($boat->length_m, 1) : '—' }}</td>
                        <td>{{ $boat->owner ?? '—' }}</td>
                        <td>{{ $boat->captain ?? '—' }}</td>
                        <td>{{ $boat->crew_count }}</td>
                        <td>{{ number_format($boat->total_catch_kg) }}</td>
                        <td><span class="badge {{ $boat->license_status === 'سارية' ? 'badge-ok' : 'badge-danger' }}">{{ $boat->license_status }}</span></td>
                        <td>{{ $boat->status }}</td>
                        <td>{{ $boat->violations_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد قوارب مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.section-head', ['icon' => 'users', 'title' => 'صيّادو الميناء'])

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>الاسم</th><th>الهوية</th><th>رقم الرخصة</th><th>الصفة</th><th>الهاتف</th><th>الترخيص</th><th>الحالة</th></tr></thead>
            <tbody>
                @forelse ($fishers as $fisher)
                    <tr>
                        <td style="font-weight:600">{{ $fisher->name }}</td>
                        <td>{{ $fisher->national_id }}</td>
                        <td>{{ $fisher->license_number ?? '—' }}</td>
                        <td>{{ $fisher->role }}</td>
                        <td>{{ $fisher->phone ?? '—' }}</td>
                        <td><span class="badge {{ $fisher->license_status === 'سارية' ? 'badge-ok' : 'badge-danger' }}">{{ $fisher->license_status }}</span></td>
                        <td>{{ $fisher->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا يوجد صيادون مسجّلون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.section-head', ['icon' => 'map-pin', 'title' => 'مواقع الصيد التابعة'])

    @if ($sites->isNotEmpty())
        <div class="cards-grid cols-4">
            @foreach ($sites as $site)
                <div class="entity-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                        <h4 style="font-weight:700">{{ $site->name }}</h4>
                        <span class="badge {{ $site->pressure_level === 'طبيعي' ? 'badge-ok' : ($site->pressure_level === 'مرتفع' ? 'badge-warn' : 'badge-danger') }}">{{ $site->pressure_level }}</span>
                    </div>
                    <div class="mini-grid">
                        <div class="mini">@include('partials.icon', ['name' => 'waves'])<div><p class="m-label">النوع</p><p class="m-value">{{ $site->site_type ?? '—' }}</p></div></div>
                        <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">المصيد (كجم)</p><p class="m-value">{{ number_format($site->catch_kg) }}</p></div></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card" style="padding:2rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد مواقع صيد مرتبطة بهذا الميناء</div>
    @endif

    @include('partials.section-head', ['icon' => 'clipboard-check', 'title' => 'موظفو الإحصاء'])

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>الاسم</th><th>الرقم الوظيفي</th><th>الوردية</th><th>الرحلات المحصورة</th><th>الحالة</th></tr></thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr>
                        <td style="font-weight:600">{{ $officer->name }}</td>
                        <td>{{ $officer->employee_number }}</td>
                        <td>{{ $officer->shift }}</td>
                        <td>{{ number_format($officer->trips_counted) }}</td>
                        <td>{{ $officer->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا يوجد موظفو إحصاء معيّنون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.section-head', ['icon' => 'sailboat', 'title' => 'رحلات المغادرة'])

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>رقم الرحلة</th><th>القارب</th><th>الربان</th><th>المغادرة</th><th>العودة</th><th>المدة (س)</th><th>أداة الصيد</th><th>المعتمد (كجم)</th><th>الفرق (كجم)</th><th>الحالة</th></tr></thead>
            <tbody>
                @forelse ($trips->take(25) as $trip)
                    <tr>
                        <td style="font-weight:600">{{ $trip->trip_number }}</td>
                        <td>{{ $trip->boat?->name ?? '—' }}</td>
                        <td>{{ $trip->captain_name ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $trip->departure_time?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $trip->return_time?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td>{{ $trip->duration_hours ? number_format($trip->duration_hours, 1) : '—' }}</td>
                        <td>{{ $trip->gear_type ?? '—' }}</td>
                        <td>{{ $trip->approved_kg !== null ? number_format($trip->approved_kg) : '—' }}</td>
                        <td>{{ $trip->diff_kg !== null ? number_format($trip->diff_kg) : '—' }}</td>
                        <td>{{ $trip->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد رحلات مسجّلة من هذا الميناء</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    /* أشرطة الحالة تقرأ ألوانها من لوحة اللوحة نفسها لا من قيم ثابتة في القالب. */
    (function () {
        const pct = @json($activityPct);
        const bar = document.getElementById('activityBar');
        bar.style.background = pct >= 70 ? hawatChart.status.good : (pct >= 40 ? hawatChart.status.warn : hawatChart.status.critical);
        document.querySelectorAll('.fleet-bar').forEach((el, i) => {
            el.style.background = hawatChart.categorical[i % hawatChart.categorical.length];
        });
    })();

    @if ($trend->isNotEmpty())
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: @json($trend->keys()->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
            datasets: [{
                label: 'المصيد (كجم)',
                data: @json($trend->values()),
                borderColor: hawatChart.accent,
                backgroundColor: hawatChart.accentFill,
                fill: true,
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });
    @endif

    @if ($topSpecies->isNotEmpty())
    new Chart(document.getElementById('speciesChart'), {
        type: 'bar',
        data: {
            labels: @json($topSpecies->keys()),
            datasets: [{ label: 'كجم', data: @json($topSpecies->values()), backgroundColor: hawatChart.accent }]
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } }
    });
    @endif
</script>
@endpush
