@extends('layouts.app')

@section('title', 'المبيعات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'coins'])</div>
            <div>
                <h1>المبيعات</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-3" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الفواتير', 'value' => number_format($totals['count']), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الإيرادات', 'value' => number_format($totals['revenue'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستحق', 'value' => number_format($totals['due'], 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $totals['due'] > 0 ? 'warning' : 'success'])
    </div>

    @if ($pendingTrips->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'shopping-cart', 'title' => 'الرحلات التي تحتاج تسجيل البيع'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الرحلة</th><th>القارب</th><th>اكتمل العد</th><th>المعدود</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($pendingTrips as $trip)
                            <tr>
                                <td><a href="{{ route('panel.owner.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                                <td>{{ $trip->boat?->name }}</td>
                                <td class="num" style="font-size:.74rem">{{ $trip->counted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="num">{{ number_format($trip->actual_weight_kg ?? 0, 1) }} كجم</td>
                                <td>
                                    <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                        <a href="{{ route('panel.owner.sales.create', ['trip' => $trip->id]) }}" class="btn btn-primary" style="padding:.3rem .6rem;font-size:.72rem">بيع مصيد لزبون</a>
                                        <a href="{{ route('panel.owner.consignments.create', ['trip' => $trip->id]) }}" class="btn btn-outline" style="padding:.3rem .6rem;font-size:.72rem">إرسال مصيد للدلال</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الفاتورة أو اسم الزبون..."></label>
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}" dir="ltr"></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.sales') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الفاتورة</th><th>الزبون</th><th>الرحلة</th><th>الوزن</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>حالة الدفع</th><th>التاريخ</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td><a href="{{ route('panel.owner.sales.show', $sale) }}" class="num" style="font-weight:700">{{ $sale->invoice_number }}</a></td>
                        <td>{{ $sale->customer?->name ?? '—' }}</td>
                        <td class="num">{{ $sale->trip?->trip_number ?? '—' }}</td>
                        <td class="num">{{ number_format($sale->items_sum_weight_kg ?? 0, 1) }} كجم</td>
                        <td class="num">{{ number_format($sale->total, 2) }}</td>
                        <td class="num">{{ number_format($sale->paid_amount, 2) }}</td>
                        <td class="num" style="{{ $sale->remaining > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($sale->remaining, 2) }}</td>
                        <td><span class="badge {{ $sale->remaining <= 0 ? 'badge-ok' : 'badge-warn' }}">{{ $sale->paymentStatus?->name ?? '—' }}</span></td>
                        <td class="num" style="font-size:.74rem">{{ $sale->sold_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('panel.owner.sales.show', $sale) }}" class="icon-action" title="الفاتورة">@include('partials.icon', ['name' => 'file-text'])</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $sales])
@endsection
