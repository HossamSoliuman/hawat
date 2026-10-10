@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>مرحبًا {{ $user->name }}</h1>
                <p>نظرة عامة على المبيعات وتسليم المصيد — {{ $period['label'] }} (<span class="num">{{ $period['from'] }}</span> — <span class="num">{{ $period['to'] }}</span>)</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.dalal.sales.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إضافة عملية بيع</a>
            <a href="{{ route('panel.dalal.stock') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'archive']) المخزون</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="filter-bar" style="justify-content:space-between;margin-bottom:1.25rem">
        <nav class="seg" aria-label="الفترة" style="align-self:flex-end">
            @foreach (\App\Services\Dalal\DalalDashboard::PERIODS as $key => $label)
                @continue($key === 'custom')
                <a href="{{ route('panel.home', ['period' => $key]) }}" class="{{ $period['key'] === $key ? 'is-active' : '' }}" style="padding-block:.5rem">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('panel.home') }}" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
            <input type="hidden" name="period" value="custom">
            <label class="field"><span>من</span><input class="input" type="date" name="from" value="{{ $period['key'] === 'custom' ? $period['from'] : '' }}"></label>
            <label class="field"><span>إلى</span><input class="input" type="date" name="to" value="{{ $period['key'] === 'custom' ? $period['to'] : '' }}"></label>
            <button class="btn btn-outline">@include('partials.icon', ['name' => 'calendar']) نطاق مخصص</button>
        </form>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المصيد المستلم', 'value' => number_format($kpis['received_kg'], 1), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المبيعات', 'value' => number_format($kpis['sales_total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'عمليات البيع', 'value' => number_format($kpis['sales_count']), 'icon' => 'file-text', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'صافي الأرباح', 'value' => number_format($kpis['net_profit'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'في المخزون', 'value' => number_format($kpis['in_stock_kg'], 1), 'unit' => 'كجم', 'icon' => 'archive', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'مستحق للملاك', 'value' => number_format($kpis['due_to_owners'], 2), 'unit' => 'ر.س', 'icon' => 'users', 'tone' => 'danger'])
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card span-2">
            @include('partials.section-head', ['icon' => 'line-chart', 'title' => 'الإيرادات والأرباح', 'note' => 'آخر ستة أشهر — الربح = العمولة + الأجور'])
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'calculator', 'title' => 'المالية', 'note' => $period['label']])
            <dl class="detail-list">
                <dt>عمولة الدلال</dt><dd class="num">{{ number_format($kpis['commission'], 2) }} ر.س</dd>
                <dt>أجور العمالة</dt><dd class="num">{{ number_format($kpis['wages'], 2) }} ر.س</dd>
                <dt>الوزن المباع</dt><dd class="num">{{ number_format($kpis['sold_kg'], 1) }} كجم</dd>
                <dt>متوسط سعر الكيلو</dt><dd class="num">{{ $kpis['avg_price_per_kg'] !== null ? number_format($kpis['avg_price_per_kg'], 2).' ر.س' : '—' }}</dd>
                <dt>العملاء النشطون</dt><dd class="num">{{ number_format($kpis['active_customers']) }} من {{ number_format($kpis['customers']) }}</dd>
                <dt>غير المحصّل</dt><dd class="num" style="{{ $kpis['unpaid'] > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($kpis['unpaid'], 2) }} ر.س</dd>
            </dl>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'handshake', 'title' => 'طلبات الملاك المعلّقة', 'note' => 'بانتظار موافقتك'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>المالك</th><th>العمولة</th><th>الأجور</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @forelse ($pending_requests as $request)
                            <tr>
                                <td><a href="{{ route('panel.dalal.requests', ['status' => 'pending']) }}" style="font-weight:600">{{ $request->owner?->name }}</a></td>
                                <td class="num">{{ rtrim(rtrim(number_format($request->commission_pct, 2), '0'), '.') }}%</td>
                                <td class="num">{{ rtrim(rtrim(number_format($request->wage_pct, 2), '0'), '.') }}%</td>
                                <td class="num">{{ $request->created_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد طلبات معلّقة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'أكثر أنواع السمك مبيعًا', 'note' => $period['label']])
            <div class="chart-wrap"><canvas id="speciesChart"></canvas></div>
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'coins', 'title' => 'العمليات الأخيرة', 'note' => 'آخر 10 مبيعات'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الفاتورة</th><th>التاريخ</th><th>العميل</th><th>نوع السمك</th><th>الوزن</th><th>الإجمالي</th><th>العمولة</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($recent_sales as $sale)
                        <tr>
                            <td><a href="{{ route('panel.dalal.sales.show', $sale) }}" class="num" style="font-weight:700">{{ $sale->invoice_number }}</a></td>
                            <td class="num">{{ $sale->sold_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $sale->customer?->name ?? '—' }}</td>
                            <td>{{ $sale->items->pluck('species.name_ar')->unique()->join('، ') }}</td>
                            <td class="num">{{ number_format($sale->items->sum('weight_kg'), 1) }} كجم</td>
                            <td class="num">{{ number_format($sale->total, 2) }}</td>
                            <td class="num">{{ number_format($sale->commission_amount + $sale->wage_amount, 2) }}</td>
                            <td><span class="badge {{ $sale->status === \App\Models\Sale::COMPLETED ? 'badge-ok' : 'badge-warn' }}">{{ $sale->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد عمليات حديثة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($revenue_by_month, 'label')),
            datasets: [
                { label: 'الإيرادات (ر.س)', data: @json(array_column($revenue_by_month, 'total')), backgroundColor: hawatChart.categorical[0] },
                { label: 'الربح (ر.س)', data: @json(array_column($revenue_by_month, 'profit')), backgroundColor: hawatChart.categorical[1] },
            ],
        },
        options: { scales: { y: { beginAtZero: true } } },
    });

    new Chart(document.getElementById('speciesChart'), {
        type: 'bar',
        data: {
            labels: @json($top_species->pluck('species')),
            datasets: [{ label: 'الوزن المباع (كجم)', data: @json($top_species->pluck('weight_kg')), backgroundColor: hawatChart.accent }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
    });
</script>
@endpush
