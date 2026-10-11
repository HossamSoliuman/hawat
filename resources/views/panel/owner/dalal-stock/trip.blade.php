@extends('layouts.app')

@section('title', 'مخزون الدلالين — '.$trip->trip_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'route'])</div>
            <div>
                <h1 class="num">الرحلة {{ $trip->trip_number }} عند الدلالين</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.trips.show', $trip->id) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'route']) صفحة الرحلة</a>
            <a href="{{ route('panel.owner.dalal-stock') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'archive']) مخزون الدلالين</a>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'أُرسل', 'value' => number_format($totals['consigned_kg'], 1), 'unit' => 'كجم', 'icon' => 'send', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'بيع', 'value' => number_format($totals['sold_kg'], 1), 'unit' => 'كجم', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'باقٍ عندهم', 'value' => number_format($totals['remaining_kg'], 1), 'unit' => 'كجم', 'icon' => 'archive', 'tone' => $totals['remaining_kg'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'قيمة المباع', 'value' => number_format($totals['sales_total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'صافيك', 'value' => number_format($totals['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
    </div>

    @forelse ($dalals as $dalal)
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'handshake', 'title' => $dalal['dalal']])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الصنف</th><th>أُرسل</th><th>بيع</th><th>أخرى</th><th>باقٍ</th><th>متوسط سعر الكيلو</th><th>قيمة المباع</th><th>صافيك</th><th>منذ</th></tr></thead>
                    <tbody>
                        @foreach ($dalal['species'] as $row)
                            <tr>
                                <td style="font-weight:600">{{ $row['species'] }}</td>
                                <td class="num">{{ number_format($row['consigned_kg'], 2) }}</td>
                                <td class="num">{{ number_format($row['sold_kg'], 2) }}</td>
                                <td class="num">{{ $row['other_kg'] != 0 ? number_format($row['other_kg'], 2) : '—' }}</td>
                                <td class="num" style="font-weight:700">{{ number_format($row['remaining_kg'], 2) }}</td>
                                <td class="num">{{ $row['avg_price'] !== null ? number_format($row['avg_price'], 2) : '—' }}</td>
                                <td class="num">{{ number_format($row['sales_total'], 2) }}</td>
                                <td class="num">{{ number_format($row['owner_net'], 2) }}</td>
                                <td class="num" style="font-size:.76rem">{{ $row['first_in']?->format('Y-m-d') }}@if ($row['age_days'] !== null) <span class="badge {{ $row['age_days'] > 7 ? 'badge-danger' : ($row['age_days'] > 3 ? 'badge-warn' : 'badge-info') }}">{{ $row['age_days'] }} يوم</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card" style="margin-bottom:1.25rem;padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لم يُرسل شيء من هذه الرحلة إلى دلال.</div>
    @endforelse

    <div class="grid-2">
        <div class="card">
            @include('partials.section-head', ['icon' => 'send', 'title' => 'الإرسالات'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الرقم</th><th>التاريخ</th><th>الدلال</th><th>الأصناف</th><th>الوزن</th></tr></thead>
                    <tbody>
                        @forelse ($consignments as $c)
                            <tr>
                                <td class="num" style="white-space:nowrap"><a href="{{ route('panel.owner.consignments.show', $c->id) }}">{{ $c->consignment_number }}</a></td>
                                <td class="num" style="white-space:nowrap">{{ $c->sent_at?->format('Y-m-d') }}</td>
                                <td>{{ $c->dalal?->name }}</td>
                                <td style="font-size:.76rem">{{ $c->items->pluck('species.name_ar')->implode('، ') }}</td>
                                <td class="num">{{ number_format($c->total_kg, 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'file-text', 'title' => 'البيع منها'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الفاتورة</th><th>التاريخ</th><th>الدلال</th><th>الصنف</th><th>الوزن</th><th>السعر</th><th>صافيك</th><th>المراجعة</th></tr></thead>
                    <tbody>
                        @forelse ($sales as $item)
                            @php $review = $reviews[$item->sale_id] ?? null; @endphp
                            <tr>
                                <td class="num" style="white-space:nowrap"><a href="{{ route('panel.owner.dalal-invoices.show', $item->sale_id) }}">{{ $item->sale?->invoice_number }}</a></td>
                                <td class="num" style="white-space:nowrap">{{ $item->sale?->sold_at?->format('Y-m-d') }}</td>
                                <td>{{ $item->sale?->seller?->name }}</td>
                                <td>{{ $item->species?->name_ar }}</td>
                                <td class="num">{{ number_format($item->weight_kg, 1) }}</td>
                                <td class="num">{{ number_format($item->price_per_kg, 2) }}</td>
                                <td class="num">{{ number_format($item->owner_net, 2) }}</td>
                                <td>@if ($review)<span class="badge {{ $review->status_badge }}">{{ $review->status_label }}</span>@else — @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لم يُبع شيء منها بعد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
