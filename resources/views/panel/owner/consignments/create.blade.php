@extends('layouts.app')

@section('title', 'إرسال للدلال — '.$trip->trip_number)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'send'])</div>
            <div>
                <h1>إضافة إلى مخزون الدلال</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.trips.show', $trip) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) الرحلة</a>
        </div>
    </div>

    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if (! $trip->canSell())
        <div class="card"><p class="card-sub">مصيد هذه الرحلة غير متاح — لم يكتمل العد أو نفد.</p></div>
    @elseif ($dalals->isEmpty())
        <div class="card"><p class="card-sub">لا دلالين مفعّلين بعد — تُنشئ إدارة حوات حسابات الدلالين.</p></div>
    @else
        <form method="POST" action="{{ route('panel.owner.consignments.store') }}" class="card" style="display:flex;flex-direction:column;gap:1rem">
            @csrf
            <input type="hidden" name="trip_id" value="{{ $trip->id }}">

            @include('partials.section-head', ['icon' => 'store', 'title' => 'الدلال'])
            <div class="form-grid cols-2">
                <label class="field"><span>اختر الدلال *</span>
                    <select class="select" name="dalal_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($dalals as $d)<option value="{{ $d->id }}" @selected((string) old('dalal_id') === (string) $d->id)>{{ $d->name }}@if ($d->phone) — {{ $d->phone }}@endif</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>ملاحظات</span><input class="input" name="notes" value="{{ old('notes') }}"></label>
            </div>

            @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأصناف'])
            @include('panel.owner.partials.lines-editor', ['available' => $available, 'priced' => false])

            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <a href="{{ route('panel.owner.trips.show', $trip) }}" class="btn btn-outline">إلغاء</a>
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'send']) إنهاء وإرسال للدلال</button>
            </div>
        </form>
    @endif
@endsection
