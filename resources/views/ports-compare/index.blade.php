@extends('layouts.app')

@section('title', 'مقارنة الموانئ')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bar-chart'])</div>
            <div>
                <h1>مقارنة الموانئ</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('ports') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'anchor']) سجل الموانئ</a>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field" style="min-width:16rem"><span>مؤشر المقارنة</span>
            <select class="select" name="metric" onchange="this.form.submit()">
                @foreach ($metrics as $key => $label)<option value="{{ $key }}" @selected($metric === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
    </form>

    <div class="card" style="margin-bottom:1.25rem">
        <p class="card-title">{{ $metrics[$metric] }} — أعلى ١٠ موانئ</p>
        <div class="chart-wrap" style="height:340px;margin-top:.75rem"><canvas id="compareChart"></canvas></div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>الميناء</th><th>المحافظة</th><th>المنطقة</th><th>{{ $metrics[$metric] }}</th><th>القوارب</th><th>النشطة</th><th>الصيادون</th><th>المصيد (طن)</th></tr>
            </thead>
            <tbody>
                @foreach ($ports as $i => $port)
                    <tr>
                        <td style="font-weight:700;color:hsl(var(--muted-foreground))">{{ $i + 1 }}</td>
                        <td style="font-weight:600">{{ $port->name }}</td>
                        <td>{{ $port->governorate?->name ?? '—' }}</td>
                        <td>{{ $port->governorate?->region?->name ?? '—' }}</td>
                        <td style="font-weight:700;color:hsl(var(--primary))">{{ number_format($port->{$metric}) }}</td>
                        <td>{{ number_format($port->boats_count) }}</td>
                        <td>{{ number_format($port->active_boats) }}</td>
                        <td>{{ number_format($port->fishers_count) }}</td>
                        <td>{{ number_format($port->total_catch_tons) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    new Chart(document.getElementById('compareChart'), {
        type: 'bar',
        data: { labels: @json($chart->keys()), datasets: [{ label: @json($metrics[$metric]), data: @json($chart->values()), backgroundColor: '#0284c7', borderRadius: 4 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
    });
</script>
@endpush