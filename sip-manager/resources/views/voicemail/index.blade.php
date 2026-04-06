@extends('layouts.app')

@section('title', 'Messagerie vocale')
@section('page-title', 'Messagerie vocale')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Messagerie vocale</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Ecoutez et gerez les messages vocaux</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:0.82rem; color:var(--text-secondary); white-space:nowrap;">Boite :</label>
            <select class="form-select form-select-sm" style="width:auto; min-width:180px;" onchange="window.location='?extension='+this.value">
                @foreach($lines as $line)
                    <option value="{{ $line->extension }}" {{ $selectedExt == $line->extension ? 'selected' : '' }}>
                        {{ $line->extension }} — {{ $line->name ?: $line->extension }}
                    </option>
                @endforeach
                @if($lines->isEmpty())
                    <option disabled>Aucune ligne avec messagerie</option>
                @endif
            </select>
        </div>
    </div>

    @if($selectedExt)
        {{-- Stats --}}
        @php
            $newCount = collect($messages)->where('folder', 'INBOX')->count();
            $oldCount = collect($messages)->where('folder', 'Old')->count();
            $totalDuration = collect($messages)->sum('duration');
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center" style="padding:1rem;">
                    <div style="font-size:1.5rem; font-weight:800;">{{ count($messages) }}</div>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">Messages</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="padding:1rem;">
                    <div style="font-size:1.5rem; font-weight:800; color:var(--accent);">{{ $newCount }}</div>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">Nouveaux</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="padding:1rem;">
                    <div style="font-size:1.5rem; font-weight:800;">{{ $oldCount }}</div>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">Lus</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="padding:1rem;">
                    <div style="font-size:1.5rem; font-weight:800;">
                        @if($totalDuration >= 60)
                            {{ floor($totalDuration / 60) }}m{{ $totalDuration % 60 }}s
                        @else
                            {{ $totalDuration }}s
                        @endif
                    </div>
                    <div style="font-size:0.75rem; color:var(--text-secondary);">Duree totale</div>
                </div>
            </div>
        </div>

        @if(empty($messages))
            <div class="stat-card text-center" style="padding:3rem;">
                <i class="bi bi-voicemail" style="font-size:3rem; color:var(--text-secondary); opacity:.3;"></i>
                <p style="color:var(--text-secondary); margin-top:1rem;">Aucun message vocal pour le poste {{ $selectedExt }}.</p>
            </div>
        @else
            <div class="data-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px;"></th>
                            <th>Appelant</th>
                            <th>Date</th>
                            <th>Duree</th>
                            <th>Statut</th>
                            <th style="width:200px;">Ecouter</th>
                            <th style="width:60px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $msg)
                            <tr>
                                <td>
                                    @if($msg['folder'] === 'INBOX')
                                        <span style="width:8px;height:8px;border-radius:50%;background:var(--accent);display:inline-block;" title="Nouveau"></span>
                                    @else
                                        <span style="width:8px;height:8px;border-radius:50%;background:var(--text-secondary);opacity:.3;display:inline-block;" title="Lu"></span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:600; font-size:0.85rem;">{{ $msg['callerid'] }}</div>
                                </td>
                                <td>
                                    @if($msg['origtime'])
                                        <span style="font-size:0.82rem;">{{ \Carbon\Carbon::createFromTimestamp($msg['origtime'])->format('d/m/Y H:i') }}</span>
                                        <br><small style="color:var(--text-secondary); font-size:0.7rem;">{{ \Carbon\Carbon::createFromTimestamp($msg['origtime'])->diffForHumans() }}</small>
                                    @else
                                        <span style="color:var(--text-secondary); font-size:0.82rem;">{{ $msg['origdate'] ?: '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-family:'JetBrains Mono',monospace; font-size:0.82rem;">
                                        @if($msg['duration'] >= 60)
                                            {{ floor($msg['duration'] / 60) }}:{{ str_pad($msg['duration'] % 60, 2, '0', STR_PAD_LEFT) }}
                                        @else
                                            0:{{ str_pad($msg['duration'], 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="codec-tag" style="{{ $msg['folder'] === 'INBOX' ? 'color:var(--accent); border-color:var(--accent-mid);' : '' }}">
                                        {{ $msg['folder_label'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($msg['has_audio'])
                                        <button class="btn-icon me-1" title="Ecouter"
                                                onclick="playVm('{{ $selectedExt }}', '{{ $msg['folder'] }}', '{{ $msg['id'] }}', '{{ addslashes($msg['callerid']) }}')">
                                            <i class="bi bi-play-circle"></i>
                                        </button>
                                    @else
                                        <span style="color:var(--text-secondary); font-size:0.75rem;">Pas d'audio</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('voicemail.destroy', [$selectedExt, $msg['folder'], $msg['id']]) }}" method="POST"
                                          onsubmit="return confirm('Supprimer ce message ?')" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Supprimer">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- Audio player --}}
    <div id="vmPlayer" style="display:none;position:fixed;bottom:1rem;right:1rem;background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:0.75rem 1rem;z-index:9999;min-width:300px;box-shadow:0 8px 30px rgba(0,0,0,0.4);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-voicemail" style="color:var(--accent);"></i>
            <span id="vmPlayerName" style="font-weight:600;font-size:0.85rem;flex:1;"></span>
            <button class="btn-icon" onclick="stopVm()" style="width:24px;height:24px;font-size:0.7rem;"><i class="bi bi-x"></i></button>
        </div>
        <audio id="vmAudio" controls style="width:100%;height:32px;"></audio>
    </div>

    <script>
        function playVm(ext, folder, msgId, callerid) {
            const player = document.getElementById('vmPlayer');
            const audio = document.getElementById('vmAudio');
            document.getElementById('vmPlayerName').textContent = callerid;
            audio.src = '/voicemail/' + ext + '/' + folder + '/' + msgId + '/play';
            player.style.display = '';
            audio.play();
        }

        function stopVm() {
            const audio = document.getElementById('vmAudio');
            audio.pause();
            audio.src = '';
            document.getElementById('vmPlayer').style.display = 'none';
        }
    </script>
@endsection
