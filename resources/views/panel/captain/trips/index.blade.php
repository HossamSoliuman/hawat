@extends('layouts.app')

@section('title', 'الرحلات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'route'])</div>
            <div>
                <h1>الرحلات</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل الرحلات', 'value' => number_format($counts['total']), 'icon' => 'route', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'بانتظارك', 'value' => number_format($counts['pending']), 'icon' => 'calendar', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($counts['active']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'مكتملة', 'value' => number_format($counts['completed']), 'icon' => 'check-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'ملغاة', 'value' => number_format($counts['cancelled']), 'icon' => 'x-circle', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الرحلة..."></label>
        <label class="field"><span>العرض</span>
            <select class="select" name="view" onchange="this.form.submit()">
                <option value="">كل الرحلات</option>
                <option value="pending" @selected($view === 'pending')>بانتظارك</option>
                <option value="active" @selected($view === 'active')>النشطة (في البحر)</option>
                <option value="completed" @selected($view === 'completed')>مكتملة</option>
                <option value="cancelled" @selected($view === 'cancelled')>ملغية</option>
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.captain.trips') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @if (in_array($view, ['pending', 'active'], true))
        {{-- شاشتا "بانتظارك" و"النشطة" بطاقات كما في التطبيق؛ القائمة الكاملة جدول. --}}
        <div class="grid-2">
            @forelse ($trips as $trip)
                @include('panel.captain.partials.trip-card', ['trip' => $trip])
            @empty
                <div class="card" style="grid-column:1/-1"><p class="card-sub" style="padding:1.5rem 0;text-align:center">لا رحلات مطابقة</p></div>
            @endforelse
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>الرحلة</th><th>القارب</th><th>الميناء</th><th>الترخيص</th><th>الانطلاق</th><th>العودة</th><th>المعلن / المعدود</th><th>الحالة</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($trips as $trip)
                        <tr>
                            <td><a href="{{ route('panel.captain.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                            <td>{{ $trip->boat?->name }}</td>
                            <td>{{ $trip->departurePort?->name }}</td>
                            <td>{{ $trip->tripType?->name ?? '—' }}</td>
                            <td class="num" style="font-size:.74rem">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="num" style="font-size:.74rem">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg) : '—' }} / {{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg) : '—' }}</td>
                            <td>
                                @include('panel.owner.partials.trip-badges', ['trip' => $trip])
                                @if ($trip->isCancelled() && $trip->cancel_reason)
                                    <div style="font-size:.68rem;color:hsl(var(--muted-foreground));margin-top:.2rem">{{ $trip->cancel_reason }}</div>
                                @endif
                            </td>
                            <td style="text-align:left"><a href="{{ route('panel.captain.trips.show', $trip) }}" class="icon-action" title="التفاصيل">@include('partials.icon', ['name' => 'chevron-left'])</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا رحلات مطابقة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
    @include('partials.pagination', ['paginator' => $trips])
@endsection
