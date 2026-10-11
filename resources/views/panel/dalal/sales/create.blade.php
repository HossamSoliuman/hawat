@extends('layouts.app')

@section('title', 'إضافة عملية بيع')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'coins'])</div>
            <div>
                <h1>إضافة عملية بيع</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.dalal.sales') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) المبيعات</a>
        </div>
    </div>

    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if ($species->isEmpty())
        <div class="card"><p class="card-sub">لا مخزون متاح للبيع — يصلك المصيد حين يرسله مالك إليك.</p></div>
    @else
        <form method="POST" action="{{ route('panel.dalal.sales.store') }}" class="card" style="display:flex;flex-direction:column;gap:1rem">
            @csrf

            @include('partials.section-head', ['icon' => 'handshake', 'title' => 'الزبون والدفع'])
            <div class="form-grid cols-3">
                <label class="field"><span>الزبون</span>
                    <select class="select" name="customer_id">
                        <option value="">— بلا زبون محدد —</option>
                        @foreach ($customers as $c)<option value="{{ $c->id }}" @selected((string) old('customer_id') === (string) $c->id)>{{ $c->name }}@if ($c->phone) — {{ $c->phone }}@endif</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>طريقة الدفع</span>
                    <select class="select" name="payment_method_id">
                        <option value="">—</option>
                        @foreach ($paymentMethods as $m)<option value="{{ $m->id }}" @selected((string) old('payment_method_id') === (string) $m->id)>{{ $m->name }}</option>@endforeach
                    </select>
                </label>
            </div>

            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأصناف'])
            <table class="data-table lines-table" style="margin-bottom:.75rem">
                <thead>
                    <tr>
                        <th>نوع السمك</th>
                        <th>الدفعة (المالك — الرحلة)</th>
                        <th style="width:8rem">الوزن (كجم)</th>
                        <th style="width:8rem">سعر الكيلو</th>
                        <th style="width:8rem;text-align:left">الإجمالي</th>
                        <th style="width:2.5rem"></th>
                    </tr>
                </thead>
                <tbody id="lines"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:left;font-size:.78rem">
                            <button type="button" class="btn btn-outline" onclick="addLine()">@include('partials.icon', ['name' => 'plus']) إضافة سطر</button>
                        </td>
                        <td class="num" style="font-weight:700;text-align:left" id="linesTotal">0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="form-grid cols-3">
                <label class="field"><span>الخصم (ر.س)</span><input class="input num" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" dir="ltr" oninput="sumLines()"></label>
                <label class="field"><span>المدفوع (ر.س)</span><input class="input num" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount') }}" dir="ltr" placeholder="اتركه فارغًا = مدفوع بالكامل"></label>
                <label class="field"><span>الإجمالي بعد الخصم</span><input class="input num" id="grandTotal" value="0" dir="ltr" readonly></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea></label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <a href="{{ route('panel.dalal.sales') }}" class="btn btn-outline">إلغاء</a>
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'check-check']) إنهاء عملية البيع</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
<script>
    const stockSpecies = @json($species);
    const stockLots = @json($lots);
    let lineIndex = 0;

    function speciesOptions(selected) {
        return '<option value="">— اختر —</option>' + stockSpecies
            .map(s => `<option value="${s.species_id}" data-max="${s.available_kg}" ${String(selected) === String(s.species_id) ? 'selected' : ''}>${s.species} — المتاح ${s.available_kg} كجم</option>`)
            .join('');
    }

    function lotOptions(speciesId, selected) {
        return '<option value="">الأقدم أوّلًا</option>' + stockLots
            .filter(l => String(l.species_id) === String(speciesId))
            .map(l => `<option value="${l.trip_id ?? ''}" data-max="${l.available_kg}" ${String(selected) === String(l.trip_id) ? 'selected' : ''}>${l.owner ?? '—'} — ${l.trip_number ?? '—'} (${l.available_kg} كجم)</option>`)
            .join('');
    }

    function addLine(line = {}) {
        const i = lineIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select class="select" name="items[${i}][species_id]" required onchange="onSpecies(this)">${speciesOptions(line.species_id)}</select></td>
            <td><select class="select" name="items[${i}][trip_id]" onchange="syncMax(this.closest('tr'))">${lotOptions(line.species_id, line.trip_id)}</select></td>
            <td><input class="input num" type="number" step="0.01" min="0.01" name="items[${i}][weight_kg]" value="${line.weight_kg ?? ''}" dir="ltr" required oninput="sumLines()"></td>
            <td><input class="input num" type="number" step="0.01" min="0.01" name="items[${i}][price_per_kg]" value="${line.price_per_kg ?? ''}" dir="ltr" required oninput="sumLines()"></td>
            <td class="num line-total" style="text-align:left">0</td>
            <td><button type="button" class="icon-action danger" onclick="this.closest('tr').remove(); sumLines()">×</button></td>`;
        document.getElementById('lines').appendChild(tr);
        syncMax(tr);
        sumLines();
    }

    function onSpecies(select) {
        const tr = select.closest('tr');
        tr.querySelector('select[name$="[trip_id]"]').innerHTML = lotOptions(select.value);
        syncMax(tr);
    }

    // الحد الأعلى للوزن = متاح الدفعة المختارة، وإلا متاح الصنف كله.
    function syncMax(tr) {
        const lot = tr.querySelector('select[name$="[trip_id]"]');
        const species = tr.querySelector('select[name$="[species_id]"]');
        const max = (lot.value ? lot.options[lot.selectedIndex] : species.options[species.selectedIndex])?.dataset.max;
        const weight = tr.querySelector('input[name$="[weight_kg]"]');
        if (max) weight.max = max; else weight.removeAttribute('max');
    }

    function sumLines() {
        let money = 0;
        document.querySelectorAll('#lines tr').forEach(tr => {
            const w = parseFloat(tr.querySelector('input[name$="[weight_kg]"]')?.value) || 0;
            const p = parseFloat(tr.querySelector('input[name$="[price_per_kg]"]')?.value) || 0;
            money += w * p;
            tr.querySelector('.line-total').textContent = (w * p).toFixed(2);
        });
        document.getElementById('linesTotal').textContent = money.toFixed(2) + ' ر.س';
        const discount = parseFloat(document.querySelector('[name=discount]')?.value) || 0;
        document.getElementById('grandTotal').value = Math.max(money - discount, 0).toFixed(2);
    }

    if (document.getElementById('lines')) {
        @if (old('items'))
            @foreach (old('items') as $line) addLine(@json($line)); @endforeach
        @else
            addLine();
        @endif
    }
</script>
@endpush
