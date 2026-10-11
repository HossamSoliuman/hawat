@extends('layouts.app')

@section('title', 'التنبيهات الإدارية')

@php
    $typeClass = [
        'طلب جديد' => 'type-طلب',
        'بانتظار الاعتماد' => 'type-اعتماد',
        'تذكير' => 'type-تذكير',
        'أخرى' => 'type-أخرى',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bell-ring'])</div>
            <div>
                <h1>التنبيهات الإدارية</h1>
            </div>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('subadmin.staff-notifications.read-all') }}">
                @csrf
                <input type="hidden" name="read" value="{{ $read }}">
                <input type="hidden" name="type" value="{{ request('type') }}">
                <button type="submit" class="btn btn-outline" @disabled($stats['unread'] === 0)>
                    @include('partials.icon', ['name' => 'check-check']) تعليم الكل كمقروء
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي التنبيهات', 'value' => $stats['total'], 'icon' => 'inbox', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'غير مقروءة', 'value' => $stats['unread'], 'icon' => 'mail-open', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => $stats['approval'], 'icon' => 'file-text', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عاجلة', 'value' => $stats['urgent'], 'icon' => 'alert-octagon', 'tone' => 'danger'])
    </div>

    <div class="filter-bar" style="margin-bottom:1.25rem;justify-content:space-between">
        <div class="seg">
            @foreach (['unread' => 'غير مقروءة', 'read' => 'مقروءة', 'all' => 'الكل'] as $value => $label)
                <a href="{{ route('subadmin.staff-notifications', ['read' => $value, 'type' => request('type')]) }}" class="{{ $read === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end">
            <input type="hidden" name="read" value="{{ $read }}">
            <label class="field"><span>النوع</span>
                <select class="select" name="type" onchange="this.form.submit()">
                    <option value="">كل الأنواع</option>
                    @foreach ($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
                </select>
            </label>
        </form>
    </div>

    @forelse ($notifications as $notification)
        <div class="notif-card {{ $notification->read ? '' : 'is-unread' }}">
            <div class="n-icon {{ $typeClass[$notification->notification_type] ?? 'type-أخرى' }}">
                @include('partials.icon', ['name' => 'bell-ring'])
            </div>
            <div style="min-width:0;flex:1">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                    <h3 style="font-size:.875rem;font-weight:700">{{ $notification->title }}</h3>
                    @unless ($notification->read)<span style="height:8px;width:8px;border-radius:9999px;background:hsl(var(--primary))"></span>@endunless
                    @if ($notification->priority === 'عاجلة')<span class="badge badge-danger">عاجلة</span>@endif
                    <span class="pill {{ $typeClass[$notification->notification_type] ?? 'type-أخرى' }}">{{ $notification->notification_type }}</span>
                </div>
                @if ($notification->body)<p class="n-body">{{ $notification->body }}</p>@endif
                <div style="display:flex;flex-wrap:wrap;gap:.25rem 1rem;margin-top:.5rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                    <span>{{ $notification->created_at?->format('Y-m-d H:i') }}</span>
                    @if ($notification->recipient_name)<span>المستلم: {{ $notification->recipient_name }}</span>@endif
                    @if ($notification->request_number)<span>الطلب: {{ $notification->request_number }}</span>@endif
                    @if ($notification->read && $notification->read_at)<span style="color:#047857">قُرئ: {{ $notification->read_at->format('Y-m-d H:i') }}</span>@endif
                </div>
            </div>
            @unless ($notification->read)
                <form method="POST" action="{{ route('subadmin.staff-notifications.read', $notification) }}">
                    @csrf
                    <input type="hidden" name="read" value="{{ $read }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <button type="submit" class="btn btn-outline" style="padding:.35rem .7rem;font-size:.72rem">تعليم كمقروء</button>
                </form>
            @endunless
        </div>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'bell-ring'])
            <h3>لا توجد تنبيهات مطابقة</h3>
            <p>التنبيهات تُنشأ تلقائيًا عند وصول طلب يحتاج توقيعًا أو اعتمادًا إداريًا.</p>
        </div>
    @endforelse
@endsection
