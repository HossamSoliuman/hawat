<div class="section-head">
    <div>
        <h2>الاستيراد الجماعي</h2>
    </div>
</div>

<div class="notice">
    @include('partials.icon', ['name' => 'upload'])
    <p>يُقبل الاستيراد للبيانات الأساسية فقط. الصف الأول يجب أن يحتوي أسماء الأعمدة كما هي موضحة في الجدول أدناه.</p>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>الجدول</th>
                <th>السجلات الحالية</th>
                <th>الأعمدة المطلوبة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['targets'] as $target)
                <tr>
                    <td>{{ $target['label'] }}</td>
                    <td>{{ number_format($target['count']) }}</td>
                    <td style="direction:ltr;text-align:left;font-size:12px;">{{ $target['columns'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>