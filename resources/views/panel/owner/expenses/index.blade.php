@extends('layouts.app')

@section('title', 'المصروفات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'receipt'])</div>
            <div>
                <h1>المصروفات</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.expenses.report', request()->query()) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة الكشف</a>
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) مصروف جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'السندات', 'value' => number_format($totals->count), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصروفات', 'value' => number_format($totals->total, 2), 'unit' => 'ر.س', 'icon' => 'receipt', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المدفوع', 'value' => number_format($totals->paid, 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المتبقي', 'value' => number_format($totals->total - $totals->paid, 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $totals->total - $totals->paid > 0 ? 'warning' : 'success'])
    </div>

    @if ($byGroup->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'layers', 'title' => 'حسب المجموعة'])
            <div style="display:grid;gap:.6rem">
                @foreach ($byGroup as $g)
                    @php $share = $totals->total > 0 ? $g->total / $totals->total * 100 : 0; @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:.2rem">
                            <span style="font-weight:600">{{ $g->name }}</span>
                            <span class="num">{{ number_format($g->total, 2) }} ر.س · {{ number_format($share, 1) }}%</span>
                        </div>
                        <div style="height:6px;background:hsl(var(--muted))"><div style="height:100%;width:{{ round($share, 1) }}%;background:hsl(var(--primary))"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم السند أو الوصف..."></label>
        <label class="field"><span>المجموعة</span>
            <select class="select" name="group">
                <option value="">الكل</option>
                @foreach ($groups as $g)<option value="{{ $g->id }}" @selected((string) request('group') === (string) $g->id)>{{ $g->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الفئة</span>
            <select class="select" name="category">
                <option value="">الكل</option>
                @foreach ($groups as $g)
                    <optgroup label="{{ $g->name }}">
                        @foreach ($g->categories as $c)<option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>@endforeach
                    </optgroup>
                @endforeach
            </select>
        </label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة الدفع</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s->id }}" @selected((string) request('status') === (string) $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}" dir="ltr"></label>
        @if (request('trip'))<input type="hidden" name="trip" value="{{ request('trip') }}">@endif
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.expenses') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>السند</th><th>التاريخ</th><th>الفئة</th><th>القارب / الرحلة</th><th>المورد</th><th>الإجمالي</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php $locked = isset($closedMonths[$row->date?->format('Y-m')]); @endphp
                    <tr>
                        <td>
                            <div class="cell-line">
                                <span class="num" style="font-weight:700">{{ $row->expense_number }}</span>
                                @if ($locked)<span class="cell-icon" title="شهره مُغلق — السداد وحده متاح">@include('partials.icon', ['name' => 'lock'])</span>@endif
                                @if ($row->is_automatic)<span class="badge badge-muted">{{ $row->source_label ?? 'تلقائي' }}</span>@endif
                            </div>
                            {{-- الرمز اللاتيني داخل الوصف (PAY-2026-0001) يُعزل حتى لا يقلب القوسين حوله. --}}
                            @if ($row->description)<div class="cell-sub clip" title="{{ $row->description }}">{!! preg_replace('/[A-Z]+(?:-\d+)+/', '<bdi dir="ltr">$0</bdi>', e($row->description)) !!}</div>@endif
                        </td>
                        <td class="num">{{ $row->date?->format('Y-m-d') }}</td>
                        <td style="white-space:nowrap" title="{{ $row->category?->group?->name }}">{{ $row->category?->name }}</td>
                        <td style="white-space:nowrap">
                            {{ $row->boat?->name ?? '—' }}
                            @if ($row->trip)<a href="{{ route('panel.owner.trips.show', $row->trip) }}" class="cell-sub num">{{ $row->trip->trip_number }}</a>@endif
                        </td>
                        <td>{{ $row->vendor?->name ?? '—' }}</td>
                        <td class="num" style="font-weight:700" @if ($row->vat_amount > 0) title="شامل ضريبة {{ number_format($row->vat_amount, 2) }}" @endif>{{ number_format($row->total, 2) }}</td>
                        <td style="white-space:nowrap">
                            <span class="badge {{ $row->is_paid ? 'badge-ok' : ($row->paid_amount > 0 ? 'badge-info' : 'badge-warn') }}">{{ $row->paymentStatus?->name ?? '—' }}</span>
                            @if (! $row->is_paid && $row->paid_amount > 0)<div class="cell-sub">المتبقي <span class="num">{{ number_format($row->remaining, 2) }}</span></div>@endif
                        </td>
                        <td>
                            {{-- الثابت أوّلًا والمشروط آخرًا، فتبقى كل أيقونة في موضعها من صفٍّ إلى صف. --}}
                            <div style="display:flex;gap:.25rem">
                                <a href="{{ route('panel.owner.expenses.print', $row->id) }}" target="_blank" class="icon-action" title="طباعة السند">@include('partials.icon', ['name' => 'printer'])</a>
                                @unless ($locked)
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($row->only(['id', 'expense_category_id', 'date', 'description', 'subtotal', 'discount', 'discount_pct', 'vat_rate', 'boat_id', 'trip_id', 'vendor_id', 'payment_method_id', 'payment_status_id', 'paid_amount', 'notes']) + ['is_automatic' => $row->is_automatic, 'source_label' => $row->source_label, 'has_attachment' => (bool) $row->attachment_path], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                <form method="POST" action="{{ route('panel.owner.expenses.destroy', $row->id) }}" onsubmit="return confirm('حذف المصروف {{ $row->expense_number }}؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                                @endunless
                                @if (! $row->is_paid && ! $row->payment_follows_source)
                                    <button type="button" class="icon-action" title="سداد" onclick='openPayment({!! json_encode(['id' => $row->id, 'number' => $row->expense_number, 'remaining' => $row->remaining], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'coins'])</button>
                                @endif
                                @if ($row->attachment_url)
                                    <a href="{{ $row->attachment_url }}" target="_blank" class="icon-action" title="المرفق">@include('partials.icon', ['name' => 'file-check'])</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])

    {{-- درج السند: جديد أو تعديل --}}
    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">مصروف جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.expenses.store') }}" class="drawer-body" autocomplete="off" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="flash" id="automaticNote" style="display:none;margin-bottom:.75rem">مرحَّل تلقائيًا من <b id="automaticSource"></b> — المبلغ والقارب والتاريخ تُعدَّل من هناك.</div>
            <div class="form-grid cols-2">
                <label class="field"><span>الفئة *</span>
                    <select class="select" name="expense_category_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($groups as $g)
                            <optgroup label="{{ $g->name }}">
                                @foreach ($g->categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>التاريخ *</span><input class="input" name="date" type="date" dir="ltr" required data-lockable></label>
                <label class="field wide"><span>الوصف</span><input class="input" name="description" maxlength="255" placeholder="مثال: تعبئة وقود قبل الرحلة"></label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id" data-lockable>
                        <option value="">— عام (بلا قارب) —</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الرحلة</span>
                    <select class="select" name="trip_id" data-lockable>
                        <option value="">—</option>
                        @foreach ($trips as $t)<option value="{{ $t->id }}" data-boat="{{ $t->boat_id }}">{{ $t->trip_number }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المبلغ قبل الخصم والضريبة *</span><input class="input" name="subtotal" type="number" step="0.01" min="0.01" dir="ltr" required data-lockable></label>
                <label class="field"><span>المورد</span>
                    <select class="select" name="vendor_id">
                        <option value="">—</option>
                        @foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الخصم (ر.س)</span><input class="input" name="discount" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>أو نسبة الخصم %</span><input class="input" name="discount_pct" type="number" step="0.01" min="0" max="100" dir="ltr"></label>
                <label class="field"><span>ضريبة القيمة المضافة %</span><input class="input" name="vat_rate" type="number" step="0.01" min="0" max="100" dir="ltr" placeholder="{{ rtrim(rtrim(number_format($vatRate, 2), '0'), '.') }}"></label>
                <div class="field"><span>الإجمالي</span><div class="input num" id="totalPreview" style="font-weight:700">0.00 ر.س</div></div>
                <label class="field"><span>حالة الدفع</span>
                    <select class="select" name="payment_status_id">
                        @foreach ($statuses as $s)<option value="{{ $s->id }}" data-name="{{ $s->name }}">{{ $s->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field" id="paidField"><span>المبلغ المدفوع</span><input class="input" name="paid_amount" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>طريقة الدفع</span>
                    <select class="select" name="payment_method_id">
                        <option value="">—</option>
                        @foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المرفق (فاتورة / إيصال)</span><input class="input" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
                <label class="field wide" id="removeAttachmentField" style="display:none;flex-direction:row;align-items:center;gap:.5rem"><input type="checkbox" name="remove_attachment" value="1"> <span>حذف المرفق الحالي</span></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('recordDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>

    {{-- درج السداد --}}
    <div class="drawer-overlay" id="payDrawer-overlay" onclick="toggleDrawer('payDrawer', false)"></div>
    <div class="drawer" id="payDrawer">
        <div class="drawer-head">
            <h3 id="payTitle">سداد مصروف</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('payDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="payForm" class="drawer-body">
            @csrf
            <p style="font-size:.8rem;margin-bottom:.75rem">المتبقي: <span class="num" id="payRemaining" style="font-weight:700"></span> ر.س</p>
            <label class="field"><span>المبلغ *</span><input class="input" name="amount" type="number" step="0.01" min="0.01" dir="ltr" required></label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('payDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">تسجيل السداد</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const defaultVat = @json($vatRate);
    const expenseForm = document.getElementById('recordFormEl');
    const field = (name) => expenseForm.querySelector(`[name=${name}]`);

    function refreshExpenseForm() {
        const subtotal = parseFloat(field('subtotal').value) || 0;
        const pct = field('discount_pct').value;
        const discount = pct !== '' ? subtotal * (parseFloat(pct) || 0) / 100 : (parseFloat(field('discount').value) || 0);
        const net = Math.max(0, subtotal - discount);
        const vat = net * (parseFloat(field('vat_rate').value) || 0) / 100;
        document.getElementById('totalPreview').textContent = (net + vat).toFixed(2) + ' ر.س';

        const status = field('payment_status_id');
        const partial = status.options[status.selectedIndex]?.dataset.name === 'مدفوع جزئيًا';
        document.getElementById('paidField').style.display = partial ? '' : 'none';
        field('paid_amount').required = partial;
    }

    // الرحلة تحدد قاربها.
    field('trip_id').addEventListener('change', (e) => {
        const boat = e.target.options[e.target.selectedIndex]?.dataset.boat;
        if (boat) field('boat_id').value = boat;
    });
    expenseForm.addEventListener('input', refreshExpenseForm);
    expenseForm.addEventListener('change', refreshExpenseForm);

    const recordForm = {
        drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle',
        storeUrl: @json(route('panel.owner.expenses.store')), createTitle: 'مصروف جديد', editTitle: 'تعديل المصروف',
        after(record, form) {
            if (!record) {
                field('date').value = new Date().toISOString().slice(0, 10);
                field('vat_rate').value = defaultVat;
                field('payment_status_id').selectedIndex = 0;
            }
            const automatic = Boolean(record && record.is_automatic);
            document.getElementById('automaticNote').style.display = automatic ? '' : 'none';
            document.getElementById('automaticSource').textContent = automatic ? record.source_label : '';
            form.querySelectorAll('[data-lockable]').forEach((el) => {
                // المقفل لا يُرسل، فيُكمله الخادم من سجل الصيانة.
                el.disabled = automatic;
            });
            document.getElementById('removeAttachmentField').style.display = record && record.has_attachment ? 'flex' : 'none';
            refreshExpenseForm();
        },
    };

    function openPayment(row) {
        const form = document.getElementById('payForm');
        form.action = @json(route('panel.owner.expenses.payment', '__ID__')).replace('__ID__', row.id);
        document.getElementById('payTitle').textContent = 'سداد ' + row.number;
        document.getElementById('payRemaining').textContent = Number(row.remaining).toFixed(2);
        form.amount.value = row.remaining;
        form.amount.max = row.remaining;
        toggleDrawer('payDrawer', true);
    }

    @if ($errors->any() && old('date') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
