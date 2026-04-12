@extends('layouts.app')

@section('title', __('ui.audio_files'))
@section('page-title', __('ui.audio_files'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.audio_files") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Annonces, messages d'accueil et musiques d'attente</p>
        </div>
        <button class="btn btn-accent" onclick="document.getElementById('uploadModal').style.display='flex'">
            <i class="bi bi-cloud-upload me-1"></i> Uploader un fichier
        </button>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="audio-stat"><div class="audio-stat-val">{{ $sounds->count() }}</div><div class="audio-stat-lbl">Annonces / Sons</div></div>
        </div>
        <div class="col-md-3">
            <div class="audio-stat"><div class="audio-stat-val" style="color:var(--accent);">{{ $moh->count() }}</div><div class="audio-stat-lbl">{{ __("ui.music_on_hold") }}</div></div>
        </div>
        <div class="col-md-3">
            <div class="audio-stat"><div class="audio-stat-val">{{ $mohClasses->count() }}</div><div class="audio-stat-lbl">Classes MOH</div></div>
        </div>
        <div class="col-md-3">
            <div class="audio-stat">
                <div class="audio-stat-val">
                    @php $totalSize = $sounds->sum('file_size') + $moh->sum('file_size'); @endphp
                    {{ $totalSize > 1048576 ? round($totalSize/1048576,1).'M' : round($totalSize/1024).'K' }}
                </div>
                <div class="audio-stat-lbl">Taille totale</div>
            </div>
        </div>
    </div>

    {{-- Info --}}
    <div style="background:rgba(var(--accent-rgb), 0.08);border:1px solid rgba(var(--accent-rgb), 0.2);border-radius:10px;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.82rem;color:var(--text-secondary);">
        <i class="bi bi-info-circle me-1" style="color:var(--accent);"></i>
        Les fichiers sont automatiquement convertis en <strong>WAV 8kHz 16-bit mono PCM</strong> (format Asterisk). Formats acceptes : WAV, MP3, OGG, FLAC, M4A, AAC.
    </div>

    {{-- Sounds table --}}
    <div class="data-table mb-4">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:0.85rem;">
            <i class="bi bi-volume-up me-1" style="color:var(--accent);"></i> Annonces & Sons
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __("ui.name") }}</th>
                    <th>{{ __("ui.original_file") }}</th>
                    <th>Reference Asterisk</th>
                    <th>{{ __("ui.duration") }}</th>
                    <th>Taille</th>
                    <th style="width:120px;">{{ __("ui.actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sounds as $s)
                    <tr>
                        <td style="font-weight:600;">{{ $s->name }}</td>
                        <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $s->original_name }}</td>
                        <td><code style="color:var(--accent);font-size:0.82rem;">{{ $s->getAsteriskRef() }}</code></td>
                        <td style="font-size:0.82rem;">{{ $s->duration ? $s->duration.'s' : '—' }}</td>
                        <td style="font-size:0.82rem;">{{ $s->file_size ? round($s->file_size/1024).'K' : '—' }}</td>
                        <td>
                            <button class="btn-icon me-1" title="Ecouter" onclick="playAudio({{ $s->id }}, '{{ addslashes($s->name) }}')">
                                <i class="bi bi-play-circle"></i>
                            </button>
                            <form action="{{ route('audio.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer {{ $s->name }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3" style="color:var(--text-secondary);">{{ __('ui.no_audio_files') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MOH table --}}
    <div class="data-table">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:0.85rem;">
            <i class="bi bi-music-note-beamed me-1" style="color:var(--accent);"></i> Musiques d'attente (MOH)
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __("ui.name") }}</th>
                    <th>Classe MOH</th>
                    <th>{{ __("ui.original_file") }}</th>
                    <th>{{ __("ui.duration") }}</th>
                    <th>Taille</th>
                    <th style="width:120px;">{{ __("ui.actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($moh as $m)
                    <tr>
                        <td style="font-weight:600;">{{ $m->name }}</td>
                        <td><span class="codec-tag">{{ $m->moh_class ?: 'default' }}</span></td>
                        <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $m->original_name }}</td>
                        <td style="font-size:0.82rem;">{{ $m->duration ? $m->duration.'s' : '—' }}</td>
                        <td style="font-size:0.82rem;">{{ $m->file_size ? round($m->file_size/1024).'K' : '—' }}</td>
                        <td>
                            <button class="btn-icon me-1" title="Ecouter" onclick="playAudio({{ $m->id }}, '{{ addslashes($m->name) }}')">
                                <i class="bi bi-play-circle"></i>
                            </button>
                            <form action="{{ route('audio.destroy', $m) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer {{ $m->name }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3" style="color:var(--text-secondary);">Aucune musique d'attente uploadee</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Upload Modal --}}
    <div id="uploadModal" class="audio-modal-overlay" style="display:none;">
        <div class="audio-modal-box">
            <div class="audio-modal-header">
                <h6 style="font-weight:700;margin:0;"><i class="bi bi-cloud-upload me-2" style="color:var(--accent);"></i> Uploader un fichier audio</h6>
                <button class="btn-icon" onclick="document.getElementById('uploadModal').style.display='none'"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="{{ route('audio.upload') }}" method="POST" enctype="multipart/form-data" style="padding:1.25rem;">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="name" class="form-control" placeholder="ex: Message d'accueil" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type *</label>
                        <select name="category" id="uploadCategory" class="form-select" onchange="toggleMohClass()">
                            <option value="sound">Annonce / Son</option>
                            <option value="moh">Musique d'attente (MOH)</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="mohClassGroup" style="display:none;">
                        <label class="form-label">Classe MOH</label>
                        <input type="text" name="moh_class" class="form-control" value="default" placeholder="ex: default, premium">
                        <small style="color:var(--text-secondary);font-size:0.72rem;">Nom de la classe musiconhold.conf</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Fichier audio *</label>
                        <input type="file" name="file" class="form-control" accept=".wav,.mp3,.ogg,.flac,.m4a,.aac" required>
                        <small style="color:var(--text-secondary);font-size:0.72rem;">WAV, MP3, OGG, FLAC, M4A, AAC — max 20 Mo — sera converti en WAV 8kHz mono</small>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4 justify-content-end">
                    <button type="button" class="btn btn-outline-custom" onclick="document.getElementById('uploadModal').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-cloud-upload me-1"></i> Uploader & convertir</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Audio player --}}
    <div id="audioPlayer" style="display:none;position:fixed;bottom:1rem;right:1rem;background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:0.75rem 1rem;z-index:9999;min-width:280px;box-shadow:0 8px 30px rgba(0,0,0,0.4);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-music-note" style="color:var(--accent);"></i>
            <span id="audioPlayerName" style="font-weight:600;font-size:0.85rem;flex:1;"></span>
            <button class="btn-icon" onclick="stopAudio()" style="width:24px;height:24px;font-size:0.7rem;"><i class="bi bi-x"></i></button>
        </div>
        <audio id="audioEl" controls style="width:100%;height:32px;"></audio>
    </div>

    <style>
        .audio-stat {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 10px; padding: 1rem 1.25rem; text-align: center;
        }
        .audio-stat-val { font-size: 1.5rem; font-weight: 800; }
        .audio-stat-lbl { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.15rem; }
        .audio-modal-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
        }
        .audio-modal-box {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 14px; width: 520px; max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .audio-modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
        }
    </style>

    <script>
        function toggleMohClass() {
            document.getElementById('mohClassGroup').style.display =
                document.getElementById('uploadCategory').value === 'moh' ? '' : 'none';
        }

        function playAudio(id, name) {
            const player = document.getElementById('audioPlayer');
            const el = document.getElementById('audioEl');
            document.getElementById('audioPlayerName').textContent = name;
            el.src = '/audio/' + id + '/play';
            player.style.display = '';
            el.play();
        }

        function stopAudio() {
            const el = document.getElementById('audioEl');
            el.pause();
            el.src = '';
            document.getElementById('audioPlayer').style.display = 'none';
        }

        // Close modal on overlay click
        document.getElementById('uploadModal')?.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
@endsection
