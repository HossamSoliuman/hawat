@extends('layouts.app')

@section('title', $definition['label'])

@push('head')
    @include('admin.partials.styles')
@endpush

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => $definition['icon']])</div>
            <div>
                <p class="eyebrow">
                    <a href="{{ route('admin.index') }}">{{ config('info.title') }}</a>
                    @if ($section)
                        @include('partials.icon', ['name' => 'chevron-left'])
                        <span>{{ $section }}</span>
                    @endif
                </p>
                <h1>{{ $definition['label'] }}</h1>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($definition['type'] === 'resource')
        <div class="notice">
            @include('partials.icon', ['name' => 'alert-triangle'])
            <p>{!! nl2br(e(config('info.notice'))) !!}</p>
        </div>
    @endif

    {{-- اللوحة بطاقةُ اللوحة نفسها: خطّ شعري وأقواس زوايا. لوحة الموارد ترسم شريط جداولها فوق جسمها. --}}
    <div class="card panel">
        @if ($definition['type'] === 'resource')
            @include($panel['view'], $panel)
        @else
            <div class="panel-body">@include($panel['view'], $panel)</div>
        @endif
    </div>
@endsection
