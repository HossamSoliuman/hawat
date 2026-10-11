@extends('layouts.app')

@section('title', 'إدارة الموظفين')

@php
    $roleBadge = ['مشرف' => 'badge-ok', 'معالج' => 'badge-info', 'مستقبل طلبات' => 'badge-warn'];
    $filters = request()->only('section', 'q');
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-cog'])</div>
            <div>
                <h1>إدارة الموظفين</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openStaffForm()">@include('partials.icon', ['name' => 'user-plus']) إضافة موظف</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الموظفين', 'value' => $stats['total'], 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الموظفون النشطون', 'value' => $stats['active'], 'icon' => 'user-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'مهام قيد التنفيذ', 'value' => $stats['openTasks'], 'icon' => 'clipboard', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'مهام مكتملة', 'value' => $stats['completedTasks'], 'icon' => 'clipboard-check', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>القسم</span>
            <select class="select" name="section" onchange="this.form.submit()">
                <option value="">كل الأقسام ({{ $stats['total'] }})</option>
                @foreach ($sections as $name)
                    <option value="{{ $name }}" @selected($section === $name)>{{ $name }} ({{ $sectionCounts[$name] ?? 0 }})</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>بحث</span><input class="input" type="search" name="q" value="{{ $query }}" placeholder="الاسم، الرقم الوظيفي، البريد..."></label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('subadmin.staff-management') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cards-grid cols-3">
        @forelse ($staff as $member)
            @php
                $memberRequests = $requestsByStaff->get($member->id, collect());
                $memberTasks = $tasksByStaff->get($member->name, collect());

                // حمولة النافذة تُبنى هنا لا داخل السمة: @json بمصفوفة متعددة
                // الأسطر داخل onclick لا يُصرَّف صحيحًا.
                $payload = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'job_number' => $member->job_number,
                    'email' => $member->email,
                    'role' => $member->role,
                    'section' => $member->section,
                    'assigned_port_id' => $member->assigned_port_id,
                    'assigned_region_id' => $member->assigned_region_id,
                    'active' => $member->active,
                    'notes' => $member->notes,
                    'permissions' => collect($permissionFields)->mapWithKeys(fn ($field) => [$field => (bool) $member->{$field}]),
                    'service_type_ids' => $member->serviceTypes->pluck('id'),
                ];
            @endphp
            <div class="entity-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                    <div style="min-width:0">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                            <h3 style="font-size:.95rem;font-weight:700">{{ $member->name }}</h3>
                            <span class="badge {{ $roleBadge[$member->role] ?? 'badge-info' }}">{{ $member->role }}</span>
                            @unless ($member->active)<span class="pill pill-slate">غير نشط</span>@endunless
                        </div>
                        <div style="margin-top:.375rem;display:flex;flex-wrap:wrap;gap:.25rem .75rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                            @if ($member->job_number)<span>وظيفي: {{ $member->job_number }}</span>@endif
                            @if ($member->email)<span>{{ $member->email }}</span>@endif
                            <span>النطاق: {{ $member->scopeLabel() }}</span>
                        </div>
                    </div>
                    <div style="display:flex;flex-shrink:0;gap:.25rem">
                        <button type="button" class="icon-action" title="تعديل" onclick='openStaffForm(@json($payload))'>
                            @include('partials.icon', ['name' => 'pencil'])
                        </button>
                        <form method="POST" action="{{ route('subadmin.staff-management.destroy', ['staff' => $member] + $filters) }}" onsubmit="return confirm('حذف الموظف «{{ $member->name }}»؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('subadmin.staff-management.reassign', ['staff' => $member] + $filters) }}" style="margin-top:.75rem">
                    @csrf
                    <label class="field"><span>القسم</span>
                        <select class="select" name="section" onchange="this.form.submit()">
                            @foreach ($sections as $name)<option value="{{ $name }}" @selected($member->section === $name)>{{ $name }}</option>@endforeach
                        </select>
                    </label>
                </form>

                <div style="margin-top:.625rem;display:flex;flex-wrap:wrap;gap:.25rem">
                    @foreach ($permissionFields as $label => $field)
                        @if ($member->{$field})<span class="pill pill-emerald">{{ $label }}</span>@endif
                    @endforeach
                    @if (collect($permissionFields)->every(fn ($field) => ! $member->{$field}))
                        <span class="pill pill-slate">لا توجد صلاحيات</span>
                    @endif
                </div>

                <p style="margin-top:.5rem;font-size:.72rem;color:hsl(var(--muted-foreground))">الخدمات: {{ $member->handledServicesLabel() }}</p>

                <div class="mini-grid" style="grid-template-columns:repeat(3,1fr)">
                    <div class="mini"><div style="min-width:0"><p class="m-label">مهام</p><p class="m-value">{{ $memberTasks->count() }}</p></div></div>
                    <div class="mini"><div style="min-width:0"><p class="m-label">طلبات</p><p class="m-value">{{ $memberRequests->count() }}</p></div></div>
                    <div class="mini"><div style="min-width:0"><p class="m-label">مسند كليًا</p><p class="m-value">{{ $memberTasks->count() + $memberRequests->count() }}</p></div></div>
                </div>

                @if ($memberTasks->isNotEmpty() || $memberRequests->isNotEmpty())
                    <details style="margin-top:.75rem">
                        <summary style="cursor:pointer;font-size:.78rem;font-weight:600;color:hsl(var(--primary))">عرض المسند إليه</summary>
                        <div class="org-staff" style="border-top:0;padding:.5rem 0 0">
                            @foreach ($memberTasks->take(5) as $task)
                                <div class="org-staff-row">
                                    <span style="font-size:.75rem">{{ $task->title }}</span>
                                    <span class="pill pill-sky">{{ $task->status }}</span>
                                </div>
                            @endforeach
                            @foreach ($memberRequests->take(5) as $item)
                                <div class="org-staff-row">
                                    <span style="font-size:.75rem">{{ $item->request_number }} — {{ $item->serviceType->name }}</span>
                                    <span class="pill pill-amber">{{ $item->status }}</span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @empty
            <div class="pending-card">
                @include('partials.icon', ['name' => 'user-cog'])
                <h3>لا يوجد موظفون مطابقون</h3>
                <p>اضغط «إضافة موظف» لتسجيل موظف خدمات، أو ألغِ التصفية لعرض الجميع.</p>
            </div>
        @endforelse
    </div>

    <div class="note-box">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">التخويل والحذف</p>
            <p class="n-body">ترك خانة «كل الخدمات» مفعّلة يعني تخويلًا مفتوحًا يشمل أي خدمة تُضاف لاحقًا. وموظف له طلبات مسندة لا يُحذف — يُوقَف فيختفي من قوائم الإسناد ويبقى أثره في سجل المعالجة.</p>
        </div>
    </div>

    <div class="drawer-overlay" id="staffDrawer-overlay" onclick="toggleDrawer('staffDrawer', false)"></div>
    <div class="drawer wide" id="staffDrawer">
        <div class="drawer-head">
            <div>
                <h3 id="staffFormTitle">إضافة موظف خدمات</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">النطاق الفارغ تغطية على مستوى المملكة، والتخويل الفارغ يشمل كل الخدمات.</p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('staffDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="staffForm" action="{{ route('subadmin.staff-management.store', $filters) }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="staffMethod" value="POST">
            <div class="form-grid">
                <label class="field wide"><span>الاسم *</span><input class="input" name="name" id="s-name" required></label>
                <label class="field"><span>الرقم الوظيفي</span><input class="input" name="job_number" id="s-job"></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" id="s-email"></label>
                <label class="field"><span>الدور *</span>
                    <select class="select" name="role" id="s-role">
                        @foreach ($roles as $role)<option value="{{ $role }}">{{ $role }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القسم التابع له *</span>
                    <select class="select" name="section" id="s-section">
                        @foreach ($sections as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الميناء المسند</span>
                    <select class="select" name="assigned_port_id" id="s-port">
                        <option value="">كل الموانئ</option>
                        @foreach ($ports as $port)<option value="{{ $port->id }}">{{ $port->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المنطقة المسندة</span>
                    <select class="select" name="assigned_region_id" id="s-region">
                        <option value="">كل المناطق</option>
                        @foreach ($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>الحالة</span>
                    <select class="select" name="active" id="s-active">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </label>

                <div class="field wide">
                    <span>الصلاحيات</span>
                    <div style="display:flex;flex-wrap:wrap;gap:.75rem;border:1px solid hsl(var(--border));border-radius:.5rem;background:hsl(var(--muted) / .3);padding:.75rem">
                        @foreach ($permissionFields as $label => $field)
                            <label style="display:flex;align-items:center;gap:.375rem;font-size:.82rem">
                                <input type="checkbox" name="{{ $field }}" value="1" id="s-perm-{{ $field }}"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="field wide">
                    <span>الخدمات المخوّل بها</span>
                    <div style="display:flex;flex-wrap:wrap;gap:.75rem;border:1px solid hsl(var(--border));border-radius:.5rem;background:hsl(var(--muted) / .3);padding:.75rem">
                        <label style="display:flex;align-items:center;gap:.375rem;font-size:.82rem;font-weight:700;width:100%">
                            <input type="checkbox" name="all_services" value="1" id="s-all-services" checked onchange="syncServices()"> كل الخدمات
                        </label>
                        @foreach ($serviceTypes as $type)
                            <label style="display:flex;align-items:center;gap:.375rem;font-size:.82rem">
                                <input type="checkbox" class="s-service" name="service_type_ids[]" value="{{ $type->id }}"> {{ $type->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="field wide"><span>ملاحظات</span><textarea class="input" rows="2" name="notes" id="s-notes"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('staffDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ الموظف</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const staffStoreUrl = @json(route('subadmin.staff-management.store', $filters));
    const staffUpdateUrl = @json(route('subadmin.staff-management.update', ['staff' => '__ID__'] + $filters));
    const permissionFields = @json(array_values($permissionFields));

    function openStaffForm(member = null) {
        document.getElementById('staffFormTitle').textContent = member ? 'تعديل بيانات الموظف' : 'إضافة موظف خدمات';
        document.getElementById('staffMethod').value = member ? 'PUT' : 'POST';
        document.getElementById('staffForm').action = member ? staffUpdateUrl.replace('__ID__', member.id) : staffStoreUrl;

        const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value ?? ''; };
        set('s-name', member?.name);
        set('s-job', member?.job_number);
        set('s-email', member?.email);
        set('s-role', member?.role ?? @json($roles[1] ?? $roles[0]));
        set('s-section', member?.section ?? 'الخدمات والتراخيص');
        set('s-port', member?.assigned_port_id);
        set('s-region', member?.assigned_region_id);
        set('s-active', member ? (member.active ? '1' : '0') : '1');
        set('s-notes', member?.notes);

        permissionFields.forEach((field) => {
            document.getElementById('s-perm-' + field).checked = member ? !!member.permissions[field] : false;
        });

        const chosen = member?.service_type_ids ?? [];
        document.getElementById('s-all-services').checked = chosen.length === 0;
        document.querySelectorAll('.s-service').forEach((box) => {
            box.checked = chosen.includes(Number(box.value));
        });
        syncServices();

        toggleDrawer('staffDrawer', true);
    }

    // "كل الخدمات" يلغي التحديد الجزئي: التخويل المفتوح لا يُقيَّد بقائمة.
    function syncServices() {
        const all = document.getElementById('s-all-services').checked;
        document.querySelectorAll('.s-service').forEach((box) => {
            box.disabled = all;
            if (all) box.checked = false;
        });
    }
</script>
@endpush
