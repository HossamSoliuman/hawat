@extends('layouts.app')

@section('title', 'رخص المواسم')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'ticket'])</div>
            <div>
                <h1>رخص المواسم</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openLicenseForm()">@include('partials.icon', ['name' => 'plus']) إصدار رخصة موسم</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الرخص', 'value' => $stats['total'], 'icon' => 'ticket', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رخص سارية', 'value' => $stats['active'], 'icon' => 'shield-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'موقوفة', 'value' => $stats['stopped'], 'icon' => 'ticket', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'منتهية', 'value' => $stats['expired'], 'icon' => 'calendar', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'قوارب مرخصة', 'value' => $stats['boats'], 'icon' => 'ship', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الموسم</span>
            <select class="select" name="season" onchange="this.form.submit()">
                <option value="">كل المواسم</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season->id }}" @selected(request('season') == $season->id)>{{ $season->name }} — {{ $season->sea }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['سارية', 'موقوفة', 'منتهية', 'ملغاة'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('services.season-licenses') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>رقم الرخصة</th><th>الموسم</th><th>القارب</th><th>حامل الرخصة</th><th>الإصدار</th><th>الانتهاء</th><th>أداة الصيد</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $license->license_number }}</td>
                        <td>{{ $license->fishingSeason?->name ?? '—' }}</td>
                        <td>{{ $license->boat?->name }} ({{ $license->boat?->boat_number ?? '—' }})</td>
                        <td>{{ $license->holder_name ?? '—' }}</td>
                        <td>{{ $license->issue_date?->toDateString() ?? '—' }}</td>
                        <td>{{ $license->expiry_date?->toDateString() ?? '—' }}</td>
                        <td>{{ $license->gear_type ?? '—' }}</td>
                        <td><span class="badge {{ ['سارية' => 'badge-ok', 'موقوفة' => 'badge-warn', 'منتهية' => 'badge-danger', 'ملغاة' => 'badge-danger'][$license->status] ?? 'badge-info' }}">{{ $license->status }}</span></td>
                        <td>
                            <button type="button" class="icon-action" title="تعديل الرخصة" onclick='openLicenseForm({!! json_encode($license->only(['id', 'license_number', 'fishing_season_id', 'boat_name', 'fisher_name', 'issued_date', 'expiry_date', 'quota_kg', 'used_kg', 'status']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                @include('partials.icon', ['name' => 'pencil'])
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد رخص مواسم مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="licenseDrawer-overlay" onclick="toggleDrawer('licenseDrawer', false)"></div>
    <div class="drawer" id="licenseDrawer">
        <div class="drawer-head">
            <div>
                <h3 id="licenseFormTitle">إصدار رخصة موسم</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">ترتبط الرخصة بقارب وموسم محددين وتُفحص تلقائيًا عند بدء الرحلة.</p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('licenseDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="licenseForm" action="{{ route('services.season-licenses.store') }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="licenseMethod" value="POST">
            <div class="form-grid">
                <label class="field"><span>رقم الرخصة *</span><input class="input" name="license_number" id="l-number" required></label>
                <label class="field"><span>حالة الرخصة</span>
                    <select class="select" name="status" id="l-status">
                        @foreach (['سارية', 'موقوفة', 'منتهية', 'ملغاة'] as $status)<option>{{ $status }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الموسم *</span>
                    <select class="select" name="fishing_season_id" id="l-season" required>
                        <option value="">اختر الموسم</option>
                        @foreach ($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }} — {{ $season->sea }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القارب *</span>
                    <select class="select" name="boat_id" id="l-boat" required>
                        <option value="">اختر القارب</option>
                        @foreach ($boats as $boat)<option value="{{ $boat->id }}">{{ $boat->name }} — {{ $boat->boat_number }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>حامل الرخصة</span><input class="input" name="holder_name" id="l-holder"></label>
                <label class="field"><span>أداة الصيد</span><input class="input" name="gear_type" id="l-gear" placeholder="مثال: شبك جر"></label>
                <label class="field"><span>تاريخ الإصدار</span><input class="input" type="date" name="issue_date" id="l-issue"></label>
                <label class="field"><span>تاريخ الانتهاء</span><input class="input" type="date" name="expiry_date" id="l-expiry"></label>
                <label class="field wide"><span>نطاق الصيد المصرح</span><textarea class="input" rows="2" name="allowed_area" id="l-area"></textarea></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" rows="2" name="notes" id="l-notes"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('licenseDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ الرخصة</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const licenseStoreUrl = @json(route('services.season-licenses.store'));

    function openLicenseForm(license = null) {
        const form = document.getElementById('licenseForm');
        document.getElementById('licenseFormTitle').textContent = license ? 'تعديل رخصة الموسم' : 'إصدار رخصة موسم';
        document.getElementById('licenseMethod').value = license ? 'PUT' : 'POST';
        form.action = license ? licenseStoreUrl + '/' + license.id : licenseStoreUrl;
        const set = (id, v) => document.getElementById(id).value = v ?? '';
        set('l-number', license?.license_number); set('l-status', license?.status ?? 'سارية');
        set('l-season', license?.fishing_season_id); set('l-boat', license?.boat_id);
        set('l-holder', license?.holder_name); set('l-gear', license?.gear_type);
        set('l-issue', license?.issue_date?.slice(0, 10)); set('l-expiry', license?.expiry_date?.slice(0, 10));
        set('l-area', license?.allowed_area); set('l-notes', license?.notes);
        toggleDrawer('licenseDrawer', true);
    }
</script>
@endpush