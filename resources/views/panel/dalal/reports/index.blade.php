@extends('layouts.app')

@section('title', 'التقارير')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
            <div>
                <h1>التقارير</h1>
            </div>
        </div>
    </div>

    <form method="GET" id="reportForm" target="_blank" action="{{ route('panel.dalal.reports.show', 'sales') }}">
        <div class="cards-grid" style="display:grid;gap:var(--gap);grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));margin-bottom:1.25rem">
            @foreach ($types as $key => $type)
                <label class="report-card" style="cursor:pointer">
                    <span class="accent {{ ['sales' => 'success', 'stock' => 'info', 'payouts' => 'warning', 'financial' => 'danger'][$key] }}"></span>
                    <div class="body">
                        <div class="lead">
                            <div class="kpi-icon {{ ['sales' => 'success', 'stock' => 'info', 'payouts' => 'warning', 'financial' => 'danger'][$key] }}">@include('partials.icon', ['name' => $type['icon']])</div>
                            <div style="min-width:0;flex:1">
                                <h3>{{ $type['title'] }}</h3>
                                <p class="desc">{{ $type['description'] }}</p>
                            </div>
                            <input type="radio" name="report_type" value="{{ $key }}" data-url="{{ route('panel.dalal.reports.show', $key) }}" @checked($loop->first) onchange="document.getElementById('reportForm').action = this.dataset.url">
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="card" style="display:flex;flex-direction:column;gap:1rem">
            @include('partials.section-head', ['icon' => 'search', 'title' => 'خيارات التصفية'])
            <div class="form-grid cols-3">
                <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from"></label>
                <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to"></label>
                <label class="field"><span>الحالة</span>
                    <select class="select" name="status">
                        <option value="">كل الحالات</option>
                        @foreach ($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>حالة الدفع</span>
                    <select class="select" name="payment_status_id">
                        <option value="">كل حالات الدفع</option>
                        @foreach ($paymentStatuses as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>نوع السمك</span>
                    <select class="select" name="species_id">
                        <option value="">كل الأنواع</option>
                        @foreach ($species as $s)<option value="{{ $s->id }}">{{ $s->name_ar }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>المالك</span>
                    <select class="select" name="owner_id">
                        <option value="">كل الملاك</option>
                        @foreach ($owners as $o)<option value="{{ $o['id'] }}">{{ $o['name'] }}</option>@endforeach
                    </select>
                </label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <button type="reset" class="btn btn-outline">إعادة تعيين</button>
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) إنشاء التقرير</button>
            </div>
            <p class="card-sub" style="margin:0">اختر نوع التقرير من البطاقات أعلاه، ثم حدّد نطاق التاريخ والفلاتر، وانقر «إنشاء التقرير» لفتحه في نافذة جديدة جاهزة للطباعة. الفلاتر التي لا تخصّ التقرير المختار تُتجاهل.</p>
        </div>
    </form>
@endsection
