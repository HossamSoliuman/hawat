@extends('panel.owner.settings.layout')

@section('settings-actions')
    <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) كابتن جديد</button>
@endsection

@section('settings')
    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال..."></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.captains') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>القارب</th><th>الميناء</th><th>آخر دخول</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($captains as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->name }}@if ($row->email)<div dir="ltr" style="font-size:.7rem;color:hsl(var(--muted-foreground));text-align:right">{{ $row->email }}</div>@endif</td>
                        <td dir="ltr" class="num" style="text-align:right">{{ $row->phone }}</td>
                        <td class="num">{{ $row->fisher?->national_id ?? '—' }}</td>
                        <td>{{ $row->fisher?->boat?->name ?? '—' }}</td>
                        <td>{{ $row->fisher?->port?->name ?? '—' }}</td>
                        <td style="font-size:.74rem;color:hsl(var(--muted-foreground))">{{ $row->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}</td>
                        <td><span class="badge {{ $row->active ? 'badge-ok' : 'badge-danger' }}">{{ $row->active ? 'مفعّل' : 'معطّل' }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode(['id' => $row->id, 'name' => $row->name, 'phone' => $row->phone, 'email' => $row->email, 'active' => $row->active, 'national_id' => $row->fisher?->national_id, 'id_type_id' => $row->fisher?->id_type_id, 'nationality' => $row->fisher?->nationality, 'boat_id' => $row->fisher?->boat_id, 'port_id' => $row->fisher?->port_id, 'license_number' => $row->fisher?->license_number, 'license_expiry' => $row->fisher?->license_expiry, 'experience_years' => $row->fisher?->experience_years], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.captains.toggle', $row) }}">
                                    @csrf
                                    <button type="submit" class="icon-action" title="{{ $row->active ? 'تعطيل' : 'تفعيل' }}">@include('partials.icon', ['name' => $row->active ? 'ban' : 'check-circle'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا كباتن بعد — أضف كابتنًا ليدخل التطبيق بجواله</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $captains])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">كابتن جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.captains.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم *</span><input class="input" name="name" required></label>
                <label class="field"><span>رقم الجوال *</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX" required></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" name="email" type="email" dir="ltr"></label>
                <label class="field"><span id="passwordLabel">كلمة المرور *</span><input class="input" name="password" type="password" dir="ltr" minlength="8" autocomplete="new-password"></label>
                <label class="field"><span>رقم الهوية *</span><input class="input" name="national_id" dir="ltr" required></label>
                <label class="field"><span>نوع الهوية</span>
                    <select class="select" name="id_type_id">
                        <option value="">—</option>
                        @foreach ($idTypes as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الجنسية</span><input class="input" name="nationality"></label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">— بلا قارب —</option>
                        @foreach ($boats as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الميناء (إن لم يُحدَّد قارب)</span>
                    <select class="select" name="port_id">
                        <option value="">— اختر —</option>
                        @foreach ($ports as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الرخصة</span><input class="input" name="license_number" dir="ltr"></label>
                <label class="field"><span>انتهاء الرخصة</span><input class="input" name="license_expiry" type="date" dir="ltr"></label>
                <label class="field"><span>سنوات الخبرة</span><input class="input" name="experience_years" type="number" min="0" dir="ltr"></label>
            </div>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;cursor:pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" checked>
                الحساب مفعّل
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('recordDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const recordForm = { drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle', storeUrl: @json(route('panel.owner.captains.store')), createTitle: 'كابتن جديد', editTitle: 'تعديل الكابتن', after: (record, form) => { form.password.required = !record; document.getElementById('passwordLabel').textContent = record ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور *'; } };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
