@extends('layouts.app')

@section('title', 'جدول الإهلاك')

@section('content')
    @php $monthNames = \App\Models\FishingSeason::MONTHS; @endphp
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calendar-days'])</div>
            <div>
                <h1>جدول الإهلاك — {{ $year }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.assets') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'archive']) سجل الأصول</a>
            <a href="{{ route('panel.owner.assets.depreciation.print', request()->query()) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>السنة</span>
            <select class="select" name="year" onchange="this.form.submit()">
                @foreach ($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat" onchange="this.form.submit()">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected($boat?->id === $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
    </form>

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إهلاك السنة', 'value' => number_format($schedule['year_total'], 2), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'متوسط الشهر', 'value' => number_format($schedule['year_total'] / 12, 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'أصول مُهلَكة في السنة', 'value' => count($schedule['assets']), 'icon' => 'archive', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المتبقي للإهلاك', 'value' => number_format(collect($schedule['assets'])->sum('remaining'), 2), 'unit' => 'ر.س', 'icon' => 'scale', 'tone' => 'success'])
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'calendar', 'title' => 'الإهلاك الشهري'])
        <div class="table-card" style="border:0;overflow-x:auto">
            <table class="data-table">
                <thead><tr><th></th>@foreach ($monthNames as $m)<th>{{ $m }}</th>@endforeach</tr></thead>
                <tbody>
                    <tr><td style="font-weight:600">الشهر</td>@foreach ($schedule['months'] as $row)<td class="num">{{ number_format($row['total'], 2) }}</td>@endforeach</tr>
                    <tr><td style="font-weight:600">المتراكم</td>@foreach ($schedule['months'] as $row)<td class="num" style="color:hsl(var(--muted-foreground))">{{ number_format($row['accumulated'], 2) }}</td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'archive', 'title' => 'تفصيل الأصول'])
        <div class="table-card" style="border:0;overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>الأصل</th><th>التكلفة</th><th>القسط</th><th>أشهر السنة</th><th>إهلاك السنة</th><th>المتراكم</th><th>القيمة الدفترية</th><th>المتبقي</th></tr></thead>
                <tbody>
                    @forelse ($schedule['assets'] as $r)
                        <tr>
                            <td style="font-weight:600">{{ $r['asset']->name }}<div style="font-size:.72rem;font-weight:400;color:hsl(var(--muted-foreground))">{{ $r['asset']->type?->name }}@if ($r['asset']->boat) · {{ $r['asset']->boat->name }}@endif</div></td>
                            <td class="num">{{ number_format($r['asset']->purchase_cost, 2) }}</td>
                            <td class="num">{{ number_format($r['monthly'], 2) }}</td>
                            <td class="num">{{ $r['months_charged'] }}</td>
                            <td class="num" style="font-weight:700">{{ number_format($r['year_total'], 2) }}</td>
                            <td class="num">{{ number_format($r['accumulated'], 2) }}</td>
                            <td class="num">{{ number_format($r['book_value'], 2) }}</td>
                            <td class="num">{{ number_format($r['remaining'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا إهلاك في هذه السنة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
