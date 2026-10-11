@extends('layouts.app')

@section('title', 'صيانة القوارب')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'hammer'])</div>
            <div>
                <h1>صيانة القوارب</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) سجل صيانة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>القارب</span>
            <select class="select" name="boat" onchange="this.form.submit()">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()"><option value="">الكل</option><option value="معلقة" @selected(request('status') === 'معلقة')>معلقة</option><option value="مكتملة" @selected(request('status') === 'مكتملة')>مكتملة</option><option value="ملغاة" @selected(request('status') === 'ملغاة')>ملغاة</option></select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.maintenance') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>القارب</th><th>النوع</th><th>التاريخ</th><th>المسؤول الفني</th><th>التكلفة المتوقعة</th><th>التكلفة الفعلية</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->boat?->name }}</td>
                        <td>{{ $row->maintenanceType?->name ?? '—' }}</td>
                        <td class="num">{{ $row->date?->format('Y-m-d') }}</td>
                        <td>{{ $row->technician ?? '—' }}</td>
                        <td class="num">{{ $row->estimated_cost !== null ? number_format($row->estimated_cost, 2).' ر.س' : '—' }}</td>
                        <td class="num">
                            {{ $row->actual_cost !== null ? number_format($row->actual_cost, 2).' ر.س' : '—' }}
                            @if ($row->expense)<div><a href="{{ route('panel.owner.expenses', ['search' => $row->expense->expense_number]) }}" style="font-size:.72rem">{{ $row->expense->expense_number }}</a></div>@endif
                        </td>
                        <td><span class="badge {{ $row->status === 'مكتملة' ? 'badge-ok' : ($row->status === 'ملغاة' ? 'badge-danger' : 'badge-warn') }}">{{ $row->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'boat_id', 'maintenance_type_id', 'date', 'technician', 'estimated_cost', 'actual_cost', 'description', 'status']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.maintenance.destroy', $row) }}" onsubmit="return confirm('حذف سجل الصيانة؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا سجلات صيانة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">سجل صيانة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.maintenance.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>القارب *</span>
                    <select class="select" name="boat_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($boats as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>نوع الصيانة</span>
                    <select class="select" name="maintenance_type_id">
                        <option value="">—</option>
                        @foreach ($types as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>التاريخ *</span><input class="input" name="date" type="date" dir="ltr" required></label>
                <label class="field"><span>المسؤول الفني</span><input class="input" name="technician"></label>
                <label class="field"><span>التكلفة المتوقعة</span><input class="input" name="estimated_cost" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>التكلفة الفعلية</span><input class="input" name="actual_cost" type="number" step="0.01" min="0" dir="ltr" placeholder="عند الاكتمال"></label>
                <label class="field"><span>الحالة</span><select class="select" name="status"><option value="معلقة">معلقة</option><option value="مكتملة">مكتملة</option><option value="ملغاة">ملغاة</option></select></label>
                <label class="field wide"><span>الوصف</span><textarea class="input" name="description" rows="2"></textarea></label>
                <p class="field wide" style="font-size:.74rem;color:hsl(var(--muted-foreground))">عند حفظها "مكتملة" تُسجَّل تكلفتها (الفعلية، وإلا المتوقعة) مصروفًا في فئة "صيانة القوارب" غير مدفوع — يُسدَّد من صفحة المصروفات.</p>
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
    const recordForm = { drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle', storeUrl: @json(route('panel.owner.maintenance.store')), createTitle: 'سجل صيانة', editTitle: 'تعديل سجل الصيانة' };
    @if ($errors->any() && old('date') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
