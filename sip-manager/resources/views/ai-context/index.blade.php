@extends('layouts.app')

@section('title', 'Base de connaissances AI')
@section('page-title', 'Base de connaissances AI')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Base de connaissances AI</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Documents de contexte charges automatiquement par l'agent IA (RAG)</p>
        </div>
        <div style="font-size:0.75rem;color:var(--text-secondary);">
            {{ count($files) }} fichiers · {{ number_format($totalSize / 1024, 1) }} KB
        </div>
    </div>

    <div class="row g-4">
        {{-- Fichiers existants --}}
        <div class="col-lg-7">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-file-earmark-text me-1" style="color:var(--accent);"></i> Documents
                    </h6>
                </div>

                @forelse($files as $f)
                <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                    <div class="d-flex align-items-start justify-content-between">
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ str_ends_with($f['name'], '.md') ? 'bi-markdown' : 'bi-file-text' }}" style="color:var(--accent);"></i>
                                <span style="font-weight:700;font-size:0.85rem;">{{ $f['name'] }}</span>
                                <span style="font-size:0.62rem;color:var(--text-secondary);">{{ number_format($f['size'] / 1024, 1) }} KB · {{ $f['lines'] }} lignes</span>
                            </div>
                            <div style="font-size:0.72rem;color:var(--text-secondary);margin-top:3px;font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $f['preview'] }}...
                            </div>
                        </div>
                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                            <button class="btn-icon" title="Editer" style="width:28px;height:28px;font-size:0.75rem;"
                                onclick="editFile('{{ $f['name'] }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('ai-context.destroy', $f['name']) }}" method="POST" onsubmit="return confirm('Supprimer {{ $f['name'] }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="Supprimer" style="width:28px;height:28px;font-size:0.75rem;color:#f85149;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-3 py-4 text-center" style="color:var(--text-secondary);font-size:0.82rem;">
                    <i class="bi bi-file-earmark-plus me-1"></i>Aucun document — uploadez ou creez un fichier de contexte
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upload + Create --}}
        <div class="col-lg-5">
            {{-- Upload --}}
            <div class="stat-card mb-3">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                    <i class="bi bi-cloud-upload me-1" style="color:#58a6ff;"></i> Uploader un fichier
                </h6>
                <form action="{{ route('ai-context.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="file" class="form-control form-control-sm" accept=".txt,.md" required>
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm w-100">
                        <i class="bi bi-upload me-1"></i>Uploader
                    </button>
                    <small style="color:var(--text-secondary);font-size:0.62rem;display:block;margin-top:4px;">Formats: .txt, .md — Max 2 MB</small>
                </form>
            </div>

            {{-- Create --}}
            <div class="stat-card mb-3">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                    <i class="bi bi-plus-circle me-1" style="color:#3fb950;"></i> Creer un document
                </h6>
                <form action="{{ route('ai-context.store') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Nom du fichier</label>
                        <input type="text" name="filename" class="form-control form-control-sm" placeholder="tarifs" required
                            style="font-family:'JetBrains Mono',monospace;">
                        <small style="color:var(--text-secondary);font-size:0.62rem;">.txt sera ajoute automatiquement</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Contenu</label>
                        <textarea name="content" class="form-control form-control-sm" rows="8" required
                            placeholder="Horaires d'ouverture:&#10;Lundi-Vendredi: 9h-18h&#10;Samedi: 10h-14h&#10;&#10;Services:&#10;- Hebergement web&#10;- Noms de domaine&#10;- Support technique"
                            style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:#3fb950;color:#fff;border:none;font-weight:600;">
                        <i class="bi bi-check-lg me-1"></i>Creer
                    </button>
                </form>
            </div>

            {{-- Info --}}
            <div class="stat-card" style="padding:0.75rem;">
                <h6 style="font-size:0.82rem;font-weight:700;margin-bottom:0.5rem;">
                    <i class="bi bi-lightbulb me-1" style="color:#d29922;"></i>Comment ca marche
                </h6>
                <ul style="font-size:0.72rem;color:var(--text-secondary);margin:0;padding-left:1rem;line-height:1.6;">
                    <li>Tous les fichiers sont <b>charges automatiquement</b> dans le contexte de l'agent IA</li>
                    <li>L'IA utilise ces informations pour <b>repondre aux questions</b> des appelants</li>
                    <li>Ajoutez vos <b>tarifs, FAQ, horaires, procedures</b></li>
                    <li>Format libre : texte brut ou Markdown</li>
                    <li>L'IA <b>refuse les sujets hors cadre</b> automatiquement</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Edit modal --}}
    <div id="editModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.6);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="width:600px;max-width:95vw;max-height:85vh;background:#1c1f26;border:1px solid var(--border);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5);display:flex;flex-direction:column;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div style="font-weight:700;" id="editTitle">Editer</div>
                <button onclick="document.getElementById('editModal').style.display='none'" style="background:none;border:none;color:var(--text-secondary);font-size:1.2rem;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
            </div>
            <form id="editForm" method="POST" style="flex:1;display:flex;flex-direction:column;">
                @csrf @method('PUT')
                <div style="flex:1;padding:1rem 1.25rem;">
                    <textarea name="content" id="editContent" style="width:100%;height:100%;min-height:300px;background:#262a33;color:#e2e4eb;border:1px solid #383c47;border-radius:8px;padding:0.75rem;font-family:'JetBrains Mono',monospace;font-size:0.78rem;resize:none;"></textarea>
                </div>
                <div style="padding:0.75rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:0.5rem;justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-sm" style="background:var(--surface-2);color:var(--text-primary);border:1px solid var(--border);">Annuler</button>
                    <button type="submit" class="btn btn-accent btn-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editFile(name) {
        document.getElementById('editTitle').textContent = 'Editer — ' + name;
        document.getElementById('editForm').action = '/ai-context/' + encodeURIComponent(name);
        document.getElementById('editContent').value = 'Chargement...';
        document.getElementById('editModal').style.display = 'flex';

        fetch('/ai-context/' + encodeURIComponent(name) + '/edit')
            .then(r => r.json())
            .then(data => {
                document.getElementById('editContent').value = data.content;
            })
            .catch(err => {
                document.getElementById('editContent').value = 'Erreur: ' + err.message;
            });
    }
    </script>
@endsection
