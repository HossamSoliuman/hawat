@extends('panel.owner.settings.layout')

@section('settings-actions')
    <button type="button" class="btn btn-primary" onclick="openDrawerForm(recordForm)">@include('partials.icon', ['name' => 'plus']) كابتن جديد</button>
@endsection

@section('settings')
    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال..."></label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.owner.captains') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>القارب</th><th>الميناء</th><th>آخر دخول</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($captains as $row)
                    @php
                        $invitation = $row->invitation_status;
                        $record = [
                            'id' => $row->id, 'name' => $row->name, 'phone' => $row->phone, 'email' => $row->email, 'active' => $row->active,
                            'national_id' => $row->fisher?->national_id, 'id_type_id' => $row->fisher?->id_type_id,
                            'iqama_issue_date' => $row->fisher?->iqama_issue_date, 'iqama_expiry_date' => $row->fisher?->iqama_expiry_date,
                            'nationality' => $row->fisher?->nationality, 'boat_id' => $row->fisher?->boat_id, 'port_id' => $row->fisher?->port_id,
                            'license_number' => $row->fisher?->license_number, 'license_date' => $row->fisher?->license_date,
                            'license_expiry' => $row->fisher?->license_expiry, 'experience_years' => $row->fisher?->experience_years,
                            'version' => $row->fisher?->version,
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="person-cell">
                                @include('partials.person-avatar', ['url' => $row->avatar_path ? asset('storage/'.$row->avatar_path) : null, 'name' => $row->name])
                                <div style="font-weight:600">{{ $row->name }}@if ($row->email)<div dir="ltr" style="font-size:.7rem;font-weight:400;color:hsl(var(--muted-foreground));text-align:right">{{ $row->email }}</div>@endif</div>
                            </div>
                        </td>
                        <td dir="ltr" class="num" style="text-align:right">{{ $row->phone }}</td>
                        <td class="num">{{ $row->fisher?->national_id ?? '—' }}</td>
                        <td>{{ $row->fisher?->boat?->name ?? '—' }}</td>
                        <td>{{ $row->fisher?->port?->name ?? '—' }}</td>
                        <td style="font-size:.74rem;color:hsl(var(--muted-foreground))">{{ $row->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}</td>
                        <td>
                            @if (! $row->active)
                                <span class="badge badge-danger">معطّل</span>
                            @elseif ($invitation === \App\Models\User::INVITATION_INVITED)
                                <span class="badge badge-info">مدعو</span>
                                <div style="font-size:.68rem;color:hsl(var(--muted-foreground));margin-top:.15rem">تنتهي {{ $row->invitation_expires_at->diffForHumans() }}</div>
                            @elseif ($invitation === \App\Models\User::INVITATION_EXPIRED)
                                <span class="badge badge-warn">انتهت الدعوة</span>
                            @else
                                <span class="badge badge-ok">مفعّل</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل" onclick='openDrawerForm(recordForm, {!! json_encode($record, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>@include('partials.icon', ['name' => 'pencil'])</button>
                                @if ($row->active && $row->isPendingInvitation())
                                    <form method="POST" action="{{ route('panel.owner.captains.invitation', $row) }}">
                                        @csrf
                                        <button type="submit" class="icon-action" title="إعادة إرسال الدعوة">@include('partials.icon', ['name' => 'send'])</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('panel.owner.captains.toggle', $row) }}">
                                    @csrf
                                    <button type="submit" class="icon-action" title="{{ $row->active ? 'تعطيل' : 'تفعيل' }}">@include('partials.icon', ['name' => $row->active ? 'ban' : 'check-circle'])</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا كباتن بعد — أضف كابتنًا ليدخل التطبيق بجواله</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $captains])

    <div class="drawer-overlay" id="recordDrawer-overlay" onclick="toggleDrawer('recordDrawer', false)"></div>
    <div class="drawer" id="recordDrawer">
        <div class="drawer-head">
            <h3 id="recordFormTitle">كابتن جديد</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('recordDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="recordFormEl" action="{{ route('panel.owner.captains.store') }}" class="drawer-body" autocomplete="off" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="version" value="" data-fill>
            <div class="form-grid cols-2">
                <label class="field"><span>الاسم *</span><input class="input" name="name" required></label>
                <label class="field"><span>رقم الجوال *</span><input class="input" name="phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX" required></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" name="email" type="email" dir="ltr"></label>
                <label class="field"><span id="passwordLabel">كلمة المرور</span><input class="input" name="password" type="password" dir="ltr" minlength="8" autocomplete="new-password"></label>
                <label class="field"><span>رقم الهوية *</span><input class="input" name="national_id" dir="ltr" required></label>
                <label class="field"><span>نوع الهوية</span>
                    <select class="select" name="id_type_id">
                        <option value="">—</option>
                        @foreach ($idTypes as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>بداية الإقامة</span><input class="input" name="iqama_issue_date" type="date" dir="ltr"></label>
                <label class="field"><span>انتهاء الإقامة</span><input class="input" name="iqama_expiry_date" type="date" dir="ltr"></label>
                <label class="field"><span>الجنسية</span><input class="input" name="nationality"></label>
                <label class="field"><span>سنوات الخبرة</span><input class="input" name="experience_years" type="number" min="0" dir="ltr"></label>
                <label class="field"><span>القارب</span>
                    <select class="select" name="boat_id">
                        <option value="">— بلا قارب —</option>
                        @foreach ($boats as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>الميناء (إن لم يُحدَّد قارب)</span>
                    <select class="select" name="port_id">
                        <option value="">— اختر —</option>
                        @foreach ($ports as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>رقم رخصة الصيد</span><input class="input" name="license_number" dir="ltr"></label>
                <label class="field"><span>بداية الرخصة</span><input class="input" name="license_date" type="date" dir="ltr"></label>
                <label class="field"><span>انتهاء الرخصة</span><input class="input" name="license_expiry" type="date" dir="ltr"></label>
                <label class="field"><span>الصورة الشخصية (JPG/PNG/WebP حتى 2MB)</span><input class="input" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"></label>
            </div>
            <p id="inviteHint" style="font-size:.74rem;color:hsl(var(--muted-foreground));margin:0">اترك كلمة المرور فارغة لتُرسل إلى جوال الكابتن دعوة برمز تفعيل يختار بها كلمته بنفسه.</p>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;cursor:pointer" id="removeAvatarRow">
                <input type="checkbox" name="remove_avatar" value="1">
                حذف الصورة الحالية
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;cursor:pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" checked>
                الحساب مفعّل
            </label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('recordDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('panel.partials.drawer-form')
<script>
    const recordForm = { drawer: 'recordDrawer', form: 'recordFormEl', title: 'recordFormTitle', storeUrl: @json(route('panel.owner.captains.store')), createTitle: 'كابتن جديد', editTitle: 'تعديل الكابتن', after: (record) => {
        document.getElementById('passwordLabel').textContent = record ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور';
        document.getElementById('inviteHint').hidden = Boolean(record);
        document.getElementById('removeAvatarRow').hidden = !record;
    } };
    @if ($errors->any() && old('name') !== null)
        openDrawerForm(recordForm, {!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
