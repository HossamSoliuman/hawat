@extends('layouts.app')

@section('title', 'الصيادون')

@php
    $licenseBadge = fn ($s) => ['سارية' => 'badge-ok', 'قريبة الانتهاء' => 'badge-warn', 'منتهية' => 'badge-danger', 'ملغاة' => 'badge-danger'][$s] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'users'])</div>
            <div>
                <h1>الصيادون</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الصيادين', 'value' => number_format($stats['total']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نشطون', 'value' => number_format($stats['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'كباتن', 'value' => number_format($stats['captains']), 'icon' => 'ship', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'رخص سارية', 'value' => number_format($stats['valid']), 'icon' => 'shield-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'رخص تحتاج متابعة', 'value' => number_format($stats['attention']), 'icon' => 'alert-triangle', 'tone' => 'warning'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم، الهوية، رقم الرخصة..."></label>
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الدور</span>
            <select class="select" name="role" onchange="this.form.submit()">
                <option value="">كل الأدوار</option>
                @foreach ($roles as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة الرخصة</span>
            <select class="select" name="license" onchange="this.form.submit()">
                <option value="">كل الرخص</option>
                @foreach (['سارية', 'قريبة الانتهاء', 'منتهية', 'ملغاة'] as $s)<option value="{{ $s }}" @selected(request('license') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('fishers') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الصياد</th><th>رقم الهوية</th><th>الدور</th><th>رقم الرخصة</th><th>حالة الرخصة</th><th>الميناء</th><th>الجوال</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($fishers as $fisher)
                    <tr>
                        <td style="font-weight:600">{{ $fisher->name }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $fisher->national_id }}</td>
                        <td>{{ $fisher->role }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $fisher->license_number ?? '—' }}</td>
                        <td><span class="badge {{ $licenseBadge($fisher->license_status) }}">{{ $fisher->license_status }}</span></td>
                        <td>{{ $fisher->port?->name ?? '—' }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $fisher->phone ?? '—' }}</td>
                        <td><span class="badge {{ $fisher->status === 'نشط' ? 'badge-ok' : 'badge-info' }}">{{ $fisher->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا يوجد صيادون مطابقون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection