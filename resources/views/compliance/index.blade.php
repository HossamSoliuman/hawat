@extends('layouts.app')

@section('title', 'الرقابة والامتثال')

@php
    $sevBadge = fn ($s) => ['منخفض' => 'badge-info', 'متوسط' => 'badge-warn', 'مرتفع' => 'badge-danger', 'حرج' => 'badge-danger'][$s] ?? 'badge-info';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'shield-check'])</div>
            <div>
                <h1>الرقابة والامتثال</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="toggleDrawer('violationDrawer', true)">@include('partials.icon', ['name' => 'plus']) تسجيل مخالفة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المخالفات', 'value' => $stats['total'], 'icon' => 'ban', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مخالفات مفتوحة', 'value' => $stats['open'], 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'خطورة مرتفعة', 'value' => $stats['high'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'إجمالي الغرامات', 'value' => number_format($stats['fines']), 'unit' => 'ريال', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'قوارب مخالفة', 'value' => $stats['boats'], 'icon' => 'ship', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>نوع المخالفة</span>
            <select class="select" name="type" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                @foreach ($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الخطورة</span>
            <select class="select" name="severity" onchange="this.form.submit()">
                <option value="">كل الدرجات</option>
                @foreach (['منخفض', 'متوسط', 'مرتفع', 'حرج'] as $s)<option value="{{ $s }}" @selected(request('severity') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['مسجلة', 'قيد المراجعة', 'تم الإجراء', 'مغلقة'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('services.compliance') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>نوع المخالفة</th><th>القارب</th><th>الموقع</th><th>الخطورة</th><th>الغرامة</th><th>الإجراء</th><th>التاريخ</th><th>الحالة</th></tr></thead>
            <tbody>
                @forelse ($violations as $violation)
                    <tr>
                        <td style="font-weight:600">{{ $violation->violation_type }}</td>
                        <td>{{ $violation->boat?->name ?? '—' }}</td>
                        <td>{{ $violation->location ?? '—' }}</td>
                        <td><span class="badge {{ $sevBadge($violation->severity) }}">{{ $violation->severity }}</span></td>
                        <td>{{ $violation->fine_amount ? number_format($violation->fine_amount) : '—' }}</td>
                        <td>{{ $violation->action ?? '—' }}</td>
                        <td>{{ $violation->date?->format('Y-m-d') ?? '—' }}</td>
                        <td><span class="badge {{ in_array($violation->status, ['مغلقة', 'تم الإجراء']) ? 'badge-ok' : 'badge-warn' }}">{{ $violation->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مخالفات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="violationDrawer-overlay" onclick="toggleDrawer('violationDrawer', false)"></div>
    <div class="drawer" id="violationDrawer">
        <div class="drawer-head">
            <h3>تسجيل مخالفة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('violationDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('services.compliance.store') }}" class="drawer-body">
            @csrf
            <div class="form-grid">
                <label class="field"><span>نوع المخالفة *</span><input class="input" name="violation_type" required></label>
                <label class="field"><span>الخطورة *</span>
                    <select class="select" name="severity" required>
                        @foreach (['منخفض', 'متوسط', 'مرتفع', 'حرج'] as $s)<option>{{ $s }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">غير محدد</option>
                        @foreach ($boats as $boat)<option value="{{ $boat->id }}">{{ $boat->name }} — {{ $boat->boat_number }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الموقع</span><input class="input" name="location"></label>
                <label class="field"><span>الغرامة (ريال)</span><input class="input" type="number" step="0.01" min="0" name="fine_amount"></label>
                <label class="field"><span>الإجراء</span><input class="input" name="action"></label>
                <label class="field"><span>التاريخ</span><input class="input" type="date" name="date" value="{{ now()->toDateString() }}"></label>
                <label class="field"><span>الحالة</span>
                    <select class="select" name="status">
                        @foreach (['مسجلة', 'قيد المراجعة', 'تم الإجراء', 'مغلقة'] as $s)<option>{{ $s }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>الوصف</span><textarea class="input" rows="3" name="description"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('violationDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ المخالفة</button>
            </div>
        </form>
    </div>
@endsection