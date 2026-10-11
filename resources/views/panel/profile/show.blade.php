@extends('layouts.app')

@section('title', 'الملف الشخصي')

@php
    // ميناء الكابتن من سجلّ الصياد، وميناء العدّاد من سجلّ موظف الإحصاء.
    $port = $user->port;
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user'])</div>
            <div>
                <h1>الملف الشخصي</h1>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="grid-3" style="margin-bottom:1.25rem">
        <div class="card">
            @include('partials.section-head', ['icon' => 'badge-check', 'title' => 'الحساب'])
            <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1rem">
                <div class="avatar-lg">
                    @if ($user->avatar_path)
                        <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}">
                    @else
                        {{ $user->initial }}
                    @endif
                </div>
                <div>
                    <p style="font-weight:700;font-size:1rem">{{ $user->name }}</p>
                    <p class="card-sub">{{ $user->appRole?->name }}</p>
                    <span class="badge {{ $user->active ? 'badge-ok' : 'badge-danger' }}" style="margin-top:.35rem">{{ $user->active ? 'الحساب مفعّل' : 'الحساب معطّل' }}</span>
                </div>
            </div>
            <dl class="detail-list">
                <dt>الجوال</dt><dd dir="ltr" class="num" style="text-align:right">{{ $user->phone ?? '—' }}</dd>
                <dt>البريد</dt><dd dir="ltr" style="text-align:right">{{ $user->email ?? '—' }}</dd>
                <dt>الميناء</dt><dd>{{ $port ? $port->name.($port->governorate ? ' — '.$port->governorate->name : '') : '—' }}</dd>
                {{-- عدّاد الشركة يرى شركته، وموظف الشركة شركته. --}}
                @php $company = $user->operatingCompany ?? $user->statisticsOfficer?->company; @endphp
                @if ($company)
                    <dt>شركة التشغيل</dt><dd>{{ $company->name }}</dd>
                @endif
                @if ($user->owner)
                    <dt>يتبع المالك</dt><dd>{{ $user->owner->name }} <span dir="ltr" class="num" style="color:hsl(var(--muted-foreground))">{{ $user->owner->phone }}</span></dd>
                @endif
                <dt>اللغة</dt><dd>{{ $user->locale === 'en' ? 'English' : 'العربية' }}</dd>
                <dt>آخر دخول</dt><dd class="num">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </dl>

            <form method="POST" action="{{ route('panel.profile.avatar') }}" enctype="multipart/form-data" style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem">
                @csrf
                <label class="field"><span>تغيير الصورة (JPG/PNG/WebP حتى 2MB)</span><input class="input" type="file" name="avatar" accept="image/*" required></label>
                <div style="display:flex;gap:.5rem;justify-content:flex-end">
                    @if ($user->avatar_path)
                        <button type="submit" form="removeAvatarForm" class="btn btn-outline">إزالة الصورة</button>
                    @endif
                    <button class="btn btn-primary">رفع</button>
                </div>
            </form>
            <form method="POST" action="{{ route('panel.profile.avatar.remove') }}" id="removeAvatarForm">@csrf @method('DELETE')</form>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'pencil', 'title' => 'تعديل الملف'])
            <form method="POST" action="{{ route('panel.profile.update') }}" style="display:flex;flex-direction:column;gap:.8rem">
                @csrf
                @method('PUT')
                <label class="field"><span>الاسم *</span><input class="input" name="name" value="{{ old('name', $user->name) }}" required></label>
                <label class="field"><span>البريد</span><input class="input" name="email" type="email" dir="ltr" value="{{ old('email', $user->email) }}"></label>
                <label class="field"><span>اللغة</span>
                    <select class="select" name="locale">
                        <option value="ar" @selected(old('locale', $user->locale) === 'ar')>العربية</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                    </select>
                </label>
                <p class="card-sub">الجوال والدور يغيّرهما من أنشأ الحساب.</p>
                <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary">@include('partials.icon', ['name' => 'save']) حفظ</button></div>
            </form>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'key-round', 'title' => 'تغيير كلمة المرور'])
            <form method="POST" action="{{ route('panel.profile.password') }}" style="display:flex;flex-direction:column;gap:.8rem" autocomplete="off">
                @csrf
                <label class="field"><span>كلمة المرور الحالية *</span><input class="input" name="current_password" type="password" dir="ltr" required autocomplete="current-password"></label>
                <label class="field"><span>كلمة المرور الجديدة * (8 أحرف فأكثر)</span><input class="input" name="password" type="password" dir="ltr" required minlength="8" autocomplete="new-password"></label>
                <label class="field"><span>تأكيد كلمة المرور *</span><input class="input" name="password_confirmation" type="password" dir="ltr" required autocomplete="new-password"></label>
                <p class="card-sub">تغييرها يُخرج أجهزة التطبيق الأخرى؛ جلستك هذه تبقى.</p>
                <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary">@include('partials.icon', ['name' => 'key-round']) تغيير</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        @include('partials.section-head', ['icon' => 'life-buoy', 'title' => 'الدعم والشروط'])
        <p class="card-sub">للمساعدة تواصل مع الدعم الفني: <a href="{{ route('services.support') }}" class="link-more">صفحة الدعم</a>. باستخدامك اللوحة والتطبيق توافق على شروط الاستخدام وسياسة الخصوصية.</p>
    </div>
@endsection
