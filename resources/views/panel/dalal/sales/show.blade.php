@extends('layouts.app')

@section('title', 'الفاتورة '.$sale->invoice_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1 class="num">تفاصيل عملية البيع {{ $sale->invoice_number }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ $sale->invoiceUrl() }}" target="_blank" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) طباعة الفاتورة</a>
            <a href="{{ route('panel.dalal.sales') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) المبيعات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المجموع الكلي', 'value' => number_format($sale->total, 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'مجموع الأوزان', 'value' => number_format($sale->items->sum('weight_kg'), 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي الكمية', 'value' => number_format($sale->items->pluck('species_id')->unique()->count()), 'unit' => 'صنف', 'icon' => 'fish', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'ربحك (عمولة + أجور)', 'value' => number_format($sale->commission_amount + $sale->wage_amount, 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'warning'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'بيانات الفاتورة'])
            <dl class="detail-list">
                <dt>اسم العميل</dt><dd>{{ $sale->customer?->name ?? '—' }}@if ($sale->customer?->phone) <span class="num" dir="ltr">({{ $sale->customer->phone }})</span>@endif</dd>
                <dt>طريقة الدفع</dt><dd>{{ $sale->paymentMethod?->name ?? '—' }}</dd>
                <dt>حالة الفاتورة</dt><dd><span class="badge {{ $sale->status === \App\Models\Sale::COMPLETED ? 'badge-ok' : 'badge-warn' }}">{{ $sale->status }}</span></dd>
                <dt>حالة الدفع</dt><dd>{{ $sale->paymentStatus?->name ?? '—' }}</dd>
                <dt>ملاحظات</dt><dd>{{ $sale->notes ?? '—' }}</dd>
            </dl>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'calculator', 'title' => 'المبالغ'])
            <dl class="detail-list">
                <dt>المجموع</dt><dd class="num">{{ number_format($sale->subtotal, 2) }} ر.س</dd>
                <dt>الخصم</dt><dd class="num">{{ number_format($sale->discount, 2) }} ر.س</dd>
                <dt>الإجمالي</dt><dd class="num" style="font-weight:700">{{ number_format($sale->total, 2) }} ر.س</dd>
                <dt>عمولة الدلال</dt><dd class="num">{{ number_format($sale->commission_amount, 2) }} ر.س</dd>
                <dt>أجور العمالة</dt><dd class="num">{{ number_format($sale->wage_amount, 2) }} ر.س</dd>
                <dt>صافي الملاك</dt><dd class="num">{{ number_format($sale->owner_net, 2) }} ر.س</dd>
                <dt>المدفوع</dt><dd class="num">{{ number_format($sale->paid_amount, 2) }} ر.س</dd>
                <dt>المتبقي</dt><dd class="num" style="{{ $sale->remaining > 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($sale->remaining, 2) }} ر.س</dd>
            </dl>
            @if ($sale->remaining > 0)
                <form method="POST" action="{{ route('panel.dalal.sales.payment', $sale) }}" class="filter-bar" style="margin-top:1rem">
                    @csrf
                    <label class="field"><span>تحصيل دفعة (ر.س)</span><input class="input num" type="number" step="0.01" min="0.01" max="{{ $sale->remaining }}" name="amount" value="{{ $sale->remaining }}" dir="ltr" required></label>
                    <button class="btn btn-primary">تسجيل الدفعة</button>
                </form>
            @endif
        </div>
    </div>

    @if ($sale->reviews->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'check-circle', 'title' => 'مراجعة الملاك'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>المالك</th><th>الحالة</th><th>سبب الرفض</th><th>ردّك</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($sale->reviews as $review)
                            <tr>
                                <td style="font-weight:600">{{ $review->owner?->name }}</td>
                                <td><span class="badge {{ $review->status_badge }}">{{ $review->status_label }}</span>@if ($review->reviewed_at)<div class="num" style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $review->reviewed_at->format('Y-m-d H:i') }}</div>@endif</td>
                                <td style="font-size:.8rem">{{ $review->reason ?? '—' }}</td>
                                <td style="font-size:.8rem">{{ $review->dalal_reply ?? '—' }}</td>
                                <td>
                                    @if ($review->isRejected())
                                        <form method="POST" action="{{ route('panel.dalal.sales.reviews.reply', [$sale, $review->id]) }}" style="display:flex;gap:.4rem;align-items:flex-end">
                                            @csrf
                                            <label class="field" style="min-width:220px"><span>الردّ على {{ $review->owner?->name }}</span><input class="input" name="reply" maxlength="500" required placeholder="وضّح أو اذكر ما سُوّي"></label>
                                            <button class="btn btn-primary">@include('partials.icon', ['name' => 'send']) إرسال</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>#</th><th>الصنف</th><th>المالك</th><th>الرحلة</th><th>الوزن (كجم)</th><th>سعر الكيلو</th><th>مجموع الأسعار</th><th>العمولة والأجور</th><th style="text-align:left">صافي المالك</th></tr></thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td style="font-weight:600">{{ $item->species?->name_ar }}</td>
                        <td>{{ $item->owner?->name ?? '—' }}</td>
                        <td class="num">{{ $item->trip?->trip_number ?? '—' }}</td>
                        <td class="num">{{ number_format($item->weight_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->price_per_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->total, 2) }}</td>
                        <td class="num">{{ number_format($item->commission_amount + $item->wage_amount, 2) }}</td>
                        <td class="num" style="text-align:left">{{ number_format($item->owner_net, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
