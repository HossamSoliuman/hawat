@extends('layouts.app')

@section('title', 'فواتير الدلالين')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1>فواتير الدلالين</h1>
            </div>
        </div>
        <div class="actions">
            @if ($summary['pending'] > 0)
                <form method="POST" action="{{ route('panel.owner.dalal-invoices.accept-all') }}" onsubmit="return confirm('قبول كل الفواتير قيد المراجعة{{ request('dalal_id') ? ' من '.($dalals[request('dalal_id')] ?? 'هذا الدلال') : '' }}؟')">
                    @csrf
                    @if (request('dalal_id'))<input type="hidden" name="dalal_id" value="{{ request('dalal_id') }}">@endif
                    <button class="btn btn-primary">@include('partials.icon', ['name' => 'check-check']) قبول كل ما قيد المراجعة{{ request('dalal_id') ? ' من '.($dalals[request('dalal_id')] ?? '') : '' }}</button>
                </form>
            @endif
            <a href="{{ route('panel.owner.dalal-accounts') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calculator']) حسابات الدلالين</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'فواتير الدلالين', 'value' => number_format($summary['invoices']), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'صافيك منها', 'value' => number_format($summary['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المستحق لك', 'value' => number_format($summary['balance'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => $summary['balance'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'قيد المراجعة', 'value' => number_format($summary['pending']), 'icon' => 'clock', 'tone' => $summary['pending'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'مرفوضة', 'value' => number_format($summary['rejected']), 'icon' => 'x-circle', 'tone' => $summary['rejected'] > 0 ? 'danger' : 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>رقم الفاتورة</span><input class="input num" name="search" value="{{ request('search') }}" dir="ltr" placeholder="26-09-..."></label>
        <label class="field"><span>الدلال</span>
            <select class="select" name="dalal_id">
                <option value="">كل الدلالين</option>
                @foreach ($dalals as $id => $name)<option value="{{ $id }}" @selected((string) request('dalal_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>المراجعة</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>السداد</span>
            <select class="select" name="payment">
                <option value="">الكل</option>
                @foreach ($payments as $key => $label)<option value="{{ $key }}" @selected(request('payment') === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}" dir="ltr"></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.dalal-invoices') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الفاتورة</th><th>التاريخ</th><th>الدلال</th><th>الرحلة</th><th>الوزن (كجم)</th><th>المبيعات</th><th>العمولة والأجور</th><th>صافيك</th><th>المسدَّد</th><th>السداد</th><th>المراجعة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($invoices as $row)
                    <tr>
                        <td class="num" style="font-weight:600"><a href="{{ route('panel.owner.dalal-invoices.show', $row['sale_id']) }}">{{ $row['invoice_number'] }}</a></td>
                        <td class="num">{{ $row['sold_at']?->format('Y-m-d') }}</td>
                        <td><a href="{{ route('panel.owner.dalal-accounts.show', $row['dalal_id']) }}">{{ $row['dalal'] }}</a></td>
                        <td class="num" style="font-size:.76rem">{{ implode('، ', $row['trips']) ?: '—' }}</td>
                        <td class="num">{{ number_format($row['weight_kg'], 1) }}</td>
                        <td class="num">{{ number_format($row['total'], 2) }}</td>
                        <td class="num">{{ number_format($row['deductions'], 2) }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($row['owner_net'], 2) }}</td>
                        <td class="num">{{ number_format($row['settled'], 2) }}</td>
                        <td><span class="badge {{ $row['payment_badge'] }}">{{ $row['payment_label'] }}</span></td>
                        <td>
                            <span class="badge {{ $row['review']->status_badge }}">{{ $row['review']->status_label }}</span>
                            @if ($row['review']->isPending() && $row['review']->dalal_reply)<div style="font-size:.68rem;color:hsl(var(--muted-foreground))">ردّ الدلال</div>@endif
                        </td>
                        <td style="text-align:left"><a href="{{ route('panel.owner.dalal-invoices.show', $row['sale_id']) }}" class="icon-action" title="عرض">@include('partials.icon', ['name' => 'eye'])</a></td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا فواتير — حين يبيع دلال من مصيد أرسلته إليه تظهر هنا</td></tr>
                @endforelse
            </tbody>
            @if ($totals['count'] > 0)
                <tfoot>
                    <tr style="font-weight:700">
                        <td colspan="6">المجموع ({{ number_format($totals['count']) }} فاتورة ضمن التصفية)</td>
                        <td class="num">{{ number_format($totals['deductions'], 2) }}</td>
                        <td class="num">{{ number_format($totals['owner_net'], 2) }}</td>
                        <td colspan="4" class="num">غير المسدَّد {{ number_format($totals['outstanding'], 2) }} ر.س</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $invoices])
@endsection
