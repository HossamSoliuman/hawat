<div class="section-head">
    <div>
        <h2>مخطط Power BI</h2>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>الجدول</th>
                <th>النوع</th>
                <th>المصدر</th>
                <th>المفاتيح</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['model'] as $row)
                <tr>
                    <td style="direction:ltr;text-align:left;">{{ $row['table'] }}</td>
                    <td>
                        <span class="badge {{ $row['type'] === 'Fact' ? 'badge-ok' : 'badge-muted' }}">{{ $row['type'] }}</span>
                    </td>
                    <td style="direction:ltr;text-align:left;">{{ $row['source'] }}</td>
                    <td style="direction:ltr;text-align:left;font-size:12px;">{{ $row['keys'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>المقياس</th>
                <th>المعادلة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['measures'] as $measure)
                <tr>
                    <td style="direction:ltr;text-align:left;">{{ $measure['name'] }}</td>
                    <td style="direction:ltr;text-align:left;font-size:12px;">{{ $measure['expression'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>