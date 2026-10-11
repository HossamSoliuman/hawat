@extends('layouts.app')

@section('title', 'الإعدادات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'settings'])</div>
            <div>
                <h1>الإعدادات</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('subadmin.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="settings-grid">
            <div class="settings-panel">
                <div class="p-head">@include('partials.icon', ['name' => 'bell']) <h3>الإشعارات</h3></div>
                @forelse ($channels as $channel)
                    <div class="set-row">
                        <div>
                            <span style="font-size:.82rem">{{ $channel->label }}</span>
                            @if ($channel->description)<p style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $channel->description }}</p>@endif
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="channels[{{ $channel->channel }}]" value="1" @checked($channel->enabled)>
                            <span></span>
                        </label>
                    </div>
                @empty
                    <p style="font-size:.78rem;color:hsl(var(--muted-foreground))">لا توجد قنوات إشعار مسجّلة.</p>
                @endforelse
            </div>

            @foreach ($panels as $panel)
                <div class="settings-panel">
                    <div class="p-head">@include('partials.icon', ['name' => $panel['icon']]) <h3>{{ $panel['title'] }}</h3></div>
                    @foreach ($panel['rows'] as $row)
                        <div class="set-row">
                            <span class="s-label">{{ $row['label'] }}</span>
                            <span class="s-value {{ $row['tone'] ?? '' }}">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="settings-panel">
                <div class="p-head">@include('partials.icon', ['name' => 'database']) <h3>ملاحظات الخصوصية</h3></div>
                <p style="font-size:.8rem;line-height:1.9;color:hsl(var(--muted-foreground))">{{ $privacyNote }}</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1.25rem">
            @include('partials.icon', ['name' => 'save']) حفظ الإعدادات
        </button>
    </form>
@endsection
