@extends('layouts.app')

@section('title', 'الموانئ والمراسي')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'anchor'])</div>
            <div>
                <h1>الموانئ والمراسي</h1>
                <p>مؤشرات تشغيل كل ميناء: القوارب، الصيادون، الرحلات، والإحصاء</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('screens.ports-compare') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'bar-chart']) مقارنة الموانئ</a>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الموانئ', 'value' => $stats['total'], 'icon' => 'anchor', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي القوارب', 'value' => number_format($stats['boats']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => number_format($stats['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الصيادون', 'value' => number_format($stats['fishers']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($stats['catch']), 'unit' => 'طن', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'موظفو الإحصاء', 'value' => number_format($stats['staff']), 'icon' => 'clipboard-check', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم الميناء أو الرمز..."></label>
        <label class="field"><span>المحافظة</span>
            <select class="select" name="governorate" onchange="this.form.submit()">
                <option value="">كل المحافظات</option>
                @foreach ($governorates as $gov)<option value="{{ $gov->id }}" @selected(request('governorate') == $gov->id)>{{ $gov->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['نشط', 'متوقف', 'صيانة'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('ports') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cards-grid cols-3">
        @forelse ($ports as $port)
            @php $pct = $port->boats_count ? round($port->active_boats / $port->boats_count * 100) : 0; @endphp
            <a href="{{ route('ports.show', $port) }}" class="entity-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div>
                        <h3 style="display:flex;align-items:center;gap:.5rem;font-weight:700">@include('partials.icon', ['name' => 'anchor']) {{ $port->name }}</h3>
                        <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $port->governorate?->name }} — {{ $port->governorate?->region?->name }}</p>
                    </div>
                    <span class="badge {{ $port->status === 'نشط' ? 'badge-ok' : 'badge-warn' }}">{{ $port->status }}</span>
                </div>
                <div class="mini-grid">
                    <div class="mini">@include('partials.icon', ['name' => 'ship'])<div><p class="m-label">القوارب</p><p class="m-value">{{ number_format($port->boats_count) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'check-circle'])<div><p class="m-label">النشطة</p><p class="m-value">{{ number_format($port->active_boats) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'users'])<div><p class="m-label">الصيادون</p><p class="m-value">{{ number_format($port->fishers_count) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'sailboat'])<div><p class="m-label">رحلات/يوم</p><p class="m-value">{{ $port->daily_trips }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">المصيد (طن)</p><p class="m-value">{{ number_format($port->total_catch_tons) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'clipboard-check'])<div><p class="m-label">موظفو الإحصاء</p><p class="m-value">{{ $port->statistics_staff }}</p></div></div>
                </div>
                <div style="margin-top:.75rem">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:.25rem"><span style="color:hsl(var(--muted-foreground))">نسبة النشاط</span><span style="font-weight:600">{{ $pct }}%</span></div>
                    <div class="progress"><div style="width:{{ $pct }}%;background:{{ $pct >= 70 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#f43f5e') }}"></div></div>
                </div>
            </a>
        @empty
            <div class="card" style="grid-column:1/-1;padding:2.5rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد موانئ مطابقة</div>
        @endforelse
    </div>
@endsection