@php
    /*
     * صفحة الهبوط العامة على الجذر "/". المحتوى من التصميم المعتمد
     * (docs/حوات/Waiting on PDF and logo — HUWAT Landing Page v2 Dark) مختصرًا إلى
     * أبرز الحقائق: عناوين وتسميات بلا فقرات شرح، ولا يتكرّر قسمٌ معنى قسمٍ قبله.
     * الألوان والخطوط من نظام اللوحة نفسه: partials/styles في الوضعين.
     *
     * قسم الأرقام محفوظ في المصدر لكنه مخفيّ حتى يؤكّد المالك قيمه (+439 / +129 /
     * +241 / +149) — انظر CONTENT-AUDIT.md §13.
     */
    $showStats = false;

    $nav = [
        ['href' => '#top', 'label' => 'الرئيسية'],
        ['href' => '#journey', 'label' => 'آلية العمل'],
        ['href' => '#features', 'label' => 'المميزات'],
        ['href' => '#coverage', 'label' => 'التغطية'],
        ['href' => '#app', 'label' => 'التطبيق'],
        ['href' => '#register', 'label' => 'سجّل معنا'],
    ];

    // أبرز ما تقدّمه حوات: شريطٌ واحد تحت الغلاف.
    $values = [
        ['icon' => 'layers', 'title' => 'إدارة رقمية متكاملة'],
        ['icon' => 'shield-check', 'title' => 'شفافية تشغيلية'],
        ['icon' => 'bar-chart', 'title' => 'بيانات تدعم القرار'],
        ['icon' => 'flag', 'title' => 'مواكبة رؤية المملكة 2030'],
    ];

    // مسار العمليات السبع: من الكابتن إلى التقارير.
    $journey = [
        ['icon' => 'user', 'label' => 'الكابتن'],
        ['icon' => 'ship', 'label' => 'الرحلة'],
        ['icon' => 'fish', 'label' => 'المصيد'],
        ['icon' => 'anchor', 'label' => 'الإنزال'],
        ['icon' => 'gavel', 'label' => 'الحراج'],
        ['icon' => 'shopping-cart', 'label' => 'البيع'],
        ['icon' => 'bar-chart', 'label' => 'التقارير'],
    ];

    $capabilities = [
        ['icon' => 'layout-dashboard', 'label' => 'لوحة تحكم موحّدة'],
        ['icon' => 'map-pin', 'label' => 'تتبع القوارب'],
        ['icon' => 'shopping-cart', 'label' => 'إدارة عمليات البيع'],
        ['icon' => 'bar-chart', 'label' => 'تقارير الأداء والتحليل'],
        ['icon' => 'users', 'label' => 'المستخدمون والصلاحيات'],
        ['icon' => 'archive', 'label' => 'أرشفة السجلات'],
        ['icon' => 'message-square', 'label' => 'التواصل مع الكباتن'],
        ['icon' => 'bell', 'label' => 'إشعارات فورية'],
    ];

    $coverage = [
        ['icon' => 'waves', 'label' => 'البحر الأحمر — المنطقة الغربية'],
        ['icon' => 'waves', 'label' => 'الخليج العربي — المنطقة الشرقية'],
        ['icon' => 'anchor', 'label' => 'موانئ الصيد'],
        ['icon' => 'store', 'label' => 'أسواق الحراج'],
    ];

    $stats = [
        ['value' => 439, 'label' => 'رحلة بحرية مسجّلة'],
        ['value' => 129, 'label' => 'كمية أسماك مسجّلة', 'unit' => 'الوحدة غير محددة في المصدر'],
        ['value' => 241, 'label' => 'عملية بيع منفّذة'],
        ['value' => 149, 'label' => 'مستخدم مسجّل'],
    ];

    $email = 'yaquobi@hawat.sa';
    $phone = '0595233393';
    $phoneHref = 'tel:+966595233393';
    $googlePlay = 'https://play.google.com/store/apps/details?id=com.os.hawat&hl=ar&pli=1';
    $facebook = 'https://www.facebook.com/profile.php?id=61578858179340';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('hawat.name') }} | أتمتة وإدارة عمليات الصيد البحري في المملكة العربية السعودية</title>
    <meta name="description" content="حوات منصة رقمية لأتمتة وإدارة عمليات الصيد البحري في المملكة العربية السعودية: إدارة الرحلات، تسجيل المصيد، عمليات البيع، والتقارير اللحظية.">
    <meta property="og:title" content="حوات — منصة عمليات الصيد البحري">
    <meta property="og:description" content="أتمتة وإدارة عمليات الصيد البحري في المملكة العربية السعودية.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_SA">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <style>
        html { scroll-behavior: smooth; }
        main, footer { overflow-x: clip; }
        ::selection { background: hsl(var(--primary) / .25); }
        *:focus-visible { outline: 2px solid hsl(var(--primary)); outline-offset: 3px; }

        {{-- الأكواد اللاتينية (HAWAT…) على الخطّ الهندسي بتباعد حروف خفيف. --}}
        .code { font-family: 'Chakra Petch', ui-monospace, monospace; letter-spacing: .06em; font-size: 11px; color: hsl(var(--muted-foreground)); }
        .dash { font-family: 'Chakra Petch', ui-monospace, monospace; }

        /* ---------- الشريط العلوي: لوح hispa الأزرق نفسه، لاصق لا مثبّت. ---------- */
        .ld-top {
            position: sticky; top: 0; z-index: 60;
            background: var(--topbar-bg); color: var(--topbar-fg);
        }
        html.dark .ld-top { border-bottom: 1px solid hsl(var(--primary) / .4); }
        .ld-top-in {
            max-width: 1280px; margin: 0 auto; height: 3.75rem;
            padding: 0 clamp(18px, 4vw, 40px);
            display: flex; align-items: center; gap: clamp(14px, 3vw, 40px);
        }
        .ld-brand img { height: 32px; width: auto; display: block; }
        .ld-nav { display: none; align-items: center; gap: 2px; margin-inline-start: auto; }
        .ld-nav a { padding: .55rem .8rem; font-size: 14px; font-weight: 500; color: hsl(0 0% 100% / .78); white-space: nowrap; border-bottom: 2px solid transparent; }
        .ld-nav a:hover, .ld-nav a.is-active { color: #fff; border-bottom-color: hsl(0 0% 100% / .7); }
        .ld-tools { display: flex; align-items: center; gap: .5rem; margin-inline-start: auto; }
        .ld-top .icon-btn { color: hsl(0 0% 100% / .82); }
        .ld-top .icon-btn:hover { color: #fff; }
        .ld-top .icon-btn svg { width: 20px; height: 20px; }
        {{-- أيقونة التبديل: قمر في الفاتح، شمس في الداكن. --}}
        .ico-sun { display: none; }
        html.dark .ico-sun { display: block; }
        html.dark .ico-moon { display: none; }
        .ld-top-cta { display: none; align-items: center; height: 2.4rem; padding: 0 1rem; font-size: 14px; font-weight: 700; color: #fff; border: 1px solid hsl(0 0% 100% / .55); transition: background .15s; }
        .ld-top-cta:hover { background: hsl(0 0% 100% / .12); }
        .ld-menu-btn { display: flex; }
        .ld-progress { position: absolute; inset-block-end: -1px; inset-inline-start: 0; height: 2px; width: 0; background: hsl(0 0% 100% / .75); transition: width .12s linear; }
        {{-- القائمة المنسدلة على الشاشة الضيّقة: لوحٌ مصمت بلون الشريط تحت الشريط. --}}
        .ld-drawer { display: none; border-top: 1px solid hsl(0 0% 100% / .15); background: var(--topbar-bg); padding: .6rem clamp(18px, 4vw, 40px) 1rem; }
        .ld-drawer.is-open { display: grid; gap: 4px; }
        .ld-drawer a { display: flex; align-items: center; min-height: 2.9rem; padding: 0 .9rem; font-size: 16px; font-weight: 500; color: #fff; border: 1px solid hsl(0 0% 100% / .18); }
        .ld-drawer a:last-child { background: hsl(0 0% 100% / .14); font-weight: 700; }
        @media (min-width: 1040px) {
            .ld-nav, .ld-top-cta { display: flex; }
            .ld-menu-btn, .ld-drawer { display: none !important; }
        }

        /* ---------- الهيكل العام للأقسام ---------- */
        .ld-section { position: relative; padding: clamp(36px, 4.5vw, 60px) clamp(18px, 4vw, 40px); scroll-margin-top: 3.75rem; }
        .ld-wrap { max-width: 1280px; margin: 0 auto; }
        {{-- شريطٌ متناوب لبعض الأقسام: حبرٌ بشفافية 3٪ وحدّان شعريان، لا لونٌ آخر. --}}
        .ld-band { background: hsl(var(--foreground) / .03); border-block: 1px solid var(--hair); }
        {{-- عنوان القسم وحده: خطٌّ أزرق قصير قبله بدل أيّ سطر شرح. --}}
        .ld-h2 { font-size: clamp(21px, 2.2vw, 30px); font-weight: 800; line-height: 1.3; text-wrap: balance; }
        .ld-h2::before { content: ''; display: block; width: 36px; height: 2px; margin-bottom: .8rem; background: hsl(var(--primary)); }
        .ld-head { margin-bottom: clamp(20px, 2.6vw, 32px); max-width: 64ch; }
        .ld-note { font-size: 12px; line-height: 1.8; color: hsl(var(--muted-foreground)); }

        {{-- البطاقات على قاعدة اللوحة: .card تحمل الأقواس والخطّ الشعري، وهنا الحشوة والمرور فقط. --}}
        .ld-card { padding: clamp(18px, 2vw, 26px); gap: .9rem; transition: border-color .15s ease, background .15s ease; }
        .ld-card:hover { border-color: hsl(var(--primary) / .6); background: linear-gradient(hsl(var(--primary) / .05) 0 0), var(--panel); }
        .ld-card h3 { font-size: .95rem; font-weight: 700; line-height: 1.4; }
        .ld-card p { font-size: .84rem; line-height: 1.75; color: hsl(var(--muted-foreground)); }
        .ico-box { display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem; flex-shrink: 0; background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .5); color: hsl(var(--primary)); }
        .ico-box svg { width: 20px; height: 20px; }
        .ico-box.sm { height: 2rem; width: 2rem; }
        .ico-box.sm svg { width: 16px; height: 16px; }
        .ico-box.lg { height: 3rem; width: 3rem; }
        .ico-box.lg svg { width: 24px; height: 24px; }
        .grid-auto { display: grid; gap: var(--gap); grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--col, 230px)), 1fr)); }
        .grid-auto > .card { height: 100%; }

        {{-- الأزرار الكبيرة لصفحة الهبوط: قاعدة .btn نفسها بقياس أوسع. --}}
        .btn-lg { padding: .7rem 1.2rem; font-size: 14px; font-weight: 700; }
        .btn-lg svg { width: 17px; height: 17px; }

        {{-- الظهور عند التمرير: يُطفأ كلّه مع تفضيل تقليل الحركة. --}}
        [data-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .6s cubic-bezier(0,0,.2,1), transform .6s cubic-bezier(0,0,.2,1); }
        [data-reveal].is-in { opacity: 1; transform: none; }
        @keyframes ld-pulse { 0%, 100% { opacity: .5; } 50% { opacity: .12; } }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
            * { animation: none !important; }
        }

        /* ---------- الغلاف ---------- */
        .ld-hero { position: relative; overflow: hidden; min-height: clamp(440px, 66vh, 680px); display: grid; align-items: center; scroll-margin-top: 3.75rem; }
        {{-- الصورة تستقرّ يسارًا (القارب) والنصّ يمينًا فوق حجابٍ بلون الصفحة يذوب نحو الصورة. --}}
        {{-- أعلى الصورة لوحتا بيانات مرسومتان فيها: القصّ ينزل تحتهما والتلاشي العلويّ يطمس ما بقي منهما. --}}
        .ld-hero-img { position: absolute; inset: 0; background: url('{{ asset('images/landing-hero.jpg') }}') left 61% / cover no-repeat; will-change: transform; }
        .ld-hero-veil { position: absolute; inset: 0; background: linear-gradient(to left, hsl(var(--background)) 0%, hsl(var(--background) / .94) 40%, hsl(var(--background) / .5) 70%, hsl(var(--background) / .18) 100%); }
        .ld-hero-fade { position: absolute; inset: 0; background: linear-gradient(to bottom, hsl(var(--background) / .85) 0%, hsl(var(--background) / .35) 16%, hsl(var(--background) / .1) 34%, hsl(var(--background) / .75) 70%, hsl(var(--background)) 100%); }
        {{-- النصّ يمينًا بعرض أطول سطوره، والشعار يسارًا فوق البحر في بقية العرض. --}}
        .ld-hero-in { position: relative; max-width: 1280px; width: 100%; margin: 0 auto; padding: clamp(28px, 4vw, 56px) clamp(18px, 4vw, 40px) clamp(18px, 2.5vw, 28px); display: grid; grid-template-columns: minmax(0, 1fr); gap: clamp(24px, 3vw, 44px); align-items: center; }
        .ld-hero-copy { position: relative; min-width: 0; justify-self: start; }
        .ld-hero-copy::before { content: ''; position: absolute; inset: -28px -32px; background: radial-gradient(120% 100% at 100% 50%, hsl(var(--background) / .96) 0%, hsl(var(--background) / .9) 55%, hsl(var(--background) / 0) 100%); pointer-events: none; }
        .ld-hero-copy > * { position: relative; }
        {{--
            شعار الغلاف فوق البحر يسارًا (وفوق العنوان على الجوال): الشعار قناعٌ يُلوَّن
            بالأزرق الأساسيّ في الوضعين. يُكشف من اليمين خلف خطّ مسحٍ عند التحميل، ثم يطفو
            ويعبره بريقٌ كل بضع ثوانٍ. بلا حركة يظهر كاملًا ساكنًا.
        --}}
        .ld-hero-mark { --h: clamp(52px, 12vw, 72px); position: relative; order: -1; justify-self: start; display: block; width: fit-content; filter: drop-shadow(0 0 22px hsl(var(--primary) / .4)); animation: ld-logo-bob 6s ease-in-out 1.4s infinite; }
        @media (min-width: 900px) {
            .ld-hero-in { grid-template-columns: auto minmax(0, 1fr); }
            .ld-hero-mark { --h: clamp(96px, 9vw, 140px); order: 0; justify-self: center; margin-top: clamp(30px, 5vw, 80px); }
        }
        .ld-hero-logo { position: relative; display: block; overflow: hidden; height: var(--h); width: calc(var(--h) * 389 / 160); background: hsl(var(--primary)); -webkit-mask: url('{{ asset('images/logo.png') }}') center / contain no-repeat; mask: url('{{ asset('images/logo.png') }}') center / contain no-repeat; animation: ld-logo-in 1.1s cubic-bezier(.65, 0, .35, 1) .15s both; }
        .ld-hero-logo::after { content: ''; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 40%, hsl(0 0% 100% / .85) 50%, transparent 60%) -50% 0 / 250% 100% no-repeat; animation: ld-logo-shine 4.8s ease-in-out 1.6s infinite; }
        .ld-hero-mark .scan { position: absolute; top: -6px; bottom: -6px; right: 0; width: 2px; background: hsl(var(--primary)); box-shadow: 0 0 12px hsl(var(--primary)); opacity: 0; animation: ld-logo-scan 1.1s cubic-bezier(.65, 0, .35, 1) .15s both; }
        @keyframes ld-logo-in { from { clip-path: inset(0 0 0 100%); } to { clip-path: inset(0); } }
        @keyframes ld-logo-scan { 0% { right: 0; opacity: 1; } 85% { opacity: 1; } 100% { right: 100%; opacity: 0; } }
        @keyframes ld-logo-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes ld-logo-shine { 0%, 55% { background-position: -50% 0; } 100% { background-position: 150% 0; } }
        {{-- العنوان سطورٌ مقصودة: كلّ <span> سطرٌ على الشاشة الواسعة، وتنساب على الضيّقة. --}}
        .ld-h1 { font-size: clamp(28px, 3.4vw, 46px); font-weight: 800; line-height: 1.28; margin-bottom: clamp(20px, 2.4vw, 32px); text-wrap: balance; }
        .ld-hero-cta { display: flex; flex-wrap: wrap; gap: .8rem; }
        @media (max-width: 719px) {
            .ld-hero { min-height: 0; }
            .ld-hero-veil { background: hsl(var(--background) / .82); }
        }
        @media (min-width: 720px) {
            .ld-h1 span { display: block; }
        }

        /* ---------- شريط القيمة: أربعٌ في صفّ، ثم اثنتان، ثم واحدة ---------- */
        .ld-value { padding: 0 clamp(18px, 4vw, 40px); margin-top: -1px; }
        .ld-value .card { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); padding: 0; }
        .ld-value .item { display: flex; align-items: center; gap: .9rem; padding: .9rem 1.1rem; font-size: .92rem; font-weight: 700; border-inline-end: 1px solid var(--hair); }
        .ld-value .item:last-child { border-inline-end: 0; }
        @media (max-width: 999px) {
            .ld-value .card { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ld-value .item:nth-child(2n) { border-inline-end: 0; }
            .ld-value .item:nth-child(-n+2) { border-bottom: 1px solid var(--hair); }
        }
        @media (max-width: 559px) {
            .ld-value .card { grid-template-columns: minmax(0, 1fr); }
            .ld-value .item { border-inline-end: 0; border-bottom: 1px solid var(--hair); }
            .ld-value .item:last-child { border-bottom: 0; }
        }

        /* ---------- رحلة الصيد: سبع عُقد على خطّ واحد ---------- */
        .ld-journey { padding: clamp(20px, 2.5vw, 34px) clamp(14px, 2vw, 24px); flex-direction: row; flex-wrap: wrap; align-items: flex-start; justify-content: center; gap: 14px 4px; }
        .ld-node { display: grid; gap: .6rem; justify-items: center; text-align: center; flex: 1 1 110px; min-width: 96px; padding: 4px 6px; cursor: default; }
        .ld-node .ico-box { transition: background .2s, border-color .2s; }
        .ld-node.is-on .ico-box { background: hsl(var(--primary) / .22); border-color: hsl(var(--primary)); }
        .ld-node .lbl { font-size: .92rem; font-weight: 700; }
        .ld-seg { flex: 0 0 24px; height: 1px; margin-top: calc(1.25rem + 4px); background: hsl(var(--primary)); opacity: .3; transition: opacity .25s; }
        .ld-seg.is-on { opacity: 1; }
        @media (max-width: 719px) { .ld-seg { display: none; } }
        {{-- على الشاشة العريضة السبع في صفّ واحد لا تنكسر. --}}
        @media (min-width: 1024px) { .ld-journey { flex-wrap: nowrap; } .ld-node { flex: 1 1 0; min-width: 0; } }

        /* ---------- الإمكانات: أربعٌ في الصفّ على الشاشة الواسعة ---------- */
        .ld-caps { display: grid; gap: var(--gap); grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (min-width: 900px) { .ld-caps { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .ld-caps .card { height: 100%; align-items: flex-start; gap: .8rem; padding: 1rem 1.1rem; }
        @media (min-width: 560px) { .ld-caps .card { flex-direction: row; align-items: center; } }

        /* ---------- التغطية: قائمة وخريطة ---------- */
        .ld-cov { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: clamp(16px, 2vw, 28px); align-items: center; }
        .ld-cov-list { display: grid; gap: 10px; align-content: start; }
        .ld-cov-list .row { display: flex; align-items: center; gap: .8rem; min-height: 48px; padding: .6rem .9rem; border: 1px solid var(--hair); background: var(--panel); font-size: .92rem; font-weight: 700; }
        @media (min-width: 900px) { .ld-cov { grid-template-columns: minmax(260px, 1fr) minmax(0, 1.6fr); } }
        .ld-map { padding: .5rem; }
        .ksa-map { width: 100%; height: auto; display: block; }
        .m-grid { fill: none; stroke: hsl(var(--primary) / .12); stroke-width: .6; }
        .m-land path { fill: hsl(var(--foreground) / .04); stroke: hsl(var(--foreground) / .22); stroke-width: .8; }
        .m-ksa { fill: hsl(var(--primary) / .18); stroke: hsl(var(--primary)); stroke-width: 1.3; }
        .m-route path { fill: none; stroke: hsl(var(--primary) / .55); stroke-width: 1.2; stroke-dasharray: 4 7; }
        .m-sea text { font-family: 'Tajawal', sans-serif; font-size: 18px; font-weight: 500; fill: hsl(var(--primary)); }
        .m-halo { fill: hsl(var(--primary) / .22); animation: ld-pulse 5s ease-in-out infinite; }
        .m-pt { fill: hsl(var(--foreground)); }
        .m-pts text { font-family: 'Tajawal', sans-serif; font-size: 15px; fill: hsl(var(--foreground) / .82); }

        /* ---------- التطبيق ---------- */
        .ld-app { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: clamp(24px, 3vw, 48px); align-items: center; }
        .ld-stores { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.4rem; }
        {{-- صورتا التطبيق من متجر Google Play بنسبة 9:16، الثانية أكبر قليلًا وأمام الأولى. --}}
        .ld-phones { min-width: 0; display: flex; gap: clamp(12px, 2vw, 24px); justify-content: center; align-items: flex-end; }
        .ld-phone { padding: 8px; }
        .ld-phone img { display: block; width: 100%; height: auto; aspect-ratio: 9 / 16; object-fit: cover; }
        .ld-phone.a { width: min(40%, 210px); }
        .ld-phone.b { width: min(48%, 250px); }

        /* ---------- الأرقام (مخفيّ) ---------- */
        .ld-nums-head { display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: 1rem; }
        .ld-nums { --col: 220px; }
        .ld-nums .value { font-family: 'Chakra Petch', monospace; font-size: clamp(28px, 3.2vw, 42px); font-weight: 600; line-height: 1; color: hsl(var(--primary)); }
        .ld-nums .lbl { font-size: .88rem; font-weight: 600; }
        .ld-flag { align-self: start; font-size: 10px; letter-spacing: .08em; padding: .25rem .6rem; color: var(--st-warn); border: 1px solid color-mix(in srgb, var(--st-warn) 45%, transparent); background: color-mix(in srgb, var(--st-warn) 10%, transparent); }

        /* ---------- انضم إلى حوات ---------- */
        .ld-contact { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: clamp(20px, 3vw, 44px); align-items: start; }
        .ld-contact-info { display: grid; gap: .7rem; margin-top: 1.2rem; max-width: 42ch; }
        .ld-contact-info .card { flex-direction: row; align-items: center; gap: .9rem; padding: 1rem 1.2rem; }
        .ld-contact-info .k { font-size: .75rem; color: hsl(var(--muted-foreground)); margin-bottom: .2rem; }
        .ld-contact-info a { font-size: .98rem; font-weight: 600; color: hsl(var(--primary)); }
        {{--
            نموذج الطلب: شبكة من عمودين تتّسع في النافذة بلا تمرير، وخطأ الخادم تحت
            حقله حتى يُعدَّل.
        --}}
        .ld-form .ld-modal-body { gap: 1.1rem; padding: 1.2rem 1.4rem; }
        .ld-fs { border: 0; margin: 0; padding: 0; min-width: 0; display: grid; gap: .5rem; }
        .ld-fs > legend { padding: 0; margin-bottom: .5rem; }
        .ld-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem 1rem; }
        @media (max-width: 520px) { .ld-fields { grid-template-columns: minmax(0, 1fr); } }
        .ld-form .field { gap: .35rem; }
        .ld-form .lbl { font-size: .8rem; font-weight: 600; color: hsl(var(--foreground) / .85); }
        .ld-form .lbl em { margin-inline-start: .35rem; font-style: normal; font-size: .72rem; font-weight: 500; color: hsl(var(--muted-foreground)); }
        .ld-form .input { padding: .62rem .85rem; font-size: .92rem; transition: border-color .15s, background .15s; }
        .ld-form .input::placeholder { color: hsl(var(--muted-foreground) / .65); }
        .ld-form .input:hover { border-color: hsl(var(--foreground) / .3); }
        .ld-form .input:focus { border-color: hsl(var(--primary)); background: hsl(var(--primary) / .06); }
        .ld-form .input:user-invalid, .ld-form .has-error .input { border-color: var(--st-critical); }
        .ld-form textarea.input { resize: vertical; min-height: 5.5rem; line-height: 1.7; }
        .ld-form .msg { font-size: .75rem; line-height: 1.6; color: var(--st-critical); }
        .ld-form :not(.has-error) > .msg { display: none; }
        {{-- كلمة المرور: زرّ الإظهار في طرف الحقل، والنصّ لاتينيّ يبدأ بعده. --}}
        .ld-form .field > .pw { position: relative; display: block; font-size: inherit; color: inherit; }
        .ld-form .pw .input { padding-left: 2.7rem; }
        .ld-form .pw-toggle { position: absolute; inset-block: 0; left: 0; width: 2.7rem; display: grid; place-items: center; border: 0; background: none; color: hsl(var(--muted-foreground)); cursor: pointer; }
        .ld-form .pw-toggle:hover { color: hsl(var(--primary)); }
        .ld-form .pw-toggle svg { width: 17px; height: 17px; }
        .ld-form .pw-toggle .ico-hide, .ld-form .pw.is-shown .ico-show { display: none; }
        .ld-form .pw.is-shown .ico-hide { display: block; }
        .ld-form .err { margin-inline-end: 1.6rem; display: flex; gap: .7rem; align-items: flex-start; padding: .8rem 1rem; font-size: .84rem; line-height: 1.7; color: var(--st-critical); border: 1px solid color-mix(in srgb, var(--st-critical) 45%, transparent); background: color-mix(in srgb, var(--st-critical) 10%, transparent); }
        .ld-form .err svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: .2rem; }
        .ld-form .err span { display: block; color: hsl(var(--foreground) / .85); }
        {{-- نوع الحساب: بطاقتان، والمختارة بحدّ أزرق ومربّع ممتلئ. --}}
        .ld-reg-roles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .ld-reg-roles label { position: relative; cursor: pointer; }
        .ld-reg-roles input { position: absolute; opacity: 0; pointer-events: none; }
        .ld-reg-roles label > span { display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem; border: 1px solid var(--hair); background: hsl(var(--muted) / .35); transition: border-color .15s, background .15s; }
        .ld-reg-roles label:hover > span { border-color: hsl(var(--primary) / .55); }
        .ld-reg-roles svg { flex-shrink: 0; width: 22px; height: 22px; color: hsl(var(--muted-foreground)); transition: color .15s; }
        .ld-reg-roles b { flex: 1; font-size: .95rem; white-space: nowrap; }
        .ld-reg-roles .tick { flex-shrink: 0; display: grid; place-items: center; width: 16px; height: 16px; border: 1px solid hsl(var(--muted-foreground) / .6); }
        .ld-reg-roles .tick::after { content: ''; width: 8px; height: 8px; background: hsl(var(--primary)); transform: scale(0); transition: transform .15s; }
        .ld-reg-roles input:checked + span { border-color: hsl(var(--primary)); background: hsl(var(--primary) / .12); }
        .ld-reg-roles input:checked + span svg { color: hsl(var(--primary)); }
        .ld-reg-roles input:checked + span .tick { border-color: hsl(var(--primary)); }
        .ld-reg-roles input:checked + span .tick::after { transform: none; }
        .ld-reg-roles input:focus-visible + span { outline: 2px solid hsl(var(--primary)); outline-offset: 2px; }
        .ld-fs.has-error .ld-reg-roles label > span { border-color: var(--st-critical); }
        {{-- على الجوال يضيق العرض عن المربّع، والحدّ الأزرق يكفي للدلالة على المختار. --}}
        @media (max-width: 520px) {
            .ld-reg-roles label > span { padding: .75rem; gap: .6rem; }
            .ld-reg-roles .tick { display: none; }
        }
        /* عدد القوارب سؤال المالك وحده، فيأخذ اسم النشاط الصفّ كاملًا للدلال. */
        .ld-form:has(input[name="role"][value="dalal"]:checked) .only-owner { display: none; }
        .ld-form:has(input[name="role"][value="dalal"]:checked) .ld-biz { grid-column: 1 / -1; }
        {{-- الملاحظات مطويّة: قليلٌ من يحتاجها. --}}
        .ld-notes summary { display: inline-flex; align-items: center; gap: .45rem; cursor: pointer; list-style: none; font-size: .84rem; font-weight: 600; color: hsl(var(--primary)); }
        .ld-notes summary::-webkit-details-marker { display: none; }
        .ld-notes summary svg { width: 15px; height: 15px; transition: transform .15s; }
        .ld-notes[open] summary svg { transform: rotate(45deg); }
        .ld-notes .field { margin-top: .7rem; }
        .ld-form .busy, .ld-form.is-busy .idle { display: none; }
        .ld-form.is-busy .busy { display: inline; }
        .ld-form.is-busy button[type="submit"] { opacity: .75; cursor: progress; }
        .ld-reg-steps { list-style: none; margin: 1.2rem 0 0; padding: 0; display: grid; gap: .7rem; max-width: 42ch; }
        .ld-reg-steps li { display: flex; gap: .9rem; align-items: baseline; }
        .ld-reg-steps .n { font-family: 'Chakra Petch', monospace; font-weight: 600; font-size: .85rem; color: hsl(var(--primary)); }
        .ld-reg-steps b { font-size: .95rem; }
        .ld-reg-done { align-items: flex-start; justify-items: start; gap: .9rem; }
        .ld-reg-done h3 { font-size: 1.2rem; font-weight: 800; }
        .ld-reg-done p { font-size: .9rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }
        .ld-reg-done .ico-box { color: var(--st-good); }
        .hint { font-size: .78rem; color: hsl(var(--muted-foreground)); }

        {{-- مكان النموذج في القسم: اختيار نوع الحساب يفتح النافذة عليه. --}}
        .ld-join-cta { padding: clamp(20px, 3vw, 40px); gap: 1rem; align-items: flex-start; }
        .ld-join-cta h3 { font-size: 1.2rem; font-weight: 800; }
        .ld-join-roles { display: grid; gap: .7rem; width: 100%; }
        .ld-join-roles button { display: flex; align-items: center; gap: .9rem; width: 100%; padding: 1rem 1.1rem; text-align: start; font: inherit; color: inherit; cursor: pointer; border: 1px solid var(--hair); background: hsl(var(--muted) / .35); transition: border-color .15s, background .15s; }
        .ld-join-roles button > svg:first-child { flex-shrink: 0; width: 24px; height: 24px; color: hsl(var(--primary)); }
        .ld-join-roles button > svg:last-child { flex-shrink: 0; width: 18px; height: 18px; color: hsl(var(--muted-foreground)); transition: transform .15s, color .15s; }
        .ld-join-roles b { flex: 1; font-size: 1rem; }
        .ld-join-roles button:hover { border-color: hsl(var(--primary)); background: hsl(var(--primary) / .1); }
        .ld-join-roles button:hover > svg:last-child { color: hsl(var(--primary)); transform: translateX(-3px); }

        {{--
            نافذة التسجيل: <dialog> أصليّ والصندوق داخلها بطاقة بأقواس الزوايا. الرأس
            والذيل ثابتان، والحقول وحدها تتمرّر إن ضاقت الشاشة.
        --}}
        html:has(.ld-modal[open]) { overflow: hidden; }
        .ld-modal { border: 0; padding: 0; margin: auto; width: min(46rem, calc(100vw - 24px)); max-width: none; max-height: calc(100dvh - 24px); overflow: hidden; background: transparent; color: hsl(var(--foreground)); font-family: inherit; box-shadow: 0 28px 70px -20px rgba(0, 0, 0, .6); }
        .ld-modal[open] { display: flex; flex-direction: column; animation: ld-modal-in .18s ease-out; }
        .ld-modal::backdrop { background: rgba(2, 8, 20, .66); backdrop-filter: blur(3px); }
        @keyframes ld-modal-in { from { opacity: 0; transform: translateY(8px) scale(.985); } }
        .ld-modal-box { flex: 1 1 auto; min-height: 0; padding: 0; gap: 0; background: hsl(var(--background)); }
        {{-- بلا رأس: زرّ الإغلاق وحده في الزاوية، فيتّسع النموذج كله بلا تمرير. --}}
        .ld-modal-close { position: absolute; top: .5rem; left: .5rem; z-index: 4; }
        .ld-modal-form { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
        .ld-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; display: grid; gap: 1.1rem; padding: 1.3rem 1.4rem; }
        .ld-modal-foot { display: flex; flex-wrap: wrap; gap: .8rem; align-items: center; justify-content: space-between; padding: .9rem 1.4rem; border-top: 1px solid var(--hair); background: hsl(var(--muted) / .35); }
        .ld-modal-foot .actions { display: flex; flex-wrap: wrap; gap: .6rem; margin-inline-start: auto; }

        /* ---------- التذييل ---------- */
        .ld-foot { border-top: 1px solid var(--hair); padding: clamp(28px, 3.5vw, 44px) clamp(18px, 4vw, 40px) 20px; }
        .ld-foot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: clamp(24px, 3vw, 48px); }
        .ld-foot-grid > :first-child { grid-column: span 1; }
        @media (min-width: 900px) { .ld-foot-grid { grid-template-columns: 1.6fr 1fr 1fr 1fr; } }
        .ld-foot-brand img { height: 30px; width: auto; display: block; margin-bottom: 1rem; }
        .ld-foot-brand .mark-dark { display: none; }
        html.dark .ld-foot-brand .mark-light { display: none; }
        html.dark .ld-foot-brand .mark-dark { display: block; }
        .ld-foot p { font-size: .84rem; line-height: 1.75; color: hsl(var(--muted-foreground)); max-width: 36ch; }
        .ld-foot-col { display: grid; gap: .55rem; align-content: start; }
        .ld-foot-col .t { font-size: .74rem; font-weight: 700; letter-spacing: .04em; color: hsl(var(--primary)); margin-bottom: .3rem; }
        .ld-foot-col a { display: inline-flex; align-items: center; gap: .4rem; font-size: .86rem; color: hsl(var(--foreground) / .82); }
        .ld-foot-col a:hover { color: hsl(var(--primary)); }
        .ld-foot-col a svg { width: 14px; height: 14px; }
        .ld-foot-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: clamp(18px, 2.2vw, 28px); padding-top: 1rem; border-top: 1px solid var(--hair); font-size: .75rem; color: hsl(var(--muted-foreground)); }
    </style>
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <header class="ld-top">
        <div class="ld-top-in">
            <a class="ld-brand" href="#top" aria-label="{{ config('hawat.name') }} — الصفحة الرئيسية">
                {{-- الشريط أزرق في الوضعين، فالنسخة البيضاء وحدها تصلح عليه. --}}
                <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('hawat.name') }}">
            </a>

            <nav class="ld-nav" aria-label="التنقل الرئيسي">
                @foreach ($nav as $link)
                    <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <div class="ld-tools">
                <button class="icon-btn" type="button" onclick="toggleTheme()" title="تبديل الوضع" aria-label="تبديل الوضع الداكن والفاتح">
                    <span class="ico-moon">@include('partials.icon', ['name' => 'moon'])</span>
                    <span class="ico-sun">@include('partials.icon', ['name' => 'sun'])</span>
                </button>
                <a class="ld-top-cta" href="#register">انضم إلى حوات</a>
                <button class="icon-btn ld-menu-btn" type="button" onclick="toggleMenu()" aria-label="القائمة" aria-expanded="false" aria-controls="ld-drawer">
                    @include('partials.icon', ['name' => 'menu'])
                </button>
            </div>
        </div>
        <span class="ld-progress" id="ld-progress" aria-hidden="true"></span>
        <nav class="ld-drawer" id="ld-drawer" aria-label="قائمة الجوال">
            @foreach ($nav as $link)
                <a href="{{ $link['href'] }}" onclick="toggleMenu(false)">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main>
        {{-- الغلاف: البحر والقارب وحوات. --}}
        <section class="ld-hero" id="top" aria-labelledby="hero-h1">
            <div class="ld-hero-img" id="ld-parallax" aria-hidden="true"></div>
            <div class="ld-hero-veil" aria-hidden="true"></div>
            <div class="ld-hero-fade" aria-hidden="true"></div>

            <div class="ld-hero-in">
                <div class="ld-hero-copy">
                    <h1 class="ld-h1" id="hero-h1" data-reveal>
                        <span>حوات لأتمتة وإدارة</span>
                        <span>عمليات الصيد البحري</span>
                        <span>في المملكة العربية السعودية</span>
                    </h1>
                    <div class="ld-hero-cta" data-reveal>
                        <a class="btn btn-primary btn-lg" href="#register">
                            انضم إلى حوات
                            @include('partials.icon', ['name' => 'arrow-left'])
                        </a>
                        <a class="btn btn-outline btn-lg" href="#journey">تعرّف على المنصة</a>
                    </div>
                </div>

                <span class="ld-hero-mark" aria-hidden="true">
                    <span class="ld-hero-logo"></span>
                    <span class="scan"></span>
                </span>
            </div>
        </section>

        {{-- أبرز ما تقدّمه حوات في شريط واحد. --}}
        <section class="ld-value" id="value" aria-label="قيمة حوات">
            <div class="ld-wrap">
                <div class="card" data-reveal>
                    @foreach ($values as $value)
                        <div class="item">
                            <span class="ico-box">@include('partials.icon', ['name' => $value['icon']])</span>
                            <span>{{ $value['title'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- رحلة الصيد: سبع عُقد متّصلة، تُضاء تباعًا وتتبع المؤشّر. --}}
        <section class="ld-section ld-band" id="journey" aria-labelledby="jr-h2">
            <div class="ld-wrap">
                <h2 class="ld-h2 ld-head" id="jr-h2" data-reveal>رحلة الصيد من البحر إلى القرار</h2>

                <div class="card ld-journey" id="ld-journey" data-reveal>
                    @foreach ($journey as $node)
                        @if (! $loop->first)
                            <span class="ld-seg" data-seg="{{ $loop->index }}" aria-hidden="true"></span>
                        @endif
                        <div class="ld-node" data-node="{{ $loop->iteration }}">
                            <span class="ico-box">@include('partials.icon', ['name' => $node['icon']])</span>
                            <span class="lbl">{{ $node['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- الإمكانات: ثماني بطاقات، أيقونة وتسمية. --}}
        <section class="ld-section" id="features" aria-labelledby="cap-h2">
            <div class="ld-wrap">
                <h2 class="ld-h2 ld-head" id="cap-h2" data-reveal>منصة متكاملة بواجهة موحدة</h2>

                <div class="ld-caps">
                    @foreach ($capabilities as $cap)
                        <div class="card ld-card" data-reveal>
                            <span class="ico-box">@include('partials.icon', ['name' => $cap['icon']])</span>
                            <h3>{{ $cap['label'] }}</h3>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- التغطية: قائمة وخريطة. --}}
        <section class="ld-section ld-band" id="coverage" aria-labelledby="mp-h2">
            <div class="ld-wrap">
                <h2 class="ld-h2 ld-head" id="mp-h2" data-reveal>تغطية عمليات الصيد في المملكة</h2>

                <div class="ld-cov">
                    <div class="ld-cov-list" data-reveal>
                        @foreach ($coverage as $item)
                            <div class="row">
                                <span class="ico-box sm">@include('partials.icon', ['name' => $item['icon']])</span>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="card ld-map" data-reveal>
                        @include('partials.landing-map')
                    </div>
                </div>
            </div>
        </section>

        {{-- التطبيق --}}
        <section class="ld-section" id="app" aria-labelledby="app-h2">
            <div class="ld-wrap ld-app">
                <div data-reveal>
                    <h2 class="ld-h2" id="app-h2">حوات في متناول يدك</h2>
                    <div class="ld-stores">
                        <a class="btn btn-primary btn-lg" href="{{ $googlePlay }}" target="_blank" rel="noopener">
                            @include('partials.icon', ['name' => 'external-link'])
                            حمّل التطبيق من Google Play
                        </a>
                    </div>
                </div>
                <div class="ld-phones" data-reveal>
                    <div class="card ld-phone a">
                        <img src="{{ asset('images/landing/app-1.png') }}" alt="تطبيق حوات — شاشة البداية" width="360" height="640" loading="lazy">
                    </div>
                    <div class="card ld-phone b">
                        <img src="{{ asset('images/landing/app-2.png') }}" alt="تطبيق حوات — إدارة ذكية لرحلات الصيد البحري" width="360" height="640" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        @if ($showStats)
            {{-- الأرقام: مخفيّ حتى يؤكّد المالك القيم. --}}
            <section class="ld-section ld-band" id="numbers" aria-labelledby="nums-h2">
                <div class="ld-wrap">
                    <div class="ld-nums-head ld-head" data-reveal>
                        <h2 class="ld-h2" id="nums-h2">أرقام المنصة</h2>
                        <span class="code ld-flag">CONTENT_REQUIRES_OWNER_VERIFICATION</span>
                    </div>
                    <div class="ld-nums grid-auto">
                        @foreach ($stats as $stat)
                            <div class="card ld-card" data-reveal>
                                <span class="value" dir="ltr" data-count="{{ $stat['value'] }}">+{{ $stat['value'] }}</span>
                                <span class="lbl">{{ $stat['label'] }}</span>
                                @isset ($stat['unit'])
                                    <span class="ld-note">{{ $stat['unit'] }}</span>
                                @endisset
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{--
            انضم إلى حوات: مالك القوارب أو الدلال يطلب حسابه من هنا. الطلب
            يُحفظ بانتظار المدير العام (/admin/registrations)، ولا يُنشأ الحساب
            إلا عند الاعتماد — ثم يدخل صاحبه بجواله وكلمة المرور التي اختارها.
        --}}
        @php
            $reg = $errors->getBag('register');
            $regRole = old('role', 'owner');
        @endphp
        <section class="ld-section" id="register" aria-labelledby="ct-h2">
            <div class="ld-wrap ld-contact">
                <div data-reveal>
                    <h2 class="ld-h2" id="ct-h2">سجّل مالكًا أو دلالًا</h2>
                    <ol class="ld-reg-steps">
                        <li><span class="n">01</span><b>أرسل طلبك</b></li>
                        <li><span class="n">02</span><b>تراجعه إدارة حوات</b></li>
                        <li><span class="n">03</span><b>ادخل برقم جوالك</b></li>
                    </ol>
                    <div class="ld-contact-info">
                        <div class="card">
                            <span class="ico-box">@include('partials.icon', ['name' => 'mail'])</span>
                            <div>
                                <div class="k">البريد الإلكتروني</div>
                                <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a>
                            </div>
                        </div>
                        <div class="card">
                            <span class="ico-box">@include('partials.icon', ['name' => 'phone'])</span>
                            <div>
                                <div class="k">رقم الهاتف</div>
                                <a href="{{ $phoneHref }}" dir="ltr">{{ $phone }}</a>
                            </div>
                        </div>
                        {{-- العدّاد لا يسجّل هنا: توظّفه شركة التشغيل في جولاتها. --}}
                        <div class="card">
                            <span class="ico-box">@include('partials.icon', ['name' => 'clipboard-check'])</span>
                            <div>
                                <div class="k">تبحث عن عمل عدّادًا في الميناء؟</div>
                                <a href="{{ route('counter-apply') }}">قدّم في جولات التوظيف المفتوحة</a>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session('registered'))
                    <div class="card ld-join-cta ld-reg-done" data-reveal role="status">
                        <span class="ico-box lg">@include('partials.icon', ['name' => 'check-circle'])</span>
                        <h3>وصل طلبك، {{ session('registered') }}</h3>
                        <p>طلبك الآن بانتظار مراجعة إدارة حوات. بعد اعتماده تدخل التطبيق ولوحته على الويب برقم جوالك وكلمة المرور التي اخترتها.</p>
                    </div>
                @else
                    {{-- النموذج نفسه في نافذة #ld-join، وهنا يختار الزائر نوع حسابه فتُفتح عليه. --}}
                    <div class="card ld-join-cta" data-reveal>
                        <span class="ico-box lg">@include('partials.icon', ['name' => 'user-plus'])</span>
                        <h3>اختر نوع حسابك</h3>
                        <div class="ld-join-roles">
                            <button type="button" data-join-open="owner">
                                @include('partials.icon', ['name' => 'ship'])
                                <b>مالك قوارب</b>
                                @include('partials.icon', ['name' => 'arrow-left'])
                            </button>
                            <button type="button" data-join-open="dalal">
                                @include('partials.icon', ['name' => 'gavel'])
                                <b>دلال</b>
                                @include('partials.icon', ['name' => 'arrow-left'])
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>

    {{--
        نافذة طلب التسجيل: كل روابط #register تفتحها بدل النزول إلى قسم التسجيل، وتُفتح
        وحدها إن وصل الزائر على #register — وهو مرجع الإرسال بنجاحه أو بأخطائه.
    --}}
    <dialog class="ld-modal" id="ld-join" aria-label="طلب الانضمام إلى حوات">
        <div class="card ld-modal-box">
            <button class="icon-btn ld-modal-close" type="button" data-join-close aria-label="إغلاق">
                @include('partials.icon', ['name' => 'x'])
            </button>

            @if (session('registered'))
                <div class="ld-modal-body ld-reg-done" role="status">
                    <span class="ico-box lg">@include('partials.icon', ['name' => 'check-circle'])</span>
                    <h3>وصل طلبك، {{ session('registered') }}</h3>
                    <p>طلبك الآن بانتظار مراجعة إدارة حوات. بعد اعتماده تدخل التطبيق ولوحته على الويب برقم جوالك وكلمة المرور التي اخترتها.</p>
                </div>
                <div class="ld-modal-foot">
                    <div class="actions">
                        <button class="btn btn-primary btn-lg" type="button" data-join-close>تم</button>
                    </div>
                </div>
            @else
                @php
                    // ما لا حقل له في النموذج (reCAPTCHA) يُعرض في التنبيه، والباقي تحت حقله.
                    $regFields = ['role', 'name', 'phone', 'email', 'city', 'business_name', 'boats_count', 'password', 'notes'];
                    $regGeneral = collect($reg->getMessages())->except($regFields)->flatten();
                    $regOnFields = collect($reg->keys())->intersect($regFields)->isNotEmpty();
                @endphp
                <form class="ld-form ld-modal-form" id="ld-form" method="POST" action="{{ route('landing.register') }}#register">
                    @csrf
                    <input type="hidden" name="g-recaptcha-response">
                    <div class="ld-modal-body">
                        @if ($reg->any())
                            <div class="err" role="alert">
                                @include('partials.icon', ['name' => 'alert-triangle'])
                                <div>
                                    <b>لم يُرسَل الطلب</b>
                                    @foreach ($regGeneral as $message)<span>{{ $message }}</span>@endforeach
                                    @if ($regOnFields)<span>صحّح الحقول المعلَّمة بالأحمر ثم أعد الإرسال.</span>@endif
                                </div>
                            </div>
                        @endif

                        <fieldset @class(['ld-fs', 'has-error' => $reg->has('role')])>
                            <legend class="lbl">نوع الحساب</legend>
                            <div class="ld-reg-roles">
                                <label>
                                    <input type="radio" name="role" value="owner" @checked($regRole === 'owner')>
                                    <span>
                                        @include('partials.icon', ['name' => 'ship'])
                                        <b>مالك قوارب</b>
                                        <span class="tick" aria-hidden="true"></span>
                                    </span>
                                </label>
                                <label>
                                    <input type="radio" name="role" value="dalal" @checked($regRole === 'dalal')>
                                    <span>
                                        @include('partials.icon', ['name' => 'gavel'])
                                        <b>دلال</b>
                                        <span class="tick" aria-hidden="true"></span>
                                    </span>
                                </label>
                            </div>
                            @error('role', 'register')<small class="msg">{{ $message }}</small>@enderror
                        </fieldset>

                        <div class="ld-fields">
                            <label @class(['field', 'has-error' => $reg->has('name')])>
                                <span class="lbl">الاسم الكامل</span>
                                <input class="input" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                                @error('name', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'has-error' => $reg->has('phone')])>
                                <span class="lbl">رقم الجوال</span>
                                <input class="input dash" name="phone" value="{{ old('phone') }}" type="tel" dir="ltr" placeholder="05XXXXXXXX" required autocomplete="tel">
                                @error('phone', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'has-error' => $reg->has('email')])>
                                <span class="lbl">البريد الإلكتروني</span>
                                <input class="input" name="email" value="{{ old('email') }}" type="email" dir="ltr" maxlength="255" autocomplete="email">
                                @error('email', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'has-error' => $reg->has('city')])>
                                <span class="lbl">المدينة</span>
                                <input class="input" name="city" value="{{ old('city') }}" maxlength="120" autocomplete="address-level2">
                                @error('city', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'ld-biz', 'has-error' => $reg->has('business_name')])>
                                <span class="lbl">اسم المنشأة / النشاط</span>
                                <input class="input" name="business_name" value="{{ old('business_name') }}" maxlength="255" autocomplete="organization">
                                @error('business_name', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'only-owner', 'has-error' => $reg->has('boats_count')])>
                                <span class="lbl">عدد القوارب</span>
                                <input class="input dash" name="boats_count" value="{{ old('boats_count') }}" type="number" min="1" max="999" dir="ltr" inputmode="numeric">
                                @error('boats_count', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label @class(['field', 'has-error' => $reg->has('password')])>
                                <span class="lbl">كلمة المرور<em>8 أحرف على الأقل</em></span>
                                <span class="pw">
                                    <input class="input" name="password" type="password" dir="ltr" minlength="8" required autocomplete="new-password">
                                    <button class="pw-toggle" type="button" aria-label="إظهار كلمة المرور">
                                        <span class="ico-show">@include('partials.icon', ['name' => 'eye'])</span>
                                        <span class="ico-hide">@include('partials.icon', ['name' => 'eye-off'])</span>
                                    </button>
                                </span>
                                @error('password', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                            <label class="field">
                                <span class="lbl">تأكيد كلمة المرور</span>
                                <span class="pw">
                                    <input class="input" name="password_confirmation" type="password" dir="ltr" minlength="8" required autocomplete="new-password">
                                    <button class="pw-toggle" type="button" aria-label="إظهار كلمة المرور">
                                        <span class="ico-show">@include('partials.icon', ['name' => 'eye'])</span>
                                        <span class="ico-hide">@include('partials.icon', ['name' => 'eye-off'])</span>
                                    </button>
                                </span>
                            </label>
                        </div>

                        <details class="ld-notes" @if (old('notes') || $reg->has('notes')) open @endif>
                            <summary>@include('partials.icon', ['name' => 'plus'])أضف ملاحظة لفريق المراجعة</summary>
                            <label @class(['field', 'has-error' => $reg->has('notes')])>
                                <textarea class="input" name="notes" rows="2" maxlength="2000" aria-label="ملاحظات" placeholder="أي معلومة تساعدنا في مراجعة طلبك">{{ old('notes') }}</textarea>
                                @error('notes', 'register')<small class="msg">{{ $message }}</small>@enderror
                            </label>
                        </details>
                    </div>
                    <div class="ld-modal-foot">
                        <span class="hint">بعد اعتماد الإدارة تدخل برقم جوالك وكلمة المرور.</span>
                        <div class="actions">
                            <button class="btn btn-primary btn-lg" type="submit">
                                @include('partials.icon', ['name' => 'send'])
                                <span class="idle">إرسال الطلب</span>
                                <span class="busy">جارٍ الإرسال…</span>
                            </button>
                            <button class="btn btn-outline btn-lg" type="button" data-join-close>إلغاء</button>
                        </div>
                    </div>
                </form>
                @if (\App\Rules\Recaptcha::enabled())
                    {{-- reCAPTCHA v3: الرمز يُطلب لحظة الإرسال (صلاحيته دقيقتان) في سكربت النافذة أدناه. --}}
                    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
                @endif
            @endif
        </div>
    </dialog>

    <footer class="ld-foot">
        <div class="ld-wrap">
            <div class="ld-foot-grid">
                <div class="ld-foot-brand">
                    <img class="mark-light" src="{{ asset('images/logo.png') }}" alt="{{ config('hawat.name') }}">
                    <img class="mark-dark" src="{{ asset('images/logo-white.png') }}" alt="" aria-hidden="true">
                    <p>منصة لأتمتة وإدارة عمليات الصيد البحري في المملكة العربية السعودية.</p>
                </div>
                <nav class="ld-foot-col" aria-label="روابط الموقع">
                    <span class="t">الموقع</span>
                    @foreach ($nav as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
                <div class="ld-foot-col">
                    <span class="t">التواصل</span>
                    <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a>
                    <a href="{{ $phoneHref }}" dir="ltr">{{ $phone }}</a>
                    <a href="{{ $facebook }}" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'external-link']) فيسبوك</a>
                </div>
                <div class="ld-foot-col">
                    <span class="t">التطبيق</span>
                    <a href="{{ $googlePlay }}" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'external-link']) Google Play</a>
                </div>
            </div>
            <div class="ld-foot-bar">
                <span>جميع الحقوق محفوظة — حوات · مؤسسة دار الحوت للتجارة</span>
                <span><span class="code">HAWAT</span> / حوات</span>
            </div>
        </div>
    </footer>

    <script>
        function toggleTheme() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('hawat-theme', dark ? 'dark' : 'light');
        }

        function toggleMenu(open) {
            const drawer = document.getElementById('ld-drawer');
            const state = drawer.classList.toggle('is-open', open);
            document.querySelector('.ld-menu-btn').setAttribute('aria-expanded', state);
        }

        // نافذة التسجيل: روابط #register وأزرار نوع الحساب تفتحها بدل النزول إلى القسم.
        (function () {
            const dialog = document.getElementById('ld-join');
            const form = document.getElementById('ld-form');

            function open(role) {
                const radio = role && form && form.querySelector('input[name="role"][value="' + role + '"]');
                if (radio) radio.checked = true;
                if (!dialog.open) dialog.showModal();
                // أول حقلٍ فيه خطأ وإلا الاسم، ولوحة المفاتيح الافتراضية لا تُفتح وحدها على الجوال.
                if (form && window.matchMedia('(pointer: fine)').matches) {
                    (form.querySelector('.has-error .input') || form.querySelector('input[name="name"]')).focus();
                }
            }

            if (form) {
                // خطأ الخادم يزول عن حقله حين يُعدَّل.
                form.addEventListener('input', function (event) {
                    const field = event.target.closest('.has-error');
                    if (field) field.classList.remove('has-error');
                });

                form.querySelectorAll('.pw-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const input = btn.previousElementSibling;
                        const show = input.type === 'password';
                        input.type = show ? 'text' : 'password';
                        btn.parentElement.classList.toggle('is-shown', show);
                        btn.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
                    });
                });

                const pw = form.querySelector('input[name="password"]');
                const pw2 = form.querySelector('input[name="password_confirmation"]');
                function matchPasswords() {
                    pw2.setCustomValidity(pw2.value && pw2.value !== pw.value ? 'كلمتا المرور غير متطابقتين.' : '');
                }
                pw.addEventListener('input', matchPasswords);
                pw2.addEventListener('input', matchPasswords);

                // الإرسال يُقفل الزرّ ويُعلن الانتظار، ورمز reCAPTCHA يُطلب لحظتها إن كان مفعّلًا.
                const siteKey = @js(\App\Rules\Recaptcha::enabled() ? config('services.recaptcha.site_key') : null);
                const submit = form.querySelector('button[type="submit"]');
                function busy(on) {
                    submit.disabled = on;
                    form.classList.toggle('is-busy', on);
                }
                form.addEventListener('submit', function (event) {
                    if (form.dataset.verified) return;
                    busy(true);
                    if (!siteKey || !window.grecaptcha) return;
                    event.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute(siteKey, { action: 'register' }).then(function (token) {
                            form.elements['g-recaptcha-response'].value = token;
                            form.dataset.verified = '1';
                            form.submit();
                        }, function () { busy(false); });
                    });
                });
                // العودة بزرّ الرجوع تُعيد الصفحة من الذاكرة: يُفكّ القفل.
                window.addEventListener('pageshow', function (event) {
                    if (!event.persisted) return;
                    busy(false);
                    delete form.dataset.verified;
                });
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('a[href="#register"], [data-join-open]');
                if (trigger) {
                    event.preventDefault();
                    open(trigger.dataset.joinOpen);
                } else if (event.target.closest('[data-join-close]')) {
                    dialog.close();
                }
            });

            // النقر على الخلفية يغلقها، لا سحبٌ بدأ داخل حقل وانتهى خارجه.
            let downOnBackdrop = false;
            dialog.addEventListener('pointerdown', function (event) { downOnBackdrop = event.target === dialog; });
            dialog.addEventListener('click', function (event) {
                if (downOnBackdrop && event.target === dialog) dialog.close();
            });

            dialog.addEventListener('close', function () {
                if (location.hash === '#register') history.replaceState(null, '', location.pathname + location.search);
            });

            if (location.hash === '#register') open();
        })();

        (function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // شريط التقدّم أسفل الشريط العلوي، وانزياح صورة الغلاف الخفيف مع التمرير.
            const progress = document.getElementById('ld-progress');
            const parallax = document.getElementById('ld-parallax');
            let raf = null;
            window.addEventListener('scroll', function () {
                if (raf) return;
                raf = requestAnimationFrame(function () {
                    raf = null;
                    const h = document.documentElement.scrollHeight - window.innerHeight;
                    progress.style.width = (h > 0 ? Math.min(100, window.scrollY / h * 100) : 0) + '%';
                    if (!reduce) parallax.style.transform = 'translate3d(0,' + (Math.min(window.scrollY, 900) * 0.16).toFixed(1) + 'px,0)';
                });
            }, { passive: true });

            // الظهور عند دخول العنصر مجال الرؤية، مرّة واحدة لكل عنصر.
            const reveals = document.querySelectorAll('[data-reveal]');
            if (reduce || !('IntersectionObserver' in window)) {
                reveals.forEach(function (el) { el.classList.add('is-in'); });
            } else {
                reveals.forEach(function (el, i) { el.style.transitionDelay = (i % 6) * 60 + 'ms'; });
                const io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (!e.isIntersecting) return;
                        e.target.classList.add('is-in');
                        io.unobserve(e.target);
                    });
                }, { threshold: 0.08, rootMargin: '0px 0px -50px 0px' });
                reveals.forEach(function (el) { io.observe(el); });
                // احتياط: ما لم يُرَ خلال ثانيتين ونصف يُظهَر على أي حال.
                setTimeout(function () { reveals.forEach(function (el) { el.classList.add('is-in'); }); }, 2500);
            }

            // رحلة الصيد: العُقد تُضاء تباعًا كل 2.4 ثانية، والمؤشّر يملك المسار عند المرور.
            const journey = document.getElementById('ld-journey');
            const nodes = journey.querySelectorAll('[data-node]');
            const segs = journey.querySelectorAll('[data-seg]');
            let owned = false;
            function light(k) {
                nodes.forEach(function (n) { n.classList.toggle('is-on', parseInt(n.dataset.node, 10) <= k); });
                segs.forEach(function (s) { s.classList.toggle('is-on', parseInt(s.dataset.seg, 10) < k); });
            }
            nodes.forEach(function (n) {
                n.addEventListener('mouseenter', function () { owned = true; light(parseInt(n.dataset.node, 10)); });
            });
            journey.addEventListener('mouseleave', function () { owned = false; });
            if (!reduce) {
                let i = 0;
                setInterval(function () {
                    if (owned) return;
                    i = (i % nodes.length) + 1;
                    light(i);
                }, 2400);
            }

            // عدّادات الأرقام إن كان القسم ظاهرًا.
            const counters = document.querySelectorAll('[data-count]');
            if (counters.length && !reduce && 'IntersectionObserver' in window) {
                const cio = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (!e.isIntersecting) return;
                        cio.unobserve(e.target);
                        const target = parseInt(e.target.dataset.count, 10), t0 = performance.now(), dur = 1200;
                        (function tick(now) {
                            const p = Math.min(1, (now - t0) / dur);
                            e.target.textContent = '+' + Math.round(target * (1 - Math.pow(1 - p, 3)));
                            if (p < 1) requestAnimationFrame(tick);
                        })(t0);
                    });
                }, { threshold: 0.4 });
                counters.forEach(function (el) { cio.observe(el); });
            }

            // تمييز رابط القسم الظاهر في شريط التنقّل.
            const links = document.querySelectorAll('.ld-nav a');
            const targets = Array.from(links).map(function (a) { return document.querySelector(a.getAttribute('href')); }).filter(Boolean);
            if (targets.length && 'IntersectionObserver' in window) {
                const nio = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (!e.isIntersecting) return;
                        links.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + e.target.id); });
                    });
                }, { rootMargin: '-40% 0px -55% 0px' });
                targets.forEach(function (t) { nio.observe(t); });
            }
        })();
    </script>
</body>
</html>
