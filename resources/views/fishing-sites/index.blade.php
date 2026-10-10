@extends('layouts.app')

@section('title', 'مواقع الصيد')

@php
    $pressureBadge = fn ($p) => ['طبيعي' => 'badge-ok', 'مراقبة' => 'badge-warn', 'ضغط مرتفع' => 'badge-danger', 'إنذار' => 'badge-danger'][$p] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'map-pin'])</div>
            <div>
                <h1>مواقع الصيد</h1>
                <p>مواقع الصيد المسجلة ومستوى ضغط الصيد على كل موقع</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('screens.sea-map') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'map']) عرض على الخريطة</a>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المواقع', 'value' => $stats['total'], 'icon' => 'map-pin', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'ضغط طبيعي', 'value' => $stats['normal'], 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'مراقبة', 'value' => $stats['watch'], 'icon' => 'activity', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'ضغط مرتفع', 'value' => $stats['high'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'إنذار', 'value' => $stats['alarm'], 'icon' => 'ban', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($stats['catch']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم الموقع..."></label>
        <label class="field"><span>أقرب ميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>مستوى الضغط</span>
            <select class="select" name="pressure" onchange="this.form.submit()">
                <option value="">كل المستويات</option>
                @foreach (['طبيعي', 'مراقبة', 'ضغط مرتفع', 'إنذار'] as $p)<option value="{{ $p }}" @selected(request('pressure') === $p)>{{ $p }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('fishing-sites') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الموقع</th><th>النوع</th><th>أقرب ميناء</th><th>المحافظة</th><th>الإحداثيات</th><th>المصيد (كجم)</th><th>مستوى الضغط</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($sites as $site)
                    <tr>
                        <td style="font-weight:600">{{ $site->name }}</td>
                        <td>{{ $site->site_type ?? '—' }}</td>
                        <td>{{ $site->port?->name ?? '—' }}</td>
                        <td>{{ $site->port?->governorate?->name ?? '—' }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $site->lat ?? '—' }}, {{ $site->lng ?? '—' }}</td>
                        <td style="font-weight:600">{{ number_format($site->catch_kg) }}</td>
                        <td><span class="badge {{ $pressureBadge($site->pressure_level) }}">{{ $site->pressure_level }}</span></td>
                        <td><span class="badge {{ $site->status === 'نشط' ? 'badge-ok' : 'badge-danger' }}">{{ $site->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مواقع مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection