@extends('layouts.app')

@section('title', config('info.title'))

@push('head')
    @include('admin.partials.styles')
@endpush

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-cog'])</div>
            <div>
                <h1>{{ config('info.title') }}</h1>
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('subadmin.audit-log') }}">
                @include('partials.icon', ['name' => 'history'])
                سجل العمليات
            </a>
        </div>
    </div>

    <div class="stat-grid cols-4">
        @include('partials.stat-card', ['label' => 'سجلات البيانات الأساسية', 'value' => number_format($totals['records']), 'icon' => 'database', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الجداول المُدارة', 'value' => $totals['tables'], 'icon' => 'layers', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'التكاملات المفعّلة', 'value' => $totals['integrations'].' / '.$totals['integrations_total'], 'icon' => 'plug', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'أقسام الإدارة', 'value' => count($sections), 'icon' => 'layout-dashboard', 'tone' => 'warning'])
    </div>

    @foreach ($sections as $section)
        <section class="hub-section">
            <h2 class="hub-title">{{ $section['title'] }} <span class="n">{{ count($section['items']) }}</span></h2>

            <div class="hub-grid">
                @foreach ($section['items'] as $item)
                    <a class="hub-card" href="{{ App\Support\Nav::url($item) }}">
                        <span class="glyph">@include('partials.icon', ['name' => $item['icon']])</span>
                        <span class="body">
                            <span class="name">{{ $item['label'] }}</span>
                            @if ($item['label_en'])
                                <span class="en" dir="ltr">{{ $item['label_en'] }}</span>
                            @endif
                        </span>
                        <span class="meta">
                            @if ($item['summary'])
                                <span class="badge badge-{{ $item['summary']['tone'] }}">{{ $item['summary']['text'] }}</span>
                            @endif
                        </span>
                        @include('partials.icon', ['name' => 'chevron-left'])
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
