<div class="section-head">
    <div>
        <h2>إحصاءات الإنتاج</h2>
    </div>
</div>

<div class="stat-grid">
    @foreach ($data['cards'] as $card)
        <div class="stat-card">
            <div class="label">{{ $card['label'] }}</div>
            <div class="value">{{ number_format((float) $card['value'], fmod((float) $card['value'], 1) === 0.0 ? 0 : 2) }}
                <span class="unit">{{ $card['unit'] }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>المنطقة</th>
                <th>عدد الموانئ</th>
                <th>المصيد (طن)</th>
                <th>القوارب النشطة</th>
                <th>الصيادون النشطون</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['regions'] as $region)
                <tr>
                    <td>{{ $region->name }}</td>
                    <td>{{ number_format($region->ports_count) }}</td>
                    <td>{{ number_format($region->total_catch_tons, 2) }}</td>
                    <td>{{ number_format($region->active_boats) }}</td>
                    <td>{{ number_format($region->active_fishers) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">لا توجد بيانات مناطق.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>الميناء</th>
                <th>المنطقة</th>
                <th>القوارب</th>
                <th>الرحلات الشهرية</th>
                <th>المصيد (طن)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['ports'] as $port)
                <tr>
                    <td>{{ $port->name }}</td>
                    <td>{{ $port->governorate?->region?->name ?? '—' }}</td>
                    <td>{{ number_format($port->boats_count) }}</td>
                    <td>{{ number_format($port->monthly_trips) }}</td>
                    <td>{{ number_format($port->total_catch_tons, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">لا توجد بيانات موانئ.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>