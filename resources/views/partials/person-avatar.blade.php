{{-- صورة صغيرة لسطر في قائمة: الصورة إن وُجدت وإلا أول حرف من الاسم. --}}
<span class="avatar-sm">@if ($url)<img src="{{ $url }}" alt="{{ $name }}">@else{{ mb_substr($name, 0, 1) }}@endif</span>
