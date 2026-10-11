@extends('layouts.app')

@section('title', 'مسير '.$payroll->payroll_number)

@section('content')
    @php
        $lines = $payroll->lines->sortBy([['is_captain', 'desc'], ['member_name', 'asc']]);
        $unpaid = $lines->reject(fn ($l) => $l->is_paid);
        $pct = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
        $monthFrom = $payroll->period_start->toDateString();
        $monthTo = $payroll->period_end->toDateString();
    @endphp
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calculator'])</div>
            <div>
                <h1>مسير {{ $payroll->boat_name }} — {{ $payroll->period_label }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.payrolls') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) المسيرات</a>
            <a href="{{ route('panel.owner.payrolls.print', $payroll->id) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
            @if ($unpaid->isNotEmpty())
                <button type="button" class="btn btn-primary" onclick="openPay(null)">@include('partials.icon', ['name' => 'check-check']) سداد الكل</button>
            @endif
            @if ($closed)
                <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'lock']) الشهر مُغلق</a>
            @elseif (! $payroll->has_payments)
                <form method="POST" action="{{ route('panel.owner.payrolls.destroy', $payroll->id) }}" onsubmit="return confirm('حذف المسير {{ $payroll->payroll_number }}؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline">@include('partials.icon', ['name' => 'trash']) حذف</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إيراد المصيد', 'value' => number_format($payroll->revenue, 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مصروفات القارب', 'value' => number_format($payroll->expenses, 2), 'unit' => 'ر.س', 'icon' => 'receipt', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'الإهلاك المحمَّل', 'value' => number_format($payroll->depreciation_charged, 2), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'صافي الربح', 'value' => number_format($payroll->net_profit, 2), 'unit' => 'ر.س', 'icon' => 'scale', 'tone' => $payroll->net_profit < 0 ? 'danger' : 'success'])
        @include('partials.stat-card', ['label' => 'نصيب المالك ('.$pct($payroll->owner_share_percent).'%)', 'value' => number_format($payroll->owner_share, 2), 'unit' => 'ر.س', 'icon' => 'user', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نصيب الطاقم', 'value' => number_format($payroll->crew_pool, 2), 'unit' => 'ر.س', 'icon' => 'users', 'tone' => 'success'])
    </div>

    <div class="card" style="margin-bottom:1.25rem;font-size:.8rem;line-height:1.9">
        @include('partials.section-head', ['icon' => 'layers', 'title' => 'من أين جاءت الأرقام'])
        <p style="margin:0"><b>الإيراد:</b> صافي المالك من بيع مصيد رحلات القارب في الشهر (بيعه المباشر، وما باعه الدلال بعد العمولة والأجور).</p>
        <p style="margin:0"><b>المصروفات:</b> <a href="{{ route('panel.owner.expenses', ['boat' => $payroll->boat_id, 'from' => $monthFrom, 'to' => $monthTo]) }}">سندات القارب في الشهر</a>@if ($payroll->expense) — ومنها الرواتب الثابتة المرحَّلة في السند <b class="num">{{ $payroll->expense->expense_number }}</b> (مدفوعه يتبع سداد سطورها)@endif.</p>
        <p style="margin:0"><b>الإهلاك:</b> قسط أصول القارب <span class="num">{{ number_format($payroll->depreciation, 2) }}</span>@if ($payroll->depreciation_deferred > 0) — حُمّل منه ما يغطيه الربح وأُجّل <b class="num">{{ number_format($payroll->depreciation_deferred, 2) }}</b> فلا يصنع خسارة@endif.</p>
        @if ($payroll->net_profit <= 0)<p style="margin:0"><b>لا ربح هذا الشهر: نصيب الطاقم صفر والخسارة على المالك.</b></p>@endif
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الفرد</th><th>الأجر</th><th>الأساس</th><th>زيادة</th><th>خصم</th><th>سلف</th><th>الصافي</th><th>السداد</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>
                            <span style="font-weight:600">{{ $line->member_name }}</span>
                            @if ($line->is_captain)<span class="badge badge-info" style="margin-inline-start:.25rem">كابتن</span>@endif
                            @if ($line->notes)<div style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $line->notes }}</div>@endif
                        </td>
                        <td>
                            {{ $line->payType?->name }}
                            <div style="font-size:.72rem;color:hsl(var(--muted-foreground))">
                                @if (! $line->payType?->isShare())
                                    <span class="num">{{ number_format($line->fixed_salary, 2) }}</span> شهريًا
                                @elseif ($line->custom_share_percent)
                                    نسبة خاصة <span class="num">{{ $pct($line->custom_share_percent) }}%</span>
                                @else
                                    <span class="num">{{ $pct($line->profit_shares) }}</span> سهم
                                @endif
                            </div>
                        </td>
                        <td class="num">{{ number_format($line->base_amount, 2) }}</td>
                        <td class="num">{{ number_format($line->bonus, 2) }}</td>
                        <td class="num">{{ number_format($line->deduction, 2) }}</td>
                        <td class="num">{{ number_format($line->advances, 2) }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($line->net, 2) }}</td>
                        <td>
                            @if ($line->is_paid)
                                <span class="badge badge-ok">مسدَّد</span>
                                <div style="font-size:.72rem;color:hsl(var(--muted-foreground))"><span class="num">{{ $line->paid_at->format('Y-m-d') }}</span> {{ $line->paymentMethod?->name }}</div>
                            @else
                                <span class="badge badge-warn">غير مسدَّد</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                @if ($line->fisher_id)
                                    <a href="{{ route('panel.owner.crew-pay.statement', $line->fisher_id) }}" target="_blank" class="icon-action" title="كشف الحساب">@include('partials.icon', ['name' => 'file-text'])</a>
                                @endif
                                @unless ($line->is_paid)
                                    @unless ($closed)
                                    <button type="button" class="icon-action" title="زيادة / خصم" onclick='openLine({!! json_encode($line->only(['id', 'member_name', 'bonus', 'deduction', 'notes', 'base_amount']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                    @endunless
                                    <button type="button" class="icon-action" title="سداد" onclick='openPay({!! json_encode($line->only(['id', 'member_name', 'net']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'coins'])</button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:700">
                    <td colspan="2">الإجمالي ({{ $lines->count() }})</td>
                    <td class="num">{{ number_format($lines->sum('base_amount'), 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('bonus'), 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('deduction'), 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('advances'), 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('net'), 2) }}</td>
                    <td class="num" colspan="2">مسدَّد {{ number_format($lines->sum('paid_amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- درج الزيادة والخصم --}}
    <div class="drawer-overlay" id="lineDrawer-overlay" onclick="toggleDrawer('lineDrawer', false)"></div>
    <div class="drawer" id="lineDrawer">
        <div class="drawer-head">
            <h3 id="lineTitle">تعديل السطر</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('lineDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="lineForm" class="drawer-body" autocomplete="off">
            @csrf @method('PUT')
            <p style="font-size:.8rem;margin-bottom:.75rem">الأساس: <span class="num" id="lineBase" style="font-weight:700"></span> ر.س</p>
            <div class="form-grid cols-2">
                <label class="field"><span>زيادة (ر.س)</span><input class="input" name="bonus" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field"><span>خصم (ر.س)</span><input class="input" name="deduction" type="number" step="0.01" min="0" dir="ltr"></label>
                <label class="field wide"><span>ملاحظة</span><input class="input" name="notes" maxlength="1000" placeholder="سبب الزيادة أو الخصم"></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('lineDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>

    {{-- درج السداد --}}
    <div class="drawer-overlay" id="payDrawer-overlay" onclick="toggleDrawer('payDrawer', false)"></div>
    <div class="drawer" id="payDrawer">
        <div class="drawer-head">
            <h3 id="payTitle">سداد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('payDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="payForm" class="drawer-body">
            @csrf
            <p style="font-size:.8rem;margin-bottom:.75rem">المبلغ: <span class="num" id="payAmount" style="font-weight:700"></span> ر.س — يُعاد حساب المسير قبل السداد ثم يُجمَّد السطر.</p>
            <label class="field"><span>طريقة الدفع</span>
                <select class="select" name="payment_method_id">
                    <option value="">—</option>
                    @foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                </select>
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('payDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">تسجيل السداد</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const lineUrl = @json(route('panel.owner.payrolls.lines.update', [$payroll->id, '__ID__']));
    const payLineUrl = @json(route('panel.owner.payrolls.lines.pay', [$payroll->id, '__ID__']));
    const payAllUrl = @json(route('panel.owner.payrolls.pay-all', $payroll->id));
    const unpaidTotal = @json(round($unpaid->sum('net'), 2));

    function openLine(line) {
        const form = document.getElementById('lineForm');
        form.action = lineUrl.replace('__ID__', line.id);
        form.bonus.value = line.bonus || '';
        form.deduction.value = line.deduction || '';
        form.notes.value = line.notes || '';
        document.getElementById('lineTitle').textContent = 'تعديل سطر ' + line.member_name;
        document.getElementById('lineBase').textContent = Number(line.base_amount).toFixed(2);
        toggleDrawer('lineDrawer', true);
    }

    function openPay(line) {
        const form = document.getElementById('payForm');
        form.action = line ? payLineUrl.replace('__ID__', line.id) : payAllUrl;
        document.getElementById('payTitle').textContent = line ? 'سداد ' + line.member_name : 'سداد كل السطور المتبقية';
        document.getElementById('payAmount').textContent = Number(line ? line.net : unpaidTotal).toFixed(2);
        toggleDrawer('payDrawer', true);
    }
</script>
@endpush
