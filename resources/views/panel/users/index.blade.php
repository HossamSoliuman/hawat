@extends('layouts.app')

@section('title', 'حسابات التطبيق')

@php
    use App\Models\Role;

    // الأدوار التابعة لمالك تُفرَض معه في النموذج؛ القائمة تصل إلى JS لتُظهر حقله.
    $ownerManaged = Role::OWNER_MANAGED;
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-cog'])</div>
            <div>
                <h1>حسابات التطبيق</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openUserForm()">@include('partials.icon', ['name' => 'user-plus']) حساب جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل الحسابات', 'value' => number_format($counts['total']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مفعّلة', 'value' => number_format($counts['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'ملاك', 'value' => number_format($counts['owners']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'دلالون', 'value' => number_format($counts['dalals']), 'icon' => 'store', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو البريد..."></label>
        <label class="field"><span>الدور</span>
            <select class="select" name="role" onchange="this.form.submit()">
                <option value="">كل الأدوار</option>
                @foreach ($roles as $role)<option value="{{ $role->key }}" @selected(request('role') === $role->key)>{{ $role->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="active" @selected(request('status') === 'active')>مفعّل</option>
                <option value="inactive" @selected(request('status') === 'inactive')>معطّل</option>
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.users') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>الجوال</th><th>البريد</th><th>الدور</th><th>يتبع</th><th>آخر دخول</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($users as $account)
                    <tr>
                        <td style="font-weight:600">{{ $account->name }}</td>
                        <td dir="ltr" style="font-family:monospace;text-align:right">{{ $account->phone ?? '—' }}</td>
                        <td dir="ltr" style="text-align:right;font-size:.74rem">{{ $account->email ?? '—' }}</td>
                        <td>{{ $account->appRole?->name }}</td>
                        <td>{{ $account->owner?->name ?? $account->operatingCompany?->name ?? '—' }}</td>
                        <td style="font-size:.74rem;color:hsl(var(--muted-foreground))">{{ $account->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}</td>
                        <td><span class="badge {{ $account->active ? 'badge-ok' : 'badge-danger' }}">{{ $account->active ? 'مفعّل' : 'معطّل' }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل"
                                    onclick='openUserForm({!! json_encode($account->only(['id', 'name', 'phone', 'email', 'role_id', 'owner_id', 'operating_company_id', 'active']) + ['port_id' => $account->statisticsOfficer?->port_id], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                    @include('partials.icon', ['name' => 'pencil'])
                                </button>
                                @unless ($account->is(auth()->user()))
                                    <form method="POST" action="{{ route('panel.users.toggle', $account) }}">
                                        @csrf
                                        <button type="submit" class="icon-action" title="{{ $account->active ? 'تعطيل' : 'تفعيل' }}">
                                            @include('partials.icon', ['name' => $account->active ? 'ban' : 'check-circle'])
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('panel.users.destroy', $account) }}" onsubmit="return confirm('حذف حساب «{{ $account->name }}»؟ يُفضَّل التعطيل إن كان له رحلات أو مبيعات.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا حسابات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $users])

    <div class="drawer-overlay" id="userDrawer-overlay" onclick="toggleDrawer('userDrawer', false)"></div>
    <div class="drawer" id="userDrawer">
        <div class="drawer-head">
            <h3 id="userFormTitle">حساب جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('userDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="userForm" action="{{ route('panel.users.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" id="userMethod" value="POST">
            {{-- المعرّف يرافق النموذج حتى يُعاد فتحه على الحساب نفسه بعد فشل التحقق. --}}
            <input type="hidden" name="id" id="u-id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم *</span><input class="input" name="name" id="u-name" required></label>
                <label class="field"><span>الدور *</span>
                    <select class="select" name="role_id" id="u-role" required onchange="syncOwnerField()">
                        <option value="">— اختر —</option>
                        @foreach ($roles as $role)<option value="{{ $role->id }}" data-key="{{ $role->key }}">{{ $role->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الجوال *</span><input class="input" name="phone" id="u-phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX" required></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" id="u-email" dir="ltr"></label>
                <label class="field" id="u-owner-field" hidden><span>يتبع المالك *</span>
                    <select class="select" name="owner_id" id="u-owner">
                        <option value="">— اختر المالك —</option>
                        @foreach ($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach
                    </select>
                </label>
                {{-- العدّاد وحده يُسأل عن ميناء: طابوره يُحسب عليه. --}}
                <label class="field" id="u-port-field" hidden><span>ميناء العمل *</span>
                    <select class="select" name="port_id" id="u-port">
                        <option value="">— اختر الميناء —</option>
                        @foreach ($ports as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                {{-- موظف شركة التشغيل يُسأل عن شركته: يدخل بوابتها. --}}
                <label class="field" id="u-company-field" hidden><span>الشركة *</span>
                    <select class="select" name="operating_company_id" id="u-company">
                        <option value="">— اختر الشركة —</option>
                        @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span id="u-password-label">كلمة المرور *</span><input class="input" type="password" name="password" id="u-password" dir="ltr" minlength="8" autocomplete="new-password"></label>
            </div>
            <label class="auth-remember" style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;cursor:pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" id="u-active" value="1" checked>
                الحساب مفعّل
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('userDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const storeUrl = @json(route('panel.users.store'));
    const ownerManaged = @json($ownerManaged);

    function syncOwnerField() {
        const role = document.getElementById('u-role');
        const key = role.options[role.selectedIndex]?.dataset.key;
        const needsOwner = ownerManaged.includes(key);
        document.getElementById('u-owner-field').hidden = !needsOwner;
        document.getElementById('u-owner').required = needsOwner;

        const needsPort = key === 'counter';
        document.getElementById('u-port-field').hidden = !needsPort;
        document.getElementById('u-port').required = needsPort;

        const needsCompany = key === 'company';
        document.getElementById('u-company-field').hidden = !needsCompany;
        document.getElementById('u-company').required = needsCompany;
    }

    function openUserForm(user = null) {
        const form = document.getElementById('userForm');
        document.getElementById('userFormTitle').textContent = user ? 'تعديل الحساب' : 'حساب جديد';
        document.getElementById('userMethod').value = user ? 'PUT' : 'POST';
        form.action = user ? storeUrl + '/' + user.id : storeUrl;
        document.getElementById('u-id').value = user?.id ?? '';
        document.getElementById('u-name').value = user?.name ?? '';
        document.getElementById('u-phone').value = user?.phone ?? '';
        document.getElementById('u-email').value = user?.email ?? '';
        document.getElementById('u-role').value = user?.role_id ?? '';
        document.getElementById('u-owner').value = user?.owner_id ?? '';
        document.getElementById('u-port').value = user?.port_id ?? '';
        document.getElementById('u-company').value = user?.operating_company_id ?? '';
        document.getElementById('u-active').checked = user ? Boolean(user.active) : true;
        // كلمة المرور تُطلب عند الإنشاء وحده؛ في التعديل تُترك فارغة لتبقى كما هي.
        const password = document.getElementById('u-password');
        password.required = !user;
        password.value = '';
        document.getElementById('u-password-label').textContent = user ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور *';
        syncOwnerField();
        toggleDrawer('userDrawer', true);
    }

    @if ($errors->any() && old('name') !== null)
        // فشل التحقق: يُعاد فتح النموذج بما أُدخل.
        openUserForm({!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
