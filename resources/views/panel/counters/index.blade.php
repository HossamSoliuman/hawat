@extends('layouts.app')

@section('title', 'العدّادون')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'clipboard-check'])</div>
            <div>
                <h1>العدّادون</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.companies') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'building']) شركات التشغيل</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل العدّادين', 'value' => number_format($counts['total']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'عدّادو الوزارة', 'value' => number_format($counts['ministry']), 'icon' => 'shield-check', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدّادو الشركات', 'value' => number_format($counts['companies']), 'icon' => 'building', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'موقوفون', 'value' => number_format($counts['suspended']), 'icon' => 'ban', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو الرقم الوظيفي..."></label>
        <label class="field"><span>الجهة</span>
            <select class="select" name="company" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="ministry" @selected(request('company') === 'ministry')>الوزارة</option>
                @foreach ($companies as $c)<option value="{{ $c->id }}" @selected(request('company') === (string) $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $p)<option value="{{ $p->id }}" @selected(request('port') === (string) $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="active" @selected(request('status') === 'active')>عامل</option>
                <option value="suspended" @selected(request('status') === 'suspended')>موقوف</option>
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.counters') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>العدّاد</th><th>الجوال</th><th>الجهة</th><th>الميناء</th><th>رحلات عدّها</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($counters as $counter)
                    <tr>
                        <td style="font-weight:600">{{ $counter->name }}<div class="card-sub num">{{ $counter->employee_number }}</div></td>
                        <td class="num" dir="ltr" style="text-align:right">{{ $counter->phone ?? '—' }}</td>
                        <td>
                            @if ($counter->company)
                                <a href="{{ route('panel.companies.show', $counter->company) }}">{{ $counter->company->name }}</a>
                            @else
                                الوزارة
                            @endif
                        </td>
                        <td>{{ $counter->port?->name }}</td>
                        <td class="num">{{ number_format($counter->trips_counted) }}</td>
                        <td>
                            <span class="badge {{ $counter->isSuspended() ? 'badge-danger' : 'badge-ok' }}">{{ $counter->status }}</span>
                            @if ($counter->isSuspended())
                                <div class="card-sub">{{ $counter->suspender?->name }} · <bdi dir="ltr" class="num">{{ $counter->suspended_at->format('Y-m-d') }}</bdi>@if ($counter->suspension_reason) — {{ $counter->suspension_reason }}@endif</div>
                            @endif
                        </td>
                        <td>
                            @include('panel.counters.partials.actions', [
                                'routes' => ['suspend' => 'panel.counters.suspend', 'reactivate' => 'panel.counters.reactivate', 'transfer' => 'panel.counters.transfer'],
                                'ports' => $counter->company ? $counter->company->ports : $ports,
                                'canReactivate' => true,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا عدّادين مطابقين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $counters])
@endsection
