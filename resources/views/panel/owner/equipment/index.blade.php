@extends('layouts.app')

@section('title', 'معدات الصيد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'anchor'])</div>
            <div>
                <h1>معدات الصيد</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) إضافة معدات</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الأصناف', 'value' => number_format($totals['items']), 'icon' => 'layers', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الوحدات', 'value' => number_format($totals['units']), 'icon' => 'hash', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'قيمة الشراء', 'value' => number_format($totals['value'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'تحتاج صيانة أو تالفة', 'value' => number_format($totals['damaged']), 'icon' => 'alert-triangle', 'tone' => $totals['damaged'] > 0 ? 'warning' : 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم المعدات..."></label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="condition">
                <option value="">الكل</option>
                @foreach ($conditions as $c)<option value="{{ $c }}" @selected(request('condition') === $c)>{{ $c }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.equipment') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>المعدات</th><th>النوع</th><th>القارب</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>المواسم</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->name }}
                            <div style="font-size:.72rem;font-weight:400;color:hsl(var(--muted-foreground))">{{ $row->purchase_date?->format('Y-m-d') ?? '—' }}@if ($row->vendor) · {{ $row->vendor->name }}@endif</div>
                        </td>
                        <td>{{ $row->gearType?->name ?? '—' }}</td>
                        <td>{{ $row->boat?->name ?? '—' }}</td>
                        <td class="num">{{ number_format($row->quantity) }}</td>
                        <td class="num">{{ number_format($row->unit_cost, 2) }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($row->total_cost, 2) }}
                            @if ($row->expense)<div><a href="{{ route('panel.owner.expenses', ['search' => $row->expense->expense_number]) }}" style="font-size:.72rem;font-weight:400">{{ $row->expense->expense_number }}</a></div>@endif
                        </td>
                        <td>
                            @forelse ($row->seasons as $s)
                                <span class="badge {{ $s->isOpenNow() ? 'badge-ok' : 'badge-info' }}" title="{{ $s->species }}">{{ $s->name }}</span>
                            @empty
                                <span style="color:hsl(var(--muted-foreground))">—</span>
                            @endforelse
                        </td>
                        <td><span class="badge {{ $row->condition === 'جيدة' ? 'badge-ok' : ($row->condition === 'تالفة' ? 'badge-danger' : 'badge-warn') }}">{{ $row->condition }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'name', 'boat_id', 'gear_type_id', 'vendor_id', 'quantity', 'unit_cost', 'purchase_date', 'condition', 'notes']) + ['season_ids' => $row->seasons->pluck('id')], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.equipment.destroy', $row->id) }}" onsubmit="return confirm('حذف {{ $row->name }}؟ مصروفها غير المدفوع يُحذف معها.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا معدات مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">إضافة معدات</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.equipment.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field wide"><span>الاسم *</span><input class="input" name="name" required maxlength="255" placeholder="مثال: شبكة خيشومية 40 م"></label>
                <label class="field"><span>نوع الأداة</span>
                    <select class="select" name="gear_type_id">
                        <option value="">—</option>
                        @foreach ($gearTypes as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">— في المستودع —</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الكمية *</span><input class="input" name="quantity" type="number" min="1" step="1" dir="ltr" required></label>
                <label class="field"><span>سعر الوحدة (ر.س)</span><input class="input" name="unit_cost" type="number" min="0" step="0.01" dir="ltr"></label>
                <label class="field"><span>تاريخ الشراء</span><input class="input" name="purchase_date" type="date" dir="ltr"></label>
                <label class="field"><span>المورد</span>
                    <select class="select" name="vendor_id">
                        <option value="">—</option>
                        @foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الحالة</span>
                    <select class="select" name="condition">
                        @foreach ($conditions as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>مواسم الصيد التي تُستخدم فيها</span>
                    <select class="select" name="season_ids[]" multiple size="5">
                        @foreach ($seasons as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $s->species }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
                <p class="field wide" style="font-size:.74rem;color:hsl(var(--muted-foreground))">الكمية × سعر الوحدة تُسجَّل مصروفًا في فئة "معدات صيد" غير مدفوع — يُسدَّد من صفحة المصروفات.</p>
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
    const recordForm = {
        drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle',
        storeUrl: @json(route('panel.owner.equipment.store')), createTitle: 'إضافة معدات', editTitle: 'تعديل المعدات',
        after(record, form) {
            const chosen = (record && record.season_ids ? record.season_ids : []).map(String);
            for (const opt of form.querySelector('[name="season_ids[]"]').options) opt.selected = chosen.includes(opt.value);
            if (!record) {
                form.quantity.value = 1;
                form.condition.selectedIndex = 0;
            }
        },
    };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
