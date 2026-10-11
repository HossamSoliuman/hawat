@php
    /*
     * صفحة الهبوط العامة على الجذر "/". المحتوى وترتيب الأقسام من التصميم المعتمد
     * (docs/حوات/Waiting on PDF and logo — HUWAT Landing Page v2 Dark)، أمّا
     * الألوان والخطوط فمن نظام اللوحة نفسه: partials/styles في الوضعين.
     *
     * قسم الأرقام محفوظ في المصدر لكنه مخفيّ حتى يؤكّد المالك قيمه (+439 / +129 /
     * +241 / +149) — انظر CONTENT-AUDIT.md §13.
     */
    $showStats = false;

    $nav = [
        ['href' => '#top', 'label' => 'الرئيسية'],
        ['href' => '#about', 'label' => 'عن حوات'],
        ['href' => '#features', 'label' => 'المميزات'],
        ['href' => '#app', 'label' => 'التطبيق'],
        ['href' => '#register', 'label' => 'سجّل معنا'],
    ];

    // مراحل الرحلة الستّ كما وردت في التعريف بالمشروع.
    $steps = [
        ['icon' => 'anchor', 'label' => 'انطلاق القارب'],
        ['icon' => 'waves', 'label' => 'رحلة الصيد'],
        ['icon' => 'fish', 'label' => 'تسجيل المصيد'],
        ['icon' => 'store', 'label' => 'السوق / الحراج'],
        ['icon' => 'handshake', 'label' => 'البيع'],
        ['icon' => 'line-chart', 'label' => 'الإيرادات والبيانات'],
    ];

    // مسار العمليات السبع: من الكابتن إلى التقارير.
    $journey = [
        ['icon' => 'user', 'label' => 'الكابتن', 'sub' => 'يبدأ الرحلة'],
        ['icon' => 'ship', 'label' => 'الرحلة', 'sub' => 'في البحر'],
        ['icon' => 'fish', 'label' => 'المصيد', 'sub' => 'تسجيل الكميات والأنواع'],
        ['icon' => 'anchor', 'label' => 'الإنزال', 'sub' => 'في الموانئ'],
        ['icon' => 'gavel', 'label' => 'الحراج', 'sub' => 'عرض وبيع المصيد'],
        ['icon' => 'shopping-cart', 'label' => 'البيع', 'sub' => 'إتمام العمليات'],
        ['icon' => 'bar-chart', 'label' => 'التقارير', 'sub' => 'بيانات تدعم القرار'],
    ];

    $coverage = [
        ['icon' => 'waves', 'label' => 'المنطقة الغربية — البحر الأحمر'],
        ['icon' => 'waves', 'label' => 'المنطقة الشرقية — الخليج العربي'],
        ['icon' => 'anchor', 'label' => 'موانئ الصيد'],
        ['icon' => 'map-pin', 'label' => 'مناطق الصيد على امتداد السواحل'],
        ['icon' => 'store', 'label' => 'أسواق الحراج'],
        ['icon' => 'route', 'label' => 'مسارات الرحلات'],
    ];

    // الإمكانات الست بعد العرضين الكبيرين (لوحة التحكم والتتبع)، فترقيمها يبدأ من 03.
    $capabilities = [
        ['icon' => 'shopping-cart', 'label' => 'إدارة عمليات البيع'],
        ['icon' => 'bar-chart', 'label' => 'تقارير الأداء والتحليل'],
        ['icon' => 'users', 'label' => 'إدارة المستخدمين والصلاحيات'],
        ['icon' => 'archive', 'label' => 'أرشفة السجلات والبيانات'],
        ['icon' => 'message-square', 'label' => 'التواصل مع كباتن القوارب'],
        ['icon' => 'bell', 'label' => 'نظام الإشعارات الفورية'],
    ];

    /*
     * لقطات المنصة في public/images/landing: التُقطت من شاشات اللوحة نفسها
     * (gov/overview، gov/sea-map، admin/trips، stats/approved-catch، stats/markets،
     * stats/reports) بقياس 1440×900، وصورتا التطبيق من صفحته على Google Play.
     */
    $screenshots = [
        ['icon' => 'layout-dashboard', 'label' => 'لوحة العمليات', 'img' => 'dashboard'],
        ['icon' => 'map-pin', 'label' => 'تتبع القوارب', 'img' => 'tracking'],
        ['icon' => 'route', 'label' => 'الرحلات', 'img' => 'trips'],
        ['icon' => 'fish', 'label' => 'تسجيل المصيد', 'img' => 'catch'],
        ['icon' => 'store', 'label' => 'السوق / الحراج', 'img' => 'market'],
        ['icon' => 'bar-chart', 'label' => 'التقارير', 'img' => 'reports'],
    ];

    $operations = [
        ['icon' => 'layout-dashboard', 'title' => 'إدارة متكاملة', 'body' => 'متابعة القوارب والرحلات والمصيد وعمليات البيع من منظومة واحدة.'],
        ['icon' => 'activity', 'title' => 'تقارير وتحليلات لحظية', 'body' => 'قراءة فورية لأداء العمليات وحركة المصيد والمبيعات.'],
        ['icon' => 'shield-check', 'title' => 'دعم الجهات التنظيمية', 'body' => 'سجلات وبيانات منظمة تخدم متطلبات المتابعة والرقابة.'],
        ['icon' => 'plug', 'title' => 'سهولة الربط والاستخدام', 'body' => 'واجهات مبسّطة تناسب كباتن القوارب وفرق التشغيل.'],
    ];

    $roles = [
        ['icon' => 'ship', 'label' => 'الكابتن', 'sub' => 'انطلاق القارب والرحلة'],
        ['icon' => 'fish', 'label' => 'الصياد / صاحب القارب', 'sub' => 'رحلة الصيد وتسجيل المصيد'],
        ['icon' => 'clipboard', 'label' => 'العدّاد', 'sub' => 'تسجيل الإنزال والكميات'],
        ['icon' => 'store', 'label' => 'الدلال', 'sub' => 'عرض المصيد في السوق / الحراج'],
        ['icon' => 'handshake', 'label' => 'التاجر', 'sub' => 'إتمام عمليات البيع'],
        ['icon' => 'line-chart', 'label' => 'الإدارة', 'sub' => 'البيانات والتقارير'],
    ];

    // القيم الثلاث: عناوينها في شريط القيمة بعد الغلاف، وشرحها في "لماذا حوات؟".
    $values = [
        ['icon' => 'layers', 'title' => 'إدارة رقمية متكاملة', 'body' => 'ربط مراحل التشغيل ضمن مسار رقمي منظم.'],
        ['icon' => 'shield-check', 'title' => 'شفافية تشغيلية', 'body' => 'تسجيل ومتابعة العمليات والبيانات عبر مراحل رحلة الصيد.'],
        ['icon' => 'bar-chart', 'title' => 'بيانات تدعم القرار', 'body' => 'تحويل البيانات التشغيلية إلى تقارير وتحليلات قابلة للاستفادة.'],
    ];

    $vision = [
        ['icon' => 'workflow', 'label' => 'التحول الرقمي'],
        ['icon' => 'gauge', 'label' => 'الكفاءة'],
        ['icon' => 'shield-check', 'label' => 'الشفافية'],
        ['icon' => 'database', 'label' => 'البنية التحتية الرقمية'],
        ['icon' => 'flag', 'label' => 'رؤية المملكة 2030'],
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

        {{-- الأكواد اللاتينية (CAP-01، HAWAT / DASHBOARD…) على الخطّ الهندسي بتباعد حروف خفيف. --}}
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
        .ld-eyebrow { display: flex; align-items: center; gap: .6rem; margin-bottom: .7rem; font-size: 11px; font-weight: 700; color: hsl(var(--primary)); letter-spacing: .02em; }
        .ld-eyebrow .n { font-family: 'Chakra Petch', monospace; font-size: 12px; letter-spacing: .08em; }
        .ld-eyebrow::before { content: ''; width: 28px; height: 1px; background: hsl(var(--primary)); }
        .ld-h2 { font-size: clamp(21px, 2.2vw, 30px); font-weight: 800; line-height: 1.3; text-wrap: balance; }
        .ld-sub { margin-top: .4rem; font-size: clamp(14px, 1.05vw, 16px); font-weight: 500; color: hsl(var(--primary)); }
        .ld-lead { margin-top: .8rem; font-size: clamp(14px, 1vw, 15.5px); line-height: 1.85; color: hsl(var(--muted-foreground)); max-width: 64ch; text-wrap: pretty; }
        .ld-head { margin-bottom: clamp(18px, 2.2vw, 28px); max-width: 64ch; }
        .ld-head-split { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: clamp(20px, 2.5vw, 48px); align-items: start; margin-bottom: clamp(18px, 2.2vw, 28px); }
        .ld-head-split .ld-lead { margin-top: 0; padding-top: .35rem; }
        .ld-note { font-size: 12px; line-height: 1.8; color: hsl(var(--muted-foreground)); }

        {{-- البطاقات على قاعدة اللوحة: .card تحمل الأقواس والخطّ الشعري، وهنا الحشوة والمرور فقط. --}}
        .ld-card { padding: clamp(18px, 2vw, 26px); gap: .9rem; transition: border-color .15s ease, background .15s ease; }
        .ld-card:hover { border-color: hsl(var(--primary) / .6); background: hsl(var(--primary) / .05); }
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
        {{-- إطار اللقطة: شريطٌ علويّ بعنوان الشاشة ثم الصورة، على قاعدة البطاقة نفسها. --}}
        .ld-shot { min-width: 0; border: 1px solid var(--hair); background: hsl(var(--foreground) / .03); }
        .ld-shot .bar { display: flex; align-items: center; gap: .5rem; padding: .6rem .9rem; border-bottom: 1px solid var(--hair); }
        .ld-shot .bar::before { content: ''; width: 7px; height: 7px; background: hsl(var(--primary)); }
        .ld-shot img { display: block; width: 100%; height: auto; aspect-ratio: 16 / 10; object-fit: cover; object-position: top; }

        {{-- الظهور عند التمرير: يُطفأ كلّه مع تفضيل تقليل الحركة. --}}
        [data-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .6s cubic-bezier(0,0,.2,1), transform .6s cubic-bezier(0,0,.2,1); }
        [data-reveal].is-in { opacity: 1; transform: none; }
        @keyframes ld-pulse { 0%, 100% { opacity: .5; } 50% { opacity: .12; } }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
            * { animation: none !important; }
        }

        /* ---------- 01 الغلاف ---------- */
        .ld-hero { position: relative; overflow: hidden; min-height: clamp(440px, 66vh, 680px); display: grid; align-items: center; scroll-margin-top: 3.75rem; }
        {{-- الصورة تستقرّ يسارًا (القارب) والنصّ يمينًا فوق حجابٍ بلون الصفحة يذوب نحو الصورة. --}}
        .ld-hero-img { position: absolute; inset: 0; background: url('{{ asset('images/landing-hero.jpg') }}') left 58% / cover no-repeat; will-change: transform; }
        .ld-hero-veil { position: absolute; inset: 0; background: linear-gradient(to left, hsl(var(--background)) 0%, hsl(var(--background) / .94) 40%, hsl(var(--background) / .5) 70%, hsl(var(--background) / .18) 100%); }
        .ld-hero-fade { position: absolute; inset: 0; background: linear-gradient(to bottom, hsl(var(--background) / .55) 0%, hsl(var(--background) / .1) 30%, hsl(var(--background) / .75) 70%, hsl(var(--background)) 100%); }
        {{-- النصّ يأخذ العمود الأوسع والبطاقة عرضها الثابت، فتبقى سطور العنوان كما كُتبت. --}}
        .ld-hero-in { position: relative; max-width: 1280px; width: 100%; margin: 0 auto; padding: clamp(28px, 4vw, 56px) clamp(18px, 4vw, 40px) clamp(18px, 2.5vw, 28px); display: grid; grid-template-columns: minmax(0, 1fr); gap: clamp(24px, 3vw, 44px); align-items: center; }
        .ld-hero-copy { position: relative; min-width: 0; }
        .ld-hero-copy::before { content: ''; position: absolute; inset: -28px -32px; background: radial-gradient(120% 100% at 100% 50%, hsl(var(--background) / .96) 0%, hsl(var(--background) / .9) 55%, hsl(var(--background) / 0) 100%); pointer-events: none; }
        .ld-hero-copy > * { position: relative; }
        .ld-chip { display: inline-flex; align-items: center; gap: .55rem; padding: .3rem .7rem; margin-bottom: 1rem; font-size: 11.5px; font-weight: 700; color: hsl(var(--primary)); border: 1px solid hsl(var(--primary) / .45); background: hsl(var(--primary) / .08); }
        .ld-chip::before { content: ''; width: 6px; height: 6px; background: hsl(var(--primary)); }
        {{-- العنوان والفقرة سطورٌ مقصودة: كلّ <span> سطرٌ على الشاشة الواسعة، وتنساب على الضيّقة. --}}
        .ld-h1 { font-size: clamp(26px, 3vw, 40px); font-weight: 800; line-height: 1.28; margin-bottom: 1rem; text-wrap: balance; }
        .ld-hero-sub { font-size: clamp(14px, 1.1vw, 16px); line-height: 1.85; color: hsl(var(--muted-foreground)); max-width: 58ch; margin-bottom: 1.3rem; text-wrap: pretty; }
        .ld-hero-cta { display: flex; flex-wrap: wrap; gap: .8rem; }
        {{-- لوحة حالة الرحلة: بطاقة واحدة على يسار الغلاف بقيم توضيحية. --}}
        .ld-trip { display: none; justify-self: center; width: 22rem; gap: .9rem; background: hsl(var(--background) / .72); backdrop-filter: blur(10px); }
        .ld-trip .top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .ld-trip .top b { font-size: .92rem; font-weight: 700; }
        .ld-trip .rows { display: grid; gap: .5rem; }
        .ld-trip .row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .55rem .7rem; border: 1px solid var(--hair); font-size: .82rem; }
        .ld-trip .row .value { font-family: 'Chakra Petch', monospace; font-size: 1rem; font-weight: 600; color: hsl(var(--primary)); }
        .ld-trip .row .value small { font-family: 'Tajawal', sans-serif; font-size: .72rem; font-weight: 500; color: hsl(var(--muted-foreground)); }
        @media (max-width: 719px) {
            .ld-hero { min-height: 0; }
            .ld-hero-veil { background: hsl(var(--background) / .82); }
        }
        @media (min-width: 720px) {
            .ld-h1 span, .ld-hero-sub span { display: block; }
        }
        @media (min-width: 900px) {
            .ld-hero-in { grid-template-columns: minmax(0, 1fr) auto; }
            .ld-trip { display: flex; }
        }

        /* ---------- 02 شريط القيمة ---------- */
        .ld-value { padding: 0 clamp(18px, 4vw, 40px); margin-top: -1px; }
        .ld-value .card { flex-direction: row; flex-wrap: wrap; padding: 0; }
        .ld-value .item { flex: 1 1 250px; display: flex; align-items: center; gap: .9rem; padding: .9rem 1.1rem; font-size: .92rem; font-weight: 700; border-inline-end: 1px solid var(--hair); }
        .ld-value .item:last-child { border-inline-end: 0; }

        /* ---------- 03 عن حوات: ستّ مراحل مرقّمة ---------- */
        .ld-steps { list-style: none; --col: 190px; }
        .ld-steps .card { align-items: start; }
        .ld-steps .num { font-family: 'Chakra Petch', monospace; font-size: 12px; letter-spacing: .1em; color: hsl(var(--muted-foreground)); }
        .ld-steps .lbl { font-size: .92rem; font-weight: 700; }

        /* ---------- 04 رحلة الصيد: سبع عُقد على خطّ واحد ---------- */
        .ld-journey { padding: clamp(20px, 2.5vw, 34px) clamp(14px, 2vw, 24px); flex-direction: row; flex-wrap: wrap; align-items: flex-start; justify-content: center; gap: 10px 4px; }
        .ld-node { display: grid; gap: .6rem; justify-items: center; text-align: center; flex: 1 1 150px; min-width: 132px; padding: 4px 6px; cursor: default; }
        .ld-node .ico-box { transition: background .2s, border-color .2s; }
        .ld-node.is-on .ico-box { background: hsl(var(--primary) / .22); border-color: hsl(var(--primary)); }
        .ld-node .lbl { font-size: .92rem; font-weight: 700; }
        .ld-node .sub { font-size: .75rem; line-height: 1.6; color: hsl(var(--muted-foreground)); }
        .ld-seg { flex: 0 0 24px; height: 1px; margin-top: calc(1.25rem + 4px); background: hsl(var(--primary)); opacity: .3; transition: opacity .25s; }
        .ld-seg.is-on { opacity: 1; }
        @media (max-width: 719px) { .ld-seg { display: none; } }
        {{-- على الشاشة العريضة السبع في صفّ واحد لا تنكسر. --}}
        @media (min-width: 1024px) { .ld-journey { flex-wrap: nowrap; } .ld-node { flex: 1 1 0; min-width: 0; } }

        /* ---------- 05 التغطية: قائمة وخريطة ---------- */
        .ld-cov { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: clamp(16px, 2vw, 28px); align-items: start; }
        .ld-cov-list { display: grid; gap: 10px; align-content: start; }
        .ld-cov-list .row { display: flex; align-items: center; gap: .8rem; min-height: 42px; padding: .5rem .8rem; border: 1px solid var(--hair); font-size: .88rem; font-weight: 600; }
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

        /* ---------- 06 الإمكانات ---------- */
        {{-- العرضان الكبيران: النصّ في العمود الأضيق واللقطة في الأوسع. --}}
        .ld-show { display: grid; grid-template-columns: minmax(0, 1fr); gap: clamp(16px, 2vw, 36px); align-items: center; padding: clamp(16px, 2vw, 26px); margin-bottom: var(--gap); }
        .ld-show .copy { display: grid; gap: .8rem; align-content: start; }
        .ld-show h3 { font-size: clamp(18px, 1.5vw, 22px); font-weight: 800; line-height: 1.35; }
        .ld-show p { font-size: .88rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }
        @media (min-width: 900px) { .ld-show { grid-template-columns: minmax(0, 2fr) minmax(0, 3fr); } }
        .ld-caps { --col: 190px; margin-bottom: clamp(18px, 2.2vw, 28px); }
        .ld-caps .card { align-items: start; }
        .ld-caps .ico-box { margin-top: .2rem; }
        {{-- شبكة اللقطات: ثلاثٌ في الصفّ على الشاشة الواسعة، والتسمية تحت كلّ صورة. --}}
        .ld-shots-head { margin-bottom: 18px; }
        .ld-shots-head h3 { font-size: .98rem; font-weight: 700; }
        .ld-shots { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: var(--gap); }
        @media (min-width: 900px) { .ld-shots { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .ld-shots figure { margin: 0; padding: 0; gap: 0; transition: border-color .15s ease; }
        .ld-shots figure:hover { border-color: hsl(var(--primary) / .6); }
        .ld-shots img { display: block; width: 100%; height: auto; aspect-ratio: 16 / 10; object-fit: cover; object-position: top; border-bottom: 1px solid var(--hair); }
        .ld-shots figcaption { display: flex; align-items: center; gap: .7rem; padding: .65rem .8rem; font-size: .88rem; font-weight: 700; }

        /* ---------- 07 إدارة العمليات ---------- */
        .ld-ops { --col: 280px; }
        .ld-ops .card { padding: clamp(18px, 2.4vw, 28px); gap: .8rem; align-items: start; }

        /* ---------- 08 المشاركون: ستّة على خطّ واحد ---------- */
        .ld-roles { position: relative; flex-direction: row; flex-wrap: wrap; padding: clamp(6px, 1vw, 10px); gap: 0; }
        .ld-roles::before { content: ''; position: absolute; inset-inline: clamp(24px, 3vw, 40px); top: calc(clamp(18px, 2vw, 26px) + clamp(6px, 1vw, 10px) + 1.25rem); height: 1px; background: linear-gradient(to left, transparent, hsl(var(--primary) / .55) 12%, hsl(var(--primary) / .55) 88%, transparent); pointer-events: none; }
        .ld-role { position: relative; z-index: 1; flex: 1 1 190px; display: grid; gap: .5rem; align-content: start; padding: clamp(18px, 2vw, 26px) clamp(14px, 1.6vw, 20px); }
        .ld-role .ico-box { background: hsl(var(--background)); }
        .ld-role .lbl { font-size: .92rem; font-weight: 700; }
        .ld-role .sub { font-size: .76rem; line-height: 1.7; color: hsl(var(--muted-foreground)); }
        /* ---------- 09 لماذا حوات: ثلاثة صفوف مرقّمة ---------- */
        .ld-why { display: grid; grid-template-columns: auto 1fr; gap: clamp(16px, 3vw, 44px); align-items: baseline; padding-block: clamp(16px, 2.2vw, 24px); border-top: 1px solid hsl(var(--border)); }
        .ld-why:last-child { border-bottom: 1px solid hsl(var(--border)); }
        .ld-why .num { font-family: 'Chakra Petch', monospace; font-size: clamp(22px, 2.4vw, 32px); font-weight: 600; line-height: 1; color: hsl(var(--primary)); }
        .ld-why .txt { min-width: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 12px clamp(20px, 3vw, 48px); align-items: baseline; }
        .ld-why h3 { font-size: clamp(16.5px, 1.4vw, 19px); font-weight: 800; }
        .ld-why p { font-size: .88rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }

        /* ---------- 10 الرؤية ---------- */
        .ld-vision { --col: 210px; }
        .ld-vision .card { align-items: start; }
        .ld-vision .lbl { font-size: .92rem; font-weight: 700; }

        /* ---------- 11 التطبيق ---------- */
        .ld-app { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: clamp(24px, 3vw, 48px); align-items: center; }
        .ld-stores { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.1rem; }
        {{-- صورتا التطبيق من متجر Google Play بنسبة 9:16، الثانية أكبر قليلًا وأمام الأولى. --}}
        .ld-phones { min-width: 0; display: flex; gap: clamp(12px, 2vw, 24px); justify-content: center; align-items: flex-end; }
        .ld-phone { padding: 8px; }
        .ld-phone img { display: block; width: 100%; height: auto; aspect-ratio: 9 / 16; object-fit: cover; }
        .ld-phone.a { width: min(40%, 210px); }
        .ld-phone.b { width: min(48%, 250px); }

        /* ---------- 12 الأرقام (مخفيّ) ---------- */
        .ld-nums-head { display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: 1rem; }
        .ld-nums { --col: 220px; }
        .ld-nums .value { font-family: 'Chakra Petch', monospace; font-size: clamp(28px, 3.2vw, 42px); font-weight: 600; line-height: 1; color: hsl(var(--primary)); }
        .ld-nums .lbl { font-size: .88rem; font-weight: 600; }
        .ld-flag { align-self: start; font-size: 10px; letter-spacing: .08em; padding: .25rem .6rem; color: var(--st-warn); border: 1px solid color-mix(in srgb, var(--st-warn) 45%, transparent); background: color-mix(in srgb, var(--st-warn) 10%, transparent); }

        /* ---------- 13 انضم إلى حوات ---------- */
        .ld-contact { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: clamp(20px, 3vw, 44px); align-items: start; }
        .ld-contact-info { display: grid; gap: .7rem; margin-top: 1.2rem; max-width: 42ch; }
        .ld-contact-info .card { flex-direction: row; align-items: center; gap: .9rem; padding: 1rem 1.2rem; }
        .ld-contact-info .k { font-size: .75rem; color: hsl(var(--muted-foreground)); margin-bottom: .2rem; }
        .ld-contact-info a { font-size: .98rem; font-weight: 600; color: hsl(var(--primary)); }
        .ld-form .row { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr)); gap: 1.2rem; }
        .ld-form .input { padding: .7rem .85rem; font-size: .92rem; }
        .ld-form .field > span { font-size: .78rem; }
        .ld-form textarea.input { resize: vertical; min-height: 8rem; line-height: 1.7; }
        .ld-form .err { display: grid; gap: .25rem; padding: .8rem 1rem; font-size: .84rem; color: var(--st-critical); border: 1px solid color-mix(in srgb, var(--st-critical) 45%, transparent); background: color-mix(in srgb, var(--st-critical) 10%, transparent); }
        .ld-reg-roles { border: 0; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr)); gap: .8rem; }
        .ld-reg-roles legend { font-size: .78rem; margin-bottom: .5rem; padding: 0; }
        .ld-reg-roles label { position: relative; cursor: pointer; }
        .ld-reg-roles input { position: absolute; opacity: 0; pointer-events: none; }
        .ld-reg-roles label > span { display: grid; grid-template-columns: auto 1fr; column-gap: .7rem; row-gap: .15rem; align-items: center; padding: .9rem 1rem; border: 1px solid var(--hair); background: hsl(var(--muted) / .35); transition: border-color .15s, background .15s; }
        .ld-reg-roles label > span svg { grid-row: span 2; width: 22px; height: 22px; color: hsl(var(--muted-foreground)); }
        .ld-reg-roles b { font-size: .95rem; }
        .ld-reg-roles small { font-size: .74rem; color: hsl(var(--muted-foreground)); }
        .ld-reg-roles input:checked + span { border-color: hsl(var(--primary)); background: hsl(var(--primary) / .12); }
        .ld-reg-roles input:checked + span svg { color: hsl(var(--primary)); }
        .ld-reg-roles input:focus-visible + span { outline: 2px solid hsl(var(--primary)); outline-offset: 2px; }
        /* عدد القوارب سؤال المالك وحده. */
        .ld-form:has(input[name="role"][value="dalal"]:checked) .only-owner { visibility: hidden; }
        .ld-reg-steps { list-style: none; margin: 1.2rem 0 0; padding: 0; display: grid; gap: .9rem; max-width: 42ch; }
        .ld-reg-steps li { display: flex; gap: .9rem; align-items: flex-start; }
        .ld-reg-steps .n { font-family: 'Chakra Petch', monospace; font-weight: 600; font-size: .85rem; color: hsl(var(--primary)); padding-top: .1rem; }
        .ld-reg-steps b { display: block; font-size: .95rem; margin-bottom: .15rem; }
        .ld-reg-steps li span:not(.n) { font-size: .84rem; line-height: 1.7; color: hsl(var(--muted-foreground)); }
        .ld-reg-done { align-items: flex-start; justify-items: start; gap: .9rem; }
        .ld-reg-done h3 { font-size: 1.2rem; font-weight: 800; }
        .ld-reg-done p { font-size: .9rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }
        .ld-reg-done .ico-box { color: var(--st-good); }
        .hint { font-size: .78rem; color: hsl(var(--muted-foreground)); }

        {{-- مكان النموذج في القسم: اختيار نوع الحساب يفتح النافذة عليه. --}}
        .ld-join-cta { padding: clamp(20px, 3vw, 40px); gap: 1rem; align-items: flex-start; }
        .ld-join-cta h3 { font-size: 1.2rem; font-weight: 800; }
        .ld-join-cta p { font-size: .9rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }
        .ld-join-roles { display: grid; gap: .7rem; width: 100%; }
        .ld-join-roles button { display: grid; grid-template-columns: auto 1fr auto; column-gap: .9rem; row-gap: .15rem; align-items: center; width: 100%; padding: 1rem 1.1rem; text-align: start; font: inherit; color: inherit; cursor: pointer; border: 1px solid var(--hair); background: hsl(var(--muted) / .35); transition: border-color .15s, background .15s; }
        .ld-join-roles button > svg:first-child { grid-column: 1; grid-row: 1 / span 2; width: 24px; height: 24px; color: hsl(var(--primary)); }
        .ld-join-roles button > svg:last-child { grid-column: 3; grid-row: 1 / span 2; width: 18px; height: 18px; color: hsl(var(--muted-foreground)); transition: transform .15s, color .15s; }
        .ld-join-roles b, .ld-join-roles small { grid-column: 2; }
        .ld-join-roles b { font-size: 1rem; }
        .ld-join-roles small { font-size: .78rem; color: hsl(var(--muted-foreground)); }
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
        .ld-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--hair); }
        .ld-modal-head .ld-eyebrow { margin-bottom: .45rem; }
        .ld-modal-head h2 { font-size: clamp(18px, 1.6vw, 21px); font-weight: 800; line-height: 1.35; }
        .ld-modal-steps { list-style: none; display: flex; flex-wrap: wrap; gap: .35rem 1.1rem; margin-top: .6rem; font-size: .78rem; color: hsl(var(--muted-foreground)); }
        .ld-modal-steps li { display: flex; align-items: center; gap: .4rem; }
        .ld-modal-steps .n { font-family: 'Chakra Petch', monospace; font-weight: 600; font-size: .74rem; color: hsl(var(--primary)); }
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
        {{-- 01 الغلاف: البحر والقارب وحوات. --}}
        <section class="ld-hero" id="top" aria-labelledby="hero-h1">
            <div class="ld-hero-img" id="ld-parallax" aria-hidden="true"></div>
            <div class="ld-hero-veil" aria-hidden="true"></div>
            <div class="ld-hero-fade" aria-hidden="true"></div>

            <div class="ld-hero-in">
                <div class="ld-hero-copy">
                    <span class="ld-chip" data-reveal>منصة عمليات الصيد البحري</span>
                    <h1 class="ld-h1" id="hero-h1" data-reveal>
                        <span>حوات لأتمتة وإدارة</span>
                        <span>عمليات الصيد البحري</span>
                        <span>في المملكة العربية السعودية</span>
                    </h1>
                    <p class="ld-hero-sub" data-reveal>
                        <span>منظومة رقمية متكاملة لإدارة عمليات الصيد البحري،</span>
                        <span>تربط القارب والرحلة والمصيد والإنزال والبيع في مسار واحد،</span>
                        <span>وتحوّل بياناتها إلى تقارير وتحليلات تدعم القرار.</span>
                    </p>
                    <div class="ld-hero-cta" data-reveal>
                        <a class="btn btn-primary btn-lg" href="#register">
                            انضم إلى حوات
                            @include('partials.icon', ['name' => 'arrow-left'])
                        </a>
                        <a class="btn btn-outline btn-lg" href="#about">تعرّف على المنصة</a>
                    </div>
                </div>

                <div class="card ld-card ld-trip" data-reveal>
                    <div class="top">
                        <b>حالة الرحلة</b>
                        <span class="badge badge-info">عرض توضيحي</span>
                    </div>
                    <div class="rows">
                        <div class="row"><span>سرعة القارب</span><span class="value"><bdi>9.2</bdi> <small>عقدة</small></span></div>
                        <div class="row"><span>اتجاه المسار</span><span class="value" dir="ltr">285°</span></div>
                        <div class="row"><span>الموقع</span><span class="value" dir="ltr">26.5°N 50.0°E</span></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 02 قيمة حوات: العناوين الثلاثة في شريط واحد. --}}
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

        {{-- 03 عن مشروع حوات --}}
        <section class="ld-section" id="about" aria-labelledby="about-h2">
            <div class="ld-wrap">
                <div class="ld-head-split">
                    <div data-reveal>
                        <div class="ld-eyebrow"><span class="n">03</span><span>عن مشروع حوات</span></div>
                        <h2 class="ld-h2" id="about-h2">رحلة الصيد كاملة، مُسجّلة رقمياً من انطلاق القارب إلى الإيرادات</h2>
                    </div>
                    <p class="ld-lead" data-reveal>يربط حوات مراحل العمل البحري في مسار واحد: انطلاق القارب، ثم رحلة الصيد، ثم تسجيل المصيد، ثم عرضه في السوق أو الحراج، ثم إتمام البيع، وانتهاءً بالإيرادات والبيانات التي تُبنى عليها التقارير.</p>
                </div>

                <ol class="ld-steps grid-auto">
                    @foreach ($steps as $step)
                        <li class="card ld-card" data-reveal>
                            <span class="num">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="ico-box">@include('partials.icon', ['name' => $step['icon']])</span>
                            <span class="lbl">{{ $step['label'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- 04 رحلة الصيد: سبع عُقد متّصلة، تُضاء تباعًا وتتبع المؤشّر. --}}
        <section class="ld-section ld-band" id="journey" aria-labelledby="jr-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">04</span><span>مسار العمليات</span></div>
                    <h2 class="ld-h2" id="jr-h2">رحلة الصيد</h2>
                    <p class="ld-sub">من البحر إلى القرار</p>
                </div>

                <div class="card ld-journey" id="ld-journey" data-reveal>
                    @foreach ($journey as $node)
                        @if (! $loop->first)
                            <span class="ld-seg" data-seg="{{ $loop->index }}" aria-hidden="true"></span>
                        @endif
                        <div class="ld-node" data-node="{{ $loop->iteration }}">
                            <span class="ico-box">@include('partials.icon', ['name' => $node['icon']])</span>
                            <span class="lbl">{{ $node['label'] }}</span>
                            <span class="sub">{{ $node['sub'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 05 تغطية عمليات الصيد --}}
        <section class="ld-section" id="coverage" aria-labelledby="mp-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">05</span><span>الموقع والتشغيل</span></div>
                    <h2 class="ld-h2" id="mp-h2">تغطية عمليات الصيد</h2>
                    <p class="ld-sub">في المملكة العربية السعودية</p>
                    <p class="ld-lead">يربط حوات النشاط البحري بالموقع والرحلة والإنزال والعملية التشغيلية على امتداد سواحل البحر الأحمر والخليج العربي.</p>
                </div>

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

        {{-- 06 الإمكانات: عرضان كبيران ثم ستّ بطاقات ثم مواضع اللقطات. --}}
        <section class="ld-section ld-band" id="features" aria-labelledby="cap-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">06</span><span>إمكانات حوات</span></div>
                    <h2 class="ld-h2" id="cap-h2">منصة متكاملة بواجهة موحدة</h2>
                </div>

                <div class="card ld-show" data-reveal>
                    <div class="copy">
                        <span class="code">01</span>
                        <h3>لوحة التحكم الرئيسية</h3>
                        <p>نقطة واحدة لمتابعة حالة العمليات اليومية: الرحلات، المصيد، البيع، والتقارير.</p>
                    </div>
                    <div class="ld-shot">
                        <div class="bar"><span class="code">HAWAT / DASHBOARD</span></div>
                        <img src="{{ asset('images/landing/dashboard.jpg') }}" alt="لوحة التحكم الرئيسية في حوات: مؤشرات المصيد والرحلات والقوارب ورسوم الإنتاج" width="1440" height="900" loading="lazy">
                    </div>
                </div>

                <div class="card ld-show" data-reveal>
                    <div class="copy">
                        <span class="code">02</span>
                        <h3>واجهة تتبع القوارب</h3>
                        <p>متابعة حركة القوارب ورحلاتها على واجهة تتبع واحدة.</p>
                    </div>
                    <div class="ld-shot">
                        <div class="bar"><span class="code">HAWAT / SEA MAP</span></div>
                        <img src="{{ asset('images/landing/tracking.jpg') }}" alt="الخريطة البحرية في حوات: الموانئ والقوارب النشطة ومناطق الحظر على خريطة تفاعلية" width="1440" height="900" loading="lazy">
                    </div>
                </div>

                <div class="ld-caps grid-auto">
                    @foreach ($capabilities as $cap)
                        <div class="card ld-card" data-reveal>
                            <span class="code">{{ sprintf('%02d', $loop->iteration + 2) }}</span>
                            <span class="ico-box">@include('partials.icon', ['name' => $cap['icon']])</span>
                            <h3>{{ $cap['label'] }}</h3>
                        </div>
                    @endforeach
                </div>

                <div data-reveal>
                    <div class="ld-shots-head">
                        <h3>لقطات من المنصة</h3>
                    </div>
                    <div class="ld-shots">
                        @foreach ($screenshots as $shot)
                            <figure class="card">
                                <img src="{{ asset('images/landing/' . $shot['img'] . '.jpg') }}" alt="شاشة {{ $shot['label'] }} في حوات" width="1440" height="900" loading="lazy">
                                <figcaption>
                                    <span class="ico-box sm">@include('partials.icon', ['name' => $shot['icon']])</span>
                                    <span>{{ $shot['label'] }}</span>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- 07 إدارة العمليات --}}
        <section class="ld-section" id="operations" aria-labelledby="ops-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">07</span><span>إدارة العمليات</span></div>
                    <h2 class="ld-h2" id="ops-h2">إدارة متكاملة لعمليات الصيد البحري</h2>
                </div>
                <div class="ld-ops grid-auto">
                    @foreach ($operations as $op)
                        <div class="card ld-card" data-reveal>
                            <span class="ico-box lg">@include('partials.icon', ['name' => $op['icon']])</span>
                            <h3>{{ $op['title'] }}</h3>
                            <p>{{ $op['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 08 المشاركون في العمليات --}}
        <section class="ld-section ld-band" id="roles" aria-labelledby="ro-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">08</span><span>المشاركون في العمليات</span></div>
                    <h2 class="ld-h2" id="ro-h2">منظومة متكاملة لأطراف الصيد</h2>
                    <p class="ld-lead">يشارك جميع أطراف العملية في المسار نفسه، كل طرف في المرحلة التي يعمل فيها، فتبقى بيانات الرحلة والمصيد والبيع متصلة في منظومة واحدة.</p>
                </div>
                <div class="card ld-roles" data-reveal>
                    @foreach ($roles as $role)
                        <div class="ld-role">
                            <span class="ico-box">@include('partials.icon', ['name' => $role['icon']])</span>
                            <span class="lbl">{{ $role['label'] }}</span>
                            <span class="sub">{{ $role['sub'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 09 لماذا حوات؟ --}}
        <section class="ld-section" id="why" aria-labelledby="why-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">09</span><span>لماذا حوات</span></div>
                    <h2 class="ld-h2" id="why-h2">لماذا حوات؟</h2>
                </div>
                <div>
                    @foreach ($values as $value)
                        <div class="ld-why" data-reveal>
                            <span class="num">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <div class="txt">
                                <h3>{{ $value['title'] }}</h3>
                                <p>{{ $value['body'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 10 الرؤية --}}
        <section class="ld-section ld-band" id="vision" aria-labelledby="vision-h2">
            <div class="ld-wrap">
                <div class="ld-head" data-reveal>
                    <div class="ld-eyebrow"><span class="n">10</span><span>الرؤية</span></div>
                    <h2 class="ld-h2" id="vision-h2">التحول الرقمي في قطاع الصيد البحري</h2>
                    <p class="ld-lead">يعمل حوات على رفع الكفاءة وتعزيز الشفافية في عمليات الصيد، وبناء بنية تحتية رقمية للقطاع تنسجم مع توجهات التحول الرقمي في رؤية المملكة 2030.</p>
                </div>
                <div class="ld-vision grid-auto">
                    @foreach ($vision as $pillar)
                        <div class="card ld-card" data-reveal>
                            <span class="ico-box">@include('partials.icon', ['name' => $pillar['icon']])</span>
                            <span class="lbl">{{ $pillar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 11 التطبيق --}}
        <section class="ld-section" id="app" aria-labelledby="app-h2">
            <div class="ld-wrap ld-app">
                <div data-reveal>
                    <div class="ld-eyebrow"><span class="n">11</span><span>تطبيق حوات</span></div>
                    <h2 class="ld-h2" id="app-h2">حوات في متناول يدك</h2>
                    <p class="ld-lead">يتيح التطبيق تسجيل الرحلات والمصيد ومتابعة عمليات البيع والتواصل مع فريق التشغيل من الجهاز المحمول.</p>
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
            {{-- 12 الأرقام: مخفيّ حتى يؤكّد المالك القيم. --}}
            <section class="ld-section ld-band" id="numbers" aria-labelledby="nums-h2">
                <div class="ld-wrap">
                    <div class="ld-nums-head ld-head" data-reveal>
                        <div>
                            <div class="ld-eyebrow"><span class="n">12</span><span>الإنجازات الرقمية</span></div>
                            <h2 class="ld-h2" id="nums-h2">أرقام المنصة</h2>
                        </div>
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
            13 انضم إلى حوات: مالك القوارب أو الدلال يطلب حسابه من هنا. الطلب
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
                    <div class="ld-eyebrow"><span class="n">13</span><span>انضم إلى حوات</span></div>
                    <h2 class="ld-h2" id="ct-h2">سجّل مالكًا أو دلالًا</h2>
                    <ol class="ld-reg-steps">
                        <li><span class="n">01</span><div><b>أرسل طلبك</b><span>بياناتك ونوع حسابك وكلمة المرور التي ستدخل بها.</span></div></li>
                        <li><span class="n">02</span><div><b>تراجعه إدارة حوات</b><span>نتحقّق من البيانات وقد نتواصل معك على جوالك.</span></div></li>
                        <li><span class="n">03</span><div><b>ادخل التطبيق واللوحة</b><span>بعد الاعتماد تدخل برقم جوالك وكلمة المرور مباشرة.</span></div></li>
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
                        <p>يُفتح نموذج الطلب على النوع الذي تختاره، ويمكنك تغييره فيه.</p>
                        <div class="ld-join-roles">
                            <button type="button" data-join-open="owner">
                                @include('partials.icon', ['name' => 'ship'])
                                <b>مالك قوارب</b>
                                <small>رحلة الصيد وتسجيل المصيد</small>
                                @include('partials.icon', ['name' => 'arrow-left'])
                            </button>
                            <button type="button" data-join-open="dalal">
                                @include('partials.icon', ['name' => 'gavel'])
                                <b>دلال</b>
                                <small>عرض المصيد في السوق / الحراج</small>
                                @include('partials.icon', ['name' => 'arrow-left'])
                            </button>
                        </div>
                        <span class="hint">لا يُنشأ الحساب إلا بعد اعتماد الإدارة.</span>
                    </div>
                @endif
            </div>
        </section>
    </main>

    {{--
        نافذة طلب التسجيل: كل روابط #register تفتحها بدل النزول إلى القسم 13، وتُفتح
        وحدها إن وصل الزائر على #register — وهو مرجع الإرسال بنجاحه أو بأخطائه.
    --}}
    <dialog class="ld-modal" id="ld-join" aria-labelledby="ld-join-title">
        <div class="card ld-modal-box">
            <div class="ld-modal-head">
                <div>
                    <div class="ld-eyebrow"><span>انضم إلى حوات</span></div>
                    <h2 id="ld-join-title">سجّل مالكًا أو دلالًا</h2>
                    @unless (session('registered'))
                        <ol class="ld-modal-steps">
                            <li><span class="n">01</span>أرسل طلبك</li>
                            <li><span class="n">02</span>تراجعه إدارة حوات</li>
                            <li><span class="n">03</span>ادخل بجوالك وكلمة المرور</li>
                        </ol>
                    @endunless
                </div>
                <button class="icon-btn" type="button" data-join-close aria-label="إغلاق">
                    @include('partials.icon', ['name' => 'x'])
                </button>
            </div>

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
                <form class="ld-form ld-modal-form" id="ld-form" method="POST" action="{{ route('landing.register') }}#register">
                    @csrf
                    <input type="hidden" name="g-recaptcha-response">
                    <div class="ld-modal-body">
                        @if ($reg->any())
                            <div class="err" role="alert">
                                @foreach ($reg->all() as $message)<div>{{ $message }}</div>@endforeach
                            </div>
                        @endif

                        <fieldset class="ld-reg-roles">
                            <legend>نوع الحساب *</legend>
                            <label>
                                <input type="radio" name="role" value="owner" @checked($regRole === 'owner')>
                                <span>@include('partials.icon', ['name' => 'ship'])<b>مالك قوارب</b></span>
                            </label>
                            <label>
                                <input type="radio" name="role" value="dalal" @checked($regRole === 'dalal')>
                                <span>@include('partials.icon', ['name' => 'gavel'])<b>دلال</b></span>
                            </label>
                        </fieldset>

                        <div class="row">
                            <label class="field"><span>الاسم الكامل *</span><input class="input" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></label>
                            <label class="field"><span>رقم الجوال *</span><input class="input dash" name="phone" value="{{ old('phone') }}" type="tel" dir="ltr" placeholder="05XXXXXXXX" required autocomplete="tel"></label>
                        </div>
                        <div class="row">
                            <label class="field"><span>البريد الإلكتروني</span><input class="input" name="email" value="{{ old('email') }}" type="email" dir="ltr" autocomplete="email"></label>
                            <label class="field"><span>المدينة</span><input class="input" name="city" value="{{ old('city') }}" maxlength="120"></label>
                        </div>
                        <div class="row">
                            <label class="field"><span>اسم المنشأة / النشاط</span><input class="input" name="business_name" value="{{ old('business_name') }}" maxlength="255" autocomplete="organization"></label>
                            <label class="field only-owner"><span>عدد القوارب</span><input class="input" name="boats_count" value="{{ old('boats_count') }}" type="number" min="1" max="999" dir="ltr"></label>
                        </div>
                        <div class="row">
                            <label class="field"><span>كلمة المرور *</span><input class="input" name="password" type="password" dir="ltr" minlength="8" required autocomplete="new-password"></label>
                            <label class="field"><span>تأكيد كلمة المرور *</span><input class="input" name="password_confirmation" type="password" dir="ltr" minlength="8" required autocomplete="new-password"></label>
                        </div>
                        <label class="field"><span>ملاحظات</span><textarea class="input" name="notes" rows="3" maxlength="2000" placeholder="أي معلومة تساعدنا في مراجعة طلبك">{{ old('notes') }}</textarea></label>
                    </div>
                    <div class="ld-modal-foot">
                        <span class="hint">لا يُنشأ الحساب إلا بعد اعتماد الإدارة.</span>
                        <div class="actions">
                            <button class="btn btn-primary btn-lg" type="submit">
                                @include('partials.icon', ['name' => 'send'])
                                إرسال الطلب
                            </button>
                            <button class="btn btn-outline btn-lg" type="button" data-join-close>إلغاء</button>
                        </div>
                    </div>
                </form>
                @if (\App\Rules\Recaptcha::enabled())
                    {{-- reCAPTCHA v3: رمز جديد لحظة الإرسال (صلاحيته دقيقتان). --}}
                    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
                    <script>
                        document.getElementById('ld-form').addEventListener('submit', function (event) {
                            const form = event.target;
                            if (form.dataset.verified) return;
                            event.preventDefault();
                            const btn = form.querySelector('button[type="submit"]');
                            btn.disabled = true;
                            grecaptcha.ready(function () {
                                grecaptcha.execute(@js(config('services.recaptcha.site_key')), { action: 'register' }).then(function (token) {
                                    form.elements['g-recaptcha-response'].value = token;
                                    form.dataset.verified = '1';
                                    form.submit();
                                }, function () { btn.disabled = false; });
                            });
                        });
                    </script>
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
                // لوحة المفاتيح الافتراضية لا تُفتح وحدها على الجوال.
                if (form && window.matchMedia('(pointer: fine)').matches) form.querySelector('input[name="name"]').focus();
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
