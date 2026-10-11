@extends('layouts.app')

@section('title', 'سجل العمليات')

@php
    $tones = ['إنشاء' => 'badge-ok', 'تعديل' => 'badge-info', 'حذف' => 'badge-danger', 'اعتماد' => 'badge-info', 'تصدير' => 'badge-warn'];
    $icons = ['إنشاء' => 'file-plus', 'تعديل' => 'file-edit', 'حذف' => 'trash', 'اعتماد' => 'badge-check', 'تسجيل دخول' => 'log-in', 'تصدير' => 'download'];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'history'])</div>
            <div>
                <h1>سجل العمليات</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي العمليات', 'value' => number_format($stats['total']), 'icon' => 'history', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إنشاء', 'value' => $stats['create'], 'icon' => 'file-plus', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'تعديل', 'value' => $stats['edit'], 'icon' => 'file-edit', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'حذف', 'value' => $stats['delete'], 'icon' => 'trash', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'اعتماد', 'value' => $stats['approve'], 'icon' => 'badge-check', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="المستخدم، الكيان، التفاصيل..."></label>
        <label class="field"><span>الإجراء</span>
            <select class="select" name="action" onchange="this.form.submit()">
                <option value="">كل الإجراءات</option>
                @foreach ($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('subadmin.audit-log') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>التاريخ والوقت</th><th>الإجراء</th><th>الكيان</th><th>السجل</th><th>المستخدم</th><th>الدور</th><th>التفاصيل</th><th>IP</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $log->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $tones[$log->action] ?? 'badge-info' }}">
                                @include('partials.icon', ['name' => $icons[$log->action] ?? 'history'])
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="font-weight:600">{{ $log->entity ?? '—' }}</td>
                        <td>{{ $log->record_label ?? '—' }}</td>
                        <td dir="ltr" style="font-size:.75rem">{{ $log->user_email ?? '—' }}</td>
                        <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $log->role ?? '—' }}</td>
                        <td style="font-size:.72rem;color:hsl(var(--muted-foreground));max-width:22rem">{{ $log->details ?? '—' }}</td>
                        <td dir="ltr" style="font-family:monospace;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $log->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد عمليات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
