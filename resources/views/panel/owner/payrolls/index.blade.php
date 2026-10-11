@extends('layouts.app')

@section('title', 'مسيرات الرواتب')

@section('content')
    @php
        $netTotal = $rows->sum(fn ($p) => $p->lines->sum('net'));
        $paidTotal = $rows->sum(fn ($p) => $p->lines->sum('paid_amount'));
    @endphp
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calculator'])</div>
            <div>
                <h1>مسيرات الرواتب</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.crew-pay') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'user-cog']) أجور الطاقم</a>
            <button type="button" class="btn btn-primary" onclick="toggleDrawer('newDrawer', true)">@include('partials.icon', ['name' => 'plus']) مسير جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المسيرات', 'value' => number_format($rows->count()), 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نصيب الطاقم', 'value' => number_format($rows->sum('crew_pool'), 2), 'unit' => 'ر.س', 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المسدَّد', 'value' => number_format($paidTotal, 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'غير مسدَّد', 'value' => number_format($rows->sum(fn ($p) => $p->lines->whereNull('paid_at')->sum('net')), 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $netTotal - $paidTotal > 0 ? 'warning' : 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>القارب</span>
            <select class="select" name="boat">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>السنة</span>
            <select class="select" name="year">
                <option value="">الكل</option>
                @foreach ($years as $y)<option value="{{ $y }}" @selected((string) request('year') === (string) $y)>{{ $y }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>حالة السداد</span>
            <select class="select" name="status">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s->id }}" @selected((string) request('status') === (string) $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.payrolls') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>المسير</th><th>الشهر</th><th>القارب</th><th>صافي الربح</th><th>نصيب الطاقم</th><th>الأفراد</th><th>الصافي</th><th>المسدَّد</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php $paid = $row->lines->whereNotNull('paid_at')->count(); @endphp
                    <tr>
                        <td><a href="{{ route('panel.owner.payrolls.show', $row->id) }}" class="num" style="font-weight:700">{{ $row->payroll_number }}</a></td>
                        <td>{{ $row->period_label }}</td>
                        <td>{{ $row->boat_name }}</td>
                        <td class="num" @if ($row->net_profit < 0) style="color:var(--st-critical)" @endif>{{ number_format($row->net_profit, 2) }}</td>
                        <td class="num">{{ number_format($row->crew_pool, 2) }}</td>
                        <td class="num">{{ $paid }} / {{ $row->lines->count() }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($row->lines->sum('net'), 2) }}</td>
                        <td class="num">{{ number_format($row->lines->sum('paid_amount'), 2) }}</td>
                        <td><span class="badge {{ $paid === $row->lines->count() && $paid > 0 ? 'badge-ok' : ($paid > 0 ? 'badge-info' : 'badge-warn') }}">{{ $row->paymentStatus?->name ?? '—' }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <a href="{{ route('panel.owner.payrolls.print', $row->id) }}" target="_blank" class="icon-action" title="طباعة">@include('partials.icon', ['name' => 'printer'])</a>
                                <a href="{{ route('panel.owner.payrolls.show', $row->id) }}" class="icon-action" title="فتح">@include('partials.icon', ['name' => 'arrow-left'])</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مسيرات — أنشئ مسير شهر لقارب</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="newDrawer-overlay" onclick="toggleDrawer('newDrawer', false)"></div>
    <div class="drawer" id="newDrawer">
        <div class="drawer-head">
            <h3>مسير جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('newDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('panel.owner.payrolls.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <div class="form-grid">
                <label class="field"><span>القارب *</span>
                    <select class="select" name="boat_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) old('boat_id') === (string) $b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الشهر *</span><input class="input" name="period" type="month" dir="ltr" required max="{{ now()->format('Y-m') }}" value="{{ old('period', now()->subMonth()->format('Y-m')) }}"></label>
            </div>
            <p style="font-size:.75rem;color:hsl(var(--muted-foreground));margin-top:.5rem">يُحسب من مبيعات مصيد القارب ومصروفاته وإهلاك أصوله في الشهر، ويُعاد حسابه كلما فُتح حتى يُسدَّد. إن وُجد مسير الشهر فُتح بدل إنشاء آخر.</p>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('newDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">إنشاء</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    @if ($errors->has('boat_id') || $errors->has('period'))
        toggleDrawer('newDrawer', true);
    @endif
</script>
@endpush
