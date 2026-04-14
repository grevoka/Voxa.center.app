@extends('layouts.app')

@section('title', __('ui.new') . ' ' . __('ui.contexts'))
@section('page-title', __('ui.new') . ' ' . __('ui.contexts'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __('ui.create') }} {{ __('ui.call_contexts') }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.define_routing_rule') }}</p>
        </div>
        <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}
        </a>
    </div>

    <form action="{{ route('contexts.store') }}" method="POST">
        @csrf
        <div class="data-table" style="padding:1.5rem;">
            <div class="row g-3">
                {{-- Nom --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.context_name') }} *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="ex: outbound-national" required>
                    <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.lowercase_hint') }}</small>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Direction --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.direction') }} *</label>
                    <select name="direction" class="form-select @error('direction') is-invalid @enderror" id="directionSelect" required>
                        <option value="inbound" {{ old('direction') === 'inbound' ? 'selected' : '' }}>{{ __("ui.inbound") }}</option>
                        <option value="outbound" {{ old('direction') === 'outbound' ? 'selected' : '' }}>{{ __("ui.outbound") }}</option>
                        <option value="internal" {{ old('direction', 'internal') === 'internal' ? 'selected' : '' }}>{{ __("ui.internal") }}</option>
                    </select>
                    @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Priorite --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.priority') }} *</label>
                    <input type="number" name="priority" class="form-control" value="{{ old('priority', 10) }}" min="1" max="99" required>
                    <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.priority_hint') }}</small>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label">{{ __('ui.description') }}</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}"
                           placeholder="{{ __('ui.description') }}">
                </div>

                {{-- Dial Pattern --}}
                <div class="col-md-6">
                    <label class="form-label">Dial Pattern</label>
                    <input type="text" name="dial_pattern" class="form-control" value="{{ old('dial_pattern') }}"
                           placeholder="ex: _0XXXXXXXXX, _+33X., _1XXX">
                    <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.asterisk_pattern_hint') }}</small>
                </div>

                {{-- Destination Type --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.dest_type') }}</label>
                    <select name="destination_type" class="form-select">
                        <option value="extensions" {{ old('destination_type') === 'extensions' ? 'selected' : '' }}>{{ __('ui.extensions') }}</option>
                        <option value="trunk" {{ old('destination_type') === 'trunk' ? 'selected' : '' }}>Trunk</option>
                        <option value="queue" {{ old('destination_type') === 'queue' ? 'selected' : '' }}>{{ __('ui.queue_dest') }}</option>
                        <option value="ivr" {{ old('destination_type') === 'ivr' ? 'selected' : '' }}>IVR</option>
                    </select>
                </div>

                {{-- Destination --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.destination') }}</label>
                    <input type="text" name="destination" class="form-control" value="{{ old('destination') }}"
                           placeholder="ex: 1001, ${EXTEN}">
                </div>

                {{-- Trunk (outbound) --}}
                <div class="col-md-4" id="trunkGroup">
                    <label class="form-label">{{ __('ui.outbound_trunk') }}</label>
                    <select name="trunk_id" class="form-select">
                        <option value="">{{ __('ui.none_trunk') }}</option>
                        @foreach($trunks as $trunk)
                            <option value="{{ $trunk->id }}" {{ old('trunk_id') == $trunk->id ? 'selected' : '' }}>
                                {{ $trunk->name }} ({{ $trunk->host }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Caller ID Override --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.callerid_override') }}</label>
                    <input type="text" name="caller_id_override" class="form-control" value="{{ old('caller_id_override') }}"
                           placeholder="ex: +33123456789">
                </div>

                {{-- Timeout --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('ui.global_timeout') }}</label>
                    <input type="number" name="timeout" class="form-control" value="{{ old('timeout', 45) }}" min="5" max="120">
                </div>

                {{-- Ring Timeout --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('ui.ring_duration') }}</label>
                    <input type="number" name="ring_timeout" class="form-control" value="{{ old('ring_timeout', 25) }}" min="5" max="120">
                    <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.before_voicemail') }}</small>
                </div>

                {{-- Prefix strip/add --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.prefix_strip') }}</label>
                    <input type="text" name="prefix_strip" class="form-control" value="{{ old('prefix_strip') }}" placeholder="ex: 0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.prefix_add') }}</label>
                    <input type="text" name="prefix_add" class="form-control" value="{{ old('prefix_add') }}" placeholder="ex: +33">
                </div>

                {{-- Voicemail section --}}
                <div class="col-12 mt-2">
                    <div style="border:1px solid var(--border);border-radius:10px;padding:1rem;">
                        <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                            <i class="bi bi-voicemail me-1" style="color:var(--accent);"></i> {{ __('ui.voicemail_section') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="voicemail_enabled" value="1"
                                           id="vmToggle" {{ old('voicemail_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label" style="font-size:0.82rem;">{{ __('ui.enable_voicemail') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">{{ __('ui.voicemail_box') }}</label>
                                <input type="text" name="voicemail_box" class="form-control" value="{{ old('voicemail_box') }}"
                                       placeholder="ex: 1001@default">
                                <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.voicemail_ctx_hint') }}</small>
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">{{ __('ui.greeting_sound') }}</label>
                                <input type="text" name="greeting_sound" class="form-control" value="{{ old('greeting_sound') }}"
                                       placeholder="ex: custom/bienvenue">
                                <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.sound_file_hint') }}</small>
                            </div>
                            <div class="col-md-3 vm-fields">
                                <label class="form-label">{{ __('ui.moh_class') }}</label>
                                <input type="text" name="music_on_hold" class="form-control" value="{{ old('music_on_hold') }}"
                                       placeholder="ex: default">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.recording_label') }}</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="record_calls" value="1" {{ old('record_calls') ? 'checked' : '' }}>
                        <label class="form-check-label" style="font-size:0.82rem;">{{ __('ui.record_calls_label') }}</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.status') }}</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                        <label class="form-check-label" style="font-size:0.82rem;">{{ __('ui.active') }}</label>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="col-12">
                    <label class="form-label">{{ __('ui.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-check-lg me-1"></i> {{ __('ui.create_context') }}
                </button>
                <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">{{ __('ui.cancel') }}</a>
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
