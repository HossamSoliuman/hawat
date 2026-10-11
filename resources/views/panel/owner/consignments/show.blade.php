@extends('layouts.app')

@section('title', 'الشحنة '.$consignment->consignment_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'send'])</div>
            <div>
                <h1 class="num">{{ $consignment->consignment_number }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.consignments') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) كل الشحنات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'بيانات الشحنة'])
            <dl class="detail-list">
                <dt>الدلال</dt><dd>{{ $consignment->dalal?->name }}@if ($consignment->dalal?->phone) <span class="num" dir="ltr">({{ $consignment->dalal->phone }})</span>@endif</dd>
                <dt>الرحلة</dt><dd>@if ($consignment->trip)<a href="{{ route('panel.owner.trips.show', $consignment->trip) }}" class="num">{{ $consignment->trip->trip_number }}</a> — {{ $consignment->trip->boat?->name }}@else — @endif</dd>
                <dt>الحالة</dt><dd><span class="badge {{ $consignment->status === 'مستلمة' ? 'badge-ok' : 'badge-info' }}">{{ $consignment->status }}</span></dd>
                <dt>إجمالي الوزن</dt><dd class="num" style="font-weight:700">{{ number_format($consignment->total_kg, 1) }} كجم</dd>
                <dt>ملاحظات</dt><dd>{{ $consignment->notes ?? '—' }}</dd>
            </dl>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأصناف'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>#</th><th>الصنف</th><th style="text-align:left">الوزن (كجم)</th></tr></thead>
                    <tbody>
                        @foreach ($consignment->items as $item)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td style="font-weight:600">{{ $item->species?->name_ar }}</td>
                                <td class="num" style="text-align:left">{{ number_format($item->weight_kg, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
