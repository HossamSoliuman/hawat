@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>مرحبًا {{ $user->name }}</h1>
                <p>أسطولك ورحلاتك ومبيعاتك — ما يراه التطبيق تراه هنا</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.trips', ['view' => 'for-sale']) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'coins']) تسجيل بيع المصيد</a>
            <a href="{{ route('panel.owner.trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'plus']) رحلة جديدة</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الإيرادات الكلية', 'value' => number_format($kpis['revenue']), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المصيد المعدود', 'value' => number_format($kpis['catch_kg']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => number_format($kpis['active_boats']).' / '.number_format($kpis['boats']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رحلات في البحر', 'value' => number_format($kpis['trips_at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'جاهزة للبيع', 'value' => number_format($kpis['trips_for_sale']), 'icon' => 'shopping-cart', 'tone' => 'warning'])
    </div>

    @if ($dalalSettlement['pending'] > 0 || $dalalSettlement['rejected'] > 0 || $dalalSettlement['balance'] > 0)
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'handshake', 'title' => 'تسوية الدلالين', 'note' => 'فواتير بيع مصيدك عند الدلالين وما لك عندهم'])
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
                @if ($dalalSettlement['pending'] > 0)
                    <a href="{{ route('panel.owner.dalal-invoices', ['status' => 'pending']) }}" class="badge badge-warn" style="font-size:.78rem;padding:.35rem .6rem">{{ number_format($dalalSettlement['pending']) }} فاتورة بانتظار مراجعتك</a>
                @endif
                @if ($dalalSettlement['rejected'] > 0)
                    <a href="{{ route('panel.owner.dalal-invoices', ['status' => 'rejected']) }}" class="badge badge-danger" style="font-size:.78rem;padding:.35rem .6rem">{{ number_format($dalalSettlement['rejected']) }} فاتورة رفضتها بانتظار ردّ الدلال</a>
                @endif
                @if ($dalalSettlement['balance'] > 0)
                    <a href="{{ route('panel.owner.dalal-accounts') }}" class="badge badge-info" style="font-size:.78rem;padding:.35rem .6rem">المستحق لك عند الدلالين <span class="num">{{ number_format($dalalSettlement['balance'], 2) }}</span> ر.س</a>
                @endif
            </div>
        </div>
    @endif

    @if ($fleetAlerts->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'alert-triangle', 'title' => 'تنبيهات الأسطول', 'note' => $fleetAlerts->count().' تنبيه — وثائق وفحوص ورخص منتهية أو تنتهي خلال 30 يومًا'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <tbody>
                        @foreach ($fleetAlerts->take(8) as $alert)
                            <tr>
                                <td style="font-weight:600">{{ $alert['title'] }}</td>
                                <td>{{ $alert['subject'] }}</td>
                                <td class="num">{{ $alert['date']->format('Y-m-d') }}</td>
                                <td><span class="badge {{ $alert['severity'] === 'danger' ? 'badge-danger' : 'badge-warn' }}">{{ $alert['days'] < 0 ? 'انتهى منذ '.abs($alert['days']).' يوم' : ($alert['days'] === 0 ? 'ينتهي اليوم' : 'بعد '.$alert['days'].' يوم') }}</span></td>
                                <td style="text-align:left"><a href="{{ $alert['url'] }}" style="font-size:.74rem">عرض</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'line-chart', 'title' => 'اتجاه الإيرادات', 'note' => 'آخر ستة أشهر'])
            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأسماك المتوفرة', 'note' => 'ما لم يُبع بعد'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الصنف</th><th>الاسم العلمي</th><th style="text-align:left">الوزن</th></tr></thead>
                    <tbody>
                        @forelse ($stock as $row)
                            <tr>
                                <td style="font-weight:600">{{ $row['species'] }}</td>
                                <td dir="ltr" style="text-align:right;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $row['name_sci'] }}</td>
                                <td class="num" style="text-align:left">{{ number_format($row['weight_kg'], 1) }} كجم</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا مخزون متاح — يُفتح بعد اكتمال عدّ رحلة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            @include('partials.section-head', ['icon' => 'route', 'title' => 'الرحلات النشطة', 'note' => 'من الانطلاق حتى البيع'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الرحلة</th><th>القارب</th><th>الكابتن</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($active_trips as $trip)
                            <tr>
                                <td><a href="{{ route('panel.owner.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                                <td>{{ $trip->boat?->name }}</td>
                                <td>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }}</td>
                                <td>@include('panel.owner.partials.trip-badges', ['trip' => $trip])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا رحلات نشطة الآن</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'coins', 'title' => 'آخر المبيعات', 'note' => number_format($kpis['sales_count']).' فاتورة — المستحق '.number_format($kpis['unpaid']).' ر.س'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الفاتورة</th><th>الزبون</th><th>الرحلة</th><th style="text-align:left">الإجمالي</th></tr></thead>
                    <tbody>
                        @forelse ($recent_sales as $sale)
                            <tr>
                                <td><a href="{{ route('panel.owner.sales.show', $sale) }}" class="num" style="font-weight:700">{{ $sale->invoice_number }}</a></td>
                                <td>{{ $sale->customer?->name ?? '—' }}</td>
                                <td class="num">{{ $sale->trip?->trip_number ?? '—' }}</td>
                                <td class="num" style="text-align:left">{{ number_format($sale->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات بعد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json(array_column($revenue_by_month, 'label')),
            datasets: [{
                label: 'الإيرادات (ر.س)',
                data: @json(array_column($revenue_by_month, 'total')),
                borderColor: hawatChart.accent,
                backgroundColor: hawatChart.accentFill,
                fill: true,
                tension: .3,
            }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
</script>
@endpush
