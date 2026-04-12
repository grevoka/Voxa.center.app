@extends('layouts.app')

@section('title', __('ui.settings'))
@section('page-title', __('ui.settings'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.settings") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.settings') }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="d-flex gap-2 mb-3" style="border-bottom:1px solid var(--border);padding-bottom:0.5rem;">
        <button class="settings-tab active" data-tab="sip" onclick="settingsTab('sip')">
            <i class="bi bi-telephone-fill me-1"></i>SIP & Securite
        </button>
        <button class="settings-tab" data-tab="smtp" onclick="settingsTab('smtp')">
            <i class="bi bi-envelope-fill me-1"></i>Email / SMTP
        </button>
        <button class="settings-tab" data-tab="ai" onclick="settingsTab('ai')">
            <i class="bi bi-robot me-1"></i>AI & TTS
        </button>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- TAB: SIP --}}
    {{-- ═══════════════════════════════════════ --}}
    <div id="tab-sip" class="settings-panel">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">{{ __("ui.sip_server") }}</h6>
                        <div class="mb-3">
                            <label class="form-label">Server address</label>
                            <input type="text" name="sip_server" class="form-control"
                                   value="{{ old('sip_server', \App\Models\SipSetting::get('sip_server', 'sip.local')) }}"
                                   placeholder="sip.example.com">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">SIP Port</label>
                                <input type="number" name="sip_port" class="form-control"
                                       value="{{ old('sip_port', \App\Models\SipSetting::get('sip_port', 5060)) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">TLS Port</label>
                                <input type="number" name="sip_tls_port" class="form-control"
                                       value="{{ old('sip_tls_port', \App\Models\SipSetting::get('sip_tls_port', 5061)) }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Transport</label>
                            <select name="sip_transport" class="form-select">
                                @php $currentTransport = old('sip_transport', \App\Models\SipSetting::get('sip_transport', 'TLS')); @endphp
                                <option value="UDP" {{ $currentTransport === 'UDP' ? 'selected' : '' }}>UDP</option>
                                <option value="TCP" {{ $currentTransport === 'TCP' ? 'selected' : '' }}>TCP</option>
                                <option value="TLS" {{ $currentTransport === 'TLS' ? 'selected' : '' }}>TLS</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">{{ __("ui.security") }}</h6>
                        <div class="mb-3">
                            <label class="form-label">Max auth attempts</label>
                            <input type="number" name="max_auth_attempts" class="form-control"
                                   value="{{ old('max_auth_attempts', \App\Models\SipSetting::get('max_auth_attempts', 3)) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ban duration (sec)</label>
                            <input type="number" name="ban_duration" class="form-control"
                                   value="{{ old('ban_duration', \App\Models\SipSetting::get('ban_duration', 300)) }}">
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="srtp_enabled" value="1"
                                   id="srtp" {{ old('srtp_enabled', \App\Models\SipSetting::get('srtp_enabled', true)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="srtp" style="font-size:0.85rem;">SRTP (media encryption)</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="tls_required" value="1"
                                   id="tlsRequired" {{ old('tls_required', \App\Models\SipSetting::get('tls_required', true)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="tlsRequired" style="font-size:0.85rem;">Require TLS signaling</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i>{{ __("ui.save") }}</button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- TAB: SMTP --}}
    {{-- ═══════════════════════════════════════ --}}
    <div id="tab-smtp" class="settings-panel" style="display:none;">
        <form action="{{ route('settings.smtp.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">SMTP Server</h6>
                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control"
                                   value="{{ old('smtp_host', \App\Models\SipSetting::get('smtp_host', '')) }}" placeholder="smtp.example.com">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control"
                                       value="{{ old('smtp_port', \App\Models\SipSetting::get('smtp_port', 587)) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    @php $enc = old('smtp_encryption', \App\Models\SipSetting::get('smtp_encryption', 'tls')); @endphp
                                    <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>Aucun</option>
                                    <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="smtp_username" class="form-control"
                                   value="{{ old('smtp_username', \App\Models\SipSetting::get('smtp_username', '')) }}" autocomplete="off">
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="smtp_password" class="form-control"
                                   value="{{ old('smtp_password', \App\Models\SipSetting::get('smtp_password', '')) }}" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">Sender >Expediteur & Notifications< Notifications</h6>
                        <div class="mb-3">
                            <label class="form-label">Sender address</label>
                            <input type="email" name="smtp_from_address" class="form-control"
                                   value="{{ old('smtp_from_address', \App\Models\SipSetting::get('smtp_from_address', 'noreply@voxa.center')) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sender name</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="{{ old('smtp_from_name', \App\Models\SipSetting::get('smtp_from_name', 'Voxa Center')) }}">
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="voicemail_notify_enabled" value="1"
                                   id="vmNotify" {{ old('voicemail_notify_enabled', \App\Models\SipSetting::get('voicemail_notify_enabled', false)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="vmNotify" style="font-size:0.85rem;">Email notification (voicemail)</label>
                        </div>
                        <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border);">
                            <h6 style="font-weight:600;font-size:0.8rem;margin-bottom:0.75rem;">Test configuration</h6>
                            <div class="d-flex gap-2">
                                <input type="email" id="testEmail" class="form-control form-control-sm" placeholder="test@example.com" style="max-width:250px;">
                                <button type="button" class="btn btn-outline-accent btn-sm" onclick="testSmtp()">
                                    <i class="bi bi-send me-1"></i>Tester
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i>{{ __('ui.save') }} SMTP</button>
            </div>
        </form>
        <form id="smtpTestForm" action="{{ route('settings.smtp.test') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="test_email" id="testEmailHidden">
        </form>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- TAB: AI & TTS --}}
    {{-- ═══════════════════════════════════════ --}}
    <div id="tab-ai" class="settings-panel" style="display:none;">
        <form action="{{ route('settings.ai.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-4">
                {{-- OpenAI --}}
                <div class="col-lg-6">
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">
                            <i class="bi bi-robot me-1" style="color:#10b981;"></i> OpenAI Realtime
                        </h6>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;">API Key</label>
                            @php
                                $envKey = '';
                                foreach(file(base_path('.env')) as $line) {
                                    if (str_starts_with(trim($line), 'OPENAI_API_KEY=')) {
                                        $envKey = trim(explode('=', $line, 2)[1] ?? '');
                                    }
                                }
                                $masked = $envKey ? substr($envKey, 0, 8) . '...' . substr($envKey, -4) : '';
                            @endphp
                            @if($envKey)
                                <div style="display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.6rem;background:var(--surface);border:1px solid var(--border);border-radius:6px;">
                                    <i class="bi bi-check-circle-fill" style="color:#3fb950;"></i>
                                    <code style="font-size:0.75rem;color:var(--text-secondary);">{{ $masked }}</code>
                                </div>
                                <small style="color:var(--text-secondary);font-size:0.65rem;">Configuree dans .env (modifier via SSH)</small>
                            @else
                                <div style="display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.6rem;background:#f8514910;border:1px solid #f8514930;border-radius:6px;">
                                    <i class="bi bi-exclamation-triangle-fill" style="color:#f85149;"></i>
                                    <span style="font-size:0.75rem;color:#f85149;">No key configuree</span>
                                </div>
                                <small style="color:var(--text-secondary);font-size:0.65rem;">Ajoutez OPENAI_API_KEY=sk-... dans /var/www/html/.env</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;">Model</label>
                            <select name="openai_model" class="form-select form-select-sm">
                                @php $curModel = \App\Models\SipSetting::get('openai_model', 'gpt-4o-realtime-preview-2024-12-17'); @endphp
                                <option value="gpt-4o-realtime-preview-2024-12-17" {{ $curModel === 'gpt-4o-realtime-preview-2024-12-17' ? 'selected' : '' }}>GPT-4o Realtime</option>
                                <option value="gpt-4o-mini-realtime-preview-2024-12-17" {{ $curModel === 'gpt-4o-mini-realtime-preview-2024-12-17' ? 'selected' : '' }}>GPT-4o Mini Realtime</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;">Default voice</label>
                            <select name="openai_voice" class="form-select form-select-sm">
                                @php $curVoice = \App\Models\SipSetting::get('openai_voice', 'coral'); @endphp
                                @foreach(['coral'=>'Coral (femme)','alloy'=>'Alloy (neutre)','ash'=>'Ash (homme)','ballad'=>'Ballad (doux)','echo'=>'Echo (homme)','sage'=>'Sage (calme)','shimmer'=>'Shimmer (femme)','verse'=>'Verse (expressif)'] as $v=>$l)
                                    <option value="{{ $v }}" {{ $curVoice === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;">Temperature</label>
                            <input type="number" name="openai_temperature" class="form-control form-control-sm"
                                   value="{{ \App\Models\SipSetting::get('openai_temperature', '0.8') }}" min="0" max="2" step="0.1">
                            <small style="color:var(--text-secondary);font-size:0.65rem;">0=precis 1=creatif 2=aleatoire</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">VAD Threshold</label>
                                <input type="number" name="openai_vad_threshold" class="form-control form-control-sm"
                                       value="{{ \App\Models\SipSetting::get('openai_vad_threshold', '0.5') }}" min="0.1" max="1.0" step="0.1">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">Silence (ms)</label>
                                <input type="number" name="openai_silence_ms" class="form-control form-control-sm"
                                       value="{{ \App\Models\SipSetting::get('openai_silence_ms', '1000') }}" min="200" max="5000" step="100">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label" style="font-size:0.78rem;">Max conversation turns</label>
                            <input type="number" name="openai_max_turns" class="form-control form-control-sm"
                                   value="{{ \App\Models\SipSetting::get('openai_max_turns', '30') }}" min="1" max="100">
                        </div>
                    </div>
                </div>

                {{-- Budget + Piper --}}
                <div class="col-lg-6">
                    {{-- Facturation live --}}
                    <div class="stat-card mb-3">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">
                            <i class="bi bi-wallet2 me-1" style="color:#d29922;"></i> Facturation & Budget
                        </h6>
                        @php
                            $budgetMax = \App\Models\SipSetting::get('openai_budget_max', '50');
                            $budgetPeriod = \App\Models\SipSetting::get('openai_budget_period', 'month');
                            $maxDurationCall = \App\Models\SipSetting::get('openai_max_duration_call', '300');
                            $maxDurationDay = \App\Models\SipSetting::get('openai_max_duration_day', '3600');
                            $activeModel = \App\Models\SipSetting::get('openai_model', 'gpt-4o-realtime-preview-2024-12-17');
                            $isMini = str_contains($activeModel, 'mini');
                            // GPT-4o Realtime: input $0.06/min + output $0.24/min = avg $0.15/min
                            // GPT-4o Mini:     input $0.01/min + output $0.04/min = avg $0.025/min
                            $pricePerMin = $isMini ? 0.025 : 0.15;
                            $modelLabel = $isMini ? 'GPT-4o Mini' : 'GPT-4o';
                            $today = now()->startOfDay();
                            $startOfWeek = now()->startOfWeek();
                            $startOfMonth = now()->startOfMonth();
                            $aiToday = \Illuminate\Support\Facades\DB::connection('asterisk')->table('cdr')->whereDate('calldate', $today)->where('lastapp', 'EAGI');
                            $usageToday = (int)(clone $aiToday)->sum('billsec');
                            $callsToday = (clone $aiToday)->count();
                            $usageWeek = (int)\Illuminate\Support\Facades\DB::connection('asterisk')->table('cdr')->where('calldate', '>=', $startOfWeek)->where('lastapp', 'EAGI')->sum('billsec');
                            $usageMonth = (int)\Illuminate\Support\Facades\DB::connection('asterisk')->table('cdr')->where('calldate', '>=', $startOfMonth)->where('lastapp', 'EAGI')->sum('billsec');
                            $periodStart = $budgetPeriod === 'month' ? $startOfMonth : ($budgetPeriod === 'week' ? $startOfWeek : $today);
                            $usagePeriod = $budgetPeriod === 'month' ? $usageMonth : ($budgetPeriod === 'week' ? $usageWeek : $usageToday);
                            $costToday = ($usageToday / 60) * $pricePerMin;
                            $costWeek = ($usageWeek / 60) * $pricePerMin;
                            $costMonth = ($usageMonth / 60) * $pricePerMin;
                            $costPeriod = ($usagePeriod / 60) * $pricePerMin;
                            $pctBudget = $budgetMax > 0 ? min(100, ($costPeriod / $budgetMax) * 100) : 0;
                        @endphp
                        <div style="padding:0.75rem;background:#0d1117;border:1px solid #21262d;border-radius:10px;margin-bottom:0.75rem;">
                            <div class="d-flex justify-content-center align-items-center gap-2 mb-2" style="font-size:0.68rem;">
                                <span style="background:{{ $isMini ? '#58a6ff20' : '#10b98120' }};color:{{ $isMini ? '#58a6ff' : '#10b981' }};padding:2px 8px;border-radius:4px;font-weight:700;">{{ $modelLabel }}</span>
                                <span style="color:#8b949e;">~${{ number_format($pricePerMin, 3) }}/min</span>
                            </div>
                            <div class="row g-2 text-center">
                                <div class="col-4">
                                    <div style="font-size:0.6rem;color:#8b949e;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.today") }}</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:#3fb950;font-family:'JetBrains Mono',monospace;">${{ number_format($costToday, 2) }}</div>
                                    <div style="font-size:0.6rem;color:#8b949e;">{{ $callsToday }} appels · {{ gmdate('H:i:s', $usageToday) }}</div>
                                </div>
                                <div class="col-4">
                                    <div style="font-size:0.6rem;color:#8b949e;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.week") }}</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:#58a6ff;font-family:'JetBrains Mono',monospace;">${{ number_format($costWeek, 2) }}</div>
                                    <div style="font-size:0.6rem;color:#8b949e;">{{ gmdate('H:i:s', $usageWeek) }}</div>
                                </div>
                                <div class="col-4">
                                    <div style="font-size:0.6rem;color:#8b949e;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.month") }}</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:#d29922;font-family:'JetBrains Mono',monospace;">${{ number_format($costMonth, 2) }}</div>
                                    <div style="font-size:0.6rem;color:#8b949e;">{{ gmdate('H:i:s', $usageMonth) }}</div>
                                </div>
                            </div>
                            <div style="margin-top:0.5rem;">
                                <div class="d-flex justify-content-between" style="font-size:0.62rem;color:#8b949e;margin-bottom:2px;">
                                    <span>Budget {{ ['day'=>'jour','week'=>'semaine','month'=>'mois'][$budgetPeriod] ?? '' }}</span>
                                    <span>${{ number_format($costPeriod, 2) }} / ${{ $budgetMax }}</span>
                                </div>
                                <div style="height:8px;background:#21262d;border-radius:4px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $pctBudget }}%;background:{{ $pctBudget > 90 ? '#f85149' : ($pctBudget > 60 ? '#d29922' : '#3fb950') }};border-radius:4px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">Budget max ($)</label>
                                <input type="number" name="openai_budget_max" class="form-control form-control-sm" value="{{ $budgetMax }}" min="0" step="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">Period</label>
                                <select name="openai_budget_period" class="form-select form-select-sm">
                                    <option value="day" {{ $budgetPeriod === 'day' ? 'selected' : '' }}>Per day</option>
                                    <option value="week" {{ $budgetPeriod === 'week' ? 'selected' : '' }}>Per week</option>
                                    <option value="month" {{ $budgetPeriod === 'month' ? 'selected' : '' }}>Per month</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">Max/call (sec)</label>
                                <input type="number" name="openai_max_duration_call" class="form-control form-control-sm" value="{{ $maxDurationCall }}" min="30" max="3600" step="30">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.78rem;">Max/day (sec)</label>
                                <input type="number" name="openai_max_duration_day" class="form-control form-control-sm" value="{{ $maxDurationDay }}" min="60" max="86400" step="60">
                            </div>
                        </div>
                    </div>

                    {{-- Piper TTS --}}
                    <div class="stat-card">
                        <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">
                            <i class="bi bi-soundwave me-1" style="color:#58a6ff;"></i> Piper TTS (local)
                        </h6>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;">Default voice</label>
                            <select name="piper_default_voice" class="form-select form-select-sm">
                                @php $curPiper = \App\Models\SipSetting::get('piper_default_voice', 'siwis'); @endphp
                                <option value="siwis" {{ $curPiper === 'siwis' ? 'selected' : '' }}>Femme (Siwis)</option>
                                <option value="upmc" {{ $curPiper === 'upmc' ? 'selected' : '' }}>Homme (UPMC)</option>
                                <option value="mls" {{ $curPiper === 'mls' ? 'selected' : '' }}>Femme 2 (MLS)</option>
                            </select>
                            <small style="color:var(--text-secondary);font-size:0.65rem;">Free local voice synthesis pour IVR et annonces</small>
                        </div>
                        @php
                            $ttsFiles = glob('/var/lib/asterisk/sounds/tts/*');
                            $cacheSize = array_sum(array_map('filesize', $ttsFiles ?: []));
                        @endphp
                        <div style="padding:0.5rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;font-size:0.75rem;">
                            <div class="d-flex justify-content-between">
                                <span style="color:var(--text-secondary);">Cache files</span>
                                <span style="font-weight:700;">{{ count($ttsFiles ?: []) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span style="color:var(--text-secondary);">Size</span>
                                <span style="font-weight:700;">{{ number_format($cacheSize / 1024 / 1024, 1) }} MB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i>{{ __('ui.save') }} AI / TTS</button>
            </div>
        </form>
    </div>

    <style>
    .settings-tab {
        background: none; border: none; padding: 0.5rem 1rem; font-size: 0.82rem; font-weight: 600;
        color: var(--text-secondary); cursor: pointer; border-bottom: 2px solid transparent;
        transition: all .15s;
    }
    .settings-tab:hover { color: var(--text-primary); }
    .settings-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
    </style>
@endsection

@push('scripts')
<script>
function settingsTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = 'block';
    document.querySelectorAll('.settings-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    localStorage.setItem('settings_tab', tab);
}
function testSmtp() {
    const email = document.getElementById('testEmail').value;
    if (!email) { alert('Entrez une adresse email'); return; }
    document.getElementById('testEmailHidden').value = email;
    document.getElementById('smtpTestForm').submit();
}
// Restore last tab
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('settings_tab');
    if (saved) settingsTab(saved);
});
</script>
@endpush
