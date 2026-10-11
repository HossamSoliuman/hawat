{{--
    ترويسة قسم داخل اللوحة: مربّع أيقونة، فعنوان، فخطٌّ يمتدّ إلى آخر السطر.
    الخطّ ليس زينة — هو ما يمنع بقاء فراغ بين العنوان وحافّة اللوحة. لا سطر
    وصفٍ بجانب العنوان: العنوان يكفي.
--}}
<div class="section-head">
    <span class="ico">@include('partials.icon', ['name' => $icon])</span>
    <h2>{{ $title }}</h2>
    <span class="line"></span>
</div>
