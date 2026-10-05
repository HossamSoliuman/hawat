@extends('layouts.app')

@section('title', 'التقارير المفصلة')

@section('content')
    @php
        // مجموعات hispa (ReportsHub) ببنودها وترتيبها، وكل بند رابط صفحته في hawat.
        $report = fn (string $key) => [route('panel.owner.reports.show', $key), $reports[$key]['hub'] ?? $reports[$key]['title']];
        $sections = [
            ['title' => 'تقارير تشغيلية', 'icon' => 'clipboard', 'tone' => 'primary', 'items' => [
                $report('trip-report'),
                $report('trip-profitability'),
                $report('boat-profitability'),
                $report('production'),
            ]],
            ['title' => 'تقارير مالية', 'icon' => 'coins', 'tone' => 'success', 'items' => [
                $report('month-summary'),
                $report('profit-loss'),
                $report('sales-report'),
                $report('expenses-by-category'),
            ]],
            ['title' => 'كشف الحسابات', 'icon' => 'file-text', 'tone' => 'info', 'items' => [
                $report('customer-statement'),
                $report('vendor-statement'),
                $report('crew-statement'),
            ]],
            ['title' => 'تقارير الإقفال', 'icon' => 'lock', 'tone' => 'warning', 'items' => [
                [route('panel.owner.month-closings'), 'الإقفال الشهري'],
                $report('annual-summary'),
            ]],
            ['title' => 'تقارير إدارية', 'icon' => 'settings', 'tone' => 'muted', 'items' => [
                $report('fish-quantity'),
            ]],
            ['title' => 'تقارير الأصول', 'icon' => 'archive', 'tone' => 'danger', 'items' => [
                [route('panel.owner.assets'), 'سجل الأصول التفصيلي'],
                [route('panel.owner.assets.depreciation'), 'جدول الإهلاك الشهري'],
            ]],
        ];
    @endphp

    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
            <div>
                <h1>التقارير المفصلة</h1>
                <p>كل التقارير على أساس الشهر مع إمكانية التصفية بالقارب</p>
            </div>
        </div>
    </div>

    <div class="report-groups">
        @foreach ($sections as $section)
            <section class="card report-group">
                <header class="{{ $section['tone'] }}">
                    <span class="kpi-icon {{ $section['tone'] }}">@include('partials.icon', ['name' => $section['icon']])</span>
                    {{ $section['title'] }}
                </header>
                @foreach ($section['items'] as [$url, $label])
                    <a href="{{ $url }}"><span>{{ $label }}</span>@include('partials.icon', ['name' => 'arrow-left'])</a>
                @endforeach
            </section>
        @endforeach
    </div>
@endsection
