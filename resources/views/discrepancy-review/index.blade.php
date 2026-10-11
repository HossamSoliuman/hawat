@extends('layouts.app')

@section('title', 'مراجعة الفروقات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'alert-triangle'])</div>
            <div>
                <h1>مراجعة الفروقات</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'رحلات بها فروقات', 'value' => $stats['total'], 'icon' => 'alert-triangle', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'فروقات ≥ 50 كجم', 'value' => $stats['high'], 'icon' => 'ban', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => $stats['pending'], 'icon' => 'clock', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'صافي الفروقات', 'value' => number_format($stats['net'], 1), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'متوسط الفرق', 'value' => number_format($stats['avg'], 1), 'unit' => 'كجم', 'icon' => 'trending-down', 'tone' => 'warning'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الحد الأدنى للفرق (كجم)</span><input class="input" type="number" min="0" name="threshold" value="{{ $threshold ?: '' }}" placeholder="0"></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('discrepancy-review') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الرحلة</th><th>القارب</th><th>الميناء</th><th>إدخال الكابتن</th><th>الوزن الفعلي</th><th>الفرق</th><th>نسبة الفرق</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    @php
                        $diff = (float) $trip->diff_kg;
                        $pct = (float) $trip->captain_input_kg ? round(abs($diff) / (float) $trip->captain_input_kg * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $trip->trip_number }}</td>
                        <td>{{ $trip->boat?->name ?? '—' }}</td>
                        <td>{{ $trip->departurePort?->name ?? '—' }}</td>
                        <td>{{ number_format($trip->captain_input_kg) }}</td>
                        <td>{{ number_format($trip->actual_weight_kg) }}</td>
                        <td style="font-weight:700;color:{{ $diff < 0 ? '#e11d48' : '#047857' }}">{{ number_format($diff, 1) }}</td>
                        <td><span class="badge {{ $pct >= 10 ? 'badge-danger' : ($pct >= 5 ? 'badge-warn' : 'badge-info') }}">{{ $pct }}%</span></td>
                        <td><span class="badge {{ $trip->status === 'معتمدة' ? 'badge-ok' : 'badge-warn' }}">{{ $trip->status }}</span></td>
                        <td>
                            @if ($trip->status !== 'معتمدة')
                                <button type="button" class="icon-action" title="معالجة الفرق" onclick='openResolveForm({!! json_encode($trip->only(["id", "trip_number", "actual_weight_kg", "diff_kg", "notes"]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'check-circle'])</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد فروقات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="resolveDrawer-overlay" onclick="toggleDrawer('resolveDrawer', false)"></div>
    <div class="drawer" id="resolveDrawer">
        <div class="drawer-head">
            <div>
                <h3>معالجة الفرق واعتماد الكمية</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))" id="resolveSub"></p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('resolveDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="resolveForm" class="drawer-body">
            @csrf
            <label class="field"><span>الكمية المعتمدة (كجم) *</span><input class="input" type="number" step="0.01" min="0" name="approved_kg" id="rv-kg" required></label>
            <label class="field"><span>سبب الفرق والقرار *</span><textarea class="input" rows="4" name="notes" id="rv-notes" required></textarea></label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('resolveDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">اعتماد بعد المعالجة</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const resolveBase = @json(url('discrepancy-review'));

    function openResolveForm(trip) {
        document.getElementById('resolveForm').action = resolveBase + '/' + trip.id + '/resolve';
        document.getElementById('resolveSub').textContent = 'الرحلة ' + trip.trip_number + ' — الفرق: ' + trip.diff_kg + ' كجم';
        document.getElementById('rv-kg').value = trip.actual_weight_kg ?? '';
        document.getElementById('rv-notes').value = trip.notes ?? '';
        toggleDrawer('resolveDrawer', true);
    }
</script>
@endpush