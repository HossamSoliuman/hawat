<style>
/*
 * نظام التصميم — لوحة حادّة مسطّحة على طراز HUD.
 *
 * ثلاث قواعد تحكم الملف كلّه: لا زوايا دائرية (كل شيء على زاوية قائمة عدا
 * بطاقات المؤشرات، انظر `--stat-radius`)، ولا تدرّجات ولا ظلال، ولا نقشَ على
 * أي سطح — عدا الصفحة نفسها: شبكة ورق رسم واحدة تجري خلف كل شيء، انظر `body`.
 * والسطوح كلّها شفّافة: ما يفصل البطاقة عن الصفحة خطٌّ شعري خافت
 * وأربعة أقواس زوايا — لا لونُ خلفيةٍ مختلف.
 */
:root {
    color-scheme: light;
    --background: 210 25% 98.5%;
    --foreground: 213 34% 12%;
    --card: 0 0% 100%;
    --primary: 199 89% 28%;
    --primary-foreground: 0 0% 100%;
    --muted: 210 24% 93%;
    --muted-foreground: 213 14% 42%;
    --accent: 199 85% 90%;
    --border: 213 18% 82%;
    --ring: 199 89% 28%;

    /*
     * سطح البطاقة شفّاف: خلفية الصفحة هي ما يُرى داخلها. `--card` باقٍ لأن
     * الأدراج والأشرطة العائمة وحدها تحتاج سطحًا مصمتًا يحجب ما تحته.
     */
    --surface: transparent;
    /* الخطّ الفاصل خافت: الأقواس هي ما يرسم حدّ البطاقة، والخطّ يهمس تحتها. */
    --hair: hsl(var(--border) / .55);

    /* الزاوية القائمة سمة اللوحة: المتغيّر باقٍ ليقرأه ما بقي من أنماط، وقيمته صفر. */
    --radius: 0px;
    /* استثناء واحد: بطاقات المؤشرات العليا تُستدار قليلًا لا كليًّا. */
    --stat-radius: 10px;

    /* أقواس الزوايا: طولها وسماكتها ولونها. تُرسم بثمانية تدرّجات في ::after. */
    --brk-color: hsl(var(--primary) / .45);
    --brk-len: 11px;
    --brk-th: 1.5px;

    /* فاصل واحد بين كل عنصرين في كل شبكة — لا فراغات متفاوتة. */
    --gap: .875rem;

    /*
     * قياسا العرض، مأخوذان من hispa حرفيًّا لأن العميل أقرّ قياسها: القائمة
     * 12.5rem، والهامش الخارجي مثلها فيبقى العمودان متناظرين حول المحتوى.
     * الهامش يُقاس بـ clamp لا بقيمة ثابتة: على 1920px يبلغ 12.5rem تمامًا كما
     * في hispa، ثم ينكمش إلى الصفر عند 1024px حتى لا يُخنق المحتوى على الشاشة
     * الأصغر — وهي العلّة التي في hispa نفسها.
     */
    --sidebar-w: 12.5rem;
    --content-gutter: clamp(0rem, (100vw - 64rem) / 4, 12.5rem);

    /* الشريط العلوي: ارتفاعه ولونه من hispa حرفيًّا — أزرقها #3675c2 على بياض. */
    --topbar-h: 3.25rem;
    --topbar-bg: #3675c2;
    --topbar-fg: #ffffff;

    /*
     * ألوان الحالة، محجوزة لها وحدها: لا يُلوَّن بها تمييز سلسلة عن أخرى.
     * لكلّ وضعٍ درجاته حتى يبقى التباين على سطحه فوق 3:1.
     */
    --st-good: #0f7a5a;
    --st-warn: #b45309;
    --st-critical: #d61f47;
    --st-neutral: #1d6fb8;
    --st-none: #94a3b8;
}
/*
 * الوضع الداكن ليس قلبًا للرمادي: هو زرقةُ بحرٍ عميق — الأساس #0a1420 وما
 * فوقه من درجاتٍ كلّها على مسار 213°، والزرقة الفاتحة #72a7e5 لونَ التمييز.
 * انظر `html.dark body` لصورة الغلاف التي تجري خلف ذلك كلّه.
 */
html.dark {
    /* حتى يرسم المتصفّح سهمَ القوائم ومنتقيَ التاريخ داكنَين لا فاتحَين. */
    color-scheme: dark;
    --background: 213 52% 8%;
    --foreground: 214 64% 96%;
    --card: 213 36% 11%;
    --primary: 212 69% 67%;
    --primary-foreground: 213 52% 8%;
    --muted: 213 36% 17%;
    --muted-foreground: 213 20% 66%;
    --accent: 213 36% 21%;
    --border: 213 28% 26%;
    --ring: 212 69% 67%;
    /* في الوضع الداكن الأقواس بيضاء لا زرقاء: الأزرق يذوب في الخلفية. */
    --brk-color: hsl(210 30% 94% / .42);
    /*
     * والخطّ الفاصل أبيضُ مثلها لا أزرق: `--border` يقارب خلفية الصفحة في
     * الإضاءة بعد أن خفّت زرقتها فيختفي حدّ البطاقة ولا يبقى منها إلا الأقواس.
     * فبياضٌ بشفافية 16٪ — أقلّ من ثلث ما للأقواس — يرسم الحدّ خطًّا رفيعًا
     * يُرى ولا يُلاحَظ: تبقى الأقواس هي ما يقرؤه البصر أولًا.
     */
    --hair: hsl(210 30% 94% / .16);
    /* وفي الوضع الداكن يهبط الشريط إلى أزرق hispa العميق مع خطٍّ رفيع من لون التمييز. */
    --topbar-bg: #14304f;
    --st-good: #3cb98a;
    --st-warn: #e0a53c;
    --st-critical: #ef5f7a;
    --st-neutral: #3f96e0;
    --st-none: #64748b;
}
* { box-sizing: border-box; margin: 0; padding: 0; border-color: hsl(var(--border)); }

/*
 * الخطّان مقصودان بهذا الترتيب: Chakra Petch لا يحمل حروفًا عربية، فتلتقط
 * Tajawal العربية ويبقى الرقم واللاتيني على الخطّ الهندسي الحادّ.
 */
/*
 * الخلفية: بياضٌ يكاد يكون ناصعًا، فوقه شبكة ورق رسم من سوادٍ بشفافية 3٪ —
 * خافتة إلى حدّ أنها لا تُقرأ لونًا بل عمقًا، فلا تزاحم البطاقات الشفّافة
 * التي تمرّ فوقها. مثبّتة (`fixed`) حتى لا تنزلق الشبكة مع التمرير فيبدو
 * الأمر كما لو أن الصفحة كلّها ورقة واحدة.
 */
body {
    font-family: 'Chakra Petch', 'Tajawal', ui-sans-serif, system-ui, sans-serif;
    background-color: hsl(var(--background));
    background-image: url('{{ asset('images/pattern.png') }}');
    background-size: 4.6875rem;
    background-attachment: fixed;
    color: hsl(var(--foreground));
    -webkit-font-smoothing: antialiased;
}
/*
 * الوضع الداكن ثلاث طبقات، من الأسفل إلى الأعلى — بالقيم نفسها المعتمدة في
 * حسبة حرفًا بحرف، حتى تتطابق إضاءة الصفحتين:
 *
 *   1. صورة غلافٍ بحرية — هي كلّ ما في الخلفية من تفصيل.
 *   2. حجابٌ رماديّ مائل إلى الأزرق لا أزرقُ مشبع: يبدأ من rgb(50 70 80)
 *      بشفافية 90٪ في أعلى الشاشة وينتهي مصمتًا تمامًا عند rgb(13 16 27) في
 *      أسفلها. فالمصمتُ في الأسفل يطفئ وهج الكرة، والرماديّ في الأعلى يمنع
 *      الصفحة من أن تُقرأ زرقاء.
 *   3. شبكة ورق الرسم نفسها التي في الوضع الفاتح، لكن بخطوطٍ بيضاء بشفافية
 *      2٪ بدل السوداء — فوق الحجاب لا تحته، وإلا ابتلعها.
 *
 * والثلاث مثبّتة على إطار الشاشة (`background-attachment: fixed` موروث من
 * `body`) فلا تنزلق مع التمرير.
 */
html.dark body {
    background-color: #1d2835;
    background-image:
        url('{{ asset('images/pattern-dark.png') }}'),
        linear-gradient(180deg, rgba(50, 70, 80, .9) 0%, rgb(13, 16, 27) 100%),
        url('{{ asset('images/cover-dark.jpg') }}');
    background-position: 0 0, center, center;
    background-size: 4.6875rem, cover, cover;
}
a { color: inherit; text-decoration: none; }
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-thumb { background: hsl(var(--muted-foreground) / .3); }
::-webkit-scrollbar-track { background: hsl(var(--muted) / .5); }

/* الأرقام في خانات متساوية العرض حتى تصطفّ رأسيًا في البطاقات والجداول. */
.value, .g-value, .m-value, .count, .totals, .data-table td, .kpi-card .value, .stat-card .value {
    font-variant-numeric: tabular-nums;
}

/*
 * أقواس الزوايا. كل سطح مُحاط بها يحمل حدًّا شعريًا وزاوية قائمة، والأقواس
 * تُرسم فوقه في طبقة لا تلتقط النقر.
 */
.card, .filter-bar, .entity-card, .report-card,
.settings-panel, .note-box, .gap-card, .alert-group, .alert-row, .notif-card,
.org-node, .cal, .cal-nav, .chat, .insight-head, .note-list, .screen-tile,
.portal-card, .pending-card, .seg {
    position: relative;
    border-radius: 0;
}
.card::after, .filter-bar::after,
.entity-card::after, .report-card::after, .settings-panel::after, .note-box::after,
.gap-card::after, .alert-group::after, .alert-row::after, .notif-card::after,
.org-node::after, .cal::after, .cal-nav::after, .chat::after, .insight-head::after,
.note-list::after, .screen-tile::after, .portal-card::after, .pending-card::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
    background:
        linear-gradient(var(--brk-color), var(--brk-color)) top left / var(--brk-len) var(--brk-th) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) top left / var(--brk-th) var(--brk-len) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) top right / var(--brk-len) var(--brk-th) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) top right / var(--brk-th) var(--brk-len) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) bottom left / var(--brk-len) var(--brk-th) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) bottom left / var(--brk-th) var(--brk-len) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) bottom right / var(--brk-len) var(--brk-th) no-repeat,
        linear-gradient(var(--brk-color), var(--brk-color)) bottom right / var(--brk-th) var(--brk-len) no-repeat;
}
/* الأقواس للسطح الخارجي وحده — صندوق داخل بطاقة لا يرسمها مرّة ثانية. */
.card :is(.card, .filter-bar, .entity-card, .report-card,
.settings-panel, .note-box, .gap-card, .alert-group, .alert-row, .notif-card,
.org-node, .cal, .cal-nav, .chat, .insight-head, .note-list, .portal-card, .pending-card)::after {
    display: none;
}

.shell { min-height: 100vh; padding-top: var(--topbar-h); }
/*
 * القائمة الجانبية شفّافة على الشاشة العريضة حيث لا يمرّ تحتها محتوى. أمّا
 * دون ذلك فهي لوح ينزلق فوق الصفحة، فيحتاج سطحًا مصمتًا يحجب ما تحته.
 */
.sidebar {
    position: fixed; inset-block: var(--topbar-h) 0; right: 0; z-index: 40; width: var(--sidebar-w);
    background: hsl(var(--background)); border-left: 1px solid var(--hair);
    display: flex; flex-direction: column; transform: translateX(100%);
    transition: transform .3s ease;
}
.sidebar.is-open { transform: translateX(0); }
.backdrop { position: fixed; inset: 0; z-index: 30; background: rgba(0,0,0,.4); display: none; }
.backdrop.is-visible { display: block; }
.main { min-height: 100vh; }
@media (min-width: 1024px) {
    /* بلا خطٍّ فاصل: شبكة الصفحة تمرّ تحت القائمة متّصلة، فتبدو اللوحة سطحًا واحدًا. */
    .sidebar { transform: translateX(0); background: var(--surface); border-left: 0; }
    .main { margin-right: var(--sidebar-w); }
    .backdrop { display: none !important; }
    .menu-btn, .sidebar-head { display: none !important; }
}

/*
 * رأس القائمة لم يبقَ فيه إلا زرّ الإغلاق بعد أن صعد الشعار إلى الشريط العلوي،
 * فلا وجود له فوق 1024px حيث لا يُغلَق شيء — القاعدة في كتلة 1024px أعلاه.
 */
.sidebar-head { display: flex; align-items: center; height: 2.5rem; padding: 0 .65rem; flex-shrink: 0; }
.sidebar-close { margin-right: auto; border: 0; background: none; color: hsl(var(--muted-foreground)); padding: .35rem; cursor: pointer; }

/*
 * القائمة أطول من الشاشة فتُمرَّر، لكن شريط التمرير يُخفى: عمودٌ رماديّ ملتصق
 * بحافّة القائمة يُقرأ حدًّا فاصلًا — وهو ما تخلّصنا منه أصلًا بإسقاط الإطار.
 * التمرير نفسه باقٍ بالعجلة واللمس ولوحة المفاتيح.
 */
.sidebar-nav { flex: 1; overflow-y: auto; padding: .25rem 0 .5rem; scrollbar-width: none; -ms-overflow-style: none; }
.sidebar-nav::-webkit-scrollbar { width: 0; height: 0; }

.nav-section-title { padding: 1rem 1rem .5rem; font-size: .65625rem; font-weight: 600; color: hsl(var(--muted-foreground) / .55); }
/* القسم بلا عنوان (روابط المالك الأولى) ثابت أعلى القائمة، يفصله خطٌّ عن الأقسام المطويّة تحته. */
.nav-section.is-pinned { padding: .35rem 0 .5rem; margin-bottom: .35rem; border-bottom: 1px solid var(--hair); }
/*
 * القسم المطويّ (قائمة المالك): عنوانه <summary> يطويه، بسهمٍ في طرفه الأيسر
 * بدل علامة المتصفّح — يتّجه لأسفل مفتوحًا ونحو البنود (يسارًا) مطويًّا.
 * بقياس البند نفسه (14px)، يعلوه وزنًا لا حجمًا.
 */
summary.nav-section-title {
    display: flex; align-items: center; gap: .5rem; min-height: 2.25rem; padding: .4rem 1rem;
    font-size: .875rem; line-height: 1.5; font-weight: 700; color: hsl(var(--muted-foreground));
    cursor: pointer; list-style: none; user-select: none; transition: color .15s;
}
summary.nav-section-title::-webkit-details-marker { display: none; }
summary.nav-section-title:hover, details.nav-section[data-current] > summary { color: hsl(var(--foreground)); }
summary.nav-section-title svg { width: .875rem; height: .875rem; margin-right: auto; transition: transform .15s; }
details.nav-section:not([open]) > summary svg { transform: rotate(90deg); }
details.nav-section[open] { padding-bottom: .35rem; }
/*
 * البند بقياس hispa حرفيًّا: 14px على سطرٍ ارتفاعه 1.5 داخل صفٍّ من 2rem
 * وحشوة .3rem/1rem — لا 2.5rem كما كان، فقد بدا البند أفسح من مرجعه. لا خلفية
 * عند المرور ولا عند النشاط: الخلفية الملوّنة تقطع شبكة الصفحة تحتها، ويكفي أن
 * يسودّ النصّ وتزرقّ الأيقونة.
 */
.nav-link {
    position: relative; display: flex; align-items: center; gap: .75rem;
    min-height: 2rem; padding: .3rem 1rem;
    font-size: .875rem; line-height: 1.5; font-weight: 500; color: hsl(var(--muted-foreground));
    transition: color .15s;
}
.nav-link:hover { color: hsl(var(--foreground)); }
.nav-link.is-active { color: hsl(var(--foreground)); font-weight: 700; }
.nav-link.is-active svg { color: hsl(var(--primary)); }
.nav-link svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
.nav-link span { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/*
 * ذيل القائمة: كلّ ما بقي من الشريط العلوي بعد إزالته — بطاقة المستخدم وزرّ
 * الوضع الداكن. أمّا مربّع البحث وجرس التنبيهات فسقطا معه: كلاهما كان بلا
 * وظيفة خلفه.
 */
.sidebar-foot { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem 1rem; flex-shrink: 0; }
.user-chip { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 0; }
.user-chip .avatar { display: flex; align-items: center; justify-content: center; height: 1.75rem; width: 1.75rem; flex-shrink: 0; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-size: .72rem; font-weight: 700; }
.user-chip .role { font-size: .75rem; font-weight: 700; line-height: 1.2; }
.user-chip .sub { font-size: 10px; color: hsl(var(--muted-foreground)); }
.icon-btn { position: relative; border: 0; background: none; color: hsl(var(--muted-foreground)); padding: .45rem; cursor: pointer; }
.icon-btn:hover { color: hsl(var(--foreground)); }
.icon-btn svg { width: 18px; height: 18px; display: block; }

/*
 * الشريط العلوي بنسخة hispa: لوحٌ أزرق مصمت مثبّت بعرض الصفحة كلّها فوق
 * القائمة الجانبية (لا داخل عمود المحتوى)، ارتفاعه 3.25rem — وهو ما تُزاح به
 * `.shell` من أعلى ويبدأ عنده رأس القائمة. المصمت هنا مقصود لا مصادفة: الشريط
 * ثابت والصفحة تجري تحته، فالشفافية تُظهر المحتوى خلف الشعار.
 */
.topbar {
    position: fixed; inset: 0 0 auto; z-index: 45; display: flex; align-items: center;
    height: var(--topbar-h); background: var(--topbar-bg); color: var(--topbar-fg);
}
/* خطّ التمييز الرفيع لا يظهر إلا في الوضع الداكن، حيث يقارب الشريطُ خلفيةَ الصفحة. */
html.dark .topbar { border-bottom: 1px solid hsl(var(--primary) / .4); }

/*
 * كتلة الشعار بعرض القائمة الجانبية نفسه، فيقف الشعار فوق عمودها لا فوق
 * المحتوى. والشعار كلمةٌ عريضة (نسبته 2.4:1) فالارتفاع وحده هو المحدَّد
 * والعرض يتبعه — و34px هو قياس hispa داخل شريطٍ بهذا الارتفاع.
 */
.topbar-brand { display: flex; align-items: center; width: var(--sidebar-w); height: 100%; padding: 0 1rem; flex-shrink: 0; }
.topbar-brand img { height: 34px; width: auto; max-width: 100%; object-fit: contain; }
.topbar-actions { display: flex; align-items: center; margin-right: auto; }

/* أزرار الشريط بيضاء على الأزرق: الخفوت هنا شفافيةٌ لا لونٌ آخر. */
.topbar .icon-btn, .topbar .menu-btn { color: hsl(0 0% 100% / .82); padding: .5rem .9rem; }
.topbar .icon-btn:hover, .topbar .menu-btn:hover { color: var(--topbar-fg); }
.topbar .icon-btn svg, .topbar .menu-btn svg { width: 20px; height: 20px; display: block; }
.menu-btn { border: 0; background: none; cursor: pointer; }
@media (max-width: 767px) { .topbar-brand { display: none; } }

/*
 * عمود المحتوى بلا سقفٍ لعرضه: كان محكومًا بـ 82.5rem، فكان يتوقّف عن النموّ
 * بعد 1320px ويتحوّل كلّ ما زاد إلى فراغٍ أيسر — على شاشة 1920px يقارب الثلث.
 * صار كـ hispa: يملأ ما بين القائمة والهامش الخارجي، والهامش وحده هو المحجوز.
 * فيكبر المحتوى مع الشاشة بدل أن يقف عندها.
 */
.content { width: auto; margin-inline-end: var(--content-gutter); padding: 1.25rem 2rem 2rem; }
{{-- دون 1024px تصير القائمة لوحًا منزلقًا والصفحة كلّها للمحتوى: لا هامش يُقتطع. --}}
@media (max-width: 1024px) { .content { margin-inline-end: 0; } }
@media (max-width: 640px) { .content { padding: 1rem; } }

/*
 * إيقاع واحد بين كتل الصفحة. الفاصل يُملى من هنا لا من نمط داخل كل صفحة، حتى
 * لا تتفاوت الفراغات بين لوحة وأخرى.
 */
.content > * { margin-bottom: var(--gap); }
.content > *:last-child { margin-bottom: 0; }
.content > .section-head { margin: 1.25rem 0 .7rem; }
.content > .section-head:first-child { margin-top: 0; }

/* ترويسة الصفحة: العنوان وحده، بلا صندوق ولا خط فاصل ثقيل. */
.page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: 1.1rem; }
.page-header .lead { display: flex; align-items: flex-start; gap: .7rem; }
.page-header .icon-wrap { display: flex; align-items: center; justify-content: center; height: 2.25rem; width: 2.25rem; flex-shrink: 0; background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .45); color: hsl(var(--primary)); }
.page-header .icon-wrap svg { width: 18px; height: 18px; }
.page-header h1 { font-size: 1.2rem; font-weight: 700; line-height: 1.3; }
.page-header p { margin-top: .15rem; font-size: .76rem; color: hsl(var(--muted-foreground)); }
.page-header .actions { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }

/*
 * ترويسة القسم: مربّع أيقونة، ثم العنوان، ثم خطّ يملأ ما بقي من السطر — فلا
 * يبقى فراغ بين العنوان وحافّة اللوحة.
 */
.section-head { display: flex; align-items: center; gap: .6rem; margin: 1.35rem 0 .7rem; }
.section-head:first-child { margin-top: 0; }
.section-head .ico { display: flex; align-items: center; justify-content: center; height: 1.9rem; width: 1.9rem; flex-shrink: 0; background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .5); color: hsl(var(--primary)); }
.section-head .ico svg { width: 15px; height: 15px; }
.section-head h2 { font-size: .82rem; font-weight: 700; letter-spacing: .02em; white-space: nowrap; }
.section-head .line { flex: 1; height: 1px; background: linear-gradient(to left, hsl(var(--border)), transparent); }
.section-head .with-icon { display: flex; align-items: center; gap: .5rem; }
.section-head .with-icon svg { width: 15px; height: 15px; color: hsl(var(--primary)); }

/* البطاقة: سطح مصمت وحدّ شعري وأقواس زوايا — بلا ظلّ ولا انحناء. */
.card {
    display: flex; flex-direction: column;
    border: 1px solid var(--hair); background: var(--surface); padding: .9rem 1rem;
}
.card-title { font-size: .82rem; font-weight: 700; letter-spacing: .01em; }
.card-sub { font-size: .7rem; color: hsl(var(--muted-foreground)); margin-top: .1rem; }

/* البطاقات داخل الشبكات تتساوى ارتفاعًا فلا يبقى فراغ أسفل الأقصر منها. */
.grid-2 > *, .grid-3 > *, .cards-grid > *, .stat-grid > *, .kpi-grid > *,
.portal-grid > *, .settings-grid > *, .gov-grid > *, .workflow-grid > * { height: 100%; }

/*
 * بطاقة المؤشر: العنوان ومربّع الأيقونة في سطر، والقيمة تحتهما — القياسات
 * والفواصل هي المعتمدة في اللوحة المرجعية.
 */
.kpi-grid { display: grid; gap: var(--gap); grid-template-columns: repeat(2, 1fr); }
@media (min-width: 640px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 1280px) { .kpi-grid { grid-template-columns: repeat(5, 1fr); } }
.stat-grid { display: grid; gap: var(--gap); grid-template-columns: repeat(2, 1fr); }
@media (min-width: 640px) { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .stat-grid.cols-4 { grid-template-columns: repeat(4, 1fr); } .stat-grid.cols-5 { grid-template-columns: repeat(5, 1fr); } .stat-grid.cols-6 { grid-template-columns: repeat(6, 1fr); } }

/*
 * بطاقة المؤشر وحدها مستديرة الزوايا استدارةً خفيفة وبلا أقواس: هكذا هي في
 * اللوحة المرجعية، فتُميَّز عن بقية السطوح القائمة الزوايا. والقيمة تُدفَع إلى
 * أسفل البطاقة لا تلتصق بعنوانها.
 */
.kpi-card, .stat-card {
    display: flex; flex-direction: column; min-height: 5.75rem;
    border: 1px solid var(--hair); border-radius: var(--stat-radius); background: var(--surface);
    padding: .85rem .95rem;
}
.kpi-card .top, .stat-card .top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
.kpi-card .value, .stat-card .value { margin-top: auto; }
/*
 * الرقم هو الخبر، فله الحجم الأكبر؛ والعنوان يسبقه صغيرًا ليقول ما يقيسه.
 */
.kpi-card .label, .stat-card .label { font-size: .95rem; font-weight: 700; line-height: 1.45; color: hsl(var(--foreground)); }
.kpi-card .value, .stat-card .value { font-size: 1.45rem; font-weight: 700; line-height: 1.15; letter-spacing: .01em; color: hsl(var(--foreground)); }
.kpi-card .unit, .stat-card .unit { font-size: .8rem; font-weight: 600; color: hsl(var(--muted-foreground)); margin-right: .3rem; }
.kpi-icon {
    display: flex; align-items: center; justify-content: center; height: 1.4rem; width: 1.4rem; flex-shrink: 0;
    background: hsl(var(--primary) / .08); border: 1px solid hsl(var(--primary) / .28); color: hsl(var(--primary));
}
.kpi-icon svg { width: 12px; height: 12px; }
.kpi-icon.info { background: hsl(199 89% 40% / .1); border-color: hsl(199 89% 40% / .35); color: hsl(199 89% 32%); }
.kpi-icon.success { background: hsl(160 62% 35% / .1); border-color: hsl(160 62% 35% / .35); color: hsl(160 68% 28%); }
.kpi-icon.warning { background: hsl(35 92% 45% / .12); border-color: hsl(35 92% 45% / .38); color: hsl(30 90% 36%); }
.kpi-icon.danger { background: hsl(352 80% 50% / .1); border-color: hsl(352 80% 50% / .35); color: hsl(352 76% 44%); }
html.dark .kpi-icon.info { color: hsl(199 85% 70%); }
html.dark .kpi-icon.success { color: hsl(160 60% 62%); }
html.dark .kpi-icon.warning { color: hsl(38 90% 66%); }
html.dark .kpi-icon.danger { color: hsl(352 85% 70%); }

.grid-3 { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 1024px) { .grid-3 { grid-template-columns: repeat(3, 1fr); } .span-2 { grid-column: span 2; } }
.grid-2 { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 1024px) { .grid-2 { grid-template-columns: repeat(2, 1fr); } }

.alert-item { display: flex; align-items: flex-start; gap: .65rem; border: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .45); padding: .6rem .7rem; margin-bottom: .4rem; }
.alert-item .sev-dot { margin-top: .35rem; height: 7px; width: 7px; flex-shrink: 0; }
.alert-item .a-title { font-size: .82rem; font-weight: 600; }
.alert-item .a-desc { font-size: .7rem; color: hsl(var(--muted-foreground)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }

.badge { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid; padding: .12rem .5rem; font-size: .68rem; font-weight: 700; white-space: nowrap; }
.badge::before { content: ''; height: 5px; width: 5px; background: currentColor; }
.badge-ok { color: #0f7a5a; border-color: #0f7a5a55; background: #0f7a5a14; }
.badge-warn { color: #b45309; border-color: #b4530955; background: #b4530914; }
.badge-danger { color: #d61f47; border-color: #d61f4755; background: #d61f4714; }
.badge-info { color: #0369a1; border-color: #0369a155; background: #0369a114; }
html.dark .badge-ok { color: hsl(160 60% 62%); border-color: hsl(160 60% 62% / .4); background: hsl(160 60% 40% / .14); }
html.dark .badge-warn { color: hsl(38 90% 66%); border-color: hsl(38 90% 66% / .4); background: hsl(38 90% 45% / .14); }
html.dark .badge-danger { color: hsl(352 85% 70%); border-color: hsl(352 85% 70% / .4); background: hsl(352 80% 50% / .14); }
html.dark .badge-info { color: hsl(199 85% 70%); border-color: hsl(199 85% 70% / .4); background: hsl(199 85% 50% / .14); }
/* رقاقة محايدة — لِما لا حالة له: "معطل"، "بُعد"، "فارغ"، مصدر الترحيل. */
.badge-muted { color: hsl(var(--muted-foreground)); border-color: hsl(var(--border)); background: hsl(var(--muted) / .6); }

.note-box { display: flex; align-items: flex-start; gap: .7rem; border: 1px solid hsl(160 55% 40% / .4); background: hsl(160 55% 40% / .07); padding: .9rem 1rem; margin-top: 1.1rem; }
.note-box svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; color: #0f7a5a; }
html.dark .note-box svg { color: hsl(160 60% 62%); }
.note-box .n-title { font-size: .82rem; font-weight: 700; }
.note-box .n-body { margin-top: .25rem; font-size: .72rem; line-height: 1.9; color: hsl(var(--muted-foreground)); }

/* الرسم يملأ ما بقي من بطاقته، فلا يترك فراغًا تحته حين تعلو البطاقة المجاورة. */
.chart-wrap { position: relative; flex: 1 1 auto; min-height: 220px; }
.link-more { display: inline-flex; align-items: center; gap: .25rem; font-size: .72rem; font-weight: 700; color: hsl(var(--primary)); }
.link-more:hover { text-decoration: underline; }

.pending-card { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .7rem; border: 1px solid var(--hair); background: var(--surface); padding: 3.25rem 2rem; text-align: center; }
.pending-card svg { width: 34px; height: 34px; color: hsl(var(--muted-foreground) / .7); }
.pending-card h3 { font-size: 1rem; font-weight: 700; }
.pending-card p { font-size: .76rem; color: hsl(var(--muted-foreground)); max-width: 28rem; line-height: 1.9; }

.filter-bar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .65rem; border: 1px solid var(--hair); background: var(--surface); padding: .85rem .95rem; }
/* زرّ الشريط الأخير يلتصق بالطرف المقابل، فلا يبقى فراغ في آخر السطر. */
.filter-bar > .btn:last-child { margin-inline-start: auto; }
.field { display: flex; flex-direction: column; gap: .3rem; }
/* السمة hidden تغلب display المصنّف: حقل يُخفى بها يختفي فعلًا. */
.field[hidden] { display: none; }
.field > span { font-size: .7rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
/*
 * بوابة المالك: شريط خطوات الرحلة الأربع كما في التطبيق، وجدول سطور البيع
 * الذي تُكتب فيه الأوزان والأسعار مباشرة، وقائمة تفاصيل مفتاح/قيمة.
 */
.trip-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; list-style: none; margin: 0; padding: 0; }
.trip-steps li { display: flex; flex-direction: column; align-items: center; gap: .4rem; text-align: center; font-size: .72rem; color: hsl(var(--muted-foreground)); }
.trip-steps li .dot { display: flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border: 1px solid var(--hair); background: var(--surface); font-family: 'Chakra Petch', sans-serif; font-weight: 700; }
.trip-steps li .dot svg { width: 14px; height: 14px; }
.trip-steps li.is-done .dot { background: hsl(var(--primary)); border-color: hsl(var(--primary)); color: #fff; }
.trip-steps li.is-current .dot { border-color: hsl(var(--primary)); color: hsl(var(--primary)); box-shadow: 0 0 0 3px hsl(var(--primary) / .18); }
.trip-steps li.is-done, .trip-steps li.is-current { color: hsl(var(--foreground)); font-weight: 600; }
.trip-steps[data-cancelled] li { opacity: .45; }
/*
 * بوابة الكابتن: بطاقة الرحلة كما في التطبيق (رأس برقمها وحالتها، شبكة
 * بيانات، ثم أزرار الفعل)، وسطر الإشعار (غير المقروء بحافّة أوّلية مميّزة).
 */
.trip-card { border: 1px solid var(--hair); background: hsl(var(--foreground) / .02); padding: .85rem .95rem; display: flex; flex-direction: column; gap: .7rem; }
.trip-card + .trip-card { margin-top: .65rem; }
.trip-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
.trip-card-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr)); gap: .5rem .9rem; margin: 0; }
.trip-card-meta dt { font-size: .66rem; color: hsl(var(--muted-foreground)); }
.trip-card-meta dd { margin: 0; font-size: .8rem; font-weight: 600; }
.trip-card-actions { display: flex; flex-wrap: wrap; gap: .4rem; border-top: 1px solid var(--hair); padding-top: .65rem; }
.notif-item { display: flex; align-items: flex-start; gap: .8rem; border: 1px solid var(--hair); border-inline-start: 3px solid transparent; background: var(--surface); padding: .8rem .95rem; }
.notif-item + .notif-item { margin-top: .5rem; }
.notif-item.is-unread { border-inline-start-color: hsl(var(--primary)); background: hsl(var(--primary) / .05); }
.notif-item .n-icon { display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; flex-shrink: 0; border: 1px solid hsl(var(--primary) / .28); background: hsl(var(--primary) / .08); color: hsl(var(--primary)); }
.notif-item .n-icon svg { width: 15px; height: 15px; }
.notif-item .n-title { font-size: .84rem; font-weight: 700; }
.notif-item .n-body { font-size: .74rem; color: hsl(var(--muted-foreground)); line-height: 1.8; margin-top: .15rem; }
.notif-item .n-time { font-size: .66rem; color: hsl(var(--muted-foreground)); white-space: nowrap; }
.notif-item form { margin-inline-start: auto; }
.topbar-bell { position: relative; }
.topbar-bell .count { position: absolute; top: -.2rem; inset-inline-end: -.2rem; min-width: 1.05rem; height: 1.05rem; padding: 0 .25rem; display: flex; align-items: center; justify-content: center; background: #d61f47; color: #fff; font-family: 'Chakra Petch', sans-serif; font-size: .62rem; font-weight: 700; }
.avatar-lg { width: 5rem; height: 5rem; display: flex; align-items: center; justify-content: center; border: 1px solid var(--hair); background: hsl(var(--primary) / .1); color: hsl(var(--primary)); font-size: 1.8rem; font-weight: 700; overflow: hidden; flex-shrink: 0; }
.avatar-lg img { width: 100%; height: 100%; object-fit: cover; }
.avatar-sm { width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--hair); background: hsl(var(--primary) / .1); color: hsl(var(--primary)); font-size: .8rem; font-weight: 700; overflow: hidden; flex-shrink: 0; vertical-align: middle; }
.avatar-sm img { width: 100%; height: 100%; object-fit: cover; }
.person-cell { display: flex; align-items: center; gap: .55rem; }
.lines-table td { vertical-align: middle; }
.lines-table .input, .lines-table .select { width: 100%; min-width: 6rem; }
.lines-table .num { font-family: 'Chakra Petch', sans-serif; font-weight: 600; white-space: nowrap; }
.detail-list { display: grid; grid-template-columns: auto 1fr; gap: .35rem 1rem; font-size: .8rem; margin: 0; }
.detail-list dt { color: hsl(var(--muted-foreground)); white-space: nowrap; }
.detail-list dd { margin: 0; }
.num { font-family: 'Chakra Petch', sans-serif; }
/*
 * الحقول تتبع قاعدة اللوحة نفسها: لا لونَ خلفيةٍ خاص بها. كانت تُملأ بـ
 * `--background` فبدت في الوضع الداكن مربّعاتٍ سوداء مقتطعة من الصفحة بعد أن
 * خفّت زرقة الخلفية؛ فصارت شفّافةً يحدّها الخطّ الشعري وحده كما البطاقات،
 * وتحتها لمسةُ حبرٍ بشفافية 4٪ تكفي لتمييز موضع الكتابة دون أن تُقرأ لونًا.
 */
.input, .select { border: 1px solid hsl(var(--border)); background: hsl(var(--foreground) / .04); padding: .45rem .65rem; font-size: .82rem; font-family: inherit; color: inherit; outline: none; width: 100%; }
.input:focus, .select:focus, .input:focus-visible, .select:focus-visible { border-color: hsl(var(--primary)); outline: none; }
/* قائمة الخيارات المنسدلة يرسمها المتصفّح لا نحن، فتحتاج سطحًا مصمتًا. */
.select option, .select optgroup { background: hsl(var(--background)); color: hsl(var(--foreground)); }
/* عنوان المجموعة يُقرأ عنوانًا لا خيارًا: أخفت من الخيارات التي تحته. */
.select optgroup { color: hsl(var(--muted-foreground)); font-weight: 700; }
.select optgroup option { color: hsl(var(--foreground)); font-weight: 400; }
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .85rem; font-size: .8rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; font-family: inherit; transition: background .15s, border-color .15s; }
.btn svg { width: 15px; height: 15px; }
.btn-primary { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); border-color: hsl(var(--primary)); }
.btn-primary:hover { background: hsl(var(--primary) / .88); }
.btn-outline { background: transparent; color: hsl(var(--foreground)); border-color: hsl(var(--border)); }
.btn-outline:hover { background: hsl(var(--muted)); border-color: hsl(var(--primary) / .5); }
.icon-action { border: 0; background: none; cursor: pointer; padding: .3rem; color: hsl(var(--muted-foreground)); }
.icon-action svg { width: 15px; height: 15px; display: block; }
.icon-action:hover { background: hsl(var(--muted)); color: hsl(var(--primary)); }
.icon-action.danger:hover { background: hsl(352 80% 50% / .12); color: #d61f47; }

.table-card { overflow-x: auto; border: 1px solid var(--hair); background: var(--surface); }
.data-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.data-table th { background: hsl(var(--muted) / .8); padding: .55rem .7rem; text-align: right; font-size: .7rem; font-weight: 700; letter-spacing: .02em; color: hsl(var(--muted-foreground)); white-space: nowrap; border-bottom: 1px solid hsl(var(--border)); }
.data-table td { padding: .55rem .7rem; border-top: 1px solid hsl(var(--border) / .6); vertical-align: middle; }
.data-table tbody tr:hover { background: hsl(var(--primary) / .04); }
/* التاريخ ورقم السند والرحلة لا يُكسر عند الشرطة فينقلب نصفه إلى سطرٍ ثانٍ. */
.data-table .num { white-space: nowrap; }
/* سطر الخانة الأول بما يلحقه من رقاقة أو أيقونة، وسطرها الثاني الخافت تحته. */
.cell-line { display: flex; align-items: center; gap: .35rem; white-space: nowrap; }
.cell-icon { display: inline-flex; color: hsl(var(--muted-foreground)); }
.cell-icon svg { width: 13px; height: 13px; }
.cell-sub { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 400; color: hsl(var(--muted-foreground)); }
a.cell-sub:hover { color: hsl(var(--primary)); }
/* نصٌّ حرّ الطول يُقصّ في سطرٍ واحد، والكامل في title. */
.cell-sub.clip { max-width: 20rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.cards-grid { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 640px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .cards-grid.cols-3 { grid-template-columns: repeat(3, 1fr); } .cards-grid.cols-4 { grid-template-columns: repeat(4, 1fr); } }
.entity-card { display: flex; flex-direction: column; border: 1px solid var(--hair); background: var(--surface); padding: 1rem; transition: border-color .15s; color: inherit; text-align: right; }
.entity-card:hover { border-color: hsl(var(--primary) / .6); }
.mini { display: flex; align-items: center; gap: .45rem; background: hsl(var(--muted) / .6); padding: .45rem .55rem; min-width: 0; }
.mini svg { width: 15px; height: 15px; flex-shrink: 0; color: hsl(var(--primary)); }
.mini .m-label { font-size: 10px; color: hsl(var(--muted-foreground)); }
.mini .m-value { font-size: .82rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-grid { display: grid; gap: .5rem; grid-template-columns: repeat(2, 1fr); margin-top: .85rem; }
.progress { height: .4rem; width: 100%; overflow: hidden; background: hsl(var(--muted)); }
.progress > div { height: 100%; transition: width .3s; }

.drawer-overlay { position: fixed; inset: 0; z-index: 50; background: rgba(0, 0, 0, .6); display: none; }
.drawer-overlay.is-open { display: block; }
{{-- النافذة المنبثقة والشريط العائم يعلوان المحتوى، فيبقيان مصمتين وحدهما. --}}
.drawer { position: fixed; top: 50%; left: 50%; z-index: 51; width: min(48rem, calc(100vw - 2.5rem)); max-height: min(88vh, 52rem); overflow-y: auto; background: hsl(var(--background)); border: 1px solid hsl(var(--border)); box-shadow: 0 28px 70px -20px rgba(0, 0, 0, .55); opacity: 0; visibility: hidden; transform: translate(-50%, -46%) scale(.97); transition: opacity .2s, transform .2s, visibility .2s; }
.drawer.is-open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
.drawer.wide { width: min(64rem, calc(100vw - 2.5rem)); }
.drawer-head { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--hair); background: hsl(var(--background)); padding: 1rem 1.4rem; }
.drawer-head h3 { font-size: 1.05rem; font-weight: 700; }
.drawer-body { padding: 1.4rem; display: flex; flex-direction: column; gap: 1rem; }
.form-grid { display: grid; gap: .9rem; grid-template-columns: 1fr; }
@media (min-width: 640px) { .form-grid { grid-template-columns: repeat(2, 1fr); } .form-grid .wide { grid-column: span 2; } }
/* النماذج الطويلة (القارب) تُعرض في ثلاثة أعمدة على الدرج العريض. */
@media (min-width: 900px) { .form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); } .form-grid.cols-3 .wide { grid-column: span 3; } }

.tag { display: inline-flex; align-items: center; gap: .25rem; padding: .1rem .45rem; font-size: 10px; font-weight: 600; border: 1px solid; }
.tag-gulf { background: #0369a114; color: #0369a1; border-color: #0369a144; }
.tag-red { background: #d61f4714; color: #be123c; border-color: #d61f4744; }
html.dark .tag-gulf { color: hsl(199 85% 70%); border-color: hsl(199 85% 70% / .4); }
html.dark .tag-red { color: hsl(352 85% 70%); border-color: hsl(352 85% 70% / .4); }

.group-head { display: flex; align-items: center; gap: .5rem; border-bottom: 2px solid hsl(var(--border)); padding-bottom: .45rem; margin-bottom: .7rem; }
.group-head.gulf { border-color: #38bdf8; }
.group-head.red { border-color: #fb7185; }
.group-head h2 { font-size: .95rem; font-weight: 700; }
.count-pill { background: hsl(var(--muted)); padding: .1rem .45rem; font-size: .7rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.hier-chip { background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .35); padding: .4rem .85rem; font-size: .82rem; font-weight: 600; color: hsl(var(--primary)); }
.legend-row { display: flex; align-items: center; justify-content: space-between; font-size: .74rem; margin-bottom: .45rem; }
.legend-row .l-icon { display: inline-flex; height: 1.15rem; width: 1.15rem; align-items: center; justify-content: center; color: #fff; margin-left: .5rem; }
.legend-row .l-icon svg { width: 11px; height: 11px; }

.workflow-grid { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 768px) { .workflow-grid { grid-template-columns: repeat(4, 1fr); } }
.pill { padding: .18rem .5rem; font-size: .7rem; font-weight: 600; white-space: nowrap; border: 1px solid; }
.pill-emerald { background: #0f7a5a14; color: #0f7a5a; border-color: #0f7a5a44; }
.pill-sky { background: #0369a114; color: #0369a1; border-color: #0369a144; }
.pill-amber { background: #b4530914; color: #b45309; border-color: #b4530944; }
.pill-rose { background: #d61f4714; color: #be123c; border-color: #d61f4744; }
.pill-slate { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); border-color: hsl(var(--border)); }
html.dark .pill-emerald { color: hsl(160 60% 62%); border-color: hsl(160 60% 62% / .4); }
html.dark .pill-sky { color: hsl(199 85% 70%); border-color: hsl(199 85% 70% / .4); }
html.dark .pill-amber { color: hsl(38 90% 66%); border-color: hsl(38 90% 66% / .4); }
html.dark .pill-rose { color: hsl(352 85% 70%); border-color: hsl(352 85% 70% / .4); }

.gov-box { background: hsl(var(--muted) / .6); border: 1px solid hsl(var(--border) / .7); padding: .5rem .65rem; }
.gov-box .g-label { font-size: .7rem; color: hsl(var(--muted-foreground)); }
.gov-box .g-value { margin-top: .2rem; font-size: 1.3rem; font-weight: 700; }
.gov-grid { display: grid; gap: .6rem; grid-template-columns: repeat(2, 1fr); }
@media (min-width: 640px) { .gov-grid { grid-template-columns: repeat(4, 1fr); } }
.flash { border: 1px solid hsl(160 55% 40% / .45); background: hsl(160 55% 40% / .1); color: #0f7a5a; padding: .6rem .9rem; font-size: .8rem; margin-bottom: .85rem; }
.flash-error { border: 1px solid hsl(352 80% 50% / .45); background: hsl(352 80% 50% / .1); color: #be123c; padding: .6rem .9rem; font-size: .8rem; margin-bottom: .85rem; }
html.dark .flash-error { color: hsl(352 85% 72%); }
/* ترقيم الصفحات: زرّان على طرفي موضع الصفحة، والمعطّل منهما باهت بلا نقر. */
.pager { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem .95rem; border: 1px solid var(--hair); border-top: 0; background: var(--surface); font-size: .76rem; }
.pager-pos { color: hsl(var(--muted-foreground)); }
.pager .is-disabled { opacity: .45; pointer-events: none; }
html.dark .flash { color: hsl(160 60% 68%); }

/* قسم الإحصاء — بوابته الموحّدة ولوحاته */
.portal-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid hsl(var(--primary) / .5); background: hsl(var(--primary) / .1); padding: 1.1rem 1.25rem; margin-bottom: 1rem; }
.portal-hero h2 { font-size: 1.1rem; font-weight: 700; }
.portal-hero p { margin-top: .2rem; font-size: .78rem; color: hsl(var(--muted-foreground)); }
.portal-hero .tiles { display: flex; flex-wrap: wrap; gap: .5rem; }
.portal-hero .tile { border: 1px solid hsl(var(--primary) / .28); background: var(--surface); padding: .45rem .9rem; text-align: center; }
.portal-hero .tile b { display: block; font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
.portal-hero .tile span { font-size: 10px; color: hsl(var(--muted-foreground)); }

.portal-group { margin-bottom: 1.1rem; }
.portal-group .head { display: flex; align-items: center; gap: .65rem; margin-bottom: .65rem; }
.portal-group .head .badge-icon { display: flex; align-items: center; justify-content: center; height: 2.15rem; width: 2.15rem; flex-shrink: 0; border: 1px solid; }
.portal-group .head h3 { font-size: .95rem; font-weight: 700; }
.portal-group .head p { font-size: .7rem; color: hsl(var(--muted-foreground)); }
.tone-sky { border-color: #0369a166; background: #0369a114; color: #0369a1; }
.tone-amber { border-color: #b4530966; background: #b4530914; color: #b45309; }
.tone-violet { border-color: #6d28d966; background: #6d28d914; color: #6d28d9; }
.tone-emerald { border-color: #0f7a5a66; background: #0f7a5a14; color: #0f7a5a; }
.tone-cyan { border-color: #0e749066; background: #0e749014; color: #0e7490; }
.tone-rose { border-color: #be123c66; background: #be123c14; color: #be123c; }
html.dark .tone-sky { color: hsl(199 85% 72%); border-color: hsl(199 85% 72% / .45); }
html.dark .tone-amber { color: hsl(38 90% 68%); border-color: hsl(38 90% 68% / .45); }
html.dark .tone-violet { color: hsl(262 85% 78%); border-color: hsl(262 85% 78% / .45); }
html.dark .tone-emerald { color: hsl(160 60% 64%); border-color: hsl(160 60% 64% / .45); }
html.dark .tone-cyan { color: hsl(188 80% 66%); border-color: hsl(188 80% 66% / .45); }
html.dark .tone-rose { color: hsl(352 85% 72%); border-color: hsl(352 85% 72% / .45); }

.portal-grid { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 640px) { .portal-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .portal-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1440px) { .portal-grid { grid-template-columns: repeat(4, 1fr); } }
.portal-card { display: flex; flex-direction: column; gap: .45rem; border: 1px solid var(--hair); background: var(--surface); padding: .9rem; transition: border-color .15s, background .15s; }
.portal-card:hover { border-color: hsl(var(--primary) / .6); background: hsl(var(--primary) / .05); }
.portal-card .top { display: flex; align-items: center; justify-content: space-between; }
.portal-card .p-icon { display: flex; align-items: center; justify-content: center; height: 1.9rem; width: 1.9rem; background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .32); color: hsl(var(--primary)); }
.portal-card .go { color: hsl(var(--muted-foreground)); opacity: 0; transition: opacity .15s; }
.portal-card:hover .go { opacity: 1; }
.portal-card .p-title { font-size: .82rem; font-weight: 700; }
.portal-card .p-desc { margin-top: .1rem; font-size: 11px; line-height: 1.75; color: hsl(var(--muted-foreground)); }

.gap-card { border: 1px solid; padding: .8rem .9rem; }
.gap-card .g-label { font-size: .7rem; font-weight: 600; opacity: .9; }
.gap-card .g-value { margin-top: .2rem; font-size: 1.35rem; font-weight: 700; }
.gap-card .g-hint { margin-top: .1rem; font-size: 10px; opacity: .75; }
.gap-card.primary { border-color: #0369a155; background: #0369a10f; color: #0369a1; }
.gap-card.success { border-color: #0f7a5a55; background: #0f7a5a0f; color: #0f7a5a; }
.gap-card.warning { border-color: #b4530955; background: #b453090f; color: #b45309; }
.gap-card.danger { border-color: #d61f4755; background: #d61f470f; color: #be123c; }
html.dark .gap-card.primary { color: hsl(199 85% 72%); border-color: hsl(199 85% 72% / .4); }
html.dark .gap-card.success { color: hsl(160 60% 64%); border-color: hsl(160 60% 64% / .4); }
html.dark .gap-card.warning { color: hsl(38 90% 68%); border-color: hsl(38 90% 68% / .4); }
html.dark .gap-card.danger { color: hsl(352 85% 72%); border-color: hsl(352 85% 72% / .4); }

.delta-pill { display: inline-flex; align-items: center; gap: .2rem; padding: .1rem .45rem; font-size: .7rem; font-weight: 700; border: 1px solid; }
.delta-pill svg { width: 12px; height: 12px; }
.delta-pill.up { background: #0f7a5a14; color: #0f7a5a; border-color: #0f7a5a44; }
.delta-pill.flat { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); border-color: hsl(var(--border)); }
.delta-pill.down { background: #d61f4714; color: #be123c; border-color: #d61f4744; }
.delta-pill.extreme { outline: 1px solid #f59e0b; outline-offset: 1px; }
html.dark .delta-pill.up { color: hsl(160 60% 64%); border-color: hsl(160 60% 64% / .4); }
html.dark .delta-pill.down { color: hsl(352 85% 72%); border-color: hsl(352 85% 72% / .4); }
{{-- نصّ الرقاقة بلون الخلفية: فاتح على الرقاقة الداكنة، وداكن على الفاتحة. --}}
.score-chip { display: inline-block; padding: .1rem .5rem; font-size: .7rem; font-weight: 700; color: hsl(var(--background)); }

/* البطاقة عمود مرن، فبلا هذا يتمدّد الشريط على عرضها كلّه. */
/* تبويبات أعرض من الشاشة (إعدادات المالك على الجوال) تُمرَّر أفقيًا بدل أن تفيض عن الصفحة. */
.seg { display: inline-flex; align-self: flex-start; max-width: 100%; overflow-x: auto; scrollbar-width: none; border: 1px solid var(--hair); background: var(--surface); }
.seg a { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; white-space: nowrap; padding: .4rem .8rem; font-size: .76rem; font-weight: 600; color: hsl(var(--muted-foreground)); border-left: 1px solid hsl(var(--border)); }
.seg a:last-child { border-left: 0; }
.seg a svg { width: 13px; height: 13px; }
.seg a.is-active { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.seg a:not(.is-active):hover { background: hsl(var(--muted)); color: hsl(var(--foreground)); }

.report-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--hair); background: var(--surface); transition: border-color .15s; }
.report-card:hover { border-color: hsl(var(--primary) / .6); }
.report-card .accent { height: 1px; width: 100%; background: hsl(var(--primary)); }
.report-card .accent.info { background: #0ea5e9; }
.report-card .accent.success { background: #0f7a5a; }
.report-card .accent.warning { background: #d97706; }
.report-card .accent.danger { background: #d61f47; }
.report-card .body { display: flex; flex: 1; flex-direction: column; justify-content: center; min-height: 5.5rem; padding: 1.25rem 1.35rem; }
.report-card .lead { display: flex; align-items: center; gap: .85rem; }
.report-card .kpi-icon { height: 2rem; width: 2rem; }
.report-card .kpi-icon svg { width: 16px; height: 16px; }
.report-card h3 { font-size: .98rem; font-weight: 700; line-height: 1.45; }
.report-card .desc { margin-top: .1rem; font-size: 11px; line-height: 1.75; color: hsl(var(--muted-foreground)); }
.report-card .actions { display: flex; gap: .3rem; margin-top: auto; padding-top: .7rem; }
.report-card .actions > * { flex: 1; justify-content: center; padding: .4rem .45rem; font-size: 11px; }

/*
 * تقارير المالك بنسق hispa: مجموعات المركز (رأس ملوّن بأيقونته فوق قائمة
 * روابط)، والمبلغ بعملته، ولون الربح والخسارة، وعمود الأرقام في طرف الجدول،
 * وسطر المجموع، وقائمة ملخص الشهر.
 */
.report-groups { display: grid; gap: var(--gap); grid-template-columns: 1fr; align-items: start; }
@media (min-width: 768px) { .report-groups { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1280px) { .report-groups { grid-template-columns: repeat(3, 1fr); } }
.report-group { padding: 0; }
.report-group > header { display: flex; align-items: center; gap: .6rem; padding: .75rem 1rem; font-size: .88rem; font-weight: 700; border-bottom: 1px solid var(--hair); background: hsl(var(--primary) / .07); }
.report-group > header.success { background: hsl(160 62% 35% / .08); }
.report-group > header.info { background: hsl(199 89% 40% / .08); }
.report-group > header.warning { background: hsl(35 92% 45% / .1); }
.report-group > header.danger { background: hsl(352 80% 50% / .07); }
.report-group > header.muted { background: hsl(var(--muted) / .7); }
.report-group a { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .7rem 1rem; font-size: .84rem; color: inherit; }
.report-group a + a { border-top: 1px solid hsl(var(--border) / .6); }
.report-group a svg { width: 15px; height: 15px; color: hsl(var(--muted-foreground)); }
.report-group a:hover { background: hsl(var(--primary) / .05); color: hsl(var(--primary)); }
.report-group a:hover svg { color: hsl(var(--primary)); }
.kpi-icon.muted { background: hsl(var(--muted)); border-color: hsl(var(--border)); color: hsl(var(--muted-foreground)); }
.money { white-space: nowrap; }
.money small { margin-inline-start: .15rem; font-size: .74em; font-weight: 600; color: hsl(var(--muted-foreground)); }
.tx-good { color: var(--st-good); }
.tx-bad { color: var(--st-critical); }
.data-table .end { text-align: end; }
.data-table tfoot td { font-weight: 700; background: hsl(var(--muted) / .55); border-top: 1px solid hsl(var(--border)); }
.data-table td.empty { padding: 2rem; text-align: center; color: hsl(var(--muted-foreground)); }
/* جدول كثير الأعمدة (تقرير الرحلات والمبيعات): خط أصغر وخانات لا تنكسر، ويُمرَّر أفقيًا. */
.data-table.compact th, .data-table.compact td { padding: .45rem .55rem; font-size: .74rem; white-space: nowrap; }
.report-note { display: block; margin-top: .6rem; font-size: .74rem; color: hsl(var(--muted-foreground)); }
.report-prompt { border: 1px solid hsl(199 89% 40% / .35); background: hsl(199 89% 40% / .08); color: hsl(199 89% 32%); padding: .75rem 1rem; font-size: .82rem; }
html.dark .report-prompt { color: hsl(199 85% 70%); }
.ms-statement { width: 100%; border-collapse: collapse; font-size: .84rem; margin-bottom: 1.1rem; }
.ms-statement th, .ms-statement td { padding: .55rem 1rem; }
.ms-statement thead th { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); text-align: start; font-weight: 700; }
.ms-statement.distribution thead th { background: hsl(var(--muted)); color: hsl(var(--foreground)); }
.ms-statement .section td { background: hsl(var(--muted) / .6); font-weight: 700; border-block: 1px solid hsl(var(--border)); }
.ms-statement .line td { border-bottom: 1px solid hsl(var(--border) / .5); color: hsl(var(--muted-foreground)); }
.ms-statement .indent { padding-inline-start: 2.25rem; }
.ms-statement .amount { text-align: end; white-space: nowrap; font-variant-numeric: tabular-nums; }
.ms-statement .subtotal td { font-weight: 700; color: hsl(var(--foreground)); border-top: 1px solid hsl(var(--border)); border-bottom: 2px solid hsl(var(--border)); background: hsl(var(--muted) / .35); }
.ms-statement .subtotal.light td { background: transparent; border-bottom-width: 1px; font-weight: 600; }
.ms-statement .total td { font-weight: 800; font-size: 1rem; padding-block: .8rem; }
.ms-statement .faint { opacity: .6; }

.chat { display: flex; flex-direction: column; gap: 1.1rem; border: 1px solid var(--hair); background: var(--surface); padding: 1.1rem; }
.chat-row { display: flex; gap: .65rem; }
.chat-row.me { flex-direction: row-reverse; }
.chat-avatar { display: flex; height: 2rem; width: 2rem; flex-shrink: 0; align-items: center; justify-content: center; background: hsl(var(--primary) / .12); border: 1px solid hsl(var(--primary) / .4); color: hsl(var(--primary)); }
.chat-avatar.me { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.chat-bubble { max-width: 82%; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); padding: .55rem .9rem; font-size: .82rem; line-height: 1.85; }
.chat-bubble.bot { max-width: none; flex: 1; background: hsl(var(--muted)); color: hsl(var(--foreground)); }
.insight { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .65rem; }
.insight-head { border: 1px solid hsl(var(--primary) / .4); background: hsl(var(--primary) / .07); padding: 1.1rem; }
.insight-head .kicker { font-size: 10px; font-weight: 700; letter-spacing: .14em; color: hsl(var(--primary)); }
.insight-head h3 { margin-top: .2rem; font-size: 1rem; font-weight: 700; }
.insight-head .answer { margin-top: .65rem; font-size: .82rem; font-weight: 500; line-height: 1.95; }
.insight-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .65rem; }
.insight-meta span { display: inline-flex; align-items: center; gap: .25rem; background: var(--surface); border: 1px solid var(--hair); padding: .2rem .55rem; font-size: 10px; color: hsl(var(--muted-foreground)); }
.insight-meta svg { width: 12px; height: 12px; }
.note-list { border: 1px solid; padding: .9rem 1rem; }
.note-list h4 { font-size: .82rem; font-weight: 700; }
.note-list li { margin-top: .4rem; margin-right: 1rem; font-size: .78rem; line-height: 1.85; }
.note-list.drivers { border-color: #b4530955; background: #b4530910; }
.note-list.actions { border-color: #0f7a5a55; background: #0f7a5a10; }
.suggestions { display: flex; flex-wrap: wrap; gap: .4rem; }
.suggestions a { border: 1px solid var(--hair); background: var(--surface); padding: .35rem .7rem; font-size: .76rem; font-weight: 500; }
.suggestions a:hover { border-color: hsl(var(--primary) / .6); background: hsl(var(--primary) / .07); }
.ask-bar { display: flex; gap: .4rem; }
.ask-bar .input { flex: 1; }

/* قسم الإدارة الفرعية — الهيكل والتقويم والإنذارات والتنبيهات والإعدادات */
.level-amber { background: #b4530914; color: #b45309; border: 1px solid #b4530944; }
.level-emerald { background: #0f7a5a14; color: #0f7a5a; border: 1px solid #0f7a5a44; }
.level-sky { background: #0369a114; color: #0369a1; border: 1px solid #0369a144; }
.level-violet { background: #6d28d914; color: #6d28d9; border: 1px solid #6d28d944; }
.level-cyan { background: #0e749014; color: #0e7490; border: 1px solid #0e749044; }
.level-slate { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); border: 1px solid hsl(var(--border)); }
html.dark .level-amber { color: hsl(38 90% 68%); border-color: hsl(38 90% 68% / .4); }
html.dark .level-emerald { color: hsl(160 60% 64%); border-color: hsl(160 60% 64% / .4); }
html.dark .level-sky { color: hsl(199 85% 72%); border-color: hsl(199 85% 72% / .4); }
html.dark .level-violet { color: hsl(262 85% 78%); border-color: hsl(262 85% 78% / .4); }
html.dark .level-cyan { color: hsl(188 80% 66%); border-color: hsl(188 80% 66% / .4); }

.org-tree { display: flex; flex-direction: column; gap: .5rem; }
.org-node { border: 1px solid var(--hair); background: var(--surface); border-right-width: 3px; }
.org-node.level-amber { border-right-color: #f59e0b; }
.org-node.level-emerald { border-right-color: #10b981; }
.org-node.level-sky { border-right-color: #38bdf8; }
.org-node.level-violet { border-right-color: #a78bfa; }
.org-node.level-cyan { border-right-color: #22d3ee; }
.org-node.level-slate { border-right-color: hsl(var(--border)); }
.org-node-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; padding: .85rem .95rem; }
.org-staff { border-top: 1px solid hsl(var(--border) / .7); padding: .45rem .95rem .7rem; display: flex; flex-direction: column; gap: .3rem; }
.org-staff-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .4rem; background: hsl(var(--muted) / .5); padding: .45rem .65rem; }

.cal-nav { display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--hair); background: var(--surface); padding: .45rem .9rem; margin-bottom: .85rem; }
.cal-nav h2 { font-size: .95rem; font-weight: 700; }
.cal { overflow: hidden; border: 1px solid var(--hair); background: var(--surface); }
.cal-head { display: grid; grid-template-columns: repeat(7, 1fr); background: hsl(var(--muted) / .8); border-bottom: 1px solid hsl(var(--border)); }
.cal-head span { padding: .45rem .25rem; text-align: center; font-size: .7rem; font-weight: 700; color: hsl(var(--muted-foreground)); }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell { min-height: 5.5rem; border-bottom: 1px solid hsl(var(--border) / .7); border-left: 1px solid hsl(var(--border) / .7); padding: .3rem; color: inherit; display: block; }
.cal-cell.is-blank { background: hsl(var(--muted) / .3); }
.cal-cell:not(.is-blank):hover { background: hsl(var(--primary) / .06); }
.cal-cell.is-today { background: hsl(var(--primary) / .08); }
.cal-cell.is-selected { box-shadow: inset 0 0 0 2px hsl(var(--primary)); }
.cal-day { display: flex; align-items: center; justify-content: space-between; margin-bottom: .2rem; }
.cal-day b { display: inline-flex; height: 1.25rem; min-width: 1.25rem; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: hsl(var(--muted-foreground)); }
.cal-cell.is-today .cal-day b { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.cal-day span { font-size: 10px; color: hsl(var(--muted-foreground)); }
.cal-task { display: flex; align-items: center; gap: .25rem; padding: .1rem .3rem; margin-bottom: 1px; font-size: 10px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-task i { height: 5px; width: 5px; flex-shrink: 0; }
.dot-عادية { background: #38bdf8; }
.dot-مهمة { background: #f59e0b; }
.dot-عاجلة { background: #f43f5e; }
.task-مجدولة { background: #0369a114; color: #0369a1; border: 1px solid #0369a133; }
.task-قيد { background: #b4530914; color: #b45309; border: 1px solid #b4530933; }
.task-مكتملة { background: #0f7a5a14; color: #0f7a5a; border: 1px solid #0f7a5a33; }
.task-متأخرة { background: #d61f4714; color: #be123c; border: 1px solid #d61f4733; }
.task-ملغاة { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); border: 1px solid hsl(var(--border)); }
html.dark .task-مجدولة { color: hsl(199 85% 72%); }
html.dark .task-قيد { color: hsl(38 90% 68%); }
html.dark .task-مكتملة { color: hsl(160 60% 64%); }
html.dark .task-متأخرة { color: hsl(352 85% 72%); }

.alert-group { overflow: hidden; border: 1px solid var(--hair); background: var(--surface); margin-bottom: .85rem; }
.alert-group > summary { display: flex; align-items: center; justify-content: space-between; gap: .7rem; padding: .65rem .9rem; cursor: pointer; border-right: 3px solid; list-style: none; }
.alert-group > summary::-webkit-details-marker { display: none; }
.alert-group .g-title { font-size: .82rem; font-weight: 700; }
.alert-group .g-meta { font-size: .7rem; color: hsl(var(--muted-foreground)); }
.alert-group .body { background: hsl(var(--muted) / .3); padding: .65rem; display: flex; flex-direction: column; gap: .5rem; }
.alert-row { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .7rem; border: 1px solid var(--hair); border-right-width: 3px; background: var(--surface); padding: .8rem; }
.alert-row .a-icon { display: flex; height: 2rem; width: 2rem; flex-shrink: 0; align-items: center; justify-content: center; border: 1px solid; }
.sev-حرج { border-right-color: #f43f5e; }
.sev-مرتفع { border-right-color: #fb923c; }
.sev-متوسط { border-right-color: #f59e0b; }
.sev-منخفض { border-right-color: #38bdf8; }
.sev-icon-حرج { background: #d61f4714; border-color: #d61f4744; color: #d61f47; }
.sev-icon-مرتفع { background: #ea580c14; border-color: #ea580c44; color: #c2410c; }
.sev-icon-متوسط { background: #b4530914; border-color: #b4530944; color: #b45309; }
.sev-icon-منخفض { background: #0369a114; border-color: #0369a144; color: #0369a1; }
html.dark .sev-icon-حرج { color: hsl(352 85% 72%); }
html.dark .sev-icon-مرتفع { color: hsl(24 90% 68%); }
html.dark .sev-icon-متوسط { color: hsl(38 90% 68%); }
html.dark .sev-icon-منخفض { color: hsl(199 85% 72%); }
.alert-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin-top: .45rem; font-size: .7rem; color: hsl(var(--muted-foreground)); }

.notif-card { display: flex; align-items: flex-start; gap: .7rem; border: 1px solid var(--hair); background: var(--surface); padding: .9rem; margin-bottom: .5rem; }
.notif-card.is-unread { border-color: hsl(var(--primary) / .5); }
.notif-card .n-icon { display: flex; height: 2rem; width: 2rem; flex-shrink: 0; align-items: center; justify-content: center; border: 1px solid; }
.notif-card .n-body { margin-top: .3rem; font-size: .76rem; line-height: 1.9; color: hsl(var(--muted-foreground)); white-space: pre-line; }
.type-طلب { background: #0369a114; border-color: #0369a144; color: #0369a1; }
.type-اعتماد { background: #6d28d914; border-color: #6d28d944; color: #6d28d9; }
.type-تذكير { background: #b4530914; border-color: #b4530944; color: #b45309; }
.type-أخرى { background: hsl(var(--muted)); border-color: hsl(var(--border)); color: hsl(var(--muted-foreground)); }
html.dark .type-طلب { color: hsl(199 85% 72%); }
html.dark .type-اعتماد { color: hsl(262 85% 78%); }
html.dark .type-تذكير { color: hsl(38 90% 68%); }

.settings-grid { display: grid; gap: var(--gap); grid-template-columns: 1fr; }
@media (min-width: 900px) { .settings-grid { grid-template-columns: repeat(2, 1fr); } }
.settings-panel { border: 1px solid var(--hair); background: var(--surface); padding: 1.1rem; }
.settings-panel .p-head { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; }
.settings-panel .p-head svg { width: 17px; height: 17px; color: hsl(var(--primary)); }
.settings-panel .p-head h3 { font-size: .82rem; font-weight: 700; }
.set-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid hsl(var(--border) / .7); padding-bottom: .45rem; margin-bottom: .45rem; font-size: .8rem; }
.set-row:last-child { border-bottom: 0; padding-bottom: 0; margin-bottom: 0; }
.set-row .s-label { color: hsl(var(--muted-foreground)); }
.set-row .s-value { font-weight: 600; text-align: left; }
.set-row .s-value.ok { color: #0f7a5a; }
html.dark .set-row .s-value.ok { color: hsl(160 60% 64%); }
.switch { position: relative; display: inline-block; height: 1.35rem; width: 2.5rem; flex-shrink: 0; }
.switch input { position: absolute; opacity: 0; height: 100%; width: 100%; margin: 0; cursor: pointer; }
.switch span { position: absolute; inset: 0; background: hsl(var(--muted)); border: 1px solid hsl(var(--border)); transition: background .2s; pointer-events: none; }
.switch span::after { content: ''; position: absolute; top: .15rem; right: .15rem; height: .95rem; width: .95rem; background: hsl(var(--muted-foreground)); transition: transform .2s, background .2s; }
.switch input:checked + span { background: hsl(var(--primary) / .2); border-color: hsl(var(--primary)); }
.switch input:checked + span::after { transform: translateX(-1.1rem); background: hsl(var(--primary)); }

/* لوحة الحكومة — شاشة الاختيار بمربّعاتها، ووضع العرض على شاشة القاعة */
.screen-launcher { display: flex; flex-direction: column; gap: clamp(1rem, 2vh, 1.75rem); }
.screen-launcher-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.screen-launcher-head .kicker { font-size: .7rem; font-weight: 700; letter-spacing: .14em; color: hsl(var(--primary)); }
.screen-launcher-head h1 { margin-top: .2rem; font-size: clamp(1.35rem, 2.2vw, 2.25rem); font-weight: 700; }

.screen-grid { display: grid; gap: clamp(.75rem, 1.2vw, 1.25rem); grid-template-columns: 1fr; }
@media (min-width: 720px) { .screen-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1280px) {
    .screen-grid { grid-template-columns: repeat(3, 1fr); }
    /* عدد المربّعات لا يقسم على ثلاثة: يتمدّد الأول عمودين فلا يبقى صفٌّ ناقصًا. */
    .screen-grid > :first-child:nth-last-child(3n + 2) { grid-column: span 2; }
}
.screen-tile {
    display: flex; flex-direction: column; gap: .7rem;
    min-height: clamp(9rem, 21vh, 16rem);
    border: 1px solid hsl(var(--border));
    background: var(--surface); padding: clamp(1rem, 1.6vw, 1.75rem); color: inherit;
    transition: border-color .18s ease, background .18s ease;
}
.screen-tile:hover, .screen-tile:focus-visible {
    outline: none;
    border-color: hsl(var(--primary) / .7);
    background: hsl(var(--primary) / .06);
}
.screen-tile .t-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.screen-tile .t-icon {
    display: flex; align-items: center; justify-content: center;
    height: clamp(2.75rem, 3.4vw, 4.25rem); width: clamp(2.75rem, 3.4vw, 4.25rem);
    background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .4); color: hsl(var(--primary));
}
.screen-tile .t-icon svg { width: 50%; height: 50%; }
.screen-tile .t-group { font-size: .68rem; font-weight: 700; letter-spacing: .1em; color: hsl(var(--muted-foreground)); }
.screen-tile h2 { font-size: clamp(1.05rem, 1.4vw, 1.6rem); font-weight: 700; }
.screen-tile p { margin-top: .3rem; font-size: clamp(.75rem, .8vw, 1rem); line-height: 1.85; color: hsl(var(--muted-foreground)); }
.screen-tile .t-go { display: flex; align-items: center; gap: .3rem; margin-top: auto; font-size: .78rem; font-weight: 700; color: hsl(var(--primary)); }
.screen-tile .t-go svg { width: 15px; height: 15px; }

/* قياس الجذر يكبر مع الشاشة، وبقية اللوحة مبنية على rem فتكبر معه. */
html.screen-mode { font-size: clamp(16px, 1vw, 22px); }
html.screen-mode .shell { padding-top: 0; }
html.screen-mode .main { margin-right: 0; }
{{-- شاشة القاعة تُملأ عن آخرها: لا قصّ للعمود ولا هامش أيسر. --}}
html.screen-mode .content { width: auto; margin-inline-end: 0; padding: clamp(1rem, 2vw, 2.5rem); }
html.screen-mode .chart-wrap { min-height: clamp(260px, 32vh, 520px); }
html.screen-mode .screen-launcher { min-height: calc(100dvh - 2 * clamp(1rem, 2vw, 2.5rem)); justify-content: center; }
{{-- على شاشة القاعة تملأ المربّعات الارتفاع بدل أن تتكوّم في أعلاها. --}}
html.screen-mode .screen-grid { flex: 1; grid-auto-rows: 1fr; }

.screen-bar {
    position: fixed; top: .75rem; left: .75rem; z-index: 60;
    display: flex; align-items: center;
    border: 1px solid var(--hair);
    background: hsl(var(--background) / .9); backdrop-filter: blur(10px);
    padding: .2rem; opacity: .45; transition: opacity .2s;
}
.screen-bar:hover, .screen-bar:focus-within { opacity: 1; }
.screen-bar a, .screen-bar button {
    display: inline-flex; align-items: center; gap: .3rem;
    border: 0; background: none; cursor: pointer; color: hsl(var(--foreground));
    padding: .4rem .65rem;
    font-family: inherit; font-size: .78rem; font-weight: 600;
}
.screen-bar a:hover, .screen-bar button:hover { background: hsl(var(--muted)); }
.screen-bar svg { width: 17px; height: 17px; }
.fs-btn .fs-off, .fs-btn.is-full .fs-on { display: none; }
.fs-btn.is-full .fs-off { display: inline-flex; }

@media print {
    .sidebar, .topbar, .screen-bar, .page-header .actions { display: none !important; }
    .shell { padding-top: 0; }
    .main { margin-right: 0 !important; }
    .content { width: auto; }
    body { background-image: none; }
    .card, .stat-card, .kpi-card { break-inside: avoid; }
}
</style>
