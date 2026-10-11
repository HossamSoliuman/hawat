{{--
    غلاف الإعدادات: ترويسة واحدة وشريط تبويبات فوق صفحة البيانات الأساسية
    الحالية. كل تبويب صفحته القائمة بمسارها وحِفظها، تمتدّ من هذا الغلاف
    وتملأ قسمين: settings-actions (زرّ الإضافة في الترويسة) وsettings (محتواها).
    التبويبات من config/hawat.php → owner_settings، والنشط منها بمسار الطلب.
--}}
@extends('layouts.app')

@php
    $settingsTabs = config('hawat.owner_settings');
    $settingsTab = collect($settingsTabs)->first(fn (array $tab) => request()->routeIs($tab['route']));
@endphp

@section('title', 'الإعدادات — '.$settingsTab['label'])

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'settings'])</div>
            <div>
                <h1>الإعدادات</h1>
            </div>
        </div>
        <div class="actions">@yield('settings-actions')</div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <nav class="seg" aria-label="تبويبات الإعدادات" style="margin-bottom:1.25rem">
        @foreach ($settingsTabs as $tab)
            <a href="{{ route($tab['route']) }}" class="{{ $tab === $settingsTab ? 'is-active' : '' }}" @if ($tab === $settingsTab) aria-current="page" @endif>@include('partials.icon', ['name' => $tab['icon']]) {{ $tab['label'] }}</a>
        @endforeach
    </nav>

    @include('partials.section-head', ['icon' => $settingsTab['icon'], 'title' => $settingsTab['label']])

    @yield('settings')
@endsection
