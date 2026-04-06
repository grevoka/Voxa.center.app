<form method="POST"
      action="{{ $room ? route('conferences.update', $room) : route('conferences.store') }}">
    @csrf
    @if($room) @method('PUT') @endif

    @if($errors->any())
        <div class="alert-flash danger" style="margin-bottom:1rem;">
            <i class="bi bi-exclamation-triangle-fill me-2" style="color:var(--danger);"></i>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        {{-- Left: general config --}}
        <div class="col-lg-7">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-gear me-1" style="color:var(--accent);"></i> Configuration
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom technique *</label>
                        <input type="text" class="form-control" name="name" required
                               value="{{ old('name', $room->name ?? '') }}"
                               placeholder="reunion-equipe" pattern="[a-zA-Z0-9_-]+">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">Lettres, chiffres, - et _ uniquement</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom affiche</label>
                        <input type="text" class="form-control" name="display_name"
                               value="{{ old('display_name', $room->display_name ?? '') }}"
                               placeholder="Reunion d'equipe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Numero de conference *</label>
                        <input type="text" class="form-control" name="conference_number" required
                               value="{{ old('conference_number', $room->conference_number ?? '') }}"
                               placeholder="8000" pattern="[0-9]+"
                               style="font-family:'JetBrains Mono',monospace; font-weight:600; color:var(--accent);">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">Numero a composer pour rejoindre la salle</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Participants max</label>
                        <input type="number" class="form-control" name="max_members"
                               value="{{ old('max_members', $room->max_members ?? 10) }}" min="2" max="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code PIN</label>
                        <input type="text" class="form-control" name="pin"
                               value="{{ old('pin', $room->pin ?? '') }}"
                               placeholder="Optionnel" pattern="[0-9]*">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">Demande a l'entree (vide = acces libre)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code PIN Admin</label>
                        <input type="text" class="form-control" name="admin_pin"
                               value="{{ old('admin_pin', $room->admin_pin ?? '') }}"
                               placeholder="Optionnel" pattern="[0-9]*">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">PIN moderateur (droits etendus)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Musique d'attente</label>
                        <select class="form-select" name="music_on_hold" id="mohSelectConf">
                            <option value="default">default</option>
                        </select>
                        <small style="color:var(--text-secondary); font-size:0.7rem;">Jouee quand un seul participant est present</small>
                        <script>
                        fetch('/api/moh').then(r=>r.json()).then(classes=>{
                            const sel=document.getElementById('mohSelectConf');
                            const current='{{ old('music_on_hold', $room->music_on_hold ?? 'default') }}';
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
                </div>
            </div>
        </div>

        {{-- Right: options --}}
        <div class="col-lg-5">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-sliders me-1" style="color:var(--accent);"></i> Options
                </h6>

                <div class="d-flex flex-column gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="record" value="1" id="optRecord"
                            {{ old('record', $room->record ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optRecord">
                            <i class="bi bi-record-circle" style="color:#ef4444;"></i> Enregistrer la conference
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">Sauvegarde l'audio dans /var/spool/asterisk/monitor/</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mute_on_join" value="1" id="optMute"
                            {{ old('mute_on_join', $room->mute_on_join ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optMute">
                            <i class="bi bi-mic-mute" style="color:#f0883e;"></i> Mute a l'entree
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">Les participants entrent micro coupe</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="announce_join_leave" value="1" id="optAnnounce"
                            {{ old('announce_join_leave', $room->announce_join_leave ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optAnnounce">
                            <i class="bi bi-megaphone" style="color:#58a6ff;"></i> Annoncer entrees/sorties
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">Son quand un participant rejoint ou quitte</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="wait_for_leader" value="1" id="optLeader"
                            {{ old('wait_for_leader', $room->wait_for_leader ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optLeader">
                            <i class="bi bi-hourglass-split" style="color:#bc8cff;"></i> Attendre le moderateur
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">La conference demarre uniquement quand l'admin rejoint</small>
                    </div>
                </div>
            </div>

            {{-- Help card --}}
            <div class="stat-card mt-3" style="background:rgba(var(--accent-rgb), 0.04); border-color:rgba(var(--accent-rgb), 0.15);">
                <h6 style="font-weight:700; font-size:0.82rem; margin-bottom:0.75rem;">
                    <i class="bi bi-lightbulb me-1" style="color:var(--accent);"></i> Aide
                </h6>
                <div style="font-size:0.78rem; color:var(--text-secondary); line-height:1.6;">
                    <p class="mb-2"><strong>Numero :</strong> Les postes composent ce numero pour rejoindre la salle.</p>
                    <p class="mb-2"><strong>PIN :</strong> Si defini, un code est demande a l'entree.</p>
                    <p class="mb-2"><strong>Admin PIN :</strong> Code special pour le moderateur — peut muter/exclure les participants.</p>
                    <p class="mb-0"><strong>Attente moderateur :</strong> Les participants entendent la musique d'attente jusqu'a l'arrivee de l'admin.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('conferences.index') }}" class="btn-outline-custom">Annuler</a>
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg me-1"></i> {{ $room ? 'Mettre a jour' : 'Creer la salle' }}
        </button>
    </div>
</form>
