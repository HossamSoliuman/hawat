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
                @include('partials.section-head', ['icon' => 'users', 'title' => 'عمالة الدكة'])
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>النوع</th><th>الجنسية</th><th>العدد</th><th>ملاحظات</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr>
                                    <td style="font-weight:600">{{ $worker->type?->name }}</td>
                                    <td>{{ $worker->nationality ?? '—' }}</td>
                                    <td class="num">{{ $worker->count }}</td>
                                    <td style="font-size:.76rem">{{ $worker->notes ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('panel.dalal.settings.workers.destroy', $worker) }}" onsubmit="return confirm('حذف السطر؟')" style="display:flex;justify-content:flex-end">
                                            @csrf @method('DELETE')
                                            <button class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لم تُسجَّل عمالة بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="POST" action="{{ route('panel.dalal.settings.workers.store') }}" class="card" style="display:flex;flex-direction:column;gap:1rem">
                @csrf
                @include('partials.section-head', ['icon' => 'user-plus', 'title' => 'إضافة عمالة'])
                <label class="field"><span>النوع *</span>
                    <select class="select" name="dalal_worker_type_id" required>
                        <option value="">—</option>
                        @foreach ($workerTypes as $o)<option value="{{ $o->id }}" @selected((string) old('dalal_worker_type_id') === (string) $o->id)>{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الجنسية</span><input class="input" name="nationality" value="{{ old('nationality') }}"></label>
                <label class="field"><span>العدد *</span><input class="input num" type="number" min="1" name="count" value="{{ old('count', 1) }}" dir="ltr" required></label>
                <label class="field"><span>ملاحظات</span><input class="input" name="notes" value="{{ old('notes') }}"></label>
                <button class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إضافة</button>
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
