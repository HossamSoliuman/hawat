@extends('panel.owner.settings.layout')

@section('settings-actions')
    <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) عضو طاقم</button>
@endsection

@section('settings')
    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم..."></label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat" onchange="this.form.submit()">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.crew') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>الدور</th><th>الجنسية</th><th>الهوية</th><th>الجوال</th><th>القارب</th><th>الميناء</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($crew as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->name }}</td>
                        <td>{{ $row->fisherRole?->name ?? $row->role }}</td>
                        <td>{{ $row->nationality ?? '—' }}</td>
                        <td class="num">{{ $row->national_id }}<div style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $row->idType?->name }}</div></td>
                        <td dir="ltr" class="num" style="text-align:right">{{ $row->phone ?? '—' }}</td>
                        <td>{{ $row->boat?->name ?? '—' }}</td>
                        <td>{{ $row->port?->name ?? '—' }}</td>
                        <td><span class="badge {{ $row->status === 'نشط' ? 'badge-ok' : 'badge-danger' }}">{{ $row->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'name', 'phone', 'email', 'national_id', 'id_type_id', 'nationality', 'fisher_role_id', 'boat_id', 'port_id', 'license_number', 'license_expiry', 'experience_years', 'status']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.crew.destroy', $row) }}" onsubmit="return confirm('حذف {{ $row->name }}؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا أعضاء طاقم بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $crew])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">عضو طاقم</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.crew.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم *</span><input class="input" name="name" required></label>
                <label class="field"><span>الدور على القارب</span>
                    <select class="select" name="fisher_role_id">
                        <option value="">—</option>
                        @foreach ($roles as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الجوال</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX"></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" name="email" type="email" dir="ltr"></label>
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
                <label class="field"><span>الحالة</span><select class="select" name="status"><option value="نشط">نشط</option><option value="غير نشط">غير نشط</option></select></label>
                <label class="field"><span>رقم الرخصة</span><input class="input" name="license_number" dir="ltr"></label>
                <label class="field"><span>انتهاء الرخصة</span><input class="input" name="license_expiry" type="date" dir="ltr"></label>
                <label class="field"><span>سنوات الخبرة</span><input class="input" name="experience_years" type="number" min="0" dir="ltr"></label>
            </div>
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
    const recordForm = { drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle', storeUrl: @json(route('panel.owner.crew.store')), createTitle: 'عضو طاقم', editTitle: 'تعديل عضو الطاقم' };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
