@extends('layouts.app')

@section('title', 'مخزون الدلالين')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'archive'])</div>
            <div>
                <h1>مخزون الدلالين</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.consignments.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'send']) إرسال لدلال</a>
            <a href="{{ route('panel.owner.consignments') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-text']) الإرسالات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'أُرسل', 'value' => number_format($totals['consigned_kg'], 1), 'unit' => 'كجم', 'icon' => 'send', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'بيع', 'value' => number_format($totals['sold_kg'], 1), 'unit' => 'كجم', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'باقٍ عندهم', 'value' => number_format($totals['remaining_kg'], 1), 'unit' => 'كجم', 'icon' => 'archive', 'tone' => $totals['remaining_kg'] > 0 ? 'warning' : 'success'])
        @include('partials.stat-card', ['label' => 'نسبة التصريف', 'value' => $totals['sell_through'] !== null ? number_format($totals['sell_through'], 1) : '—', 'unit' => $totals['sell_through'] !== null ? '%' : null, 'icon' => 'gauge', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'قيمة المباع', 'value' => number_format($totals['sales_total'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'صافيك منه', 'value' => number_format($totals['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>القارب</span>
            <select class="select" name="boat_id">
                <option value="">كل القوارب</option>
                @foreach ($boatOptions as $id => $name)<option value="{{ $id }}" @selected((string) request('boat_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الدلال</span>
            <select class="select" name="dalal_id">
                <option value="">كل الدلالين</option>
                @foreach ($dalalOptions as $id => $name)<option value="{{ $id }}" @selected((string) request('dalal_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <label class="field" style="flex-direction:row;align-items:center;gap:.4rem;align-self:flex-end;padding-bottom:.55rem">
            <input type="checkbox" name="holding" value="1" @checked(request()->boolean('holding'))> <span>ما بقي عندهم فقط</span>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.dalal-stock') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'ship', 'title' => 'حسب القارب'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>القارب</th><th>رحلات</th><th>دلالون</th><th>أُرسل</th><th>بيع</th><th>باقٍ</th><th>التصريف</th><th>صافيك</th></tr></thead>
                    <tbody>
                        @forelse ($boats as $boat)
                            <tr>
                                <td style="font-weight:600"><a href="{{ route('panel.owner.dalal-stock', ['boat_id' => $boat['boat_id']] + request()->only('dalal_id', 'holding')) }}">{{ $boat['boat'] }}</a></td>
                                <td class="num">{{ $boat['trips'] }}</td>
                                <td class="num">{{ $boat['dalals'] }}</td>
                                <td class="num">{{ number_format($boat['consigned_kg'], 1) }}</td>
                                <td class="num">{{ number_format($boat['sold_kg'], 1) }}</td>
                                <td class="num" style="font-weight:700">{{ number_format($boat['remaining_kg'], 1) }}</td>
                                <td class="num">{{ $boat['sell_through'] !== null ? number_format($boat['sell_through'], 1).'%' : '—' }}</td>
                                <td class="num">{{ number_format($boat['owner_net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا شيء عند الدلالين</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'handshake', 'title' => 'حسب الدلال'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الدلال</th><th>أُرسل</th><th>بيع</th><th>باقٍ</th><th>التصريف</th><th>صافيك</th></tr></thead>
                    <tbody>
                        @forelse ($dalals as $dalal)
                            <tr>
                                <td style="font-weight:600"><a href="{{ route('panel.owner.dalal-accounts.show', $dalal['dalal_id']) }}">{{ $dalal['dalal'] }}</a></td>
                                <td class="num">{{ number_format($dalal['consigned_kg'], 1) }}</td>
                                <td class="num">{{ number_format($dalal['sold_kg'], 1) }}</td>
                                <td class="num" style="font-weight:700">{{ number_format($dalal['remaining_kg'], 1) }}</td>
                                <td class="num">{{ $dalal['sell_through'] !== null ? number_format($dalal['sell_through'], 1).'%' : '—' }}</td>
                                <td class="num">{{ number_format($dalal['owner_net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'route', 'title' => 'حسب الرحلة'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الرحلة</th><th>القارب</th><th>الدلالون</th><th>أُرسل</th><th>بيع</th><th>أخرى</th><th>باقٍ</th><th>التصريف</th><th>أقدم باقٍ</th><th>قيمة المباع</th><th>صافيك</th><th></th></tr></thead>
                <tbody>
                    @forelse ($trips as $trip)
                        <tr>
                            <td class="num" style="font-weight:600"><a href="{{ route('panel.owner.dalal-stock.trip', $trip['trip_id']) }}">{{ $trip['trip_number'] }}</a></td>
                            <td>{{ $trip['boat'] }}</td>
                            <td style="font-size:.76rem">{{ implode('، ', $trip['dalals']) }}</td>
                            <td class="num">{{ number_format($trip['consigned_kg'], 1) }}</td>
                            <td class="num">{{ number_format($trip['sold_kg'], 1) }}</td>
                            <td class="num">{{ $trip['other_kg'] != 0 ? number_format($trip['other_kg'], 1) : '—' }}</td>
                            <td class="num" style="font-weight:700">{{ number_format($trip['remaining_kg'], 1) }}</td>
                            <td>
                                @if ($trip['sell_through'] !== null)
                                    <span class="num" style="font-size:.76rem">{{ number_format($trip['sell_through'], 1) }}%</span>
                                    <div class="progress" style="margin-top:.2rem"><div style="width:{{ min($trip['sell_through'], 100) }}%;background:hsl(var(--primary))"></div></div>
                                @else — @endif
                            </td>
                            <td>
                                @if ($trip['oldest_days'] !== null)
                                    <span class="badge {{ $trip['oldest_days'] > 7 ? 'badge-danger' : ($trip['oldest_days'] > 3 ? 'badge-warn' : 'badge-info') }}">{{ $trip['oldest_days'] === 0 ? 'اليوم' : 'منذ '.$trip['oldest_days'].' يوم' }}</span>
                                @else — @endif
                            </td>
                            <td class="num">{{ number_format($trip['sales_total'], 2) }}</td>
                            <td class="num">{{ number_format($trip['owner_net'], 2) }}</td>
                            <td style="text-align:left"><a href="{{ route('panel.owner.dalal-stock.trip', $trip['trip_id']) }}" class="icon-action" title="تفاصيل الرحلة">@include('partials.icon', ['name' => 'eye'])</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لم ترسل مصيدًا إلى دلال بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
