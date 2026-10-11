@extends('layouts.app')

@section('title', 'طلبات المالكين')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'handshake'])</div>
            <div>
                <h1>طلبات المالكين</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <nav class="seg" aria-label="الحالة" style="margin-bottom:1.25rem">
        <a href="{{ route('panel.dalal.requests') }}" class="{{ $status === null ? 'is-active' : '' }}">الكل <span class="num">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\DalalPartnership::STATUS_LABELS as $key => $label)
            <a href="{{ route('panel.dalal.requests', ['status' => $key]) }}" class="{{ $status === $key ? 'is-active' : '' }}">{{ $label }} <span class="num">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>اسم المالك</th><th>معلومات الاتصال</th><th>العمولة المقترحة</th><th>الأجور</th><th>الرسالة</th><th>تاريخ الطلب</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row->owner?->name }}</td>
                        <td><span class="num" dir="ltr">{{ $row->owner?->phone }}</span>@if ($row->owner?->email)<br><span dir="ltr" style="font-size:.72rem">{{ $row->owner->email }}</span>@endif</td>
                        <td class="num">{{ rtrim(rtrim(number_format($row->commission_pct, 2), '0'), '.') }}%</td>
                        <td class="num">{{ rtrim(rtrim(number_format($row->wage_pct, 2), '0'), '.') }}%</td>
                        <td style="max-width:18rem;font-size:.78rem">{{ $row->message ?? '—' }}@if ($row->response_note)<br><span style="color:hsl(var(--muted-foreground))">ردّك: {{ $row->response_note }}</span>@endif</td>
                        <td class="num">{{ $row->created_at?->format('Y-m-d') }}</td>
                        <td><span class="badge {{ ['pending' => 'badge-warn', 'accepted' => 'badge-ok', 'rejected' => 'badge-danger'][$row->status] ?? '' }}">{{ $row->status_label }}</span></td>
                        <td>
                            @if ($row->isPending())
                                <div style="display:flex;gap:.35rem;justify-content:flex-end;align-items:center">
                                    <form method="POST" action="{{ route('panel.dalal.requests.accept', $row) }}">
                                        @csrf
                                        <button class="btn btn-primary" style="padding:.3rem .7rem">@include('partials.icon', ['name' => 'check-check']) قبول</button>
                                    </form>
                                    <form method="POST" action="{{ route('panel.dalal.requests.reject', $row) }}" style="display:flex;gap:.35rem">
                                        @csrf
                                        <input class="input" name="response_note" placeholder="سبب الرفض (اختياري)" style="width:10rem">
                                        <button class="btn btn-outline" style="padding:.3rem .7rem">رفض</button>
                                    </form>
                                </div>
                            @else
                                <span class="num" style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $row->responded_at?->format('Y-m-d') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد طلبات — يرسلها المالك من صفحة «الدلالون» في بوابته</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rows])
@endsection
