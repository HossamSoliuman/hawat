@extends('layouts.app')

@section('title', 'القوارب')

@php
    $statusBadge = fn ($s) => ['نشط' => 'badge-ok', 'في البحر' => 'badge-info', 'عاد للميناء' => 'badge-info', 'صيانة' => 'badge-warn', 'موقوف' => 'badge-danger'][$s] ?? 'badge-info';
    $licenseBadge = fn ($s) => ['سارية' => 'badge-ok', 'قريبة الانتهاء' => 'badge-warn', 'منتهية' => 'badge-danger', 'ملغاة' => 'badge-danger'][$s] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'ship'])</div>
            <div>
                <h1>القوارب</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي القوارب', 'value' => number_format($stats['total']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'قوارب نشطة', 'value' => number_format($stats['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($stats['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'رخص تحتاج متابعة', 'value' => number_format($stats['expiring']), 'icon' => 'alert-triangle', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'المصيد التراكمي', 'value' => number_format($stats['catch']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المخالفات', 'value' => number_format($stats['violations']), 'icon' => 'ban', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم القارب، الرقم، المالك..."></label>
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة التشغيل</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['نشط', 'في البحر', 'عاد للميناء', 'صيانة', 'موقوف'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة الرخصة</span>
            <select class="select" name="license" onchange="this.form.submit()">
                <option value="">كل الرخص</option>
                @foreach (['سارية', 'قريبة الانتهاء', 'منتهية', 'ملغاة'] as $s)<option value="{{ $s }}" @selected(request('license') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('boats') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>القارب</th><th>النوع</th><th>الطول</th><th>المالك</th><th>الكابتن</th><th>الطاقم</th><th>الميناء</th><th>المصيد (كجم)</th><th>الرخصة</th><th>الحالة</th><th>مخالفات</th></tr>
            </thead>
            <tbody>
                @forelse ($boats as $boat)
                    <tr>
                        <td><div style="font-weight:600">{{ $boat->name }}</div><div style="font-family:monospace;font-size:11px;color:hsl(var(--muted-foreground))">{{ $boat->boat_number }}</div></td>
                        <td>{{ $boat->boat_type ?? '—' }}</td>
                        <td>{{ $boat->length_m ? $boat->length_m.' م' : '—' }}</td>
                        <td>{{ $boat->owner ?? '—' }}</td>
                        <td>{{ $boat->captain ?? '—' }}</td>
                        <td>{{ $boat->crew_count }}</td>
                        <td>{{ $boat->port?->name ?? '—' }}</td>
                        <td style="font-weight:600">{{ number_format($boat->total_catch_kg) }}</td>
                        <td><span class="badge {{ $licenseBadge($boat->license_status) }}">{{ $boat->license_status }}</span></td>
                        <td><span class="badge {{ $statusBadge($boat->status) }}">{{ $boat->status }}</span></td>
                        <td style="text-align:center">{{ $boat->violations_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد قوارب مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection