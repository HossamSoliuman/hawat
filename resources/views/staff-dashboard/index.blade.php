@extends('layouts.app')

@section('title', 'لوحة الموظف')

@php
    $statusBadge = [
        'جديدة' => 'badge-info',
        'قيد المعالجة' => 'badge-warn',
        'بحاجة مستندات' => 'badge-warn',
        'بانتظار الاعتماد' => 'badge-info',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'shield-check'])</div>
            <div>
                <h1>لوحة الموظف</h1>
            </div>
        </div>
        <form method="GET" class="actions">
            <label class="field">
                <select class="select" name="staff" onchange="this.form.submit()" style="width:20rem">
                    @forelse ($staff as $member)
                        <option value="{{ $member->id }}" @selected($selected?->id === $member->id)>
                            {{ $member->name }} · {{ $member->role }} · {{ $member->scopeLabel() }}
                        </option>
                    @empty
                        <option value="">لا يوجد موظفون نشطون</option>
                    @endforelse
                </select>
            </label>
        </form>
    </div>

    @if ($selected === null)
        <div class="pending-card">
            @include('partials.icon', ['name' => 'user-cog'])
            <h3>لا يوجد موظفون نشطون</h3>
            <p>أضف موظفًا من لوحة «إدارة الموظفين» ليظهر هنا بقوائمه.</p>
        </div>
    @else
        <div class="portal-hero" style="background:linear-gradient(270deg,#0369a1,#0891b2)">
            <div>
                <h2>{{ $selected->name }}</h2>
                <p>{{ $selected->role }} · قسم {{ $selected->section }} · النطاق: {{ $selected->scopeLabel() }}</p>
                <p style="margin-top:.375rem">التخويل: {{ $selected->handledServicesLabel() }}</p>
            </div>
            <div class="tiles">
                <div class="tile"><b>{{ $approval->count() }}</b><span>بانتظار الاعتماد</span></div>
                <div class="tile"><b>{{ $processing->count() }}</b><span>بانتظار المعالجة</span></div>
                <div class="tile"><b>{{ $trips->count() }}</b><span>رحلة في النطاق</span></div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.25rem">
            <p class="card-title" style="margin-bottom:.75rem">الصلاحيات المعتمدة</p>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($permissionFields as $label => $field)
                    <span class="pill {{ $selected->{$field} ? 'pill-emerald' : 'pill-slate' }}" style="{{ $selected->{$field} ? '' : 'text-decoration:line-through' }}">{{ $label }}</span>
                @endforeach
                <span class="pill pill-sky">@include('partials.icon', ['name' => 'map-pin']) {{ $selected->scopeLabel() }}</span>
            </div>
            @if (! $selected->can_approve && ! $selected->can_process)
                <p style="margin-top:.75rem;font-size:.78rem;color:#b45309">هذا الموظف لا يملك صلاحية المعالجة ولا الاعتماد — لا توجد طلبات في انتظاره.</p>
            @endif
        </div>

        @if ($selected->can_approve)
            @include('staff-dashboard.queue', [
                'title' => 'بانتظار توقيعك واعتمادك',
                'icon' => 'shield-check',
                'tone' => 'violet',
                'items' => $approval,
                'empty' => 'لا توجد طلبات بانتظار اعتمادك.',
                'statusBadge' => $statusBadge,
            ])
        @endif

        @if ($selected->can_process)
            @include('staff-dashboard.queue', [
                'title' => 'بانتظار معالجتك',
                'icon' => 'refresh-cw',
                'tone' => 'amber',
                'items' => $processing,
                'empty' => 'لا توجد طلبات بانتظار معالجتك.',
                'statusBadge' => $statusBadge,
            ])
        @endif

        <section class="portal-group">
            <div class="head">
                <div class="badge-icon tone-sky">@include('partials.icon', ['name' => 'sailboat'])</div>
                <div>
                    <h3>رحلات النطاق الجغرافي</h3>
                    <p>مفلترة تلقائيًا بميناء الموظف أو منطقته — {{ $trips->count() }} رحلة</p>
                </div>
            </div>
            @forelse ($trips as $trip)
                <div class="alert-row" style="border-right-color:#7dd3fc">
                    <div class="a-icon" style="background:#f0f9ff;color:#0369a1">@include('partials.icon', ['name' => 'sailboat'])</div>
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                            <span style="font-family:monospace;font-size:.72rem;font-weight:700;color:hsl(var(--muted-foreground))">{{ $trip->trip_number }}</span>
                            <h3 style="font-size:.875rem;font-weight:700">{{ $trip->boat?->name ?? '—' }}</h3>
                            <span class="badge badge-info">{{ $trip->status }}</span>
                        </div>
                        <div class="alert-meta">
                            @if ($trip->departurePort)<span>ميناء الانطلاق: {{ $trip->departurePort->name }}</span>@endif
                            @if ($trip->departurePort?->governorate)<span>المحافظة: {{ $trip->departurePort->governorate->name }}</span>@endif
                            @if ($trip->captain_name)<span>الكابتن: {{ $trip->captain_name }}</span>@endif
                            @if ($trip->departure_time)<span>الانطلاق: {{ $trip->departure_time->format('Y-m-d H:i') }}</span>@endif
                            @if ($trip->gear_type)<span>أداة الصيد: {{ $trip->gear_type }}</span>@endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="pending-card">
                    @include('partials.icon', ['name' => 'sailboat'])
                    <h3>لا توجد رحلات في نطاق هذا الموظف</h3>
                    <p>النطاق مأخوذ من الميناء المسند، أو من المنطقة إن لم يُسند ميناء.</p>
                </div>
            @endforelse
        </section>

        <div class="note-box">
            @include('partials.icon', ['name' => 'shield-check'])
            <div>
                <p class="n-title">كيف تُبنى القائمتان</p>
                <p class="n-body">الطلب يظهر لمن يملك صلاحيته وتقع خدمته وميناؤه داخل تخويله. والطلب المسند لموظف لا يظهر في قائمة معالجة غيره — أما غير المسند فمفتوح لكل من يغطّي نطاقه.</p>
            </div>
        </div>
    @endif
@endsection
