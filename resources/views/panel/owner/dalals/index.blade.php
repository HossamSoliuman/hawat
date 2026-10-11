@extends('layouts.app')

@section('title', 'الدلالون')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'handshake'])</div>
            <div>
                <h1>الدلالون</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.owner.consignments') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'send']) الإرسال للدلال</a>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="اسم الدلال..."></label>
        <button class="btn btn-primary">بحث</button>
        <a href="{{ route('panel.owner.dalals') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>الدلال</th><th>الجوال</th><th>الدكة / الميناء</th><th>الاتفاق</th><th>أرسلت</th><th>بيع منه</th><th>صافيك</th><th>استلمت</th><th>المستحق لك</th><th></th></tr></thead>
            <tbody>
                @forelse ($dalals as $dalal)
                    @php
                        $partnership = $partnerships[$dalal->id] ?? null;
                        $account = $accounts[$dalal->id];
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ $dalal->name }}</td>
                        <td class="num" dir="ltr" style="text-align:right">{{ $dalal->phone }}</td>
                        <td style="font-size:.76rem">{{ $dalal->dalalProfile?->dakka_name ?? '—' }}{{ $dalal->dalalProfile?->port ? ' / '.$dalal->dalalProfile->port->name : '' }}</td>
                        <td>
                            @if ($partnership)
                                <span class="badge {{ ['pending' => 'badge-warn', 'accepted' => 'badge-ok', 'rejected' => 'badge-danger'][$partnership->status] ?? '' }}">{{ $partnership->status_label }}</span>
                                <span class="num" style="font-size:.72rem">{{ (float) $partnership->commission_pct }}% + {{ (float) $partnership->wage_pct }}%</span>
                                @if ($partnership->response_note)<br><span style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $partnership->response_note }}</span>@endif
                            @else
                                <span style="font-size:.74rem;color:hsl(var(--muted-foreground))">لا طلب</span>
                            @endif
                        </td>
                        <td class="num">{{ number_format($account['sent_kg'], 1) }} كجم</td>
                        <td class="num">{{ number_format($account['sold_kg'], 1) }} كجم</td>
                        <td class="num">{{ number_format($account['net'], 2) }}</td>
                        <td class="num">{{ number_format($account['paid'], 2) }}</td>
                        <td class="num" style="font-weight:700">{{ number_format($account['net'] - $account['paid'], 2) }}</td>
                        <td>
                            @if ($partnership?->status !== \App\Models\DalalPartnership::ACCEPTED)
                                <button type="button" class="btn btn-outline" style="padding:.3rem .7rem" onclick='openRequest({!! json_encode(['id' => $dalal->id, 'name' => $dalal->name, 'commission_pct' => $partnership ? (float) $partnership->commission_pct : null, 'wage_pct' => $partnership ? (float) $partnership->wage_pct : null, 'message' => $partnership?->message], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                    {{ $partnership?->isPending() ? 'تعديل الطلب' : 'اقتراح عمولة' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا دلالين مفعّلين بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="requestDrawer-overlay" onclick="toggleDrawer('requestDrawer', false)"></div>
    <div class="drawer" id="requestDrawer">
        <div class="drawer-head">
            <h3 id="requestTitle">اقتراح عمولة لدلال</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('requestDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="requestForm" action="" class="drawer-body" autocomplete="off">
            @csrf
            <div class="form-grid cols-2">
                <label class="field"><span>العمولة المقترحة % *</span><input class="input num" type="number" step="0.01" min="0" max="100" name="commission_pct" dir="ltr" required></label>
                <label class="field"><span>أجور العمالة %</span><input class="input num" type="number" step="0.01" min="0" max="100" name="wage_pct" dir="ltr"></label>
                <label class="field wide"><span>رسالة للدلال</span><textarea class="input" name="message" rows="3"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('requestDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'send']) إرسال الطلب</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const requestUrl = @json(route('panel.owner.dalals.partnership', ['dalal' => '__ID__']));

    function openRequest(dalal) {
        const form = document.getElementById('requestForm');
        form.action = requestUrl.replace('__ID__', dalal.id);
        form.commission_pct.value = dalal.commission_pct ?? '';
        form.wage_pct.value = dalal.wage_pct ?? '';
        form.message.value = dalal.message ?? '';
        document.getElementById('requestTitle').textContent = 'اقتراح عمولة لـ ' + dalal.name;
        toggleDrawer('requestDrawer', true);
    }
</script>
@endpush
