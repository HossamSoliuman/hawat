@extends('layouts.app')

@section('title', 'إغلاق الشهر')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'lock'])</div>
            <div>
                <h1>إغلاق الشهر</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'calendar', 'title' => 'الشهر التالي'])
        @if ($next === null)
            <p style="margin:0;font-size:.85rem">آخر شهر مُغلق هو {{ $rows->first()?->period_label }} — الشهر التالي لم ينتهِ بعد.</p>
        @else
            <form method="GET" action="{{ route('panel.owner.month-closings.preview') }}" class="filter-bar" style="margin:0">
                <label class="field"><span>الشهر</span>
                    <input class="input" name="period" type="month" dir="ltr" required value="{{ old('period', $next->format('Y-m')) }}"
                        max="{{ now()->startOfMonth()->subMonth()->format('Y-m') }}" @unless ($isFirst) readonly @endunless>
                </label>
                <button class="btn btn-primary">@include('partials.icon', ['name' => 'eye']) معاينة قبل الإغلاق</button>
            </form>
            <p style="margin:.5rem 0 0;font-size:.75rem;color:hsl(var(--muted-foreground))">
                @if ($isFirst)
                    أول إغلاق: اختر أي شهر انتهى، وبعده تُغلق الأشهر بالتسلسل.
                @else
                    الإغلاق بالتسلسل فينتقل مؤجَّل الإهلاك من كل شهر إلى الذي يليه.
                @endif
            </p>
        @endif
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الشهر</th><th>القوارب</th><th>الإيراد</th><th>المصروفات</th><th>الإهلاك المحمَّل</th><th>نصيب الطاقم</th><th>صافي المالك</th><th>المؤجَّل</th><th>أُغلق</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('panel.owner.month-closings.show', $row->id) }}" style="font-weight:700">{{ $row->period_label }}</a></td>
                        <td class="num">{{ $row->boats->count() }}</td>
                        <td class="num">{{ number_format($row->revenue, 2) }}</td>
                        <td class="num">{{ number_format($row->boat_expenses + $row->general_expenses, 2) }}</td>
                        <td class="num">{{ number_format($row->depreciation_charged + $row->general_depreciation, 2) }}</td>
                        <td class="num">{{ number_format($row->crew_pool, 2) }}</td>
                        <td class="num" style="font-weight:700;{{ $row->owner_net < 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($row->owner_net, 2) }}</td>
                        <td class="num">{{ number_format($row->depreciation_deferred, 2) }}</td>
                        <td class="num">{{ $row->closed_at->format('Y-m-d') }}</td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <a href="{{ route('panel.owner.month-closings.print', $row->id) }}" target="_blank" class="icon-action" title="طباعة">@include('partials.icon', ['name' => 'printer'])</a>
                                <a href="{{ route('panel.owner.month-closings.show', $row->id) }}" class="icon-action" title="فتح">@include('partials.icon', ['name' => 'arrow-left'])</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لم يُغلق أي شهر بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
