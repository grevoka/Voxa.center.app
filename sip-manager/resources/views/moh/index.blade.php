@extends('layouts.app')

@section('title', __('ui.music_on_hold')\')
@section('page-title', __('ui.music_on_hold')\')

@push('styles')
<style>
    .moh-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:1.25rem; }
    .moh-tab {
        padding:0.65rem 1.25rem; font-size:0.82rem; font-weight:600; color:var(--text-secondary);
        cursor:pointer; border-bottom:2px solid transparent; transition:all .15s;
        display:flex; align-items:center; gap:0.4rem; user-select:none;
    }
    .moh-tab:hover { color:var(--text-primary); }
    .moh-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
    .moh-tab .tab-count {
        font-size:0.65rem; font-weight:700; background:rgba(var(--accent-rgb),0.12);
        color:var(--accent); padding:0 6px; border-radius:10px; min-width:18px; text-align:center;
    }
    .moh-panel { display:none; }
    .moh-panel.active { display:block; }

    .pl-track {
        display:flex; align-items:center; gap:0.5rem; padding:0.45rem 0.75rem;
        border-bottom:1px solid var(--border); font-size:0.82rem; transition:background .1s;
    }
    .pl-track:last-child { border-bottom:none; }
    .pl-track:hover { background:rgba(var(--accent-rgb),0.03); }
    .pl-track .pl-grip { cursor:grab; color:var(--text-secondary); font-size:0.75rem; padding:0 0.2rem; }
    .pl-track .pl-grip:active { cursor:grabbing; }
    .pl-track .pl-num { color:var(--text-secondary); font-size:0.7rem; font-family:'JetBrains Mono',monospace; min-width:22px; text-align:right; }
    .pl-track.dragging { opacity:0.4; background:rgba(var(--accent-rgb),0.08); }
    .pl-track.drag-over { border-top:2px solid var(--accent); }

    .file-picker { cursor:pointer; display:flex; align-items:center; gap:0.5rem; padding:0.4rem 0.65rem; border-radius:6px; background:var(--surface-3); border:1px solid var(--border); margin-bottom:0.35rem; transition:all .15s; }
    .file-picker:hover { border-color:var(--accent-mid); }
    .file-picker.selected { border-color:var(--accent); background:rgba(var(--accent-rgb),0.06); }
    .file-picker .fp-name { flex:1; font-size:0.82rem; }
    .file-picker .fp-check { color:var(--accent); font-size:0.85rem; }

    /* Playlist row in list */
    .pl-row {
        display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1rem;
        border-bottom:1px solid var(--border); transition:background .1s;
    }
    .pl-row:last-child { border-bottom:none; }
    .pl-row:hover { background:rgba(var(--accent-rgb),0.02); }

    /* Modal */
    .moh-modal-overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000;
        align-items:center; justify-content:center; backdrop-filter:blur(3px);
    }
    .moh-modal-overlay.open { display:flex; }
    .moh-modal {
        background:var(--surface-2); border:1px solid var(--border); border-radius:14px;
        width:90%; max-width:680px; max-height:85vh; overflow:hidden; display:flex; flex-direction:column;
        box-shadow:0 20px 60px rgba(0,0,0,0.5);
    }
    .moh-modal-header {
        display:flex; align-items:center; gap:0.75rem; padding:1rem 1.25rem;
        border-bottom:1px solid var(--border); flex-shrink:0;
    }
    .moh-modal-header h6 { font-weight:700; font-size:0.95rem; margin:0; flex:1; }
    .moh-modal-body { padding:1rem 1.25rem; overflow-y:auto; flex:1; }
    .moh-modal-footer {
        display:flex; justify-content:flex-end; gap:0.5rem; padding:0.75rem 1.25rem;
        border-top:1px solid var(--border); flex-shrink:0;
    }
</style>
@endpush

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.music_on_hold") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerez les sources audio pour la musique d'attente</p>
        </div>
    </div>

    {{-- Current default source --}}
    <div class="stat-card mb-4" style="padding:1rem 1.25rem;">
        <form action="{{ route('moh.set-default') }}" method="POST" style="display:flex; align-items:center; gap:0.75rem;">
            @csrf
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(var(--accent-rgb),0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-music-note-beamed" style="color:var(--accent); font-size:1.1rem;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); font-weight:600; margin-bottom:0.3rem;">Source par defaut</div>
                <select class="form-select" name="source" id="mohDefaultSource" style="max-width:400px;">
                    <option value="rotation" {{ $currentSource === 'rotation' ? 'selected' : '' }}>
                        Rotation automatique — tous les fichiers
                    </option>
                    @if(count($files))
                        <optgroup label="Fichier unique">
                            @foreach($files as $file)
                                <option value="file:{{ $file['name'] }}" {{ $currentSource === 'file:' . $file['name'] ? 'selected' : '' }}>
                                    {{ $file['display_name'] }} ({{ strtoupper($file['ext']) }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($playlists->where('enabled', true)->count())
                        <optgroup label="Playlists">
                            @foreach($playlists->where('enabled', true) as $pl)
                                <option value="playlist:{{ $pl->id }}" {{ $currentSource === 'playlist:' . $pl->id ? 'selected' : '' }}>
                                    {{ $pl->display_name ?: $pl->name }} ({{ count($pl->files ?? []) }} titres)
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($streams->where('enabled', true)->count())
                        <optgroup label="Flux streaming">
                            @foreach($streams->where('enabled', true) as $st)
                                <option value="stream:{{ $st->id }}" {{ $currentSource === 'stream:' . $st->id ? 'selected' : '' }}>
                                    {{ $st->display_name ?: $st->name }} (stream)
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>
            <button type="submit" class="btn btn-accent" style="font-size:0.82rem; flex-shrink:0;">
                <i class="bi bi-check-lg me-1"></i> Appliquer
            </button>
        </form>
    </div>

    {{-- Tabs --}}
    <div class="moh-tabs">
        <div class="moh-tab active" data-tab="files">
            <i class="bi bi-music-note-list"></i> Fichiers
            <span class="tab-count">{{ count($files) }}</span>
        </div>
        <div class="moh-tab" data-tab="playlists">
            <i class="bi bi-collection-play"></i> Playlists
            <span class="tab-count">{{ $playlists->count() }}</span>
        </div>
        <div class="moh-tab" data-tab="new-playlist">
            <i class="bi bi-plus-circle"></i> Nouvelle playlist
        </div>
        <div class="moh-tab" data-tab="streams">
            <i class="bi bi-broadcast"></i> Streaming
            <span class="tab-count">{{ $streams->count() }}</span>
        </div>
    </div>

    {{-- ═══ TAB: Fichiers ═══ --}}
    <div class="moh-panel active" id="panel-files">
        <div class="data-table">
            @if(count($files))
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Musique</th>
                            <th>Format</th>
                            <th>Taille</th>
                            <th style="width:100px;">{{ __("ui.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                            @php $isActive = $currentSource === 'file:' . $file['name']; @endphp
                            <tr style="{{ $isActive ? 'background:rgba(41,182,246,0.04);' : '' }}">
                                <td>
                                    @if($isActive)
                                        <i class="bi bi-check-circle-fill" style="color:var(--accent); font-size:1.1rem;"></i>
                                    @else
                                        <i class="bi bi-music-note" style="color:var(--text-secondary);"></i>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <span style="font-weight:600; font-size:0.85rem;">{{ $file['display_name'] }}</span>
                                        @if($isActive)
                                            <span style="font-size:0.6rem; font-weight:600; padding:1px 6px; border-radius:4px; background:rgba(41,182,246,0.12); color:#29b6f6; text-transform:uppercase;">Par defaut</span>
                                        @endif
                                    </div>
                                    <div style="font-size:0.7rem; color:var(--text-secondary); font-family:'JetBrains Mono',monospace;">{{ $file['file'] }}</div>
                                </td>
                                <td><span class="codec-tag">{{ strtoupper($file['ext']) }}</span></td>
                                <td style="font-size:0.82rem; color:var(--text-secondary);">{{ $file['size_human'] }}</td>
                                <td>
                                    <div style="display:flex; gap:4px;">
                                        @if($file['playable'])
                                            <button class="btn-icon" onclick="playMoh('default', '{{ $file['name'] }}')" title="Ecouter"><i class="bi bi-play-fill"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-4" style="color:var(--text-secondary);">
                    <i class="bi bi-music-note me-2"></i>Aucun fichier audio dans /var/lib/asterisk/moh/
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ TAB: Playlists (liste compacte) ═══ --}}
    <div class="moh-panel" id="panel-playlists">
        @if($playlists->count())
            <div class="data-table">
                @foreach($playlists as $playlist)
                    <div class="pl-row" style="{{ !$playlist->enabled ? 'opacity:0.5;' : '' }}">
                        <div style="width:40px;height:40px;border-radius:10px;background:{{ $playlist->enabled ? 'rgba(var(--accent-rgb),0.12)' : 'rgba(255,255,255,0.04)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-collection-play" style="color:{{ $playlist->enabled ? 'var(--accent)' : 'var(--text-secondary)' }}; font-size:1.1rem;"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:0.9rem;">
                                {{ $playlist->display_name ?: $playlist->name }}
                                @if($playlist->enabled)
                                    <span style="font-size:0.6rem; font-weight:600; padding:1px 6px; border-radius:4px; background:rgba(41,182,246,0.12); color:#29b6f6; text-transform:uppercase; margin-left:0.3rem;">Actif</span>
                                @endif
                            </div>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <span class="codec-tag" style="font-size:0.62rem;">{{ $playlist->getMohClassName() }}</span>
                                <span style="font-size:0.72rem; color:var(--text-secondary);">{{ count($playlist->files ?? []) }} titres</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:4px; flex-shrink:0;">
                            <button type="button" class="btn-icon" title="Editer" style="width:28px;height:28px;font-size:0.75rem;"
                                    onclick="openPlModal({{ $playlist->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('moh.playlists.toggle', $playlist) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon" title="{{ $playlist->enabled ? 'Desactiver' : 'Activer' }}" style="width:28px;height:28px;font-size:0.75rem;"><i class="bi bi-power"></i></button>
                            </form>
                            <form action="{{ route('moh.playlists.destroy', $playlist) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Supprimer la playlist {{ $playlist->name }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer" style="width:28px;height:28px;font-size:0.75rem;"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="stat-card text-center py-4" style="color:var(--text-secondary);">
                <i class="bi bi-collection-play me-2" style="font-size:1.5rem;"></i>
                <div style="margin-top:0.5rem; font-size:0.85rem;">{{ __('ui.no_playlist') }}</div>
                <div style="font-size:0.75rem;">Allez sur l'onglet "Nouvelle playlist" pour en creer une</div>
            </div>
        @endif
    </div>

    {{-- ═══ MODAL: Editer playlist ═══ --}}
    @foreach($playlists as $playlist)
        <div class="moh-modal-overlay" id="plModal{{ $playlist->id }}">
            <div class="moh-modal">
                <div class="moh-modal-header">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(var(--accent-rgb),0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-collection-play" style="color:var(--accent);"></i>
                    </div>
                    <h6>{{ $playlist->display_name ?: $playlist->name }}</h6>
                    <span class="codec-tag" style="font-size:0.62rem;">{{ $playlist->getMohClassName() }}</span>
                    <button type="button" class="btn-icon" onclick="closePlModal({{ $playlist->id }})" style="width:28px;height:28px;font-size:0.75rem;"><i class="bi bi-x-lg"></i></button>
                </div>

                <form action="{{ route('moh.playlists.update', $playlist) }}" method="POST" class="pl-form" data-id="{{ $playlist->id }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="display_name" value="{{ $playlist->display_name }}">

                    <div class="moh-modal-body">
                        <label class="form-label" style="font-size:0.8rem; font-weight:600;">
                            <i class="bi bi-list-ol me-1" style="color:var(--accent);"></i>
                            Pistes ({{ count($playlist->files ?? []) }})
                        </label>
                        <div class="pl-tracklist" data-id="{{ $playlist->id }}" style="border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:1rem;">
                            @foreach($playlist->files ?? [] as $idx => $f)
                                @php
                                    $dn = ucfirst(str_replace(['_', '-'], ' ', pathinfo($f, PATHINFO_FILENAME)));
                                    $ext = strtoupper(pathinfo($f, PATHINFO_EXTENSION));
                                @endphp
                                <div class="pl-track" draggable="true" data-file="{{ $f }}">
                                    <span class="pl-grip"><i class="bi bi-grip-vertical"></i></span>
                                    <span class="pl-num">{{ $idx + 1 }}.</span>
                                    <i class="bi bi-music-note" style="color:var(--accent); font-size:0.8rem;"></i>
                                    <span style="flex:1;">{{ $dn }}</span>
                                    <span class="codec-tag" style="font-size:0.6rem;">{{ $ext }}</span>
                                    <button type="button" class="btn-icon" onclick="playMoh('default','{{ pathinfo($f, PATHINFO_FILENAME) }}')" style="width:22px;height:22px;font-size:0.65rem;" title="Ecouter"><i class="bi bi-play-fill"></i></button>
                                    <button type="button" class="btn-icon danger" onclick="removeTrack(this, {{ $playlist->id }})" style="width:22px;height:22px;font-size:0.65rem;" title="Retirer"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        </div>

                        {{-- Add tracks --}}
                        <label class="form-label" style="font-size:0.8rem; font-weight:600;">
                            <i class="bi bi-plus-circle me-1" style="color:var(--accent);"></i>
                            Ajouter des titres
                        </label>
                        <div id="addTracks{{ $playlist->id }}" style="max-height:200px; overflow-y:auto;">
                            @foreach($files as $file)
                                @php $inPl = in_array($file['name'], $playlist->files ?? []); @endphp
                                @if(!$inPl)
                                    <div class="file-picker" onclick="addTrackToPlaylist({{ $playlist->id }}, '{{ $file['name'] }}', '{{ addslashes($file['display_name']) }}', '{{ strtoupper($file['ext']) }}'); this.remove();">
                                        <i class="bi bi-plus-circle fp-check"></i>
                                        <span class="fp-name">{{ $file['display_name'] }}</span>
                                        <span class="codec-tag" style="font-size:0.6rem;">{{ strtoupper($file['ext']) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="moh-modal-footer">
                        <div class="pl-hidden-inputs"></div>
                        <button type="button" class="btn-outline-custom" onclick="closePlModal({{ $playlist->id }})">Annuler</button>
                        <button type="submit" class="btn btn-accent" onclick="preparePlFiles({{ $playlist->id }})">
                            <i class="bi bi-check-lg me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- ═══ TAB: Nouvelle playlist ═══ --}}
    <div class="moh-panel" id="panel-new-playlist">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="stat-card">
                    <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                        <i class="bi bi-plus-circle me-1" style="color:var(--accent);"></i> {{ __('ui.create_playlist') }}
                    </h6>
                    <form action="{{ route('moh.playlists.store') }}" method="POST" id="newPlForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nom technique *</label>
                                <input type="text" class="form-control" name="name" required placeholder="ambiance-jazz" pattern="[a-zA-Z0-9_-]+">
                                <small style="color:var(--text-secondary); font-size:0.7rem;">Genere la classe <code>playlist-xxx</code></small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nom affiche</label>
                                <input type="text" class="form-control" name="display_name" placeholder="Ambiance Jazz">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-accent w-100">
                                    <i class="bi bi-check-lg me-1"></i> Creer la playlist
                                </button>
                            </div>
                        </div>
                        <div id="newPlHidden"></div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="stat-card">
                    <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:0.75rem;">
                        <i class="bi bi-music-note-list me-1" style="color:var(--accent);"></i>
                        Selectionnez les fichiers
                        <span id="newPlCount" style="color:var(--accent); font-weight:400; font-size:0.78rem;"></span>
                    </h6>

                    {{-- Selected tracks (sortable) --}}
                    <div id="newPlTracks" style="min-height:50px; border:1px dashed var(--border); border-radius:8px; padding:0.5rem; margin-bottom:0.75rem;">
                        <div id="newPlEmpty" style="text-align:center; padding:1rem; color:var(--text-secondary); font-size:0.78rem;">
                            <i class="bi bi-arrow-down me-1"></i> Cliquez sur les fichiers ci-dessous pour les ajouter
                        </div>
                    </div>

                    {{-- Available files --}}
                    <div style="max-height:300px; overflow-y:auto;">
                        @foreach($files as $file)
                            <div class="file-picker" id="newPick_{{ $file['name'] }}" onclick="newPlAdd('{{ $file['name'] }}', '{{ addslashes($file['display_name']) }}', '{{ strtoupper($file['ext']) }}')">
                                <i class="bi bi-plus-circle fp-check"></i>
                                <span class="fp-name">{{ $file['display_name'] }}</span>
                                <span class="codec-tag" style="font-size:0.6rem;">{{ strtoupper($file['ext']) }}</span>
                                @if($file['playable'])
                                    <button type="button" class="btn-icon" onclick="event.stopPropagation(); playMoh('default','{{ $file['name'] }}')" style="width:24px;height:24px;font-size:0.7rem;"><i class="bi bi-play-fill"></i></button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ TAB: Streaming ═══ --}}
    <div class="moh-panel" id="panel-streams">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="data-table">
                    <div style="padding:0.75rem 1rem; border-bottom:1px solid var(--border); font-weight:700; font-size:0.85rem; display:flex; align-items:center; gap:0.5rem;">
                        <i class="bi bi-broadcast" style="color:var(--accent);"></i> Flux actifs
                        <span class="nav-badge" style="font-size:0.7rem;">{{ $streams->count() }}</span>
                    </div>
                    @if($streams->count())
                        @foreach($streams as $stream)
                            <div style="padding:0.75rem 1rem; border-bottom:1px solid var(--border); {{ !$stream->enabled ? 'opacity:0.45;' : '' }}">
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    <div style="width:36px;height:36px;border-radius:8px;background:{{ $stream->enabled ? 'rgba(var(--accent-rgb),0.12)' : 'rgba(255,255,255,0.04)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-broadcast" style="color:{{ $stream->enabled ? 'var(--accent)' : 'var(--text-secondary)' }};"></i>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:600; font-size:0.85rem;">{{ $stream->display_name ?: $stream->name }}
                                            @if($stream->enabled) <span class="status-dot online" style="width:6px;height:6px;display:inline-block;"></span> @endif
                                        </div>
                                        <div style="font-size:0.68rem; color:var(--text-secondary); font-family:'JetBrains Mono',monospace; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $stream->url }}">{{ $stream->url }}</div>
                                        <span class="codec-tag" style="font-size:0.62rem; margin-top:0.2rem; display:inline-block;">{{ $stream->getMohClassName() }}</span>
                                    </div>
                                    <div style="display:flex; gap:3px; flex-shrink:0;">
                                        <form action="{{ route('moh.streams.toggle', $stream) }}" method="POST" class="d-inline">@csrf
                                            <button type="submit" class="btn-icon" title="{{ $stream->enabled ? 'Desactiver' : 'Activer' }}" style="width:28px;height:28px;font-size:0.75rem;"><i class="bi bi-power"></i></button>
                                        </form>
                                        <form action="{{ route('moh.streams.destroy', $stream) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer le flux {{ $stream->name }} ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon danger" title="Supprimer" style="width:28px;height:28px;font-size:0.75rem;"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4" style="color:var(--text-secondary); font-size:0.82rem;">
                            <i class="bi bi-broadcast me-2"></i>Aucun flux configure
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-5">
                <div class="stat-card">
                    <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                        <i class="bi bi-plus-circle me-1" style="color:var(--accent);"></i> Nouveau flux
                    </h6>
                    <form action="{{ route('moh.streams.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nom technique *</label>
                                <input type="text" class="form-control" name="name" required placeholder="lofi-radio" pattern="[a-zA-Z0-9_-]+">
                                <small style="color:var(--text-secondary); font-size:0.7rem;">Genere la classe <code>stream-xxx</code></small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nom affiche</label>
                                <input type="text" class="form-control" name="display_name" placeholder="Radio Lofi Chill">
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL du flux *</label>
                                <input type="url" class="form-control" name="url" required placeholder="https://stream.example.com/radio.mp3" style="font-family:'JetBrains Mono',monospace; font-size:0.82rem;">
                                <small style="color:var(--text-secondary); font-size:0.7rem;">HTTP/HTTPS — MP3, AAC, OGG (decode via ffmpeg)</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-accent w-100"><i class="bi bi-check-lg me-1"></i> Ajouter le flux</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Audio player --}}
    <div id="mohPlayer" style="display:none; position:fixed; bottom:1rem; right:1rem; background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:0.75rem 1rem; box-shadow:0 8px 30px rgba(0,0,0,0.4); z-index:999; min-width:280px;">
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
            <i class="bi bi-music-note-beamed" style="color:var(--accent);"></i>
            <span id="mohPlayerTitle" style="font-size:0.82rem; font-weight:600; flex:1;"></span>
            <button class="btn-icon" onclick="stopMoh()" style="width:24px;height:24px;font-size:0.7rem;"><i class="bi bi-x-lg"></i></button>
        </div>
        <audio id="mohAudio" controls style="width:100%; height:32px;"></audio>
    </div>

    <script>
    // ── Tabs ──
    document.querySelectorAll('.moh-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.moh-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.moh-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('panel-' + this.dataset.tab).classList.add('active');
            localStorage.setItem('moh_tab', this.dataset.tab);
        });
    });
    const savedTab = localStorage.getItem('moh_tab');
    if (savedTab) { const t = document.querySelector('.moh-tab[data-tab="'+savedTab+'"]'); if (t) t.click(); }

    // ── Audio ──
    function playMoh(c, f) {
        const a = document.getElementById('mohAudio'), p = document.getElementById('mohPlayer');
        a.src = '/moh/'+c+'/'+f+'/play';
        document.getElementById('mohPlayerTitle').textContent = f.replace(/[_-]/g,' ');
        p.style.display = 'block'; a.play();
    }
    function stopMoh() {
        const a = document.getElementById('mohAudio'); a.pause(); a.src = '';
        document.getElementById('mohPlayer').style.display = 'none';
    }

    // ── Modal ──
    function openPlModal(id) {
        document.getElementById('plModal' + id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closePlModal(id) {
        document.getElementById('plModal' + id).classList.remove('open');
        document.body.style.overflow = '';
    }
    // Close on overlay click
    document.querySelectorAll('.moh-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    });
    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.moh-modal-overlay.open').forEach(m => {
                m.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
    });

    // ── Drag & drop for playlist modals ──
    document.querySelectorAll('.pl-tracklist').forEach(list => {
        let dragEl = null;
        list.addEventListener('dragstart', e => {
            dragEl = e.target.closest('.pl-track');
            if (dragEl) { dragEl.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
        });
        list.addEventListener('dragend', e => {
            if (dragEl) dragEl.classList.remove('dragging');
            list.querySelectorAll('.pl-track').forEach(t => t.classList.remove('drag-over'));
            renumberTracks(list);
        });
        list.addEventListener('dragover', e => {
            e.preventDefault();
            const target = e.target.closest('.pl-track');
            list.querySelectorAll('.pl-track').forEach(t => t.classList.remove('drag-over'));
            if (target && target !== dragEl) target.classList.add('drag-over');
        });
        list.addEventListener('drop', e => {
            e.preventDefault();
            const target = e.target.closest('.pl-track');
            if (target && target !== dragEl && dragEl) {
                const rect = target.getBoundingClientRect();
                const mid = rect.top + rect.height / 2;
                if (e.clientY < mid) { list.insertBefore(dragEl, target); }
                else { list.insertBefore(dragEl, target.nextSibling); }
            }
            renumberTracks(list);
        });
    });

    function renumberTracks(list) {
        list.querySelectorAll('.pl-track').forEach((t, i) => {
            t.querySelector('.pl-num').textContent = (i+1) + '.';
        });
    }

    function removeTrack(btn, plId) {
        const track = btn.closest('.pl-track');
        const list = track.parentElement;
        track.remove();
        renumberTracks(list);
    }

    function preparePlFiles(plId) {
        const form = document.querySelector('.pl-form[data-id="'+plId+'"]');
        const container = form.querySelector('.pl-hidden-inputs');
        container.innerHTML = '';
        form.querySelectorAll('.pl-track').forEach((t, i) => {
            container.innerHTML += '<input type="hidden" name="files['+i+']" value="'+t.dataset.file+'">';
        });
    }

    function addTrackToPlaylist(plId, fileName, displayName, ext) {
        const list = document.querySelector('.pl-tracklist[data-id="'+plId+'"]');
        const num = list.querySelectorAll('.pl-track').length + 1;
        const div = document.createElement('div');
        div.className = 'pl-track';
        div.draggable = true;
        div.dataset.file = fileName;
        var baseName = fileName.replace(/\.[^.]+$/, '');
        div.innerHTML = '<span class="pl-grip"><i class="bi bi-grip-vertical"></i></span>'
            + '<span class="pl-num">'+num+'.</span>'
            + '<i class="bi bi-music-note" style="color:var(--accent);font-size:0.8rem;"></i>'
            + '<span style="flex:1;">'+displayName+'</span>'
            + '<span class="codec-tag" style="font-size:0.6rem;">'+ext+'</span>'
            + '<button type="button" class="btn-icon" onclick="playMoh(\'default\',\''+baseName+'\')" style="width:22px;height:22px;font-size:0.65rem;" title="Ecouter"><i class="bi bi-play-fill"></i></button>'
            + '<button type="button" class="btn-icon danger" onclick="removeTrack(this,'+plId+')" style="width:22px;height:22px;font-size:0.65rem;" title="Retirer"><i class="bi bi-x-lg"></i></button>';
        list.appendChild(div);
    }

    // ── New playlist builder ──
    let newPlFiles = [];

    function newPlAdd(fileName, displayName, ext) {
        if (newPlFiles.includes(fileName)) return;
        newPlFiles.push(fileName);
        const picker = document.getElementById('newPick_' + fileName);
        if (picker) { picker.classList.add('selected'); picker.querySelector('.fp-check').className = 'bi bi-check-circle-fill fp-check'; }

        document.getElementById('newPlEmpty').style.display = 'none';
        const container = document.getElementById('newPlTracks');
        const num = newPlFiles.length;
        const div = document.createElement('div');
        div.className = 'pl-track';
        div.draggable = true;
        div.dataset.file = fileName;
        var baseName = fileName.replace(/\.[^.]+$/, '');
        div.innerHTML = '<span class="pl-grip"><i class="bi bi-grip-vertical"></i></span>'
            + '<span class="pl-num">'+num+'.</span>'
            + '<i class="bi bi-music-note" style="color:var(--accent);font-size:0.8rem;"></i>'
            + '<span style="flex:1;">'+displayName+'</span>'
            + '<span class="codec-tag" style="font-size:0.6rem;">'+ext+'</span>'
            + '<button type="button" class="btn-icon" onclick="playMoh(\'default\',\''+baseName+'\')" style="width:22px;height:22px;font-size:0.65rem;" title="Ecouter"><i class="bi bi-play-fill"></i></button>'
            + '<button type="button" class="btn-icon danger" onclick="newPlRemove(this, \''+fileName+'\')" style="width:22px;height:22px;font-size:0.65rem;"><i class="bi bi-x-lg"></i></button>';
        container.appendChild(div);
        document.getElementById('newPlCount').textContent = '(' + newPlFiles.length + ' selectionne' + (newPlFiles.length > 1 ? 's' : '') + ')';
    }

    function newPlRemove(btn, fileName) {
        newPlFiles = newPlFiles.filter(f => f !== fileName);
        btn.closest('.pl-track').remove();
        const picker = document.getElementById('newPick_' + fileName);
        if (picker) { picker.classList.remove('selected'); picker.querySelector('.fp-check').className = 'bi bi-plus-circle fp-check'; }
        if (newPlFiles.length === 0) document.getElementById('newPlEmpty').style.display = 'block';
        document.getElementById('newPlCount').textContent = newPlFiles.length ? '(' + newPlFiles.length + ' selectionne' + (newPlFiles.length > 1 ? 's' : '') + ')' : '';
        document.querySelectorAll('#newPlTracks .pl-track').forEach((t,i) => { t.querySelector('.pl-num').textContent = (i+1)+'.'; });
    }

    // Drag & drop for new playlist
    const newPlContainer = document.getElementById('newPlTracks');
    let newDragEl = null;
    newPlContainer.addEventListener('dragstart', e => { newDragEl = e.target.closest('.pl-track'); if(newDragEl) newDragEl.classList.add('dragging'); });
    newPlContainer.addEventListener('dragend', e => { if(newDragEl) newDragEl.classList.remove('dragging'); newPlContainer.querySelectorAll('.pl-track').forEach(t=>t.classList.remove('drag-over')); newPlContainer.querySelectorAll('.pl-track').forEach((t,i)=>{t.querySelector('.pl-num').textContent=(i+1)+'.';}); });
    newPlContainer.addEventListener('dragover', e => { e.preventDefault(); const t=e.target.closest('.pl-track'); newPlContainer.querySelectorAll('.pl-track').forEach(x=>x.classList.remove('drag-over')); if(t&&t!==newDragEl) t.classList.add('drag-over'); });
    newPlContainer.addEventListener('drop', e => { e.preventDefault(); const t=e.target.closest('.pl-track'); if(t&&t!==newDragEl&&newDragEl){const r=t.getBoundingClientRect();if(e.clientY<r.top+r.height/2) newPlContainer.insertBefore(newDragEl,t); else newPlContainer.insertBefore(newDragEl,t.nextSibling);} newPlContainer.querySelectorAll('.pl-track').forEach((t,i)=>{t.querySelector('.pl-num').textContent=(i+1)+'.';}); newPlFiles=[]; newPlContainer.querySelectorAll('.pl-track').forEach(t=>newPlFiles.push(t.dataset.file)); });

    document.getElementById('newPlForm').addEventListener('submit', function(e) {
        newPlFiles = [];
        newPlContainer.querySelectorAll('.pl-track').forEach(t => newPlFiles.push(t.dataset.file));
        if (newPlFiles.length === 0) { e.preventDefault(); alert('Selectionnez au moins un fichier.'); return; }
        const h = document.getElementById('newPlHidden');
        h.innerHTML = '';
        newPlFiles.forEach((f,i) => { h.innerHTML += '<input type="hidden" name="files['+i+']" value="'+f+'">'; });
    });
    </script>
@endsection
