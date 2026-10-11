@extends('layouts.app')

@section('title', 'عدّ الرحلة '.$trip->trip_number)

@php
    use App\Models\Trip;

    $declared = (float) ($trip->captain_input_kg ?? 0);
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'scale'])</div>
            <div>
                <h1 class="num">{{ $trip->trip_number }}</h1>
            </div>
        </div>
        <div class="actions">
            @if ($trip->canReceive())
                <form method="POST" action="{{ route('panel.counter.trips.receive', $trip) }}" onsubmit="return confirm('استلام الرحلة وبدء عملية العد؟')">
                    @csrf
                    <button class="btn btn-primary">@include('partials.icon', ['name' => 'inbox']) استلام وبدء عملية العد</button>
                </form>
            @endif
            <a href="{{ route('panel.counter.trips.report', $trip) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-text']) تقرير مفصّل للرحلة</a>
            <a href="{{ route('panel.counter.trips') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) طابور العد</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-bottom:1.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
            <div style="display:flex;gap:.4rem;align-items:center">@include('panel.owner.partials.trip-badges', ['trip' => $trip])</div>
            @if ($trip->canReceive())
                <span style="font-size:.78rem;color:hsl(var(--muted-foreground))">الرحلة عادت بمصيدها — استلمها لتبدأ العد.</span>
            @elseif ($trip->status === Trip::COUNTING)
                <span style="font-size:.78rem;color:hsl(var(--muted-foreground))">استلمها {{ $trip->counter?->name ?? $trip->statistics_officer }} {{ $trip->received_at?->format('Y-m-d H:i') }} — افحص الكميات وأكّد.</span>
            @elseif ($trip->isCounted())
                <span style="font-size:.78rem;color:hsl(var(--muted-foreground))">اكتمل العد {{ $trip->counted_at?->format('Y-m-d H:i') }} — يمكن تصحيح الأوزان قبل اعتماد الوزارة.</span>
            @endif
        </div>
        @include('panel.owner.partials.trip-progress', ['trip' => $trip, 'steps' => ['بدأت الرحلة', 'بانتظار العدّاد', 'جارية العد', 'اكتمل العد']])
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'معلومات الرحلة'])
            <dl class="detail-list">
                <dt>القارب</dt><dd>{{ $trip->boat?->name ?? '—' }} <span class="num" style="color:hsl(var(--muted-foreground))">{{ $trip->boat?->boat_number }}</span></dd>
                <dt>المالك</dt><dd>{{ $trip->owner?->name ?? '—' }} <span dir="ltr" class="num" style="color:hsl(var(--muted-foreground))">{{ $trip->owner?->phone }}</span></dd>
                <dt>القبطان</dt><dd>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }} <span dir="ltr" class="num" style="color:hsl(var(--muted-foreground))">{{ $trip->captain?->phone }}</span></dd>
                <dt>نوع التصريح</dt><dd>{{ $trip->tripType?->name ?? '—' }}</dd>
                <dt>رقم الرخصة</dt><dd class="num">{{ $trip->license_number ?? '—' }}</dd>
                <dt>عدد الطاقم</dt><dd class="num">{{ $trip->crew_count }}</dd>
                <dt>ميناء المغادرة / العودة</dt><dd>{{ $trip->departurePort?->name ?? '—' }} / {{ $trip->returnPort?->name ?? $trip->departurePort?->name ?? '—' }}</dd>
                <dt>الانطلاق</dt><dd class="num">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</dd>
                <dt>العودة</dt><dd class="num">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</dd>
                <dt>المدة</dt><dd class="num">{{ $trip->duration_hours !== null ? number_format($trip->duration_hours, 1).' ساعة' : '—' }}</dd>
                <dt>المعلن / المعدود / الفرق</dt><dd class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1) : '—' }} / {{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg, 1) : '—' }} / {{ $trip->diff_kg !== null ? number_format($trip->diff_kg, 1) : '—' }}</dd>
            </dl>
        </div>

        <div class="card span-2">
            @include('partials.section-head', [
                'icon' => 'scale',
                'title' => $trip->canCount() && ! $trip->canReceive() ? 'عدّ المصيد' : 'مخرجات المصيد',
            ])

            @if ($trip->canReceive())
                <p class="card-sub" style="margin-bottom:.75rem">مخرجات الكابتن كما أرسلها. استلم الرحلة أولًا لتفتح حقول العد.</p>
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>الصنف</th><th>وزن الكابتن (كجم)</th><th>ملاحظات الكابتن</th></tr></thead>
                        <tbody>
                            @forelse ($trip->catchRecords as $record)
                                <tr>
                                    <td style="font-weight:600">{{ $record->species?->name_ar }}</td>
                                    <td class="num">{{ number_format($record->captain_kg ?? $record->quantity_kg, 1) }}</td>
                                    <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $record->captain_notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا سطور مصيد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($trip->canCount())
                <p class="card-sub" style="margin-bottom:.75rem">افحص كمية كل صنف: علّم ✓ لما طابق، وصحّح الوزن المعدود لما خالف مع ملاحظتك — وإن وجدت صنفًا لم يعلنه الكابتن فأضفه سطرًا جديدًا.</p>
                <form method="POST" action="{{ route('panel.counter.trips.count', $trip) }}" id="countForm">
                    @csrf
                    <table class="data-table lines-table" style="margin-bottom:.75rem">
                        <thead>
                            <tr>
                                <th style="width:3rem">فحص</th>
                                <th>الصنف</th>
                                <th style="width:7rem">وزن الكابتن</th>
                                <th style="width:9rem">الوزن المعدود (كجم)</th>
                                <th>ملاحظات العدّاد</th>
                                <th style="width:2.5rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trip->catchRecords as $i => $record)
                                <tr>
                                    <td style="text-align:center">
                                        <input type="hidden" name="items[{{ $i }}][verified]" value="0">
                                        <input type="checkbox" name="items[{{ $i }}][verified]" value="1" @checked($record->verified === null || $record->verified)>
                                    </td>
                                    <td style="font-weight:600">
                                        {{ $record->species?->name_ar }}
                                        <input type="hidden" name="items[{{ $i }}][species_id]" value="{{ $record->species_id }}">
                                        @if ($record->captain_kg === null)
                                            <span class="badge" style="margin-inline-start:.35rem">أضافه العدّاد</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ $record->captain_kg !== null ? number_format($record->captain_kg, 1) : '—' }}</td>
                                    <td><input class="input num" type="number" step="0.01" min="0" dir="ltr" required oninput="sumCount()"
                                        name="items[{{ $i }}][weight_kg]" value="{{ $record->counted_kg ?? $record->captain_kg ?? $record->quantity_kg }}"></td>
                                    <td><input class="input" name="items[{{ $i }}][notes]" value="{{ $record->counter_notes }}" placeholder="الكمية مطابقة / فرق في الوزن..."></td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tbody id="extraLines"></tbody>
                    </table>

                    <label class="field" style="margin-bottom:.75rem"><span>ملاحظات العد على الرحلة</span><input class="input" name="notes" value="{{ old('notes', $trip->notes) }}"></label>

                    <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <button type="button" class="btn btn-outline" onclick="addExtraLine()">@include('partials.icon', ['name' => 'plus']) إضافة صنف</button>
                        <span class="num" style="font-size:.78rem">المعدود: <strong id="countTotal">0</strong> كجم — الفرق عن المعلن: <strong id="countDiff">0</strong> كجم</span>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('تأكيد الكميات وإنهاء العد؟ يُفتح المصيد للبيع عند المالك.')">@include('partials.icon', ['name' => 'check-check']) تأكيد الكميات وإنهاء العد</button>
                    </div>
                </form>
            @else
                <div class="table-card" style="border:0">
                    <table class="data-table">
                        <thead><tr><th>الصنف</th><th>وزن الكابتن</th><th>المعدود</th><th>فُحص</th><th>ملاحظات العدّاد</th></tr></thead>
                        <tbody>
                            @forelse ($trip->catchRecords as $record)
                                <tr>
                                    <td style="font-weight:600">{{ $record->species?->name_ar }}</td>
                                    <td class="num">{{ $record->captain_kg !== null ? number_format($record->captain_kg, 1) : '—' }}</td>
                                    <td class="num">{{ $record->counted_kg !== null ? number_format($record->counted_kg, 1) : '—' }}</td>
                                    <td>{!! $record->verified ? '<span class="badge badge-ok">نعم</span>' : '<span class="badge">لا</span>' !!}</td>
                                    <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $record->counter_notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا سطور مصيد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const speciesOptions = @json($species->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar]));
    const declaredKg = @json($declared);
    let extraIndex = {{ $trip->catchRecords->count() }};

    function addExtraLine() {
        const tbody = document.getElementById('extraLines');
        if (!tbody) return;
        const i = extraIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center"><input type="hidden" name="items[${i}][verified]" value="0"><input type="checkbox" name="items[${i}][verified]" value="1" checked></td>
            <td><select class="select" name="items[${i}][species_id]" required><option value="">— اختر —</option>${speciesOptions.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}</select></td>
            <td class="num">—</td>
            <td><input class="input num" type="number" step="0.01" min="0" name="items[${i}][weight_kg]" dir="ltr" required oninput="sumCount()"></td>
            <td><input class="input" name="items[${i}][notes]" placeholder="صنف أُضيف عند العد"></td>
            <td><button type="button" class="icon-action danger" onclick="this.closest('tr').remove(); sumCount()">×</button></td>`;
        tbody.appendChild(tr);
        sumCount();
    }

    function sumCount() {
        const inputs = [...document.querySelectorAll('#countForm input[name$="[weight_kg]"]')];
        const total = inputs.reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
        const totalEl = document.getElementById('countTotal');
        const diffEl = document.getElementById('countDiff');
        if (totalEl) totalEl.textContent = total.toFixed(1);
        if (diffEl) {
            const diff = total - declaredKg;
            diffEl.textContent = diff.toFixed(1);
            diffEl.style.color = diff < 0 ? 'var(--st-critical)' : 'inherit';
        }
    }

    sumCount();
</script>
@endpush
