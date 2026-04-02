@extends('layouts.app')

@section('title', 'Parametres')
@section('page-title', 'Parametres SIP')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Parametres SIP</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Configuration globale du serveur SIP</p>
        </div>
    </div>

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="stat-card">
                    <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">Serveur SIP</h6>
                    <div class="mb-3">
                        <label class="form-label">Adresse du serveur</label>
                        <input type="text" name="sip_server" class="form-control @error('sip_server') is-invalid @enderror"
                               value="{{ old('sip_server', \App\Models\SipSetting::get('sip_server', 'sip.local')) }}"
                               placeholder="sip.example.com">
                        @error('sip_server')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Port SIP</label>
                            <input type="number" name="sip_port" class="form-control"
                                   value="{{ old('sip_port', \App\Models\SipSetting::get('sip_port', 5060)) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Port TLS</label>
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
                    <h6 style="font-weight:700;font-size:0.9rem;margin-bottom:1rem;">Securite</h6>
                    <div class="mb-3">
                        <label class="form-label">Tentatives max d'authentification</label>
                        <input type="number" name="max_auth_attempts" class="form-control"
                               value="{{ old('max_auth_attempts', \App\Models\SipSetting::get('max_auth_attempts', 3)) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duree ban (secondes)</label>
                        <input type="number" name="ban_duration" class="form-control"
                               value="{{ old('ban_duration', \App\Models\SipSetting::get('ban_duration', 300)) }}">
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="srtp_enabled" value="1"
                               id="srtp" {{ old('srtp_enabled', \App\Models\SipSetting::get('srtp_enabled', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="srtp" style="font-size:0.85rem;">Activer SRTP (chiffrement media)</label>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="tls_required" value="1"
                               id="tlsRequired" {{ old('tls_required', \App\Models\SipSetting::get('tls_required', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="tlsRequired" style="font-size:0.85rem;">Exiger TLS pour la signalisation</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-accent">
                <i class="bi bi-check-lg me-1"></i> Sauvegarder
            </button>
        </div>
    </form>
@endsection
