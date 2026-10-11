@extends('layouts.app')

@section('title', 'طابور العد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'clipboard-check'])</div>
            <div>
                <h1>طابور العد</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل الرحلات', 'value' => number_format($counts['total']), 'icon' => 'route', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'بحاجة لموافقتك', 'value' => number_format($counts['awaiting']), 'icon' => 'inbox', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'جارية العد', 'value' => number_format($counts['counting']), 'icon' => 'scale', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'معدودة', 'value' => number_format($counts['counted']), 'icon' => 'clipboard-check', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الرحلة..."></label>
        <label class="field"><span>العرض</span>
            <select class="select" name="view" onchange="this.form.submit()">
                <option value="">كل الرحلات</option>
                <option value="awaiting" @selected($view === 'awaiting')>بحاجة لموافقتك</option>
                <option value="counting" @selected($view === 'counting')>جارية العد</option>
                <option value="counted" @selected($view === 'counted')>معدودة</option>
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.counter.trips') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @if (in_array($view, ['awaiting', 'counting'], true))
        {{-- شاشتا "بحاجة لموافقتك" و"النشطة" بطاقات كما في التطبيق؛ القائمة الكاملة جدول. --}}
        <div class="grid-2">
            @forelse ($trips as $trip)
                @include('panel.counter.partials.trip-card', ['trip' => $trip])
            @empty
                <div class="card" style="grid-column:1/-1"><p class="card-sub" style="padding:1.5rem 0;text-align:center">لا رحلات مطابقة</p></div>
            @endforelse
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>الرحلة</th><th>القارب</th><th>المالك</th><th>القبطان</th><th>ميناء العودة</th><th>العودة</th><th>المعلن / المعدود</th><th>الحالة</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($trips as $trip)
                        <tr>
                            <td><a href="{{ route('panel.counter.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                            <td>{{ $trip->boat?->name }}</td>
                            <td>{{ $trip->owner?->name ?? '—' }}</td>
                            <td>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }}</td>
                            <td>{{ $trip->returnPort?->name ?? $trip->departurePort?->name ?? '—' }}</td>
                            <td class="num" style="font-size:.74rem">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1) : '—' }} / {{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg, 1) : '—' }}</td>
                            <td>@include('panel.owner.partials.trip-badges', ['trip' => $trip])</td>
                            <td style="text-align:left"><a href="{{ route('panel.counter.trips.show', $trip) }}" class="icon-action" title="التفاصيل">@include('partials.icon', ['name' => 'chevron-left'])</a></td>
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
