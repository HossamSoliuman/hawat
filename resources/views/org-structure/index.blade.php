@extends('layouts.app')

@section('title', 'الهيكل التنظيمي')

@php
    $levelTone = [
        'وكيل وزارة' => 'level-amber',
        'مدير عام' => 'level-emerald',
        'مدير إدارة' => 'level-sky',
        'مدير قسم' => 'level-violet',
        'مسؤول' => 'level-cyan',
        'موظف مشرف' => 'level-slate',
    ];
    $staffTone = ['نشط' => 'badge-ok', 'إجازة' => 'badge-warn', 'مكلف' => 'badge-info', 'متوقف' => 'badge-danger'];
    $permissionFields = \App\Models\OrgStaff::PERMISSION_FIELDS;
    // ترميز آمن داخل سمة onclick ذات علامات اقتباس مفردة.
    $attr = fn ($value) => json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $positionFields = ['id', 'title', 'title_en', 'level', 'parent_id', 'authorities', 'responsibilities', 'linked_role', 'scope_level', 'reports_to', 'display_order', 'active'];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'git-branch'])</div>
            <div>
                <h1>الهيكل التنظيمي لقطاع الثروة السمكية</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openPositionForm()">@include('partials.icon', ['name' => 'plus']) إضافة منصب</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @if ($errors->any())
        <div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المناصب', 'value' => $stats['positions'], 'icon' => 'git-branch', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الشاغلون', 'value' => $stats['staff'], 'icon' => 'users', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستويات', 'value' => $stats['levels'], 'icon' => 'layers', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'مناصب شاغرة', 'value' => $stats['vacant'], 'icon' => 'alert-triangle', 'tone' => 'warning'])
    </div>

    <div class="filter-bar" style="margin-bottom:1.25rem;justify-content:space-between">
        <div class="seg">
            <a href="{{ route('subadmin.org-structure', ['view' => 'tree']) }}" class="{{ $view === 'tree' ? 'is-active' : '' }}">
                @include('partials.icon', ['name' => 'git-branch']) شجري
            </a>
            <a href="{{ route('subadmin.org-structure', ['view' => 'table', 'level' => request('level')]) }}" class="{{ $view === 'table' ? 'is-active' : '' }}">
                @include('partials.icon', ['name' => 'layers']) جدول
            </a>
        </div>
        @if ($view === 'table')
            <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end">
                <input type="hidden" name="view" value="table">
                <label class="field"><span>المستوى</span>
                    <select class="select" name="level" onchange="this.form.submit()">
                        <option value="">كل المستويات</option>
                        @foreach ($levels as $level)<option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>@endforeach
                    </select>
                </label>
            </form>
        @endif
    </div>

    @if ($positions->isEmpty())
        <div class="pending-card">
            @include('partials.icon', ['name' => 'git-branch'])
            <h3>لا توجد مناصب بعد</h3>
            <p>ابدأ ببناء الهيكل التنظيمي: أضف منصبًا جذرًا ثم علّق تحته بقية المناصب.</p>
        </div>
    @elseif ($view === 'tree')
        <div class="org-tree">
            @foreach ($tree as $node)
                @php $position = $node['position']; @endphp
                <div class="org-node {{ $levelTone[$position->level] ?? 'level-slate' }}" style="margin-right:{{ $node['depth'] * 1.75 }}rem">
                    <div class="org-node-head">
                        <div style="min-width:0;flex:1">
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                                <h3 style="font-size:.95rem;font-weight:700">{{ $position->title }}</h3>
                                @if ($position->title_en)<span dir="ltr" style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $position->title_en }}</span>@endif
                                <span class="pill {{ $levelTone[$position->level] ?? 'level-slate' }}">{{ $position->level }}</span>
                                <span class="pill pill-slate">@include('partials.icon', ['name' => 'shield-check']) {{ $roles[$position->linked_role] ?? $position->linked_role }}</span>
                                @unless ($position->active)<span class="badge badge-warn">موقوف</span>@endunless
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:.25rem 1rem;margin-top:.5rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                                <span>النطاق: {{ $scopes[$position->scope_level] ?? $position->scope_level }}</span>
                                <span>{{ $position->staff->count() }} شاغل</span>
                                @if ($position->parent)<span>تابع لـ: {{ $position->parent->title }}</span>@endif
                                @if ($position->reports_to)<span>يرفع تقاريره لـ: {{ $position->reports_to }}</span>@endif
                            </div>
                            @if ($position->authorityList())
                                <div style="display:flex;flex-wrap:wrap;gap:.375rem;margin-top:.5rem">
                                    @foreach ($position->authorityList() as $authority)
                                        <span class="tag tag-gulf">{{ $authority }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($position->responsibilities)
                                <p style="margin-top:.5rem;font-size:.72rem;line-height:1.8;color:hsl(var(--muted-foreground))">{{ $position->responsibilities }}</p>
                            @endif
                        </div>
                        <div style="display:flex;flex-shrink:0;gap:.25rem">
                            <button type="button" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem"
                                onclick="openStaffForm({{ $position->id }})">@include('partials.icon', ['name' => 'user-plus']) توظيف</button>
                            <button type="button" class="icon-action" title="تعديل" onclick='openPositionForm({!! $attr($position->only($positionFields)) !!})'>
                                @include('partials.icon', ['name' => 'pencil'])
                            </button>
                            <form method="POST" action="{{ route('subadmin.org-structure.destroy', $position) }}" onsubmit="return confirm('حذف «{{ $position->title }}»؟ المناصب التابعة له ستصبح جذورًا مستقلة.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                            </form>
                        </div>
                    </div>
                    @if ($position->staff->isNotEmpty())
                        <div class="org-staff">
                            @foreach ($position->staff as $member)
                                <div class="org-staff-row">
                                    <div style="min-width:0">
                                        <p style="font-size:.8rem;font-weight:600">{{ $member->name }}</p>
                                        <p style="font-size:.7rem;color:hsl(var(--muted-foreground))">
                                            {{ $member->rank }}@if ($member->job_number) · {{ $member->job_number }}@endif
                                            @if ($member->email) · <span dir="ltr">{{ $member->email }}</span>@endif
                                        </p>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:.375rem">
                                        @foreach ($permissionFields as $label => $field)
                                            @if ($member->{$field})<span class="tag tag-gulf">{{ $label }}</span>@endif
                                        @endforeach
                                        <span class="badge {{ $staffTone[$member->status] ?? 'badge-info' }}">{{ $member->status }}</span>
                                        @php
                                            $memberPayload = $member->only(['id', 'name', 'job_number', 'email', 'phone', 'rank', 'status', 'notes'])
                                                + ['start_date' => $member->start_date?->toDateString()]
                                                + collect($permissionFields)->mapWithKeys(fn ($field) => [$field => (bool) $member->{$field}])->all();
                                        @endphp
                                        <button type="button" class="icon-action" title="تعديل"
                                            onclick='openStaffForm({{ $position->id }}, {!! $attr($memberPayload) !!})'>
                                            @include('partials.icon', ['name' => 'pencil'])
                                        </button>
                                        <form method="POST" action="{{ route('subadmin.org-structure.staff.destroy', $member) }}" onsubmit="return confirm('حذف «{{ $member->name }}» من المنصب؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>المسمى</th><th>المستوى</th><th>الدور بالنظام</th><th>النطاق</th><th>الجهة الأم</th><th>الشاغلون</th><th>الحالة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $position)
                        <tr>
                            <td style="font-weight:600">{{ $position->title }}</td>
                            <td><span class="pill {{ $levelTone[$position->level] ?? 'level-slate' }}">{{ $position->level }}</span></td>
                            <td>{{ $roles[$position->linked_role] ?? $position->linked_role }}</td>
                            <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $scopes[$position->scope_level] ?? $position->scope_level }}</td>
                            <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $position->parent?->title ?? '—' }}</td>
                            <td style="font-weight:600">{{ $position->staff->count() }}</td>
                            <td><span class="badge {{ $position->active ? 'badge-ok' : 'badge-warn' }}">{{ $position->active ? 'نشط' : 'موقوف' }}</span></td>
                            <td>
                                <div style="display:flex;gap:.25rem">
                                    <button type="button" class="icon-action" title="توظيف" onclick="openStaffForm({{ $position->id }})">@include('partials.icon', ['name' => 'user-plus'])</button>
                                    <button type="button" class="icon-action" title="تعديل" onclick='openPositionForm({!! $attr($position->only($positionFields)) !!})'>
                                        @include('partials.icon', ['name' => 'pencil'])
                                    </button>
                                    <form method="POST" action="{{ route('subadmin.org-structure.destroy', $position) }}" onsubmit="return confirm('حذف «{{ $position->title }}»؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مناصب في هذا المستوى</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="drawer-overlay" id="positionDrawer-overlay" onclick="toggleDrawer('positionDrawer', false)"></div>
    <div class="drawer" id="positionDrawer">
        <div class="drawer-head">
            <h3 id="positionFormTitle">إضافة منصب تنظيمي</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('positionDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="positionForm" action="{{ route('subadmin.org-structure.store') }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="positionMethod" value="POST">
            <label class="field"><span>المسمى الوظيفي *</span><input class="input" name="title" id="p-title" required></label>
            <label class="field"><span>المسمى بالإنجليزية</span><input class="input" name="title_en" id="p-title-en" dir="ltr"></label>
            <div class="form-grid">
                <label class="field"><span>المستوى التنظيمي *</span>
                    <select class="select" name="level" id="p-level">
                        @foreach ($levels as $level)<option value="{{ $level }}">{{ $level }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الجهة الأم</span>
                    <select class="select" name="parent_id" id="p-parent">
                        <option value="">— لا توجد —</option>
                        @foreach ($positions as $option)<option value="{{ $option->id }}">{{ $option->title }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الدور المرتبط بالنظام *</span>
                    <select class="select" name="linked_role" id="p-role">
                        @foreach ($roles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>نطاق الصلاحية *</span>
                    <select class="select" name="scope_level" id="p-scope">
                        @foreach ($scopes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>يرفع تقاريره لـ</span><input class="input" name="reports_to" id="p-reports"></label>
                <label class="field"><span>ترتيب العرض</span><input class="input" type="number" min="0" name="display_order" id="p-order" value="0"></label>
            </div>
            <label class="field"><span>الصلاحيات (مفصولة بفاصلة)</span><textarea class="input" name="authorities" id="p-authorities" rows="2" placeholder="اعتماد الرخص، إسناد المهام"></textarea></label>
            <label class="field"><span>المهام والمسؤوليات</span><textarea class="input" name="responsibilities" id="p-responsibilities" rows="2"></textarea></label>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem">
                <input type="checkbox" name="active" id="p-active" value="1" checked> منصب نشط
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('positionDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="drawer-overlay" id="staffDrawer-overlay" onclick="toggleDrawer('staffDrawer', false)"></div>
    <div class="drawer" id="staffDrawer">
        <div class="drawer-head">
            <h3 id="staffFormTitle">إضافة موظف</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('staffDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="staffForm" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="staffMethod" value="POST">
            <label class="field"><span>اسم الموظف *</span><input class="input" name="name" id="s-name" required></label>
            <div class="form-grid">
                <label class="field"><span>الرقم الوظيفي</span><input class="input" name="job_number" id="s-job"></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" id="s-email" dir="ltr"></label>
                <label class="field"><span>رقم الجوال</span><input class="input" name="phone" id="s-phone" dir="ltr"></label>
                <label class="field"><span>تاريخ الالتحاق</span><input class="input" type="date" name="start_date" id="s-start"></label>
                <label class="field"><span>المرتبة *</span>
                    <select class="select" name="rank" id="s-rank">
                        @foreach ($ranks as $rank)<option value="{{ $rank }}">{{ $rank }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الحالة *</span>
                    <select class="select" name="status" id="s-status">
                        @foreach ($staffStatuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
                    </select>
                </label>
            </div>
            <div class="field">
                <span>صلاحيات الإجراء — تحدد المهام التي يجوز إسنادها إليه</span>
                <div style="display:flex;flex-wrap:wrap;gap:.75rem;padding-top:.25rem">
                    @foreach ($permissionFields as $label => $field)
                        <label style="display:flex;align-items:center;gap:.375rem;font-size:.82rem">
                            <input type="checkbox" name="{{ $field }}" id="s-{{ $field }}" value="1"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <label class="field"><span>ملاحظات</span><textarea class="input" name="notes" id="s-notes" rows="2"></textarea></label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('staffDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const positionStoreUrl = @json(route('subadmin.org-structure.store'));
    const staffBaseUrl = @json(url('subadmin/org-structure'));
    const permissionFields = {!! $attr(array_values($permissionFields)) !!};

    function openPositionForm(position = null) {
        document.getElementById('positionFormTitle').textContent = position ? 'تعديل منصب' : 'إضافة منصب تنظيمي';
        document.getElementById('positionMethod').value = position ? 'PUT' : 'POST';
        document.getElementById('positionForm').action = position ? positionStoreUrl + '/' + position.id : positionStoreUrl;
        document.getElementById('p-title').value = position?.title ?? '';
        document.getElementById('p-title-en').value = position?.title_en ?? '';
        document.getElementById('p-level').value = position?.level ?? 'موظف مشرف';
        document.getElementById('p-parent').value = position?.parent_id ?? '';
        document.getElementById('p-role').value = position?.linked_role ?? 'user';
        document.getElementById('p-scope').value = position?.scope_level ?? 'kingdom';
        document.getElementById('p-reports').value = position?.reports_to ?? '';
        document.getElementById('p-order').value = position?.display_order ?? 0;
        document.getElementById('p-authorities').value = position?.authorities ?? '';
        document.getElementById('p-responsibilities').value = position?.responsibilities ?? '';
        document.getElementById('p-active').checked = position ? !!position.active : true;
        toggleDrawer('positionDrawer', true);
    }

    function openStaffForm(positionId, staff = null) {
        document.getElementById('staffFormTitle').textContent = staff ? 'تعديل بيانات موظف' : 'إضافة موظف للمنصب';
        document.getElementById('staffMethod').value = staff ? 'PUT' : 'POST';
        document.getElementById('staffForm').action = staff
            ? staffBaseUrl + '/staff/' + staff.id
            : staffBaseUrl + '/' + positionId + '/staff';
        document.getElementById('s-name').value = staff?.name ?? '';
        document.getElementById('s-job').value = staff?.job_number ?? '';
        document.getElementById('s-email').value = staff?.email ?? '';
        document.getElementById('s-phone').value = staff?.phone ?? '';
        document.getElementById('s-start').value = staff?.start_date ?? '';
        document.getElementById('s-rank').value = staff?.rank ?? 'الرتبة الثالثة';
        document.getElementById('s-status').value = staff?.status ?? 'نشط';
        document.getElementById('s-notes').value = staff?.notes ?? '';
        permissionFields.forEach((field) => {
            document.getElementById('s-' + field).checked = !!staff?.[field];
        });
        toggleDrawer('staffDrawer', true);
    }
</script>
@endpush
