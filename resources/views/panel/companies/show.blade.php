@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'building'])</div>
            <div>
                <h1>{{ $company->name }}</h1>
                <p>
                    <span class="badge {{ $company->isActive() ? 'badge-ok' : 'badge-danger' }}">{{ $company->status_label }}</span>
                    @if ($company->commercial_register) · سجل تجاري <bdi dir="ltr" class="num">{{ $company->commercial_register }}</bdi>@endif
                    @if ($company->contact_name) · {{ $company->contact_name }}@endif
                    @if ($company->phone) · <bdi dir="ltr" class="num">{{ $company->phone }}</bdi>@endif
                </p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.companies') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left']) كل الشركات</a>
            <button type="button" class="btn btn-primary"
                onclick='openCompanyForm({!! json_encode($company->only(['id', 'name', 'commercial_register', 'phone', 'email', 'contact_name', 'status', 'notes']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                @include('partials.icon', ['name' => 'pencil']) تعديل البيانات
            </button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'anchor', 'title' => 'الموانئ التي تشغّلها'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الميناء</th><th>المحافظة</th><th>منذ</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($company->ports as $port)
                            <tr>
                                <td style="font-weight:600">{{ $port->name }}</td>
                                <td>{{ $port->governorate?->name ?? '—' }}</td>
                                <td class="num">{{ $port->pivot->started_at ? \Illuminate\Support\Carbon::parse($port->pivot->started_at)->format('Y-m-d') : '—' }}</td>
                                <td style="text-align:left">
                                    <form method="POST" action="{{ route('panel.companies.ports.detach', [$company, $port]) }}" onsubmit="return confirm('نزع {{ $port->name }} من الشركة؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action danger" title="نزع">@include('partials.icon', ['name' => 'x'])</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1.25rem;text-align:center;color:hsl(var(--muted-foreground))">لم يُسند ميناء بعد — لا تفتح الشركة توظيفًا إلا في موانئها.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('panel.companies.ports.attach', $company) }}" class="filter-bar" style="margin-top:.75rem">
                @csrf
                <label class="field"><span>إسناد ميناء</span>
                    <select class="select" name="port_id" required>
                        <option value="">— ميناء لا تشغّله شركة —</option>
                        @foreach ($freePorts as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>منذ</span><input class="input" type="date" name="started_at" value="{{ today()->toDateString() }}"></label>
                <button class="btn btn-primary">@include('partials.icon', ['name' => 'plus']) إسناد</button>
            </form>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'user-cog', 'title' => 'موظفو الشركة'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الاسم</th><th>الجوال</th><th>آخر دخول</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($company->staff as $member)
                            <tr>
                                <td style="font-weight:600"><a href="{{ route('panel.users', ['search' => $member->phone]) }}">{{ $member->name }}</a></td>
                                <td class="num" dir="ltr" style="text-align:right">{{ $member->phone }}</td>
                                <td style="font-size:.74rem;color:hsl(var(--muted-foreground))">{{ $member->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}</td>
                                <td><span class="badge {{ $member->active ? 'badge-ok' : 'badge-danger' }}">{{ $member->active ? 'مفعّل' : 'معطّل' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1.25rem;text-align:center;color:hsl(var(--muted-foreground))">لا حساب للشركة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <details style="margin-top:.75rem">
                <summary class="btn btn-outline" style="list-style:none;cursor:pointer">@include('partials.icon', ['name' => 'user-plus']) حساب موظف جديد</summary>
                <form method="POST" action="{{ route('panel.companies.staff.store', $company) }}" class="form-grid cols-2" style="margin-top:.75rem" autocomplete="off">
                    @csrf
                    <label class="field"><span>الاسم *</span><input class="input" name="name" required maxlength="255"></label>
                    <label class="field"><span>الجوال *</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX" required></label>
                    <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" dir="ltr"></label>
                    <label class="field"><span>كلمة المرور *</span><input class="input" type="password" name="password" dir="ltr" minlength="8" required autocomplete="new-password"></label>
                    <div style="grid-column:1/-1;display:flex;justify-content:flex-end"><button class="btn btn-primary">إنشاء الحساب</button></div>
                </form>
            </details>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'users', 'title' => 'عدّادو الشركة'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>العدّاد</th><th>الرقم الوظيفي</th><th>الميناء</th><th>رحلات عدّها</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($counters as $counter)
                        <tr>
                            <td style="font-weight:600">{{ $counter->name }}</td>
                            <td class="num">{{ $counter->employee_number }}</td>
                            <td>{{ $counter->port?->name }}</td>
                            <td class="num">{{ number_format($counter->trips_counted) }}</td>
                            <td><span class="badge {{ $counter->isSuspended() ? 'badge-danger' : 'badge-ok' }}">{{ $counter->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.25rem;text-align:center;color:hsl(var(--muted-foreground))">لم توظّف الشركة عدّادًا بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:.6rem"><a href="{{ route('panel.counters', ['company' => $company->id]) }}">إدارة العدّادين (إيقاف ونقل) ←</a></div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'calendar', 'title' => 'آخر جولات التوظيف'])
        <div class="table-card" style="border:0">
            <table class="data-table">
                <thead><tr><th>الجولة</th><th>الميناء</th><th>المدة</th><th>المقاعد</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($rounds as $round)
                        <tr>
                            <td style="font-weight:600">{{ $round->title }}</td>
                            <td>{{ $round->port?->name }}</td>
                            <td class="num" style="font-size:.74rem"><bdi dir="ltr">{{ $round->opens_at->format('Y-m-d') }}</bdi> ← <bdi dir="ltr">{{ $round->closes_at->format('Y-m-d') }}</bdi></td>
                            <td class="num">{{ $round->approved_count }} / {{ $round->seats }}</td>
                            <td><span class="badge {{ $round->state_tone }}">{{ $round->state_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.25rem;text-align:center;color:hsl(var(--muted-foreground))">لا جولات توظيف بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('panel.companies.partials.form-drawer')
@endsection
