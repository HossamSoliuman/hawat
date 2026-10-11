@extends('layouts.app')

@section('title', 'الرحلات')

@php
    use App\Models\Trip;

    $view = request('view');
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'route'])</div>
            <div>
                <h1>الرحلات</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openDrawerForm(tripForm)">@include('partials.icon', ['name' => 'plus']) رحلة جديدة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'كل الرحلات', 'value' => number_format($counts['total']), 'icon' => 'route', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'بانتظار الانطلاق', 'value' => number_format($counts['scheduled']), 'icon' => 'calendar', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'في البحر', 'value' => number_format($counts['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'في طور العد', 'value' => number_format($counts['counting']), 'icon' => 'clipboard', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'جاهزة للبيع', 'value' => number_format($counts['for_sale']), 'icon' => 'shopping-cart', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="رقم الرحلة..."></label>
        <label class="field"><span>العرض</span>
            <select class="select" name="view" onchange="this.form.submit()">
                <option value="">كل الرحلات</option>
                <option value="active" @selected($view === 'active')>الرحلات النشطة</option>
                <option value="for-sale" @selected($view === 'for-sale')>تحتاج تسجيل البيع</option>
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>القارب</span>
            <select class="select" name="boat" onchange="this.form.submit()">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) request('boat') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.trips') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الرحلة</th><th>القارب</th><th>الكابتن</th><th>الميناء</th><th>الانطلاق</th><th>العودة</th><th>المعلن / المعدود</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td><a href="{{ route('panel.owner.trips.show', $trip) }}" class="num" style="font-weight:700">{{ $trip->trip_number }}</a></td>
                        <td>{{ $trip->boat?->name }}</td>
                        <td>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }}</td>
                        <td>{{ $trip->departurePort?->name }}</td>
                        <td class="num" style="font-size:.74rem">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="num" style="font-size:.74rem">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg) : '—' }} / {{ $trip->actual_weight_kg !== null ? number_format($trip->actual_weight_kg) : '—' }}</td>
                        <td>@include('panel.owner.partials.trip-badges', ['trip' => $trip])</td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                @if ($trip->canSell())
                                    <a href="{{ route('panel.owner.sales.create', ['trip' => $trip->id]) }}" class="btn btn-primary" style="padding:.3rem .6rem;font-size:.72rem">بيع</a>
                                    <a href="{{ route('panel.owner.consignments.create', ['trip' => $trip->id]) }}" class="btn btn-outline" style="padding:.3rem .6rem;font-size:.72rem">للدلال</a>
                                @endif
                                <a href="{{ route('panel.owner.trips.show', $trip) }}" class="icon-action" title="التفاصيل">@include('partials.icon', ['name' => 'chevron-left'])</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا رحلات مطابقة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $trips])

    <div class="drawer-overlay" id="tripDrawer-overlay" onclick="toggleDrawer('tripDrawer', false)"></div>
    <div class="drawer" id="tripDrawer">
        <div class="drawer-head">
            <h3 id="tripFormTitle">رحلة جديدة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('tripDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="tripFormEl" action="{{ route('panel.owner.trips.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <div class="form-grid cols-2">
                <label class="field"><span>القارب *</span>
                    <select class="select" name="boat_id" required onchange="syncBoatDefaults()">
                        <option value="">— اختر —</option>
                        @foreach ($boats as $b)<option value="{{ $b->id }}" data-port="{{ $b->port_id }}" data-captain="{{ $b->captain_id }}" data-crew="{{ $b->crew_count }}" data-license="{{ $b->license_number }}">{{ $b->name }}@if ($b->captainUser) — {{ $b->captainUser->name }}@endif</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الكابتن</span>
                    <select class="select" name="captain_id">
                        <option value="">— كابتن القارب —</option>
                        @foreach ($captains as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>ميناء المغادرة</span>
                    <select class="select" name="departure_port_id">
                        <option value="">— ميناء القارب —</option>
                        @foreach ($ports as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>ميناء العودة</span>
                    <select class="select" name="return_port_id">
                        <option value="">— نفس المغادرة —</option>
                        @foreach ($ports as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>نوع التصريح</span>
                    <select class="select" name="trip_type_id">
                        <option value="">—</option>
                        @foreach ($tripTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>أداة الصيد</span>
                    <select class="select" name="gear_type">
                        <option value="">—</option>
                        @foreach ($gearTypes as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>وقت الانطلاق المخطط</span><input class="input" name="departure_time" type="datetime-local" dir="ltr"></label>
                <label class="field"><span>مدة الرحلة (أيام)</span><input class="input" name="planned_days" type="number" min="1" max="90" dir="ltr"></label>
                <label class="field"><span>عدد الطاقم</span><input class="input" name="crew_count" type="number" min="0" dir="ltr"></label>
                <label class="field"><span>رقم الرخصة</span><input class="input" name="license_number" dir="ltr"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('tripDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">إنشاء وإسناد</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const tripForm = { drawer: 'tripDrawer', form: 'tripFormEl', title: 'tripFormTitle', storeUrl: @json(route('panel.owner.trips.store')), createTitle: 'رحلة جديدة', editTitle: 'تعديل الرحلة' };

    // اختيار القارب يملأ الكابتن والطاقم والرخصة من بياناته ما لم تُكتب.
    function syncBoatDefaults() {
        const form = document.getElementById('tripFormEl');
        const opt = form.boat_id.options[form.boat_id.selectedIndex];
        if (!opt || !opt.value) return;
        if (!form.captain_id.value) form.captain_id.value = opt.dataset.captain || '';
        if (!form.crew_count.value) form.crew_count.value = opt.dataset.crew || '';
        if (!form.license_number.value) form.license_number.value = opt.dataset.license || '';
    }

    @if ($errors->any() && old('boat_id') !== null)
        openDrawerForm(tripForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
