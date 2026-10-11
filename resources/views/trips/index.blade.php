@extends('layouts.app')

@section('title', 'رحلات الصيد')

@php
    $tripBadge = fn ($s) => ['معتمدة' => 'badge-ok', 'في البحر' => 'badge-info', 'بانتظار الاعتماد' => 'badge-warn', 'تحت الإحصاء' => 'badge-warn', 'بانتظار الإحصاء' => 'badge-info'][$s] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'sailboat'])</div>
            <div>
                <h1>رحلات الصيد</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الرحلات', 'value' => number_format($stats['total']), 'icon' => 'sailboat', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($stats['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار الإحصاء', 'value' => number_format($stats['pending_stats']), 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => number_format($stats['pending_approval']), 'icon' => 'alert-triangle', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'رحلات معتمدة', 'value' => number_format($stats['approved']), 'icon' => 'badge-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($stats['approved_kg']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الرحلة، القارب، الكابتن..."></label>
        <label class="field"><span>ميناء الانطلاق</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة الرحلة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('trips') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الرحلة</th><th>القارب</th><th>الكابتن</th><th>الميناء</th><th>الانطلاق</th><th>العودة</th><th>المدة</th><th>إدخال الكابتن</th><th>الوزن الفعلي</th><th>الفرق</th><th>المعتمد</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $trip->trip_number }}</td>
                        <td>{{ $trip->boat?->name ?? '—' }}</td>
                        <td>{{ $trip->captain_name ?? '—' }}</td>
                        <td>{{ $trip->departurePort?->name ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $trip->departure_time?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $trip->duration_hours ? $trip->duration_hours.' س' : '—' }}</td>
                        <td>{{ $trip->captain_input_kg ? number_format($trip->captain_input_kg) : '—' }}</td>
                        <td>{{ $trip->actual_weight_kg ? number_format($trip->actual_weight_kg) : '—' }}</td>
                        <td style="color:{{ abs((float) $trip->diff_kg) > 0 ? '#e11d48' : 'inherit' }}">{{ $trip->diff_kg !== null ? number_format($trip->diff_kg, 1) : '—' }}</td>
                        <td style="font-weight:600">{{ $trip->approved_kg ? number_format($trip->approved_kg) : '—' }}</td>
                        <td><span class="badge {{ $tripBadge($trip->status) }}">{{ $trip->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد رحلات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection