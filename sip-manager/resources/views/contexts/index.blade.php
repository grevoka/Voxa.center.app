@extends('layouts.app')

@section('title', __('ui.contexts')\')
@section('page-title', __('ui.contexts')\')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Contextes d'appel</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Regles de routage entrant, sortant et interne</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contexts.dialplan') }}" class="btn btn-outline-custom">
                <i class="bi bi-code-slash me-1"></i> Voir Dialplan
            </a>
            <a href="{{ route('contexts.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1"></i> Nouveau contexte
            </a>
        </div>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __("ui.priority") }}</th>
                    <th>{{ __("ui.name") }}</th>
                    <th>{{ __("ui.direction") }}</th>
                    <th>{{ __("ui.pattern") }}</th>
                    <th>{{ __("ui.destination") }}</th>
                    <th>Repondeur</th>
                    <th>{{ __("ui.status") }}</th>
                    <th>{{ __("ui.actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contexts as $ctx)
                    <tr>
                        <td>
                            <span class="ext-number">{{ $ctx->priority }}</span>
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ $ctx->name }}</div>
                            @if($ctx->description)
                                <div style="font-size:0.75rem;color:var(--text-secondary);">{{ $ctx->description }}</div>
                            @endif
                        </td>
                        <td>
                            @if($ctx->direction === 'inbound')
                                <span class="direction-badge inbound"><i class="bi bi-telephone-inbound-fill me-1"></i>{{ __("ui.inbound") }}</span>
                            @elseif($ctx->direction === 'outbound')
                                <span class="direction-badge outbound"><i class="bi bi-telephone-outbound-fill me-1"></i>{{ __("ui.outbound") }}</span>
                            @else
                                <span class="direction-badge internal"><i class="bi bi-telephone-fill me-1"></i>{{ __("ui.internal") }}</span>
                            @endif
                        </td>
                        <td><code style="color:var(--accent);font-size:0.8rem;">{{ $ctx->dial_pattern ?: '_X.' }}</code></td>
                        <td style="font-size:0.82rem;color:var(--text-secondary);">
                            {{ $ctx->destination ?: '—' }}
                            @if($ctx->trunk_id)
                                <span class="codec-tag">{{ optional($ctx->trunk)->name ?? 'trunk #'.$ctx->trunk_id }}</span>
                            @endif
                        </td>
                        <td>
                            @if($ctx->voicemail_enabled)
                                <span style="color:var(--accent);font-size:0.8rem;">
                                    <i class="bi bi-voicemail"></i> {{ $ctx->voicemail_box }}
                                </span>
                                <br><small style="color:var(--text-secondary);font-size:0.7rem;">apres {{ $ctx->ring_timeout }}s</small>
                            @else
                                <span style="color:var(--text-secondary);font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-dot {{ $ctx->enabled ? 'online' : 'offline' }}"></span>
                            {{ $ctx->enabled ? 'Actif' : 'Inactif' }}
                        </td>
                        <td>
                            <form action="{{ route('contexts.toggle', $ctx) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="Basculer">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>
                            <a href="{{ route('contexts.edit', $ctx) }}" class="btn-icon me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('contexts.destroy', $ctx) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer le contexte {{ $ctx->name }} ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-signpost-split me-2"></i>{{ __('ui.no_contexts') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $contexts->links() }}
    </div>
@endsection
