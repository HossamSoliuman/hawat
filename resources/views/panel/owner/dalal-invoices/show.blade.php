@extends('layouts.app')

@section('title', 'فاتورة الدلال '.$review->sale->invoice_number)

@section('content')
    @php
        $sale = $review->sale;
        $profile = $review->dalal?->dalalProfile;
    @endphp

    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1>فاتورة الدلال <bdi class="num" dir="ltr">{{ $sale->invoice_number }}</bdi></h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.dalal-invoices.print', $sale->id) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
            <a href="{{ route('panel.owner.dalal-accounts.show', $review->dalal_id) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calculator']) كشف حساب الدلال</a>
            <a href="{{ route('panel.owner.dalal-invoices') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) الفواتير</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الوزن المباع', 'value' => number_format($invoice['weight_kg'], 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مجموع المبيعات', 'value' => number_format($invoice['total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'العمولة والأجور', 'value' => number_format($invoice['deductions'], 2), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'صافيك', 'value' => number_format($invoice['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'غير المسدَّد منها', 'value' => number_format($invoice['outstanding'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => $invoice['outstanding'] > 0 ? 'danger' : 'success'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'بيانات الفاتورة'])
            <dl class="detail-list">
                <dt>الدلال</dt><dd>{{ $review->dalal?->name }}@if ($review->dalal?->phone) <span class="num" dir="ltr">({{ $review->dalal->phone }})</span>@endif</dd>
                <dt>الدكة</dt><dd>{{ $profile?->dakka_name ?? '—' }}{{ $profile?->dakka_number ? ' — '.$profile->dakka_number : '' }}{{ $profile?->port ? ' / '.$profile->port->name : '' }}</dd>
                <dt>المشتري</dt><dd>{{ $sale->customer?->name ?? 'بلا زبون محدد' }}</dd>
                <dt>الرحلات</dt><dd class="num">{{ implode('، ', $invoice['trips']) ?: '—' }}</dd>
                <dt>القوارب</dt><dd>{{ implode('، ', $invoice['boats']) ?: '—' }}</dd>
                <dt>السداد</dt><dd><span class="badge {{ $invoice['payment_badge'] }}">{{ $invoice['payment_label'] }}</span> <span class="num" style="font-size:.76rem">{{ number_format($invoice['settled'], 2) }} من {{ number_format($invoice['owner_net'], 2) }} ر.س</span></dd>
            </dl>
            <p class="note" style="margin-top:.75rem;font-size:.74rem;color:hsl(var(--muted-foreground))">دفعات الدلال لا تُربط بفاتورة — تُوزَّع على فواتيره الأقدم أوّلًا، فهذه حال الفاتورة من مجموع ما دفعه لك.</p>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'check-circle', 'title' => 'المراجعة'])
            <dl class="detail-list">
                <dt>الحالة</dt><dd><span class="badge {{ $review->status_badge }}">{{ $review->status_label }}</span></dd>
                @if ($review->reviewed_at)
                    <dt>آخر مراجعة</dt><dd class="num">{{ $review->reviewed_at->format('Y-m-d H:i') }}{{ $review->reviewer ? ' — '.$review->reviewer->name : '' }}</dd>
                @endif
                @if ($review->reason)
                    <dt>سبب الرفض</dt><dd>{{ $review->reason }}</dd>
                @endif
                @if ($review->dalal_reply)
                    <dt>ردّ الدلال</dt><dd>{{ $review->dalal_reply }} <span class="num" style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $review->replied_at?->format('Y-m-d H:i') }}</span></dd>
                @endif
            </dl>

            @unless ($review->isAccepted())
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem">
                    <form method="POST" action="{{ route('panel.owner.dalal-invoices.accept', $sale->id) }}">
                        @csrf
                        <button class="btn btn-primary">@include('partials.icon', ['name' => 'check-check']) قبول الفاتورة</button>
                    </form>
                    @if ($review->isPending())
                        <button type="button" class="btn btn-outline" onclick="toggleDrawer('rejectDrawer', true)">@include('partials.icon', ['name' => 'x-circle']) رفض بسبب</button>
                    @endif
                </div>
                @if ($review->isRejected())
                    <p style="margin-top:.6rem;font-size:.74rem;color:hsl(var(--muted-foreground))">بانتظار ردّ الدلال — ويمكنك قبولها متى سُوّي الخلاف.</p>
                @endif
            @endunless
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>#</th><th>الصنف</th><th>الرحلة</th><th>القارب</th><th>الوزن (كجم)</th><th>سعر الكيلو</th><th>المبيعات</th><th>العمولة</th><th>الأجور</th><th style="text-align:left">صافيك</th></tr></thead>
            <tbody>
                @foreach ($lines as $item)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td style="font-weight:600">{{ $item->species?->name_ar }}</td>
                        <td class="num">
                            @if ($item->trip)<a href="{{ route('panel.owner.dalal-stock.trip', $item->trip_id) }}">{{ $item->trip->trip_number }}</a>@else — @endif
                        </td>
                        <td>{{ $item->trip?->boat?->name ?? '—' }}</td>
                        <td class="num">{{ number_format($item->weight_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->price_per_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->total, 2) }}</td>
                        <td class="num">{{ number_format($item->commission_amount, 2) }}</td>
                        <td class="num">{{ number_format($item->wage_amount, 2) }}</td>
                        <td class="num" style="text-align:left;font-weight:700">{{ number_format($item->owner_net, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:700">
                    <td colspan="4">المجموع</td>
                    <td class="num">{{ number_format($invoice['weight_kg'], 2) }}</td>
                    <td></td>
                    <td class="num">{{ number_format($invoice['total'], 2) }}</td>
                    <td class="num">{{ number_format($invoice['commission'], 2) }}</td>
                    <td class="num">{{ number_format($invoice['wage'], 2) }}</td>
                    <td class="num" style="text-align:left">{{ number_format($invoice['owner_net'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($review->isPending())
        <div class="drawer-overlay" id="rejectDrawer-overlay" onclick="toggleDrawer('rejectDrawer', false)"></div>
        <div class="drawer" id="rejectDrawer">
            <div class="drawer-head">
                <h3>رفض الفاتورة <bdi class="num" dir="ltr">{{ $sale->invoice_number }}</bdi></h3>
                <button type="button" class="icon-action" onclick="toggleDrawer('rejectDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
            </div>
            <form method="POST" action="{{ route('panel.owner.dalal-invoices.reject', $sale->id) }}" class="drawer-body" autocomplete="off">
                @csrf
                <label class="field wide"><span>سبب الرفض *</span><textarea class="input" name="reason" rows="4" required maxlength="500" placeholder="مثال: سعر الكيلو أقل من سعر السوق يوم البيع">{{ old('reason') }}</textarea></label>
                <p style="font-size:.74rem;color:hsl(var(--muted-foreground));margin-top:.5rem">يصل السبب إلى الدلال في إشعار، ويردّ عليه فتعود الفاتورة إلى مراجعتك. الرفض لا يغيّر المستحق.</p>
                <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                    <button type="button" class="btn btn-outline" onclick="toggleDrawer('rejectDrawer', false)">إلغاء</button>
                    <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'send']) إرسال الرفض</button>
                </div>
            </form>
        </div>
    @endif
@endsection

@if ($review->isPending() && $errors->has('reason'))
    @push('scripts')
        <script>toggleDrawer('rejectDrawer', true);</script>
    @endpush
@endif
