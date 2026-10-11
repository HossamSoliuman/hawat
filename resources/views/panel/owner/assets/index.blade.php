@extends('layouts.app')

@section('title', 'الأصول والإهلاك')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'archive'])</div>
            <div>
                <h1>الأصول والإهلاك</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.assets.depreciation') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calendar-days']) جدول الإهلاك</a>
            <a href="{{ route('panel.owner.assets.print', request()->query()) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة السجل</a>
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) أصل جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'تكلفة الأصول', 'value' => number_format($register['totals']['cost'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الإهلاك المتراكم', 'value' => number_format($register['totals']['accumulated'], 2), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'القيمة الدفترية', 'value' => number_format($register['totals']['book_value'], 2), 'unit' => 'ر.س', 'icon' => 'scale', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'إهلاك هذا الشهر', 'value' => number_format($thisMonth, 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>النوع</span>
            <select class="select" name="type">
                <option value="">الكل</option>
                @foreach ($types as $t)<option value="{{ $t->id }}" @selected((string) request('type') === (string) $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.assets') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الأصل</th><th>النوع</th><th>الشراء</th><th>التكلفة</th><th>الخردة</th><th>القسط الشهري</th><th>الأشهر</th><th>المتراكم</th><th>القيمة الدفترية</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($register['rows'] as $r)
                    @php $a = $r['asset']; @endphp
                    <tr>
                        <td style="font-weight:600">{{ $a->name }}@if ($a->boat)<div style="font-size:.72rem;font-weight:400;color:hsl(var(--muted-foreground))">{{ $a->boat->name }}</div>@endif</td>
                        <td>{{ $a->type?->name }}</td>
                        <td class="num">{{ $a->purchase_date->format('Y-m-d') }}<div style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $a->useful_life_years }} سنة</div></td>
                        <td class="num">{{ number_format($a->purchase_cost, 2) }}</td>
                        <td class="num">{{ number_format($a->salvage_value, 2) }}</td>
                        <td class="num">{{ number_format($r['monthly'], 2) }}</td>
                        <td class="num">{{ $r['months_charged'] }} / {{ $r['total_months'] }}</td>
                        <td class="num">{{ number_format($r['accumulated'], 2) }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($r['book_value'], 2) }}</td>
                        <td>
                            <span class="badge {{ $a->status === 'نشط' ? 'badge-ok' : ($a->status === 'مباع' ? 'badge-info' : 'badge-danger') }}">{{ $a->status }}</span>
                            @if ($a->disposed_at)<div class="num" style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $a->disposed_at->format('Y-m-d') }}</div>@endif
                            @if ($r['disposal_gain'] !== null)<div class="num" style="font-size:.7rem;color:var(--{{ $r['disposal_gain'] >= 0 ? 'st-good' : 'st-critical' }})">{{ $r['disposal_gain'] >= 0 ? 'ربح' : 'خسارة' }} {{ number_format(abs($r['disposal_gain']), 2) }}</div>@endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($a->only(['id', 'asset_type_id', 'boat_id', 'name', 'description', 'purchase_date', 'purchase_cost', 'salvage_value', 'useful_life_years', 'status', 'disposed_at', 'disposal_value', 'notes']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.assets.destroy', $a->id) }}" onsubmit="return confirm('حذف الأصل {{ $a->name }}؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا أصول مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">أصل جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.assets.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field wide"><span>اسم الأصل *</span><input class="input" name="name" required maxlength="255" placeholder="مثال: محرك ياماها 200 حصان"></label>
                <label class="field"><span>النوع *</span>
                    <select class="select" name="asset_type_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">—</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>تاريخ الشراء *</span><input class="input" name="purchase_date" type="date" dir="ltr" required></label>
                <label class="field"><span>تكلفة الشراء *</span><input class="input" name="purchase_cost" type="number" step="0.01" min="0.01" dir="ltr" required></label>
                <label class="field"><span>قيمة الخردة</span><input class="input" name="salvage_value" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>العمر الإنتاجي (سنوات) *</span><input class="input" name="useful_life_years" type="number" step="1" min="1" max="100" dir="ltr" required></label>
                <div class="field"><span>القسط الشهري</span><div class="input num" id="monthlyPreview" style="font-weight:700">—</div></div>
                <label class="field"><span>الحالة</span>
                    <select class="select" name="status">
                        @foreach ($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </label>
                <label class="field" data-disposal><span>تاريخ البيع/التلف *</span><input class="input" name="disposed_at" type="date" dir="ltr"></label>
                <label class="field" data-disposal><span>قيمة البيع</span><input class="input" name="disposal_value" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field wide"><span>الوصف</span><input class="input" name="description"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
                <p class="field wide" style="font-size:.74rem;color:hsl(var(--muted-foreground))">الأصل لا يُسجَّل مصروفًا — تكلفته تُحمَّل على الأشهر بالإهلاك. الأصل المباع أو التالف يُهلَك حتى شهر التخلّص ثم يتوقف.</p>
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
    const assetForm = document.getElementById('recordFormEl');

    function refreshAssetForm() {
        const cost = parseFloat(assetForm.purchase_cost.value) || 0;
        const salvage = parseFloat(assetForm.salvage_value.value) || 0;
        const years = parseInt(assetForm.useful_life_years.value, 10) || 0;
        document.getElementById('monthlyPreview').textContent = years > 0 && cost > salvage ? ((cost - salvage) / years / 12).toFixed(2) + ' ر.س' : '—';

        const disposed = assetForm.status.value !== 'نشط';
        assetForm.querySelectorAll('[data-disposal]').forEach((el) => { el.style.display = disposed ? '' : 'none'; });
        assetForm.disposed_at.required = disposed;
    }

    assetForm.addEventListener('input', refreshAssetForm);
    assetForm.addEventListener('change', refreshAssetForm);

    const recordForm = {
        drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle',
        storeUrl: @json(route('panel.owner.assets.store')), createTitle: 'أصل جديد', editTitle: 'تعديل الأصل',
        after(record, form) {
            if (!record) form.status.selectedIndex = 0;
            refreshAssetForm();
        },
    };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
