@extends('layouts.app')

@section('title', 'الإرسال للدلال')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'send'])</div>
            <div>
                <h1>الإرسال للدلال</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.trips', ['view' => 'for-sale']) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إرسال من رحلة</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-2" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الشحنات', 'value' => number_format($rows->total()), 'icon' => 'send', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المرسل', 'value' => number_format($totalKg, 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الدلال</span>
            <select class="select" name="dalal" onchange="this.form.submit()">
                <option value="">كل الدلالين</option>
                @foreach ($dalals as $d)<option value="{{ $d->id }}" @selected((string) request('dalal') === (string) $d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.consignments') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>الشحنة</th><th>الدلال</th><th>الرحلة</th><th>الأصناف</th><th>الوزن</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('panel.owner.consignments.show', $row) }}" class="num" style="font-weight:700">{{ $row->consignment_number }}</a></td>
                        <td>{{ $row->dalal?->name }}</td>
                        <td class="num">{{ $row->trip?->trip_number ?? '—' }}</td>
                        <td style="font-size:.74rem">{{ $row->items->map(fn ($i) => $i->species?->name_ar)->join('، ') }}</td>
                        <td class="num">{{ number_format($row->total_kg, 1) }} كجم</td>
                        <td><span class="badge {{ $row->status === 'مستلمة' ? 'badge-ok' : ($row->status === 'ملغاة' ? 'badge-danger' : 'badge-info') }}">{{ $row->status }}</span></td>
                        <td class="num" style="font-size:.74rem">{{ $row->sent_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('panel.owner.consignments.show', $row) }}" class="icon-action">@include('partials.icon', ['name' => 'chevron-left'])</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا شحنات بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])
@endsection
