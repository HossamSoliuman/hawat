# سجل تغييرات واجهة التطبيق — `/api/v1`

المواصفة الكاملة: `docs/api/openapi.yaml` (تُعرض على `/api/docs`، وتُستورد في Postman من `/api/openapi.yaml`).
كل ردّ في الاختبارات يُطابَق مع المواصفة (Spectator)، فما هنا هو ما يعمل فعلًا.

## 1.7.0 — 2026-10-17 — الكابتن سجلّ عند المالك بلا حساب

الكابتن لا يدخل حوات ولا يفعل شيئًا بنفسه: صار سجلّ صياد عند المالك كفرد الطاقم (`Fisher` بـ`is_captain: true`)، يُسند إلى القارب والرحلات، والمالك يدير الرحلة كلها من مسارات `/owner/*`. **تغيير كاسر** لما يخصّ الكابتن.

### أُزيل
- دور `captain` من الأدوار (`Role.key`)، وبوابة الكابتن كلها: `/captain/dashboard`، `/captain/trips`، `/captain/trips/{trip}` و`start` و`cancel` و`catch`، `/captain/catch-log` و`/catch-log/summary`. حسابات الكباتن القائمة حُذفت مع رموزها.
- دعوة الكابتن: `POST /auth/activate` و`POST /owner/captains/{captain}/invitation`، و`password` و`invitation` و`active` و`last_login_at` من الكابتن.
- مخطط `Captain` و`Invitation` و`CaptainDashboard`، و`Fisher.user_id`، وتنبيه الجاهزية `captain_not_activated`.
- إشعارا الكابتن «رحلة جديدة بانتظارك» و«الرحلة مكتملة» — لا أحد يستقبلهما.

### تغيّر
- `GET|POST /owner/captains` و`GET|PUT /owner/captains/{captain}` ترجع `Fisher` (كشكل فرد الطاقم) بـ`is_captain: true`، و`{captain}` معرّف سجلّ الصياد. `CaptainInput` = حقول `CrewInput` بلا `fisher_role_id`: `name` و`national_id` مطلوبان، و`phone` اختياري للتواصل فقط (ليس حسابًا ولا يُشترط تفرّده)، والتعطيل بـ`status: غير نشط` بدل `active`. فلاتر القائمة: `search` (الاسم أو الجوال أو الهوية) و`boat_id` و`status`.
- `boats.captain_id` و`trips.captain_id` (و`BoatInput.captain_id` و`TripInput.captain_id`) صارت معرّف سجلّ الكابتن من `/owner/captains`. الكابتن على قارب واحد وللقارب كابتن واحد: إسناده من نموذج القارب أو من نموذج الكابتن يرفعه عن قاربه الآخر ويُنزل كابتن القارب السابق. `Boat.captain` و`Trip.captain` بالشكل نفسه (`id`, `name`, `phone`).
- صورة الكابتن (`POST|DELETE /owner/captains/{captain}/avatar`) في سجلّه كصورة فرد الطاقم.
- جاهزية القارب: `captain_inactive` = الكابتن غير نشط (يبقى على قاربه حتى يُفعَّل أو يُستبدل).
- `GET /owner/dashboard`: `kpis.trips_scheduled` و`scheduled_trips` (رحلات بانتظار الانطلاق — يبدؤها المالك).
- إشعارات بدء الرحلة وإلغائها وإرسال مخرجاتها لا تصل المالك حين يكون هو الفاعل؛ «اكتمل العد» يصله كما كان.

### جديد
- `DELETE /owner/captains/{captain}` — يُرفع عن قاربه ورحلاته، واسمه يبقى على الرحلات السابقة (`captain.name`)، ورقم هويته يتحرّر.
- `GET /owner/catch-log` و`GET /owner/catch-log/summary` — سجل الصيد على رحلات المالك (كان للكابتن)، بفلتر `captain_id` و`boat_id` (و`search` للسطور). `CatchLogEntry.trip.captain` اسم كابتن الرحلة.

## 1.6.0 — 2026-10-17 — طلبات حوات 1: إعداد القارب والكباتن والطاقم

### جديد
- **تواريخ الإقامة**: `iqama_issue_date` و`iqama_expiry_date` في `CaptainInput` و`CrewInput`، وفي `Fisher` (ومنه `Captain.fisher`). `YYYY-MM-DD`، اختياريان لأي نوع هوية (العامل المسجّل بجوازه قد يحمل إقامة). الانتهاء قبل البداية → 422 على `iqama_expiry_date` برسالة عربية.
- **بداية رخصة الصيد**: `license_date` في `CaptainInput` و`CrewInput` و`Fisher`، بالاسم نفسه كما في القارب. الانتهاء قبل البداية → 422 على `license_expiry`.
- **صور الكابتن والطاقم**: `POST|DELETE /owner/captains/{captain}/avatar` و`POST|DELETE /owner/crew/{crew}/avatar` (multipart، حقل `avatar`)، و`avatar_url` في `Captain` و`Fisher`. JPG/PNG/WebP يُفحص محتواها، حتى 2MB و4096 بكسل للضلع (512×512 و≤250KB من حوات 1 مقبولة). PDF أو حجم أكبر → 422 على `avatar`. صورة الكابتن هي صورة حسابه: يراها ويغيّرها من تطبيقه (`/auth/avatar`).
- **دعوة الكابتن دون كلمة مرور**: `password` اختيارية في `POST /owner/captains`. بدونها تصل جوال الكابتن رسالة نصية برمز تفعيل (6 أرقام، صالح 72 ساعة، 5 محاولات)، و`Captain.invitation` = `{status: invited|expired|activated, invited_at, expires_at, activated_at}`. الكابتن يفعّل حسابه بنفسه: `POST /auth/activate` (`phone`, `code`, `password`, `password_confirmation`) فيدخل مباشرة بردّ كردّ الدخول. إعادة الإرسال: `POST /owner/captains/{captain}/invitation` (3 في الدقيقة). لا كلمة مرور ثابتة أو مولّدة يعرفها المالك. جوال مسجّل لحساب آخر (أي دور) → 422 على `phone`. تغيير جوال كابتن مدعوّ يرسل الدعوة إلى الجديد. من استعاد كلمة مروره برمز `forgot-password` قبل التفعيل يُعدّ مفعّلًا.
- **منع التكرار**: ترويسة `Idempotency-Key` على كل طلب POST يحمل رمزًا (ومنها `/owner/boats` و`/owner/crew` و`/owner/captains`). تكرار الطلب بالمفتاح نفسه يعيد الردّ الأول (`Idempotency-Replayed: true`) ولا يُنشئ سجلًّا. المفتاح لكل حساب ويُحفظ 24 ساعة؛ لطلب مختلف → 422، والأول ما زال يُنفَّذ → 409؛ الردّ غير الناجح لا يُحفظ.
- **التعديلات المتزامنة**: `version` و`updated_at` في `Boat` و`Fisher` و`Captain`، وترويسة `ETag` في ردود العرض والتعديل والإنشاء. `If-Match: "3"` (أو حقل `version`) في PUT؛ نسخة قديمة → 409 (`current_version`) ولا تُكتب القيم. بلا نسخة يُقبل التعديل كما كان. نسخة الكابتن تشمل حسابه وسجلّ صياده.
- **قراءة فرد طاقم**: `GET /owner/crew/{crew}` (404 لمالك آخر).
- **عمال الدلال أفرادًا**: `GET|POST /dalal/workers`، `GET|PUT /dalal/workers/{worker}`، `POST|DELETE /dalal/workers/{worker}/avatar`. الحقول: `name`, `phone`, `nationality`, `id_type_id`, `id_number` (لا يتكرر عند الدلال نفسه), `dalal_worker_type_id`, `status`, `notes`. التعطيل بـ`status = غير نشط`، لا حذف. بحث وصفحات وفلتر حالة ونوع. عامل دلال آخر 404.
- **اكتمال إعداد القارب**: `Boat.setup = {ready, missing[], warnings[]}`. يكتمل حين يكون القارب نشطًا (لا معطّلًا ولا في الصيانة)، وله كابتن حسابه مفعّل غير معطّل، و`crew_count ≥ 1`. التنبيهات لا تمنعه: كابتن لم يفعّل دعوته (المالك يبدأ الرحلة عنه)، رخصة القارب منتهية، طاقم أكثر من السعة. الرخصة السارية ليست شرطًا في الخادم. استرشادي: إنشاء الرحلة لا يُرفض بسببه.
- **أنواع العملاء**: `customer_types` تضم «سوق سمك» و«محل سمك».

### تغيّر
- `crew_count` في القارب صار محسوبًا: أفراد الطاقم النشطون المسجّلون عليه (بلا الكابتن)، ويُتجاهل في الإدخال (أُزيل من `BoatInput`).
- `capacity` الحمولة **بالطن** وتقبل الكسور (`number`). `crew_capacity` أقصى عدد للطاقم.
- `license_date` في القارب = تاريخ بداية الرخصة (إصدارها)، و`license_expiry` لا يسبقه.
- `status` في تعديل القارب اختيارية (غيابها = كما هي). «في البحر» تضبطه الرحلات وحدها: لا يُختار يدويًا، والقارب في رحلة لا تتغيّر حالته حتى تنتهي؛ إرسال الحالة الحالية كما هي مقبول دائمًا، فتعديل اسم قارب في البحر لا يغيّر حالته.
- `DalalSettings.workers` صار أعدادًا محسوبة من العمال النشطين متى سُجّل عامل واحد، و`workers_source` = `records` أو `counts` (السطور القديمة). `PUT /dalal/settings/workers` مهجور ويُرفض بـ422 بعد تسجيل أول عامل.
- رسائل التحقق كلها بالعربية (كانت بعض القواعد تعيد مفاتيح مثل `validation.required`).

### التفرد (توضيح)
- `boat_number` و`national_id` (الصياد) وجوال الحساب لا تتكرر **على مستوى النظام**، وتشمل السجلات غير النشطة. التكرار 422 على الحقل نفسه. فرد الطاقم المحذوف يُحرّر رقم هويته (الحذف نهائي).

## 1.5.0 — 2026-10-02 — توظيف العدّادين عبر شركات التشغيل

شركات التشغيل تفتح جولات توظيف في موانئها وتعتمد العدّادين من بوابتها على الويب (`/admin/company/*`)؛ ما يصل التطبيق هو التقديم نفسه، بلا حساب:

### جديد
- `GET /hiring-rounds` — الجولات التي تستقبل الطلبات الآن (`HiringRound`: الشركة، الميناء، المحافظة، المنطقة، `seats_left`، `closes_at`) لبناء اختيار المنطقة ← المحافظة ← الميناء.
- `POST /counter-applications` (`CounterApplicationInput`) → `201` بالطلب و`meta.expires_in`، ويُرسل رمز تحقق إلى الجوال. احفظ `data.token`.
- `POST /counter-applications/{token}/verify` (`code`) — يوثّق الجوال ويوصل الطلب إلى الشركة. `POST …/resend` يعيد الرمز (3 في الدقيقة).
- `GET /counter-applications/{token}` — الحالة (`pending` / `approved` / `rejected` / `withdrawn`، و`phone_verified`، و`rejection_reason`). `POST …/withdraw` يسحب طلبًا قيد المراجعة.
- عند الاعتماد يُنشأ حساب `counter` بكلمة المرور التي اختارها المتقدّم، فيدخل بـ `POST /auth/login` ويعمل في طابور ميناء الجولة كأي عدّاد. القرار يصله رسالة نصية.
- **إشعار جديد** للعدّاد: "نُقلت إلى ميناء آخر" (بلا `trip_id`) حين تنقله شركته أو الوزارة — طابوره بعده طابور الميناء الجديد.
- الحساب الموقوف (من الشركة أو الوزارة) يردّ `403` كأي حساب معطّل وتُلغى رموزه.

## 1.4.2 — 2026-09-28 — O5: تسوية المالك مع الدلال

التسوية نفسها في الويب فقط (`/admin/owner/dalal-invoices|dalal-accounts|dalal-stock|dalal-performance`، والردّ على الرفض في `/admin/dalal/sales/{sale}`)؛ لا مسار ولا مخطط تغيّر. ما يصل التطبيق:

### جديد
- **إشعارات جديدة** (`GET /notifications`): للدلال "قبل المالك فاتورتك" و"رفض المالك فاتورتك" (`payload.target = "sale"`, `payload.sale_id`, `payload.review_id`) و"سجّل المالك استلام دفعة" (`payload.target = "owners"`, `payload.payout_id`)؛ وللمالك "رد الدلال على فاتورة مرفوضة" (`payload.target = "dalal_invoice"`, `payload.sale_id`). قيم `target` غير المعروفة للتطبيق تفتح شاشة الإشعارات كما قبل.

### تغيّر
- `GET /dalal/owners` → `paid` و`due` يشملان ما سجّله المالك من استلام (دفعة بـ`user_id` = المالك)، فالمستحق عند الطرفين رقم واحد.

## 1.4.1 — 2026-09-25 — O1: مصروفات المالك

المصروفات نفسها في الويب فقط (`/admin/owner/expenses`)؛ ما يمسّ الواجهة:

### تغيّر
- `Maintenance` يحمل `actual_cost` (التكلفة الفعلية) و`expense` (`id`, `expense_number`, `total`, `paid_amount` أو `null`)، و`MaintenanceInput` يقبل `actual_cost`.
- حفظ الصيانة `مكتملة` بتكلفة يُنشئ مصروفها في فئة "صيانة القوارب" (غير مدفوع) أو يحدّثه؛ إرجاعها عن الاكتمال أو حذفها يحذف المصروف ما لم يُدفع منه شيء.

## 1.4.0 — 2026-09-24 — المرحلة 4: بوابة الدلال

### جديد
- **الدلال** (`/dalal/*`، دور `dalal`، سجلات دلال آخر `404`): `GET /dalal/dashboard` (`?period=today|week|month|year|custom&from&to` — المؤشرات، الإيرادات والأرباح ستة أشهر، أكثر الأصناف مبيعًا، الطلبات المعلّقة، آخر المبيعات)، `GET /dalal/stock` (البطاقات، المخزون مجمّعًا حسب المالك بدفعاته، المتاح لكل صنف).
- **البيع من المخزون**: `POST /dalal/sales` (`DalalSaleInput` — `trip_id` اختياري لكل سطر؛ بدونه يُصرف من الأقدم استلامًا أوّلًا وقد ينقسم على أكثر من رحلة ومالك)، `GET /dalal/sales` (`status`, `customer_id`, `from`, `to`, `min`, `max`, `search`)، `GET /dalal/sales/{sale}`، `POST /dalal/sales/{sale}/payments` (تحصيل المتبقي).
- **العملاء**: `/dalal/customers` (CRUD، `region_id`, `governorate_id`, `search`).
- **طلبات المالكين**: `GET /dalal/partnerships` (`?status=pending|accepted|rejected`)، `POST /dalal/partnerships/{id}/accept|reject` (`response_note`). ومن جهة المالك: `POST /owner/dalals/{dalal}/partnership` (`commission_pct`, `wage_pct`, `message`).
- **الصيّادون المرتبطون**: `GET /dalal/owners` (حساب كل مالك: المستلم، في المخزون، المباع، العمولة والأجور، صافيه، المدفوع، المستحق)، `POST /dalal/owners/{owner}/payouts` (لا تتجاوز المستحق).
- **التقارير**: `GET /dalal/reports/{sales|stock|payouts|financial}` ببنية واحدة (`Report`: أعمدة بصيغها، سطور، مجاميع).
- **الإعدادات**: `GET|PUT /dalal/settings` (الملف التجاري والدكة والشركة)، `POST|DELETE /dalal/settings/logo`، `PUT /dalal/settings/workers` (عمالة الدكة كاملةً).
- **الفاتورة المطبوعة**: `Sale.invoice_url` — رابط موقّع لصفحة A4 تُطبع أو تُحفظ PDF بلا رمز (لفواتير المالك والدلال).
- **إشعارات جديدة**: مصيد جديد في مخزونك، طلب تعامل من مالك، تم قبول/رفض طلب التعامل، بيع من مصيدك، دفعة من الدلال.

### تغيّر
- `Sale` يحمل `commission_pct`, `commission_amount`, `wage_pct`, `wage_amount`, `invoice_url`؛ و`SaleItem` يحمل `species.name_sci` و`trip` و`owner` و`commission_amount`, `wage_amount`, `owner_net`.
- `GET /owner/dalals`: كل دلال يحمل `partnership` (`PartnershipSummary` أو `null`).
- `GET /lookups` يحمل `dalal_worker_types`.
- `User.port` يشمل الدلال (من ملفه التجاري).

## 1.3.0 — 2026-09-22 — المرحلة 3: بوابة العدّاد

### جديد
- **العدّاد** (`/counter/*`، دور `counter`، رحلة ميناء آخر `404`): `GET /counter/dashboard` (ميناء العمل، الأعداد، ما بحاجة لموافقتك، ما تحت العد، آخر ما عُدّ)، `GET /counter/trips` (`?view=awaiting|counting|counted`، `status`، `search`)، `GET /counter/trips/{trip}`، `POST /counter/trips/{trip}/receive` (استلام → تحت الإحصاء)، `POST /counter/trips/{trip}/count` (تأكيد الكميات → بانتظار الاعتماد، ويُفتح المصيد للبيع عند المالك)، `GET /counter/trips/{trip}/report` (التقرير المفصّل: أقسام معنونة بسطور + تفاصيل المصيد).
- **العد بالصنف** (`CountInput`): سطر لكل صنف بوزنه المعدود و`verified` (مربّع "فحص الكمية") وملاحظة العدّاد، و`notes` للرحلة كلها. الوزن `0` مقبول لصنف أُعلن ولم يُوجد، والصنف الذي لم يعلنه الكابتن يُضاف سطرًا جديدًا منسوبًا إلى العدّاد (`captain_kg = null`).
- **إشعار جديد**: "رحلة بانتظار العد" يصل عدّادي ميناء العودة حين يرسل الكابتن مخرجاته.

### تغيّر
- `User.port` صار يشمل العدّاد أيضًا (ميناؤه من سجلّ موظف الإحصاء `statistics_officers`)، وكان للكابتن وحده من سجلّ الصياد.

## 1.2.0 — 2026-09-21 — المرحلة 2: بوابة الكابتن والإشعارات

### جديد
- **الكابتن** (`/captain/*`، دور `captain`، الرحلة المسندة لغيره `404`): `GET /captain/dashboard` (الأعداد، الرحلات التي بانتظارك، النشطة، آخر الرحلات)، `GET /captain/trips` (`?view=pending|active|completed|cancelled`، `status`، `search`)، `GET /captain/trips/{trip}`، `POST /captain/trips/{trip}/start|cancel|catch` بحساب الكابتن نفسه، `GET /captain/catch-log` + `GET /captain/catch-log/summary` (سجل الصيد وملخصه بالصنف).
- **الإشعارات** (لكل الأدوار): `GET /notifications` (`?unread=1`؛ `meta.unread_count`)، `GET /notifications/unread-count`، `POST /notifications/{notification}/read`، `POST /notifications/read-all`. تُكتب عند: إسناد رحلة (الكابتن)، بدئها وإلغائها وإرسال مخرجاتها (الطرف الآخر)، واكتمال العد (الكابتن "الرحلة مكتملة" + المالك "اكتمل العد"). تُدفع إلى الجوال عبر Firebase حين يُفعَّل التكامل (`fcm_token` من `POST /auth/fcm-token`)؛ إلى ذلك الحين تُكتب في السجل.
- **الصورة الشخصية**: `POST /auth/avatar` (multipart، حتى 2MB) و`DELETE /auth/avatar`؛ `avatar_url` في `User`.

### تغيّر
- `User` يحمل `port` (ميناء الكابتن من سجلّ الصياد: `id`, `name`, `governorate`) — `null` لغير الكباتن.
- `Trip.captain` و`Trip.counter` يعودان الآن بالاسم النصي القديم (`captain_name` / `statistics_officer`) حين لا حساب مرتبط — كانا `null`.

## 1.1.0 — 2026-09-20 — المرحلة 1: بوابة المالك

### جديد
- `GET /lookups` — كل القوائم المرجعية في ردّ واحد (الأصناف، الموانئ، أدوات الصيد، تصنيفات/أنواع القوارب، أنواع الصيانة، أنواع التصريح، أدوار الطاقم، أنواع الهوية، المسميات الوظيفية، طرق الدفع، حالات الدفع، أنواع العملاء).
- `GET /owner/dashboard` — المؤشرات، إيرادات ستة أشهر، الأسماك المتوفرة، الرحلات النشطة، آخر المبيعات.
- `GET /owner/stock`، `GET /owner/stock/movements` — المخزون المتاح وحركات الصنف.
- `GET /owner/dalals` — الدلالون المفعّلون لقائمة الإرسال.
- **الأسطول**: `/owner/boats` (CRUD — الحذف يُرفض لقارب له رحلات)، `/owner/maintenance` (بلا show)، `/owner/captains` (index/store/show/update — الإنشاء يصنع حساب دخول + سجلّ صياد؛ التعطيل بـ `active=false`)، `/owner/crew` (بلا show).
- **الجهات**: `/owner/employees`، `/owner/customers`، `/owner/vendors` (CRUD).
- **الرحلات**: `/owner/trips` (index/store/show/update) + `POST /owner/trips/{trip}/start|cancel|catch` — نيابةً عن الكابتن. `?active=1` للرحلات النشطة، `?sale_status=قيد البيع` لما يحتاج تسجيل البيع. التفاصيل تحمل `catch_records` و`available_stock`.
- **البيع**: `POST /owner/sales` (لا يتجاوز المتاح من كل صنف؛ يخصم من دفتر المخزون؛ الرحلة تصير "مباعة" حين ينفد مصيدها)، `GET /owner/sales`, `GET /owner/sales/{sale}`.
- **الإرسال للدلال**: `POST /owner/consignments` + index/show.

### مفاهيم
- كل مسار تحت `/owner/*` يتطلب دور `owner` (وإلا `403`)، وكل سجل مقيّد بمالكه (`404` لغيره).
- حالات الرحلة (`status`) بمفردات الوزارة، ومعها `app_status` (التسمية في التطبيق) و`progress_step` (0–4) و`sale_status` (لم يبدأ / قيد البيع / مباعة) و`can_sell`.
- القوائم مصفّحة: `{ data, links, meta }` مع `?page=` و`?per_page=` (حتى 100).

### تغيّر
- `openapi` صارت `3.0.3` (كانت 3.1.0) لتوافق مولّدات العملاء وPostman؛ لا تغيير في مسارات `auth/*`.

## 1.0.0 — 2026-09-19 — المرحلة 0

- `POST /auth/login` (رمز لكل جهاز + `fcm_token`)، `POST /auth/logout`، `GET|PUT /auth/me`، `POST /auth/change-password`، `POST /auth/fcm-token`.
- استعادة كلمة المرور بالجوال: `POST /auth/forgot-password` → `POST /auth/verify-otp` → `POST /auth/reset-password`.
