@extends('layouts.app')

@section('title', 'إغلاق '.$closing->period_label)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'lock'])</div>
            <div>
                <h1>{{ $closing->period_label }} — مُغلق</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) إغلاق الشهر</a>
            <a href="{{ route('panel.owner.expenses', ['from' => $closing->period_start->toDateString(), 'to' => $closing->period_end->toDateString()]) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'receipt']) سندات الشهر</a>
            <a href="{{ route('panel.owner.month-closings.print', $closing->id) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
            @if ($isLatest)
                <form method="POST" action="{{ route('panel.owner.month-closings.reopen', $closing->id) }}"
                    onsubmit="return confirm('إعادة فتح {{ $closing->period_label }}؟ تُحذف لقطة الإغلاق ويُعاد حساب الشهر ومسيراته غير المسدَّدة.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline">@include('partials.icon', ['name' => 'lock-open']) إعادة فتح</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if ($closing->notes)
        <div class="card" style="margin-bottom:1.25rem;font-size:.82rem"><b>ملاحظة:</b> {{ $closing->notes }}</div>
    @endif

    @include('panel.owner.month-closings.partials.figures', ['data' => $data])
@endsection
