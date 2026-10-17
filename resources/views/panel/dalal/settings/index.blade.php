@extends('layouts.app')

@section('title', 'إعدادات الدلال')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'settings'])</div>
            <div>
                <h1>إعدادات النظام</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.profile') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'key-round']) الاسم وكلمة المرور</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <nav class="seg" aria-label="الأقسام" style="margin-bottom:1.25rem">
        @foreach (['profile' => ['الملف التجاري والدكة', 'user'], 'company' => ['الشركة', 'building'], 'workers' => ['عمالة الدكة', 'users']] as $key => [$label, $icon])
            <a href="{{ route('panel.dalal.settings', ['tab' => $key]) }}" class="{{ $tab === $key ? 'is-active' : '' }}">@include('partials.icon', ['name' => $icon]) {{ $label }}</a>
        @endforeach
    </nav>

    @if ($tab === 'company')
        <div class="grid-3">
            <form method="POST" action="{{ route('panel.dalal.settings.update') }}" class="card span-2" style="display:flex;flex-direction:column;gap:1rem">
                @csrf @method('PUT')
                @include('partials.section-head', ['icon' => 'building', 'title' => 'معلومات الشركة'])
                <div class="form-grid cols-2">
                    <label class="field"><span>اسم الشركة</span><input class="input" name="company_name" value="{{ old('company_name', $profile->company_name) }}"></label>
                    <label class="field"><span>رقم السجل التجاري</span><input class="input num" name="cr_number" value="{{ old('cr_number', $profile->cr_number) }}" dir="ltr"></label>
                    <label class="field"><span>الرقم الضريبي</span><input class="input num" name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" dir="ltr"></label>
                    <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="company_email" value="{{ old('company_email', $profile->company_email) }}" dir="ltr"></label>
                    <label class="field"><span>رقم الهاتف</span><input class="input num" name="company_phone" value="{{ old('company_phone', $profile->company_phone) }}" dir="ltr"></label>
                    <label class="field"><span>الموقع الإلكتروني</span><input class="input" name="website" value="{{ old('website', $profile->website) }}" dir="ltr" placeholder="https://"></label>
                    <label class="field wide"><span>العنوان</span><input class="input" name="address" value="{{ old('address', $profile->address) }}"></label>
                </div>
                <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary">@include('partials.icon', ['name' => 'save']) حفظ المعلومات</button></div>
            </form>

            <div class="card" style="display:flex;flex-direction:column;gap:1rem">
                @include('partials.section-head', ['icon' => 'file-text', 'title' => 'شعار الشركة'])
                <div style="display:flex;align-items:center;justify-content:center;min-height:9rem;border:1px dashed hsl(var(--border))">
                    @if ($profile->logo_url)
                        <img src="{{ $profile->logo_url }}" alt="الشعار" style="max-height:8rem;max-width:100%">
                    @else
                        <span class="card-sub">لا شعار بعد</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('panel.dalal.settings.logo') }}" enctype="multipart/form-data" style="display:flex;gap:.5rem;flex-wrap:wrap">
                    @csrf
                    <input class="input" type="file" name="logo" accept="image/png,image/jpeg,image/webp" required style="flex:1">
                    <button class="btn btn-primary">رفع</button>
                </form>
                @if ($profile->logo_path)
                    <form method="POST" action="{{ route('panel.dalal.settings.logo.remove') }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline" style="width:100%">حذف الشعار</button>
                    </form>
                @endif
            </div>
        </div>
    @elseif ($tab === 'workers')
        <div class="grid-3">
            <div class="card span-2">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem">
                    @include('partials.section-head', ['icon' => 'users', 'title' => 'عمال الدكة'])
                    <button type="button" class="btn btn-primary" onclick="openDrawerForm(workerForm)">@include('partials.icon', ['name' => 'plus']) عامل جديد</button>
                </div>
                <form method="GET" class="filter-bar" style="margin:.75rem 0 1rem">
                    <input type="hidden" name="tab" value="workers">
                    <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو الهوية..."></label>
                    <label class="field"><span>الحالة</span>
                        <select class="select" name="status" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            @foreach (\App\Models\DalalWorker::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
                        </select>
                    </label>
                    <button class="btn btn-primary">تصفية</button>
                    <a href="{{ route('panel.dalal.settings', ['tab' => 'workers']) }}" class="btn btn-outline">إعادة تعيين</a>
                </form>
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>العامل</th><th>النوع</th><th>الجنسية</th><th>الهوية</th><th>الجوال</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr>
                                    <td><div class="person-cell">@include('partials.person-avatar', ['url' => $worker->avatar_url, 'name' => $worker->name])<span style="font-weight:600">{{ $worker->name }}</span></div></td>
                                    <td>{{ $worker->type?->name ?? '—' }}</td>
                                    <td>{{ $worker->nationality ?? '—' }}</td>
                                    <td class="num">{{ $worker->id_number ?? '—' }}<div style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $worker->idType?->name }}</div></td>
                                    <td dir="ltr" class="num" style="text-align:right">{{ $worker->phone ?? '—' }}</td>
                                    <td><span class="badge {{ $worker->status === 'نشط' ? 'badge-ok' : 'badge-danger' }}">{{ $worker->status }}</span></td>
                                    <td>
                                        <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                            <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(workerForm, {!! json_encode($worker->only(['id', 'name', 'phone', 'nationality', 'id_type_id', 'id_number', 'dalal_worker_type_id', 'status', 'notes']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                            <form method="POST" action="{{ route('panel.dalal.settings.workers.toggle', $worker) }}">
                                                @csrf
                                                <button type="submit" class="icon-action" title="{{ $worker->status === 'نشط' ? 'تعطيل' : 'تفعيل' }}">@include('partials.icon', ['name' => $worker->status === 'نشط' ? 'ban' : 'check-circle'])</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لم يُسجَّل عمال بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.pagination', ['paginator' => $workers])
            </div>
            <div class="card" style="display:flex;flex-direction:column;gap:1rem">
                @include('partials.section-head', ['icon' => 'scale', 'title' => 'أعداد العمالة'])
                <table class="data-table">
                    <thead><tr><th>النوع</th><th>الجنسية</th><th>العدد</th></tr></thead>
                    <tbody>
                        @forelse ($workforce['rows'] as $row)
                            <tr><td>{{ $row['type']?->name ?? '—' }}</td><td>{{ $row['nationality'] ?? '—' }}</td><td class="num">{{ $row['count'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" style="padding:1rem;text-align:center;color:hsl(var(--muted-foreground))">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($workforce['source'] === 'records' && $legacyCounts->isNotEmpty())
                    @include('partials.section-head', ['icon' => 'archive', 'title' => 'الأعداد السابقة'])
                    <table class="data-table">
                        <tbody>
                            @foreach ($legacyCounts as $row)
                                <tr>
                                    <td>{{ $row->type?->name }}</td><td>{{ $row->nationality ?? '—' }}</td><td class="num">{{ $row->count }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('panel.dalal.settings.worker-counts.destroy', $row) }}" onsubmit="return confirm('حذف السطر؟')" style="display:flex;justify-content:flex-end">
                                            @csrf @method('DELETE')
                                            <button class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="drawer-overlay" id="workerDrawer-overlay" onclick="toggleDrawer('workerDrawer', false)"></div>
        <div class="drawer" id="workerDrawer">
            <div class="drawer-head">
                <h3 id="workerFormTitle">عامل جديد</h3>
                <button type="button" class="icon-action" onclick="toggleDrawer('workerDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
            </div>
            <form method="POST" id="workerFormEl" action="{{ route('panel.dalal.settings.workers.store') }}" class="drawer-body" autocomplete="off" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">
                <div class="form-grid cols-2">
                    <label class="field"><span>الاسم *</span><input class="input" name="name" required></label>
                    <label class="field"><span>النوع *</span>
                        <select class="select" name="dalal_worker_type_id" required>
                            <option value="">—</option>
                            @foreach ($workerTypes as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="field"><span>رقم الجوال</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX"></label>
                    <label class="field"><span>الجنسية</span><input class="input" name="nationality"></label>
                    <label class="field"><span>نوع الهوية</span>
                        <select class="select" name="id_type_id">
                            <option value="">—</option>
                            @foreach ($idTypes as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="field"><span>رقم الهوية</span><input class="input" name="id_number" dir="ltr"></label>
                    <label class="field"><span>الحالة</span><select class="select" name="status">@foreach (\App\Models\DalalWorker::STATUSES as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></label>
                    <label class="field"><span>ملاحظات</span><input class="input" name="notes"></label>
                    <label class="field"><span>الصورة الشخصية (JPG/PNG/WebP حتى 2MB)</span><input class="input" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"></label>
                </div>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;cursor:pointer" id="removeWorkerAvatarRow">
                    <input type="checkbox" name="remove_avatar" value="1">
                    حذف الصورة الحالية
                </label>
                <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                    <button type="button" class="btn btn-outline" onclick="toggleDrawer('workerDrawer', false)">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('panel.dalal.settings.update') }}" class="card" style="display:flex;flex-direction:column;gap:1rem">
            @csrf @method('PUT')
            @include('partials.section-head', ['icon' => 'user', 'title' => 'الملف التجاري'])
            <div class="form-grid cols-3">
                <label class="field"><span>رقم الهوية</span><input class="input num" name="id_number" value="{{ old('id_number', $profile->id_number) }}" dir="ltr"></label>
                <label class="field"><span>المنطقة</span>
                    <select class="select" name="region_id">
                        <option value="">—</option>
                        @foreach ($regions as $o)<option value="{{ $o->id }}" @selected((string) old('region_id', $profile->region_id) === (string) $o->id)>{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المحافظة</span>
                    <select class="select" name="governorate_id">
                        <option value="">—</option>
                        @foreach ($governorates as $o)<option value="{{ $o->id }}" @selected((string) old('governorate_id', $profile->governorate_id) === (string) $o->id)>{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الميناء</span>
                    <select class="select" name="port_id">
                        <option value="">—</option>
                        @foreach ($ports as $o)<option value="{{ $o->id }}" @selected((string) old('port_id', $profile->port_id) === (string) $o->id)>{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الدكة المنسوب إليها</span><input class="input" name="dakka_name" value="{{ old('dakka_name', $profile->dakka_name) }}"></label>
                <label class="field"><span>رقم الدكة</span><input class="input num" name="dakka_number" value="{{ old('dakka_number', $profile->dakka_number) }}" dir="ltr"></label>
                <label class="field"><span>رقم السجل التجاري</span><input class="input num" name="cr_number" value="{{ old('cr_number', $profile->cr_number) }}" dir="ltr"></label>
                <label class="field"><span>الرقم الضريبي</span><input class="input num" name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" dir="ltr"></label>
            </div>
            <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary">@include('partials.icon', ['name' => 'save']) حفظ الملف</button></div>
        </form>
    @endif
@endsection

@if ($tab === 'workers')
    @push('scripts')
    @include('panel.partials.drawer-form')
    <script>
        const workerForm = { drawer: 'workerDrawer', form: 'workerFormEl', title: 'workerFormTitle', storeUrl: @json(route('panel.dalal.settings.workers.store')), createTitle: 'عامل جديد', editTitle: 'تعديل العامل', after: (record) => { document.getElementById('removeWorkerAvatarRow').hidden = !record; } };
        @if ($errors->any() && old('name') !== null)
            openDrawerForm(workerForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
        @endif
    </script>
    @endpush
@endif
