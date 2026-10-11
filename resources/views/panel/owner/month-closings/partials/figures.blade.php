{{--
    أرقام الشهر — مشتركة بين المعاينة والشهر المُغلق ($data من
    MonthClosingService::preview / present): بطاقات، جدول القوارب، نتيجة
    المالك، ومستحقات الطاقم لكل قارب من سطور مسيره.
--}}
@php
    $t = $data['totals'];
    $g = $data['general'];
    $pct = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
@endphp

<div class="stat-grid cols-6" style="margin-bottom:1.25rem">
    @include('partials.stat-card', ['label' => 'إيراد المصيد', 'value' => number_format($t['revenue'], 2), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'primary'])
    @include('partials.stat-card', ['label' => 'المصروفات', 'value' => number_format($t['expenses'] + $g['expenses'], 2), 'unit' => 'ر.س', 'icon' => 'receipt', 'tone' => 'warning'])
    @include('partials.stat-card', ['label' => 'الإهلاك المحمَّل', 'value' => number_format($t['depreciation_charged'] + $g['depreciation'], 2), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'warning'])
    @include('partials.stat-card', ['label' => 'نصيب الطاقم', 'value' => number_format($t['crew_pool'], 2), 'unit' => 'ر.س', 'icon' => 'users', 'tone' => 'success'])
    @include('partials.stat-card', ['label' => 'صافي المالك', 'value' => number_format($t['owner_net'], 2), 'unit' => 'ر.س', 'icon' => 'scale', 'tone' => $t['owner_net'] < 0 ? 'danger' : 'success'])
    @include('partials.stat-card', ['label' => 'إهلاك مؤجَّل للشهر التالي', 'value' => number_format($t['depreciation_deferred'], 2), 'unit' => 'ر.س', 'icon' => 'clock', 'tone' => $t['depreciation_deferred'] > 0 ? 'warning' : 'primary'])
</div>

@include('partials.section-head', ['icon' => 'ship', 'title' => 'القوارب'])
<div class="table-card" style="margin-bottom:1.25rem">
    <table class="data-table">
        <thead>
            <tr><th>القارب</th><th>الإيراد</th><th>المصروفات</th><th>الإهلاك</th><th>المحمَّل</th><th>المؤجَّل</th><th>صافي الربح</th><th>نصيب المالك</th><th>نصيب الطاقم</th></tr>
        </thead>
        <tbody>
            @forelse ($data['boats'] as $boat)
                <tr>
                    <td style="font-weight:600">{{ $boat['boat_name'] }}</td>
                    <td class="num">{{ number_format($boat['revenue'], 2) }}</td>
                    <td class="num">
                        {{ number_format($boat['expenses'], 2) }}
                        @if ($boat['pending_fixed'] > 0)<div style="font-size:.7rem;color:hsl(var(--muted-foreground))">منها رواتب ثابتة تُرحَّل عند الإغلاق <span class="num">{{ number_format($boat['pending_fixed'], 2) }}</span></div>@endif
                    </td>
                    <td class="num">
                        {{ number_format($boat['depreciation_own'], 2) }}
                        @if ($boat['depreciation_brought_forward'] > 0)<div style="font-size:.7rem;color:hsl(var(--muted-foreground))">+ مؤجَّل من الشهر السابق <span class="num">{{ number_format($boat['depreciation_brought_forward'], 2) }}</span></div>@endif
                    </td>
                    <td class="num">{{ number_format($boat['depreciation_charged'], 2) }}</td>
                    <td class="num" @if ($boat['depreciation_deferred'] > 0) style="color:var(--st-warn);font-weight:700" @endif>{{ number_format($boat['depreciation_deferred'], 2) }}</td>
                    <td class="num" @if ($boat['net_profit'] < 0) style="color:var(--st-critical)" @endif>{{ number_format($boat['net_profit'], 2) }}</td>
                    <td class="num">{{ number_format($boat['owner_share'], 2) }} <span style="font-size:.7rem;color:hsl(var(--muted-foreground))">({{ $pct($boat['owner_share_percent']) }}%)</span></td>
                    <td class="num" style="font-weight:700">{{ number_format($boat['crew_pool'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا نشاط لأي قارب في هذا الشهر</td></tr>
            @endforelse
        </tbody>
        @if ($data['boats'] !== [])
            <tfoot>
                <tr style="font-weight:700">
                    <td>الإجمالي</td>
                    <td class="num">{{ number_format($t['revenue'], 2) }}</td>
                    <td class="num">{{ number_format($t['expenses'], 2) }}</td>
                    <td class="num">{{ number_format($t['depreciation'], 2) }}</td>
                    <td class="num">{{ number_format($t['depreciation_charged'], 2) }}</td>
                    <td class="num">{{ number_format($t['depreciation_deferred'], 2) }}</td>
                    <td class="num">{{ number_format($t['net_profit'], 2) }}</td>
                    <td class="num">{{ number_format($t['owner_share'], 2) }}</td>
                    <td class="num">{{ number_format($t['crew_pool'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<div class="card" style="margin-bottom:1.25rem;font-size:.82rem;line-height:2">
    @include('partials.section-head', ['icon' => 'scale', 'title' => 'نتيجة المالك'])
    <div style="display:grid;grid-template-columns:1fr auto;gap:0 1.5rem;max-width:520px">
        <span>نصيب المالك من القوارب</span><span class="num">{{ number_format($t['owner_share'], 2) }}</span>
        <span>− مصروفات عامة بلا قارب</span><span class="num">{{ number_format($g['expenses'], 2) }}</span>
        <span>− إهلاك أصول غير مربوطة بقارب @if ($g['assets'] !== [])<span style="font-size:.72rem;color:hsl(var(--muted-foreground))">({{ collect($g['assets'])->pluck('name')->join('، ') }})</span>@endif</span><span class="num">{{ number_format($g['depreciation'], 2) }}</span>
        <b style="border-top:1px solid hsl(var(--border));padding-top:.25rem">صافي المالك</b><b class="num" style="border-top:1px solid hsl(var(--border));padding-top:.25rem;{{ $t['owner_net'] < 0 ? 'color:var(--st-critical)' : '' }}">{{ number_format($t['owner_net'], 2) }}</b>
    </div>
    <p style="margin:.5rem 0 0;font-size:.75rem;color:hsl(var(--muted-foreground))">ما لا قارب له يُنقص نصيب المالك وحده — لا يمسّ نصيب الطاقم. إهلاك القارب لا يُحمَّل منه إلا ما يغطيه ربحه، والباقي يؤجَّل إلى الشهر التالي.</p>
</div>

@include('partials.section-head', ['icon' => 'users', 'title' => 'مستحقات الطاقم'])
@foreach ($data['boats'] as $boat)
    @continue($boat['dues'] === [])
    <div class="table-card" style="margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .9rem;font-size:.82rem">
            <b>{{ $boat['boat_name'] }}</b>
            @if ($boat['payroll'])
                <a href="{{ route('panel.owner.payrolls.show', $boat['payroll']->id) }}" class="num">{{ $boat['payroll']->payroll_number }}</a>
            @else
                <span class="badge badge-info">يُنشأ مسيره عند الإغلاق</span>
            @endif
        </div>
        <table class="data-table">
            <thead><tr><th>الفرد</th><th>الأجر</th><th>المستحق</th><th>سلف مخصومة</th><th>الصافي</th><th>السداد</th></tr></thead>
            <tbody>
                @foreach ($boat['dues'] as $due)
                    <tr>
                        <td>{{ $due['name'] }}@if ($due['is_captain']) <span class="badge badge-info" style="margin-inline-start:.25rem">كابتن</span>@endif</td>
                        <td>{{ $due['pay'] }}</td>
                        <td class="num">{{ number_format($due['gross'], 2) }}</td>
                        <td class="num">{{ $due['advances'] === null ? '—' : number_format($due['advances'], 2) }}</td>
                        <td class="num" style="font-weight:700">{{ $due['net'] === null ? '—' : number_format($due['net'], 2) }}</td>
                        <td>
                            @if ($due['paid'] === null)
                                <span style="color:hsl(var(--muted-foreground))">—</span>
                            @elseif ($due['paid'])
                                <span class="badge badge-ok">مسدَّد</span>
                            @else
                                <span class="badge badge-warn">غير مسدَّد</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
@if (collect($data['boats'])->every(fn ($b) => $b['dues'] === []))
    <p style="font-size:.8rem;color:hsl(var(--muted-foreground))">لا أفراد بإعداد أجر على قوارب الشهر.</p>
@endif
