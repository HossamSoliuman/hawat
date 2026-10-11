@extends('layouts.app')

@section('title', 'طلبات التسجيل')

@php
    use App\Models\RegistrationRequest;

    $tabs = [
        RegistrationRequest::PENDING => ['label' => 'بانتظار المراجعة', 'icon' => 'inbox'],
        RegistrationRequest::APPROVED => ['label' => 'معتمدة', 'icon' => 'check-circle'],
        RegistrationRequest::REJECTED => ['label' => 'مرفوضة', 'icon' => 'x-circle'],
        'all' => ['label' => 'الكل', 'icon' => 'list-checks'],
    ];
    $badge = [
        RegistrationRequest::PENDING => 'badge-warn',
        RegistrationRequest::APPROVED => 'badge-ok',
        RegistrationRequest::REJECTED => 'badge-danger',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-check'])</div>
            <div>
                <h1>طلبات التسجيل</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'بانتظار المراجعة', 'value' => number_format($counts['pending']), 'icon' => 'inbox', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'معتمدة', 'value' => number_format($counts['approved']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'مرفوضة', 'value' => number_format($counts['rejected']), 'icon' => 'x-circle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'كل الطلبات', 'value' => number_format($counts['all']), 'icon' => 'users', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو المنشأة..."></label>
        <label class="field"><span>نوع الحساب</span>
            <select class="select" name="role" onchange="this.form.submit()">
                <option value="">مالك ودلال</option>
                @foreach ($roles as $role)<option value="{{ $role->key }}" @selected(request('role') === $role->key)>{{ $role->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.registrations', ['status' => $status]) }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <nav class="seg" style="margin-bottom:1.25rem" aria-label="حالة الطلب">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('panel.registrations', ['status' => $key] + request()->only('role', 'search')) }}" @class(['is-active' => $status === $key])>
                @include('partials.icon', ['name' => $tab['icon']]) {{ $tab['label'] }}
                <span class="num">({{ number_format($counts[$key]) }})</span>
            </a>
        @endforeach
    </nav>

    <div class="grid-2">
        @forelse ($requests as $item)
            <div class="card" style="display:flex;flex-direction:column;gap:.9rem">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem">
                    <div>
                        <div style="font-weight:700;font-size:1rem">{{ $item->name }}</div>
                        <div class="card-sub">{{ $item->role?->name }}{{ $item->business_name ? ' · '.$item->business_name : '' }}</div>
                    </div>
                    <span class="badge {{ $badge[$item->status] ?? 'badge-info' }}">{{ $item->status_label }}</span>
                </div>

                <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem .9rem;font-size:.8rem;margin:0">
                    <dt style="color:hsl(var(--muted-foreground))">الجوال</dt>
                    <dd style="margin:0" dir="ltr" class="num"><a href="tel:{{ $item->phone }}">{{ $item->phone }}</a></dd>
                    <dt style="color:hsl(var(--muted-foreground))">البريد</dt>
                    <dd style="margin:0" dir="ltr">{{ $item->email ?? '—' }}</dd>
                    <dt style="color:hsl(var(--muted-foreground))">المدينة</dt>
                    <dd style="margin:0">{{ $item->city ?? '—' }}</dd>
                    @if ($item->boats_count !== null)
                        <dt style="color:hsl(var(--muted-foreground))">عدد القوارب</dt>
                        <dd style="margin:0" class="num">{{ $item->boats_count }}</dd>
                    @endif
                    <dt style="color:hsl(var(--muted-foreground))">تاريخ الطلب</dt>
                    <dd style="margin:0" class="num">{{ $item->created_at->format('Y-m-d H:i') }} <span style="color:hsl(var(--muted-foreground))">({{ $item->created_at->diffForHumans() }})</span></dd>
                </dl>

                @if ($item->notes)
                    <p style="margin:0;font-size:.8rem;line-height:1.7;padding:.6rem .8rem;border:1px solid var(--hair);background:hsl(var(--muted) / .4);white-space:pre-line">{{ $item->notes }}</p>
                @endif

                @if ($item->isPending())
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end;align-items:flex-start;padding-top:.25rem;border-top:1px solid var(--hair)">
                        <details style="flex:1 1 220px">
                            <summary class="btn btn-outline" style="list-style:none;cursor:pointer">@include('partials.icon', ['name' => 'x-circle']) رفض</summary>
                            <form method="POST" action="{{ route('panel.registrations.reject', $item) }}" style="display:grid;gap:.5rem;margin-top:.6rem">
                                @csrf
                                <label class="field"><span>سبب الرفض (اختياري)</span><textarea class="input" name="rejection_reason" rows="2" maxlength="1000"></textarea></label>
                                <button type="submit" class="btn btn-outline" style="justify-self:start">تأكيد الرفض</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('panel.registrations.approve', $item) }}" onsubmit="return confirm('اعتماد طلب «{{ $item->name }}» وإنشاء حساب {{ $item->role?->name }} له؟')">
                            @csrf
                            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'check-circle']) اعتماد وإنشاء الحساب</button>
                        </form>
                    </div>
                @else
                    <div class="card-sub" style="padding-top:.6rem;border-top:1px solid var(--hair)">
                        {{ $item->status_label }} بواسطة {{ $item->reviewer?->name ?? '—' }} · <span class="num">{{ $item->reviewed_at?->format('Y-m-d H:i') }}</span>
                        @if ($item->user)
                            · <a href="{{ route('panel.users', ['search' => $item->user->phone]) }}">عرض الحساب</a>
                        @endif
                        @if ($item->rejection_reason)
                            <div style="margin-top:.35rem">السبب: {{ $item->rejection_reason }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="card" style="grid-column:1/-1"><p class="card-sub" style="padding:1.5rem 0;text-align:center">لا طلبات {{ $status === 'pending' ? 'بانتظار المراجعة' : 'مطابقة' }}</p></div>
        @endforelse
    </div>
    @include('partials.pagination', ['paginator' => $requests])
@endsection
