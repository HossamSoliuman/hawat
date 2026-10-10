@extends('layouts.app')

@section('title', 'الوثائق')

@section('content')
    @php
        $badge = fn (string $status) => match ($status) {
            \App\Models\FleetDocument::EXPIRED => 'badge-danger',
            \App\Models\FleetDocument::EXPIRING => 'badge-warn',
            \App\Models\FleetDocument::VALID => 'badge-ok',
            default => 'badge-info',
        };
        $stateBadge = fn (string $state) => match ($state) {
            'ممتثل' => 'badge-ok',
            'تنبيه' => 'badge-warn',
            'غير ممتثل' => 'badge-danger',
            default => 'badge-info',
        };
    @endphp

    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-check'])</div>
            <div>
                <h1>الوثائق</h1>
                <p>وثائق القوارب والطاقم بتواريخ انتهائها — المنتهية وما ينتهي خلال {{ \App\Models\FleetDocument::EXPIRING_DAYS }} يومًا تظهر في تنبيهات الرئيسية</p>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) إضافة وثيقة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الوثائق', 'value' => number_format($counts['total']), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'سارية', 'value' => number_format($counts['سارية']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'تنتهي قريبًا', 'value' => number_format($counts['تنتهي قريبًا']), 'icon' => 'clock', 'tone' => $counts['تنتهي قريبًا'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'منتهية', 'value' => number_format($counts['منتهية']), 'icon' => 'alert-triangle', 'tone' => $counts['منتهية'] > 0 ? 'danger' : 'success'])
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'users', 'title' => 'امتثال الطاقم', 'note' => 'منتهية = غير ممتثل، تنتهي قريبًا = تنبيه، بلا وثائق = ناقص'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الفرد</th><th>الدور</th><th>الوثائق</th><th>منتهية</th><th>تنتهي قريبًا</th><th>أقرب انتهاء</th><th>الموقف</th></tr></thead>
                <tbody>
                    @forelse ($crewCompliance as $c)
                        <tr>
                            <td style="font-weight:600">{{ $c['holder']->name }}@if ($c['holder']->nationality)<div style="font-size:.72rem;font-weight:400;color:hsl(var(--muted-foreground))">{{ $c['holder']->nationality }}</div>@endif</td>
                            <td>{{ $c['holder']->role ?? '—' }}</td>
                            <td class="num">{{ $c['count'] }}</td>
                            <td class="num">{{ $c['expired'] }}</td>
                            <td class="num">{{ $c['expiring'] }}</td>
                            <td>{{ $c['nearest'] ? $c['nearest']->type?->name.' — ' : '' }}<span class="num">{{ $c['nearest']?->expiry_date?->format('Y-m-d') ?? '—' }}</span></td>
                            <td><span class="badge {{ $stateBadge($c['state']) }}">{{ $c['state'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا طاقم مسجّل</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'ship', 'title' => 'وثائق القوارب'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>القارب</th><th>الوثائق</th><th>منتهية</th><th>تنتهي قريبًا</th><th>أقرب انتهاء</th><th>الموقف</th></tr></thead>
                <tbody>
                    @forelse ($boatCompliance as $c)
                        <tr>
                            <td style="font-weight:600">{{ $c['holder']->name }}</td>
                            <td class="num">{{ $c['count'] }}</td>
                            <td class="num">{{ $c['expired'] }}</td>
                            <td class="num">{{ $c['expiring'] }}</td>
                            <td>{{ $c['nearest'] ? $c['nearest']->type?->name.' — ' : '' }}<span class="num">{{ $c['nearest']?->expiry_date?->format('Y-m-d') ?? '—' }}</span></td>
                            <td><span class="badge {{ $stateBadge($c['state']) }}">{{ $c['state'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا قوارب</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>رقم الوثيقة</span><input class="input" name="search" value="{{ request('search') }}"></label>
        <label class="field"><span>الحائز</span>
            <select class="select" name="holder">
                <option value="">الكل</option>
                <option value="boat" @selected(request('holder') === 'boat')>القوارب</option>
                <option value="crew" @selected(request('holder') === 'crew')>الطاقم</option>
            </select>
        </label>
        <label class="field"><span>النوع</span>
            <select class="select" name="type">
                <option value="">الكل</option>
                @foreach ($types as $t)<option value="{{ $t->id }}" @selected((string) request('type') === (string) $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ([\App\Models\FleetDocument::EXPIRED, \App\Models\FleetDocument::EXPIRING, \App\Models\FleetDocument::VALID, \App\Models\FleetDocument::NO_EXPIRY] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.documents') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>الوثيقة</th><th>الحائز</th><th>الرقم</th><th>الإصدار</th><th>الانتهاء</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->type?->name }}</td>
                        <td>{{ $row->documentable?->name ?? '—' }}<div style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $row->holder_kind === 'crew' ? 'طاقم' : 'قارب' }}</div></td>
                        <td class="num">{{ $row->number ?? '—' }}</td>
                        <td class="num">{{ $row->issue_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="num">{{ $row->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                        <td><span class="badge {{ $badge($row->status) }}">{{ $row->status }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                @if ($row->attachment_url)<a href="{{ $row->attachment_url }}" target="_blank" class="icon-action" title="المرفق">@include('partials.icon', ['name' => 'file-check'])</a>@endif
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'document_type_id', 'number', 'issue_date', 'expiry_date', 'notes']) + ['holder_type' => $row->holder_kind, 'holder_id' => $row->documentable_id, 'has_attachment' => (bool) $row->attachment_path], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.documents.destroy', $row->id) }}" onsubmit="return confirm('حذف الوثيقة؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا وثائق</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">إضافة وثيقة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.documents.store') }}" class="drawer-body" autocomplete="off" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>الوثيقة لـ *</span>
                    <select class="select" name="holder_type" required>
                        <option value="boat">قارب</option>
                        <option value="crew">فرد طاقم</option>
                    </select>
                </label>
                <label class="field"><span>الحائز *</span><select class="select" name="holder_id" required></select></label>
                <label class="field"><span>نوع الوثيقة *</span>
                    <select class="select" name="document_type_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الوثيقة</span><input class="input" name="number" maxlength="255" dir="ltr"></label>
                <label class="field"><span>تاريخ الإصدار</span><input class="input" name="issue_date" type="date" dir="ltr"></label>
                <label class="field"><span>تاريخ الانتهاء</span><input class="input" name="expiry_date" type="date" dir="ltr"></label>
                <label class="field wide"><span>المرفق</span><input class="input" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
                <label class="field wide" id="removeAttachmentField" style="display:none;flex-direction:row;align-items:center;gap:.5rem"><input type="checkbox" name="remove_attachment" value="1"> <span>حذف المرفق الحالي</span></label>
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
    const holders = {
        boat: @json($boats->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values()),
        crew: @json($crew->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values()),
    };
    const docForm = document.getElementById('recordFormEl');

    function fillHolders(selected) {
        const list = holders[docForm.holder_type.value] || [];
        docForm.holder_id.innerHTML = '<option value="">— اختر —</option>' + list.map((h) => `<option value="${h.id}">${h.name.replace(/</g, '&lt;')}</option>`).join('');
        docForm.holder_id.value = selected != null ? String(selected) : '';
    }

    docForm.holder_type.addEventListener('change', () => fillHolders(null));

    const recordForm = {
        drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle',
        storeUrl: @json(route('panel.owner.documents.store')), createTitle: 'إضافة وثيقة', editTitle: 'تعديل الوثيقة',
        after(record, form) {
            if (!record) form.holder_type.value = 'boat';
            fillHolders(record ? record.holder_id : null);
            document.getElementById('removeAttachmentField').style.display = record && record.has_attachment ? 'flex' : 'none';
        },
    };
    @if ($errors->any() && old('holder_type') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
