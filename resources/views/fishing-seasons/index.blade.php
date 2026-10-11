@extends('layouts.app')

@section('title', 'مواسم الصيد')

@php
    $approvalPill = fn ($status) => ['منشور' => 'pill-emerald', 'معتمد' => 'pill-sky', 'قيد المراجعة' => 'pill-amber', 'مرفوض' => 'pill-rose'][$status] ?? 'pill-slate';
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calendar'])</div>
            <div>
                <h1>مواسم الصيد</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openSeasonForm()">@include('partials.icon', ['name' => 'plus']) إضافة موسم جديد</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'مواسم مفتوحة', 'value' => $stats['open'], 'unit' => 'من '.$stats['total'], 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'إجمالي المواسم', 'value' => $stats['total'], 'icon' => 'calendar', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'قيد المراجعة', 'value' => $stats['pending'], 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'الرخص الممنوحة', 'value' => number_format($stats['issued']), 'icon' => 'ticket', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'الرخص النشطة', 'value' => number_format($stats['active']), 'icon' => 'shield-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'السعة القصوى', 'value' => number_format($stats['max']), 'icon' => 'activity', 'tone' => 'primary'])
    </div>

    <div class="workflow-grid" style="margin-bottom:1.25rem">
        @foreach ([['1', 'إضافة الموسم', 'الموظف يدخل بيانات الموسم والقرار والرخص', 'plus'], ['2', 'حفظ كمسودة', 'يمكن تعديل البيانات قبل إرسالها للمراجعة', 'save'], ['3', 'المراجعة والاعتماد', 'المسؤول المختص يراجع ويعتمد الموسم', 'badge-check'], ['4', 'النشر', 'يظهر الموسم للقوارب والرحلات والتنبيهات', 'megaphone']] as [$n, $title, $desc, $icon])
            <div class="card">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;color:hsl(var(--primary))">
                    <span style="display:flex;height:1.75rem;width:1.75rem;align-items:center;justify-content:center;border-radius:9999px;background:hsl(var(--primary)/.1);font-size:.72rem;font-weight:700">{{ $n }}</span>
                    @include('partials.icon', ['name' => $icon])
                </div>
                <h3 style="font-size:.875rem;font-weight:700">{{ $title }}</h3>
                <p style="margin-top:.25rem;font-size:.72rem;line-height:1.7;color:hsl(var(--muted-foreground))">{{ $desc }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>البحر</span>
            <select class="select" name="sea" onchange="this.form.submit()">
                <option value="">كل البحار</option>
                @foreach (['البحر الأحمر', 'الخليج العربي'] as $sea)
                    <option value="{{ $sea }}" @selected(request('sea') === $sea)>{{ $sea }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>الحالة الحالية</span>
            <select class="select" name="now" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach (['مفتوح', 'مغلق'] as $s)
                    <option value="{{ $s }}" @selected(request('now') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>حالة الاعتماد</span>
            <select class="select" name="approval" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['مسودة', 'قيد المراجعة', 'معتمد', 'منشور', 'مرفوض'] as $a)
                    <option value="{{ $a }}" @selected(request('approval') === $a)>{{ $a }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('fishing-seasons') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @if ($seasons->isNotEmpty())
        <div class="table-card" style="margin-bottom:1.25rem">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الموسم</th>
                        @foreach ($months as $m)<th style="text-align:center;white-space:nowrap">{{ $m }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($seasons as $season)
                        <tr>
                            <td style="font-weight:600;white-space:nowrap">{{ $season->name }}</td>
                            @foreach (range(1, 12) as $m)
                                @php
                                    $start = (int) $season->start_month; $end = (int) $season->end_month;
                                    $inWindow = $start && $end && ($start <= $end ? $m >= $start && $m <= $end : $m >= $start || $m <= $end);
                                @endphp
                                <td style="text-align:center;padding:.35rem">
                                    <span style="display:inline-block;height:.9rem;width:100%;min-width:1.2rem;border-radius:.25rem;background:{{ $inWindow ? ($season->sea === 'البحر الأحمر' ? '#fda4af' : '#7dd3fc') : 'hsl(var(--muted))' }};{{ $m === now()->month ? 'outline:2px solid hsl(var(--primary));' : '' }}"></span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="table-card" style="margin-bottom:1.25rem">
        <table class="data-table">
            <thead>
                <tr><th>الموسم</th><th>النوع</th><th>البحر</th><th>الفترة</th><th style="text-align:center">الرخص</th><th style="text-align:center">الاعتماد</th><th style="text-align:center">الموسم الآن</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($seasons as $season)
                    <tr>
                        <td><div style="font-weight:600">{{ $season->name }}</div><div style="font-size:11px;color:hsl(var(--muted-foreground))">{{ $season->decision_number ? 'قرار '.$season->decision_number : 'بدون رقم قرار' }}</div></td>
                        <td>{{ $season->species }}</td>
                        <td>{{ $season->sea }}</td>
                        <td>{{ $months[$season->start_month - 1] ?? '—' }} — {{ $months[$season->end_month - 1] ?? '—' }}</td>
                        <td style="text-align:center">{{ number_format($season->licenses_issued) }}{{ $season->licenses_max ? ' / '.number_format($season->licenses_max) : '' }}</td>
                        <td style="text-align:center"><span class="pill {{ $approvalPill($season->approval_status) }}">{{ $season->approval_status }}</span></td>
                        <td style="text-align:center"><span class="badge {{ $season->isOpenNow() ? 'badge-ok' : 'badge-warn' }}">{{ $season->isOpenNow() ? 'مفتوح' : 'مغلق' }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openSeasonForm(@json($season))'>@include('partials.icon', ['name' => 'pencil'])</button>
                                @if (in_array($season->approval_status, ['مسودة', 'مرفوض']))
                                    <form method="POST" action="{{ route('fishing-seasons.status', $season) }}">@csrf<input type="hidden" name="approval_status" value="قيد المراجعة"><button class="icon-action" title="إرسال للمراجعة">@include('partials.icon', ['name' => 'send'])</button></form>
                                @endif
                                @if ($season->approval_status === 'قيد المراجعة')
                                    <form method="POST" action="{{ route('fishing-seasons.status', $season) }}">@csrf<input type="hidden" name="approval_status" value="معتمد"><button class="icon-action" title="اعتماد">@include('partials.icon', ['name' => 'badge-check'])</button></form>
                                @endif
                                @if ($season->approval_status === 'معتمد')
                                    <form method="POST" action="{{ route('fishing-seasons.status', $season) }}">@csrf<input type="hidden" name="approval_status" value="منشور"><button class="icon-action" title="نشر">@include('partials.icon', ['name' => 'megaphone'])</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مواسم مطابقة للفلترة الحالية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid-2">
        @foreach ($seasons as $season)
            @php
                $open = $season->isOpenNow();
                $win = $season->nextWindow();
                $fill = $season->licenses_max ? min(100, round($season->licenses_issued / $season->licenses_max * 100)) : 0;
                $remaining = max(0, $season->licenses_max - $season->licenses_issued);
            @endphp
            <div class="card" @if ($open) style="border-color:#a7f3d0;box-shadow:0 0 0 1px #d1fae5" @endif>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem">
                    <div style="display:flex;align-items:flex-start;gap:.75rem">
                        <div style="border-radius:.5rem;padding:.625rem;background:{{ $season->sea === 'البحر الأحمر' ? '#fff1f2' : '#f0f9ff' }};color:{{ $season->sea === 'البحر الأحمر' ? '#e11d48' : '#0284c7' }}">@include('partials.icon', ['name' => 'waves'])</div>
                        <div>
                            <h3 style="font-weight:700">{{ $season->name }}</h3>
                            <p style="margin-top:.125rem;font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $season->species }} · {{ $season->sea }}{{ $season->region ? ' · '.$season->region : '' }}</p>
                        </div>
                    </div>
                    <span class="pill {{ $approvalPill($season->approval_status) }}">{{ $season->approval_status }}</span>
                </div>
                <div class="mini-grid" style="grid-template-columns:repeat(4,1fr)">
                    <div class="mini"><div><p class="m-label">البداية</p><p class="m-value">{{ $season->start_date?->format('Y-m-d') ?? ($months[$season->start_month - 1] ?? '—') }}</p></div></div>
                    <div class="mini"><div><p class="m-label">النهاية</p><p class="m-value">{{ $season->end_date?->format('Y-m-d') ?? ($months[$season->end_month - 1] ?? '—') }}</p></div></div>
                    <div class="mini"><div><p class="m-label">الرخص النشطة</p><p class="m-value">{{ number_format($season->licenses_active) }}</p></div></div>
                    <div class="mini"><div><p class="m-label">المتبقي</p><p class="m-value">{{ number_format($remaining) }}</p></div></div>
                </div>
                @if ($season->licenses_max)
                    <div style="margin-top:.75rem">
                        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:hsl(var(--muted-foreground))"><span>استخدام سعة الرخص</span><span>{{ $fill }}%</span></div>
                        <div class="progress" style="margin-top:.35rem"><div style="width:{{ $fill }}%;background:{{ $fill >= 90 ? '#f43f5e' : ($fill >= 60 ? '#f59e0b' : '#0284c7') }}"></div></div>
                    </div>
                @endif
                <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:1rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
                    @if ($season->license_type)<span>🎫 {{ $season->license_type }}</span>@endif
                    <span>⛵ {{ number_format($season->boats_count) }} قارب</span>
                    @if ($season->decision_number)<span>📄 قرار {{ $season->decision_number }}</span>@endif
                </div>
                <div style="margin-top:.75rem;display:flex;align-items:center;gap:.4rem;border-radius:.5rem;padding:.5rem .75rem;font-size:.72rem;font-weight:500;background:{{ $open ? '#ecfdf5' : '#fffbeb' }};color:{{ $open ? '#047857' : '#b45309' }}">
                    @include('partials.icon', ['name' => 'clock'])
                    {{ $open ? ($win['months'] > 0 ? 'يغلق خلال '.$win['months'].($win['months'] === 1 ? ' شهر' : ' أشهر') : 'يغلق خلال هذا الشهر') : ($win['months'] === 0 ? 'يفتح خلال هذا الشهر' : 'يفتح خلال '.$win['months'].($win['months'] === 1 ? ' شهر' : ' أشهر')) }}
                </div>
                @if ($season->status === 'موقوف مؤقتاً')
                    <div style="margin-top:.5rem;display:flex;align-items:center;gap:.4rem;border-radius:.5rem;background:#fff1f2;padding:.5rem .75rem;font-size:.72rem;font-weight:500;color:#be123c">@include('partials.icon', ['name' => 'alert-triangle']) الموسم موقوف مؤقتاً بقرار تنظيمي</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="drawer-overlay" id="seasonDrawer-overlay" onclick="toggleDrawer('seasonDrawer', false)"></div>
    <div class="drawer wide" id="seasonDrawer">
        <div class="drawer-head">
            <div>
                <h3 id="seasonFormTitle">إضافة موسم صيد جديد</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">أدخل القرار التنظيمي والفترة والرخص ثم احفظ كمسودة أو أرسله للمراجعة.</p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('seasonDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="seasonForm" action="{{ route('fishing-seasons.store') }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="seasonMethod" value="POST">
            <input type="hidden" name="approval_status" id="s-approval" value="مسودة">
            <div class="card">
                <p class="card-title" style="margin-bottom:.75rem">بيانات الموسم</p>
                <div class="form-grid">
                    <label class="field"><span>اسم الموسم *</span><input class="input" name="name" id="s-name" required placeholder="مثال: موسم صيد الروبيان"></label>
                    <label class="field"><span>النوع المستهدف *</span><input class="input" name="species" id="s-species" required placeholder="روبيان"></label>
                    <label class="field"><span>البحر</span><select class="select" name="sea" id="s-sea"><option>البحر الأحمر</option><option>الخليج العربي</option></select></label>
                    <label class="field"><span>المنطقة</span><input class="input" name="region" id="s-region" placeholder="المملكة أو منطقة محددة"></label>
                    <label class="field"><span>شهر البداية *</span><select class="select" name="start_month" id="s-start" required><option value="">اختر</option>@foreach ($months as $i => $m)<option value="{{ $i + 1 }}">{{ $m }}</option>@endforeach</select></label>
                    <label class="field"><span>شهر النهاية *</span><select class="select" name="end_month" id="s-end" required><option value="">اختر</option>@foreach ($months as $i => $m)<option value="{{ $i + 1 }}">{{ $m }}</option>@endforeach</select></label>
                    <label class="field"><span>تاريخ بداية دقيق</span><input class="input" type="date" name="start_date" id="s-sdate"></label>
                    <label class="field"><span>تاريخ نهاية دقيق</span><input class="input" type="date" name="end_date" id="s-edate"></label>
                </div>
            </div>
            <div class="card">
                <p class="card-title" style="margin-bottom:.75rem">الرخص والقوارب</p>
                <div class="form-grid">
                    <label class="field"><span>السعة القصوى للرخص</span><input class="input" type="number" min="0" name="licenses_max" id="s-max"></label>
                    <label class="field"><span>الرخص الممنوحة</span><input class="input" type="number" min="0" name="licenses_issued" id="s-issued"></label>
                    <label class="field"><span>الرخص النشطة</span><input class="input" type="number" min="0" name="licenses_active" id="s-active"></label>
                    <label class="field"><span>عدد القوارب المرخصة</span><input class="input" type="number" min="0" name="boats_count" id="s-boats"></label>
                    <label class="field"><span>نوع الرخص</span><input class="input" name="license_type" id="s-ltype" placeholder="مثال: صيد حرفي / تجاري"></label>
                    <label class="field"><span>أداة الصيد المسموحة</span><input class="input" name="gear_type" id="s-gear"></label>
                    <label class="field"><span>الحد الأدنى للحجم (سم)</span><input class="input" type="number" min="0" name="min_size_cm" id="s-minsize"></label>
                    <label class="field"><span>حالة الموسم التشغيلية</span><select class="select" name="status" id="s-status"><option>مغلق</option><option>مفتوح</option><option>موقوف مؤقتاً</option></select></label>
                    <label class="field wide"><span>مناطق الصيد المسموحة</span><textarea class="input" rows="2" name="allowed_areas" id="s-allowed"></textarea></label>
                    <label class="field wide"><span>المناطق المحظورة</span><textarea class="input" rows="2" name="prohibited_areas" id="s-prohibited"></textarea></label>
                </div>
            </div>
            <div class="card">
                <p class="card-title" style="margin-bottom:.75rem">القرار الرسمي</p>
                <div class="form-grid">
                    <label class="field"><span>الجهة المنظمة</span><input class="input" name="authority" id="s-authority" value="وزارة البيئة والمياه والزراعة"></label>
                    <label class="field"><span>رقم القرار / التعميم</span><input class="input" name="decision_number" id="s-decision"></label>
                    <label class="field"><span>تاريخ القرار</span><input class="input" type="date" name="decision_date" id="s-ddate"></label>
                    <label class="field"><span>رابط ملف القرار PDF</span><input class="input" name="decision_document_url" id="s-durl" placeholder="https://..."></label>
                    <label class="field wide"><span>ملاحظات</span><textarea class="input" rows="2" name="notes" id="s-notes"></textarea></label>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem">
                <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">يمكن حفظ السجل كمسودة، ثم إرساله لاحقاً للمراجعة والاعتماد.</span>
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="btn btn-outline" onclick="document.getElementById('s-approval').value = 'مسودة'">@include('partials.icon', ['name' => 'save']) حفظ كمسودة</button>
                    <button type="submit" class="btn btn-primary" onclick="document.getElementById('s-approval').value = 'قيد المراجعة'">@include('partials.icon', ['name' => 'send']) إرسال للمراجعة</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const seasonStoreUrl = @json(route('fishing-seasons.store'));

    function openSeasonForm(season = null) {
        const form = document.getElementById('seasonForm');
        document.getElementById('seasonFormTitle').textContent = season ? 'تعديل موسم الصيد' : 'إضافة موسم صيد جديد';
        document.getElementById('seasonMethod').value = season ? 'PUT' : 'POST';
        form.action = season ? seasonStoreUrl + '/' + season.id : seasonStoreUrl;
        const set = (id, v) => document.getElementById(id).value = v ?? '';
        set('s-name', season?.name); set('s-species', season?.species); set('s-sea', season?.sea ?? 'البحر الأحمر');
        set('s-region', season?.region); set('s-start', season?.start_month); set('s-end', season?.end_month);
        set('s-sdate', season?.start_date?.slice(0, 10)); set('s-edate', season?.end_date?.slice(0, 10));
        set('s-max', season?.licenses_max); set('s-issued', season?.licenses_issued); set('s-active', season?.licenses_active);
        set('s-boats', season?.boats_count); set('s-ltype', season?.license_type); set('s-gear', season?.gear_type);
        set('s-minsize', season?.min_size_cm); set('s-status', season?.status ?? 'مغلق');
        set('s-allowed', season?.allowed_areas); set('s-prohibited', season?.prohibited_areas);
        set('s-authority', season?.authority ?? 'وزارة البيئة والمياه والزراعة'); set('s-decision', season?.decision_number);
        set('s-ddate', season?.decision_date?.slice(0, 10)); set('s-durl', season?.decision_document_url); set('s-notes', season?.notes);
        document.getElementById('s-approval').value = season?.approval_status ?? 'مسودة';
        toggleDrawer('seasonDrawer', true);
    }
</script>
@endpush