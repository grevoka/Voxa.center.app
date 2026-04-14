@extends('layouts.app')

@section('title', __('ui.new_f') . ' ' . __('ui.lines'))
@section('page-title', __('ui.new_f') . ' ' . __('ui.lines'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="stat-card">
                <h6 style="font-weight:700;font-size:1rem;margin-bottom:1.5rem;">
                    <i class="bi bi-telephone-plus-fill me-2" style="color:var(--accent);"></i>
                    {{ __('ui.new_f') }} {{ __('ui.lines') }}
                </h6>

                <form action="{{ route('lines.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.extension') }}</label>
                            <input type="text" name="extension" class="form-control @error('extension') is-invalid @enderror"
                                   value="{{ old('extension') }}" placeholder="1001" required>
                            @error('extension') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.protocol') }}</label>
                            <select name="protocol" class="form-select">
                                <option value="SIP/UDP" {{ old('protocol') == 'SIP/UDP' ? 'selected' : '' }}>SIP/UDP</option>
                                <option value="SIP/TCP" {{ old('protocol') == 'SIP/TCP' ? 'selected' : '' }}>SIP/TCP</option>
                                <option value="SIP/TLS" {{ old('protocol') == 'SIP/TLS' ? 'selected' : '' }}>SIP/TLS</option>
                                <option value="WebRTC" {{ old('protocol') == 'WebRTC' ? 'selected' : '' }}>WebRTC</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('ui.full_name') }}</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="John Doe" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('ui.email') }}</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="john@example.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.sip_password') }}</label>
                            <input type="password" name="secret" class="form-control @error('secret') is-invalid @enderror"
                                   placeholder="{{ __('ui.min_chars') }}" required>
                            @error('secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Caller ID</label>
                            <input type="text" name="caller_id" class="form-control"
                                   value="{{ old('caller_id') }}" placeholder="+33 1 23 45 67 89">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.th_context') }}</label>
                            <input type="text" name="context" class="form-control"
                                   value="{{ old('context', 'from-internal') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-telephone-outbound me-1" style="color:var(--accent);"></i> {{ __('ui.outbound_trunk') }}
                            </label>
                            <select name="outbound_trunk_id" class="form-select">
                                <option value="">— {{ __('ui.default_route') }} —</option>
                                @foreach($trunks as $trunk)
                                    <option value="{{ $trunk->id }}" {{ old('outbound_trunk_id') == $trunk->id ? 'selected' : '' }}>
                                        {{ $trunk->name }} ({{ $trunk->host }})
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:var(--text-secondary);font-size:0.72rem;">{{ __('ui.force_trunk_desc') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.max_contacts') }}</label>
                            <input type="number" name="max_contacts" class="form-control"
                                   value="{{ old('max_contacts', 1) }}" min="1" max="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('ui.codecs') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($codecs as $key => $codec)
                                    <label class="codec-tag codec-check" style="cursor:pointer;">
                                        <input type="checkbox" name="codecs[]" value="{{ $key }}"
                                               {{ in_array($key, old('codecs', ['alaw', 'ulaw', 'g722'])) ? 'checked' : '' }}
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
                                       id="voicemail" {{ old('voicemail_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="voicemail" style="font-size:0.85rem;">
                                    {{ __('ui.enable_voicemail') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('ui.optional_notes') }}...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('lines.index') }}" class="btn btn-outline-custom">{{ __('ui.cancel') }}</a>
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-lg me-1"></i> {{ __('ui.save') }}
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
