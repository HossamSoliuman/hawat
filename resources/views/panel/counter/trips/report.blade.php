@extends('layouts.app')

@section('title', 'تقرير الرحلة '.$trip->trip_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1>تقرير مفصّل للرحلة <span class="num">{{ $trip->trip_number }}</span></h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.counter.trips.show', $trip) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'scale']) شاشة العد</a>
            <button type="button" class="btn btn-outline" onclick="window.print()">@include('partials.icon', ['name' => 'printer']) طباعة</button>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
            @include('panel.owner.partials.trip-badges', ['trip' => $trip])
            <span class="badge">المعلن {{ $totals['captain_kg'] !== null ? number_format($totals['captain_kg'], 1) : '—' }} كجم</span>
            <span class="badge">المعدود {{ $totals['counted_kg'] !== null ? number_format($totals['counted_kg'], 1) : '—' }} كجم</span>
            <span class="badge {{ (float) $totals['diff_kg'] < 0 ? 'badge-danger' : 'badge-ok' }}">الفرق {{ $totals['diff_kg'] !== null ? number_format($totals['diff_kg'], 1) : '—' }} كجم</span>
            @if ($totals['approved_kg'] !== null)
                <span class="badge badge-ok">المعتمد {{ number_format($totals['approved_kg'], 1) }} كجم</span>
            @endif
        </div>
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        @foreach ($sections as $title => $rows)
            <div class="card">
                @include('partials.section-head', ['icon' => 'clipboard', 'title' => $title])
                <dl class="detail-list">
                    @foreach ($rows as $label => $value)
                        <dt>{{ $label }}</dt><dd>{{ ($value === null || $value === '') ? '—' : $value }}</dd>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'fish', 'title' => 'تفاصيل المصيد'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead>
                    <tr><th>الصنف</th><th>الوزن الكلي</th><th>وزن القبطان</th><th>المعدود</th><th>فُحص</th><th>ملاحظات القبطان</th><th>ملاحظات العدّاد</th><th>أُضيف بواسطة</th><th>تاريخ الإضافة</th></tr>
                </thead>
                <tbody>
                    @forelse ($catch as $line)
                        <tr>
                            <td style="font-weight:600">{{ $line['species'] }}<div dir="ltr" style="text-align:right;font-size:.66rem;color:hsl(var(--muted-foreground))">{{ $line['name_sci'] }}</div></td>
                            <td class="num">{{ number_format($line['total_kg'], 1) }}</td>
                            <td class="num">{{ $line['captain_kg'] !== null ? number_format($line['captain_kg'], 1) : '—' }}</td>
                            <td class="num">{{ $line['counted_kg'] !== null ? number_format($line['counted_kg'], 1) : '—' }}</td>
                            <td>{!! $line['verified'] ? '<span class="badge badge-ok">نعم</span>' : '<span class="badge">لا</span>' !!}</td>
                            <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $line['captain_notes'] ?? '—' }}</td>
                            <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $line['counter_notes'] ?? '—' }}</td>
                            <td style="font-size:.74rem">{{ $line['added_by'] ?? '—' }}</td>
                            <td class="num" style="font-size:.74rem">{{ $line['recorded_at'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا سطور مصيد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
