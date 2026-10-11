@extends('layouts.app')

@section('title', 'سلف الطاقم')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'arrow-left-right'])</div>
            <div>
                <h1>سلف الطاقم</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(advanceForm)">@include('partials.icon', ['name' => 'plus']) سلفة جديدة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'السلف (ضمن التصفية)', 'value' => number_format($count), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مجموعها', 'value' => number_format($total, 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'خُصم في مسيرات مسدَّدة', 'value' => number_format($settled, 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'متبقٍّ على الطاقم', 'value' => number_format($outstanding, 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $outstanding > 0 ? 'warning' : 'success'])
    </div>

    @if ($debtors->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'users', 'title' => 'سلف لم تُخصم بعد'])
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($debtors as $d)
                    <a href="{{ route('panel.owner.advances', ['fisher' => $d['fisher']->id]) }}" class="badge badge-warn" style="font-size:.78rem;padding:.35rem .6rem">
                        {{ $d['fisher']->name }} — <span class="num">{{ number_format($d['outstanding'], 2) }}</span> ر.س
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الفرد</span>
            <select class="select" name="fisher">
                <option value="">الكل</option>
                @foreach ($fishers as $f)<option value="{{ $f->id }}" @selected((string) request('fisher') === (string) $f->id)>{{ $f->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}" dir="ltr"></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.advances') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>التاريخ</th><th>الفرد</th><th>القارب</th><th>المبلغ</th><th>طريقة الدفع</th><th>ملاحظات</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="num">{{ $row->date->format('Y-m-d') }}</td>
                        <td>
                            {{ $row->member_name }}
                            @if ($row->fisher_id)<a href="{{ route('panel.owner.crew-pay.statement', $row->fisher_id) }}" target="_blank" style="font-size:.72rem;margin-inline-start:.35rem">كشف الحساب</a>@endif
                        </td>
                        <td>{{ $row->boat?->name ?? '—' }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($row->amount, 2) }}</td>
                        <td>{{ $row->paymentMethod?->name ?? '—' }}</td>
                        <td style="font-size:.78rem">{{ $row->notes ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('panel.owner.advances.destroy', $row->id) }}" onsubmit="return confirm('حذف سلفة {{ $row->member_name }}؟')" style="display:flex;justify-content:flex-end">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا سلف</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    <div class="drawer-overlay" id="advanceDrawer-overlay" onclick="toggleDrawer('advanceDrawer', false)"></div>
    <div class="drawer" id="advanceDrawer">
        <div class="drawer-head">
            <h3 id="advanceFormTitle">سلفة جديدة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('advanceDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="advanceFormEl" action="{{ route('panel.owner.advances.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field wide"><span>الفرد *</span>
                    <select class="select" name="fisher_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($fishers as $f)<option value="{{ $f->id }}">{{ $f->name }}{{ $f->user_id ? ' (كابتن)' : '' }}{{ $f->boat ? ' — '.$f->boat->name : '' }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>التاريخ *</span><input class="input" name="date" type="date" dir="ltr" required max="{{ now()->toDateString() }}"></label>
                <label class="field"><span>المبلغ (ر.س) *</span><input class="input" name="amount" type="number" step="0.01" min="0.01" dir="ltr" required></label>
                <label class="field wide"><span>طريقة الدفع</span>
                    <select class="select" name="payment_method_id">
                        <option value="">—</option>
                        @foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('advanceDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const advanceForm = {
        drawer: 'advanceDrawer', form: 'advanceFormEl', title: 'advanceFormTitle',
        storeUrl: @json(route('panel.owner.advances.store')), createTitle: 'سلفة جديدة', editTitle: 'سلفة',
        after(record, form) {
            if (!record || !record.date) form.date.value = new Date().toISOString().slice(0, 10);
            @if (request('fisher'))
                if (!record) form.fisher_id.value = @json(request('fisher'));
            @endif
        },
    };

    @if ($errors->any() && old('amount') !== null)
        openDrawerForm(advanceForm, {!! json_encode(old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
        document.getElementById('advanceFormEl').action = @json(route('panel.owner.advances.store'));
        document.querySelector('#advanceFormEl [name=_method]').value = 'POST';
    @endif
</script>
@endpush
