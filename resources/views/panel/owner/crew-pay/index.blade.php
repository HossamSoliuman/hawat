@extends('layouts.app')

@section('title', 'أجور الطاقم')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-cog'])</div>
            <div>
                <h1>أجور الطاقم</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.payrolls') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'calculator']) مسيرات الرواتب</a>
            <a href="{{ route('panel.owner.advances') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) السلف</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-bottom:1.25rem;font-size:.8rem;line-height:1.9">
        @include('partials.section-head', ['icon' => 'calculator', 'title' => 'كيف يُحسب المسير'])
        <p style="margin:0">صافي ربح القارب = صافي المالك من مبيعات مصيده (مباشرةً ومن الدلال) − مصروفات القارب (ومنها الرواتب الثابتة) − إهلاك أصوله.</p>
        <p style="margin:0">يأخذ المالك نسبته من الصافي والباقي <b>نصيب الطاقم</b>: صاحب النسبة الخاصة يأخذ نسبته منه أولًا، ثم يُقسم الباقي على بقية أصحاب النسبة بأسهمهم.</p>
        <p style="margin:0">تُخصم السلف من المستحق، ولا يدخل المسير إلا أفراد القارب النشطون ذوو إعداد أجر.</p>
    </div>

    @foreach ($boats->sortBy(fn ($b) => [! $byBoat->has($b->id), $b->name])->concat([null]) as $boat)
        @php $members = $byBoat->get($boat?->id ?? 0, collect()); @endphp
        @continue($boat === null && $members->isEmpty())
        <div class="table-card" style="margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.75rem 1rem;border-bottom:1px solid hsl(var(--border))">
                <div>
                    <b>{{ $boat?->name ?? 'بلا قارب' }}</b>
                    @if ($boat)
                        @php
                            $shareMembers = $members->where('pay_type_id', $shareTypeId);
                            $customTotal = $shareMembers->sum('custom_share_percent');
                        @endphp
                        <span style="font-size:.75rem;color:hsl(var(--muted-foreground));margin-inline-start:.5rem">
                            نسبة المالك <b class="num">{{ rtrim(rtrim(number_format($boat->owner_share_percent, 2), '0'), '.') }}%</b>
                            · للطاقم <b class="num">{{ rtrim(rtrim(number_format(100 - $boat->owner_share_percent, 2), '0'), '.') }}%</b>
                            @if ($customTotal > 0) · نسب خاصة <b class="num">{{ rtrim(rtrim(number_format($customTotal, 2), '0'), '.') }}%</b>@endif
                        </span>
                    @else
                        <span style="font-size:.75rem;color:hsl(var(--muted-foreground));margin-inline-start:.5rem">لا يدخلون أي مسير حتى يُسندوا إلى قارب</span>
                    @endif
                </div>
                @if ($boat)
                    <button type="button" class="btn btn-outline" onclick='openDrawerForm(shareForm, {!! json_encode(['id' => $boat->id, 'owner_share_percent' => (float) $boat->owner_share_percent, 'name' => $boat->name], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil']) نسبة المالك</button>
                @endif
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>الاسم</th><th>الحالة</th><th>نوع الأجر</th><th>الراتب / الحصة</th><th>سلف متبقية</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $fisher)
                        @php $balance = $balances[$fisher->id] ?? ['outstanding' => 0]; @endphp
                        <tr>
                            <td>
                                <span style="font-weight:600">{{ $fisher->name }}</span>
                                @if ($fisher->is_captain)<span class="badge badge-info" style="margin-inline-start:.25rem">كابتن</span>@endif
                                <div style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $fisher->fisherRole?->name ?? $fisher->role }}</div>
                            </td>
                            <td><span class="badge {{ $fisher->status === 'نشط' ? 'badge-ok' : 'badge-warn' }}">{{ $fisher->status }}</span></td>
                            <td>
                                @if ($fisher->payType)
                                    {{ $fisher->payType->name }}
                                @else
                                    <span class="badge badge-warn">لم يُضبط</span>
                                @endif
                            </td>
                            <td class="num">
                                @if (! $fisher->payType)
                                    —
                                @elseif ((int) $fisher->pay_type_id === (int) $shareTypeId)
                                    @if ($fisher->custom_share_percent > 0)
                                        نسبة خاصة {{ rtrim(rtrim(number_format($fisher->custom_share_percent, 2), '0'), '.') }}%
                                    @else
                                        {{ rtrim(rtrim(number_format($fisher->profit_shares, 2), '0'), '.') }} سهم
                                    @endif
                                @else
                                    {{ number_format($fisher->fixed_salary, 2) }} ر.س / شهر
                                @endif
                            </td>
                            <td class="num" @if ($balance['outstanding'] > 0) style="color:var(--st-warn);font-weight:700" @endif>{{ number_format($balance['outstanding'], 2) }}</td>
                            <td>
                                <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                    <a href="{{ route('panel.owner.crew-pay.statement', $fisher->id) }}" target="_blank" class="icon-action" title="كشف الحساب">@include('partials.icon', ['name' => 'printer'])</a>
                                    <button type="button" class="icon-action" title="إعداد الأجر" onclick='openDrawerForm(payForm, {!! json_encode($fisher->only(['id', 'name', 'pay_type_id', 'fixed_salary', 'profit_shares', 'custom_share_percent']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا كباتن ولا طاقم على هذا القارب</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- درج إعداد الأجر --}}
    <div class="drawer-overlay" id="payDrawer-overlay" onclick="toggleDrawer('payDrawer', false)"></div>
    <div class="drawer" id="payDrawer">
        <div class="drawer-head">
            <h3 id="payFormTitle">إعداد الأجر</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('payDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="payFormEl" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" value="">
            <div class="form-grid">
                <label class="field"><span>نوع الأجر *</span>
                    <select class="select" name="pay_type_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($payTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field" data-for="fixed"><span>الراتب الشهري (ر.س) *</span><input class="input" name="fixed_salary" type="number" step="0.01" min="0.01" dir="ltr"></label>
                <label class="field" data-for="share"><span>الأسهم</span><input class="input" name="profit_shares" type="number" step="0.25" min="0" dir="ltr" placeholder="1"></label>
                <label class="field" data-for="share"><span>أو نسبة خاصة من نصيب الطاقم %</span><input class="input" name="custom_share_percent" type="number" step="0.01" min="0" max="100" dir="ltr"></label>
                <p data-for="share" style="font-size:.75rem;color:hsl(var(--muted-foreground))">صاحب النسبة الخاصة يأخذها من نصيب الطاقم كله أولًا ولا تُحسب أسهمه. اتركها فارغة ليُقسم بالأسهم (سهم لكلٍّ = بالتساوي).</p>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('payDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>

    {{-- درج نسبة المالك --}}
    <div class="drawer-overlay" id="shareDrawer-overlay" onclick="toggleDrawer('shareDrawer', false)"></div>
    <div class="drawer" id="shareDrawer">
        <div class="drawer-head">
            <h3 id="shareFormTitle">نسبة المالك</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('shareDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="shareFormEl" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" value="">
            <label class="field"><span>نسبة المالك من صافي ربح القارب % *</span><input class="input" name="owner_share_percent" type="number" step="0.01" min="0" max="100" dir="ltr" required></label>
            <p style="font-size:.75rem;color:hsl(var(--muted-foreground));margin-top:.5rem">الباقي نصيب الطاقم. تُطبَّق على المسيرات التي لم يُسدَّد منها شيء.</p>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.75rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('shareDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const shareTypeId = @json($shareTypeId);
    const payFormEl = document.getElementById('payFormEl');

    function refreshPayForm() {
        const type = payFormEl.pay_type_id.value;
        const share = String(type) === String(shareTypeId);
        payFormEl.querySelectorAll('[data-for=share]').forEach((el) => { el.style.display = type && share ? '' : 'none'; });
        payFormEl.querySelectorAll('[data-for=fixed]').forEach((el) => { el.style.display = type && !share ? '' : 'none'; });
        payFormEl.fixed_salary.required = Boolean(type) && !share;
    }
    payFormEl.pay_type_id.addEventListener('change', refreshPayForm);

    const payForm = {
        drawer: 'payDrawer', form: 'payFormEl', title: 'payFormTitle',
        storeUrl: @json(route('panel.owner.crew-pay')), createTitle: 'إعداد الأجر', editTitle: 'إعداد الأجر',
        after(record) {
            document.getElementById('payFormTitle').textContent = 'أجر ' + (record ? record.name : '');
            refreshPayForm();
        },
    };

    const shareForm = {
        drawer: 'shareDrawer', form: 'shareFormEl', title: 'shareFormTitle',
        storeUrl: @json(\Illuminate\Support\Str::beforeLast(route('panel.owner.crew-pay.boat', 0), '/')), createTitle: 'نسبة المالك', editTitle: 'نسبة المالك',
        after(record) {
            document.getElementById('shareFormTitle').textContent = 'نسبة المالك — ' + (record ? record.name : '');
        },
    };

    @if ($errors->any() && old('pay_type_id') !== null)
        openDrawerForm(payForm, {!! json_encode(['id' => old('id') ?: null, 'name' => ''] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
