@extends('layouts.app')

@section('title', 'الخريطة البحرية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'map'])</div>
            <div>
                <h1>الخريطة البحرية</h1>
            </div>
        </div>
    </div>

    <div class="card" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.72rem;color:hsl(var(--muted-foreground))">
        @include('partials.icon', ['name' => 'layers'])
        <span>خريطة حوات المحلية — فعّل ArcGIS من إدارة النظام لإظهار الطبقات المؤسسية</span>
    </div>

    <div class="grid-3" style="grid-template-columns:1fr">
        <div style="display:grid;gap:1rem;grid-template-columns:1fr">
            <div style="display:grid;gap:1rem" class="map-layout">
                <div class="card" style="padding:0;overflow:hidden">
                    <div id="seaMap" style="height:70vh;min-height:480px"></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <div class="card">
                        <p class="card-title" style="margin-bottom:.75rem">مفتاح الخريطة</p>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#0284c7">@include('partials.icon', ['name' => 'anchor'])</span>الموانئ</span><strong>{{ $ports->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#10b981">@include('partials.icon', ['name' => 'map-pin'])</span>مواقع طبيعية</span><strong>{{ $sites->where('pressure_level', 'طبيعي')->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#f59e0b">@include('partials.icon', ['name' => 'map-pin'])</span>مواقع مراقبة</span><strong>{{ $sites->where('pressure_level', 'مراقبة')->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#f97316">@include('partials.icon', ['name' => 'alert-triangle'])</span>ضغط مرتفع</span><strong>{{ $sites->where('pressure_level', 'ضغط مرتفع')->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#f43f5e">@include('partials.icon', ['name' => 'alert-triangle'])</span>إنذار</span><strong>{{ $sites->where('pressure_level', 'إنذار')->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#16a34a">@include('partials.icon', ['name' => 'ship'])</span>قوارب في البحر</span><strong>{{ $atSeaBoats->count() }}</strong></div>
                        <div class="legend-row"><span style="display:flex;align-items:center"><span class="l-icon" style="background:#f43f5e">@include('partials.icon', ['name' => 'ban'])</span>مناطق الحظر</span><strong>1</strong></div>
                    </div>
                    <div class="card" id="selectedPanel" style="display:none">
                        <p class="card-title" style="margin-bottom:.5rem">تفاصيل المحدد</p>
                        <div id="selectedBody" style="font-size:.82rem;line-height:2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (min-width: 1024px) { .map-layout { grid-template-columns: 3fr 1fr; } }
        .leaflet-container { font-family: 'Tajawal', sans-serif; }
    </style>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const ports = {!! json_encode($ports->map(fn ($p) => ['name' => $p->name, 'lat' => (float) $p->lat, 'lng' => (float) $p->lng, 'region' => $p->governorate?->region?->name, 'governorate' => $p->governorate?->name, 'boats' => $p->boats_count, 'fishers' => $p->fishers_count, 'catch' => (float) $p->total_catch_tons, 'trips' => $p->daily_trips]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    const sites = {!! json_encode($sites->map(fn ($s) => ['name' => $s->name, 'lat' => (float) $s->lat, 'lng' => (float) $s->lng, 'level' => $s->pressure_level, 'port' => $s->port?->name, 'catch' => (float) $s->catch_kg]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    const boats = {!! json_encode($atSeaBoats->map(fn ($b) => ['name' => $b->name, 'number' => $b->boat_number, 'captain' => $b->captain, 'port' => $b->port?->name, 'lat' => (float) $b->port?->lat, 'lng' => (float) $b->port?->lng]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

    const map = L.map('seaMap').setView([22.5, 39.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

    const siteColor = l => l === 'إنذار' ? '#ef4444' : l === 'ضغط مرتفع' ? '#f97316' : l === 'مراقبة' ? '#f59e0b' : '#10b981';
    const panel = document.getElementById('selectedPanel');
    const body = document.getElementById('selectedBody');
    const show = html => { body.innerHTML = html; panel.style.display = 'block'; };

    const portIcon = L.divIcon({ html: '<div style="background:#0284c7;color:#fff;width:28px;height:28px;border-radius:8px 8px 8px 0;transform:rotate(45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.3);border:2px solid #fff"><span style="transform:rotate(-45deg);font-size:14px">⚓</span></div>', iconSize: [28, 28], iconAnchor: [0, 28] });
    const boatIcon = L.divIcon({ html: '<div style="background:#16a34a;color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.3);border:2px solid #fff;font-size:10px">⛵</div>', iconSize: [22, 22], iconAnchor: [11, 11] });

    ports.forEach(p => {
        if (!p.lat || !p.lng) return;
        L.marker([p.lat, p.lng], { icon: portIcon })
            .addTo(map)
            .bindPopup(`<b>${p.name}</b><br><small>${p.region} — ${p.governorate}</small>`)
            .on('click', () => show(`<b>${p.name}</b><br><span style="color:hsl(var(--muted-foreground))">${p.region}</span><br>القوارب: ${p.boats} | الصيادون: ${p.fishers}<br>المصيد: ${p.catch} طن<br>رحلات اليوم: ${p.trips}`));
    });

    sites.forEach(s => {
        if (!s.lat || !s.lng) return;
        L.circleMarker([s.lat, s.lng], { radius: 10, color: '#fff', weight: 2, fillColor: siteColor(s.level), fillOpacity: 1 })
            .addTo(map)
            .bindPopup(`<b>${s.name}</b><br><small>ضغط: ${s.level}</small>`)
            .on('click', () => show(`<b>${s.name}</b><br>مستوى الضغط: ${s.level}<br><span style="color:hsl(var(--muted-foreground))">أقرب ميناء: ${s.port}</span><br>المصيد: ${s.catch.toLocaleString()} كجم`));
    });

    boats.forEach((b, i) => {
        if (!b.lat || !b.lng) return;
        const lat = b.lat + (((i * 37) % 10) - 5) / 10;
        const lng = b.lng + (((i * 53) % 10) - 5) / 10;
        L.marker([lat, lng], { icon: boatIcon })
            .addTo(map)
            .bindPopup(`<b>${b.name}</b><br><small>${b.number}</small>`)
            .on('click', () => show(`<b>${b.name}</b><br><span style="font-family:monospace;font-size:.75rem">${b.number}</span><br>الكابتن: ${b.captain}<br>الميناء: ${b.port}`));
    });

    L.polygon([[22.5, 38.0], [22.5, 38.8], [21.8, 38.8], [21.8, 38.0]], { color: '#ef4444', fillColor: '#ef4444', fillOpacity: .15, dashArray: '6 6' }).addTo(map);
    L.circleMarker([22.15, 38.4], { color: '#f97316', fillColor: '#f97316', fillOpacity: .15, radius: 30 }).addTo(map);
</script>
@endpush