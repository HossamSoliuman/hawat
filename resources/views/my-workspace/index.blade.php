@extends('layouts.app')

@section('title', 'مساحتي')

@php
    $statusBadge = [
        'جديدة' => 'badge-info',
        'قيد المعالجة' => 'badge-warn',
        'بحاجة مستندات' => 'badge-warn',
        'بانتظار الاعتماد' => 'badge-info',
        'مجدولة' => 'badge-info',
        'قيد التنفيذ' => 'badge-warn',
        'متأخرة' => 'badge-danger',
        'مكتملة' => 'badge-ok',
    ];
    $severityBadge = ['حرج' => 'badge-danger', 'مرتفع' => 'badge-warn', 'متوسط' => 'badge-warn', 'منخفض' => 'badge-info'];
    $tab = request('tab', 'actions');
    $keep = ['staff' => request('staff'), 'period' => $period];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user'])</div>
            <div>
                <h1>مساحتي</h1>
            </div>
        </div>
        <form method="GET" class="actions">
            <input type="hidden" name="period" value="{{ $period }}">
            <label class="field">
                <select class="select" name="staff" onchange="this.form.submit()" style="width:20rem">
                    @forelse ($staff as $member)
                        <option value="{{ $member->id }}" @selected($me?->id === $member->id)>{{ $member->name }} · {{ $member->role }}</option>
                    @empty
                        <option value="">لا يوجد موظفون نشطون</option>
                    @endforelse
                </select>
            </label>
        </form>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @if ($me === null)
        <div class="pending-card">
            @include('partials.icon', ['name' => 'user-cog'])
            <h3>لا يوجد موظفون نشطون</h3>
            <p>أضف موظفًا من لوحة «إدارة الموظفين» لتظهر مساحته هنا.</p>
        </div>
    @else
        <div class="portal-hero">
            <div>
                <h2>{{ $me->name }}</h2>
                <p>{{ $me->role }} · قسم {{ $me->section }} · النطاق: {{ $me->scopeLabel() }}</p>
                <p style="margin-top:.375rem">التخصص: {{ $me->handledServicesLabel() }}</p>
            </div>
            <div class="tiles">
                <div class="tile"><b>{{ $approval->count() }}</b><span>بانتظار اعتمادي</span></div>
                <div class="tile"><b>{{ $processing->count() }}</b><span>بانتظار معالجتي</span></div>
                <div class="tile"><b>{{ $tasks->count() }}</b><span>مهامي</span></div>
                <div class="tile"><b>{{ $unread }}</b><span>إشعار غير مقروء</span></div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.25rem">
            <p class="card-title" style="margin-bottom:.75rem">صلاحياتي</p>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($permissionFields as $label => $field)
                    <span class="pill {{ $me->{$field} ? 'pill-emerald' : 'pill-slate' }}" style="{{ $me->{$field} ? '' : 'text-decoration:line-through' }}">{{ $label }}</span>
                @endforeach
            </div>
        </div>

        {{-- إجراءات فورية: ما يستحق التدخّل الآن، لا كل ما هو مفتوح --}}
        @php
            $immediate = [
                ['key' => 'approval', 'title' => 'بانتظار اعتمادي', 'icon' => 'shield-check', 'tone' => 'violet', 'items' => $me->can_approve ? $approval : collect()],
                ['key' => 'processing', 'title' => 'بانتظار معالجتي', 'icon' => 'refresh-cw', 'tone' => 'amber', 'items' => $me->can_process ? $processing->where('priority', 'عاجلة')->values() : collect()],
            ];
            $immediateCount = $immediate[0]['items']->count() + $immediate[1]['items']->count() + $urgentTasks->count() + $criticalAlerts->count();
        @endphp

        <div class="card" style="margin-bottom:1.25rem">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.75rem">
                <p class="card-title" style="display:flex;align-items:center;gap:.5rem">
                    @include('partials.icon', ['name' => 'zap']) إجراءات فورية
                </p>
                <span class="pill {{ $immediateCount > 0 ? 'pill-rose' : 'pill-emerald' }}">{{ $immediateCount }}</span>
            </div>

            @if ($immediateCount === 0)
                <p style="padding:2rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد إجراءات معلّقة — كل ما يخصّك مُنجَز.</p>
            @else
                @foreach ($immediate as $block)
                    @continue($block['items']->isEmpty())
                    <div style="margin-bottom:.75rem">
                        <p style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;font-size:.82rem;font-weight:700">
                            @include('partials.icon', ['name' => $block['icon']]) {{ $block['title'] }}
                            <span class="count-pill">{{ $block['items']->count() }}</span>
                        </p>
                        @foreach ($block['items']->take(4) as $item)
                            <div class="org-staff-row">
                                <div style="min-width:0">
                                    <p style="font-size:.82rem;font-weight:600">{{ $item->serviceType->name }} — {{ $item->fisher_name }}</p>
                                    <p style="margin-top:.125rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                                        {{ $item->request_number }}@if ($item->port) · {{ $item->port->name }}@endif @if ($item->priority === 'عاجلة') · عاجلة @endif
                                    </p>
                                </div>
                                <a href="{{ route('services.fisher-services', ['q' => $item->request_number]) }}" class="btn btn-outline" style="padding:.3rem .55rem;font-size:.7rem">فتح</a>
                            </div>
                        @endforeach
                        @if ($block['items']->count() > 4)
                            <p style="margin-top:.375rem;font-size:.72rem;color:hsl(var(--muted-foreground))">+ {{ $block['items']->count() - 4 }} أخرى…</p>
                        @endif
                    </div>
                @endforeach

                @if ($urgentTasks->isNotEmpty())
                    <div style="margin-bottom:.75rem">
                        <p style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;font-size:.82rem;font-weight:700">
                            @include('partials.icon', ['name' => 'list-checks']) مهام عاجلة أو متأخرة
                            <span class="count-pill">{{ $urgentTasks->count() }}</span>
                        </p>
                        @foreach ($urgentTasks->take(4) as $task)
                            <div class="org-staff-row">
                                <div style="min-width:0">
                                    <p style="font-size:.82rem;font-weight:600">{{ $task->title }}</p>
                                    <p style="margin-top:.125rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                                        {{ $task->status }} · الاستحقاق {{ $task->due_date?->toDateString() ?? '—' }}
                                    </p>
                                </div>
                                <a href="{{ route('subadmin.admin-tasks', ['month' => $task->due_date?->format('Y-m')]) }}" class="btn btn-outline" style="padding:.3rem .55rem;font-size:.7rem">فتح</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($criticalAlerts->isNotEmpty())
                    <div>
                        <p style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;font-size:.82rem;font-weight:700">
                            @include('partials.icon', ['name' => 'alert-triangle']) إنذارات حرجة أو مرتفعة
                            <span class="count-pill">{{ $criticalAlerts->count() }}</span>
                        </p>
                        @foreach ($criticalAlerts->take(4) as $alert)
                            <div class="org-staff-row">
                                <div style="min-width:0">
                                    <p style="font-size:.82rem;font-weight:600">{{ $alert->title }}</p>
                                    <p style="margin-top:.125rem;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $alert->type }} · {{ $alert->severity }}</p>
                                </div>
                                <a href="{{ route('subadmin.alerts') }}" class="btn btn-outline" style="padding:.3rem .55rem;font-size:.7rem">فتح</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- تقرير الأداء --}}
        <div class="card" style="margin-bottom:1.25rem">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem">
                <div>
                    <p class="card-title">تقرير الأداء</p>
                    <p style="margin-top:.125rem;font-size:.72rem;color:hsl(var(--muted-foreground))">إنتاجية وجودة والتزام — {{ $me->name }}</p>
                </div>
                <div class="seg">
                    @foreach ($periods as $key => $label)
                        <a href="{{ route('services.my-workspace', ['staff' => $me->id, 'period' => $key, 'tab' => $tab]) }}" class="{{ $period === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div class="gov-grid" style="grid-template-columns:repeat(3,1fr);gap:1rem">
                <div>
                    <p class="group-head" style="font-size:.82rem;font-weight:700;border-color:#7dd3fc">الإنتاجية</p>
                    <div class="set-row"><span class="s-label">طلبات معالَجة</span><span class="s-value">{{ $performance['processed'] }}</span></div>
                    <div class="set-row"><span class="s-label">طلبات معتمَدة بتوقيعي</span><span class="s-value">{{ $performance['approved'] }}</span></div>
                    <div class="set-row"><span class="s-label">مهام مكتملة</span><span class="s-value">{{ $performance['tasksCompleted'] }}</span></div>
                </div>
                <div>
                    <p class="group-head" style="font-size:.82rem;font-weight:700;border-color:#c4b5fd">الجودة</p>
                    <div class="set-row"><span class="s-label">نسبة الاعتماد</span><span class="s-value {{ $performance['approvedRatio'] >= 70 ? 'ok' : '' }}">{{ $performance['approvedRatio'] }}٪ <span style="font-weight:400;color:hsl(var(--muted-foreground))">({{ $performance['approved'] }}/{{ $performance['decisions'] }})</span></span></div>
                    <div class="set-row"><span class="s-label">معدّل الرفض</span><span class="s-value {{ $performance['rejectRate'] <= 15 ? 'ok' : '' }}">{{ $performance['rejectRate'] }}٪</span></div>
                    <div class="set-row"><span class="s-label">مرتجعة لمستندات</span><span class="s-value {{ $performance['returned'] === 0 ? 'ok' : '' }}">{{ $performance['returned'] }}</span></div>
                </div>
                <div>
                    <p class="group-head" style="font-size:.82rem;font-weight:700;border-color:#6ee7b7">الالتزام</p>
                    <div class="set-row"><span class="s-label">مهام متأخرة</span><span class="s-value {{ $performance['overdue'] === 0 ? 'ok' : '' }}">{{ $performance['overdue'] }}</span></div>
                    <div class="set-row"><span class="s-label">الإنجاز في الوقت</span><span class="s-value {{ $performance['onTimeRate'] >= 80 ? 'ok' : '' }}">{{ $performance['onTimeRate'] }}٪ <span style="font-weight:400;color:hsl(var(--muted-foreground))">({{ $performance['onTime'] }}/{{ $performance['closedTasks'] }})</span></span></div>
                    <div class="set-row"><span class="s-label">متوسط زمن المعالجة</span><span class="s-value">{{ $performance['avgDays'] === null ? '—' : $performance['avgDays'].' يوم' }}</span></div>
                </div>
            </div>
        </div>

        {{-- التبويبات --}}
        @php
            $tabs = [
                'actions' => ['label' => 'الاعتماد', 'icon' => 'shield-check', 'count' => $approval->count()],
                'processing' => ['label' => 'المعالجة', 'icon' => 'refresh-cw', 'count' => $processing->count()],
                'tasks' => ['label' => 'المهام', 'icon' => 'list-checks', 'count' => $tasks->count()],
                'alerts' => ['label' => 'التنبيهات', 'icon' => 'alert-triangle', 'count' => $alerts->count()],
                'notifications' => ['label' => 'الإشعارات', 'icon' => 'bell', 'count' => $unread],
            ];
        @endphp
        <div class="seg" style="margin-bottom:1rem;flex-wrap:wrap">
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('services.my-workspace', $keep + ['tab' => $key]) }}" class="{{ $tab === $key ? 'is-active' : '' }}">
                    @include('partials.icon', ['name' => $meta['icon']]) {{ $meta['label'] }} ({{ $meta['count'] }})
                </a>
            @endforeach
        </div>

        @if ($tab === 'actions')
            @include('staff-dashboard.queue', [
                'title' => 'قائمة الاعتماد',
                'icon' => 'shield-check',
                'tone' => 'violet',
                'items' => $approval,
                'empty' => 'لا توجد طلبات بانتظار اعتمادك.',
                'statusBadge' => $statusBadge,
            ])
        @elseif ($tab === 'processing')
            @include('staff-dashboard.queue', [
                'title' => 'قائمة المعالجة',
                'icon' => 'refresh-cw',
                'tone' => 'amber',
                'items' => $processing,
                'empty' => 'لا توجد طلبات بانتظار معالجتك.',
                'statusBadge' => $statusBadge,
            ])
        @elseif ($tab === 'tasks')
            @forelse ($tasks as $task)
                <div class="alert-row" style="border-right-color:#7dd3fc">
                    <div class="a-icon" style="background:#f0f9ff;color:#0369a1">@include('partials.icon', ['name' => 'list-checks'])</div>
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                            <h3 style="font-size:.875rem;font-weight:700">{{ $task->title }}</h3>
                            <span class="badge {{ $statusBadge[$task->status] ?? 'badge-info' }}">{{ $task->status }}</span>
                            @if ($task->priority === 'عاجلة')<span class="pill pill-rose">عاجلة</span>@endif
                        </div>
                        <div class="alert-meta">
                            @if ($task->position)<span>المنصب: {{ $task->position->title }}</span>@endif
                            <span>القسم المختص: {{ $task->section }}</span>
                            <span>النوع: {{ $task->task_type }}</span>
                            <span>الاستحقاق: {{ $task->due_date?->toDateString() ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="pending-card">
                    @include('partials.icon', ['name' => 'list-checks'])
                    <h3>لا توجد مهام إدارية مسندة إليك</h3>
                    <p>المهام تُسند من «تقويم المهام الإدارية» في قسم الإدارة الفرعية.</p>
                </div>
            @endforelse
        @elseif ($tab === 'alerts')
            @forelse ($alerts as $alert)
                <div class="alert-row" style="border-right-color:#fda4af">
                    <div class="a-icon" style="background:#fff1f2;color:#be123c">@include('partials.icon', ['name' => 'alert-triangle'])</div>
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                            <h3 style="font-size:.875rem;font-weight:700">{{ $alert->title }}</h3>
                            <span class="badge {{ $severityBadge[$alert->severity] ?? 'badge-info' }}">{{ $alert->severity }}</span>
                            <span class="badge badge-info">{{ $alert->status }}</span>
                        </div>
                        @if ($alert->description)<p style="margin-top:.375rem;font-size:.78rem;color:hsl(var(--muted-foreground))">{{ $alert->description }}</p>@endif
                        <div class="alert-meta">
                            <span>النوع: {{ $alert->type }}</span>
                            @if ($alert->region)<span>المنطقة: {{ $alert->region }}</span>@endif
                            @if ($alert->port)<span>الميناء: {{ $alert->port }}</span>@endif
                            @if ($alert->date)<span>التاريخ: {{ $alert->date->toDateString() }}</span>@endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="pending-card">
                    @include('partials.icon', ['name' => 'alert-triangle'])
                    <h3>لا توجد تنبيهات نشطة</h3>
                    <p>تُولَّد الإنذارات من «مركز الإنذارات» في قسم الإدارة الفرعية.</p>
                </div>
            @endforelse
        @else
            @forelse ($notifications as $notification)
                <div class="notif-card {{ $notification->read ? '' : 'is-unread' }}">
                    <div class="n-icon" style="background:{{ $notification->read ? 'hsl(var(--muted))' : '#d1fae5' }};color:{{ $notification->read ? 'hsl(var(--muted-foreground))' : '#047857' }}">
                        @include('partials.icon', ['name' => $notification->read ? 'mail-open' : 'bell'])
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                            <h3 style="font-size:.875rem;font-weight:700">{{ $notification->title }}</h3>
                            @unless ($notification->read)<span class="pill pill-emerald">جديد</span>@endunless
                            @if ($notification->priority === 'عاجلة')<span class="pill pill-rose">عاجلة</span>@endif
                        </div>
                        @if ($notification->body)<p class="n-body">{{ $notification->body }}</p>@endif
                        <div class="alert-meta">
                            <span>النوع: {{ $notification->notification_type }}</span>
                            @if ($notification->request_number)<span>الطلب: {{ $notification->request_number }}</span>@endif
                        </div>
                    </div>
                    @unless ($notification->read)
                        <form method="POST" action="{{ route('services.my-workspace.read', ['notification' => $notification] + $keep) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem">
                                @include('partials.icon', ['name' => 'check-circle']) تعليم كمقروء
                            </button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="pending-card">
                    @include('partials.icon', ['name' => 'bell'])
                    <h3>لا توجد إشعارات</h3>
                    <p>تصلك الإشعارات باسمك أو ببريدك من «التنبيهات الإدارية».</p>
                </div>
            @endforelse
        @endif
    @endif
@endsection
