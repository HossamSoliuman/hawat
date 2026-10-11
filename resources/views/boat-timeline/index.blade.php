@extends('layouts.app')

@section('title', 'الجدول الزمني للقارب')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'clock'])</div>
            <div>
                <h1>الجدول الزمني للقارب</h1>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field" style="min-width:16rem"><span>اختر القارب</span>
            <select class="select" name="boat" onchange="this.form.submit()">
                @foreach ($boats as $item)
                    <option value="{{ $item->id }}" @selected($boat && $boat->id === $item->id)>{{ $item->name }} — {{ $item->boat_number }}</option>
                @endforeach
            </select>
        </label>
    </form>

    @if ($boat)
        <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
            @include('partials.stat-card', ['label' => 'الميناء', 'value' => $boat->port?->name ?? '—', 'icon' => 'anchor', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'حالة القارب', 'value' => $boat->status, 'icon' => 'activity', 'tone' => 'info'])
            @include('partials.stat-card', ['label' => 'عدد الرحلات', 'value' => $trips->count(), 'icon' => 'sailboat', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($totalApproved), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'success'])
            @include('partials.stat-card', ['label' => 'متوسط مدة الرحلة', 'value' => $avgDuration, 'unit' => 'ساعة', 'icon' => 'clock', 'tone' => 'primary'])
        </div>

        <div class="card">
            <p class="card-title" style="margin-bottom:1rem">الخط الزمني للرحلات</p>
            @forelse ($trips as $trip)
                <div style="position:relative;padding-inline-start:1.5rem;padding-bottom:1.25rem;border-inline-start:2px solid hsl(var(--border))">
                    <span style="position:absolute;inset-inline-start:-7px;top:.25rem;height:12px;width:12px;border-radius:9999px;background:{{ $trip->status === 'معتمدة' ? '#10b981' : ($trip->status === 'في البحر' ? '#0ea5e9' : '#f59e0b') }};box-shadow:0 0 0 3px hsl(var(--card))"></span>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                        <span style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $trip->trip_number }}</span>
                        <span class="badge {{ $trip->status === 'معتمدة' ? 'badge-ok' : 'badge-info' }}">{{ $trip->status }}</span>
                        <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $trip->departure_time?->format('Y-m-d H:i') ?? '—' }} ← {{ $trip->return_time?->format('Y-m-d H:i') ?? 'لم تعد بعد' }}</span>
                    </div>
                    <div style="margin-top:.35rem;font-size:.72rem;color:hsl(var(--muted-foreground));line-height:1.9">
                        <span>المدة: <strong style="color:hsl(var(--foreground))">{{ $trip->duration_hours ?? '—' }}</strong> ساعة</span> ·
                        <span>المصيد المعتمد: <strong style="color:hsl(var(--foreground))">{{ number_format($trip->approved_kg) }}</strong> كجم</span> ·
                        <span>أداة الصيد: <strong style="color:hsl(var(--foreground))">{{ $trip->gear_type ?? '—' }}</strong></span>
                    </div>
                    @if ($trip->catchRecords->isNotEmpty())
                        <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.35rem">
                            @foreach ($trip->catchRecords as $record)
                                <span class="tag tag-gulf">{{ $record->species?->name_ar ?? 'نوع' }} — {{ number_format($record->quantity_kg) }} كجم</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p style="padding:1.5rem;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد رحلات مسجلة لهذا القارب</p>
            @endforelse
        </div>
    @endif
@endsection