@extends('layouts.app')

@section('title', 'المناطق')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'map'])</div>
            <div>
                <h1>المناطق</h1>
                <p>إدارة المناطق الإدارية الساحلية وبياناتها الإجمالية</p>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="openRegionForm()">@include('partials.icon', ['name' => 'plus']) إضافة منطقة</button>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash-error">{{ session('error') }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'المناطق', 'value' => $stats['total'], 'icon' => 'map', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الموانئ', 'value' => $stats['ports'], 'icon' => 'anchor', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القوارب النشطة', 'value' => number_format($stats['boats']), 'icon' => 'ship', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الصيادون', 'value' => number_format($stats['fishers']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'إجمالي المصيد', 'value' => number_format($stats['catch']), 'unit' => 'طن', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'طول الساحل', 'value' => number_format($stats['coast']), 'unit' => 'كم', 'icon' => 'waves', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="text" name="search" value="{{ request('search') }}" placeholder="اسم المنطقة أو الرمز..."></label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('regions') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cards-grid cols-3">
        @forelse ($regions as $region)
            <div class="entity-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:.625rem">
                        <div style="height:2.25rem;width:2.25rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;background:hsl(var(--primary)/.1);color:hsl(var(--primary))">@include('partials.icon', ['name' => 'map'])</div>
                        <div>
                            <h3 style="font-weight:700">{{ $region->name }}</h3>
                            <p style="display:flex;align-items:center;gap:.25rem;font-size:.72rem;color:hsl(var(--muted-foreground))">@include('partials.icon', ['name' => 'hash']) {{ $region->code ?? 'بدون رمز' }}</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:.25rem">
                        <button type="button" class="icon-action" title="تعديل"
                            onclick='openRegionForm({!! json_encode($region->only(['id', 'name', 'code', 'coast_length_km', 'governorates_count', 'ports_count', 'total_catch_tons', 'active_boats', 'active_fishers']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                            @include('partials.icon', ['name' => 'pencil'])
                        </button>
                        <form method="POST" action="{{ route('regions.destroy', $region) }}" onsubmit="return confirm('حذف منطقة «{{ $region->name }}» مع محافظاتها وموانئها ومواقع الصيد والأسواق فيها؟ لا يمكن التراجع.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action danger" title="حذف">@include('partials.icon', ['name' => 'trash'])</button>
                        </form>
                    </div>
                </div>
                <div class="mini-grid">
                    <div class="mini">@include('partials.icon', ['name' => 'waves'])<div><p class="m-label">طول الساحل</p><p class="m-value">{{ number_format($region->coast_length_km) }} كم</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'building'])<div><p class="m-label">المحافظات</p><p class="m-value">{{ $region->governorates_count }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'anchor'])<div><p class="m-label">موانئ</p><p class="m-value">{{ $region->ports_count }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'ship'])<div><p class="m-label">قوارب نشطة</p><p class="m-value">{{ number_format($region->active_boats) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'users'])<div><p class="m-label">صيادون</p><p class="m-value">{{ number_format($region->active_fishers) }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'scale'])<div><p class="m-label">مصيد (طن)</p><p class="m-value">{{ number_format($region->total_catch_tons) }}</p></div></div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;padding:2.5rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد مناطق مطابقة</div>
        @endforelse
    </div>

    <div class="drawer-overlay" id="regionDrawer-overlay" onclick="toggleDrawer('regionDrawer', false)"></div>
    <div class="drawer" id="regionDrawer">
        <div class="drawer-head">
            <h3 id="regionFormTitle">إضافة منطقة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('regionDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="regionForm" action="{{ route('regions.store') }}" class="drawer-body">
            @csrf
            <input type="hidden" name="_method" id="regionMethod" value="POST">
            <label class="field"><span>اسم المنطقة *</span><input class="input" name="name" id="f-name" required placeholder="منطقة تبوك"></label>
            <label class="field"><span>رمز المنطقة</span><input class="input" name="code" id="f-code" placeholder="TBU"></label>
            <div class="form-grid">
                <label class="field"><span>طول الساحل (كم)</span><input class="input" type="number" name="coast_length_km" id="f-coast"></label>
                <label class="field"><span>عدد المحافظات</span><input class="input" type="number" name="governorates_count" id="f-govs"></label>
                <label class="field"><span>عدد الموانئ</span><input class="input" type="number" name="ports_count" id="f-ports"></label>
                <label class="field"><span>إجمالي المصيد (طن)</span><input class="input" type="number" name="total_catch_tons" id="f-catch"></label>
                <label class="field"><span>القوارب النشطة</span><input class="input" type="number" name="active_boats" id="f-boats"></label>
                <label class="field"><span>الصيادون النشطون</span><input class="input" type="number" name="active_fishers" id="f-fishers"></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('regionDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const storeUrl = @json(route('regions.store'));

    function openRegionForm(region = null) {
        const form = document.getElementById('regionForm');
        document.getElementById('regionFormTitle').textContent = region ? 'تعديل المنطقة' : 'إضافة منطقة';
        document.getElementById('regionMethod').value = region ? 'PUT' : 'POST';
        form.action = region ? storeUrl + '/' + region.id : storeUrl;
        document.getElementById('f-name').value = region?.name ?? '';
        document.getElementById('f-code').value = region?.code ?? '';
        document.getElementById('f-coast').value = region?.coast_length_km ?? '';
        document.getElementById('f-govs').value = region?.governorates_count ?? '';
        document.getElementById('f-ports').value = region?.ports_count ?? '';
        document.getElementById('f-catch').value = region?.total_catch_tons ?? '';
        document.getElementById('f-boats').value = region?.active_boats ?? '';
        document.getElementById('f-fishers').value = region?.active_fishers ?? '';
        toggleDrawer('regionDrawer', true);
    }
</script>
@endpush