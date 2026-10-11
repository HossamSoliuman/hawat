@extends('layouts.app')

@section('title', 'العملاء')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'handshake'])</div>
            <div>
                <h1>العملاء</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) عميل جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو البريد..."></label>
        <label class="field"><span>المنطقة</span>
            <select class="select" name="region_id" onchange="this.form.submit()">
                <option value="">كل المناطق</option>
                @foreach ($regions as $o)<option value="{{ $o->id }}" @selected((string) request('region_id') === (string) $o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>المحافظة</span>
            <select class="select" name="governorate_id" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($governorates as $o)<option value="{{ $o->id }}" @selected((string) request('governorate_id') === (string) $o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.dalal.customers') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>النوع</th><th>الجوال</th><th>البريد</th><th>المنطقة</th><th>الفواتير</th><th>إجمالي المشتريات</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->name }}</td>
                        <td>{{ $row->customerType?->name ?? '—' }}</td>
                        <td dir="ltr" class="num" style="text-align:right">{{ $row->phone ?? '—' }}</td>
                        <td dir="ltr" style="text-align:right;font-size:.74rem">{{ $row->email ?? '—' }}</td>
                        <td>{{ $row->region?->name ?? '—' }}{{ $row->governorate ? ' / '.$row->governorate->name : '' }}</td>
                        <td class="num" style="text-align:center">{{ $row->sales_count }}</td>
                        <td class="num">{{ number_format($row->sales_sum_total ?? 0, 2) }} ر.س</td>
                        <td><span class="badge {{ $row->status === 'نشط' ? 'badge-ok' : 'badge-danger' }}">{{ $row->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'name', 'phone', 'email', 'customer_type_id', 'region_id', 'governorate_id', 'notes', 'status']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.dalal.customers.destroy', $row) }}" onsubmit="return confirm('حذف {{ $row->name }}؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا عملاء بعد — أضف عميلًا ليظهر في شاشة البيع</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">عميل جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.dalal.customers.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم *</span><input class="input" name="name" required></label>
                <label class="field"><span>النوع</span>
                    <select class="select" name="customer_type_id">
                        <option value="">—</option>
                        @foreach ($types as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الجوال</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX"></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" name="email" type="email" dir="ltr"></label>
                <label class="field"><span>المنطقة</span>
                    <select class="select" name="region_id">
                        <option value="">—</option>
                        @foreach ($regions as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المحافظة</span>
                    <select class="select" name="governorate_id">
                        <option value="">—</option>
                        @foreach ($governorates as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الحالة</span><select class="select" name="status"><option value="نشط">نشط</option><option value="غير نشط">غير نشط</option></select></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
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
    const recordForm = { drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle', storeUrl: @json(route('panel.dalal.customers.store')), createTitle: 'عميل جديد', editTitle: 'تعديل العميل' };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
