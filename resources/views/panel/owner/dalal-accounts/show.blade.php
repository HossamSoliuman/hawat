@extends('layouts.app')

@section('title', 'حساب '.$dalal->name)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calculator'])</div>
            <div>
                <h1>كشف حساب {{ $dalal->name }}</h1>
            </div>
        </div>
        <div class="actions">
            @if ($account && $account['balance'] > 0)
                <button type="button" class="btn btn-primary" onclick='openReceipt({!! json_encode(['id' => $dalal->id, 'name' => $dalal->name, 'balance' => $account['balance']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'plus']) تسجيل استلام</button>
            @endif
            <a href="{{ route('panel.owner.dalal-accounts.print', ['dalal' => $dalal->id] + request()->only('from', 'to')) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة الكشف</a>
            <a href="{{ route('panel.owner.dalal-invoices', ['dalal_id' => $dalal->id]) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-text']) فواتيره</a>
            <a href="{{ route('panel.owner.dalal-accounts') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) الحسابات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if ($account)
        <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
            @include('partials.stat-card', ['label' => 'أرسلت إليه', 'value' => number_format($account['sent_kg'], 1), 'unit' => 'كجم', 'icon' => 'send', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'بمخزونه الآن', 'value' => number_format($account['in_stock_kg'], 1), 'unit' => 'كجم', 'icon' => 'archive', 'tone' => 'info'])
            @include('partials.stat-card', ['label' => 'المبيعات', 'value' => number_format($account['sales_total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'صافيك', 'value' => number_format($account['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
            @include('partials.stat-card', ['label' => 'المستلم', 'value' => number_format($account['paid'], 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'info'])
            @include('partials.stat-card', ['label' => 'المستحق لك', 'value' => number_format($account['balance'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => $account['balance'] > 0 ? 'warning' : 'success'])
        </div>
    @endif

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'user', 'title' => 'الدلال'])
            <dl class="detail-list">
                <dt>الجوال</dt><dd class="num" dir="ltr" style="text-align:right">{{ $dalal->phone ?? '—' }}</dd>
                <dt>الدكة</dt><dd>{{ $dalal->dalalProfile?->dakka_name ?? '—' }}{{ $dalal->dalalProfile?->port ? ' / '.$dalal->dalalProfile->port->name : '' }}</dd>
                <dt>المنشأة</dt><dd>{{ $dalal->dalalProfile?->company_name ?? '—' }}</dd>
                <dt>الاتفاق</dt>
                <dd>
                    @if ($account['partnership'] ?? null)
                        {{ $account['partnership']->status_label }} — <span class="num">{{ (float) $account['partnership']->commission_pct }}% عمولة + {{ (float) $account['partnership']->wage_pct }}% أجور</span>
                    @else
                        بلا اتفاق مقبول — لا عمولة ولا أجور
                    @endif
                </dd>
            </dl>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'clock', 'title' => 'المراجعة والتواريخ'])
            <dl class="detail-list">
                <dt>الفواتير</dt><dd class="num">{{ number_format($account['invoices'] ?? 0) }}</dd>
                <dt>قيد المراجعة</dt><dd class="num">{{ number_format($account['pending'] ?? 0) }}</dd>
                <dt>مرفوضة</dt><dd class="num">{{ number_format($account['rejected'] ?? 0) }}</dd>
                <dt>آخر إرسال</dt><dd class="num">{{ ($account['last_sent_at'] ?? null)?->format('Y-m-d') ?? '—' }}</dd>
                <dt>آخر بيع</dt><dd class="num">{{ ($account['last_sale_at'] ?? null)?->format('Y-m-d') ?? '—' }}</dd>
                <dt>آخر دفعة</dt><dd class="num">{{ ($account['last_payout_at'] ?? null)?->format('Y-m-d') ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}" dir="ltr"></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.dalal-accounts.show', $dalal->id) }}" class="btn btn-outline">كل الحركات</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>التاريخ</th><th>البيان</th><th>التفاصيل</th><th>المرجع / المراجعة</th><th>صافي لك</th><th>مستلم</th><th>الرصيد</th><th></th></tr></thead>
            <tbody>
                @if (request('from'))
                    <tr style="font-weight:600">
                        <td class="num">{{ request('from') }}</td>
                        <td colspan="5">رصيد افتتاحي — ما قبل بداية الفترة</td>
                        <td class="num">{{ number_format($statement['opening'], 2) }}</td>
                        <td></td>
                    </tr>
                @endif
                @forelse ($statement['entries'] as $entry)
                    <tr>
                        <td class="num">{{ $entry['date']?->format('Y-m-d') }}</td>
                        <td style="font-weight:600">
                            @if ($entry['kind'] === 'invoice')
                                <a href="{{ route('panel.owner.dalal-invoices.show', $entry['sale_id']) }}">{{ $entry['label'] }} <bdi class="num" dir="ltr">{{ $entry['number'] }}</bdi></a>
                            @else
                                {{ $entry['label'] }}
                            @endif
                        </td>
                        <td style="font-size:.76rem">{{ $entry['details'] ?: '—' }}</td>
                        <td class="num" style="font-size:.76rem">{{ $entry['reference'] ?? '—' }}</td>
                        <td class="num">{{ $entry['net'] ? number_format($entry['net'], 2) : '—' }}</td>
                        <td class="num">{{ $entry['paid'] ? number_format($entry['paid'], 2) : '—' }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($entry['balance'], 2) }}</td>
                        <td>
                            @if ($entry['kind'] === 'payout' && $entry['payout']->recordedByOwner())
                                <form method="POST" action="{{ route('panel.owner.dalal-accounts.receipts.destroy', [$dalal->id, $entry['payout']->id]) }}" onsubmit="return confirm('حذف هذا الاستلام؟')" style="display:flex;justify-content:flex-end">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا حركات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:700">
                    <td colspan="4">مجموع الفترة</td>
                    <td class="num">{{ number_format($statement['totals']['net'], 2) }}</td>
                    <td class="num">{{ number_format($statement['totals']['paid'], 2) }}</td>
                    <td class="num">{{ number_format($statement['totals']['closing'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @include('panel.owner.dalal-accounts.partials.receipt-drawer')
@endsection
