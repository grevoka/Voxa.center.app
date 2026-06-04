@extends('layouts.app')

@section('title', 'Import CSV')
@section('page-title', 'Import contacts')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;"><i class="bi bi-upload me-1"></i>Import CSV</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Format attendu : <code>prenom, nom, telephone</code> — separateurs <code>,</code> ou <code>;</code>. Les doublons (memes prenom, nom, telephone) sont ignores.</p>
        </div>
        <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>

    <div class="data-table">
        <form action="{{ route('contacts.import.store') }}" method="POST" enctype="multipart/form-data" class="px-3 py-3">
            @csrf
            <div class="mb-3">
                <label class="form-label" style="font-size:0.82rem;font-weight:600;">Fichier CSV</label>
                <input type="file" name="csv" accept=".csv,text/csv" required class="form-control form-control-sm">
                <small style="font-size:0.72rem;color:var(--text-secondary);">Max 5 Mo. Premiere ligne header optionnelle ("prenom,nom,telephone").</small>
            </div>
            <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-cloud-upload me-1"></i>Importer</button>
        </form>
    </div>

    @isset($imported)
        <div class="data-table mt-3">
            <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;"><i class="bi bi-clipboard-check me-1" style="color:var(--accent);"></i>Resultat</h6>
            </div>
            <div class="px-3 py-3">
                <p class="mb-2" style="font-size:0.92rem;">
                    <span class="badge bg-success">{{ $imported }}</span> contact{{ $imported > 1 ? 's' : '' }} importe{{ $imported > 1 ? 's' : '' }}.
                    @if(!empty($duplicates))
                        <span class="badge bg-warning text-dark ms-2">{{ count($duplicates) }}</span> doublon{{ count($duplicates) > 1 ? 's' : '' }} ignore{{ count($duplicates) > 1 ? 's' : '' }}.
                    @endif
                    @if(!empty($errors))
                        <span class="badge bg-danger ms-2">{{ count($errors) }}</span> erreur{{ count($errors) > 1 ? 's' : '' }}.
                    @endif
                </p>

                @if(!empty($duplicates))
                    <details class="mt-2">
                        <summary style="font-size:0.82rem;cursor:pointer;color:var(--text-secondary);">Liste des doublons ({{ count($duplicates) }})</summary>
                        <ul class="mt-2 mb-0" style="font-size:0.8rem;font-family:'JetBrains Mono',monospace;">
                            @foreach($duplicates as $d)<li>{{ $d }}</li>@endforeach
                        </ul>
                    </details>
                @endif

                @if(!empty($errors))
                    <details class="mt-2">
                        <summary style="font-size:0.82rem;cursor:pointer;color:var(--danger);">Erreurs ({{ count($errors) }})</summary>
                        <ul class="mt-2 mb-0" style="font-size:0.8rem;">
                            @foreach($errors as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </details>
                @endif

                <div class="mt-3">
                    <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-list me-1"></i>Voir le carnet</a>
                </div>
            </div>
        </div>
    @endisset
@endsection
