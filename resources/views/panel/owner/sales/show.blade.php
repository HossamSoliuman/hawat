@extends('layouts.app')

@section('title', 'الفاتورة '.$sale->invoice_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1 class="num">فاتورة {{ $sale->invoice_number }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ $sale->invoiceUrl() }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة الفاتورة</a>
            <a href="{{ route('panel.owner.sales') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) المبيعات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'بيانات الفاتورة'])
            <dl class="detail-list">
                <dt>الزبون</dt><dd>{{ $sale->customer?->name ?? '—' }}@if ($sale->customer?->phone) <span class="num" dir="ltr">({{ $sale->customer->phone }})</span>@endif</dd>
                <dt>الرحلة</dt><dd>@if ($sale->trip)<a href="{{ route('panel.owner.trips.show', $sale->trip) }}" class="num">{{ $sale->trip->trip_number }}</a> — {{ $sale->trip->boat?->name }}@else — @endif</dd>
                <dt>طريقة الدفع</dt><dd>{{ $sale->paymentMethod?->name ?? '—' }}</dd>
                <dt>حالة الدفع</dt><dd><span class="badge {{ $sale->remaining <= 0 ? 'badge-ok' : 'badge-warn' }}">{{ $sale->paymentStatus?->name ?? '—' }}</span></dd>
                <dt>الحالة</dt><dd>{{ $sale->status }}</dd>
                <dt>ملاحظات</dt><dd>{{ $sale->notes ?? '—' }}</dd>
            </dl>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'calculator', 'title' => 'المبالغ'])
            <dl class="detail-list">
                <dt>المجموع</dt><dd class="num">{{ number_format($sale->subtotal, 2) }} ر.س</dd>
                <dt>الخصم</dt><dd class="num">{{ number_format($sale->discount, 2) }} ر.س</dd>
                <dt>الإجمالي</dt><dd class="num" style="font-weight:700">{{ number_format($sale->total, 2) }} ر.س</dd>
                <dt>المدفوع</dt><dd class="num">{{ number_format($sale->paid_amount, 2) }} ر.س</dd>
                <dt>المتبقي</dt><dd class="num" style="{{ $sale->remaining > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($sale->remaining, 2) }} ر.س</dd>
                <dt>إجمالي الوزن</dt><dd class="num">{{ number_format($sale->items->sum('weight_kg'), 1) }} كجم</dd>
            </dl>
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>#</th><th>الصنف</th><th>الوزن (كجم)</th><th>سعر الكيلو</th><th style="text-align:left">الإجمالي</th></tr></thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td style="font-weight:600">{{ $item->species?->name_ar }}</td>
                        <td class="num">{{ number_format($item->weight_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->price_per_kg, 2) }}</td>
                        <td class="num" style="text-align:left">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
