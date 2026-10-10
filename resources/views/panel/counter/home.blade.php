@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>مرحبًا {{ $user->name }}</h1>
                <p>{{ $port ? 'طابور '.$port->name.' — استلم الرحلة العائدة ثم عُدّ مصيدها صنفًا صنفًا' : 'لم يُسند إليك ميناء بعد — راجع الإدارة لربط حسابك بميناء عملك' }}</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.counter.trips', ['view' => 'awaiting']) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'inbox']) رحلات بحاجة لموافقتك</a>
            <a href="{{ route('panel.counter.trips', ['view' => 'counting']) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'scale']) الرحلات النشطة</a>
            <a href="{{ route('panel.counter.trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'route']) قائمة الرحلات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @unless ($port)
        <div class="flash-error">حسابك غير مربوط بميناء، فلا تظهر لك رحلات. يربطه المدير العام من صفحة حسابات التطبيق.</div>
    @endunless

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'بحاجة لموافقتك', 'value' => number_format($kpis['awaiting']), 'icon' => 'inbox', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'جارية العد', 'value' => number_format($kpis['counting']), 'icon' => 'scale', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عُدّت اليوم', 'value' => number_format($kpis['counted_today']), 'icon' => 'clipboard-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المصيد المعدود', 'value' => number_format($kpis['counted_kg'], 1), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'فرق العدّ', 'value' => number_format($kpis['diff_kg'], 1), 'unit' => 'كجم', 'icon' => 'git-compare', 'tone' => $kpis['diff_kg'] < 0 ? 'danger' : 'primary'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'inbox', 'title' => 'رحلات بحاجة لموافقتك', 'note' => $awaiting_trips->count().' رحلة — '.number_format($kpis['declared_kg'], 1).' كجم معلنة'])
            @forelse ($awaiting_trips as $trip)
                @include('panel.counter.partials.trip-card', ['trip' => $trip])
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا رحلة بانتظار الاستلام — تصلك حين يرسل كابتن مخرجاته.</p>
            @endforelse
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'scale', 'title' => 'الرحلات النشطة (جاهزة للعد)', 'note' => $counting_trips->count().' رحلة'])
            @forelse ($counting_trips as $trip)
                @include('panel.counter.partials.trip-card', ['trip' => $trip])
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا رحلة تحت العد الآن.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'clipboard-check', 'title' => 'آخر ما عددته', 'note' => number_format($kpis['counted']).' رحلة معدودة'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الرحلة</th><th>القارب</th><th>المالك</th><th>العودة</th><th>المعلن</th><th>المعدود</th><th>الفرق</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recent_trips as $trip)
                        <tr>
                            <td><a href="{{ route('panel.counter.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                            <td>{{ $trip->boat?->name }}</td>
                            <td>{{ $trip->owner?->name ?? '—' }}</td>
                            <td class="num" style="font-size:.74rem">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1) : '—' }}</td>
                            <td class="num">{{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg, 1) : '—' }}</td>
                            <td class="num" style="color:{{ (float) $trip->diff_kg < 0 ? 'var(--st-critical)' : 'inherit' }}">{{ $trip->diff_kg !== null ? number_format($trip->diff_kg, 1) : '—' }}</td>
                            <td style="text-align:left"><a href="{{ route('panel.counter.trips.report', $trip) }}" class="icon-action" title="التقرير المفصّل">@include('partials.icon', ['name' => 'file-text'])</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لم تعدّ رحلة بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
