@extends('layouts.app')

@section('title', 'فحوصات القوارب')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'clipboard-check'])</div>
            <div>
                <h1>فحوصات القوارب</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) تسجيل فحص</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'ship', 'title' => 'موقف الأسطول'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>القارب</th><th>آخر فحص</th><th>النتيجة</th><th>الفحص القادم</th><th>الموقف</th></tr></thead>
                <tbody>
                    @forelse ($fleet as $boat)
                        @php
                            $last = $boat->inspections->first();
                            $due = $boat->next_inspection_date;
                            $days = $due ? (int) now()->startOfDay()->diffInDays($due, false) : null;
                        @endphp
                        <tr>
                            <td style="font-weight:600">{{ $boat->name }}</td>
                            <td class="num">{{ $last?->inspection_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $last?->result ?? '—' }}</td>
                            <td class="num">{{ $due?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if ($due === null)
                                    <span class="badge badge-warn">لم يُفحص</span>
                                @elseif ($days < 0)
                                    <span class="badge badge-danger">متأخر {{ abs($days) }} يوم</span>
                                @elseif ($days <= 30)
                                    <span class="badge badge-warn">بعد {{ $days }} يوم</span>
                                @else
                                    <span class="badge badge-ok">ساري</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا قوارب</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>النتيجة</span>
            <select class="select" name="result">
                <option value="">الكل</option>
                @foreach ($results as $r)<option value="{{ $r }}" @selected(request('result') === $r)>{{ $r }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.inspections') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>القارب</th><th>تاريخ الفحص</th><th>الفاحص</th><th>النتيجة</th><th>الفحص القادم</th><th>ملاحظات</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->boat?->name }}</td>
                        <td class="num">{{ $row->inspection_date->format('Y-m-d') }}</td>
                        <td>{{ $row->inspector ?? '—' }}</td>
                        <td><span class="badge {{ $row->result === 'مطابق' ? 'badge-ok' : ($row->result === 'غير مطابق' ? 'badge-danger' : 'badge-warn') }}">{{ $row->result }}</span></td>
                        <td class="num">{{ $row->next_due_date?->format('Y-m-d') ?? '—' }}</td>
                        <td style="max-width:18rem">{{ $row->notes ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                @if ($row->attachment_url)<a href="{{ $row->attachment_url }}" target="_blank" class="icon-action" title="المرفق">@include('partials.icon', ['name' => 'file-check'])</a>@endif
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'boat_id', 'inspection_date', 'next_due_date', 'inspector', 'result', 'notes']) + ['has_attachment' => (bool) $row->attachment_path], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.inspections.destroy', $row->id) }}" onsubmit="return confirm('حذف الفحص؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا فحوصات مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">تسجيل فحص</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.inspections.store') }}" class="drawer-body" autocomplete="off" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>القارب *</span>
                    <select class="select" name="boat_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>النتيجة *</span>
                    <select class="select" name="result" required>
                        @foreach ($results as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>تاريخ الفحص *</span><input class="input" name="inspection_date" type="date" dir="ltr" required></label>
                <label class="field"><span>الفحص القادم</span><input class="input" name="next_due_date" type="date" dir="ltr" placeholder="تلقائي"></label>
                <label class="field wide"><span>الفاحص / الجهة</span><input class="input" name="inspector" maxlength="255"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
                <label class="field wide"><span>المرفق (تقرير الفحص)</span><input class="input" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
                <label class="field wide" id="removeAttachmentField" style="display:none;flex-direction:row;align-items:center;gap:.5rem"><input type="checkbox" name="remove_attachment" value="1"> <span>حذف المرفق الحالي</span></label>
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
        storeUrl: @json(route('panel.owner.inspections.store')), createTitle: 'تسجيل فحص', editTitle: 'تعديل الفحص',
        after(record, form) {
            if (!record) {
                form.inspection_date.value = new Date().toISOString().slice(0, 10);
                form.result.selectedIndex = 0;
            }
            document.getElementById('removeAttachmentField').style.display = record && record.has_attachment ? 'flex' : 'none';
        },
    };
    @if ($errors->any() && old('inspection_date') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
