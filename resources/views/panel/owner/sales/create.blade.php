@extends('layouts.app')

@section('title', 'بيع مصيد — '.$trip->trip_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'coins'])</div>
            <div>
                <h1>بيع مصيد لزبون</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.trips.show', $trip) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) الرحلة</a>
        </div>
    </div>

    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if (! $trip->canSell())
        <div class="card"><p class="card-sub">مصيد هذه الرحلة غير متاح للبيع — لم يكتمل العد أو نفد.</p></div>
    @else
        <form method="POST" action="{{ route('panel.owner.sales.store') }}" class="card" style="display:flex;flex-direction:column;gap:1rem">
            @csrf
            <input type="hidden" name="trip_id" value="{{ $trip->id }}">

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
                <label class="field"><span>حالة الدفع</span>
                    <select class="select" name="payment_status_id">
                        <option value="">— تُحسب من المدفوع —</option>
                        @foreach ($paymentStatuses as $s)<option value="{{ $s->id }}" @selected((string) old('payment_status_id') === (string) $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </label>
            </div>

            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأصناف'])
            @include('panel.owner.partials.lines-editor', ['available' => $available, 'priced' => true])

            <div class="form-grid cols-3">
                <label class="field"><span>الخصم (ر.س)</span><input class="input num" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" dir="ltr" oninput="sumLines()"></label>
                <label class="field"><span>المدفوع (ر.س)</span><input class="input num" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount') }}" dir="ltr" placeholder="اتركه فارغًا = مدفوع بالكامل"></label>
                <label class="field"><span>الإجمالي بعد الخصم</span><input class="input num" id="grandTotal" value="0" dir="ltr" readonly></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea></label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <a href="{{ route('panel.owner.trips.show', $trip) }}" class="btn btn-outline">إلغاء</a>
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'check-check']) إنهاء المبيعات</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
<script>
    function onLinesTotal(money) {
        const discount = parseFloat(document.querySelector('[name=discount]')?.value) || 0;
        document.getElementById('grandTotal').value = Math.max(money - discount, 0).toFixed(2);
    }
    if (typeof sumLines === 'function') sumLines();
</script>
@endpush
