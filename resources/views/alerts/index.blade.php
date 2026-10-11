@extends('layouts.app')

@section('title', 'مركز الإنذارات')

@php
    $severityLabel = ['حرج' => 'حرجة', 'مرتفع' => 'مرتفعة', 'متوسط' => 'متوسطة', 'منخفض' => 'منخفضة'];
    $severityBadge = ['حرج' => 'badge-danger', 'مرتفع' => 'badge-warn', 'متوسط' => 'badge-warn', 'منخفض' => 'badge-info'];
    $statusBadge = ['جديدة' => 'badge-info', 'قيد المعالجة' => 'badge-warn', 'تم الحل' => 'badge-ok'];
    $typeIcon = [
        'انخفاض المصيد' => 'fish',
        'ضغط صيد مرتفع' => 'map-pin',
        'رحلة غير معتمدة' => 'clock',
        'فرق مرتفع' => 'scale',
        'رخصة منتهية' => 'anchor',
        'صيد في منطقة محظورة' => 'shield-alert',
        'مخالفة موسمية' => 'shield-alert',
        'اقتراب موسم صيد' => 'calendar',
        'صيد عرضي لكائن حساس' => 'waves',
    ];
    $filters = request()->only('q', 'severity', 'type', 'status');
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bell-ring'])</div>
            <div>
                <h1>مركز الإنذارات</h1>
            </div>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('subadmin.alerts.generate') }}">
                @csrf
                @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'zap']) توليد الإنذارات تلقائيًا</button>
            </form>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ session('error') }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الإنذارات', 'value' => $stats['total'], 'icon' => 'bell-ring', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'حرجة', 'value' => $stats['critical'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'مرتفعة', 'value' => $stats['high'], 'icon' => 'alert-triangle', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'جديدة', 'value' => $stats['new'], 'icon' => 'clock', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'معيّن مسؤول', 'value' => $stats['assigned'], 'icon' => 'user-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'بلا مسؤول', 'value' => $stats['unassigned'], 'icon' => 'alert-octagon', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="العنوان، القارب، الميناء، المسؤول..."></label>
        <label class="field"><span>الخطورة</span>
            <select class="select" name="severity" onchange="this.form.submit()">
                <option value="">كل الدرجات</option>
                @foreach ($severities as $severity)<option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ $severity }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>النوع</span>
            <select class="select" name="type" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                @foreach ($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('subadmin.alerts') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @forelse ($groups as $group)
        @php $severity = $group['severity']; @endphp
        <details class="alert-group" open>
            <summary class="sev-{{ $severity }}">
                <div style="display:flex;align-items:center;gap:.625rem">
                    <span class="a-icon sev-icon-{{ $severity }}" style="height:2rem;width:2rem;display:flex;align-items:center;justify-content:center;border-radius:.5rem">
                        @include('partials.icon', ['name' => 'alert-triangle'])
                    </span>
                    <div>
                        <span class="g-title">أولوية {{ $severityLabel[$severity] ?? $severity }}</span>
                        <span class="g-meta">
                            {{ $group['items']->count() }} إنذار ·
                            {{ $group['items']->whereNotNull('assigned_to')->count() }} معيّن ·
                            {{ $group['items']->where('status', 'تم الحل')->count() }} محلول
                        </span>
                    </div>
                </div>
                @include('partials.icon', ['name' => 'chevron-down'])
            </summary>
            <div class="body">
                @foreach ($group['items'] as $alert)
                    <div class="alert-row sev-{{ $severity }}">
                        <div class="a-icon sev-icon-{{ $severity }}">
                            @include('partials.icon', ['name' => $typeIcon[$alert->type] ?? 'alert-triangle'])
                        </div>
                        <div style="min-width:0;flex:1">
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                                <h3 style="font-size:.875rem;font-weight:700">{{ $alert->title }}</h3>
                                <span class="badge {{ $severityBadge[$alert->severity] ?? 'badge-info' }}">{{ $alert->severity }}</span>
                                <span class="badge {{ $statusBadge[$alert->status] ?? 'badge-info' }}">{{ $alert->status }}</span>
                                @if ($alert->assigned_to)
                                    <span class="pill pill-emerald">@include('partials.icon', ['name' => 'user-check']) {{ $alert->assigned_to }}</span>
                                @endif
                            </div>
                            @if ($alert->description)<p style="margin-top:.375rem;font-size:.82rem;color:hsl(var(--muted-foreground))">{{ $alert->description }}</p>@endif
                            <div class="alert-meta">
                                <span>النوع: {{ $alert->type }}</span>
                                @if ($alert->region)<span>المنطقة: {{ $alert->region }}</span>@endif
                                @if ($alert->port)<span>الميناء: {{ $alert->port }}</span>@endif
                                @if ($alert->boat)<span>القارب: {{ $alert->boat }}</span>@endif
                                @if ($alert->species)<span>النوع السمكي: {{ $alert->species }}</span>@endif
                                @if ($alert->site)<span>الموقع: {{ $alert->site }}</span>@endif
                                @if ($alert->date)<span>التاريخ: {{ $alert->date->toDateString() }}</span>@endif
                                @if ($alert->resolution_note)<span>ملاحظة الإغلاق: {{ $alert->resolution_note }}</span>@endif
                            </div>
                        </div>
                        @if ($alert->status !== 'تم الحل')
                            <div style="display:flex;flex-wrap:wrap;flex-shrink:0;align-items:center;gap:.375rem">
                                <form method="POST" action="{{ route('subadmin.alerts.assign', $alert) }}" style="display:flex;gap:.375rem;align-items:center">
                                    @csrf
                                    @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                                    <select class="select" name="assigned_to" required style="width:12rem;padding:.35rem .5rem;font-size:.78rem">
                                        <option value="">— اختر المسؤول —</option>
                                        @foreach ($people as $person)<option value="{{ $person }}" @selected($alert->assigned_to === $person)>{{ $person }}</option>@endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem">
                                        @include('partials.icon', ['name' => 'user-check']) {{ $alert->assigned_to ? 'تغيير' : 'تعيين' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('subadmin.alerts.resolve', $alert) }}" style="display:flex;gap:.375rem;align-items:center">
                                    @csrf
                                    @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                                    <input class="input" name="resolution_note" placeholder="ملاحظة الإغلاق (اختياري)" style="width:12rem;padding:.35rem .5rem;font-size:.78rem">
                                    <button type="submit" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem">
                                        @include('partials.icon', ['name' => 'check-circle']) إغلاق
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'bell-ring'])
            <h3>لا توجد إنذارات مطابقة</h3>
            <p>اضغط «توليد الإنذارات تلقائيًا» لقراءة الرخص والرحلات والمواسم والصيد العرضي واشتقاق ما يستحق تنبيهًا.</p>
        </div>
    @endforelse

    <div class="note-box">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">قاعدة الإغلاق</p>
            <p class="n-body">لا يُغلق إنذار قبل تعيين مسؤول له: الإغلاق شهادة بأن أحدًا تابع الإنذار، لا مجرد إخفاء له من القائمة.</p>
        </div>
    </div>
@endsection
