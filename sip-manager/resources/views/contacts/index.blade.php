@extends('layouts.app')

@section('title', 'Contacts')
@section('page-title', 'Contacts')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Carnet de contacts</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Ajouter un contact ou importer un fichier CSV (prenom, nom, telephone).</p>
        </div>
        <div>
            <a href="{{ route('contacts.import') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-upload me-1"></i> Importer CSV
            </a>
        </div>
    </div>

    <div class="data-table">
        <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
            <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                <i class="bi bi-person-rolodex me-1" style="color:var(--accent);"></i> Contacts
                <span class="badge" style="background:var(--accent-dim);color:var(--accent);font-size:0.6rem;margin-left:0.3rem;">{{ $contacts->total() }}</span>
            </h6>
            <form method="GET" action="{{ route('contacts.index') }}" class="d-flex" style="gap:6px;">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Rechercher…" class="form-control form-control-sm" style="font-size:0.78rem;width:200px;">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
        </div>

        {{-- Add form --}}
        <form action="{{ route('contacts.store') }}" method="POST" class="px-3 py-2" style="border-bottom:1px solid var(--border);background:rgba(var(--accent-rgb),0.02);">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-3">
                    <label class="form-label" style="font-size:0.72rem;">Prenom</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" required class="form-control form-control-sm" placeholder="Jean">
                </div>
                <div class="col-3">
                    <label class="form-label" style="font-size:0.72rem;">Nom</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required class="form-control form-control-sm" placeholder="Dupont">
                </div>
                <div class="col-4">
                    <label class="form-label" style="font-size:0.72rem;">Telephone</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" required class="form-control form-control-sm"
                           placeholder="0671852707" style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:0.82rem;">
                <thead>
                    <tr>
                        <th>Prenom</th>
                        <th>Nom</th>
                        <th>Telephone</th>
                        <th style="font-size:0.7rem;color:var(--text-secondary);">Ajoute le</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $c)
                        <tr>
                            <td>{{ $c->prenom }}</td>
                            <td><strong>{{ $c->nom }}</strong></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $c->telephone }}</td>
                            <td style="font-size:0.72rem;color:var(--text-secondary);">{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <form action="{{ route('contacts.destroy', $c) }}" method="POST" onsubmit="return confirm('Supprimer ce contact ?');" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);font-size:0.85rem;">Aucun contact.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
            <div class="px-3 py-2" style="border-top:1px solid var(--border);">{{ $contacts->links() }}</div>
        @endif
    </div>
@endsection
