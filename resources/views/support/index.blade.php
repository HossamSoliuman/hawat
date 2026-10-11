@extends('layouts.app')

@section('title', 'الدعم الفني')

@php
    $statusBadge = [
        'جديدة' => 'badge-info',
        'قيد المعالجة' => 'badge-warn',
        'بانتظار رد مقدم الطلب' => 'badge-warn',
        'تم الحل' => 'badge-ok',
        'مغلقة' => 'badge-ok',
    ];
    $categoryIcon = [
        'مشكلة تقنية' => 'alert-octagon',
        'استفسار' => 'search',
        'طلب تعديل بيانات' => 'file-edit',
        'اقتراح تطوير' => 'sparkles',
        'صلاحيات الوصول' => 'shield-check',
        'أخرى' => 'inbox',
    ];
    $filters = request()->only('status', 'category');
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'life-buoy'])</div>
            <div>
                <h1>الدعم الفني</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="toggleDrawer('ticketDrawer', true)">@include('partials.icon', ['name' => 'file-plus']) تقديم طلب</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="flash" style="border-color:#fecdd3;background:#fff1f2;color:#be123c">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي التذاكر', 'value' => $stats['total'], 'icon' => 'inbox', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مفتوحة', 'value' => $stats['open'], 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'عاجلة مفتوحة', 'value' => $stats['urgent'], 'icon' => 'alert-triangle', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'بلا مسؤول', 'value' => $stats['unassigned'], 'icon' => 'alert-octagon', 'tone' => 'danger'])
        @include('partials.stat-card', ['label' => 'مغلقة', 'value' => $stats['resolved'], 'icon' => 'check-circle', 'tone' => 'success'])
    </div>

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card">
            <p class="card-title" style="display:flex;align-items:center;gap:.5rem">@include('partials.icon', ['name' => 'timer']) وقت الاستجابة</p>
            <p class="card-sub" style="margin-top:.375rem">خلال ٢٤ ساعة عمل من تاريخ التقديم</p>
        </div>
        <div class="card">
            <p class="card-title" style="display:flex;align-items:center;gap:.5rem">@include('partials.icon', ['name' => 'headset']) فريق دعم حوات</p>
            <p class="card-sub" style="margin-top:.375rem">support@hawat.gov.sa · الأحد–الخميس ٨ص–٤م</p>
        </div>
        <div class="card">
            <p class="card-title" style="display:flex;align-items:center;gap:.5rem">@include('partials.icon', ['name' => 'shield-check']) الخصوصية</p>
            <p class="card-sub" style="margin-top:.375rem">التذكرة مرئية لمقدّمها ولفريق الدعم وحدهما</p>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>التصنيف</span>
            <select class="select" name="category" onchange="this.form.submit()">
                <option value="">كل التصنيفات</option>
                @foreach ($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('services.support') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @forelse ($tickets as $ticket)
        <div class="alert-row" style="border-right-color:{{ $ticket->priority === 'عاجلة' ? '#fda4af' : '#7dd3fc' }}">
            <div class="a-icon" style="background:hsl(var(--primary) / .1);color:hsl(var(--primary))">
                @include('partials.icon', ['name' => $categoryIcon[$ticket->category] ?? 'inbox'])
            </div>
            <div style="min-width:0;flex:1">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                    <span style="font-family:monospace;font-size:.72rem;font-weight:700;color:hsl(var(--muted-foreground))">{{ $ticket->ticket_number }}</span>
                    <h3 style="font-size:.875rem;font-weight:700">{{ $ticket->subject }}</h3>
                    <span class="badge {{ $statusBadge[$ticket->status] ?? 'badge-info' }}">{{ $ticket->status }}</span>
                    @if ($ticket->priority === 'عاجلة')<span class="pill pill-rose">عاجلة</span>@endif
                    <span class="pill pill-slate">{{ $ticket->category }}</span>
                </div>
                <p style="margin-top:.375rem;font-size:.82rem;color:hsl(var(--muted-foreground))">{{ $ticket->description }}</p>
                <div class="alert-meta">
                    @if ($ticket->module)<span>الوحدة: {{ $ticket->module }}</span>@endif
                    @if ($ticket->submitted_by_name)<span>مقدّم الطلب: {{ $ticket->submitted_by_name }}</span>@endif
                    @if ($ticket->submitted_by_email)<span>{{ $ticket->submitted_by_email }}</span>@endif
                    @if ($ticket->contact_phone)<span>الجوال: {{ $ticket->contact_phone }}</span>@endif
                    <span>التقديم: {{ $ticket->submitted_at?->format('Y-m-d H:i') ?? '—' }}</span>
                    @if ($ticket->assignedStaff)<span>المسؤول: {{ $ticket->assignedStaff->name }}</span>@endif
                    @if ($ticket->resolved_at)<span>الإغلاق: {{ $ticket->resolved_at->format('Y-m-d H:i') }}</span>@endif
                </div>
                @if ($ticket->resolution)
                    <p style="margin-top:.375rem;font-size:.78rem;font-weight:600;color:#047857">الحل: {{ $ticket->resolution }}</p>
                @endif
            </div>
            @if ($ticket->isOpen())
                <div style="display:flex;flex-shrink:0;flex-direction:column;gap:.375rem;width:14rem">
                    <form method="POST" action="{{ route('services.support.assign', ['ticket' => $ticket] + $filters) }}" style="display:flex;gap:.375rem">
                        @csrf
                        <select class="select" name="assigned_staff_id" required style="flex:1;padding:.35rem .5rem;font-size:.75rem">
                            <option value="">— المسؤول —</option>
                            @foreach ($staff as $member)<option value="{{ $member->id }}" @selected($ticket->assigned_staff_id === $member->id)>{{ $member->name }}</option>@endforeach
                        </select>
                        <button type="submit" class="btn btn-outline" style="padding:.35rem .5rem;font-size:.7rem">@include('partials.icon', ['name' => 'user-check'])</button>
                    </form>
                    <form method="POST" action="{{ route('services.support.resolve', ['ticket' => $ticket] + $filters) }}" style="display:flex;flex-direction:column;gap:.375rem">
                        @csrf
                        <select class="select" name="status" style="padding:.35rem .5rem;font-size:.75rem">
                            @foreach ($statuses as $status)<option value="{{ $status }}" @selected($ticket->status === $status)>{{ $status }}</option>@endforeach
                        </select>
                        <input class="input" name="resolution" value="{{ $ticket->resolution }}" placeholder="الحل / الرد" style="padding:.35rem .5rem;font-size:.75rem">
                        <button type="submit" class="btn btn-outline" style="padding:.35rem .5rem;font-size:.7rem">
                            @include('partials.icon', ['name' => 'check-circle']) تحديث
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'life-buoy'])
            <h3>لا توجد تذاكر مطابقة</h3>
            <p>اضغط «تقديم طلب» لفتح تذكرة دعم، أو ألغِ التصفية لعرض التذاكر كاملة.</p>
        </div>
    @endforelse

    <div class="note-box">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">قاعدة الإغلاق</p>
            <p class="n-body">لا تُغلق تذكرة بلا حلّ مكتوب: من يقرأ السجل بعد شهر يحتاج أن يعرف بماذا انتهت المشكلة، لا أن حالتها تغيّرت.</p>
        </div>
    </div>

    <div class="drawer-overlay" id="ticketDrawer-overlay" onclick="toggleDrawer('ticketDrawer', false)"></div>
    <div class="drawer" id="ticketDrawer">
        <div class="drawer-head">
            <div>
                <h3>طلب دعم جديد — {{ $nextNumber }}</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">اذكر الوحدة المتأثرة وخطوات إعادة المشكلة لتصل التذكرة لمن يعالجها مباشرة.</p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('ticketDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" action="{{ route('services.support.store', $filters) }}" class="drawer-body">
            @csrf
            <div class="form-grid">
                <label class="field wide"><span>الموضوع *</span><input class="input" name="subject" required></label>
                <label class="field"><span>التصنيف *</span>
                    <select class="select" name="category">
                        @foreach ($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الأولوية</span>
                    <select class="select" name="priority">
                        @foreach ($priorities as $priority)<option value="{{ $priority }}">{{ $priority }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>الوحدة المتأثرة</span>
                    <select class="select" name="module">
                        <option value="">—</option>
                        @foreach ($modules as $module)<option value="{{ $module }}">{{ $module }}</option>@endforeach
                    </select>
                </label>
                <label class="field wide"><span>تفاصيل الطلب *</span><textarea class="input" rows="4" name="description" required placeholder="صف المشكلة وخطوات إعادتها، أو الاستفسار بدقة..."></textarea></label>
                <label class="field"><span>اسمك</span><input class="input" name="submitted_by_name"></label>
                <label class="field"><span>بريدك الإلكتروني</span><input class="input" type="email" name="submitted_by_email"></label>
                <label class="field wide"><span>رقم الجوال للتواصل</span><input class="input" name="contact_phone"></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid hsl(var(--border));padding-top:1rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('ticketDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">إرسال الطلب</button>
            </div>
        </form>
    </div>
@endsection
