@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $s = $statistics;
    @endphp

    @include('panel.owner.reports.partials.head', ['print' => true])

    <div class="stat-grid" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المبيعات', 'value' => number_format($s['total_sales']), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي الإيرادات', 'value' => number_format($s['total_revenue'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'إجمالي الوزن', 'value' => number_format($s['total_weight'], 2), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'warning'])
    </div>

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>من تاريخ:</span><input class="input" type="date" name="from" value="{{ $from }}" dir="ltr"></label>
        <label class="field"><span>إلى تاريخ:</span><input class="input" type="date" name="to" value="{{ $to }}" dir="ltr"></label>
        <label class="field" style="min-width:10rem"><span>الحالة:</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <div style="display:flex;gap:.5rem;margin-inline-start:auto">
            <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) تصفية</button>
            <a href="{{ route('panel.owner.reports.show', $key) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'refresh-cw']) إعادة تعيين</a>
        </div>
    </form>

    <div class="table-card">
        <table class="data-table compact">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الفاتورة</th>
                    <th>الحالة</th>
                    <th>الزبون</th>
                    <th>وسيلة الدفع</th>
                    <th class="end">الوزن</th>
                    <th class="end">العمولة</th>
                    <th class="end">الأجور</th>
                    <th class="end">السعر الإجمالي</th>
                    <th class="end">صافي المالك</th>
                    <th class="end">المتبقي (الدلال)</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td><a href="{{ route('panel.owner.sales.show', $row['sale_id']) }}"><bdi class="num" dir="ltr">{{ $row['number'] }}</bdi></a></td>
                        <td><span class="badge {{ $row['completed'] ? 'badge-ok' : 'badge-warn' }}">{{ $row['completed'] ? 'مكتملة' : 'جارية' }}</span></td>
                        <td>{{ $row['customer'] }}</td>
                        <td>{{ $row['payment_method'] }}</td>
                        <td class="end num">{{ number_format($row['weight'], 2) }} كجم</td>
                        <td class="end">{{ $money($row['commission']) }}</td>
                        <td class="end">{{ $money($row['labor']) }}</td>
                        <td class="end">{{ $money($row['total']) }}</td>
                        <td class="end" style="font-weight:700">{{ $money($row['net_owner']) }}</td>
                        <td class="end {{ $row['remaining'] > 0 ? 'tx-bad' : '' }}">{{ $money($row['remaining']) }}</td>
                        <td><bdi class="num" dir="ltr">{{ $row['date'] }}</bdi></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="empty">لا توجد بيانات مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
