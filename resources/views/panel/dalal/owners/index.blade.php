@extends('layouts.app')

@section('title', 'الصيّادون المرتبطون')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'users'])</div>
            <div>
                <h1>الصيّادون المرتبطون</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.dalal.reports.show', 'payouts') }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) تقرير المدفوعات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الملاك', 'value' => number_format($rows->count()), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'صافي الملاك', 'value' => number_format($rows->sum('owner_net'), 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'المدفوع', 'value' => number_format($rows->sum('paid'), 2), 'unit' => 'ر.س', 'icon' => 'check-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستحق', 'value' => number_format($rows->sum('due'), 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال..."></label>
        <label class="field"><span>المنطقة</span>
            <select class="select" name="region_id">
                <option value="">كل المناطق</option>
                @foreach ($regions as $o)<option value="{{ $o->id }}" @selected((string) request('region_id') === (string) $o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تطبيق</button>
        <a href="{{ route('panel.dalal.owners') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card" style="margin-bottom:1.25rem">
        <table class="data-table">
            <thead><tr><th>الاسم</th><th>الجوال</th><th>القوارب</th><th>المنطقة / الميناء</th><th>الاتفاق</th><th>المستلم</th><th>في المخزون</th><th>المباع</th><th>صافيه</th><th>المدفوع</th><th>المستحق</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row['name'] }}</td>
                        <td class="num" dir="ltr" style="text-align:right">{{ $row['phone'] ?? '—' }}</td>
                        <td class="num" style="text-align:center">{{ $row['boats_count'] }}</td>
                        <td style="font-size:.76rem">{{ $row['region'] ?? '—' }}{{ $row['port'] ? ' / '.$row['port'] : '' }}</td>
                        <td>
                            @if ($row['partnership'])
                                <span class="badge {{ $row['partnership']['status'] === 'accepted' ? 'badge-ok' : ($row['partnership']['status'] === 'pending' ? 'badge-warn' : 'badge-danger') }}">{{ $row['partnership']['status_label'] }}</span>
                                <span class="num" style="font-size:.72rem">{{ $row['partnership']['commission_pct'] }}% + {{ $row['partnership']['wage_pct'] }}%</span>
                            @else
                                <span style="font-size:.74rem;color:hsl(var(--muted-foreground))">بلا اتفاق</span>
                            @endif
                        </td>
                        <td class="num">{{ number_format($row['received_kg'], 1) }}</td>
                        <td class="num">{{ number_format($row['in_stock_kg'], 1) }}</td>
                        <td class="num">{{ number_format($row['sold_kg'], 1) }}</td>
                        <td class="num">{{ number_format($row['owner_net'], 2) }}</td>
                        <td class="num">{{ number_format($row['paid'], 2) }}</td>
                        <td class="num" style="font-weight:700;{{ $row['due'] > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($row['due'], 2) }}</td>
                        <td>
                            @if ($row['due'] > 0)
                                <button type="button" class="btn btn-outline" style="padding:.3rem .7rem" onclick='openPayout({!! json_encode(['id' => $row['id'], 'name' => $row['name'], 'due' => $row['due']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>دفع</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لم يقم أي صياد بإرسال مخزون إليك بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'calculator', 'title' => 'آخر الدفعات للملاك'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>المالك</th><th>طريقة الدفع</th><th>ملاحظات</th><th style="text-align:left">المبلغ</th></tr></thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr>
                            <td class="num">{{ $payout->paid_at?->format('Y-m-d') }}</td>
                            <td>{{ $payout->owner?->name }}</td>
                            <td>{{ $payout->paymentMethod?->name ?? '—' }}</td>
                            <td style="font-size:.76rem">{{ $payout->notes ?? '—' }}@if ($payout->recordedByOwner())<div><span class="badge badge-info">سجّله المالك استلامًا</span>{{ $payout->reference ? ' — '.$payout->reference : '' }}</div>@endif</td>
                            <td class="num" style="text-align:left">{{ number_format($payout->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا دفعات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="drawer-overlay" id="payoutDrawer-overlay" onclick="toggleDrawer('payoutDrawer', false)"></div>
    <div class="drawer" id="payoutDrawer">
        <div class="drawer-head">
            <h3 id="payoutTitle">دفعة لمالك</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('payoutDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="payoutForm" action="" class="drawer-body" autocomplete="off">
            @csrf
            <div class="form-grid cols-2">
                <label class="field"><span>المبلغ (ر.س) *</span><input class="input num" type="number" step="0.01" min="0.01" name="amount" dir="ltr" required></label>
                <label class="field"><span>طريقة الدفع</span>
                    <select class="select" name="payment_method_id">
                        <option value="">—</option>
                        @foreach ($paymentMethods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>التاريخ</span><input class="input" type="date" name="paid_at" value="{{ now()->toDateString() }}"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('payoutDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">تسجيل الدفعة</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const payoutUrl = @json(route('panel.dalal.owners.payout', ['owner' => '__ID__']));

    function openPayout(owner) {
        const form = document.getElementById('payoutForm');
        form.action = payoutUrl.replace('__ID__', owner.id);
        form.amount.value = owner.due;
        form.amount.max = owner.due;
        document.getElementById('payoutTitle').textContent = 'دفعة إلى ' + owner.name + ' — المستحق ' + owner.due + ' ر.س';
        toggleDrawer('payoutDrawer', true);
    }
</script>
@endpush
