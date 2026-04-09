@extends('layouts.operator')

@section('title', 'Messagerie vocale')
@section('page-title', 'Messagerie vocale')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Messagerie vocale</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Poste {{ $line->extension }} — {{ $line->name }}</p>
        </div>
    </div>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr><th>Dossier</th><th>Appelant</th><th>Date</th><th>Duree</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr>
                    <td>
                        @if($msg['folder'] === 'INBOX')
                            <span style="font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:4px;background:rgba(var(--accent-rgb),0.12);color:var(--accent);">Nouveau</span>
                        @else
                            <span style="font-size:0.72rem;color:var(--text-secondary);">Lu</span>
                        @endif
                    </td>
                    <td style="font-size:0.85rem;font-weight:500;">{{ $msg['callerid'] }}</td>
                    <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $msg['origdate'] }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $msg['duration'] }}s</td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($msg['has_audio'])
                            <button class="btn-icon" title="Ecouter" style="width:28px;height:28px;font-size:0.75rem;"
                                    onclick="playMsg('{{ route('operator.voicemail.play', [$msg['folder'], $msg['id']]) }}')">
                                <i class="bi bi-play-fill"></i>
                            </button>
                            @endif
                            <form action="{{ route('operator.voicemail.destroy', [$msg['folder'], $msg['id']]) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce message ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="Supprimer" style="width:28px;height:28px;font-size:0.75rem;color:var(--danger);">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">
                    <i class="bi bi-voicemail me-1"></i>Aucun message vocal
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Audio player --}}
    <div id="audioPlayer" style="display:none;position:fixed;bottom:1rem;right:1rem;background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:0.75rem 1rem;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:1000;">
        <div class="d-flex align-items-center gap-2">
            <button onclick="document.getElementById('vmAudio').paused ? document.getElementById('vmAudio').play() : document.getElementById('vmAudio').pause()" class="btn-icon" style="width:32px;height:32px;">
                <i class="bi bi-play-fill" id="playIcon"></i>
            </button>
            <audio id="vmAudio" onplay="document.getElementById('playIcon').className='bi bi-pause-fill'" onpause="document.getElementById('playIcon').className='bi bi-play-fill'" onended="document.getElementById('audioPlayer').style.display='none'"></audio>
            <span style="font-size:0.75rem;color:var(--text-secondary);">Lecture en cours</span>
            <button onclick="document.getElementById('vmAudio').pause();document.getElementById('audioPlayer').style.display='none'" class="btn-icon" style="width:24px;height:24px;font-size:0.65rem;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function playMsg(url) {
    var a = document.getElementById('vmAudio');
    var p = document.getElementById('audioPlayer');
    a.src = url;
    a.play();
    p.style.display = 'block';
}
</script>
@endpush
