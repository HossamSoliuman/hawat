@php
    use App\Support\Nav;

    /*
     * جذر مضيف الوزارة: صناديق بواباتها الثلاث. تطبيق حوات ليس منها — له
     * النطاق الرئيسي ودخوله على /admin.
     *
     * الصفحة تُقرأ في شاشة واحدة بلا تمرير، فوصف كل بوابة سطر قصير، والوسوم —
     * مجموعات قائمتها الجانبية — وحدها تفصّل ما بداخلها.
     */
    $card = function (string $key, string $blurb): array {
        $portal = Nav::portal($key);

        return [
            'label' => $portal['label'],
            'icon' => $portal['icon'],
            'href' => route($portal['home']),
            'blurb' => $blurb,
            'tags' => array_column(Nav::sections($key), 'title'),
            // الإحصاء والخدمات مفتوحتان، وإدارة النظام خلف الدخول.
            'guarded' => $key === Nav::SUBADMIN,
        ];
    };

    $portals = [
        $card(Nav::STATS, 'شاشة العرض والمؤشرات والرصد والتحليلات والتقارير.'),
        $card(Nav::SERVICES, 'طلبات الصيادين والرخص والامتثال والدعم الفني.'),
        $card(Nav::SUBADMIN, 'المستخدمون والموظفون والبيانات الأساسية والتكاملات.'),
    ];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابات الوزارة — {{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <style>
        html, body { height: 100%; }
        {{-- الصفحة كلها في نافذة واحدة: ترويسة، ثم البوابات، ثم حقوق حوات. وعلى
             الشاشة الضيقة تتراصّ الصناديق فتُمرَّر بدل أن تُقصّ. --}}
        .portal-page {
            min-height: 100dvh;
            display: grid; grid-template-rows: auto 1fr auto; gap: 1.5rem;
            padding: 1.5rem clamp(1rem, 3vw, 2.5rem);
        }
        @media (min-width: 860px) and (min-height: 640px) { .portal-page { height: 100dvh; overflow: hidden; } }
        .portal-head { display: flex; justify-content: center; }
        .hawat-mark img { height: clamp(40px, 6vh, 64px); width: auto; display: block; }
        {{-- نسخة الشعار البيضاء للوضع الليلي، والزرقاء للنهاري. --}}
        .hawat-mark .mark-dark { display: none; }
        .dark .hawat-mark .mark-light { display: none; }
        .dark .hawat-mark .mark-dark { display: block; }

        .portal-main { display: flex; align-items: center; justify-content: center; min-height: 0; }
        .portal-group { display: flex; flex-direction: column; gap: .75rem; width: 100%; max-width: 30rem; }
        .group-title {
            display: flex; align-items: center; gap: .6rem;
            font-size: .8rem; font-weight: 800; color: hsl(var(--primary));
        }
        .group-title::after { content: ''; flex: 1; border-top: 1px solid var(--hair); }
        .portal-grid { display: grid; gap: 1rem; }
        @media (min-width: 860px) {
            .portal-group { max-width: 66rem; }
            .portal-grid { grid-template-columns: repeat(3, 1fr); }
        }
        {{-- بوّابات الواجهة على قاعدة اللوحة نفسها التي في الداخل: سطحٌ شفّاف
             تمرّ خلفه صورة الصفحة، يحدّه خطٌّ شعري وأربعة أقواس زوايا — لا
             لونُ بطاقةٍ مصمت يقتطعها من الخلفية. والمرور يزيدها لمسةَ لونٍ
             خافتة لا رفعًا ولا ظلًّا. --}}
        .portal-card {
            display: flex; flex-direction: column; gap: .75rem; padding: 1.25rem;
            border: 1px solid var(--hair); border-radius: 0;
            background: var(--surface);
            transition: border-color .15s ease, background .15s ease;
        }
        .portal-card:hover { border-color: hsl(var(--primary) / .6); background: hsl(var(--primary) / .05); }
        {{-- رأس البطاقة سطر واحد: مربّع الأيقونة، ثم علامة البوابة المحميّة إن كانت. --}}
        .portal-card .card-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .portal-card .icon-wrap {
            display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem;
            border-radius: .75rem; background: hsl(var(--primary) / .12); color: hsl(var(--primary));
        }
        .portal-card .icon-wrap svg { width: 20px; height: 20px; }
        {{-- العلامة رقاقةٌ بلون التمييز لا قفلٌ مصمت: البوابة تُفتح بالدخول لا تُمنع. --}}
        .portal-lock {
            display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .45rem;
            font-size: 10px; font-weight: 700; color: hsl(var(--primary));
            border: 1px solid hsl(var(--primary) / .35); background: hsl(var(--primary) / .08);
        }
        .portal-lock svg { width: 12px; height: 12px; }
        .portal-card h3 { font-size: 1.05rem; font-weight: 700; }
        .portal-card .blurb { font-size: .78rem; line-height: 1.6; color: hsl(var(--muted-foreground)); }
        .portal-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
        .portal-tag { font-size: 10.5px; font-weight: 600; padding: .15rem .45rem; border-radius: 9999px; background: hsl(var(--muted) / .8); color: hsl(var(--muted-foreground)); }
        .portal-enter { display: flex; align-items: center; gap: .375rem; margin-top: auto; padding-top: .25rem; font-size: .8rem; font-weight: 700; color: hsl(var(--primary)); }
        .portal-enter svg { width: 16px; height: 16px; }
        .portal-foot { font-size: .72rem; color: hsl(var(--muted-foreground)); text-align: center; }
    </style>
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="portal-page">
        <header class="portal-head">
            <span class="hawat-mark">
                <img class="mark-light" src="{{ asset('images/logo.png') }}" alt="{{ config('hawat.name') }}">
                <img class="mark-dark" src="{{ asset('images/logo-white.png') }}" alt="" aria-hidden="true">
            </span>
        </header>

        <main class="portal-main">
            <section class="portal-group">
                <h1 class="group-title">{{ config('hawat.ministry') }} — {{ config('hawat.sector') }}</h1>
                <div class="portal-grid">
                    @foreach ($portals as $entry)
                        <a href="{{ $entry['href'] }}" class="portal-card">
                            <div class="card-top">
                                <div class="icon-wrap">@include('partials.icon', ['name' => $entry['icon']])</div>
                                @if ($entry['guarded'])
                                    <span class="portal-lock" title="بوابة محميّة — تتطلب تسجيل الدخول">
                                        @include('partials.icon', ['name' => 'shield-alert'])
                                        دخول
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h3>{{ $entry['label'] }}</h3>
                                <p class="blurb">{{ $entry['blurb'] }}</p>
                            </div>
                            <div class="portal-tags">
                                @foreach ($entry['tags'] as $tag)
                                    <span class="portal-tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                            <span class="portal-enter">
                                الدخول
                                @include('partials.icon', ['name' => 'chevron-left'])
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        </main>

        <footer class="portal-foot">
            جميع الحقوق محفوظة — حوات · مؤسسة دار الحوت للتجارة
        </footer>
    </div>
</body>
</html>
