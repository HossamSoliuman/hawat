@extends('layouts.app')

@section('title', 'مخزون الدلال')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'archive'])</div>
            <div>
                <h1>مخزون الدلال</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.dalal.sales.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إضافة عملية بيع</a>
            <a href="{{ route('panel.dalal.reports.show', 'stock') }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) تقرير المخزون</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'عدد الأصناف', 'value' => number_format($summary['species_count']), 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الوزن الكلي', 'value' => number_format($summary['total_kg'], 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الملاك', 'value' => number_format($summary['owners_count']), 'icon' => 'users', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد الرحلات', 'value' => number_format($summary['trips_count']), 'unit' => 'رحلة', 'icon' => 'route', 'tone' => 'warning'])
    </div>

    @forelse ($owners as $group)
        <div class="card" style="margin-bottom:1rem">
            @include('partials.section-head', ['icon' => 'user', 'title' => $group['owner']])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الصنف</th><th>الاسم العلمي</th><th>الرحلة</th><th>القارب</th><th>تاريخ الإضافة</th><th style="text-align:left">وزن الصنف</th></tr></thead>
                    <tbody>
                        @foreach ($group['lots'] as $lot)
                            <tr>
                                <td style="font-weight:600">{{ $lot['species'] }}</td>
                                <td dir="ltr" style="text-align:right;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $lot['name_sci'] ?? '—' }}</td>
                                <td class="num">{{ $lot['trip_number'] ?? '—' }}</td>
                                <td>{{ $lot['boat'] ?? '—' }}</td>
                                <td class="num">{{ $lot['received_at'] ? \Illuminate\Support\Carbon::parse($lot['received_at'])->format('Y-m-d H:i') : '—' }}</td>
                                <td class="num" style="text-align:left;font-weight:700">{{ number_format($lot['available_kg'], 2) }} كجم</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card" style="margin-bottom:1rem;text-align:center;padding:2rem">
            <p style="font-weight:600;margin:0 0 .35rem">لا يوجد مخزون</p>
            <p class="card-sub" style="margin:0">لم يتم استلام أي مخزون من الملاك بعد — يصلك حين يرسل مالك مصيد رحلة إليك</p>
        </div>
    @endforelse

    <div class="card">
        @include('partials.section-head', ['icon' => 'send', 'title' => 'الإرسالات الواردة'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الرقم</th><th>المالك</th><th>الرحلة</th><th>الأصناف</th><th>الوزن</th><th>التاريخ</th></tr></thead>
                <tbody>
                    @forelse ($consignments as $consignment)
                        <tr>
                            <td class="num" style="font-weight:700">{{ $consignment->consignment_number }}</td>
                            <td>{{ $consignment->owner?->name }}</td>
                            <td class="num">{{ $consignment->trip?->trip_number ?? '—' }}</td>
                            <td>{{ $consignment->items->pluck('species.name_ar')->join('، ') }}</td>
                            <td class="num">{{ number_format($consignment->total_kg, 1) }} كجم</td>
                            <td class="num">{{ $consignment->sent_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا إرسالات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('partials.pagination', ['paginator' => $consignments])
@endsection
