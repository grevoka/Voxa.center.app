@extends('layouts.app')

@section('title', 'Scenarios d\'appels')
@section('page-title', 'Scenarios d\'appels entrants')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Scenarios d'appels</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Gerez vos flux d'appels entrants visuellement</p>
        </div>
        <a href="{{ route('callflows.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Nouveau scenario
        </a>
    </div>

    @if($flows->isEmpty())
        <div class="stat-card text-center" style="padding:3rem;">
            <i class="bi bi-diagram-2" style="font-size:3rem; color:var(--text-secondary); opacity:.3;"></i>
            <p style="color:var(--text-secondary); margin-top:1rem;">Aucun scenario configure.</p>
            <a href="{{ route('callflows.create') }}" class="btn btn-accent mt-2">
                <i class="bi bi-plus-lg me-1"></i> Creer un scenario
            </a>
        </div>
    @else
        <div class="data-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Trunk</th>
                        <th>Contexte</th>
                        <th>Etapes</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flows as $flow)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $flow->name }}</div>
                                @if($flow->description)
                                    <small style="color:var(--text-secondary);">{{ Str::limit($flow->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="trunk-type sip">{{ $flow->trunk->name ?? '—' }}</span>
                            </td>
                            <td>
                                <code style="font-size:0.75rem; color:var(--accent);">{{ $flow->inbound_context }}</code>
                            </td>
                            <td>
                                @foreach($flow->steps ?? [] as $step)
                                    @php
                                        $icons = [
                                            'answer' => 'bi-telephone-inbound',
                                            'ring' => 'bi-bell',
                                            'queue' => 'bi-people',
                                            'voicemail' => 'bi-voicemail',
                                            'playback' => 'bi-volume-up',
                                            'moh' => 'bi-music-note-beamed',
                                            'hangup' => 'bi-telephone-x',
                                            'announcement' => 'bi-megaphone',
                                            'goto' => 'bi-arrow-right-circle',
                                        ];
                                        $icon = $icons[$step['type'] ?? ''] ?? 'bi-circle';
                                    @endphp
                                    <i class="bi {{ $icon }}" style="color:var(--accent); margin-right:2px;" title="{{ $step['type'] ?? '' }}"></i>
                                    @if(!$loop->last)
                                        <i class="bi bi-chevron-right" style="font-size:0.6rem; opacity:.3; margin:0 1px;"></i>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <form action="{{ route('callflows.toggle', $flow) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon" style="border:none; background:none;">
                                        <span class="status-dot {{ $flow->enabled ? 'online' : 'offline' }}"></span>
                                        <span style="font-size:0.75rem;">{{ $flow->enabled ? 'Actif' : 'Inactif' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('callflows.dialplan', $flow) }}" class="btn-icon" title="Voir dialplan">
                                        <i class="bi bi-code-slash"></i>
                                    </a>
                                    <a href="{{ route('callflows.edit', $flow) }}" class="btn-icon" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('callflows.destroy', $flow) }}" method="POST"
                                          onsubmit="return confirm('Supprimer ce scenario ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $flows->links() }}</div>
    @endif
@endsection
