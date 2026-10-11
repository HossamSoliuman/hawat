<?php

$domain = env('APP_DOMAIN') ?: parse_url((string) env('APP_URL'), PHP_URL_HOST);

/*
 * إعدادات المالك: بياناته الأساسية التي يُدخلها مرّة وتعتمد عليها الرحلات
 * والمبيعات والرواتب. كل تبويب صفحته القائمة بمسارها، تُعرض تحت ترويسة
 * الإعدادات وشريط تبويباتها — انظر panel/owner/settings/layout.
 */
$ownerSettings = [
    'boats' => ['label' => 'القوارب', 'icon' => 'ship', 'route' => 'panel.owner.boats'],
    'captains' => ['label' => 'الكباتن', 'icon' => 'user-check', 'route' => 'panel.owner.captains'],
    'crew' => ['label' => 'الطاقم', 'icon' => 'users', 'route' => 'panel.owner.crew'],
    'employees' => ['label' => 'الموظفون', 'icon' => 'user', 'route' => 'panel.owner.employees'],
    'customers' => ['label' => 'العملاء', 'icon' => 'handshake', 'route' => 'panel.owner.customers'],
    'vendors' => ['label' => 'الموردون', 'icon' => 'truck', 'route' => 'panel.owner.vendors'],
];

return [
    /*
     * مضيفا المنتجين — انظر routes/web.php:
     *
     *   domain      — النطاق الرئيسي (hawat.sa): صفحة الهبوط وتطبيق حوات على /admin.
     *                 يُشتق من APP_URL ما لم يُضبط APP_DOMAIN.
     *   gov_domain  — بوابات الوزارة الثلاث (gov.hawat.sa)، وصفحة اختيارها على "/".
     *                 الافتراضي gov.<النطاق الرئيسي>، ومحليًا gov.hawat.test.
     */
    'domain' => $domain,
    'gov_domain' => env('GOV_DOMAIN') ?: 'gov.'.$domain,

    'name' => 'حوات',
    'tagline' => 'لوحة وزارة البيئة والمياه والزراعة',
    'ministry' => 'وزارة البيئة والمياه والزراعة',
    'sector' => 'قطاع المصايد البحرية',
    'logo' => 'https://media.base44.com/images/public/6a7a814ea23d8fee1b1c5058/2b4d90b35_logo-arabic-png.png',

    /*
     * نسبة ضريبة القيمة المضافة المقترحة في نماذج المصروفات (السعودية 15%).
     * تُحفظ النسبة على كل سند، فتغييرها هنا لا يمسّ ما سُجّل قبلها.
     */
    'vat_rate' => (float) env('HAWAT_VAT_RATE', 15),

    /*
     * أربع بوابات في منتجين، لكلٍّ مضيفه:
     *
     *   الوزارة     — على gov_domain، وصناديقها الثلاثة صفحةُ "/" هناك:
     *                 "الإحصاء" تحت /stats (ومعها شاشة العرض تحت /screens)،
     *                 و"الخدمات والتراخيص" تحت /services، و"إدارة النظام"
     *                 تحت /subadmin ومعها بوابة المعلومات.
     *   تطبيق حوات — على النطاق الرئيسي تحت /admin، وقائمته تتبدّل مع دور
     *                 المستخدم (nav_panel).
     *
     * App\Support\Nav يختار بينها حسب اسم المسار. البوابتان الأخيرتان خلف
     * تسجيل الدخول، والإحصاء والخدمات مفتوحتان.
     *
     * لوحات كل قسم لا تظهر إلا في قائمته: البوابة الواحدة تعرض قائمتها وحدها،
     * ولا يُكرَّر التبويب في بوابتين.
     */
    'portals' => [
        'stats' => [
            'label' => 'الإحصاء',
            'icon' => 'database',
            'home' => 'stats.executive-briefing',
        ],
        'services' => [
            'label' => 'الخدمات والتراخيص',
            'icon' => 'headset',
            'home' => 'services.fisher-services',
        ],
        /*
         * قسم الإدارة الفرعية وبوابة المعلومات بوابةٌ واحدة باسم واحد ودخول
         * واحد تحت /subadmin. رئيستها رئيسة بوابة المعلومات: نظرة عامة على
         * صفحاتها.
         */
        'subadmin' => [
            'label' => 'إدارة النظام',
            // لا 'settings': ترسها ثمانية أشعة حول دائرة، فتُقرأ شمسًا في صندوق البوابة.
            'icon' => 'user-cog',
            'home' => 'admin.index',
        ],
        'ops' => [
            'label' => 'تطبيق حوات',
            'icon' => 'smartphone',
            'home' => 'panel.home',
        ],
    ],

    /*
     * شاشة العرض — لوحات القاعة تحت /screens، تُفتح بملء الشاشة. ليست بوابة: تبويبها
     * الوحيد في قائمة الإحصاء (screens.home)، وهذه القائمة مربّعات شاشة الاختيار
     * نفسها. أي لوحة تُضاف هنا تظهر مربّعًا تلقائيًا، عدا screens.home نفسها.
     */
    'nav_screens' => [
        [
            'title' => 'عام',
            'items' => [
                ['label' => 'شاشة العرض', 'route' => 'screens.home', 'icon' => 'layout-dashboard'],
                ['label' => 'المؤشرات العامة', 'route' => 'screens.overview', 'icon' => 'gauge'],
                ['label' => 'الخريطة البحرية', 'route' => 'screens.sea-map', 'icon' => 'map'],
            ],
        ],
        [
            'title' => 'الإنتاج',
            'items' => [
                ['label' => 'الإنتاج السمكي', 'route' => 'screens.production', 'icon' => 'fish'],
                ['label' => 'مقارنة الموانئ', 'route' => 'screens.ports-compare', 'icon' => 'git-compare'],
            ],
        ],
        [
            'title' => 'الاستدامة',
            'items' => [
                // الرقابة والامتثال انتقلت إلى قسم الخدمات والتراخيص مع الرخص
                // التي تُخالَف شروطها.
                ['label' => 'الاستدامة والمخزون', 'route' => 'screens.sustainability', 'icon' => 'leaf'],
            ],
        ],
    ],

    /*
     * قسم الإحصاء — ست عشرة لوحة كانت موزّعة بين البوابتين فجُمعت في بوابة واحدة.
     * ترتيب المجموعات يتبع مسار البيانات: مؤشرات، ثم رصد ميداني واعتماد، ثم تحليل
     * وتقارير، ثم ما بعد الاعتماد من أسواق وأمن غذائي.
     *
     * "شاشة العرض" أولها: لوحات القاعة كلها (nav_screens) خلف تبويب واحد، فيبقى
     * نشطًا على أيٍّ منها ('active').
     */
    'nav_stats' => [
        [
            'title' => 'المؤشرات واللوحات التنفيذية',
            'items' => [
                ['label' => 'شاشة العرض', 'route' => 'screens.home', 'icon' => 'layout-dashboard', 'active' => 'screens.*'],
                ['label' => 'موجز الإدارة العليا', 'route' => 'stats.executive-briefing', 'icon' => 'crown'],
                ['label' => 'المؤشرات الوطنية', 'route' => 'stats.national-indicators', 'icon' => 'bar-chart'],
                ['label' => 'مقارنة الأداء', 'route' => 'stats.performance-compare', 'icon' => 'gauge'],
            ],
        ],
        [
            'title' => 'الرصد الميداني والاعتماد',
            'items' => [
                ['label' => 'الإحصاء الميداني', 'route' => 'stats.field-statistics', 'icon' => 'clipboard'],
                ['label' => 'المصيد المعتمد', 'route' => 'stats.approved-catch', 'icon' => 'badge-check'],
                ['label' => 'موظفو الإحصاء', 'route' => 'stats.statistics-officers', 'icon' => 'user-cog'],
                ['label' => 'تتبع المصيد', 'route' => 'stats.catch-trace', 'icon' => 'link'],
            ],
        ],
        [
            'title' => 'التحليلات والتقارير',
            'items' => [
                ['label' => 'التحليلات والمؤشرات', 'route' => 'stats.analytics', 'icon' => 'line-chart'],
                ['label' => 'حوات AI', 'route' => 'stats.ai-assistant', 'icon' => 'bot'],
                ['label' => 'التقارير', 'route' => 'stats.reports', 'icon' => 'file-text'],
                ['label' => 'تقارير الإنتاج الشهرية', 'route' => 'stats.monthly-reports', 'icon' => 'file-chart'],
                ['label' => 'النشرة السنوية', 'route' => 'stats.annual-bulletin', 'icon' => 'book-open'],
            ],
        ],
        [
            'title' => 'الأسواق والأمن الغذائي',
            'items' => [
                ['label' => 'الأسواق والمزادات', 'route' => 'stats.markets', 'icon' => 'store'],
                ['label' => 'سلسلة الإمداد', 'route' => 'stats.supply-chain', 'icon' => 'truck'],
                ['label' => 'الأمن الغذائي', 'route' => 'stats.food-security', 'icon' => 'utensils'],
            ],
        ],
    ],

    /*
     * إدارة النظام — قائمة واحدة لنصفين تحت /subadmin: لوحات القسم وتبويبات
     * بوابة المعلومات. القائمة نفسها تُرسم في النصفين، فيرى الداخل قسمًا واحدًا
     * أينما كان.
     *
     * عنصر بـ 'tab' تبويبٌ من config/info.php يأخذ اسمه وأيقونته من هناك —
     * انظر App\Support\Nav::sections(). وكل تبويب هناك له موضعه هنا. و'label'
     * على العنصر اسمٌ أقصر للقائمة وحدها، ويبقى عنوان الصفحة باسمه الكامل.
     */
    'nav_subadmin' => [
        [
            'title' => 'الأشخاص والصلاحيات',
            'items' => [
                ['tab' => 'permissions', 'label' => 'المستخدمون'],
                ['label' => 'الهيكل التنظيمي', 'route' => 'subadmin.org-structure', 'icon' => 'git-branch'],
                ['label' => 'إدارة الموظفين', 'route' => 'subadmin.staff-management', 'icon' => 'users'],
            ],
        ],
        [
            'title' => 'المهام والتنبيهات',
            'items' => [
                ['label' => 'تقويم المهام الإدارية', 'route' => 'subadmin.admin-tasks', 'icon' => 'calendar-days'],
                ['label' => 'التنبيهات الإدارية', 'route' => 'subadmin.staff-notifications', 'icon' => 'bell'],
                ['label' => 'مركز الإنذارات', 'route' => 'subadmin.alerts', 'icon' => 'bell-ring'],
            ],
        ],
        [
            'title' => 'البيانات الأساسية',
            'items' => [
                ['tab' => 'geo'],
                ['tab' => 'fleet', 'label' => 'الأسطول'],
                ['tab' => 'seasons'],
                ['tab' => 'markets'],
            ],
        ],
        [
            'title' => 'الحوكمة والتدقيق',
            'items' => [
                ['tab' => 'data-quality'],
                ['tab' => 'data-catalog', 'label' => 'كتالوج البيانات'],
                ['tab' => 'business-glossary', 'label' => 'قاموس الأعمال'],
                ['tab' => 'fao', 'label' => 'معايير FAO'],
                ['label' => 'سجل العمليات', 'route' => 'subadmin.audit-log', 'icon' => 'history'],
            ],
        ],
        [
            'title' => 'التكاملات',
            'items' => [
                ['tab' => 'powerbi'],
                ['tab' => 'powerbi-blueprint'],
                ['tab' => 'powerbi-feed'],
                ['tab' => 'arcgis'],
                ['tab' => 'fabric'],
                ['tab' => 'hawat-ai'],
                ['tab' => 'sms'],
                ['tab' => 'firebase'],
            ],
        ],
        [
            'title' => 'الأدوات والإعدادات',
            'items' => [
                ['tab' => 'import'],
                ['tab' => 'stats'],
                ['tab' => 'translation'],
                ['label' => 'الإعدادات', 'route' => 'subadmin.settings', 'icon' => 'settings'],
            ],
        ],
    ],

    /*
     * قسم الخدمات والتراخيص — الوجه الخدمي للقطاع: طلبات الصيادين ومعالجتها،
     * ثم الرخص التي تنتهي إليها الطلبات والرقابة على شروطها، ثم الدعم الفني
     * لمستخدمي المنصة أنفسهم. إدارة الموظفين انتقلت إلى إدارة النظام.
     *
     * "رخص المواسم" و"الرقابة والامتثال" جاءتا من المنصة التشغيلية ولوحة
     * الحكومة — الرخصة والمخالفة طرفا الدورة نفسها التي يفتحها الطلب.
     */
    'nav_services' => [
        [
            'title' => 'الطلبات والمعالجة',
            'items' => [
                ['label' => 'خدمات الصيادين', 'route' => 'services.fisher-services', 'icon' => 'headset'],
                ['label' => 'مساحتي', 'route' => 'services.my-workspace', 'icon' => 'user'],
                ['label' => 'لوحة الموظف', 'route' => 'services.staff-dashboard', 'icon' => 'shield-check'],
            ],
        ],
        [
            'title' => 'الرخص والامتثال',
            'items' => [
                ['label' => 'رخص المواسم', 'route' => 'services.season-licenses', 'icon' => 'ticket'],
                ['label' => 'الرقابة والامتثال', 'route' => 'services.compliance', 'icon' => 'shield-alert'],
            ],
        ],
        [
            'title' => 'الدعم',
            'items' => [
                ['label' => 'الدعم الفني', 'route' => 'services.support', 'icon' => 'life-buoy'],
            ],
        ],
    ],

    /*
     * قوائم تطبيق حوات حسب دور التطبيق. المدير العام يرى هذه الأقسام ثم
     * مركز العمليات (nav أدناه) — انظر App\Support\Nav::panelSections().
     * بقية الأدوار تُملأ قوائمها مع بناء بوابة كل دور. قسم "الحساب" (الإشعارات
     * والملف الشخصي) واحد لكل الأدوار — شاشتا التطبيق المشتركتان.
     */
    'nav_panel' => [
        'super_admin' => [
            [
                'title' => 'المدير العام',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'حسابات التطبيق', 'route' => 'panel.users', 'icon' => 'user-cog'],
                    ['label' => 'طلبات التسجيل', 'route' => 'panel.registrations', 'icon' => 'user-check'],
                ],
            ],
            [
                'title' => 'العدّادون',
                'items' => [
                    ['label' => 'شركات التشغيل', 'route' => 'panel.companies', 'icon' => 'building'],
                    ['label' => 'العدّادون', 'route' => 'panel.counters', 'icon' => 'clipboard-check'],
                ],
            ],
            [
                'title' => 'الحساب',
                'items' => [
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'owner' => [
            [
                // بلا عنوان: روابط المالك اليومية ثابتة أعلى القائمة فوق الأقسام المطويّة.
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'الرحلات', 'route' => 'panel.owner.trips', 'icon' => 'route'],
                    ['label' => 'المبيعات', 'route' => 'panel.owner.sales', 'icon' => 'coins'],
                ],
            ],
            [
                'title' => 'الدلالون',
                'items' => [
                    ['label' => 'الدلالون', 'route' => 'panel.owner.dalals', 'icon' => 'handshake'],
                    ['label' => 'الإرسال للدلال', 'route' => 'panel.owner.consignments', 'icon' => 'send'],
                    ['label' => 'فواتير الدلالين', 'route' => 'panel.owner.dalal-invoices', 'icon' => 'file-text'],
                    ['label' => 'حسابات الدلالين', 'route' => 'panel.owner.dalal-accounts', 'icon' => 'calculator'],
                    ['label' => 'مخزون الدلالين', 'route' => 'panel.owner.dalal-stock', 'icon' => 'archive'],
                    ['label' => 'أداء الدلالين', 'route' => 'panel.owner.dalal-performance', 'icon' => 'bar-chart'],
                ],
            ],
            [
                'title' => 'المالية',
                'items' => [
                    ['label' => 'المصروفات', 'route' => 'panel.owner.expenses', 'icon' => 'receipt'],
                    ['label' => 'الأصول والإهلاك', 'route' => 'panel.owner.assets', 'icon' => 'archive'],
                    ['label' => 'مسيرات الرواتب', 'route' => 'panel.owner.payrolls', 'icon' => 'calculator'],
                    ['label' => 'سلف الطاقم', 'route' => 'panel.owner.advances', 'icon' => 'arrow-left-right'],
                    ['label' => 'أجور الطاقم', 'route' => 'panel.owner.crew-pay', 'icon' => 'user-cog'],
                    ['label' => 'إغلاق الشهر', 'route' => 'panel.owner.month-closings', 'icon' => 'lock'],
                    ['label' => 'التقارير', 'route' => 'panel.owner.reports', 'icon' => 'file-chart'],
                ],
            ],
            [
                'title' => 'الأسطول',
                'items' => [
                    ['label' => 'الصيانة', 'route' => 'panel.owner.maintenance', 'icon' => 'hammer'],
                    ['label' => 'الفحوصات', 'route' => 'panel.owner.inspections', 'icon' => 'clipboard-check'],
                    ['label' => 'الوثائق', 'route' => 'panel.owner.documents', 'icon' => 'file-check'],
                    ['label' => 'معدات الصيد', 'route' => 'panel.owner.equipment', 'icon' => 'anchor'],
                ],
            ],
            [
                // القوارب والكباتن والطاقم والجهات صارت تبويبات في الإعدادات ($ownerSettings أعلاه).
                'title' => 'الإعدادات والحساب',
                'items' => [
                    [
                        'label' => 'الإعدادات', 'route' => 'panel.owner.settings', 'icon' => 'settings',
                        'active' => ['panel.owner.settings', ...array_column($ownerSettings, 'route')],
                    ],
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'captain' => [
            [
                'title' => 'الكابتن',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'الرحلات', 'route' => 'panel.captain.trips', 'icon' => 'route'],
                    ['label' => 'سجل الصيد', 'route' => 'panel.captain.catch-log', 'icon' => 'fish'],
                ],
            ],
            [
                'title' => 'الحساب',
                'items' => [
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'counter' => [
            [
                'title' => 'العدّاد',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'طابور العد', 'route' => 'panel.counter.trips', 'icon' => 'clipboard-check'],
                ],
            ],
            [
                'title' => 'الحساب',
                'items' => [
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'company' => [
            [
                'title' => 'شركة التشغيل',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'جولات التوظيف', 'route' => 'panel.company.hiring', 'icon' => 'calendar'],
                    ['label' => 'طلبات التوظيف', 'route' => 'panel.company.applications', 'icon' => 'user-plus'],
                    ['label' => 'العدّادون', 'route' => 'panel.company.counters', 'icon' => 'users'],
                ],
            ],
            [
                'title' => 'الحساب',
                'items' => [
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'employee' => [
            [
                'title' => 'الموظف',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'dalal' => [
            [
                'title' => 'الدلال',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'المخزون', 'route' => 'panel.dalal.stock', 'icon' => 'archive'],
                    ['label' => 'المبيعات', 'route' => 'panel.dalal.sales', 'icon' => 'coins'],
                    ['label' => 'العملاء', 'route' => 'panel.dalal.customers', 'icon' => 'user-check'],
                ],
            ],
            [
                'title' => 'الملاك',
                'items' => [
                    ['label' => 'طلبات المالكين', 'route' => 'panel.dalal.requests', 'icon' => 'handshake'],
                    ['label' => 'الصيّادون المرتبطون', 'route' => 'panel.dalal.owners', 'icon' => 'users'],
                    ['label' => 'التقارير', 'route' => 'panel.dalal.reports', 'icon' => 'file-chart'],
                ],
            ],
            [
                'title' => 'الحساب',
                'items' => [
                    ['label' => 'الإعدادات', 'route' => 'panel.dalal.settings', 'icon' => 'settings'],
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
        'merchant' => [
            [
                'title' => 'التاجر',
                'items' => [
                    ['label' => 'الرئيسية', 'route' => 'panel.home', 'icon' => 'layout-dashboard'],
                    ['label' => 'الإشعارات', 'route' => 'panel.notifications', 'icon' => 'bell'],
                    ['label' => 'الملف الشخصي', 'route' => 'panel.profile', 'icon' => 'user'],
                ],
            ],
        ],
    ],

    // تبويبات إعدادات المالك — انظر $ownerSettings أعلى الملف.
    'owner_settings' => $ownerSettings,

    /*
     * مركز العمليات — قسم المدير العام في تطبيق حوات؛ أسماء مساراته بلا بادئة.
     *
     * البيانات الأساسية (المناطق والمحافظات والأنواع والمواسم والقوارب
     * والصيادون والموانئ ومواقع الصيد) خرجت من هذه القائمة: موضعها الوحيد
     * "البيانات الأساسية" في إدارة النظام. صفحاتها القديمة باقية على مساراتها
     * لمن يصلها برابط، لكن القائمة لا تعرضها مرتين.
     */
    'nav' => [
        [
            'title' => 'الميدان',
            'items' => [
                ['label' => 'رحلات الصيد', 'route' => 'trips', 'icon' => 'sailboat'],
                ['label' => 'الجدول الزمني للقوارب', 'route' => 'boat-timeline', 'icon' => 'clock'],
            ],
        ],
        [
            'title' => 'المراجعة والاستدامة',
            'items' => [
                ['label' => 'مراجعة الفروقات', 'route' => 'discrepancy-review', 'icon' => 'alert-triangle'],
                ['label' => 'الصيد العرضي', 'route' => 'bycatch', 'icon' => 'waves'],
            ],
        ],
    ],
];
