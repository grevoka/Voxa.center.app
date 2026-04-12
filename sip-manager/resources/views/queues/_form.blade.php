@push('styles')
<style>
    .member-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        background: var(--surface-3);
        border: 1px solid var(--border);
        margin-bottom: 0.4rem;
        transition: all .15s;
    }
    .member-row:hover {
        border-color: var(--accent-mid);
    }
    .member-row .ext-badge {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: var(--accent);
        font-size: 0.8rem;
        min-width: 50px;
    }
    .member-row .member-name {
        flex: 1;
        font-size: 0.82rem;
        color: var(--text-secondary);
    }
    .member-row .penalty-input {
        width: 60px;
    }
</style>
@endpush

<form method="POST"
      action="{{ $queue ? route('queues.update', $queue) : route('queues.store') }}"
      id="queueForm">
    @csrf
    @if($queue) @method('PUT') @endif

    @if($errors->any())
        <div class="alert-flash danger" style="margin-bottom:1rem;">
            <i class="bi bi-exclamation-triangle-fill me-2" style="color:var(--danger);"></i>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-gear me-1" style="color:var(--accent);"></i> {{ __('ui.general_settings') }}
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom technique</label>
                        <input type="text" class="form-control" name="name" required
                               value="{{ old('name', $queue->name ?? '') }}"
                               placeholder="support-queue" pattern="[a-zA-Z0-9_-]+">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">Lettres, chiffres, - et _ uniquement</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom affiche</label>
                        <input type="text" class="form-control" name="display_name"
                               value="{{ old('display_name', $queue->display_name ?? '') }}"
                               placeholder="Support technique">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Strategie</label>
                        <select class="form-select" name="strategy" required>
                            @foreach($strategies as $key => $label)
                                <option value="{{ $key }}" {{ old('strategy', $queue->strategy ?? 'ringall') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Musique d'attente</label>
                        <select class="form-select" name="music_on_hold" id="mohSelect">
                            <option value="default">default</option>
                        </select>
                        <script>
                        fetch('/api/moh').then(r=>r.json()).then(classes=>{
                            const sel=document.getElementById('mohSelect');
                            const current='{{ old('music_on_hold', $queue->music_on_hold ?? 'default') }}';
                            sel.innerHTML='';
                            const local=classes.filter(c=>!c.is_stream&&!c.is_playlist);
                            const playlists=classes.filter(c=>c.is_playlist);
                            const streams=classes.filter(c=>c.is_stream);
                            [{label:'Fichiers locaux',items:local,suffix:f=>f.files.length+' fichiers'},
                             {label:'Playlists',items:playlists,suffix:f=>f.files.length+' titres'},
                             {label:'Flux streaming',items:streams,suffix:()=>'stream'}
                            ].forEach(g=>{
                                if(!g.items.length) return;
                                const grp=document.createElement('optgroup');
                                grp.label=g.label;
                                g.items.forEach(c=>{
                                    const opt=document.createElement('option');
                                    opt.value=c.name;
                                    opt.textContent=(c.display_name||c.name)+' ('+g.suffix(c)+')';
                                    if(c.name===current) opt.selected=true;
                                    grp.appendChild(opt);
                                });
                                sel.appendChild(grp);
                            });
                        });
                        </script>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timeout agent (sec)</label>
                        <input type="number" class="form-control" name="timeout"
                               value="{{ old('timeout', $queue->timeout ?? 25) }}" min="5" max="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Retry (sec)</label>
                        <input type="number" class="form-control" name="retry"
                               value="{{ old('retry', $queue->retry ?? 5) }}" min="0" max="60">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Attente max (sec)</label>
                        <input type="number" class="form-control" name="max_wait_time"
                               value="{{ old('max_wait_time', $queue->max_wait_time ?? 300) }}" min="30" max="3600">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Frequence annonce (sec)</label>
                        <input type="number" class="form-control" name="announce_frequency"
                               value="{{ old('announce_frequency', $queue->announce_frequency ?? 0) }}" min="0" max="300">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">0 = pas d'annonce</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Annoncer temps d'attente</label>
                        <select class="form-select" name="announce_holdtime">
                            <option value="" {{ !old('announce_holdtime', $queue->announce_holdtime ?? '') ? 'selected' : '' }}>Non</option>
                            <option value="yes" {{ old('announce_holdtime', $queue->announce_holdtime ?? '') === 'yes' ? 'selected' : '' }}>Oui</option>
                            <option value="once" {{ old('announce_holdtime', $queue->announce_holdtime ?? '') === 'once' ? 'selected' : '' }}>Une fois</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-people me-1" style="color:var(--accent);"></i> Membres
                </h6>
                <p style="color:var(--text-secondary); font-size:0.78rem; margin-bottom:1rem;">
                    {{ __('ui.select_members') }} de cette file.
                </p>

                <div id="membersList">
                    @php
                        $currentMembers = old('members', $queue->members ?? []);
                        $memberExts = collect($currentMembers)->pluck('extension')->toArray();
                    @endphp
                    @foreach($lines as $line)
                        @php $isMember = in_array($line->extension, $memberExts); @endphp
                        @php
                            $penalty = 0;
                            if ($isMember) {
                                $found = collect($currentMembers)->firstWhere('extension', $line->extension);
                                $penalty = $found['penalty'] ?? 0;
                            }
                        @endphp
                        <div class="member-row">
                            <input type="checkbox" class="form-check-input" style="margin:0;"
                                   id="member_{{ $line->extension }}"
                                   onchange="toggleMember('{{ $line->extension }}', this.checked)"
                                   {{ $isMember ? 'checked' : '' }}>
                            <span class="ext-badge">{{ $line->extension }}</span>
                            <span class="member-name">{{ $line->callerid_name ?? $line->username ?? '' }}</span>
                            <input type="number" class="form-control penalty-input"
                                   id="penalty_{{ $line->extension }}"
                                   value="{{ $penalty }}" min="0" max="10"
                                   placeholder="P" title="Penalite (0-10)"
                                   {{ !$isMember ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>

                @if($lines->isEmpty())
                    <p style="color:var(--text-secondary); font-size:0.82rem; text-align:center; padding:1rem;">
                        {{ __('ui.no_sip_lines') }}.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Hidden inputs for members --}}
    <div id="membersHidden"></div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('queues.index') }}" class="btn-outline-custom">Annuler</a>
        <button type="submit" class="btn btn-accent" onclick="prepareMembers()">
            <i class="bi bi-check-lg me-1"></i> {{ $queue ? 'Mettre a jour' : 'Creer la file' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
function toggleMember(ext, checked) {
    const penaltyInput = document.getElementById('penalty_' + ext);
    if (penaltyInput) {
        penaltyInput.disabled = !checked;
        if (!checked) penaltyInput.value = 0;
    }
}

function prepareMembers() {
    const container = document.getElementById('membersHidden');
    container.innerHTML = '';

    let idx = 0;
    document.querySelectorAll('.member-row').forEach(row => {
        const cb = row.querySelector('input[type="checkbox"]');
        if (cb && cb.checked) {
            const ext = row.querySelector('.ext-badge').textContent.trim();
            const penalty = row.querySelector('.penalty-input')?.value || 0;
            container.innerHTML += `<input type="hidden" name="members[${idx}][extension]" value="${ext}">`;
            container.innerHTML += `<input type="hidden" name="members[${idx}][penalty]" value="${penalty}">`;
            idx++;
        }
    });
}

document.getElementById('queueForm').addEventListener('submit', function() {
    prepareMembers();
});
</script>
@endpush
