@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>لوحة وزارة البيئة والمياه والزراعة</h1>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        @foreach ($kpis as $kpi)
            <div class="kpi-card" data-tone="{{ $kpi['tone'] }}">
                <div class="top">
                    <p class="label">{{ $kpi['label'] }}</p>
                    <div class="kpi-icon {{ $kpi['tone'] }}">@include('partials.icon', ['name' => $kpi['icon']])</div>
                </div>
                <p class="value">{{ $kpi['value'] }}@if ($kpi['unit'])<span class="unit">{{ $kpi['unit'] }}</span>@endif</p>
            </div>
        @endforeach
    </div>

    <div class="grid-3" style="margin-top:1.5rem">
        <div class="card span-2">
            <div class="section-head">
                <div>
                    <p class="card-title">الإنتاج المعتمد خلال آخر 12 شهرًا</p>
                    <p class="card-sub">بالأطنان — من سجلات الإحصاء الفعلية</p>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
        </div>
        <div class="card">
            <div class="section-head">
                <div>
                    <p class="card-title">الإنتاج حسب المنطقة</p>
                    <p class="card-sub">المصيد المعتمد — طن</p>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="regionChart"></canvas></div>
        </div>
    </div>

    <div class="grid-3" style="margin-top:1rem">
        <div class="card">
            <div class="section-head">
                <div>
                    <p class="card-title">أعلى الموانئ إنتاجًا</p>
                    <p class="card-sub">طن</p>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="portChart"></canvas></div>
        </div>
        <div class="card">
            <div class="section-head">
                <div>
                    <p class="card-title">أعلى الأنواع إنتاجًا</p>
                    <p class="card-sub">طن</p>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="speciesChart"></canvas></div>
        </div>
        <div class="card">
            <div class="section-head">
                <div class="with-icon">
                    @include('partials.icon', ['name' => 'alert-triangle'])
                    <p class="card-title">التنبيهات المفتوحة</p>
                </div>
                <a href="{{ route('subadmin.alerts') }}" class="link-more">عرض الكل @include('partials.icon', ['name' => 'chevron-left'])</a>
            </div>
            @forelse ($alerts as $alert)
                <div class="alert-item">
                    <span class="sev-dot" style="background: {{ ['حرج' => '#f43f5e', 'مرتفع' => '#f97316', 'متوسط' => '#f59e0b'][$alert->severity] ?? '#0ea5e9' }}"></span>
                    <div style="min-width:0;flex:1">
                        <p class="a-title">{{ $alert->title }}</p>
                        <p class="a-desc">{{ $alert->description }}</p>
                    </div>
                    <span class="badge {{ ['حرج' => 'badge-danger', 'مرتفع' => 'badge-danger', 'متوسط' => 'badge-warn'][$alert->severity] ?? 'badge-info' }}">{{ $alert->severity }}</span>
                </div>
            @empty
                <p style="padding:2rem 0;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد تنبيهات مفتوحة</p>
            @endforelse
        </div>
    </div>

    <div class="note-box">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">حوكمة المؤشرات مفعلة</p>
            <p class="n-body">هذه الصفحة لا تعرض KPI إلا إذا كان معتمدًا رسميًا، معتمدًا تقنيًا، ومجتازًا بوابة Business Approved لنفس الإصدار. الرسوم مبنية على سجلات التشغيل الفعلية وليست بيانات تجريبية.</p>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;

    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: @json($monthly->pluck('label')),
            datasets: [{
                label: 'الإنتاج المعتمد',
                data: @json($monthly->pluck('tons')),
                borderColor: '#0284c7',
                backgroundColor: 'rgba(2,132,199,.18)',
                fill: true,
                tension: .4,
                borderWidth: 2.5,
                pointRadius: 2
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    const hBar = (id, labels, data) => new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels, datasets: [{ data, backgroundColor: '#0369a1', borderRadius: 4 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
    });

    hBar('regionChart', @json($byRegion->keys()), @json($byRegion->values()));
    hBar('portChart', @json($byPort->keys()), @json($byPort->values()));
    hBar('speciesChart', @json($bySpecies->keys()), @json($bySpecies->values()));
</script>
@endpush