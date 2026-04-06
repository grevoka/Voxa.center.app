@extends('layouts.app')

@section('title', 'Modifier ligne ' . $line->extension)
@section('page-title', 'Modifier ligne ' . $line->extension)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="stat-card">
                <h6 style="font-weight:700;font-size:1rem;margin-bottom:1.5rem;">
                    <i class="bi bi-pencil-fill me-2" style="color:var(--accent);"></i>
                    Modifier la ligne {{ $line->extension }}
                </h6>

                <form action="{{ route('lines.update', $line) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Extension</label>
                            <input type="text" name="extension" class="form-control @error('extension') is-invalid @enderror"
                                   value="{{ old('extension', $line->extension) }}" required>
                            @error('extension')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Protocole</label>
                            <select name="protocol" class="form-select">
                                @foreach(['SIP/UDP', 'SIP/TCP', 'SIP/TLS', 'WebRTC'] as $proto)
                                    <option value="{{ $proto }}" {{ old('protocol', $line->protocol) == $proto ? 'selected' : '' }}>{{ $proto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $line->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $line->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nouveau mot de passe SIP <small>(laisser vide pour ne pas changer)</small></label>
                            <input type="password" name="secret" class="form-control @error('secret') is-invalid @enderror"
                                   placeholder="Min. 8 caracteres">
                            @error('secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Caller ID</label>
                            <input type="text" name="caller_id" class="form-control"
                                   value="{{ old('caller_id', $line->caller_id) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contexte</label>
                            <input type="text" name="context" class="form-control"
                                   value="{{ old('context', $line->context) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-telephone-outbound me-1" style="color:var(--accent);"></i> Trunk sortant
                            </label>
                            <select name="outbound_trunk_id" class="form-select">
                                <option value="">— Defaut (route globale) —</option>
                                @foreach($trunks as $trunk)
                                    <option value="{{ $trunk->id }}" {{ old('outbound_trunk_id', $line->outbound_trunk_id) == $trunk->id ? 'selected' : '' }}>
                                        {{ $trunk->name }} ({{ $trunk->host }})
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Force la sortie via ce trunk pour ce poste (vide = trunk de la route sortante)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max contacts</label>
                            <input type="number" name="max_contacts" class="form-control"
                                   value="{{ old('max_contacts', $line->max_contacts) }}" min="1" max="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Codecs</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($codecs as $key => $codec)
                                    <label class="codec-tag codec-check" style="cursor:pointer;">
                                        <input type="checkbox" name="codecs[]" value="{{ $key }}"
                                               {{ in_array($key, old('codecs', $line->codecs ?? [])) ? 'checked' : '' }}
                                               style="display:none;"
                                               onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                        {{ $codec['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="voicemail_enabled" value="1"
                                       id="voicemail" {{ old('voicemail_enabled', $line->voicemail_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="voicemail" style="font-size:0.85rem;">
                                    Activer la messagerie vocale
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $line->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('lines.index') }}" class="btn btn-outline-custom">Annuler</a>
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
