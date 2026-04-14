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
        <div class="col-lg-7">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-gear me-1" style="color:var(--accent);"></i> {{ __('ui.configuration') }}
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.technical_name') }} *</label>
                        <input type="text" class="form-control" name="name" required
                               value="{{ old('name', $room->name ?? '') }}"
                               placeholder="reunion-equipe" pattern="[a-zA-Z0-9_-]+">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">{{ __('ui.letters_hint') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.display_name') }}</label>
                        <input type="text" class="form-control" name="display_name"
                               value="{{ old('display_name', $room->display_name ?? '') }}"
                               placeholder="{{ app()->getLocale() === 'fr' ? 'Reunion d\'equipe' : 'Team meeting' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.conf_number') }} *</label>
                        <input type="text" class="form-control" name="conference_number" required
                               value="{{ old('conference_number', $room->conference_number ?? '') }}"
                               placeholder="8000" pattern="[0-9]+"
                               style="font-family:'JetBrains Mono',monospace; font-weight:600; color:var(--accent);">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">{{ __('ui.number_to_dial') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.max_participants') }}</label>
                        <input type="number" class="form-control" name="max_members"
                               value="{{ old('max_members', $room->max_members ?? 10) }}" min="2" max="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.pin_code') }}</label>
                        <input type="text" class="form-control" name="pin"
                               value="{{ old('pin', $room->pin ?? '') }}"
                               placeholder="{{ __('ui.optional') }}" pattern="[0-9]*">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">{{ __('ui.pin_required_hint') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.admin_pin') }}</label>
                        <input type="text" class="form-control" name="admin_pin"
                               value="{{ old('admin_pin', $room->admin_pin ?? '') }}"
                               placeholder="{{ __('ui.optional') }}" pattern="[0-9]*">
                        <small style="color:var(--text-secondary); font-size:0.7rem;">{{ __('ui.admin_pin_hint') }}</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('ui.moh_label') }}</label>
                        <select class="form-select" name="music_on_hold" id="mohSelectConf">
                            <option value="default">default</option>
                        </select>
                        <small style="color:var(--text-secondary); font-size:0.7rem;">{{ __('ui.moh_single_hint') }}</small>
                        <script>
                        fetch('/api/moh').then(r=>r.json()).then(classes=>{
                            const sel=document.getElementById('mohSelectConf');
                            const current='{{ old('music_on_hold', $room->music_on_hold ?? 'default') }}';
                            sel.innerHTML='';
                            const local=classes.filter(c=>!c.is_stream&&!c.is_playlist);
                            const playlists=classes.filter(c=>c.is_playlist);
                            const streams=classes.filter(c=>c.is_stream);
                            [{label:'{{ __("ui.local_files") }}',items:local,suffix:f=>f.files.length+' {{ __("ui.files_count") }}'},
                             {label:'{{ __("ui.playlists") }}',items:playlists,suffix:f=>f.files.length+' {{ __("ui.tracks_count") }}'},
                             {label:'{{ __("ui.streaming") }}',items:streams,suffix:()=>'stream'}
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

        <div class="col-lg-5">
            <div class="stat-card">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:1rem;">
                    <i class="bi bi-sliders me-1" style="color:var(--accent);"></i> {{ __('ui.options') }}
                </h6>

                <div class="d-flex flex-column gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="record" value="1" id="optRecord"
                            {{ old('record', $room->record ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optRecord">
                            <i class="bi bi-record-circle" style="color:#ef4444;"></i> {{ __('ui.record_conference') }}
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">{{ __('ui.record_conf_hint') }}</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mute_on_join" value="1" id="optMute"
                            {{ old('mute_on_join', $room->mute_on_join ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optMute">
                            <i class="bi bi-mic-mute" style="color:#f0883e;"></i> {{ __('ui.mute_on_join') }}
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">{{ __('ui.mute_on_join_hint') }}</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="announce_join_leave" value="1" id="optAnnounce"
                            {{ old('announce_join_leave', $room->announce_join_leave ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optAnnounce">
                            <i class="bi bi-megaphone" style="color:#58a6ff;"></i> {{ __('ui.announce_join_leave') }}
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">{{ __('ui.announce_join_leave_hint') }}</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="wait_for_leader" value="1" id="optLeader"
                            {{ old('wait_for_leader', $room->wait_for_leader ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="optLeader">
                            <i class="bi bi-hourglass-split" style="color:#bc8cff;"></i> {{ __('ui.wait_for_leader') }}
                        </label>
                        <br><small style="color:var(--text-secondary); font-size:0.68rem;">{{ __('ui.wait_for_leader_hint') }}</small>
                    </div>
                </div>
            </div>

            <div class="stat-card mt-3" style="background:rgba(var(--accent-rgb), 0.04); border-color:rgba(var(--accent-rgb), 0.15);">
                <h6 style="font-weight:700; font-size:0.82rem; margin-bottom:0.75rem;">
                    <i class="bi bi-lightbulb me-1" style="color:var(--accent);"></i> {{ __('ui.help') }}
                </h6>
                <div style="font-size:0.78rem; color:var(--text-secondary); line-height:1.6;">
                    <p class="mb-2"><strong>{{ __('ui.conf_number') }} :</strong> {{ __('ui.help_number') }}</p>
                    <p class="mb-2"><strong>PIN :</strong> {{ __('ui.help_pin') }}</p>
                    <p class="mb-2"><strong>{{ __('ui.admin_pin') }} :</strong> {{ __('ui.help_admin_pin') }}</p>
                    <p class="mb-0"><strong>{{ __('ui.wait_for_leader') }} :</strong> {{ __('ui.help_wait_leader') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('conferences.index') }}" class="btn-outline-custom">{{ __('ui.cancel') }}</a>
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg me-1"></i> {{ $room ? __('ui.update_room') : __('ui.create_room_btn') }}
        </button>
    </div>
</form>
