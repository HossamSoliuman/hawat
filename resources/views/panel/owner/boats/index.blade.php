@extends('panel.owner.settings.layout')

@php
    // الحقول التي يعيدها الزر إلى النموذج عند التعديل — بأسماء الأعمدة نفسها.
    $fields = [
        'id', 'name', 'name_en', 'boat_number', 'port_id', 'boat_category_id', 'boat_type_id', 'captain_id', 'status',
        'length_m', 'width_m', 'color', 'hull_number', 'body_type', 'engine_type', 'engine_power', 'engine_status',
        'call_sign', 'serial_number', 'capacity', 'crew_count', 'crew_capacity', 'license_type', 'license_number',
        'license_status', 'license_process', 'license_area', 'license_date', 'license_expiry', 'next_inspection_date', 'version',
    ];
@endphp

@section('settings-actions')
    <button type="button" class="btn btn-primary" onclick="openDrawerForm(boatForm)">@include('partials.icon', ['name' => 'plus']) قارب جديد</button>
@endsection

@section('settings')
    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل القوارب', 'value' => number_format($counts['total']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نشطة', 'value' => number_format($counts['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($counts['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'في الصيانة', 'value' => number_format($counts['maintenance']), 'icon' => 'hammer', 'tone' => 'warning'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو رقم القارب..."></label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.boats') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>القارب</th><th>الرقم</th><th>الميناء</th><th>التصنيف / النوع</th><th>الكابتن</th><th>الطاقم</th><th>الرحلات</th><th>الفحص القادم</th><th>الإعداد</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($boats as $boat)
                    <tr>
                        <td style="font-weight:600">{{ $boat->name }}@if ($boat->name_en)<div style="font-size:.7rem;color:hsl(var(--muted-foreground))" dir="ltr">{{ $boat->name_en }}</div>@endif</td>
                        <td class="num">{{ $boat->boat_number }}</td>
                        <td>{{ $boat->port?->name }}</td>
                        <td style="font-size:.74rem">{{ $boat->category?->name ?? '—' }} / {{ $boat->type?->name ?? $boat->boat_type ?? '—' }}</td>
                        <td>{{ $boat->captainFisher?->name ?? $boat->captain ?? '—' }}</td>
                        <td class="num" style="text-align:center">{{ $boat->crew_count }}</td>
                        <td class="num" style="text-align:center">{{ $boat->trips_count }}</td>
                        <td class="num" style="font-size:.74rem">{{ $boat->next_inspection_date?->format('Y-m-d') ?? '—' }}</td>
                        @php $setup = $boat->setup(); @endphp
                        <td style="font-size:.72rem">
                            @if ($setup['ready'])
                                <span class="badge badge-ok">جاهز</span>
                            @else
                                <span class="badge badge-warn">ناقص</span>
                            @endif
                            @foreach ([...$setup['missing'], ...$setup['warnings']] as $item)
                                <div style="color:hsl(var(--muted-foreground));margin-top:.15rem">{{ $item['label'] }}</div>
                            @endforeach
                        </td>
                        <td><span class="badge {{ $boat->status === 'نشط' ? 'badge-ok' : ($boat->status === 'في البحر' ? 'badge-info' : ($boat->status === 'صيانة' ? 'badge-warn' : 'badge-danger')) }}">{{ $boat->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(boatForm, {!! json_encode($boat->only($fields), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <a href="{{ route('panel.owner.maintenance', ['boat' => $boat->id]) }}" class="icon-action" title="الصيانة">@include('partials.icon', ['name' => 'hammer'])</a>
                                <form method="POST" action="{{ route('panel.owner.boats.destroy', $boat) }}" onsubmit="return confirm('حذف القارب «{{ $boat->name }}»؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا قوارب بعد — أضف أول قارب</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $boats])

    <div class="drawer-overlay" id="boatDrawer-overlay" onclick="toggleDrawer('boatDrawer', false)"></div>
    <div class="drawer wide" id="boatDrawer">
        <div class="drawer-head">
            <h3 id="boatFormTitle">قارب جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('boatDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="boatFormEl" action="{{ route('panel.owner.boats.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="version" value="" data-fill>

            @include('partials.section-head', ['icon' => 'ship', 'title' => 'التعريف'])
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم بالعربية *</span><input class="input" name="name" required></label>
                <label class="field"><span>الاسم بالإنجليزية</span><input class="input" name="name_en" dir="ltr"></label>
                <label class="field"><span>رقم القارب *</span><input class="input" name="boat_number" dir="ltr" required></label>
                <label class="field"><span>الميناء *</span>
                    <select class="select" name="port_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($ports as $port)<option value="{{ $port->id }}">{{ $port->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>التصنيف</span>
                    <select class="select" name="boat_category_id">
                        <option value="">—</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>النوع</span>
                    <select class="select" name="boat_type_id">
                        <option value="">—</option>
                        @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الكابتن</span>
                    <select class="select" name="captain_id">
                        <option value="">— بلا كابتن —</option>
                        @foreach ($captains as $captain)<option value="{{ $captain->id }}">{{ $captain->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الحالة</span>
                    <select class="select" name="status">
                        @foreach ($statuses as $s)<option value="{{ $s }}" @if ($s === \App\Models\Boat::AT_SEA) hidden @endif>{{ $s }}</option>@endforeach
                    </select>
                </label>
            </div>

            @include('partials.section-head', ['icon' => 'scale', 'title' => 'المواصفات'])
            <div class="form-grid cols-3">
                <label class="field"><span>الطول (م)</span><input class="input" name="length_m" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>العرض (م)</span><input class="input" name="width_m" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>اللون</span><input class="input" name="color"></label>
                <label class="field"><span>رقم الهيكل</span><input class="input" name="hull_number" dir="ltr"></label>
                <label class="field"><span>نوع البدن</span><input class="input" name="body_type"></label>
                <label class="field"><span>الرقم التسلسلي</span><input class="input" name="serial_number" dir="ltr"></label>
                <label class="field"><span>نوع المحرك</span><input class="input" name="engine_type"></label>
                <label class="field"><span>قوة المحرك</span><input class="input" name="engine_power" dir="ltr"></label>
                <label class="field"><span>حالة المحرك</span><input class="input" name="engine_status"></label>
                <label class="field"><span>رمز النداء</span><input class="input" name="call_sign" dir="ltr"></label>
                <label class="field"><span>الحمولة (طن)</span><input class="input" name="capacity" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>عدد الطاقم (من سجلات الطاقم)</span><input class="input" name="crew_count" type="number" dir="ltr" disabled></label>
                <label class="field"><span>سعة الطاقم</span><input class="input" name="crew_capacity" type="number" min="0" dir="ltr"></label>
                <label class="field"><span>الفحص القادم</span><input class="input" name="next_inspection_date" type="date" dir="ltr"></label>
            </div>

            @include('partials.section-head', ['icon' => 'badge-check', 'title' => 'الرخصة'])
            <div class="form-grid cols-3">
                <label class="field"><span>نوع الرخصة</span><input class="input" name="license_type"></label>
                <label class="field"><span>رقم الرخصة</span><input class="input" name="license_number" dir="ltr"></label>
                <label class="field"><span>حالة الرخصة</span>
                    <select class="select" name="license_status">
                        @foreach (['سارية', 'منتهية', 'قيد التجديد'] as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>إجراء الترخيص</span><input class="input" name="license_process"></label>
                <label class="field"><span>منطقة الترخيص</span><input class="input" name="license_area"></label>
                <label class="field"><span>تاريخ الرخصة</span><input class="input" name="license_date" type="date" dir="ltr"></label>
                <label class="field"><span>انتهاء الرخصة</span><input class="input" name="license_expiry" type="date" dir="ltr"></label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('boatDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    // "في البحر" تضبطه الرحلات: لا يُختار، والقارب في رحلة لا تتغيّر حالته من هنا.
    const boatForm = { drawer: 'boatDrawer', form: 'boatFormEl', title: 'boatFormTitle', storeUrl: @json(route('panel.owner.boats.store')), createTitle: 'قارب جديد', editTitle: 'تعديل القارب', after: (record, form) => { form.status.disabled = Boolean(record) && record.status === @json(\App\Models\Boat::AT_SEA); } };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(boatForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
