@extends('layouts.app')

@section('title', 'الصيد العرضي')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'waves'])</div>
            <div>
                <h1>الصيد العرضي</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="toggleDrawer('bycatchDrawer', true)">@include('partials.icon', ['name' => 'plus']) تسجيل صيد عرضي</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي السجلات', 'value' => $stats['total'], 'icon' => 'clipboard-check', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الكمية الكلية', 'value' => number_format($stats['quantity']), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'أُعيد للبحر', 'value' => number_format($stats['released']), 'unit' => 'كجم', 'icon' => 'leaf', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'تم إنزاله', 'value' => number_format($stats['kept']), 'unit' => 'كجم', 'icon' => 'anchor', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد الأنواع', 'value' => $stats['species'], 'icon' => 'fish', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث بالنوع</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم النوع..."></label>
        <label class="field"><span>الإجراء</span>
            <select class="select" name="action" onchange="this.form.submit()">
                <option value="">كل الإجراءات</option>
                @foreach ($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('bycatch') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>النوع</th><th>الرحلة</th><th>القارب</th><th>الكمية (كجم)</th><th>الإجراء</th><th>الحالة</th><th>التاريخ</th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td style="font-weight:600">{{ $record->species_name }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $record->trip?->trip_number ?? '—' }}</td>
                        <td>{{ $record->trip?->boat?->name ?? '—' }}</td>
                        <td>{{ number_format($record->quantity_kg) }}</td>
                        <td>{{ $record->action_taken ?? '—' }}</td>
                        <td><span class="badge {{ $record->action_taken === 'إعادة للبحر' ? 'badge-ok' : 'badge-info' }}">{{ $record->status }}</span></td>
                        <td>{{ $record->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد سجلات صيد عرضي</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="bycatchDrawer-overlay" onclick="toggleDrawer('bycatchDrawer', false)"></div>
    <div class="drawer" id="bycatchDrawer">
        <div class="drawer-head">
            <h3>تسجيل صيد عرضي</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('bycatchDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('bycatch.store') }}" class="drawer-body">
            @csrf
            <label class="field"><span>الرحلة *</span>
                <select class="select" name="trip_id" required>
                    <option value="">اختر الرحلة</option>
                    @foreach ($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_number }}</option>@endforeach
                </select>
            </label>
            <label class="field"><span>اسم النوع *</span><input class="input" name="species_name" required></label>
            <label class="field"><span>الكمية (كجم) *</span><input class="input" type="number" step="0.01" min="0" name="quantity_kg" required></label>
            <label class="field"><span>الإجراء المتخذ</span>
                <select class="select" name="action_taken">
                    <option value="إعادة للبحر">إعادة للبحر</option>
                    <option value="إنزال">إنزال</option>
                    <option value="إتلاف">إتلاف</option>
                </select>
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('bycatchDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection