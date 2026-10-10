@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'building'])</div>
            <div>
                <h1>{{ $company->name }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.company.applications') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'user-plus']) طلبات التوظيف</a>
            <a href="{{ route('panel.company.hiring') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calendar']) جولات التوظيف</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @if ($kpis['ports'] === 0)
        <div class="flash-error">لم يُسند إلى شركتك ميناء بعد — لا تُفتح جولة توظيف إلا في موانئك. يسندها المدير العام.</div>
    @endif

    @include('panel.company.partials.apply-link')

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الموانئ', 'value' => number_format($kpis['ports']), 'icon' => 'anchor', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'عدّادون عاملون', 'value' => number_format($kpis['counters']), 'icon' => 'users', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'طلبات بانتظارك', 'value' => number_format($kpis['pending']), 'icon' => 'inbox', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'جولات تستقبل', 'value' => number_format($kpis['open_rounds']), 'icon' => 'calendar', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'رحلات عُدّت هذا الشهر', 'value' => number_format($kpis['counted_month']), 'icon' => 'clipboard-check', 'tone' => 'primary'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'inbox', 'title' => 'أقدم الطلبات بانتظارك', 'note' => number_format($kpis['pending']).' طلب'])
            @forelse ($pending_applications as $item)
                <div style="display:flex;justify-content:space-between;gap:.75rem;padding:.6rem 0;border-bottom:1px solid var(--hair)">
                    <div>
                        <div style="font-weight:700">{{ $item->name }}</div>
                        <div class="card-sub">{{ $item->port?->name }} · {{ $item->experience_years !== null ? $item->experience_years.' سنوات خبرة' : 'بلا خبرة مذكورة' }}</div>
                    </div>
                    <span class="card-sub num">{{ $item->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا طلبات بانتظار المراجعة.</p>
            @endforelse
            @if ($kpis['pending'] > 0)
                <div style="margin-top:.6rem"><a href="{{ route('panel.company.applications') }}">راجع الطلبات ←</a></div>
            @endif
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'calendar', 'title' => 'الجولات المفتوحة', 'note' => $rounds->count().' جولة'])
            @forelse ($rounds as $round)
                <div style="display:flex;justify-content:space-between;gap:.75rem;padding:.6rem 0;border-bottom:1px solid var(--hair)">
                    <div>
                        <div style="font-weight:700">{{ $round->title }}</div>
                        <div class="card-sub">{{ $round->port?->name }} · حتى <bdi dir="ltr" class="num">{{ $round->closes_at->format('Y-m-d') }}</bdi></div>
                    </div>
                    <div style="text-align:left">
                        <span class="badge {{ $round->state_tone }}">{{ $round->state_label }}</span>
                        <div class="card-sub num">{{ $round->approved_count }} / {{ $round->seats }} مقعد</div>
                    </div>
                </div>
            @empty
                <p class="card-sub" style="padding:1.25rem 0;text-align:center">لا جولة مفتوحة — افتح جولة من صفحة جولات التوظيف.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'activity', 'title' => 'نشاط العدّادين', 'note' => 'الأنشط أولًا'])
        @include('panel.company.partials.activity-table', ['counters' => $activity, 'actions' => false])
        <div style="margin-top:.6rem"><a href="{{ route('panel.company.counters') }}">كل العدّادين ←</a></div>
    </div>
@endsection
