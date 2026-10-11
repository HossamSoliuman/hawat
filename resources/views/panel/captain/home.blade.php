@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>مرحبًا {{ $user->name }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.captain.trips', ['view' => 'pending']) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'zap']) انطلق برحلتك القادمة</a>
            <a href="{{ route('panel.captain.trips', ['view' => 'active']) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'waves']) الرحلات النشطة</a>
            <a href="{{ route('panel.captain.trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'route']) قائمة الرحلات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'بانتظارك', 'value' => number_format($kpis['pending']), 'icon' => 'calendar', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($kpis['active']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار العدّاد', 'value' => number_format($kpis['awaiting_count']), 'icon' => 'clipboard', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'مكتملة', 'value' => number_format($kpis['completed']), 'icon' => 'check-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المصيد المعلن', 'value' => number_format($kpis['catch_kg']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'zap', 'title' => 'الرحلات التي بانتظارك'])
            @forelse ($pending_trips as $trip)
                @include('panel.captain.partials.trip-card', ['trip' => $trip])
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا رحلة بانتظارك الآن — يُسندها إليك المالك.</p>
            @endforelse
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'waves', 'title' => 'الرحلات النشطة'])
            @forelse ($active_trips as $trip)
                @include('panel.captain.partials.trip-card', ['trip' => $trip])
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا رحلة في البحر الآن.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'route', 'title' => 'آخر الرحلات'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الرحلة</th><th>القارب</th><th>الميناء</th><th>الانطلاق</th><th>المعلن</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recent_trips as $trip)
                        <tr>
                            <td><a href="{{ route('panel.captain.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                            <td>{{ $trip->boat?->name }}</td>
                            <td>{{ $trip->departurePort?->name }}</td>
                            <td class="num" style="font-size:.74rem">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1).' كجم' : '—' }}</td>
                            <td>@include('panel.owner.partials.trip-badges', ['trip' => $trip])</td>
                            <td style="text-align:left"><a href="{{ route('panel.captain.trips.show', $trip) }}" class="icon-action" title="التفاصيل">@include('partials.icon', ['name' => 'chevron-left'])</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا رحلات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
