@extends('layouts.app')

@section('title', 'الرحلة '.$trip->trip_number)

@php
    use App\Models\Trip;

    $availableTotal = $available->sum();
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'route'])</div>
            <div>
                <h1 class="num">{{ $trip->trip_number }}</h1>
            </div>
        </div>
        <div class="actions">
            @if ($trip->canSell())
                <a href="{{ route('panel.owner.sales.create', ['trip' => $trip->id]) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'coins']) بيع مصيد لزبون</a>
                <a href="{{ route('panel.owner.consignments.create', ['trip' => $trip->id]) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'send']) إرسال مصيد للدلال</a>
            @endif
            @if ($trip->status === Trip::SCHEDULED)
                <form method="POST" action="{{ route('panel.owner.trips.start', $trip) }}" onsubmit="return confirm('بدء الرحلة الآن؟ يصير القارب في البحر.')">
                    @csrf
                    <button class="btn btn-primary">@include('partials.icon', ['name' => 'zap']) ابدأ الرحلة</button>
                </form>
            @endif
            @if (in_array($trip->status, [Trip::SCHEDULED, Trip::AT_SEA], true))
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('cancelDrawer', true)">@include('partials.icon', ['name' => 'x-circle']) إلغاء الرحلة</button>
            @endif
            <a href="{{ route('panel.owner.trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) كل الرحلات</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-bottom:1.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
            <div style="display:flex;gap:.4rem;align-items:center">@include('panel.owner.partials.trip-badges', ['trip' => $trip])</div>
            @if ($trip->isCancelled())
                <span style="font-size:.78rem;color:var(--st-critical)">سبب الإلغاء: {{ $trip->cancel_reason }}</span>
            @elseif ($trip->canSell())
                <span class="num" style="font-size:.78rem">المتاح للبيع: <strong>{{ number_format($availableTotal, 1) }}</strong> كجم</span>
            @endif
        </div>
        @include('panel.owner.partials.trip-progress', ['trip' => $trip])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'بيانات الرحلة'])
            <dl class="detail-list">
                <dt>نوع التصريح</dt><dd>{{ $trip->tripType?->name ?? '—' }}</dd>
                <dt>الرخصة</dt><dd class="num">{{ $trip->license_number ?? '—' }}</dd>
                <dt>أداة الصيد</dt><dd>{{ $trip->gear_type ?? '—' }}</dd>
                <dt>عدد الطاقم</dt><dd class="num">{{ $trip->crew_count }}</dd>
                <dt>المدة المخططة</dt><dd class="num">{{ $trip->planned_days ? $trip->planned_days.' يوم' : '—' }}</dd>
                <dt>ميناء المغادرة / العودة</dt><dd>{{ $trip->departurePort?->name }} / {{ $trip->returnPort?->name ?? $trip->departurePort?->name }}</dd>
                <dt>الانطلاق</dt><dd class="num">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</dd>
                <dt>العودة</dt><dd class="num">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</dd>
                <dt>المدة الفعلية</dt><dd class="num">{{ $trip->duration_hours !== null ? number_format($trip->duration_hours, 1).' ساعة' : '—' }}</dd>
                <dt>العدّاد</dt><dd>{{ $trip->counter?->name ?? $trip->statistics_officer ?? '—' }}</dd>
                <dt>المعلن / المعدود / الفرق</dt><dd class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1) : '—' }} / {{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg, 1) : '—' }} / {{ $trip->diff_kg !== null ? number_format($trip->diff_kg, 1) : '—' }}</dd>
                <dt>ملاحظات</dt><dd>{{ $trip->notes ?? '—' }}</dd>
            </dl>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'مخرجات المصيد'])
            @if ($trip->catchRecords->isNotEmpty())
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>الصنف</th><th>المعلن</th><th>المعدود</th><th>المتاح</th><th>ملاحظات</th></tr></thead>
                        <tbody>
                            @foreach ($trip->catchRecords as $record)
                                <tr>
                                    <td style="font-weight:600">{{ $record->species?->name_ar }}</td>
                                    <td class="num">{{ number_format($record->captain_kg ?? $record->quantity_kg, 1) }}</td>
                                    <td class="num">{{ $record->counted_kg !== null ? number_format($record->counted_kg, 1) : '—' }}</td>
                                    <td class="num">{{ $trip->sale_status !== Trip::SALE_NOT_STARTED ? number_format($available[$record->species_id] ?? 0, 1) : '—' }}</td>
                                    <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $record->captain_notes ?? $record->counter_notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($trip->status === Trip::AT_SEA)
                <p class="card-sub" style="margin-bottom:.75rem">الرحلة في البحر — سجّل مخرجاتها هنا عند عودة القارب.</p>
                <form method="POST" action="{{ route('panel.owner.trips.catch', $trip) }}" id="catchForm">
                    @csrf
                    <table class="data-table lines-table" style="margin-bottom:.75rem">
                        <thead><tr><th>نوع السمك</th><th style="width:8rem">الوزن (كجم)</th><th>ملاحظات</th><th style="width:2.5rem"></th></tr></thead>
                        <tbody id="catchLines"></tbody>
                    </table>
                    <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:center">
                        <button type="button" class="btn btn-outline" onclick="addCatchLine()">@include('partials.icon', ['name' => 'plus']) إضافة صنف</button>
                        <span class="num" style="font-size:.78rem">المجموع: <strong id="catchTotal">0</strong> كجم</span>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('تأكيد المخرجات وإنهاء الرحلة؟')">@include('partials.icon', ['name' => 'check-check']) تأكيد المخرجات وإنهاء الرحلة</button>
                    </div>
                </form>
            @else
                <p class="card-sub">لا مخرجات بعد.</p>
            @endif
        </div>
    </div>

    @if ($trip->sales->isNotEmpty() || $trip->consignments->isNotEmpty())
        <div class="grid-2">
            <div class="card">
                @include('partials.section-head', ['icon' => 'coins', 'title' => 'مبيعات الرحلة'])
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>الفاتورة</th><th>الزبون</th><th>الوزن</th><th style="text-align:left">الإجمالي</th></tr></thead>
                        <tbody>
                            @forelse ($trip->sales as $sale)
                                <tr>
                                    <td><a href="{{ route('panel.owner.sales.show', $sale) }}" class="num" style="font-weight:700">{{ $sale->invoice_number }}</a></td>
                                    <td>{{ $sale->customer?->name ?? '—' }}</td>
                                    <td class="num">{{ number_format($sale->items->sum('weight_kg'), 1) }} كجم</td>
                                    <td class="num" style="text-align:left">{{ number_format($sale->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="padding:1rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                @include('partials.section-head', ['icon' => 'send', 'title' => 'المرسل للدلالين'])
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>الشحنة</th><th>الدلال</th><th>الأصناف</th><th style="text-align:left">الوزن</th></tr></thead>
                        <tbody>
                            @forelse ($trip->consignments as $c)
                                <tr>
                                    <td><a href="{{ route('panel.owner.consignments.show', $c) }}" class="num" style="font-weight:700">{{ $c->consignment_number }}</a></td>
                                    <td>{{ $c->dalal?->name }}</td>
                                    <td class="num">{{ $c->items->count() }}</td>
                                    <td class="num" style="text-align:left">{{ number_format($c->total_kg, 1) }} كجم</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="padding:1rem;text-align:center;color:hsl(var(--muted-foreground))">لا إرسالات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="drawer-overlay" id="cancelDrawer-overlay" onclick="toggleDrawer('cancelDrawer', false)"></div>
    <div class="drawer" id="cancelDrawer">
        <div class="drawer-head">
            <h3>إلغاء الرحلة {{ $trip->trip_number }}</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('cancelDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('panel.owner.trips.cancel', $trip) }}" class="drawer-body">
            @csrf
            <label class="field"><span>سبب الإلغاء *</span><textarea class="input" name="reason" rows="3" required>{{ old('reason') }}</textarea></label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('cancelDrawer', false)">رجوع</button>
                <button type="submit" class="btn btn-primary">تأكيد الإلغاء</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const speciesOptions = @json($species->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar]));
    let catchIndex = 0;

    function addCatchLine(line = {}) {
        const tbody = document.getElementById('catchLines');
        if (!tbody) return;
        const i = catchIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select class="select" name="items[${i}][species_id]" required><option value="">— اختر —</option>${speciesOptions.map(s => `<option value="${s.id}" ${String(line.species_id) === String(s.id) ? 'selected' : ''}>${s.name}</option>`).join('')}</select></td>
            <td><input class="input num" type="number" step="0.01" min="0.01" name="items[${i}][weight_kg]" value="${line.weight_kg ?? ''}" dir="ltr" required oninput="sumCatch()"></td>
            <td><input class="input" name="items[${i}][notes]" value="${line.notes ?? ''}"></td>
            <td><button type="button" class="icon-action danger" onclick="this.closest('tr').remove(); sumCatch()">×</button></td>`;
        tbody.appendChild(tr);
        sumCatch();
    }

    function sumCatch() {
        const total = [...document.querySelectorAll('#catchLines input[name$="[weight_kg]"]')].reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
        const out = document.getElementById('catchTotal');
        if (out) out.textContent = total.toFixed(1);
    }

    @if (old('items'))
        @foreach (old('items') as $line) addCatchLine(@json($line)); @endforeach
    @elseif ($trip->status === Trip::AT_SEA)
        addCatchLine();
    @endif

    @if ($errors->has('reason'))
        toggleDrawer('cancelDrawer', true);
    @endif
</script>
@endpush
