@extends('layouts.app')

@section('title', 'Trunks SIP')
@section('page-title', 'Trunks SIP')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Trunks SIP</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerer les connexions vers les operateurs</p>
        </div>
        <a href="{{ route('trunks.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Nouveau trunk
        </a>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Hote</th>
                    <th>Port</th>
                    <th>Codecs</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trunks as $trunk)
                    <tr>
                        <td style="font-weight:600;">{{ $trunk->name }}</td>
                        <td><span class="trunk-type {{ strtolower($trunk->type) }}">{{ $trunk->type }}</span></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">{{ $trunk->host }}</td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">{{ $trunk->port }}</td>
                        <td>
                            @if($trunk->codecs)
                                @foreach($trunk->codecs as $codec)
                                    <span class="codec-tag">{{ $codec }}</span>
                                @endforeach
                            @else
                                <span style="color:var(--text-secondary);">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-dot {{ $trunk->status }}"></span>
                            {{ $trunk->status === 'online' ? 'En ligne' : ($trunk->status === 'error' ? 'Erreur' : 'Hors ligne') }}
                        </td>
                        <td>
                            <form action="{{ route('trunks.toggle', $trunk) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="Basculer statut">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>
                            <a href="{{ route('trunks.edit', $trunk) }}" class="btn-icon me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('trunks.destroy', $trunk) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer le trunk {{ $trunk->name }} ?')">
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
                        <td colspan="7" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-diagram-3 me-2"></i>Aucun trunk configure
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $trunks->links() }}
    </div>
@endsection
