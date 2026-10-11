@extends('layouts.app')

@section('title', 'المحافظات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'building'])</div>
            <div>
                <h1>المحافظات الساحلية</h1>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المحافظات', 'value' => $stats['total'], 'icon' => 'building', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الموانئ', 'value' => $stats['ports'], 'icon' => 'anchor', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => number_format($stats['boats']), 'icon' => 'ship', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الصيادون', 'value' => number_format($stats['fishers']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($stats['catch']), 'unit' => 'طن', 'icon' => 'scale', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="text" name="search" value="{{ request('search') }}" placeholder="اسم المحافظة..."></label>
        <label class="field"><span>المنطقة</span>
            <select class="select" name="region" onchange="this.form.submit()">
                <option value="">كل المناطق</option>
                @foreach ($regions as $region)
                    <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">تصفية</button>
        <a href="{{ route('governorates') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cards-grid cols-3">
        @forelse ($governorates as $gov)
            <a href="{{ route('governorates.show', $gov) }}" class="entity-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:.625rem">
                        <div class="icon-wrap" style="height:2.25rem;width:2.25rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;background:hsl(var(--primary)/.1);color:hsl(var(--primary))">@include('partials.icon', ['name' => 'building'])</div>
                        <div>
                            <h3 style="font-weight:700">{{ $gov->name }}</h3>
                            <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $gov->region?->name }}</p>
                        </div>
                    </div>
                    <span style="color:hsl(var(--muted-foreground))">@include('partials.icon', ['name' => 'chevron-left'])</span>
                </div>
                <div class="mini-grid">
                    <div class="mini">@include('partials.icon', ['name' => 'anchor'])<div><p class="m-label">موانئ</p><p class="m-value">{{ number_format($gov->ports_count) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'ship'])<div><p class="m-label">قوارب نشطة</p><p class="m-value">{{ number_format($gov->active_boats) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'users'])<div><p class="m-label">صيادون</p><p class="m-value">{{ number_format($gov->active_fishers) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">مصيد (طن)</p><p class="m-value">{{ number_format($gov->total_catch_tons) }}</p></div></div>
                </div>
            </a>
        @empty
            <div class="card" style="grid-column:1/-1;padding:2.5rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد محافظات مطابقة</div>
        @endforelse
    </div>
@endsection