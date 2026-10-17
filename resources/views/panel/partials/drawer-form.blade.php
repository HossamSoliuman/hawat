{{--
    نموذج الدرج المشترك لصفحات القوائم: الزر نفسه يفتح "جديد" أو "تعديل"،
    والحقول تُملأ بأسمائها من السجل المرسل. عند فشل التحقق تُعيد الصفحة
    فتحه بما أُدخل (old) — انظر استعماله في نهاية كل صفحة.
--}}
<script>
    function fillDrawerForm(form, record) {
        for (const el of form.elements) {
            if (!el.name || el.name.startsWith('_')) continue;
            if (el.type === 'password' || el.type === 'file') { el.value = ''; continue; }
            if (el.type === 'checkbox') { el.checked = record ? Boolean(Number(record[el.name])) : el.defaultChecked; continue; }
            // المخفي يبقى كما هو (قيمة 0 لمربع اختيار) إلا المعرّف وما وُسم data-fill (نسخة السجل).
            if (el.type === 'hidden' && el.name !== 'id' && !('fill' in el.dataset)) continue;
            let value = record && record[el.name] != null ? String(record[el.name]) : '';
            // تواريخ Eloquent تصل بصيغة ISO كاملة، وحقول التاريخ تقبل الجزء الأول منها.
            if (el.type === 'date') value = value.slice(0, 10);
            if (el.type === 'datetime-local') value = value.replace(' ', 'T').slice(0, 16);
            el.value = value;
        }
    }

    /**
     * opts: { drawer, form, title, storeUrl, createTitle, editTitle, after? }
     */
    function openDrawerForm(opts, record = null) {
        const form = document.getElementById(opts.form);
        document.getElementById(opts.title).textContent = record ? opts.editTitle : opts.createTitle;
        form.querySelector('[name=_method]').value = record ? 'PUT' : 'POST';
        form.action = record ? opts.storeUrl + '/' + record.id : opts.storeUrl;
        fillDrawerForm(form, record);
        if (opts.after) opts.after(record, form);
        toggleDrawer(opts.drawer, true);
    }
</script>
