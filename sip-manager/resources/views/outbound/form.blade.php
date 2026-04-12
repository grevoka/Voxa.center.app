@extends('layouts.app')

@section('title', isset($route) ? __('ui.modify') . ' ' . __('ui.outbound_routes') : __('ui.new_f') . ' ' . __('ui.outbound_routes'))
@section('page-title', isset($route) ? __('ui.modify') . ' ' . __('ui.outbound_routes') : __('ui.new_f') . ' ' . __('ui.outbound_routes'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">
                @if(isset($route))
                    <i class="bi bi-pencil me-1" style="color:var(--accent);"></i> Modifier « {{ $route->name }} »
                @else
                    <i class="bi bi-plus-circle me-1" style="color:var(--accent);"></i> Nouvelle route sortante
                @endif
            </h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">
                Definir comment les appels sortants sont achemines vers le trunk SIP
            </p>
        </div>
        <a href="{{ route('outbound.index') }}" class="btn btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    <form action="{{ isset($route) ? route('outbound.update', $route) : route('outbound.store') }}" method="POST">
        @csrf
        @if(isset($route))
            @method('PUT')
        @endif

        <div class="row g-4">
            {{-- Left column: main settings --}}
            <div class="col-lg-8">
                <div class="data-table" style="padding:1.5rem;">
                    <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:1rem;">
                        <i class="bi bi-gear me-1" style="color:var(--accent);"></i> Configuration
                    </h6>

                    <div class="row g-3">
                        {{-- Nom --}}
                        <div class="col-md-6">
                            <label class="form-label">Nom de la route *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $route->name ?? '') }}"
                                   placeholder="ex: outbound-national" required>
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Minuscules, chiffres, tirets, underscores</small>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Priorite --}}
                        <div class="col-md-3">
                            <label class="form-label">Priorite *</label>
                            <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror"
                                   value="{{ old('priority', $route->priority ?? 10) }}" min="1" max="99" required>
                            <small style="color:var(--text-secondary);font-size:0.72rem;">1 = plus prioritaire</small>
                            @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Timeout --}}
                        <div class="col-md-3">
                            <label class="form-label">Timeout (sec)</label>
                            <input type="number" name="timeout" class="form-control"
                                   value="{{ old('timeout', $route->timeout ?? 45) }}" min="5" max="120">
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control"
                                   value="{{ old('description', $route->description ?? '') }}"
                                   placeholder="ex: Appels nationaux France metropolitaine">
                        </div>

                        {{-- Dial Pattern --}}
                        <div class="col-md-6">
                            <label class="form-label">Dial Pattern *</label>
                            <input type="text" name="dial_pattern" class="form-control @error('dial_pattern') is-invalid @enderror"
                                   value="{{ old('dial_pattern', $route->dial_pattern ?? '') }}"
                                   placeholder="ex: _0XXXXXXXXX" required>
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Pattern Asterisk qui correspond aux numeros a router</small>
                            @error('dial_pattern') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Trunk --}}
                        <div class="col-md-6">
                            <label class="form-label">Trunk SIP *</label>
                            <select name="trunk_id" class="form-select @error('trunk_id') is-invalid @enderror" required>
                                <option value="">— Choisir un trunk —</option>
                                @foreach($trunks as $trunk)
                                    <option value="{{ $trunk->id }}" {{ old('trunk_id', $route->trunk_id ?? '') == $trunk->id ? 'selected' : '' }}>
                                        {{ $trunk->name }} ({{ $trunk->host }})
                                    </option>
                                @endforeach
                            </select>
                            @error('trunk_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Prefix manipulation --}}
                <div class="data-table mt-3" style="padding:1.5rem;">
                    <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:1rem;">
                        <i class="bi bi-scissors me-1" style="color:var(--accent);"></i> Manipulation du numero
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Prefixe a retirer</label>
                            <input type="text" name="prefix_strip" class="form-control"
                                   value="{{ old('prefix_strip', $route->prefix_strip ?? '') }}"
                                   placeholder="ex: 0">
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Retire ces caracteres au debut du numero</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prefixe a ajouter</label>
                            <input type="text" name="prefix_add" class="form-control"
                                   value="{{ old('prefix_add', $route->prefix_add ?? '') }}"
                                   placeholder="ex: +33">
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Ajoute ce prefixe apres le retrait</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CallerID override</label>
                            <input type="text" name="caller_id_override" class="form-control"
                                   value="{{ old('caller_id_override', $route->caller_id_override ?? '') }}"
                                   placeholder="ex: +33123456789">
                            <small style="color:var(--text-secondary);font-size:0.72rem;">Force le numero presenté (vide = CallerID de la ligne)</small>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div id="previewBox" style="margin-top:1rem;padding:0.75rem;background:var(--surface-1);border:1px solid var(--border);border-radius:8px;font-size:0.82rem;display:none;">
                        <strong style="color:var(--accent);">Apercu :</strong>
                        <span id="previewText" style="color:var(--text-secondary);"></span>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="data-table mt-3" style="padding:1.5rem;">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Notes optionnelles...">{{ old('notes', $route->notes ?? '') }}</textarea>
                </div>
            </div>

            {{-- Right column: options + actions --}}
            <div class="col-lg-4">
                {{-- Status card --}}
                <div class="data-table" style="padding:1.25rem;">
                    <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:1rem;">
                        <i class="bi bi-toggles me-1" style="color:var(--accent);"></i> Options
                    </h6>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabledToggle"
                                   {{ old('enabled', $route->enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enabledToggle" style="font-size:0.85rem;font-weight:600;">Route active</label>
                        </div>
                        <small style="color:var(--text-secondary);font-size:0.72rem;">Desactiver sans supprimer la route</small>
                    </div>

                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="record_calls" value="1" id="recordToggle"
                                   {{ old('record_calls', $route->record_calls ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="recordToggle" style="font-size:0.85rem;font-weight:600;">Enregistrer les appels</label>
                        </div>
                        <small style="color:var(--text-secondary);font-size:0.72rem;">Enregistrement MixMonitor automatique</small>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="data-table mt-3" style="padding:1.25rem;">
                    <button type="submit" class="btn btn-accent w-100 mb-2">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($route) ? '{{ __('ui.update_apply') }}' : '{{ __('ui.create_apply') }}' }}
                    </button>
                    <a href="{{ route('outbound.index') }}" class="btn btn-outline-custom w-100">Annuler</a>
                </div>

                {{-- Help card --}}
                <div class="data-table mt-3" style="padding:1.25rem;">
                    <h6 style="font-weight:700;font-size:0.82rem;margin-bottom:0.75rem;">
                        <i class="bi bi-lightbulb me-1" style="color:var(--accent);"></i> Exemples courants
                    </h6>
                    <div style="font-size:0.78rem;color:var(--text-secondary);line-height:1.8;">
                        <div><code>_0XXXXXXXXX</code> — National FR (10 chiffres)</div>
                        <div class="ps-3" style="font-size:0.72rem;">Retirer <code>0</code>, ajouter <code>+33</code></div>
                        <div class="mt-1"><code>_+X.</code> — International (format E.164)</div>
                        <div class="mt-1"><code>_00X.</code> — International (prefixe 00)</div>
                        <div class="ps-3" style="font-size:0.72rem;">Retirer <code>00</code>, ajouter <code>+</code></div>
                        <div class="mt-1"><code>_1X</code> — Urgences (15, 17, 18...)</div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        // Live preview of number transformation
        const stripInput = document.querySelector('input[name="prefix_strip"]');
        const addInput = document.querySelector('input[name="prefix_add"]');
        const patternInput = document.querySelector('input[name="dial_pattern"]');
        const previewBox = document.getElementById('previewBox');
        const previewText = document.getElementById('previewText');

        function updatePreview() {
            const strip = stripInput.value;
            const add = addInput.value;
            const pattern = patternInput.value;

            if (!strip && !add) {
                previewBox.style.display = 'none';
                return;
            }

            // Generate example number from pattern
            let example = patternToExample(pattern);
            if (!example) {
                previewBox.style.display = 'none';
                return;
            }

            let result = example;
            if (strip && result.startsWith(strip)) {
                result = result.substring(strip.length);
            }
            if (add) {
                result = add + result;
            }

            previewBox.style.display = '';
            previewText.innerHTML = `<code>${example}</code> &rarr; <code style="color:var(--accent);">${result}</code>`;
        }

        function patternToExample(p) {
            if (!p) return null;
            let s = p.replace(/^_/, '');
            let out = '';
            for (let c of s) {
                if (c === 'X') out += '5';
                else if (c === 'Z') out += '3';
                else if (c === 'N') out += '7';
                else if (c === '.') out += '123';
                else if (c === '!') {}
                else out += c;
            }
            return out || null;
        }

        [stripInput, addInput, patternInput].forEach(el => {
            el?.addEventListener('input', updatePreview);
        });
        updatePreview();
    </script>
@endsection
