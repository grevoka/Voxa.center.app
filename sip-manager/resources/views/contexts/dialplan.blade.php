@extends('layouts.app')

@section('title', 'Dialplan genere')
@section('page-title', 'Dialplan genere')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Dialplan Asterisk</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">extensions.conf genere a partir des contextes configures</p>
        </div>
        <a href="{{ route('contexts.index') }}" class="btn btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i> Retour aux contextes
        </a>
    </div>

    <div class="data-table" style="padding:1.5rem;">
        <pre style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1.25rem;color:var(--accent);font-family:'JetBrains Mono',monospace;font-size:0.8rem;overflow-x:auto;white-space:pre;margin:0;">{{ $dialplan }}</pre>
    </div>
@endsection
