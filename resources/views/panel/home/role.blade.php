@extends('layouts.app')

@section('title', $user->appRole->name)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layout-dashboard'])</div>
            <div>
                <h1>مرحبًا {{ $user->name }}</h1>
            </div>
        </div>
    </div>

    <div class="card" style="padding:1.25rem;display:flex;flex-direction:column;gap:.5rem">
        <p class="card-title">بوابة «{{ $user->appRole->name }}» قيد البناء</p>
        <p class="card-sub">حسابك مفعّل وتستطيع الدخول من التطبيق الآن؛ صفحات هذه البوابة تُضاف تباعًا.</p>
        <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;font-size:.8rem;margin-top:.5rem">
            <dt style="color:hsl(var(--muted-foreground))">الجوال</dt><dd dir="ltr" style="font-family:monospace;text-align:right">{{ $user->phone ?? '—' }}</dd>
            <dt style="color:hsl(var(--muted-foreground))">البريد</dt><dd dir="ltr" style="text-align:right">{{ $user->email ?? '—' }}</dd>
            @if ($user->owner)
                <dt style="color:hsl(var(--muted-foreground))">يتبع المالك</dt><dd>{{ $user->owner->name }}</dd>
            @endif
        </dl>
    </div>
@endsection
