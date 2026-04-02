@extends('layouts.app')

@section('title', 'Lignes SIP')
@section('page-title', 'Lignes telephoniques')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Lignes telephoniques</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerer les extensions et comptes SIP</p>
        </div>
        <a href="{{ route('lines.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Nouvelle ligne
        </a>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Extension</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Protocole</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $line)
                    <tr>
                        <td><span class="ext-number">{{ $line->extension }}</span></td>
                        <td style="font-weight:500;">{{ $line->name }}</td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;">{{ $line->email ?? '—' }}</td>
                        <td><span class="codec-tag">{{ $line->protocol }}</span></td>
                        <td>
                            <span class="status-dot {{ $line->status }}"></span>
                            {{ $line->status === 'online' ? 'En ligne' : ($line->status === 'busy' ? 'Occupe' : 'Hors ligne') }}
                        </td>
                        <td>
                            <form action="{{ route('lines.toggle', $line) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="Basculer statut">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>
                            <a href="{{ route('lines.edit', $line) }}" class="btn-icon me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('lines.destroy', $line) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer la ligne {{ $line->extension }} ?')">
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
                        <td colspan="6" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-telephone-x me-2"></i>Aucune ligne configuree
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $lines->links() }}
    </div>
@endsection
