@php
    $settings = $setting->settings ?? [];
@endphp

<div class="section-head">
    <div>
        <h2>{{ $config['title'] }}</h2>
    </div>
    <span class="badge {{ $setting->enabled ? 'badge-ok' : 'badge-muted' }}">{{ $setting->enabled ? 'مفعّل' : 'معطل' }}</span>
</div>

@if (! empty($config['safety_title']))
    <div class="note-box" style="margin-top:0">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <div class="n-title">{{ $config['safety_title'] }}</div>
            <p class="n-body">{{ $config['safety_text'] }}</p>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('admin.integration.update', ['tab' => $activeTab, 'provider' => $provider]) }}">
    @csrf
    @method('PUT')

    <div class="field field-check" style="margin-bottom:1rem;">
        <input type="hidden" name="enabled" value="0">
        <input type="checkbox" id="integration-enabled" name="enabled" value="1" @checked($setting->enabled)>
        <label for="integration-enabled">تفعيل التكامل</label>
    </div>

    <div class="form-grid">
        @foreach ($config['fields'] as $field)
            @php
                $key = $field['key'];
                $type = $field['type'] ?? 'text';
                $value = old("settings.{$key}", $settings[$key] ?? null);
            @endphp

            @if ($type === 'boolean')
                <div class="field field-check">
                    <input type="hidden" name="settings[{{ $key }}]" value="0">
                    <input type="checkbox" id="setting-{{ $key }}" name="settings[{{ $key }}]" value="1" @checked($value)>
                    <label for="setting-{{ $key }}">{{ $field['label'] }}</label>
                </div>
            @elseif ($type === 'select')
                <div class="field">
                    <label for="setting-{{ $key }}">{{ $field['label'] }}</label>
                    <select class="select" id="setting-{{ $key }}" name="settings[{{ $key }}]">
                        <option value="">— اختر —</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="field">
                    <label for="setting-{{ $key }}">{{ $field['label'] }}</label>
                    <input class="input" type="{{ $type === 'number' ? 'number' : 'text' }}" step="any"
                           id="setting-{{ $key }}" name="settings[{{ $key }}]" value="{{ $value }}">
                </div>
            @endif
        @endforeach

        <div class="field field-wide">
            <label for="integration-notes">ملاحظات</label>
            <textarea class="input" id="integration-notes" name="notes">{{ old('notes', $setting->notes) }}</textarea>
        </div>
    </div>

    <div class="form-actions" style="margin-top:1.1rem;">
        <button type="submit" class="btn btn-primary">
            @include('partials.icon', ['name' => 'save'])
            حفظ الإعدادات
        </button>
    </div>
</form>