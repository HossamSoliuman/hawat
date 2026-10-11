@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $s = $statistics; @endphp

    @include('panel.owner.reports.partials.head', ['print' => true])

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الرحلات', 'value' => number_format($s['total_trips']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($s['total_catch']), 'icon' => 'fish', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الوزن الإجمالي', 'value' => number_format($s['total_weight'], 2), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'عدد السجلات', 'value' => number_format(count($rows)), 'icon' => 'list-checks', 'tone' => 'muted'])
    </div>

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>من</span><input class="input" type="date" name="from" value="{{ $from }}" dir="ltr"></label>
        <label class="field"><span>إلى</span><input class="input" type="date" name="to" value="{{ $to }}" dir="ltr"></label>
        <label class="field" style="min-width:12rem"><span>الحالة</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach
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
                    <th>القارب</th>
                    <th>رقم الرحلة</th>
                    <th>رقم التصريح</th>
                    <th>حالة الرحلة</th>
                    <th>الصيّاد</th>
                    <th>الكابتن</th>
                    <th class="end">مجموع الأصناف</th>
                    <th class="end">الوزن الإجمالي</th>
                    <th>الميناء</th>
                    <th>التاريخ (المغادرة - العودة)</th>
                    <th>الوقت (المغادرة - العودة)</th>
                    <th>عدد الأيام</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td>{{ $row['boat_name'] }}</td>
                        <td><a href="{{ route('panel.owner.trips.show', $row['trip_id']) }}"><bdi class="num" dir="ltr">{{ $row['number'] }}</bdi></a></td>
                        <td><bdi class="num" dir="ltr">{{ $row['license_number'] ?: '--' }}</bdi></td>
                        <td><span class="badge badge-muted">{{ $row['status'] }}</span></td>
                        <td>{{ $row['owner_name'] }}</td>
                        <td>{{ $row['captain_name'] }}</td>
                        <td class="end num">{{ $row['items'] ?: '--' }}</td>
                        <td class="end num">{{ $row['weight'] > 0 ? number_format($row['weight'], 2).' كجم' : '--' }}</td>
                        <td>{{ $row['port'] }}</td>
                        <td>@if ($row['departed'] && $row['returned'])<bdi class="num" dir="ltr">{{ $row['departed'] }} - {{ $row['returned'] }}</bdi>@else--@endif</td>
                        <td>@if ($row['departed_time'] && $row['returned_time'])<bdi class="num" dir="ltr">{{ $row['departed_time'] }} - {{ $row['returned_time'] }}</bdi>@else--@endif</td>
                        <td>{{ $row['days'] ? '('.$row['days'].' '.($row['days'] === 1 ? 'يوم' : 'أيام').')' : '--' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="empty">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
