@extends('layouts.app')

@section('title', 'خدمات الصيادين')

@php
    $statusBadge = [
        'جديدة' => 'badge-info',
        'قيد المعالجة' => 'badge-warn',
        'بحاجة مستندات' => 'badge-warn',
        'بانتظار الاعتماد' => 'badge-info',
        'معتمدة' => 'badge-ok',
        'مرفوضة' => 'badge-danger',
    ];
    $filters = request()->only('q', 'status', 'type');
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'headset'])</div>
            <div>
                <h1>خدمات الصيادين</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openRequestForm()">@include('partials.icon', ['name' => 'file-plus']) طلب جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الطلبات', 'value' => $stats['total'], 'icon' => 'file-text', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'طلبات جديدة', 'value' => $stats['new'], 'icon' => 'clock', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'قيد المعالجة', 'value' => $stats['inProgress'], 'icon' => 'refresh-cw', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => $stats['approval'], 'icon' => 'shield-check', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'معتمدة', 'value' => $stats['approved'], 'icon' => 'badge-check', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="search" name="q" value="{{ $query }}" placeholder="اسم الصياد، رقم الهوية، رقم الطلب، الميناء..."></label>
        <label class="field"><span>الخدمة</span>
            <select class="select" name="type" onchange="this.form.submit()">
                <option value="">كل الخدمات</option>
                @foreach ($types as $type)<option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('services.fisher-services') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @forelse ($requests as $item)
        @php
            // حمولة النافذة تُبنى هنا لا داخل السمة: @json بمصفوفة متعددة الأسطر
            // داخل onclick لا يُصرَّف صحيحًا.
            $payload = [
                'id' => $item->id,
                'number' => $item->request_number,
                'service' => $item->serviceType->name,
                'fisher' => $item->fisher_name,
                'staff' => $item->assigned_staff_id,
                'license' => $item->new_license_number,
                'expiry' => $item->new_license_expiry?->toDateString(),
                'resolution' => $item->resolution,
            ];
        @endphp
        <div class="alert-row" style="border-right-color:hsl(var(--primary) / .4)">
            <div class="a-icon" style="background:hsl(var(--primary) / .1);color:hsl(var(--primary))">
                @include('partials.icon', ['name' => $item->serviceType->icon])
            </div>
            <div style="min-width:0;flex:1">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                    <span style="font-family:monospace;font-size:.72rem;font-weight:700;color:hsl(var(--muted-foreground))">{{ $item->request_number }}</span>
                    <h3 style="font-size:.875rem;font-weight:700">{{ $item->serviceType->name }}</h3>
                    <span class="badge {{ $statusBadge[$item->status] ?? 'badge-info' }}">{{ $item->status }}</span>
                    @if ($item->priority === 'عاجلة')<span class="pill pill-rose">عاجلة</span>@endif
                    <span class="pill {{ $item->nationality_type === 'سعودي' ? 'pill-emerald' : 'pill-sky' }}">
                        {{ $item->nationality_type }}@if ($item->nationality_type === 'أجنبي' && $item->nationality) · {{ $item->nationality }}@endif
                    </span>
                </div>
                <p style="margin-top:.375rem;font-size:.85rem">
                    {{ $item->fisher_name }}@if ($item->national_id) <span style="color:hsl(var(--muted-foreground))">· هوية: {{ $item->national_id }}</span>@endif
                </p>
                <div class="alert-meta">
                    @if ($item->port)<span>الميناء: {{ $item->port->name }}</span>@endif
                    @if ($item->regionName())<span>المنطقة: {{ $item->regionName() }}</span>@endif
                    @if ($item->license_number)<span>الرخصة: {{ $item->license_number }}</span>@endif
                    @if ($item->fishingSeason)<span>الموسم: {{ $item->fishingSeason->name }}</span>@endif
                    @if ($item->boat)<span>القارب: {{ $item->boat->name }}</span>@endif
                    <span>التقديم: {{ $item->submitted_date?->toDateString() ?? '—' }}</span>
                    @if ($item->assignedStaff)<span>المعالج: {{ $item->assignedStaff->name }}</span>@endif
                    @if ($item->approved_by)<span>اعتمد: {{ $item->approved_by }}@if ($item->approved_at) · {{ $item->approved_at->toDateString() }}@endif</span>@endif
                </div>
                @if ($item->description)<p style="margin-top:.375rem;font-size:.78rem;color:hsl(var(--muted-foreground))">{{ $item->description }}</p>@endif
                @if ($item->new_license_number)
                    <p style="margin-top:.375rem;font-size:.78rem;font-weight:600;color:#047857">
                        الرخصة {{ $item->status === 'معتمدة' ? 'الصادرة' : 'المقترحة' }}: {{ $item->new_license_number }}@if ($item->new_license_expiry) (تنتهي {{ $item->new_license_expiry->toDateString() }})@endif
                    </p>
                @endif
                @if ($item->resolution)<p style="margin-top:.25rem;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $item->resolution }}</p>@endif
            </div>
            <div style="display:flex;flex-shrink:0;flex-direction:column;gap:.375rem">
                @if ($item->isOpen())
                    <button type="button" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem" onclick='openProcessForm(@json($payload))'>
                        @include('partials.icon', ['name' => 'file-check']) معالجة
                    </button>
                @endif
                @if ($item->status === 'بانتظار الاعتماد')
                    <button type="button" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem;border-color:#ddd6fe;background:#f5f3ff;color:#6d28d9" onclick='openDecideForm(@json($payload))'>
                        @include('partials.icon', ['name' => 'shield-check']) اعتماد
                    </button>
                @endif
                @if ($item->status === 'معتمدة')
                    <a href="{{ route('services.fisher-services.license', $item) }}" target="_blank" class="btn btn-outline" style="padding:.35rem .6rem;font-size:.72rem;border-color:#a7f3d0;background:#ecfdf5;color:#047857">
                        @include('partials.icon', ['name' => 'printer']) طباعة الرخصة
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'file-text'])
            <h3>لا توجد طلبات مطابقة</h3>
            <p>اضغط «طلب جديد» لتسجيل طلب خدمة، أو ألغِ التصفية لعرض الطلبات كاملة.</p>
        </div>
    @endforelse

    <div class="note-box">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">فصل المعالجة عن الاعتماد</p>
            <p class="n-body">المعالج يقترح رقم الرخصة وتاريخ انتهائها، ولا تصير رخصة صادرة إلا بتوقيع مسؤول مختص في خطوة الاعتماد. لذلك لا تظهر «معتمدة» بين حالات المعالجة، ولا يُعاد فتح طلب أُغلق.</p>
        </div>
    </div>

    {{-- طلب جديد --}}
    <div class="drawer-overlay" id="requestDrawer-overlay" onclick="toggleDrawer('requestDrawer', false)"></div>
    <div class="drawer wide" id="requestDrawer">
        <div class="drawer-head">
            <div>
                <h3>طلب خدمة جديد — <span id="r-number">{{ $nextNumber }}</span></h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">اختيار صياد مسجّل يملأ رقم هويته ورخصته وميناءه تلقائيًا.</p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('requestDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('services.fisher-services.store', $filters) }}" class="drawer-body">
            @csrf
            <div class="form-grid">
                <label class="field wide"><span>نوع الجنسية</span>
                    <select class="select" name="nationality_type" id="r-nationality-type" onchange="syncNationality()">
                        @foreach ($nationalityTypes as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide" id="r-nationality-wrap" style="display:none"><span>الجنسية</span>
                    <input class="input" name="nationality" id="r-nationality" placeholder="دولة الجنسية...">
                </label>
                <label class="field wide"><span>الصياد *</span>
                    <input class="input" name="fisher_name" id="r-fisher-name" list="fishers-list" required onchange="pickFisher(this.value)" autocomplete="off">
                    <datalist id="fishers-list">
                        @foreach ($fishers as $fisher)<option value="{{ $fisher->name }}"></option>@endforeach
                    </datalist>
                    <input type="hidden" name="fisher_id" id="r-fisher-id">
                </label>
                <label class="field"><span>رقم الهوية</span><input class="input" name="national_id" id="r-national-id"></label>
                <label class="field"><span>رقم الجوال</span><input class="input" name="phone" id="r-phone"></label>
                <label class="field"><span>فصيلة الدم</span><input class="input" name="blood_type" placeholder="مثل: O+"></label>
                <label class="field"><span>تاريخ الميلاد</span><input class="input" type="date" name="birth_date"></label>
                <label class="field"><span>المهنة</span><input class="input" name="profession" placeholder="صياد سمك"></label>
                <label class="field"><span>صاحب العمل</span><input class="input" name="employer"></label>
                <label class="field"><span>نوع الخدمة *</span>
                    <select class="select" name="fisher_service_type_id" id="r-service" required onchange="syncSeason()">
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" data-season="{{ $type->requires_season ? '1' : '0' }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>الأولوية</span>
                    <select class="select" name="priority">
                        @foreach ($priorities as $priority)<option value="{{ $priority }}">{{ $priority }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide" id="r-season-wrap" style="display:none"><span>موسم الصيد *</span>
                    <select class="select" name="fishing_season_id" id="r-season">
                        <option value="">— اختر الموسم —</option>
                        @foreach ($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم الرخصة الحالية</span><input class="input" name="license_number" id="r-license"></label>
                <label class="field"><span>الميناء</span>
                    <select class="select" name="port_id" id="r-port">
                        <option value="">—</option>
                        @foreach ($ports as $port)<option value="{{ $port->id }}">{{ $port->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">—</option>
                        @foreach ($boats as $boat)<option value="{{ $boat->id }}">{{ $boat->name }} — {{ $boat->boat_number }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المركز</span><input class="input" name="center"></label>
                <label class="field wide"><span>تفاصيل الطلب</span><textarea class="input" rows="3" name="description" placeholder="سبب الطلب أو التفاصيل الإضافية..."></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('requestDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">تقديم الطلب</button>
            </div>
        </form>
    </div>

    {{-- معالجة --}}
    <div class="drawer-overlay" id="processDrawer-overlay" onclick="toggleDrawer('processDrawer', false)"></div>
    <div class="drawer" id="processDrawer">
        <div class="drawer-head">
            <div>
                <h3>معالجة الطلب — <span id="p-number"></span></h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))" id="p-summary"></p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('processDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="processForm" class="drawer-body">
            @csrf
            <div class="form-grid">
                <label class="field"><span>الحالة</span>
                    <select class="select" name="status" id="p-status" onchange="syncProposal()">
                        @foreach ($processingStatuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المسؤول المعالج</span>
                    <select class="select" name="assigned_staff_id" id="p-staff">
                        <option value="">— غير مسند —</option>
                        @foreach ($staff as $member)<option value="{{ $member->id }}">{{ $member->name }} · {{ $member->role }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide" id="p-license-wrap"><span>رقم الرخصة المقترحة</span><input class="input" name="new_license_number" id="p-license"></label>
                <label class="field wide" id="p-expiry-wrap"><span>انتهاء الرخصة المقترحة</span><input class="input" type="date" name="new_license_expiry" id="p-expiry"></label>
                <label class="field wide"><span>نتيجة المعالجة</span><textarea class="input" rows="2" name="resolution" id="p-resolution" placeholder="ملخص الإجراء المتخذ..."></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('processDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ المعالجة</button>
            </div>
        </form>
    </div>

    {{-- اعتماد --}}
    <div class="drawer-overlay" id="decideDrawer-overlay" onclick="toggleDrawer('decideDrawer', false)"></div>
    <div class="drawer" id="decideDrawer">
        <div class="drawer-head">
            <div>
                <h3>اعتماد الطلب — <span id="d-number"></span></h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))" id="d-summary"></p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('decideDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="decideForm" class="drawer-body">
            @csrf
            <div class="form-grid">
                <label class="field wide"><span>القرار</span>
                    <select class="select" name="decision" id="d-decision">
                        <option value="اعتماد">اعتماد وإصدار الرخصة</option>
                        <option value="رفض">رفض الطلب</option>
                    </select>
                </label>
                <label class="field wide"><span>اسم المسؤول المختص (التوقيع)</span>
                    <input class="input" name="approved_by" id="d-approved-by" placeholder="الاسم الرسمي للمسؤول">
                </label>
                <label class="field wide"><span>ملاحظة القرار</span><textarea class="input" rows="2" name="note" placeholder="ملاحظة اختيارية..."></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('decideDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ القرار</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@php
    $fisherIndex = $fishers->mapWithKeys(fn ($fisher) => [$fisher->name => [
        'id' => $fisher->id,
        'national_id' => $fisher->national_id,
        'license_number' => $fisher->license_number,
        'port_id' => $fisher->port_id,
    ]]);
@endphp
<script>
    const fisherIndex = @json($fisherIndex);
    const processUrl = @json(route('services.fisher-services.process', ['serviceRequest' => '__ID__'] + $filters));
    const decideUrl = @json(route('services.fisher-services.decide', ['serviceRequest' => '__ID__'] + $filters));
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value ?? ''; };

    function openRequestForm() {
        syncNationality();
        syncSeason();
        toggleDrawer('requestDrawer', true);
    }

    // اختيار صياد مسجّل يملأ ما هو معروف عنه، وإدخال اسم غير مسجّل يترك الحقول
    // كما هي بدل مسح ما كتبه الموظف.
    function pickFisher(name) {
        const fisher = fisherIndex[name];
        document.getElementById('r-fisher-id').value = fisher ? fisher.id : '';
        if (!fisher) return;
        set('r-national-id', fisher.national_id);
        set('r-license', fisher.license_number);
        set('r-port', fisher.port_id);
    }

    function syncNationality() {
        const foreign = document.getElementById('r-nationality-type').value === 'أجنبي';
        document.getElementById('r-nationality-wrap').style.display = foreign ? '' : 'none';
        if (!foreign) set('r-nationality', '');
    }

    function syncSeason() {
        const option = document.getElementById('r-service').selectedOptions[0];
        const needed = option && option.dataset.season === '1';
        document.getElementById('r-season-wrap').style.display = needed ? '' : 'none';
        if (!needed) set('r-season', '');
    }

    function openProcessForm(request) {
        document.getElementById('processForm').action = processUrl.replace('__ID__', request.id);
        document.getElementById('p-number').textContent = request.number;
        document.getElementById('p-summary').textContent = request.service + ' — ' + request.fisher;
        set('p-status', 'قيد المعالجة');
        set('p-staff', request.staff);
        set('p-license', request.license);
        set('p-expiry', request.expiry);
        set('p-resolution', request.resolution);
        syncProposal();
        toggleDrawer('processDrawer', true);
    }

    // مقترح الرخصة لا يُطلب إلا عند رفع الطلب للاعتماد.
    function syncProposal() {
        const pending = document.getElementById('p-status').value === 'بانتظار الاعتماد';
        document.getElementById('p-license-wrap').style.display = pending ? '' : 'none';
        document.getElementById('p-expiry-wrap').style.display = pending ? '' : 'none';
    }

    function openDecideForm(request) {
        document.getElementById('decideForm').action = decideUrl.replace('__ID__', request.id);
        document.getElementById('d-number').textContent = request.number;
        document.getElementById('d-summary').textContent = request.service + ' — ' + request.fisher
            + (request.license ? ' · الرخصة المقترحة: ' + request.license : '');
        set('d-decision', 'اعتماد');
        set('d-approved-by', '');
        toggleDrawer('decideDrawer', true);
    }
</script>
@endpush
