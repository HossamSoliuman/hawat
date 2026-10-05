<?php

return [

    'title' => 'إدارة النظام',
    'subtitle' => 'HAWAT Administration Center — المرجع الأساسي للبيانات الأساسية والإعدادات',
    'notice' => 'هذه اللوحة لإدارة البيانات الأساسية (Master Data) فقط. لا تُعدّل بيانات الرحلات أو المصيد المعتمد مباشرةً — تمرّ عبر مسارها التشغيلي مع سجل العمليات.',

    /*
     * القائمة الجانبية ليست هنا: البوابة نصف "إدارة النظام" ونصفه الآخر قسم
     * /subadmin، فقائمتهما واحدة في config/hawat.php → nav_subadmin.
     *
     * تبويبات سقطت لأن لصفحتها موضعًا آخر، وروابطها القديمة تُحوَّل إليه
     * (routes/web.php): "الرخص" إلى رخص المواسم في الخدمات والتراخيص، و"سجل
     * العمليات" إلى صفحته في /subadmin.
     */
    'tabs' => [

        'geo' => [
            'label' => 'البيانات الجغرافية',
            'label_en' => 'Geographic Data',
            'icon' => 'map',
            'type' => 'resource',
            'resources' => ['regions', 'governorates', 'ports', 'fishing-sites'],
        ],

        'fleet' => [
            'label' => 'الأسطول والثروة السمكية',
            'label_en' => 'Fleet & Fisheries',
            'icon' => 'ship',
            'type' => 'resource',
            'resources' => ['boats', 'fishers', 'species', 'gear-types', 'statistics-officers'],
        ],

        'seasons' => [
            'label' => 'مواسم الصيد',
            'label_en' => 'Fishing Seasons',
            'icon' => 'calendar',
            'type' => 'resource',
            'resources' => ['fishing-seasons'],
        ],

        'markets' => [
            'label' => 'الأسواق والمزادات',
            'label_en' => 'Markets & Auctions',
            'icon' => 'store',
            'type' => 'resource',
            'resources' => ['markets', 'market-auctions'],
        ],

        // صفحة المستخدمين الوحيدة: كانت في /subadmin نسخة للعرض فقط، والتحرير هنا.
        'permissions' => [
            'label' => 'المستخدمون والصلاحيات',
            'label_en' => 'Users & Permissions',
            'icon' => 'key',
            'type' => 'resource',
            'resources' => ['user-permissions'],
        ],

        'translation' => [
            'label' => 'الترجمة',
            'label_en' => 'Translation',
            'icon' => 'languages',
            'type' => 'resource',
            'resources' => ['ui-translations'],
        ],

        'import' => [
            'label' => 'الاستيراد الجماعي',
            'label_en' => 'Bulk Import',
            'icon' => 'upload',
            'type' => 'tool',
            'view' => 'admin.tools.bulk-import',
        ],

        'stats' => [
            'label' => 'إحصاءات الإنتاج',
            'label_en' => 'Production Statistics',
            'icon' => 'chart',
            'type' => 'tool',
            'view' => 'admin.tools.production-stats',
        ],

        'powerbi' => [
            'label' => 'تكامل Power BI',
            'label_en' => 'Power BI Integration',
            'icon' => 'chart',
            'type' => 'integration',
            'provider' => 'powerbi',
        ],

        'powerbi-blueprint' => [
            'label' => 'مخطط Power BI',
            'label_en' => 'Power BI Blueprint',
            'icon' => 'chart',
            'type' => 'tool',
            'view' => 'admin.tools.powerbi-blueprint',
        ],

        'powerbi-feed' => [
            'label' => 'بيانات Power BI',
            'label_en' => 'Power BI Data Feed',
            'icon' => 'chart',
            'type' => 'tool',
            'view' => 'admin.tools.powerbi-feed',
        ],

        'arcgis' => [
            'label' => 'تكامل ArcGIS',
            'label_en' => 'ArcGIS Integration',
            'icon' => 'map',
            'type' => 'integration',
            'provider' => 'arcgis',
        ],

        'fabric' => [
            'label' => 'تكامل Microsoft Fabric',
            'label_en' => 'Microsoft Fabric Integration',
            'icon' => 'chart',
            'type' => 'integration',
            'provider' => 'fabric',
        ],

        'hawat-ai' => [
            'label' => 'حوات AI',
            'label_en' => 'HAWAT AI',
            'icon' => 'chart',
            'type' => 'integration',
            'provider' => 'hawat_ai',
        ],

        'sms' => [
            'label' => 'الرسائل النصية',
            'label_en' => 'SMS Gateway',
            'icon' => 'plug',
            'type' => 'integration',
            'provider' => 'sms',
        ],

        'firebase' => [
            'label' => 'إشعارات Firebase',
            'label_en' => 'Firebase Cloud Messaging',
            'icon' => 'plug',
            'type' => 'integration',
            'provider' => 'firebase',
        ],

        'fao' => [
            'label' => 'معايير وتقارير FAO',
            'label_en' => 'FAO Standards & Reporting',
            'icon' => 'globe',
            'type' => 'resource',
            'resources' => ['fao-mappings'],
        ],

        'data-quality' => [
            'label' => 'حوكمة وجودة البيانات',
            'label_en' => 'Data Governance & Quality',
            'icon' => 'database',
            'type' => 'resource',
            'resources' => ['data-quality-issues'],
        ],

        'data-catalog' => [
            'label' => 'كتالوج ومسارات البيانات',
            'label_en' => 'Data Catalog & Lineage',
            'icon' => 'book',
            'type' => 'resource',
            'resources' => ['catalog-assets', 'lineage-edges'],
        ],

        'business-glossary' => [
            'label' => 'قاموس الأعمال والمؤشرات',
            'label_en' => 'Business Glossary & KPIs',
            'icon' => 'library',
            'type' => 'resource',
            'resources' => ['glossary-terms', 'kpi-registry'],
        ],

    ],

    'integrations' => [

        'powerbi' => [
            'title' => 'تكامل Power BI',
            'description' => 'ربط التقارير التفاعلية المستضافة على Power BI بلوحة حوات.',
            'fields' => [
                ['key' => 'workspace_id', 'label' => 'Workspace ID'],
                ['key' => 'report_id', 'label' => 'Report ID'],
                ['key' => 'dataset_id', 'label' => 'Dataset ID'],
                ['key' => 'tenant_id', 'label' => 'Tenant ID'],
                ['key' => 'client_id', 'label' => 'Client ID'],
                ['key' => 'embed_mode', 'label' => 'نمط التضمين', 'type' => 'select', 'options' => ['Embed for your organization', 'Embed for your customers']],
            ],
        ],

        'arcgis' => [
            'title' => 'تكامل ArcGIS',
            'description' => 'طبقات الخرائط البحرية: الموانئ، مواقع الصيد، مناطق الحظر، القوارب وكثافة المصيد.',
            'fields' => [
                ['key' => 'portal_url', 'label' => 'Portal URL'],
                ['key' => 'webmap_id', 'label' => 'Web Map Item ID'],
                ['key' => 'ports_layer_url', 'label' => 'طبقة الموانئ'],
                ['key' => 'fishing_sites_layer_url', 'label' => 'طبقة مواقع الصيد'],
                ['key' => 'restricted_areas_layer_url', 'label' => 'طبقة مناطق الحظر'],
                ['key' => 'boats_layer_url', 'label' => 'طبقة القوارب'],
                ['key' => 'catch_heatmap_layer_url', 'label' => 'طبقة كثافة المصيد'],
                ['key' => 'default_basemap', 'label' => 'الخريطة الأساسية', 'type' => 'select', 'options' => ['oceans', 'satellite', 'topo-vector', 'streets']],
                ['key' => 'default_zoom', 'label' => 'مستوى التكبير', 'type' => 'number'],
            ],
        ],

        'fabric' => [
            'title' => 'تكامل Microsoft Fabric',
            'description' => 'مزامنة بيانات المصايد إلى Lakehouse وWarehouse داخل Microsoft Fabric.',
            'fields' => [
                ['key' => 'workspace_name', 'label' => 'اسم مساحة العمل'],
                ['key' => 'lakehouse_name', 'label' => 'Lakehouse'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
                ['key' => 'sql_endpoint', 'label' => 'SQL Endpoint'],
                ['key' => 'refresh_mode', 'label' => 'وضع التحديث', 'type' => 'select', 'options' => ['لحظي', 'كل ساعة', 'يومي', 'عند الطلب']],
            ],
        ],

        'hawat_ai' => [
            'title' => 'HAWAT AI Analytics',
            'description' => 'محرك التحليل التنفيذي لبيانات المصايد — مقارنات، أسباب محتملة، وتوصيات ضمن صلاحيات المستخدم.',
            'safety_title' => 'الأمان الجغرافي مفعّل داخل المحرك',
            'safety_text' => 'قبل التحليل، تُصفّى البيانات حسب مستوى المستخدم: المملكة، المنطقة، المحافظة أو الميناء. الذكاء لا يستقبل بيانات خارج نطاقه.',
            'fields' => [
                ['key' => 'model', 'label' => 'النموذج', 'type' => 'select', 'options' => ['gpt_5_mini', 'gpt_5_4', 'gemini_3_flash', 'claude_sonnet_4_6']],
                ['key' => 'max_context_records', 'label' => 'أقصى عدد سجلات السياق', 'type' => 'number'],
                ['key' => 'daily_query_limit', 'label' => 'حد الاستعلامات اليومي', 'type' => 'number'],
                ['key' => 'enforce_jurisdiction', 'label' => 'فرض النطاق الجغرافي', 'type' => 'boolean'],
                ['key' => 'log_queries', 'label' => 'تسجيل الاستعلامات', 'type' => 'boolean'],
            ],
        ],

        /*
         * رموز التحقق لتطبيق الجوال. ما دام التكامل معطّلًا تُكتب الرموز في سجل
         * التطبيق (storage/logs) بدل إرسالها — انظر App\Services\Sms.
         */
        'sms' => [
            'title' => 'بوابة الرسائل النصية',
            'description' => 'إرسال رموز التحقق لاستعادة كلمة المرور من التطبيق، وما يليها من تنبيهات نصية.',
            'safety_title' => 'قبل التفعيل تُكتب الرموز في سجل التطبيق',
            'safety_text' => 'التكامل المعطّل لا يوقف استعادة كلمة المرور: الرمز يُسجَّل في storage/logs ليُقرأ منه أثناء التطوير.',
            'fields' => [
                ['key' => 'provider', 'label' => 'المزوّد', 'type' => 'select', 'options' => ['unifonic', 'msegat', 'twilio', 'custom']],
                ['key' => 'sender_name', 'label' => 'اسم المرسل'],
                ['key' => 'api_url', 'label' => 'API URL'],
                ['key' => 'api_key', 'label' => 'API Key'],
                ['key' => 'api_secret', 'label' => 'API Secret'],
            ],
        ],

        'firebase' => [
            'title' => 'إشعارات Firebase',
            'description' => 'بثّ إشعارات التطبيق (رحلة جديدة، استلام المصيد، بيع…) عبر Firebase Cloud Messaging.',
            'fields' => [
                ['key' => 'project_id', 'label' => 'Project ID'],
                ['key' => 'service_account_path', 'label' => 'مسار ملف حساب الخدمة (JSON)'],
                ['key' => 'android_channel_id', 'label' => 'Android Channel ID'],
            ],
        ],

    ],

];
