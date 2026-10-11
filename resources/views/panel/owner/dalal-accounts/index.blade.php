@extends('layouts.app')

@section('title', 'حسابات الدلالين')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calculator'])</div>
            <div>
                <h1>حسابات الدلالين</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.dalal-invoices') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-text']) فواتير الدلالين</a>
            <a href="{{ route('panel.owner.dalal-performance') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'bar-chart']) أداء الدلالين</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'دلالون تعاملت معهم', 'value' => number_format($rows->count()), 'icon' => 'handshake', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'صافيك من مبيعاتهم', 'value' => number_format($summary['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستلم منهم', 'value' => number_format($summary['paid'], 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'المستحق لك', 'value' => number_format($summary['balance'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => $summary['balance'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'بمخزونهم الآن', 'value' => number_format($rows->sum('in_stock_kg'), 1), 'unit' => 'كجم', 'icon' => 'archive', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم الدلال أو جواله..."></label>
        <button class="btn btn-primary">بحث</button>
        <a href="{{ route('panel.owner.dalal-accounts') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الدلال</th><th>الاتفاق</th><th>أرسلت</th><th>بمخزونه</th><th>الفواتير</th><th>المبيعات</th><th>العمولة والأجور</th><th>صافيك</th><th>المستلم</th><th>المستحق لك</th><th>المراجعة</th><th>آخر دفعة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <a href="{{ route('panel.owner.dalal-accounts.show', $row['id']) }}" style="font-weight:600">{{ $row['name'] }}</a>
                            <div class="num" dir="ltr" style="text-align:right;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $row['phone'] }}</div>
                        </td>
                        <td style="font-size:.74rem">
                            @if ($row['partnership'])
                                <span class="badge {{ ['pending' => 'badge-warn', 'accepted' => 'badge-ok', 'rejected' => 'badge-danger'][$row['partnership']->status] ?? '' }}">{{ $row['partnership']->status_label }}</span>
                                <span class="num">{{ (float) $row['partnership']->commission_pct }}% + {{ (float) $row['partnership']->wage_pct }}%</span>
                            @else
                                <span style="color:hsl(var(--muted-foreground))">بلا اتفاق — عمولة 0</span>
                            @endif
                        </td>
                        <td class="num">{{ number_format($row['sent_kg'], 1) }} كجم</td>
                        <td class="num">{{ number_format($row['in_stock_kg'], 1) }} كجم</td>
                        <td class="num">{{ number_format($row['invoices']) }}</td>
                        <td class="num">{{ number_format($row['sales_total'], 2) }}</td>
                        <td class="num">{{ number_format($row['deductions'], 2) }}</td>
                        <td class="num">{{ number_format($row['owner_net'], 2) }}</td>
                        <td class="num">{{ number_format($row['paid'], 2) }}</td>
                        <td class="num" style="font-weight:700;{{ $row['balance'] > 0 ? 'color:var(--st-warn)' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                        <td style="font-size:.72rem">
                            @if ($row['pending'])<a href="{{ route('panel.owner.dalal-invoices', ['dalal_id' => $row['id'], 'status' => 'pending']) }}" class="badge badge-warn">{{ $row['pending'] }} قيد المراجعة</a>@endif
                            @if ($row['rejected'])<a href="{{ route('panel.owner.dalal-invoices', ['dalal_id' => $row['id'], 'status' => 'rejected']) }}" class="badge badge-danger">{{ $row['rejected'] }} مرفوضة</a>@endif
                            @if (! $row['pending'] && ! $row['rejected'])<span style="color:hsl(var(--muted-foreground))">—</span>@endif
                        </td>
                        <td class="num" style="font-size:.76rem">{{ $row['last_payout_at']?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:.35rem;justify-content:flex-end">
                                @if ($row['balance'] > 0)
                                    <button type="button" class="btn btn-outline" style="padding:.3rem .7rem" onclick='openReceipt({!! json_encode(['id' => $row['id'], 'name' => $row['name'], 'balance' => $row['balance']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>تسجيل استلام</button>
                                @endif
                                <a href="{{ route('panel.owner.dalal-accounts.show', $row['id']) }}" class="icon-action" title="كشف الحساب">@include('partials.icon', ['name' => 'eye'])</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا دلالين بعد — أرسل مصيدًا إلى دلال أو اقترح عليه عمولة من صفحة "الدلالون"</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('panel.owner.dalal-accounts.partials.receipt-drawer')
@endsection
