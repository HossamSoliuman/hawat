@extends('layouts.app')

@section('title', 'الإشعارات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bell'])</div>
            <div>
                <h1>الإشعارات</h1>
            </div>
        </div>
        <div class="actions">
            @if (request('filter') === 'unread')
                <a href="{{ route('panel.notifications') }}" class="btn btn-outline">الكل</a>
            @else
                <a href="{{ route('panel.notifications', ['filter' => 'unread']) }}" class="btn btn-outline">غير المقروء فقط</a>
            @endif
            @if ($unread > 0)
                <form method="POST" action="{{ route('panel.notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-primary">@include('partials.icon', ['name' => 'check-check']) تعليم الكل مقروءًا</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    @forelse ($notifications as $n)
        <div class="notif-item {{ $n->isRead() ? '' : 'is-unread' }}">
            <div class="n-icon">@include('partials.icon', ['name' => $n->type?->icon ?: 'bell'])</div>
            <div style="min-width:0;flex:1">
                <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
                    <span class="n-title">{{ $n->title }}</span>
                    @if ($n->trip)<span class="badge badge-info num">{{ $n->trip->trip_number }}</span>@endif
                    <span class="n-time">{{ $n->created_at->diffForHumans() }} — {{ $n->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <p class="n-body">{{ $n->body }}</p>
            </div>
            <form method="POST" action="{{ route('panel.notifications.read', $n) }}">
                @csrf
                <button class="btn btn-outline" style="padding:.3rem .6rem;font-size:.72rem" title="{{ $n->isRead() ? 'فتح' : 'تعليم مقروءًا وفتح' }}">
                    @include('partials.icon', ['name' => 'chevron-left']) {{ $n->trip ? 'الرحلة' : 'فتح' }}
                </button>
            </form>
        </div>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'bell'])
            <h3>لا إشعارات</h3>
            <p>يصلك هنا ما يخصّك من رحلاتك: رحلة جديدة بانتظارك، انطلاقها، إلغاؤها، واكتمالها.</p>
        </div>
    @endforelse

    @include('partials.pagination', ['paginator' => $notifications])
@endsection
