@extends('layouts.app')

@section('title', 'شركات التشغيل')

@php
    use App\Models\OperatingCompany;
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'building'])</div>
            <div>
                <h1>شركات التشغيل</h1>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openCompanyForm()">@include('partials.icon', ['name' => 'plus']) شركة جديدة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الشركات', 'value' => number_format($counts['companies']), 'icon' => 'building', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نشطة', 'value' => number_format($counts['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'موانئ مُسندة', 'value' => number_format($counts['ports']), 'icon' => 'anchor', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدّادو الشركات', 'value' => number_format($counts['counters']), 'icon' => 'users', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو السجل التجاري..."></label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach (OperatingCompany::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.companies') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الشركة</th><th>السجل التجاري</th><th>المسؤول</th><th>الموانئ</th><th>الموظفون</th><th>العدّادون</th><th>طلبات معلّقة</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td style="font-weight:600"><a href="{{ route('panel.companies.show', $company) }}">{{ $company->name }}</a></td>
                        <td class="num" dir="ltr" style="text-align:right">{{ $company->commercial_register ?? '—' }}</td>
                        <td>{{ $company->contact_name ?? '—' }}<div class="card-sub num" dir="ltr" style="text-align:right">{{ $company->phone }}</div></td>
                        <td class="num">{{ $company->ports_count }}</td>
                        <td class="num">{{ $company->staff_count }}</td>
                        <td class="num">{{ $company->counters_count }}</td>
                        <td class="num">{{ $company->pending_count }}</td>
                        <td><span class="badge {{ $company->isActive() ? 'badge-ok' : 'badge-danger' }}">{{ $company->status_label }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <a href="{{ route('panel.companies.show', $company) }}" class="icon-action" title="عرض">@include('partials.icon', ['name' => 'eye'])</a>
                                <button type="button" class="icon-action" title="تعديل"
                                    onclick='openCompanyForm({!! json_encode($company->only(['id', 'name', 'commercial_register', 'phone', 'email', 'contact_name', 'status', 'notes']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                    @include('partials.icon', ['name' => 'pencil'])
                                </button>
                                @if ($company->counters_count === 0)
                                    <form method="POST" action="{{ route('panel.companies.destroy', $company) }}" onsubmit="return confirm('حذف شركة «{{ $company->name }}»؟ تُعطَّل حسابات موظفيها.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا شركات تشغيل بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $companies])

    @include('panel.companies.partials.form-drawer')
@endsection
