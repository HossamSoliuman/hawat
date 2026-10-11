@extends('layouts.app')

@section('title', 'سجل الصيد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'fish'])</div>
            <div>
                <h1>سجل الصيد</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-3" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المصيد المعلن', 'value' => number_format($totals['captain_kg'], 1), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المعدود', 'value' => number_format($totals['counted_kg'], 1), 'unit' => 'كجم', 'icon' => 'clipboard', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الأصناف', 'value' => number_format($totals['species']), 'icon' => 'list-checks', 'tone' => 'info'])
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card span-2">
            @include('partials.section-head', ['icon' => 'history', 'title' => 'السطور'])
            <form method="GET" class="filter-bar" style="margin-bottom:.9rem">
                <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الرحلة أو الصنف..."></label>
                <button class="btn btn-primary">بحث</button>
                <a href="{{ route('panel.captain.catch-log') }}" class="btn btn-outline">إعادة تعيين</a>
            </form>
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>الرحلة</th><th>القارب</th><th>الصنف</th><th>وزنك</th><th>المعدود</th><th>ملاحظات</th></tr></thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="num" style="font-size:.74rem">{{ $entry->recorded_at?->format('Y-m-d') ?? '—' }}</td>
                                <td><a href="{{ route('panel.captain.trips.show', $entry->trip_id) }}" class="num" style="font-weight:700">{{ $entry->trip?->trip_number }}</a></td>
                                <td>{{ $entry->trip?->boat?->name ?? '—' }}</td>
                                <td style="font-weight:600">{{ $entry->species?->name_ar }}</td>
                                <td class="num">{{ $entry->captain_kg !== null ? number_format($entry->captain_kg, 1) : '—' }}</td>
                                <td class="num">{{ $entry->counted_kg !== null ? number_format($entry->counted_kg, 1) : '—' }}</td>
                                <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $entry->captain_notes ?? $entry->counter_notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا سطور بعد — تُسجَّل عند إرسال مخرجات رحلة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $entries])
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'ملخص بالصنف'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الصنف</th><th>رحلات</th><th style="text-align:left">معلن / معدود</th></tr></thead>
                    <tbody>
                        @forelse ($summary as $row)
                            <tr>
                                <td style="font-weight:600">{{ $row->species }}<div dir="ltr" style="text-align:right;font-size:.66rem;color:hsl(var(--muted-foreground))">{{ $row->name_sci }}</div></td>
                                <td class="num">{{ $row->trips }}</td>
                                <td class="num" style="text-align:left">{{ number_format($row->captain_kg, 1) }} / {{ number_format($row->counted_kg, 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا مصيد بعد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
