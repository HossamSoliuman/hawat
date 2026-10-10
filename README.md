# حوات — منصّة المصايد البحرية (Laravel)

تطبيق Laravel واحد فوق قاعدة بيانات واحدة، يخدم **منتجين على مضيفين** (محليًا
`hawat.test` و`gov.hawat.test`). المضيفان يُشتقّان من `APP_URL` في `config/hawat.php`
(`domain` و`gov_domain`)، ويُضبطان صراحةً بـ `APP_DOMAIN` و`GOV_DOMAIN` عند الحاجة:

| المضيف | ما عليه |
|---|---|
| `hawat.sa` | صفحة الهبوط `/`، وتطبيق حوات تحت `/admin` (دخوله `/admin/login`)، والتقديم للعدّادين `/counter/apply`، والفاتورة الموقّعة، وواجهة التطبيق `/api/v1` وتوثيقها `/api/docs` |
| `gov.hawat.sa` | بوابات الوزارة الثلاث، وصناديقها على `/` |

وبوابات الوزارة يحدّدها `App\Support\Nav` من اسم المسار — والتبويب الواحد يظهر في
قائمة بوابته وحدها:

| البادئة | البوابة | القائمة |
|---|---|---|
| `/stats` | الإحصاء | `hawat.nav_stats` |
| `/screens` | شاشة العرض (تبويب في الإحصاء) | `hawat.nav_screens` |
| `/services` | الخدمات والتراخيص | `hawat.nav_services` |
| `/subadmin` | إدارة النظام مع بوابة المعلومات (دخولها `/login`) | `hawat.nav_subadmin` |

نقل لوحة بين البوابات يستلزم `Route::permanentRedirect` من موضعها القديم — يحرس ذلك
`tests/Feature/PortalSplitTest.php`. وما كان من بوابات الوزارة على `hawat.sa`
(`/sections`، `/stats`، `/gov`، `/services`، `/subadmin`، `/login`، `/info`) يُحوَّل
دائمًا إلى `gov.hawat.sa` بالمسار نفسه، ومضيف `info.hawat.sa` القديم إلى موضع صفحته
في `/subadmin`، وأي مضيف آخر يصل التطبيق (كـ `www.`) إلى `hawat.sa`.

---

## 1) المتطلبات

- PHP ≥ 8.2 مع الإضافات: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Composer ≥ 2
- MySQL 8 / MariaDB 10.6

لا حاجة إلى Node.js أو خطوة build — نظام التصميم مضمّن في Blade.

---

## 2) التشغيل

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

ثم اضبط `APP_URL=http://hawat.test` في `.env`، واربط مضيف الوزارة في Herd من مجلد
المشروع: `herd link gov.hawat` (يفتح `http://gov.hawat.test`).

### الدخول إلى إدارة النظام

`/subadmin` كلّه خلف تسجيل دخول — لوحة الوزارة عرضٌ وتحليل، أمّا هذه فتحرّر البيانات
الأساسية وتكتب في سجل العمليات، فلكلّ سطرٍ فيه صاحبٌ معروف. صفحة الدخول على
`gov.hawat.sa/login`، والخروج `POST /logout` من ذيل القائمة الجانبية.

`UserSeeder` يبذر خمسة حسابات ببُرُدٍ هي نفسها مفاتيح جدول الصلاحيات
(`user_permissions`) الذي يبذره `SystemSeeder` — فالدور يُقرأ من هناك لا من جدول
المستخدمين:

| البريد | الدور |
|---|---|
| `admin@hawat.sa` | مدير النظام |
| `dg@hawat.sa` | الإدارة العليا |
| `fisheries@hawat.sa` | إدارة المصايد |
| `east@hawat.sa` | مدير منطقة |
| `qatif@hawat.sa` | مدير ميناء |

كلمة المرور واحدة لها كلّها، تُقرأ من `SEED_PASSWORD` وقيمتها الافتراضية
`hawat@2026`. **بيانات تهيئة لا بيانات إنتاج** — غيّرها على الخادم قبل التسليم.
والبذر لا يدهس كلمةً غُيّرت بعده: الحساب يُكتب عند إنشائه وحده.

---

## 3) مسارات إدارة النظام

`/subadmin` رئيسة القسم: نظرة عامة ببطاقة لكل صفحة وعدد سجلاتها. تبويبات البوابة
على `/subadmin/{tab}` بجانب لوحات القسم الثابتة (`/subadmin/audit-log`…)، والقيد
`whereIn('tab', …)` يمنعها من ابتلاع تلك اللوحات. الروابط القديمة
`hawat.sa/info/admin/{tab}` ومضيف `info.hawat.sa` كلّه يحوّلها التطبيق نفسه دائمًا إلى
`gov.hawat.sa/subadmin/{tab}`.

---

## 4) معمارية بوابة المعلومات

مبنية على نمط **Config-Driven Resources**: تعريف واحد لكل مورد في ملف إعدادات،
وطبقة عرض عامة تتولّى الجدول والنموذج والتحقق. إضافة جدول جديد لا تحتاج
Controller ولا View جديدة.

```
config/info.php                تعريف التبويبات العشرين + إعدادات التكاملات
config/info_resources.php      تعريف كل مورد: النموذج، الأعمدة، الحقول، الشارات
app/Support/AdminRegistry.php  حلّ التبويبات/الموارد، بناء الحقول، توليد قواعد التحقق
app/Support/ToolData.php       بيانات تبويبات الأدوات (الإحصاءات، الاستيراد، Power BI)
app/Http/Controllers/
  AdminController.php               عرض التبويب وتحديد نوع اللوحة (مورد/تكامل/أداة)
  AdminResourceController.php       إنشاء/تعديل/حذف مع تسجيل تلقائي في سجل العمليات
  IntegrationSettingController.php  حفظ إعدادات التكاملات
```

### الحقول المرتبطة بمفاتيح أجنبية

المخطط علائقي (`region_id` / `governorate_id` / `port_id` / `boat_id` …)، لذا
تدعم `options_from` شكلين:

```php
// قائمة قيم نصية — القيمة هي التسمية
['key' => 'top_port', 'type' => 'select',
 'options_from' => ['model' => Port::class, 'column' => 'name']],

// مفتاح أجنبي — تُخزَّن id وتُعرض التسمية
['key' => 'port_id', 'type' => 'select',
 'options_from' => ['model' => Port::class, 'value' => 'id', 'label' => 'name']],
```

وتُعرض في الجدول بمسار نقطي مع تحميل مسبق تفاديًا لاستعلامات N+1:

```php
'with' => ['governorate.region'],
'columns' => ['name' => 'الاسم', 'governorate.region.name' => 'المنطقة'],
```

### إضافة مورد جديد

1. أضف الجدول في Migration والموديل في `app/Models` (بالوراثة من `BaseModel`).
2. أضف تعريفه في `config/info_resources.php` (columns / fields / badges / with).
3. أضف مفتاحه في مصفوفة `resources` للتبويب المناسب في `config/info.php`.

يظهر تلقائيًا بجدول قابل للترقيم ونموذج إضافة/تعديل مع تحقق كامل.

---

## 5) قاعدة البيانات

| Migration | الجداول |
|---|---|
| `create_geographic_tables` | regions, governorates, ports, fishing_sites |
| `create_fleet_tables` | species, gear_types, boats, fishers, statistics_officers |
| `create_seasons_tables` | fishing_seasons, season_licenses |
| `create_operations_tables` | trips, catch_records, bycatch_records, alerts, violations |
| `create_market_tables` | markets, market_auctions |
| `create_governance_tables` | data_catalog_assets, data_lineage_edges, business_glossary_terms, kpi_registries, data_quality_issues, fao_standard_mappings |
| `create_system_tables` | user_permissions, audit_logs, ui_translations, integration_settings |
| `extend_tables_for_info_portal` | الحقول المرجعية الإضافية التي تحرّرها البوابة ولا تحتاجها اللوحة |
| `create_sub_administration_tables` | org_positions, org_staff, admin_tasks, staff_notifications, notification_settings |
| `create_services_licensing_tables` | fisher_service_types, fisher_service_staff, fisher_service_staff_type, fisher_service_requests, support_tickets |

جميع الـ Seeders تستخدم `updateOrCreate` — تشغيلها أكثر من مرة آمن ولا يُكرّر البيانات.

---

## 6) نظام التصميم

- الخط: **Tajawal** (400/500/700/800) من Google Fonts، والاتجاه RTL على مستوى الصفحة.
- توكنز البوابة في `resources/views/admin/partials/styles.blade.php`، وتوكنز اللوحة
  في `resources/views/partials/styles.blade.php`.
- الجداول: ترويسة رمادية خفيفة، تباين صفوف بالتناوب، شارات حالة بنقطة لونية
  (`badge-ok` / `badge-warn` / `badge-danger`)، أزرار تعديل/حذف أيقونية.
- الأيقونات SVG داخلية — بدون أي اعتماد خارجي.

---

## 7) الأمان

- عمليات الكتابة تمر عبر `AdminResourceController` مع تحقق مُولّد من تعريف الحقول
  (`required` / `numeric` / `date` / `in:` للقوائم، بما فيها قيم المفاتيح الأجنبية).
- تبويب سجل العمليات معرّف `readonly` — أي محاولة كتابة عليه تُرفض بـ 403.
- كل إنشاء/تعديل/حذف يُسجّل تلقائيًا في `audit_logs` مع المستخدم والدور و IP.
- الحذف محمي بتأكيد، وكل النماذج تحمل `@csrf`.

> بوابة المعلومات **غير محمية بمصادقة بعد**. لتقييد الوصول أضف
> `->middleware(['auth'])` على مجموعة مسارات البوابة في `routes/web.php`.

---

## 8) الاختبارات

```bash
php artisan test
```

تعمل على SQLite في الذاكرة، وتضبط `APP_URL` عبر `phpunit.xml` حتى لا تعتمد على `.env` المحلي.

---

## 9) النشر

الدفع إلى `main` يشغّل `.github/workflows/deploy.yml` الذي ينفّذ على الخادم:

```bash
cd /www/wwwroot/hawat
git pull --ff-only origin main
php artisan migrate --force
php artisan optimize
```

الموقع `hawat.sa` جذره `/www/wwwroot/hawat/public`، ويصل `gov.hawat.sa` (ومعه
`info.hawat.sa` القديم) الجذرَ نفسه بإعادة كتابة Laravel نفسها
(`try_files $uri $uri/ /index.php?$query_string;`) وبشهادة SSL تشملهما: التطبيق يختار
ما يقدّمه من اسم المضيف، والتحويلات كلها فيه لا في nginx.
