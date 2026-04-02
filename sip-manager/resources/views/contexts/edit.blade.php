@extends('layouts.app')

@section('title', 'Modifier contexte')
@section('page-title', 'Modifier contexte')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Modifier « {{ $context->name }} »</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Modifier la regle de routage</p>
        </div>
        <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    <form action="{{ route('contexts.update', $context) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="data-table" style="padding:1.5rem;">
            <div class="row g-3">
                {{-- Nom --}}
                <div class="col-md-4">
                    <label class="form-label">Nom du contexte *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $context->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Direction --}}
                <div class="col-md-4">
                    <label class="form-label">Direction *</label>
                    <select name="direction" class="form-select" id="directionSelect" required>
                        <option value="inbound" {{ old('direction', $context->direction) === 'inbound' ? 'selected' : '' }}>Entrant</option>
                        <option value="outbound" {{ old('direction', $context->direction) === 'outbound' ? 'selected' : '' }}>Sortant</option>
                        <option value="internal" {{ old('direction', $context->direction) === 'internal' ? 'selected' : '' }}>Interne</option>
                    </select>
                </div>

                {{-- Priorite --}}
                <div class="col-md-4">
                    <label class="form-label">Priorite *</label>
                    <input type="number" name="priority" class="form-control" value="{{ old('priority', $context->priority) }}" min="1" max="99" required>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $context->description) }}">
                </div>

                {{-- Dial Pattern --}}
                <div class="col-md-6">
                    <label class="form-label">Dial Pattern</label>
                    <input type="text" name="dial_pattern" class="form-control" value="{{ old('dial_pattern', $context->dial_pattern) }}">
                </div>

                {{-- Destination Type --}}
                <div class="col-md-3">
                    <label class="form-label">Type destination</label>
                    <select name="destination_type" class="form-select">
                        <option value="extensions" {{ old('destination_type', $context->destination_type) === 'extensions' ? 'selected' : '' }}>Extensions</option>
                        <option value="trunk" {{ old('destination_type', $context->destination_type) === 'trunk' ? 'selected' : '' }}>Trunk</option>
                        <option value="queue" {{ old('destination_type', $context->destination_type) === 'queue' ? 'selected' : '' }}>File d'attente</option>
                        <option value="ivr" {{ old('destination_type', $context->destination_type) === 'ivr' ? 'selected' : '' }}>IVR</option>
                    </select>
                </div>

                {{-- Destination --}}
                <div class="col-md-3">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-control" value="{{ old('destination', $context->destination) }}">
                </div>

                {{-- Trunk --}}
                <div class="col-md-4" id="trunkGroup">
                    <label class="form-label">Trunk sortant</label>
                    <select name="trunk_id" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach($trunks as $trunk)
                            <option value="{{ $trunk->id }}" {{ old('trunk_id', $context->trunk_id) == $trunk->id ? 'selected' : '' }}>
                                {{ $trunk->name }} ({{ $trunk->host }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Caller ID Override --}}
                <div class="col-md-4">
                    <label class="form-label">CallerID override</label>
                    <input type="text" name="caller_id_override" class="form-control" value="{{ old('caller_id_override', $context->caller_id_override) }}">
                </div>

                {{-- Timeout --}}
                <div class="col-md-2">
                    <label class="form-label">Timeout global (sec)</label>
                    <input type="number" name="timeout" class="form-control" value="{{ old('timeout', $context->timeout) }}" min="5" max="120">
                </div>

                {{-- Ring Timeout --}}
                <div class="col-md-2">
                    <label class="form-label">Sonnerie (sec)</label>
                    <input type="number" name="ring_timeout" class="form-control" value="{{ old('ring_timeout', $context->ring_timeout) }}" min="5" max="120">
                    <small style="color:var(--text-secondary);font-size:0.72rem;">Duree avant repondeur</small>
                </div>

                {{-- Prefix --}}
                <div class="col-md-3">
                    <label class="form-label">Prefixe a retirer</label>
                    <input type="text" name="prefix_strip" class="form-control" value="{{ old('prefix_strip', $context->prefix_strip) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prefixe a ajouter</label>
                    <input type="text" name="prefix_add" class="form-control" value="{{ old('prefix_add', $context->prefix_add) }}">
                </div>

                {{-- Voicemail section --}}
                <div class="col-12 mt-2">
                    <div style="border:1px solid var(--border);border-radius:10px;padding:1rem;">
                        <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                            <i class="bi bi-voicemail me-1" style="color:var(--accent);"></i> Repondeur / Voicemail
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="voicemail_enabled" value="1"
                                           id="vmToggle" {{ old('voicemail_enabled', $context->voicemail_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" style="font-size:0.82rem;">Activer le repondeur</label>
                                </div>
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">Boite vocale</label>
                                <input type="text" name="voicemail_box" class="form-control"
                                       value="{{ old('voicemail_box', $context->voicemail_box) }}" placeholder="ex: 1001@default">
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">Message d'accueil</label>
                                <input type="text" name="greeting_sound" class="form-control"
                                       value="{{ old('greeting_sound', $context->greeting_sound) }}" placeholder="ex: custom/bienvenue">
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">Musique d'attente</label>
                                <input type="text" name="music_on_hold" class="form-control"
                                       value="{{ old('music_on_hold', $context->music_on_hold) }}" placeholder="ex: default">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="col-md-3">
                    <label class="form-label">Enregistrement</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="record_calls" value="1" {{ old('record_calls', $context->record_calls) ? 'checked' : '' }}>
                        <label class="form-check-label" style="font-size:0.82rem;">Enregistrer</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" {{ old('enabled', $context->enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" style="font-size:0.82rem;">Actif</label>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $context->notes) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
                <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">Annuler</a>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('directionSelect')?.addEventListener('change', function() {
            document.getElementById('trunkGroup').style.display = this.value === 'outbound' ? '' : 'none';
        });
        document.getElementById('directionSelect')?.dispatchEvent(new Event('change'));

        function toggleVmFields() {
            const checked = document.getElementById('vmToggle')?.checked;
            document.querySelectorAll('.vm-fields').forEach(el => {
                el.style.display = checked ? '' : 'none';
            });
        }
        document.getElementById('vmToggle')?.addEventListener('change', toggleVmFields);
        toggleVmFields();
    </script>
@endsection
