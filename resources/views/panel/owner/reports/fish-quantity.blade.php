@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')

    <div class="card">
        <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="border:0;padding:0;background:none;margin-bottom:1rem">
            <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ $from }}" dir="ltr"></label>
            <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ $to }}" dir="ltr"></label>
            <label class="field" style="min-width:10rem"><span>القارب</span>
                <select class="select" name="boat_id">
                    <option value="">الكل</option>
                    @foreach ($boats as $b)<option value="{{ $b->id }}" @selected($boatId === $b->id)>{{ $b->name }}</option>@endforeach
                </select>
            </label>
            <label class="field" style="min-width:10rem"><span>اسم الرحلة</span>
                <select class="select" name="trip_id">
                    <option value="">الكل</option>
                    @foreach ($trips as $t)<option value="{{ $t->id }}" @selected($trip?->id === $t->id)>{{ $t->trip_number }}</option>@endforeach
                </select>
            </label>
            <label class="field" style="min-width:10rem"><span>نوع السمك</span>
                <select class="select" name="fish_id">
                    <option value="">الكل</option>
                    @foreach ($species as $s)<option value="{{ $s->id }}" @selected($fish?->id === $s->id)>{{ $s->name_ar }}</option>@endforeach
                </select>
            </label>
            <div style="display:flex;gap:.5rem;margin-inline-start:auto">
                <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) تحديث</button>
                <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
            </div>
        </form>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نوع السمك</th>
                        <th class="end">إجمالي الوزن</th>
                        <th>الوحدة</th>
                        <th class="end">سعر الكيلو</th>
                        <th class="end">إجمالي السعر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        <tr>
                            <td class="num">{{ $loop->iteration }}</td>
                            <td>{{ $stock['fish_name'] }}</td>
                            <td class="end num">{{ number_format($stock['weight'], 2) }}</td>
                            <td>{{ $stock['unit'] }}</td>
                            <td class="end">{{ $money($stock['price_per_kg']) }}</td>
                            <td class="end">{{ $money($stock['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">لا توجد بيانات للتصفية المختارة</td></tr>
                    @endforelse
                </tbody>
                @if ($stocks->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2">المجموع (كجم)</td>
                            <td class="end num">{{ number_format($stocks->sum('weight'), 2) }}</td>
                            <td colspan="3">كجم</td>
                        </tr>
                        <tr>
                            <td colspan="2">إجمالي السعر</td>
                            <td colspan="4" class="end">{{ $money($stocks->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
