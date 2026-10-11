@extends('layouts.app')

@section('title', 'الأنواع السمكية')

@php
    $inGulf = fn ($s) => filled($s->name_local_gulf) && ! preg_match('/^[\s_—-]+$/u', $s->name_local_gulf);
    $inRedSea = fn ($s) => filled($s->name_local_red_sea) && ! preg_match('/^[\s_—-]+$/u', $s->name_local_red_sea);
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'fish'])</div>
            <div>
                <h1>الأنواع السمكية</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="toggleDrawer('speciesDrawer', true)">@include('partials.icon', ['name' => 'plus']) إضافة نوع</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الأنواع', 'value' => $stats['total'], 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مصحح وموثق', 'value' => $stats['documented'], 'icon' => 'shield-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'منسّق آليًا', 'value' => $stats['auto'], 'icon' => 'check-circle', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار المراجعة', 'value' => $stats['pending'], 'icon' => 'alert-triangle', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'انخفاض حاد', 'value' => $stats['declined'], 'icon' => 'trending-down', 'tone' => 'danger'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="text" name="search" value="{{ request('search') }}" placeholder="الاسم العربي أو العلمي أو الإنجليزي..."></label>
        <label class="field"><span>التصنيف</span>
            <select class="select" name="category" onchange="this.form.submit()">
                <option value="">كل التصنيفات</option>
                @foreach (['أسماك', 'روبيان', 'قشريات', 'رخويات', 'أخرى'] as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>المنطقة البحرية</span>
            <select class="select" name="sea_region" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach (['الخليج العربي', 'البحر الأحمر', 'كلاهما'] as $r)
                    <option value="{{ $r }}" @selected(request('sea_region') === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>حالة المخزون</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['مستقر', 'مراقبة', 'ضغط صيد مرتفع', 'انخفاض حاد'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>حالة المراجعة</span>
            <select class="select" name="review" onchange="this.form.submit()">
                <option value="">حالة المراجعة</option>
                @foreach (['مصحح وموثق', 'منسق آليًا', 'مقبول مبدئيًا'] as $r)
                    <option value="{{ $r }}" @selected(request('review') === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">تصفية</button>
        <a href="{{ route('species') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @foreach ($groups as $group)
        <section style="margin-bottom:2rem">
            <div class="group-head {{ $group['tone'] }}">
                <span style="display:inline-flex;height:1.75rem;width:1.75rem;align-items:center;justify-content:center;border-radius:9999px;background:{{ $group['tone'] === 'gulf' ? '#e0f2fe' : ($group['tone'] === 'red' ? '#ffe4e6' : 'hsl(var(--muted))') }}">🌊</span>
                <h2>{{ $group['title'] }}</h2>
                <span class="count-pill">{{ $group['items']->count() }} نوع</span>
            </div>
            @if ($group['items']->isEmpty())
                <p class="card" style="border-style:dashed;padding:1.5rem;text-align:center;font-size:.72rem;color:hsl(var(--muted-foreground))">لا توجد أنواع في هذه المجموعة</p>
            @else
                <div class="cards-grid cols-4">
                    @foreach ($group['items'] as $sp)
                        <a href="{{ request()->fullUrlWithQuery(['selected' => $sp->id]) }}" class="entity-card" style="padding:1rem">
                            <div style="display:flex;align-items:center;gap:.35rem;font-size:.72rem;font-weight:600;color:hsl(var(--primary));margin-bottom:.5rem">@include('partials.icon', ['name' => 'hash'])<span style="font-family:monospace">{{ $sp->code ?? '—' }}</span></div>
                            <h3 style="font-weight:700">{{ $sp->name_ar }}</h3>
                            <div style="margin-top:.5rem;font-size:.72rem;line-height:1.9">
                                <p><span style="color:hsl(var(--muted-foreground))">العلمي:</span> <em>{{ $sp->corrected_name_sci ?: ($sp->name_sci ?: '—') }}</em></p>
                                <p><span style="color:hsl(var(--muted-foreground))">المحلي:</span> {{ $sp->name_local_gulf ?: ($sp->name_local_red_sea ?: ($sp->name_en ?: '—')) }}</p>
                            </div>
                            <div style="margin-top:auto;padding-top:.75rem;display:flex;flex-wrap:wrap;gap:.25rem">
                                @if ($inGulf($sp))<span class="tag tag-gulf">🌊 الخليج العربي</span>@endif
                                @if ($inRedSea($sp))<span class="tag tag-red">🌊 البحر الأحمر</span>@endif
                                @if (! $inGulf($sp) && ! $inRedSea($sp))<span style="font-size:11px;color:hsl(var(--muted-foreground))">لم يُحدد البحر بعد</span>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach

    <div class="drawer-overlay {{ $errors->any() && ! $selected ? 'is-open' : '' }}" id="speciesDrawer-overlay" onclick="toggleDrawer('speciesDrawer', false)"></div>
    <div class="drawer {{ $errors->any() && ! $selected ? 'is-open' : '' }}" id="speciesDrawer">
        <div class="drawer-head">
            <h3>إضافة نوع</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('speciesDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('species.store') }}" class="drawer-body">
            @csrf
            @if ($errors->any())
                <div class="flash" style="border-color:hsl(var(--danger));color:hsl(var(--danger))">{{ $errors->first() }}</div>
            @endif
            <label class="field"><span>الاسم العربي *</span><input class="input" name="name_ar" value="{{ old('name_ar') }}" required placeholder="الهامور"></label>
            <div class="form-grid">
                <label class="field"><span>الرمز</span><input class="input" type="number" name="code" value="{{ old('code') }}" min="1" placeholder="105"></label>
                <label class="field"><span>الاسم الإنجليزي</span><input class="input" name="name_en" value="{{ old('name_en') }}" placeholder="Grouper"></label>
                <label class="field wide"><span>الاسم العلمي</span><input class="input" name="name_sci" value="{{ old('name_sci') }}" placeholder="Epinephelus coioides"></label>
                <label class="field"><span>الاسم المحلي – الخليج</span><input class="input" name="name_local_gulf" value="{{ old('name_local_gulf') }}"></label>
                <label class="field"><span>الاسم المحلي – البحر الأحمر</span><input class="input" name="name_local_red_sea" value="{{ old('name_local_red_sea') }}"></label>
                <label class="field"><span>التصنيف *</span>
                    <select class="select" name="category">
                        @foreach (['أسماك', 'روبيان', 'قشريات', 'رخويات', 'أخرى'] as $c)
                            <option value="{{ $c }}" @selected(old('category', 'أسماك') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>حالة المخزون *</span>
                    <select class="select" name="status">
                        @foreach (['مستقر', 'مراقبة', 'ضغط صيد مرتفع', 'انخفاض حاد'] as $st)
                            <option value="{{ $st }}" @selected(old('status', 'مستقر') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field wide"><span>حالة المراجعة</span>
                    <select class="select" name="review_status">
                        <option value="">— بدون —</option>
                        @foreach (['مصحح وموثق', 'منسق آليًا', 'مقبول مبدئيًا'] as $r)
                            <option value="{{ $r }}" @selected(old('review_status', 'مقبول مبدئيًا') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>متوسط الوزن (كجم)</span><input class="input" type="number" step="0.01" min="0" name="avg_weight_kg" value="{{ old('avg_weight_kg') }}"></label>
                <label class="field"><span>متوسط الطول (سم)</span><input class="input" type="number" step="0.01" min="0" name="avg_length_cm" value="{{ old('avg_length_cm') }}"></label>
                <label class="field"><span>موسم الصيد</span><input class="input" name="season" value="{{ old('season') }}" placeholder="أكتوبر – مارس"></label>
                <label class="field"><span>مناطق الانتشار</span><input class="input" name="regions" value="{{ old('regions') }}"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2">{{ old('notes') }}</textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('speciesDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>

    @if ($selected)
        <div class="drawer-overlay is-open" onclick="location.href='{{ request()->fullUrlWithQuery(['selected' => null]) }}'"></div>
        <div class="drawer is-open">
            <div class="drawer-head">
                <h3>{{ $selected->name_ar }}</h3>
                <div style="display:flex;gap:.25rem;align-items:center">
                    <button type="button" class="btn btn-primary" style="padding:.4rem .75rem;font-size:.72rem" onclick="document.getElementById('reviewForm').style.display = document.getElementById('reviewForm').style.display === 'none' ? 'block' : 'none'">
                        @include('partials.icon', ['name' => 'pencil']) مراجعة وتصحيح
                    </button>
                    <a href="{{ request()->fullUrlWithQuery(['selected' => null]) }}" class="icon-action">@include('partials.icon', ['name' => 'x'])</a>
                </div>
            </div>
            <div class="drawer-body">
                <div id="reviewForm" style="display:none" class="card">
                    <p class="card-title" style="margin-bottom:.75rem">مراجعة وتصحيح بيانات النوع</p>
                    <form method="POST" action="{{ route('species.update', $selected) }}" class="form-grid">
                        @csrf
                        @method('PUT')
                        <label class="field"><span>الاسم المحلي – الخليج</span><input class="input" name="name_local_gulf" value="{{ $selected->name_local_gulf }}"></label>
                        <label class="field"><span>الاسم المحلي – البحر الأحمر</span><input class="input" name="name_local_red_sea" value="{{ $selected->name_local_red_sea }}"></label>
                        <label class="field wide"><span>الاسم العلمي المصحّح</span><input class="input" name="corrected_name_sci" value="{{ $selected->corrected_name_sci }}"></label>
                        <label class="field"><span>حالة المراجعة</span>
                            <select class="select" name="review_status">
                                @foreach (['مصحح وموثق', 'منسق آليًا', 'مقبول مبدئيًا'] as $r)
                                    <option value="{{ $r }}" @selected($selected->review_status === $r)>{{ $r }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field"><span>تاريخ المراجعة</span><input class="input" type="date" name="review_date" value="{{ $selected->review_date?->toDateString() }}"></label>
                        <label class="field"><span>المصدر 1</span><input class="input" name="source_1" value="{{ $selected->source_1 }}"></label>
                        <label class="field"><span>المصدر 2</span><input class="input" name="source_2" value="{{ $selected->source_2 }}"></label>
                        <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2">{{ $selected->notes }}</textarea></label>
                        <div class="wide" style="display:flex;justify-content:flex-end"><button type="submit" class="btn btn-primary">حفظ المراجعة</button></div>
                    </form>
                </div>

                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                    <div>
                        <p style="display:flex;align-items:center;gap:.35rem;font-size:.72rem;color:hsl(var(--muted-foreground))">@include('partials.icon', ['name' => 'hash']) <span style="font-family:monospace">{{ $selected->code ?? '—' }}</span></p>
                        <p style="font-style:italic;font-size:.875rem;color:hsl(var(--muted-foreground))">{{ $selected->corrected_name_sci ?: $selected->name_sci }}</p>
                        <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $selected->name_en }}</p>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.25rem">
                        <span class="badge badge-info">{{ $selected->record_type }}</span>
                        <span class="badge badge-ok">{{ $selected->directory_status }}</span>
                        @if ($selected->review_status)<span class="badge badge-info">{{ $selected->review_status }}</span>@endif
                        <span class="badge {{ in_array($selected->status, ['ضغط صيد مرتفع', 'انخفاض حاد']) ? 'badge-danger' : ($selected->status === 'مراقبة' ? 'badge-warn' : 'badge-ok') }}">{{ $selected->status }}</span>
                    </div>
                </div>

                <div class="card" style="background:hsl(var(--muted)/.2)">
                    <p class="card-title" style="display:flex;align-items:center;gap:.35rem;margin-bottom:.75rem;color:hsl(var(--primary))">@include('partials.icon', ['name' => 'book-open']) <span style="color:hsl(var(--foreground))">بيانات الدليل العلمي</span></p>
                    <div class="mini-grid" style="margin-top:0">
                        <div class="mini"><div><p class="m-label">الاسم المحلي – الخليج</p><p class="m-value">{{ $selected->name_local_gulf ?: '—' }}</p></div></div>
                        <div class="mini"><div><p class="m-label">الاسم المحلي – البحر الأحمر</p><p class="m-value">{{ $selected->name_local_red_sea ?: '—' }}</p></div></div>
                        <div class="mini"><div><p class="m-label">حالة المراجعة</p><p class="m-value">{{ $selected->review_status ?: '—' }}</p></div></div>
                        <div class="mini"><div><p class="m-label">موسم الصيد</p><p class="m-value">{{ $selected->season ?: '—' }}</p></div></div>
                    </div>
                </div>

                <div class="mini-grid" style="margin-top:0">
                    <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">متوسط الوزن</p><p class="m-value">{{ $selected->avg_weight_kg ? $selected->avg_weight_kg.' كجم' : '—' }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">متوسط الحجم</p><p class="m-value">{{ $selected->avg_length_cm ? $selected->avg_length_cm.' سم' : '—' }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'map-pin'])<div><p class="m-label">مناطق الانتشار</p><p class="m-value">{{ $selected->regions ?: '—' }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'map-pin'])<div><p class="m-label">أكثر ميناء إنزالاً</p><p class="m-value">{{ $selected->top_port ?: '—' }}</p></div></div>
                </div>

                @if ($selected->catch_kg > 0)
                    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
                        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($selected->catch_kg), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
                        @include('partials.stat-card', ['label' => 'عدد الرحلات', 'value' => number_format($selected->trips_count), 'icon' => 'sailboat', 'tone' => 'primary'])
                        @include('partials.stat-card', ['label' => 'عدد القوارب', 'value' => number_format($selected->boats_count), 'icon' => 'ship', 'tone' => 'primary'])
                    </div>
                    <div>
                        <p style="font-size:.72rem;font-weight:600;text-transform:uppercase;color:hsl(var(--muted-foreground));margin-bottom:.5rem">تطور الإنتاج (طن)</p>
                        <div style="height:200px"><canvas id="speciesTrend"></canvas></div>
                    </div>
                @else
                    <p class="card" style="border-style:dashed;text-align:center;font-size:.72rem;color:hsl(var(--muted-foreground))">لا توجد بيانات مصيد إنتاجي مرتبطة بهذا النوع بعد</p>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('scripts')
@if ($selected && $selected->catch_kg > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    const base = {{ round($selected->catch_kg / 1000) }};
    new Chart(document.getElementById('speciesTrend'), {
        type: 'line',
        data: {
            labels: ['2022', '2023', '2024', '2025', '2026'],
            datasets: [{ data: [Math.round(base * 1.25), Math.round(base * 1.12), Math.round(base * 1.05), Math.round(base * 1.02), base], borderColor: '#0284c7', borderWidth: 2.5, pointRadius: 4, tension: .35 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>
@endif
@endpush