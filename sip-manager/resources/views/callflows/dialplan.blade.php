@extends('layouts.app')

@section('title', 'Dialplan — ' . $callflow->name)
@section('page-title', 'Dialplan')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">
                <i class="bi bi-code-slash me-1" style="color:var(--accent);"></i>
                Dialplan: {{ $callflow->name }}
            </h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">
                {{ __('ui.th_context') }} <code style="color:var(--accent);">{{ $callflow->inbound_context }}</code>
                — Trunk {{ $callflow->trunk->name ?? '—' }}
                — {{ count($callflow->steps ?? []) }} {{ __('ui.steps') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('callflows.edit', $callflow) }}" class="btn-outline-custom">
                <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
            </a>
            <a href="{{ route('callflows.index') }}" class="btn-outline-custom">
                <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back') }}
            </a>
        </div>
    </div>

    <div class="stat-card" style="padding:0; overflow:hidden;">
        <div style="padding:0.75rem 1.25rem; background:var(--surface-3); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
            <span style="font-weight:600; font-size:0.82rem;">
                <i class="bi bi-file-earmark-code me-1"></i> extensions.conf
            </span>
            <button class="btn-outline-custom" style="padding:0.3rem 0.75rem; font-size:0.75rem;" onclick="copyDialplan()">
                <i class="bi bi-clipboard me-1"></i> {{ __('ui.copy') }}
            </button>
        </div>
        <pre id="dialplanCode" style="padding:1.25rem; margin:0; font-family:'JetBrains Mono',monospace; font-size:0.78rem; color:var(--accent); overflow-x:auto; line-height:1.7;">{{ $dialplan }}</pre>
    </div>
@endsection

@push('scripts')
<script>
function copyDialplan() {
    const text = document.getElementById('dialplanCode').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-1"></i> {{ __('ui.copied') }}';
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}
</script>
@endpush
