{{--
    رأس صفحة التقرير كما في hispa: العنوان، ورابط العودة إلى مركز
    التقارير، وزر الطباعة أعلى الصفحة في التقارير التي تضعه هناك.
--}}
<div class="page-header">
    <div class="lead">
        <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
        <div>
            <h1>{{ $meta['title'] }}</h1>
        </div>
    </div>
    <div class="actions">
        <a href="{{ route('panel.owner.reports') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'chevron-right']) التقارير المفصلة</a>
        @if ($print ?? false)
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
        @endif
    </div>
</div>
