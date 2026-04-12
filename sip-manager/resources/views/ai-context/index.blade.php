@extends('layouts.app')

@section('title', 'Base de connaissances AI')
@section('page-title', 'Base de connaissances AI')

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Base de connaissances AI</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Organisez vos documents RAG par dossier pour cibler le contexte de chaque agent IA</p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:#f8514915;border:1px solid #f8514930;border-radius:8px;padding:0.5rem 1rem;margin-bottom:1rem;font-size:0.78rem;color:#f85149;">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="row g-4">
        {{-- Dossiers + Fichiers --}}
        <div class="col-lg-7">
            {{-- Dossiers --}}
            <div class="data-table mb-3">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-folder-fill me-1" style="color:#d29922;"></i> Dossiers RAG
                    </h6>
                    <button class="btn btn-sm" style="background:#d2992220;color:#d29922;border:1px solid #d2992240;font-size:0.72rem;font-weight:600;"
                        onclick="document.getElementById('newFolderForm').style.display=document.getElementById('newFolderForm').style.display==='none'?'block':'none'">
                        <i class="bi bi-folder-plus me-1"></i>Nouveau
                    </button>
                </div>

                {{-- New folder form --}}
                <form id="newFolderForm" action="{{ route('ai-context.folders.store') }}" method="POST" class="px-3 py-2" style="display:none;border-bottom:1px solid var(--border);background:rgba(210,153,34,0.03);">
                    @csrf
                    <div class="d-flex gap-2">
                        <input type="text" name="folder_name" class="form-control form-control-sm" placeholder="nom-du-dossier" required
                            style="font-family:'JetBrains Mono',monospace;" pattern="[a-zA-Z0-9_-]+">
                        <button type="submit" class="btn btn-sm" style="background:#d29922;color:#fff;border:none;white-space:nowrap;">Creer</button>
                    </div>
                </form>

                {{-- General (root) --}}
                <a href="{{ route('ai-context.index') }}" class="d-flex align-items-center gap-2 px-3 py-2 {{ $currentFolder === '' ? 'active-folder' : '' }}"
                   style="text-decoration:none;color:inherit;border-bottom:1px solid var(--border);{{ $currentFolder === '' ? 'background:var(--accent-dim);' : '' }}">
                    <i class="bi bi-folder2-open" style="color:var(--accent);"></i>
                    <span style="font-weight:600;font-size:0.82rem;">General</span>
                    <span style="font-size:0.65rem;color:var(--text-secondary);margin-left:auto;">Partage par tous les agents</span>
                </a>

                @foreach($folders as $f)
                <div class="d-flex align-items-center gap-2 px-3 py-2" style="border-bottom:1px solid var(--border);{{ $currentFolder === $f['name'] ? 'background:var(--accent-dim);' : '' }}">
                    <a href="{{ route('ai-context.index', ['folder' => $f['name']]) }}" class="d-flex align-items-center gap-2" style="text-decoration:none;color:inherit;flex:1;">
                        <i class="bi bi-folder-fill" style="color:#d29922;"></i>
                        <span style="font-weight:600;font-size:0.82rem;">{{ $f['name'] }}</span>
                        <span style="font-size:0.62rem;color:var(--text-secondary);">{{ $f['files'] }} fichiers · {{ number_format($f['size']/1024, 1) }} KB</span>
                    </a>
                    <form action="{{ route('ai-context.folders.destroy', $f['name']) }}" method="POST" onsubmit="return confirm('Supprimer le dossier {{ $f['name'] }} et tout son contenu ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-icon" title="Supprimer" style="width:24px;height:24px;font-size:0.65rem;color:#f85149;">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>

            {{-- Fichiers du dossier courant --}}
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-file-earmark-text me-1" style="color:var(--accent);"></i>
                        {{ $currentFolder ? $currentFolder : 'General' }}
                        <span style="font-size:0.65rem;color:var(--text-secondary);font-weight:400;margin-left:0.3rem;">{{ count($files) }} fichiers · {{ number_format($totalSize/1024, 1) }} KB</span>
                    </h6>
                </div>

                @forelse($files as $f)
                <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                    <div class="d-flex align-items-start justify-content-between">
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ str_ends_with($f['name'], '.md') ? 'bi-markdown' : 'bi-file-text' }}" style="color:var(--accent);"></i>
                                <span style="font-weight:700;font-size:0.82rem;">{{ $f['name'] }}</span>
                                <span style="font-size:0.62rem;color:var(--text-secondary);">{{ number_format($f['size']/1024, 1) }} KB · {{ $f['lines'] }} lignes</span>
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:2px;font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $f['preview'] }}
                            </div>
                        </div>
                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                            @php $editPath = $currentFolder ? "{$currentFolder}/{$f['name']}" : $f['name']; @endphp
                            <button class="btn-icon" title="Editer" style="width:26px;height:26px;font-size:0.7rem;"
                                onclick="editFile('{{ $editPath }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('ai-context.destroy', $editPath) }}" method="POST" onsubmit="return confirm('Supprimer ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="Supprimer" style="width:26px;height:26px;font-size:0.7rem;color:#f85149;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-3 py-4 text-center" style="color:var(--text-secondary);font-size:0.82rem;">
                    <i class="bi bi-file-earmark-plus me-1"></i>Aucun document dans ce dossier
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upload + Create --}}
        <div class="col-lg-5">
            <div class="stat-card mb-3">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                    <i class="bi bi-cloud-upload me-1" style="color:#58a6ff;"></i> Uploader
                </h6>
                <form action="{{ route('ai-context.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Dossier destination</label>
                        <select name="folder" class="form-select form-select-sm">
                            <option value="" {{ $currentFolder === '' ? 'selected' : '' }}>General</option>
                            @foreach($folders as $f)
                                <option value="{{ $f['name'] }}" {{ $currentFolder === $f['name'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Fichier</label>
                        <input type="file" name="file" class="form-control form-control-sm" accept=".txt,.md,.pdf" required>
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-upload me-1"></i>Uploader</button>
                    <small style="color:var(--text-secondary);font-size:0.62rem;">.txt, .md — Max 2 MB</small>
                </form>
            </div>

            <div class="stat-card mb-3">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
                    <i class="bi bi-plus-circle me-1" style="color:#3fb950;"></i> Creer un document
                </h6>
                <form action="{{ route('ai-context.store') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Dossier</label>
                        <select name="folder" class="form-select form-select-sm">
                            <option value="" {{ $currentFolder === '' ? 'selected' : '' }}>General</option>
                            @foreach($folders as $f)
                                <option value="{{ $f['name'] }}" {{ $currentFolder === $f['name'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Nom</label>
                        <input type="text" name="filename" class="form-control form-control-sm" placeholder="tarifs" required style="font-family:'JetBrains Mono',monospace;">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.72rem;">Contenu</label>
                        <textarea name="content" class="form-control form-control-sm" rows="8" required
                            placeholder="Horaires: Lun-Ven 9h-18h&#10;&#10;Services:&#10;- Hebergement web&#10;- Support technique"
                            style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:#3fb950;color:#fff;border:none;font-weight:600;">
                        <i class="bi bi-check-lg me-1"></i>Creer
                    </button>
                </form>
            </div>

            <div class="stat-card" style="padding:0.75rem;">
                <h6 style="font-size:0.82rem;font-weight:700;margin-bottom:0.5rem;">
                    <i class="bi bi-lightbulb me-1" style="color:#d29922;"></i>Comment ca marche
                </h6>
                <ul style="font-size:0.72rem;color:var(--text-secondary);margin:0;padding-left:1rem;line-height:1.6;">
                    <li><b>General</b> : documents charges par tous les agents IA</li>
                    <li><b>Dossiers</b> : contexte specifique a un bloc AI dans le scenario</li>
                    <li>Dans le builder, chaque bloc "Agent IA" peut choisir son dossier RAG</li>
                    <li>L'agent charge les docs du dossier + les docs generaux</li>
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
    function editFile(path) {
        document.getElementById('editTitle').textContent = 'Editer — ' + path;
        document.getElementById('editForm').action = '/ai-context/' + encodeURIComponent(path);
        document.getElementById('editContent').value = 'Chargement...';
        document.getElementById('editModal').style.display = 'flex';

        fetch('/ai-context/' + encodeURIComponent(path) + '/edit')
            .then(r => r.json())
            .then(data => { document.getElementById('editContent').value = data.content; })
            .catch(err => { document.getElementById('editContent').value = 'Erreur: ' + err.message; });
    }
    </script>
@endsection
