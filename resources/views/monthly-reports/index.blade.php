@extends('layouts.app')

@section('title', 'تقارير الإنتاج')

@php
    $filters = ['period' => $period, 'year' => $year, 'month' => $month, 'day' => $day, 'region' => $region];
    $totals = $report['totals'];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
            <div>
                <h1>تقارير الإنتاج</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('stats.monthly-reports.print', $filters) }}" target="_blank" rel="noopener" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) طباعة / PDF</a>
            <a href="{{ route('stats.monthly-reports.export', $filters) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-spreadsheet']) CSV</a>
        </div>
    </div>

    {{-- الشريط والمرشّحات في سطر واحد: لا يبقى نصف البطاقة فارغًا. --}}
    <div class="card" style="flex-direction:row;flex-wrap:wrap;align-items:flex-end;gap:.75rem">
        <div class="seg">
            @foreach ($periods as $key => $label)
                <a href="{{ route('stats.monthly-reports', array_merge($filters, ['period' => $key])) }}" class="{{ $period === $key ? 'is-active' : '' }}">
                    @include('partials.icon', ['name' => 'calendar']) {{ $label }}
                </a>
            @endforeach
        </div>
        <form method="GET" class="filter-bar" style="border:0;padding:0;flex:1">
            <input type="hidden" name="period" value="{{ $period }}">
            <label class="field"><span>السنة</span>
                <select class="select" name="year" onchange="this.form.submit()">
                    @foreach ($years as $option)
                        <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            @if ($period !== 'yearly')
                <label class="field"><span>الشهر</span>
                    <select class="select" name="month" onchange="this.form.submit()">
                        @foreach ($months as $number => $name)
                            <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($period === 'daily')
                <label class="field"><span>اليوم</span>
                    <select class="select" name="day" onchange="this.form.submit()">
                        @foreach (range(1, 31) as $option)
                            <option value="{{ $option }}" @selected($day === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="field"><span>المنطقة</span>
                <select class="select" name="region" onchange="this.form.submit()">
                    <option value="">كل المناطق</option>
                    @foreach ($regions as $name)
                        <option value="{{ $name }}" @selected($region === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn btn-primary">توليد التقرير</button>
        </form>
    </div>

    @include('partials.section-head', ['icon' => 'gauge', 'title' => 'إجماليات الفترة'])

    <div class="stat-grid cols-5">
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($totals['catch_kg'], 1), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المعتمد', 'value' => $totals['approved_kg'] ? number_format($totals['approved_kg'], 1) : '—', 'unit' => 'كجم', 'icon' => 'badge-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الرحلات', 'value' => number_format($totals['trips']), 'unit' => 'رحلة', 'icon' => 'sailboat', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'القوارب', 'value' => number_format($totals['boats']), 'unit' => 'قارب', 'icon' => 'ship', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'عدد الأنواع', 'value' => number_format($totals['species']), 'unit' => 'نوع', 'icon' => 'fish', 'tone' => 'primary'])
    </div>

    @if ($totals['records'] !== 0)
        @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'التفصيل'])
    @endif

    @if ($totals['records'] === 0)
        <div class="pending-card">
            @include('partials.icon', ['name' => 'file-chart'])
            <h3>لا توجد بيانات لهذه الفترة</h3>
            <p>لم تُسجَّل سجلات مصيد ضمن {{ $report['period_label'] }}{{ $region ? ' في '.$region : '' }}. جرّب فترة أخرى أو أزل مرشّح المنطقة.</p>
        </div>
    @else
        @include('monthly-reports.table', [
            'title' => 'الإنتاج حسب المنطقة',
            'headers' => ['المنطقة', 'المصيد (كجم)', 'الرحلات', 'الموانئ', 'القوارب'],
            'rows' => $report['by_region']->map(fn ($r) => [$r['region'], number_format($r['catch_kg'], 1), $r['trips'], $r['ports'], $r['boats']]),
        ])

        @include('monthly-reports.table', [
            'title' => 'الإنتاج حسب الميناء',
            'headers' => ['الميناء', 'المحافظة', 'المنطقة', 'المصيد (كجم)', 'الرحلات', 'القوارب', 'الأنواع'],
            'rows' => $report['by_port']->map(fn ($r) => [$r['port'], $r['governorate'], $r['region'], number_format($r['catch_kg'], 1), $r['trips'], $r['boats'], $r['species_count']]),
        ])

        @include('monthly-reports.table', [
            'title' => 'أبرز الأنواع السمكية',
            'headers' => ['النوع', 'المصيد (كجم)', 'عدد السجلات', 'الرحلات'],
            'rows' => $report['by_species']->map(fn ($r) => [$r['species'], number_format($r['catch_kg'], 1), $r['records'], $r['trips']]),
        ])
    @endif
@endsection
