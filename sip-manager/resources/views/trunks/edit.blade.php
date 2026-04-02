@extends('layouts.app')

@section('title', 'Modifier trunk ' . $trunk->name)
@section('page-title', 'Modifier trunk — ' . $trunk->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="stat-card">
                <h6 style="font-weight:700;font-size:1rem;margin-bottom:1.5rem;">
                    <i class="bi bi-pencil-fill me-2" style="color:var(--accent);"></i>
                    Modifier trunk — {{ $trunk->name }}
                </h6>

                <form action="{{ route('trunks.update', $trunk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom du trunk</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $trunk->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                @foreach(['SIP', 'IAX', 'PRI'] as $type)
                                    <option value="{{ $type }}" {{ old('type', $trunk->type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Transport</label>
                            <select name="transport" class="form-select">
                                @foreach(['UDP', 'TCP', 'TLS'] as $transport)
                                    <option value="{{ $transport }}" {{ old('transport', $trunk->transport) == $transport ? 'selected' : '' }}>{{ $transport }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hote / IP</label>
                            <input type="text" name="host" class="form-control @error('host') is-invalid @enderror"
                                   value="{{ old('host', $trunk->host) }}" required>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" class="form-control"
                                   value="{{ old('port', $trunk->port) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Canaux max</label>
                            <input type="number" name="max_channels" class="form-control"
                                   value="{{ old('max_channels', $trunk->max_channels) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Utilisateur / Auth</label>
                            <input type="text" name="username" class="form-control"
                                   value="{{ old('username', $trunk->username) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe <small>(laisser vide pour ne pas changer)</small></label>
                            <input type="password" name="secret" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Codecs</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($codecs as $key => $codec)
                                    <label class="codec-tag codec-check" style="cursor:pointer;">
                                        <input type="checkbox" name="codecs[]" value="{{ $key }}"
                                               {{ in_array($key, old('codecs', $trunk->codecs ?? [])) ? 'checked' : '' }}
                                               style="display:none;"
                                               onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                        {{ $codec['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Caller ID sortant</label>
                            <input type="text" name="caller_id" class="form-control"
                                   value="{{ old('caller_id', $trunk->caller_id) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contexte (sortant)</label>
                            <input type="text" name="context" class="form-control"
                                   value="{{ old('context', $trunk->context) }}">
                        </div>

                        {{-- Inbound IPs --}}
                        <div class="col-12" style="border-top:1px solid rgba(255,255,255,.05); padding-top:1rem; margin-top:.5rem;">
                            <h6 style="font-weight:600; font-size:0.9rem; margin-bottom:0.5rem;">
                                <i class="bi bi-shield-lock me-1" style="color:var(--accent);"></i>
                                Appels entrants (Identify by IP)
                            </h6>
                            <p style="font-size:0.8rem; opacity:0.6; margin-bottom:1rem;">
                                IPs/CIDR du provider autorisees a envoyer des appels sans authentification. Une par ligne.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IPs autorisees (CIDR)</label>
                            <textarea name="inbound_ips_text" class="form-control" rows="4"
                                      placeholder="91.121.129.0/24&#10;91.121.128.0/24">{{ old('inbound_ips_text', $trunk->inbound_ips ? implode("\n", $trunk->inbound_ips) : '') }}</textarea>
                            <small style="opacity:0.5;">Une IP ou plage CIDR par ligne</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contexte entrant</label>
                            <input type="text" name="inbound_context" class="form-control"
                                   value="{{ old('inbound_context', $trunk->inbound_context) }}" placeholder="from-trunk-d4 (auto si vide)">
                            <small style="opacity:0.5;">Laissez vide pour generer automatiquement</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="register" value="1"
                                       id="register" {{ old('register', $trunk->register) ? 'checked' : '' }}>
                                <label class="form-check-label" for="register" style="font-size:0.85rem;">
                                    Enregistrement aupres du provider
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $trunk->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('trunks.index') }}" class="btn btn-outline-custom">Annuler</a>
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-lg me-1"></i> Mettre a jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.codec-check').forEach(el => {
        if (el.querySelector('input').checked) el.classList.add('selected');
    });
</script>
@endpush
