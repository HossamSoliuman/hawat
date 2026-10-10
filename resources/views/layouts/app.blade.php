<!DOCTYPE html>
@php
    /*
     * وضع العرض: الصفحة تُعرض على شاشة قاعة، فتُطوى القائمة الجانبية
     * ويُكبَّر القياس، ويبقى شريط تحكّم صغير للرجوع وملء الشاشة. لوحات
     * شاشة العرض (/screens) عليه افتراضًا — انظر App\Support\Nav::screenMode().
     */
    $screen = App\Support\Nav::screenMode();
@endphp
<html lang="ar" dir="rtl" @class(['screen-mode' => $screen])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'الرئيسية') — {{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{--
        خطّان لا خطّ واحد: Chakra Petch للأرقام واللاتيني — وهو ما يعطي اللوحة
        حِدَّتها الهندسية — و Tajawal للعربية، لأن الأول بلا حروف عربية فتلتقطها
        الثانية تلقائيًا.
    --}}
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    @stack('head')
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="shell">
        @unless ($screen)
            {{-- الشريط مثبّت بعرض الصفحة فوق القائمة، فموضعه خارج عمود المحتوى. --}}
            @include('partials.topbar')
            @include('partials.sidebar')
            <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>
        @endunless
        <div class="main">
            @if ($screen)
                @include('partials.screen-bar')
            @endif
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        function toggleSidebar(open) {
            document.getElementById('sidebar').classList.toggle('is-open', open);
            document.getElementById('backdrop').classList.toggle('is-visible', open);
        }
        function toggleTheme() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('hawat-theme', dark ? 'dark' : 'light');
        }
        function toggleDrawer(id, open) {
            document.getElementById(id).classList.toggle('is-open', open);
            document.getElementById(id + '-overlay').classList.toggle('is-open', open);
        }

        // ملء شاشة المتصفح نفسه — يُكمل وضع العرض بإخفاء إطار المتصفح. المتصفح
        // يخرج منه عند الانتقال بين الصفحات أحيانًا، فالزر حاضر في كل شاشة عرض.
        function toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                return;
            }

            document.documentElement.requestFullscreen?.().catch(() => {});
        }

        document.addEventListener('fullscreenchange', function () {
            document.querySelectorAll('.fs-btn').forEach(function (btn) {
                btn.classList.toggle('is-full', document.fullscreenElement !== null);
            });
        });

        // القائمة الجانبية أطول من الشاشة: نُوسّط الرابط النشط داخلها حتى لا يفتح
        // التبويب وهو خارج مجال العرض.
        function centerActiveNavLink(box) {
            const active = box?.querySelector('.is-active');

            if (!active) {
                return;
            }

            const offset = active.getBoundingClientRect().top - box.getBoundingClientRect().top;
            box.scrollTop += offset - (box.clientHeight - active.offsetHeight) / 2;
        }

        centerActiveNavLink(document.querySelector('.sidebar-nav'));
    </script>
    @stack('scripts')
</body>
</html>
