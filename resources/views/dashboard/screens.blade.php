@extends('layouts.app')

@section('title', 'شاشة العرض')

@php
    use App\Support\Nav;

    /*
     * المربّعات لوحات nav_screens عدا هذه الشاشة، فلا تُذكر اللوحات هنا مرتين:
     * يكفي تعديل nav_screens ليظهر مربّعها. السطر الوصفي وحده محلّي.
     */
    $blurbs = [
        'screens.overview' => 'المصيد المعتمد والرحلات والأسطول والتنبيهات في لوحة واحدة.',
        'screens.sea-map' => 'مواقع الموانئ ومناطق الصيد وحركة القوارب على الخريطة.',
        'screens.production' => 'الإنتاج السمكي حسب المنطقة والميناء والنوع.',
        'screens.ports-compare' => 'مقارنة أداء الموانئ في الإنتاج والرحلات والالتزام.',
        'screens.sustainability' => 'حالة المخزون السمكي ومؤشرات الاستدامة والصيد العرضي.',
    ];

    $screen = Nav::screenMode();
    $tiles = collect(Nav::screens())
        ->flatMap(fn (array $section) => array_map(
            fn (array $item) => $item + ['group' => $section['title']],
            $section['items'],
        ))
        ->reject(fn (array $item) => request()->routeIs($item['route']));
@endphp

@section('content')
    <div class="screen-launcher">
        <div class="screen-launcher-head">
            <div>
                <p class="kicker">{{ config('hawat.sector') }}</p>
                <h1>{{ config('hawat.tagline') }}</h1>
            </div>

            {{-- الشاشة على وضع العرض افتراضًا، وهذا الزر لا يظهر إلا بعد الخروج منه. --}}
            @unless ($screen)
                <a href="{{ route('screens.home') }}" class="btn btn-outline" data-screen-tile>
                    @include('partials.icon', ['name' => 'maximize'])
                    وضع العرض
                </a>
            @endunless
        </div>

        <div class="screen-grid">
            @foreach ($tiles as $tile)
                <a href="{{ route($tile['route'], ['screen' => 1]) }}" class="screen-tile" data-screen-tile>
                    <div class="t-top">
                        <div class="t-icon">@include('partials.icon', ['name' => $tile['icon']])</div>
                        <span class="t-group">{{ $tile['group'] }}</span>
                    </div>
                    <div>
                        <h2>{{ $tile['label'] }}</h2>
                        <p>{{ $blurbs[$tile['route']] ?? '' }}</p>
                    </div>
                    <span class="t-go">
                        عرض بملء الشاشة
                        @include('partials.icon', ['name' => 'chevron-left'])
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ملء شاشة المتصفح لا يُطلب إلا داخل إيماءة مستخدم، ونقر المربّع هو الإيماءة
    // الوحيدة قبل فتح اللوحة: نطلبه أولًا ثم ننتقل، ولا نُعطّل النقرة إن رُفض.
    document.querySelectorAll('[data-screen-tile]').forEach(function (tile) {
        tile.addEventListener('click', function (event) {
            if (document.fullscreenElement || !document.documentElement.requestFullscreen) {
                return;
            }

            event.preventDefault();
            document.documentElement.requestFullscreen()
                .catch(function () {})
                .finally(function () { window.location.href = tile.href; });
        });
    });
</script>
@endpush
