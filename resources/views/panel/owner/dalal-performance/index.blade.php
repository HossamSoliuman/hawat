@extends('layouts.app')

@section('title', 'أداء الدلالين')

@section('content')
    @php
        $netRows = $rows->where('owner_net', '>', 0)->take(8)->values();
        $settleRows = $rows->filter(fn ($r) => $r['paid'] > 0 || $r['balance'] > 0)->take(8)->values();
        $barHeight = fn ($n) => max(220, $n * 34 + 40);
    @endphp

    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bar-chart'])</div>
            <div>
                <h1>أداء الدلالين</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.dalal-accounts') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calculator']) حسابات الدلالين</a>
        </div>
    </div>

    <div class="filter-bar" style="justify-content:space-between;margin-bottom:1.25rem">
        <nav class="seg" aria-label="الفترة" style="align-self:flex-end">
            @foreach (\App\Services\Owner\DalalPerformance::PERIODS as $key => $label)
                @continue($key === 'custom')
                <a href="{{ route('panel.owner.dalal-performance', ['period' => $key]) }}" class="{{ $period['key'] === $key ? 'is-active' : '' }}" style="padding-block:.5rem">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('panel.owner.dalal-performance') }}" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
            <input type="hidden" name="period" value="custom">
            <label class="field"><span>من</span><input class="input" type="date" name="from" value="{{ $period['key'] === 'custom' ? $period['from'] : '' }}" dir="ltr"></label>
            <label class="field"><span>إلى</span><input class="input" type="date" name="to" value="{{ $period['key'] === 'custom' ? $period['to'] : '' }}" dir="ltr"></label>
            <button class="btn btn-outline">@include('partials.icon', ['name' => 'calendar']) نطاق مخصص</button>
        </form>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'دلالون باعوا لك', 'value' => number_format($kpis['dalals']), 'icon' => 'handshake', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المبيعات', 'value' => number_format($kpis['sales_total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط سعر الكيلو', 'value' => $kpis['avg_price'] !== null ? number_format($kpis['avg_price'], 2) : '—', 'unit' => $kpis['avg_price'] !== null ? 'ر.س' : null, 'icon' => 'scale', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'نسبة الاقتطاع', 'value' => $kpis['deduction_pct'] !== null ? number_format($kpis['deduction_pct'], 1) : '—', 'unit' => $kpis['deduction_pct'] !== null ? '%' : null, 'icon' => 'trending-down', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'صافيك', 'value' => number_format($kpis['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستحق لك (كل الفترات)', 'value' => number_format($kpis['balance'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => $kpis['balance'] > 0 ? 'danger' : 'success'])
    </div>

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @php
            $cards = [
                ['label' => 'الأعلى صافيًا لك', 'icon' => 'trophy', 'row' => $highlights['top_net'], 'value' => fn ($r) => number_format($r['owner_net'], 2).' ر.س'],
                ['label' => 'الأكثر فواتير', 'icon' => 'activity', 'row' => $highlights['most_active'], 'value' => fn ($r) => number_format($r['invoices']).' فاتورة'],
                ['label' => 'أعلى سعر لـ'.($prices['species_name'] ?? 'الصنف'), 'icon' => 'scale', 'row' => $highlights['best_price'], 'value' => fn ($r) => number_format($r['avg_price'], 2).' ر.س/كجم'],
                ['label' => 'أكبر رصيد عليه', 'icon' => 'alert-triangle', 'row' => $highlights['highest_balance'], 'value' => fn ($r) => number_format($r['balance'], 2).' ر.س'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="card">
                @include('partials.section-head', ['icon' => $card['icon'], 'title' => $card['label']])
                @if ($card['row'])
                    <p style="font-weight:700;font-size:1rem">{{ $card['row']['dalal'] }}</p>
                    <p class="num" style="font-size:.82rem;color:hsl(var(--muted-foreground))">{{ ($card['value'])($card['row']) }}</p>
                @else
                    <p style="color:hsl(var(--muted-foreground));font-size:.82rem">لا يوجد</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'bar-chart', 'title' => 'صافيك من كل دلال'])
            @if ($netRows->isNotEmpty())
                <div class="chart-wrap" style="min-height:{{ $barHeight($netRows->count()) }}px"><canvas id="netChart" aria-label="صافي المالك من كل دلال"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات في هذه الفترة</p>
            @endif
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'scale', 'title' => 'سعر الكيلو عند كل دلال'])
            @if ($prices['species']->count() > 1)
                <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end;margin-bottom:.75rem">
                    @foreach (request()->only('period', 'from', 'to') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <label class="field"><span>الصنف</span>
                        <select class="select" name="species_id" onchange="this.form.submit()">
                            @foreach ($prices['species'] as $s)<option value="{{ $s->id }}" @selected($s->id === $prices['species_id'])>{{ $s->name_ar }}</option>@endforeach
                        </select>
                    </label>
                </form>
            @endif
            @if ($prices['rows']->isNotEmpty())
                <div class="chart-wrap" style="min-height:{{ $barHeight($prices['rows']->count()) }}px"><canvas id="priceChart" aria-label="متوسط سعر الكيلو عند كل دلال"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات في هذه الفترة</p>
            @endif
        </div>
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'line-chart', 'title' => 'صافيك شهرًا بشهر'])
            @if (! empty($trend['series']))
                <div class="chart-wrap"><canvas id="trendChart" aria-label="صافي المالك شهريًا حسب الدلال"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات دلالين في آخر ستة أشهر</p>
            @endif
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'check-circle', 'title' => 'المستلم والمستحق'])
            @if ($settleRows->isNotEmpty())
                <div class="chart-wrap" style="min-height:{{ $barHeight($settleRows->count()) }}px"><canvas id="settleChart" aria-label="المستلم والمستحق عند كل دلال"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا أرصدة بعد</p>
            @endif
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'list-checks', 'title' => 'مقارنة الدلالين'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead>
                    <tr><th>الدلال</th><th>الفواتير</th><th>الوزن المباع</th><th>المبيعات</th><th>متوسط الكيلو</th><th>الاقتطاع</th><th>صافيك</th><th>حصته</th><th>أُرسل في الفترة</th><th>التصريف</th><th>المستلم</th><th>المستحق</th><th>التسديد</th><th>مرفوضة</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td style="font-weight:600"><a href="{{ route('panel.owner.dalal-accounts.show', $row['dalal_id']) }}">{{ $row['dalal'] }}</a></td>
                            <td class="num">{{ number_format($row['invoices']) }}</td>
                            <td class="num">{{ number_format($row['sold_kg'], 1) }}</td>
                            <td class="num">{{ number_format($row['sales_total'], 2) }}</td>
                            <td class="num">{{ $row['avg_price'] !== null ? number_format($row['avg_price'], 2) : '—' }}</td>
                            <td class="num">{{ $row['deduction_pct'] !== null ? number_format($row['deduction_pct'], 1).'%' : '—' }}</td>
                            <td class="num" style="font-weight:700">{{ number_format($row['owner_net'], 2) }}</td>
                            <td class="num">{{ $row['share_pct'] !== null ? number_format($row['share_pct'], 1).'%' : '—' }}</td>
                            <td class="num">{{ number_format($row['sent_kg'], 1) }}</td>
                            <td class="num">{{ $row['sell_through'] !== null ? number_format($row['sell_through'], 1).'%' : '—' }}</td>
                            <td class="num">{{ number_format($row['paid'], 2) }}</td>
                            <td class="num" style="{{ $row['balance'] > 0 ? 'color:var(--st-warn);font-weight:700' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                            <td class="num">{{ $row['paid_ratio'] !== null ? number_format($row['paid_ratio'], 1).'%' : '—' }}</td>
                            <td class="num">@if ($row['rejected'])<a href="{{ route('panel.owner.dalal-invoices', ['dalal_id' => $row['dalal_id'], 'status' => 'rejected']) }}" class="badge badge-danger">{{ $row['rejected'] }}</a>@else — @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="14" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا نشاط مع الدلالين في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
    /* فاصل 2px بلون الصفحة بين قطع العمود المكدّس — بدل حدٍّ مرسوم حول القطع. */
    const gap = 'hsl(' + getComputedStyle(document.documentElement).getPropertyValue('--background').trim() + ')';
    const hbar = { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } };

    @if ($netRows->isNotEmpty())
        new Chart(document.getElementById('netChart'), {
            type: 'bar',
            data: {
                labels: @json($netRows->pluck('dalal')),
                datasets: [{ label: 'صافيك (ر.س)', data: @json($netRows->pluck('owner_net')), backgroundColor: hawatChart.accent }],
            },
            options: { ...hbar, plugins: { ...hbar.plugins, tooltip: { callbacks: { label: (c) => 'صافيك ' + money(c.raw) } } } },
        });
    @endif

    @if ($prices['rows']->isNotEmpty())
        new Chart(document.getElementById('priceChart'), {
            type: 'bar',
            data: {
                labels: @json($prices['rows']->pluck('dalal')),
                datasets: [{ label: 'متوسط سعر الكيلو (ر.س)', data: @json($prices['rows']->pluck('avg_price')), backgroundColor: hawatChart.accent }],
            },
            options: {
                ...hbar,
                plugins: { ...hbar.plugins, tooltip: { callbacks: {
                    label: (c) => money(c.raw) + ' / كجم',
                    afterLabel: (c) => 'بيع ' + @json($prices['rows']->pluck('kg'))[c.dataIndex] + ' كجم',
                } } },
            },
        });
    @endif

    @if (! empty($trend['series']))
        (function () {
            const series = @json($trend['series']);
            const colors = hawatChart.colors(series.length);
            new Chart(document.getElementById('trendChart'), {
                type: 'bar',
                data: {
                    labels: @json($trend['labels']),
                    // الفاصل على أسفل كل قطعة فوق الأولى، فلا خطّ فوق أعلى العمود.
                    datasets: series.map((s, i) => ({ label: s.label, data: s.data, backgroundColor: colors[i], borderColor: gap, borderWidth: i > 0 ? { bottom: 2 } : 0, borderSkipped: false })),
                },
                options: {
                    scales: { x: { stacked: true, ticks: { autoSkip: false } }, y: { stacked: true, beginAtZero: true } },
                    plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + money(c.raw) } } },
                },
            });
        })();
    @endif

    @if ($settleRows->isNotEmpty())
        new Chart(document.getElementById('settleChart'), {
            type: 'bar',
            data: {
                labels: @json($settleRows->pluck('dalal')),
                datasets: [
                    { label: 'المستلم', data: @json($settleRows->pluck('paid')), backgroundColor: hawatChart.status.good },
                    { label: 'المستحق لك', data: @json($settleRows->map(fn ($r) => max($r['balance'], 0))), backgroundColor: hawatChart.status.warn, borderColor: gap, borderWidth: { left: 2 }, borderSkipped: false },
                ],
            },
            options: {
                indexAxis: 'y',
                scales: { x: { stacked: true, beginAtZero: true }, y: { stacked: true } },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + money(c.raw) } } },
            },
        });
    @endif
</script>
@endpush
