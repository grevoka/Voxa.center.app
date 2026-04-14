@extends('layouts.app')

@section('title', __('ui.generated_dialplan'))
@section('page-title', __('ui.generated_dialplan'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __('ui.asterisk_dialplan') }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.dialplan_desc') }}</p>
        </div>
        <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back_to_contexts') }}
        </a>
    </div>

    <div class="data-table" style="padding:1.5rem;">
        <pre style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1.25rem;color:var(--accent);font-family:'JetBrains Mono',monospace;font-size:0.8rem;overflow-x:auto;white-space:pre;margin:0;">{{ $dialplan }}</pre>
    </div>
@endsection
