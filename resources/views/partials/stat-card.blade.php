{{--
    بطاقة المؤشر: العنوان ومربّع الأيقونة في السطر الأول، والقيمة تحتهما كبيرةً
    وحدها. الترتيب مقصود — العين تقرأ ما يقيسه المؤشر ثم تنزل إلى الرقم.
--}}
<div class="stat-card" data-tone="{{ $tone ?? 'primary' }}">
    <div class="top">
        <p class="label">{{ $label }}</p>
        <div class="kpi-icon {{ $tone ?? 'primary' }}">@include('partials.icon', ['name' => $icon])</div>
    </div>
    {{-- القيمة معزولة LTR: رقم سالب بعد نص عربي يُعرض "123-" بدونها. --}}
    <p class="value"><bdi dir="ltr">{{ $value }}</bdi>@if (!empty($unit))<span class="unit">{{ $unit }}</span>@endif</p>
</div>
