@extends('layouts.app')

@section('title', 'تقويم المهام الإدارية')

@php
    $taskClass = [
        'مجدولة' => 'task-مجدولة',
        'قيد التنفيذ' => 'task-قيد',
        'مكتملة' => 'task-مكتملة',
        'متأخرة' => 'task-متأخرة',
        'ملغاة' => 'task-ملغاة',
    ];
    $filters = request()->only('position', 'status');
    $attr = fn ($value) => json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $dayUrl = fn (string $date) => route('subadmin.admin-tasks', $filters + ['month' => $cursor->format('Y-m'), 'day' => $date]);
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calendar-days'])</div>
            <div>
                <h1>تقويم المهام الإدارية</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openTaskForm(null, '{{ $day ?? $today }}')">
                @include('partials.icon', ['name' => 'plus']) مهمة جديدة
            </button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @if ($errors->any())
        <div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المهام', 'value' => $stats['total'], 'icon' => 'list-checks', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مجدولة', 'value' => $stats['scheduled'], 'icon' => 'clock', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'قيد التنفيذ', 'value' => $stats['inProgress'], 'icon' => 'refresh-cw', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'متأخرة', 'value' => $stats['overdue'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'مكتملة', 'value' => $stats['completed'], 'icon' => 'check-circle', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <input type="hidden" name="month" value="{{ $cursor->format('Y-m') }}">
        <label class="field"><span>القسم / المنصب</span>
            <select class="select" name="position" onchange="this.form.submit()">
                <option value="">كل الأقسام</option>
                @foreach ($positions as $position)<option value="{{ $position->id }}" @selected(request('position') == $position->id)>{{ $position->title }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('subadmin.admin-tasks') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cal-nav">
        <a href="{{ route('subadmin.admin-tasks', $filters + ['month' => $previous]) }}" class="icon-action" title="الشهر السابق">@include('partials.icon', ['name' => 'chevron-right'])</a>
        <h2>{{ $monthLabel }}</h2>
        <a href="{{ route('subadmin.admin-tasks', $filters + ['month' => $next]) }}" class="icon-action" title="الشهر التالي">@include('partials.icon', ['name' => 'chevron-left'])</a>
    </div>

    <div class="cal">
        <div class="cal-head">
            @foreach ($weekdays as $weekday)
                <span>{{ $weekday }}</span>
            @endforeach
        </div>
        <div class="cal-grid">
            @foreach ($cells as $cell)
                @if ($cell === null)
                    <div class="cal-cell is-blank"></div>
                @else
                    @php $dayTasksForCell = $byDate->get($cell, collect()); @endphp
                    <a href="{{ $dayUrl($cell) }}" class="cal-cell {{ $cell === $today ? 'is-today' : '' }} {{ $cell === $day ? 'is-selected' : '' }}">
                        <div class="cal-day">
                            <b>{{ (int) substr($cell, 8, 2) }}</b>
                            @if ($dayTasksForCell->isNotEmpty())<span>{{ $dayTasksForCell->count() }}</span>@endif
                        </div>
                        @foreach ($dayTasksForCell->take(3) as $task)
                            <div class="cal-task {{ $taskClass[$task->status] ?? 'task-مجدولة' }}">
                                <i class="dot-{{ $task->priority }}"></i>{{ $task->title }}
                            </div>
                        @endforeach
                        @if ($dayTasksForCell->count() > 3)
                            <div style="font-size:10px;color:hsl(var(--muted-foreground))">+{{ $dayTasksForCell->count() - 3 }} أخرى</div>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    @if ($day)
        <div class="card" style="margin-top:1.25rem">
            <div class="section-head">
                <p class="card-title">مهام يوم {{ $day }}</p>
                <a href="{{ route('subadmin.admin-tasks', $filters + ['month' => $cursor->format('Y-m')]) }}" class="link-more">إغلاق</a>
            </div>
            @forelse ($dayTasks as $task)
                <div class="org-staff-row" style="margin-bottom:.5rem">
                    <div style="min-width:0">
                        <p style="font-size:.85rem;font-weight:700">{{ $task->title }}</p>
                        <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">
                            {{ $task->position?->title }}@if ($task->assignee) · {{ $task->assignee->name }}@endif · {{ $task->task_type }} · {{ $task->section }}
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:.375rem">
                        <span class="pill pill-slate">@include('partials.icon', ['name' => 'shield-check']) {{ $task->required_permission }}</span>
                        <span class="cal-task {{ $taskClass[$task->status] ?? 'task-مجدولة' }}" style="margin:0">{{ $task->status }}</span>
                        @unless (in_array($task->status, \App\Models\AdminTask::CLOSED, true))
                            <form method="POST" action="{{ route('subadmin.admin-tasks.complete', $task) }}">
                                @csrf
                                <input type="hidden" name="month" value="{{ $cursor->format('Y-m') }}">
                                <button type="submit" class="icon-action" title="إنجاز">@include('partials.icon', ['name' => 'check-circle'])</button>
                            </form>
                        @endunless
                        <button type="button" class="icon-action" title="تعديل" onclick='openTaskForm({!! $attr($task->only(['id', 'title', 'description', 'org_position_id', 'assigned_staff_id', 'required_permission', 'task_type', 'priority', 'status', 'recurrence', 'notes']) + ['start_date' => $task->start_date?->toDateString(), 'due_date' => $task->due_date?->toDateString()]) !!})'>
                            @include('partials.icon', ['name' => 'pencil'])
                        </button>
                        <form method="POST" action="{{ route('subadmin.admin-tasks.destroy', $task) }}" onsubmit="return confirm('حذف المهمة «{{ $task->title }}»؟')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="month" value="{{ $cursor->format('Y-m') }}">
                            <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                        </form>
                    </div>
                </div>
            @empty
                <p style="padding:1.5rem;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد مهام في هذا اليوم.</p>
            @endforelse
            <button type="button" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:.5rem" onclick="openTaskForm(null, '{{ $day }}')">
                @include('partials.icon', ['name' => 'plus']) إضافة مهمة في هذا اليوم
            </button>
        </div>
    @endif

    <div class="table-card" style="margin-top:1.25rem">
        <table class="data-table">
            <thead>
                <tr><th>المهمة</th><th>القسم</th><th>المسؤول</th><th>الصلاحية المطلوبة</th><th>النوع</th><th>القسم المختص</th><th>الأولوية</th><th>الاستحقاق</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($monthTasks as $task)
                    <tr>
                        <td style="font-weight:600">{{ $task->title }}</td>
                        <td>{{ $task->position?->title ?? '—' }}</td>
                        <td>{{ $task->assignee?->name ?? 'بلا مسؤول' }}</td>
                        <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $task->required_permission }}</td>
                        <td>{{ $task->task_type }}</td>
                        <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $task->section }}</td>
                        <td><span class="cal-task" style="margin:0"><i class="dot-{{ $task->priority }}"></i>{{ $task->priority }}</span></td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $task->due_date?->toDateString() }}</td>
                        <td><span class="cal-task {{ $taskClass[$task->status] ?? 'task-مجدولة' }}" style="margin:0">{{ $task->isOverdue() ? 'متأخرة' : $task->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مهام في هذا الشهر</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="taskDrawer-overlay" onclick="toggleDrawer('taskDrawer', false)"></div>
    <div class="drawer wide" id="taskDrawer">
        <div class="drawer-head">
            <h3 id="taskFormTitle">مهمة إدارية جديدة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('taskDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="taskForm" action="{{ route('subadmin.admin-tasks.store') }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="taskMethod" value="POST">
            <input type="hidden" name="month" value="{{ $cursor->format('Y-m') }}">
            <label class="field"><span>عنوان المهمة *</span><input class="input" name="title" id="t-title" required placeholder="مراجعة طلبات رخص القوارب الشهرية"></label>
            <label class="field"><span>الوصف</span><textarea class="input" name="description" id="t-description" rows="2"></textarea></label>
            <div class="form-grid">
                <label class="field"><span>القسم / المنصب المرتبط *</span>
                    <select class="select" name="org_position_id" id="t-position" required>
                        <option value="">— اختر القسم —</option>
                        @foreach ($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>نوع المهمة *</span>
                    <select class="select" name="task_type" id="t-type">
                        @foreach ($types as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الصلاحية المطلوبة للإجراء *</span>
                    <select class="select" name="required_permission" id="t-permission" onchange="refreshEligibleStaff()">
                        @foreach ($permissions as $permission)<option value="{{ $permission }}">{{ $permission }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span id="t-staff-label">الموظف المسؤول</span>
                    <select class="select" name="assigned_staff_id" id="t-staff">
                        <option value="">— بلا مسؤول —</option>
                    </select>
                </label>
                <label class="field"><span>الأولوية *</span>
                    <select class="select" name="priority" id="t-priority">
                        @foreach ($priorities as $priority)<option value="{{ $priority }}">{{ $priority }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الحالة *</span>
                    <select class="select" name="status" id="t-status">
                        @foreach ($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>تاريخ البدء</span><input class="input" type="date" name="start_date" id="t-start"></label>
                <label class="field"><span>تاريخ الاستحقاق *</span><input class="input" type="date" name="due_date" id="t-due" required></label>
                <label class="field"><span>التكرار *</span>
                    <select class="select" name="recurrence" id="t-recurrence">
                        @foreach ($recurrences as $recurrence)<option value="{{ $recurrence }}">{{ $recurrence }}</option>@endforeach
                    </select>
                </label>
            </div>
            <label class="field"><span>ملاحظات</span><textarea class="input" name="notes" id="t-notes" rows="2"></textarea></label>
            <p style="font-size:.72rem;line-height:1.8;color:hsl(var(--muted-foreground))">
                القسم المختص يُشتق تلقائيًا من نوع المهمة، ولا يُسند العمل إلا لموظف يملك الصلاحية المطلوبة.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('taskDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const taskStoreUrl = @json(route('subadmin.admin-tasks.store'));
    const staffList = {!! $attr($staff->map(fn ($member) => $member->only(['id', 'name', 'status']) + ['position' => $member->position?->title] + collect($permissionFields)->mapWithKeys(fn ($field) => [$field => (bool) $member->{$field}])->all())->all()) !!};
    const permissionFieldMap = {!! $attr($permissionFields) !!};

    function eligibleStaff(permission) {
        return staffList.filter((member) => {
            if (member.status === 'متوقف') return false;
            if (permission === 'أي صلاحية') return Object.values(permissionFieldMap).some((field) => member[field]);
            const field = permissionFieldMap[permission];
            return field ? member[field] : false;
        });
    }

    function refreshEligibleStaff(selectedId = null) {
        const permission = document.getElementById('t-permission').value;
        const select = document.getElementById('t-staff');
        const eligible = eligibleStaff(permission);
        select.innerHTML = '<option value="">— بلا مسؤول —</option>';
        eligible.forEach((member) => {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.position ? member.name + ' — ' + member.position : member.name;
            select.appendChild(option);
        });
        if (selectedId && eligible.some((member) => String(member.id) === String(selectedId))) {
            select.value = selectedId;
        }
        document.getElementById('t-staff-label').textContent = 'الموظف المسؤول (' + eligible.length + ' مؤهل)';
    }

    function openTaskForm(task = null, defaultDate = '') {
        document.getElementById('taskFormTitle').textContent = task ? 'تعديل مهمة إدارية' : 'مهمة إدارية جديدة';
        document.getElementById('taskMethod').value = task ? 'PUT' : 'POST';
        document.getElementById('taskForm').action = task ? taskStoreUrl + '/' + task.id : taskStoreUrl;
        document.getElementById('t-title').value = task?.title ?? '';
        document.getElementById('t-description').value = task?.description ?? '';
        document.getElementById('t-position').value = task?.org_position_id ?? '';
        document.getElementById('t-type').value = task?.task_type ?? 'متابعة';
        document.getElementById('t-permission').value = task?.required_permission ?? 'أي صلاحية';
        document.getElementById('t-priority').value = task?.priority ?? 'عادية';
        document.getElementById('t-status').value = task?.status ?? 'مجدولة';
        document.getElementById('t-start').value = task?.start_date ?? '';
        document.getElementById('t-due').value = task?.due_date ?? defaultDate;
        document.getElementById('t-recurrence').value = task?.recurrence ?? 'بدون';
        document.getElementById('t-notes').value = task?.notes ?? '';
        refreshEligibleStaff(task?.assigned_staff_id ?? null);
        toggleDrawer('taskDrawer', true);
    }

    refreshEligibleStaff();
</script>
@endpush
