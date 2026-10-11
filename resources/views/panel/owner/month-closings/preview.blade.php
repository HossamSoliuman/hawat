@extends('layouts.app')

@section('title', 'معاينة إغلاق '.$data['label'])

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'eye'])</div>
            <div>
                <h1>معاينة إغلاق {{ $data['label'] }}</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) إغلاق الشهر</a>
        </div>
    </div>

    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if ($data['warnings'] !== [])
        <div class="card" style="margin-bottom:1.25rem;border-inline-start:3px solid var(--st-warn)">
            @include('partials.section-head', ['icon' => 'alert-triangle', 'title' => 'قبل الإغلاق'])
            <ul style="margin:0;padding-inline-start:1.1rem;font-size:.82rem;line-height:1.9">
                @foreach ($data['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach
            </ul>
        </div>
    @endif

    @include('panel.owner.month-closings.partials.figures', ['data' => $data])

    <form method="POST" action="{{ route('panel.owner.month-closings.store') }}" class="card" style="margin-top:1.25rem"
        onsubmit="return confirm('إغلاق {{ $data['label'] }}؟ تُقفل مصروفاته ومسيراته حتى يُعاد فتحه.')">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <label class="field"><span>ملاحظة (اختيارية)</span><input class="input" name="notes" maxlength="1000" value="{{ old('notes') }}"></label>
        <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
            <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline">إلغاء</a>
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'lock']) إغلاق {{ $data['label'] }}</button>
        </div>
    </form>
@endsection
