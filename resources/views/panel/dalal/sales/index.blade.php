@extends('layouts.app')

@section('title', 'المبيعات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'coins'])</div>
            <div>
                <h1>المبيعات</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.dalal.sales.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إضافة عملية بيع</a>
            <a href="{{ route('panel.dalal.reports.show', ['sales'] + request()->only(['from', 'to', 'status'])) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) تقرير المبيعات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'عدد المبيعات', 'value' => number_format($totals['count']), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي الإيرادات', 'value' => number_format($totals['revenue'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الوزن الكلي', 'value' => number_format($totals['weight'], 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد العملاء', 'value' => number_format($totals['customers']), 'icon' => 'users', 'tone' => 'warning'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الفاتورة أو العميل..."></label>
        <label class="field"><span>حالة المبيعة</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ([\App\Models\Sale::IN_PROGRESS, \App\Models\Sale::COMPLETED] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>طريقة الدفع</span>
            <select class="select" name="payment_method_id">
                <option value="">الكل</option>
                @foreach ($paymentMethods as $m)<option value="{{ $m->id }}" @selected((string) request('payment_method_id') === (string) $m->id)>{{ $m->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ request('from') }}"></label>
        <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ request('to') }}"></label>
        <label class="field"><span>أقل قيمة</span><input class="input num" type="number" step="0.01" min="0" name="min" value="{{ request('min') }}" dir="ltr" style="width:7rem"></label>
        <label class="field"><span>أعلى قيمة</span><input class="input num" type="number" step="0.01" min="0" name="max" value="{{ request('max') }}" dir="ltr" style="width:7rem"></label>
        <button class="btn btn-primary">تطبيق</button>
        <a href="{{ route('panel.dalal.sales') }}" class="btn btn-outline">مسح الفلاتر</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>رقم الفاتورة</th><th>الحالة</th><th>العميل</th><th>طريقة الدفع</th><th>الوزن</th><th>الإجمالي</th><th>المتبقي</th><th>التاريخ والوقت</th><th></th></tr></thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td><a href="{{ route('panel.dalal.sales.show', $sale) }}" class="num" style="font-weight:700">{{ $sale->invoice_number }}</a></td>
                        <td>
                            <span class="badge {{ $sale->status === \App\Models\Sale::COMPLETED ? 'badge-ok' : 'badge-warn' }}">{{ $sale->status }}</span>
                            @if ($sale->rejected_reviews_count)<a href="{{ route('panel.dalal.sales.show', $sale) }}" class="badge badge-danger" title="رفض مالك سطوره — افتح الفاتورة للردّ">رفض مالك</a>@endif
                        </td>
                        <td>{{ $sale->customer?->name ?? '—' }}</td>
                        <td>{{ $sale->paymentMethod?->name ?? '—' }}</td>
                        <td class="num">{{ number_format($sale->items_sum_weight_kg ?? 0, 1) }} كجم</td>
                        <td class="num" style="font-weight:700">{{ number_format($sale->total, 2) }}</td>
                        <td class="num" style="{{ $sale->remaining > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($sale->remaining, 2) }}</td>
                        <td class="num">{{ $sale->sold_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <a href="{{ $sale->invoiceUrl() }}" target="_blank" class="icon-action" title="طباعة الفاتورة">@include('partials.icon', ['name' => 'printer'])</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $sales])
@endsection
