@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', '⚙️ Settings')
@section('page-subtitle', 'Scanner configuration & parameters')

@section('content')

<form method="POST" action="{{ route('settings.update') }}">
    @csrf

    @php
    $groups = [
        'filter'   => ['icon' => '🔍', 'title' => 'Filter Parameters',   'desc' => 'Stage 3: Kriteria filter awal koin'],
        'analysis' => ['icon' => '📊', 'title' => 'Analysis Parameters',  'desc' => 'Stage 4: Parameter analisa teknikal'],
        'signal'   => ['icon' => '⚡', 'title' => 'Signal Parameters',    'desc' => 'Stage 5-6: Risk & position setup'],
        'telegram' => ['icon' => '📱', 'title' => 'Telegram Config',      'desc' => 'Stage 7: Notifikasi Bot Telegram'],
        'general'  => ['icon' => '⚙️', 'title' => 'General Settings',     'desc' => 'Konfigurasi umum scanner'],
    ];
    @endphp

    @foreach($groups as $groupKey => $groupMeta)
    @if(isset($settings[$groupKey]))
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">{{ $groupMeta['icon'] }} {{ $groupMeta['title'] }}</div>
            <span class="text-sm text-muted">{{ $groupMeta['desc'] }}</span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
                @foreach($settings[$groupKey] as $setting)
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="setting_{{ $setting->id }}">
                        {{ $setting->label ?? $setting->key }}
                    </label>
                    @php $fieldName = str_replace('.', '__', $setting->key); @endphp
                    @if($setting->type === 'boolean')
                        <select name="{{ $fieldName }}" id="setting_{{ $setting->id }}" class="form-select">
                            <option value="true"  {{ $setting->value === 'true'  ? 'selected' : '' }}>✅ Enabled</option>
                            <option value="false" {{ $setting->value !== 'true'  ? 'selected' : '' }}>❌ Disabled</option>
                        </select>
                    @elseif($setting->type === 'number')
                        <input type="number" step="any" name="{{ $fieldName }}" id="setting_{{ $setting->id }}"
                               class="form-input" value="{{ $setting->value }}">
                    @else
                        <input type="{{ in_array('token', [$setting->key]) ? 'password' : 'text' }}"
                               name="{{ $fieldName }}" id="setting_{{ $setting->id }}"
                               class="form-input" value="{{ $setting->value }}"
                               placeholder="{{ $setting->value ? '' : 'Not set' }}">
                    @endif
                    @if($setting->description)
                        <div class="form-description">{{ $setting->description }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endforeach

    <div style="display:flex;gap:12px;align-items:center">
        <button type="submit" class="btn btn-primary btn-lg">💾 Save All Settings</button>
        <button type="button" class="btn btn-ghost" id="testTelegramBtn" onclick="testTelegram()">
            📱 Test Telegram
        </button>
        <span id="telegramTestResult" style="font-size:13px;color:var(--text-muted)"></span>
    </div>
</form>

@push('scripts')
<script>
function testTelegram() {
    const btn = document.getElementById('testTelegramBtn');
    const result = document.getElementById('telegramTestResult');
    btn.disabled = true;
    result.textContent = '⏳ Testing...';
    result.style.color = 'var(--text-muted)';

    fetch('{{ route("settings.test-telegram") }}')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                result.textContent = `✅ Connected! Bot: @${data.bot_name}`;
                result.style.color = 'var(--green)';
            } else {
                result.textContent = `❌ Failed: ${data.message}`;
                result.style.color = 'var(--red)';
            }
        })
        .catch(() => {
            result.textContent = '❌ Network error';
            result.style.color = 'var(--red)';
        })
        .finally(() => btn.disabled = false);
}
</script>
@endpush
@endsection
