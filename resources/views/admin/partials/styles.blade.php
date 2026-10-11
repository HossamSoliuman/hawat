<style>
/*
 * إدارة النظام — ما تنفرد به صفحات بوابة المعلومات داخل تخطيط اللوحة.
 *
 * نظام التصميم كلّه في partials/styles.blade.php: المتغيّرات والوضعان،
 * والبطاقة والجدول والحقل والزرّ وأقواس الزوايا. هنا الرئيسة وشريط الجداول
 * وشريط الأدوات والنموذج المنبثق فحسب.
 */

/* سطر فوق عنوان الصفحة: القسم ثم موضع الصفحة منه. */
.eyebrow { display: flex; align-items: center; gap: .35rem; margin-bottom: .2rem; font-size: .68rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.eyebrow a:hover { color: hsl(var(--primary)); }
.eyebrow svg { width: 11px; height: 11px; opacity: .6; }

/* ── الرئيسة ─────────────────────────────────────────────── */
.hub-section { margin-top: 1.6rem; }
.hub-title { display: flex; align-items: center; gap: .6rem; margin-bottom: .7rem; font-size: .74rem; font-weight: 700; letter-spacing: .02em; color: hsl(var(--muted-foreground)); }
.hub-title::after { content: ''; flex: 1; height: 1px; background: var(--hair); }
.hub-title .n { font-family: 'Chakra Petch', sans-serif; font-weight: 600; opacity: .7; }
.hub-grid { display: grid; gap: .6rem; grid-template-columns: 1fr; }
@media (min-width: 640px) { .hub-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1100px) { .hub-grid { grid-template-columns: repeat(3, 1fr); } }
.hub-card { position: relative; display: flex; align-items: center; gap: .8rem; border: 1px solid var(--hair); background: var(--surface); padding: .85rem .95rem; color: inherit; transition: border-color .15s, background .15s; }
.hub-card::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 2px; background: hsl(var(--primary)); transform: scaleY(0); transition: transform .18s; }
.hub-card:hover { border-color: hsl(var(--primary) / .55); background: hsl(var(--primary) / .04); }
.hub-card:hover::before { transform: scaleY(1); }
.hub-card .glyph { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; flex-shrink: 0; border: 1px solid hsl(var(--primary) / .35); background: hsl(var(--primary) / .08); color: hsl(var(--primary)); }
.hub-card .glyph svg { width: 17px; height: 17px; }
.hub-card .body { min-width: 0; flex: 1; }
.hub-card .name { font-size: .84rem; font-weight: 700; line-height: 1.45; }
.hub-card .en { margin-top: .05rem; font-family: 'Chakra Petch', sans-serif; font-size: .66rem; letter-spacing: .03em; color: hsl(var(--muted-foreground)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hub-card .meta { display: flex; flex-direction: column; align-items: flex-end; gap: .35rem; flex-shrink: 0; }
.hub-card > svg { width: 14px; height: 14px; color: hsl(var(--muted-foreground)); transition: transform .15s, color .15s; }
.hub-card:hover > svg { color: hsl(var(--primary)); transform: translateX(-3px); }

/* ── صفحة التبويب ────────────────────────────────────────── */
.panel { gap: 0; padding: 0; }
.panel-body { display: flex; flex-direction: column; gap: 1rem; padding: 1.1rem 1.15rem; }

/* جداول التبويب: شريط بخطّ سفلي، والنشط مسطّر باللون مع عدد سجلاته. */
.restabs { display: flex; gap: .15rem; overflow-x: auto; overflow-y: hidden; border-bottom: 1px solid var(--hair); padding-inline: .6rem; scrollbar-width: thin; }
.restab { display: inline-flex; align-items: center; gap: .45rem; padding: .75rem .7rem .65rem; border-bottom: 2px solid transparent; margin-bottom: -1px; font-size: .78rem; font-weight: 600; color: hsl(var(--muted-foreground)); white-space: nowrap; transition: color .15s, border-color .15s; }
.restab:hover { color: hsl(var(--foreground)); }
.restab.is-active { color: hsl(var(--primary)); border-bottom-color: hsl(var(--primary)); }
.restab .count { min-width: 1.4rem; padding: .05rem .35rem; border: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .6); font-size: .64rem; text-align: center; color: hsl(var(--muted-foreground)); }
.restab.is-active .count { border-color: hsl(var(--primary) / .45); background: hsl(var(--primary) / .1); color: hsl(var(--primary)); }

.toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .8rem; }
.toolbar h2 { font-size: .98rem; font-weight: 700; }
.toolbar .tools { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; }
.search { position: relative; display: flex; }
.search svg { position: absolute; inset-inline-start: .6rem; top: 50%; width: 14px; height: 14px; transform: translateY(-50%); color: hsl(var(--muted-foreground)); pointer-events: none; }
.search .input { width: 16rem; max-width: 60vw; padding-inline-start: 1.9rem; }
.search .clear { position: absolute; inset-inline-end: .35rem; top: 50%; transform: translateY(-50%); }

/* ترويسة الجدول ثابتة عند التمرير، والسطر يُبرز تحت المؤشّر. */
.panel .table-card { max-height: 68vh; overflow: auto; }
.panel .data-table thead th { position: sticky; top: 0; z-index: 1; }
.panel .data-table tbody tr:hover td { background: hsl(var(--primary) / .035); }
.panel .data-table td:first-child { font-weight: 600; }

.empty-state { display: flex; flex-direction: column; align-items: center; gap: .55rem; padding: 3rem 1.25rem; text-align: center; }
.empty-state .glyph { display: grid; place-items: center; width: 2.8rem; height: 2.8rem; border: 1px dashed hsl(var(--border)); color: hsl(var(--muted-foreground)); }
.empty-state .glyph svg { width: 18px; height: 18px; }
.empty-state h3 { font-size: .86rem; font-weight: 700; }
.empty-state p { max-width: 26rem; font-size: .74rem; line-height: 1.8; color: hsl(var(--muted-foreground)); }

/* خلايا الإجراءات: زرّان متجاوران في وسط الخانة، بلا لفّ سطر بينهما. */
.cell-actions { width: 1%; text-align: center; white-space: nowrap; }
.inline-form { display: inline; }
.cell-actions .icon-action { display: inline-grid; place-items: center; vertical-align: middle; }

.pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; font-size: .74rem; color: hsl(var(--muted-foreground)); }
.pager .range { font-family: 'Chakra Petch', 'Tajawal', sans-serif; }
.pager nav { display: flex; gap: .25rem; }
.pager nav a, .pager nav span { display: inline-flex; align-items: center; border: 1px solid hsl(var(--border)); padding: .3rem .75rem; }
.pager nav a:hover { border-color: hsl(var(--primary) / .6); color: hsl(var(--foreground)); }
.pager nav .is-disabled { opacity: .45; }

/* تنبيه البوابة: سطر كهرماني يقول ما لا يُحرَّر من هنا. */
.notice { display: flex; align-items: center; gap: .6rem; border: 1px solid hsl(38 90% 45% / .35); border-inline-start-width: 3px; background: hsl(38 90% 45% / .06); padding: .6rem .85rem; margin-bottom: 1rem; font-size: .74rem; line-height: 1.8; }
.notice svg { width: 16px; height: 16px; flex-shrink: 0; color: #b45309; }
html.dark .notice svg { color: hsl(38 90% 66%); }
.notice strong { font-weight: 700; }

/* ترويسة الأقسام في لوحات الأدوات والتكاملات: سطران وزرّ في الطرف المقابل. */
.section-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem; margin: 0; }
.section-head h2 { font-size: .95rem; font-weight: 700; letter-spacing: 0; white-space: normal; }

/* الحقل: التسمية عنصر <label>، والحقول نفسها تحمل .input و .select. */
.field label { font-size: .7rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.field .req { color: #d61f47; margin-inline-start: .15rem; }
html.dark .field .req { color: hsl(352 85% 72%); }
.field textarea.input { min-height: 5rem; resize: vertical; line-height: 1.8; }
.field-check { flex-direction: row; align-items: center; gap: .5rem; }
.field-check input[type=checkbox] { width: 1rem; height: 1rem; accent-color: hsl(var(--primary)); cursor: pointer; }
.field-check label { font-size: .78rem; font-weight: 600; color: hsl(var(--foreground)); cursor: pointer; }
.field-wide { grid-column: 1 / -1; }

.form-actions { display: flex; gap: .5rem; border-top: 1px solid var(--hair); padding-top: 1rem; margin-top: .25rem; }

/*
 * النموذج المنبثق: <dialog> أصليّ بسطحٍ مصمت فوق الصفحة، بزاوية قائمة وخطّ
 * شعري. رأسه وذيله ثابتان، والجسم وحده يتمرّر إن طالت الحقول.
 */
dialog.modal {
    border: 1px solid hsl(var(--border)); border-radius: 0; padding: 0;
    margin: auto; /* * { margin: 0 } في اللوحة يُبطل توسيط المتصفّح. */
    width: min(56rem, 94vw); max-width: 94vw; max-height: 90vh;
    background: hsl(var(--background)); color: hsl(var(--foreground));
    font-family: inherit;
    box-shadow: 0 28px 70px -20px rgba(0, 0, 0, .55);
}
dialog.modal[open] { display: flex; flex-direction: column; animation: modal-in .16s ease-out; }
dialog.modal > form { display: flex; flex-direction: column; min-height: 0; flex: 1; }
dialog.modal.sm { width: min(26rem, 94vw); }
dialog.modal::backdrop { background: rgba(2, 8, 20, .62); backdrop-filter: blur(2px); }
@keyframes modal-in { from { opacity: 0; transform: translateY(6px) scale(.985); } }
.modal-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--hair); padding: .9rem 1.2rem; }
.modal-head h3 { font-size: .98rem; font-weight: 700; }
.modal-head p { margin-top: .1rem; font-size: .7rem; color: hsl(var(--muted-foreground)); }
.modal-body { padding: 1.2rem; overflow-y: auto; }
.modal-body > p { font-size: .8rem; line-height: 1.9; }
.modal-foot { display: flex; gap: .5rem; border-top: 1px solid var(--hair); padding: .85rem 1.2rem; background: hsl(var(--muted) / .35); }
.modal .form-grid { grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: .8rem .9rem; }
.modal .field textarea.input { min-height: 4rem; }
.btn-danger { background: #d61f47; border-color: #d61f47; color: #fff; }
.btn-danger:hover { background: #be123c; }
</style>
