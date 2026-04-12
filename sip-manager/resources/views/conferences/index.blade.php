@extends('layouts.app')

@section('title', __('ui.conferences'))
@section('page-title', __('ui.conferences'))

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Salles de conference</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">ConfBridge — conferences audio multi-participants</p>
        </div>
        <a href="{{ route('conferences.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Nouvelle salle
        </a>
    </div>

    {{-- Stats --}}
    @if($rooms->total())
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->total() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Salles</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800; color:var(--accent);">{{ $rooms->where('enabled', true)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Actives</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->where('record', true)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Avec enregistrement</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->where('pin', '!=', null)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Protegees par PIN</div>
            </div>
        </div>
    </div>
    @endif

    @if($rooms->isEmpty())
        <div class="stat-card text-center" style="padding:3rem;">
            <i class="bi bi-camera-video" style="font-size:3rem; color:var(--text-secondary); opacity:.3;"></i>
            <p style="color:var(--text-secondary); margin-top:1rem;">{{ __('ui.no_conferences') }}.</p>
            <a href="{{ route('conferences.create') }}" class="btn btn-accent mt-2">
                <i class="bi bi-plus-lg me-1"></i> {{ __('ui.create_room') }}
            </a>
        </div>
    @else
        <div class="data-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __("ui.room") }}</th>
                        <th>Numero</th>
                        <th>PIN</th>
                        <th>Max</th>
                        <th>Options</th>
                        <th>{{ __("ui.status") }}</th>
                        <th>{{ __("ui.actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rooms as $room)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $room->display_name ?: $room->name }}</div>
                                @if($room->display_name)
                                    <small style="color:var(--text-secondary); font-family:'JetBrains Mono',monospace; font-size:0.7rem;">{{ $room->name }}</small>
                                @endif
                            </td>
                            <td>
                                <code style="font-size:0.85rem; color:var(--accent); font-weight:600;">{{ $room->conference_number }}</code>
                            </td>
                            <td>
                                @if($room->pin)
                                    <span class="codec-tag" style="color:var(--accent); border-color:var(--accent-mid);">
                                        <i class="bi bi-lock-fill" style="font-size:0.6rem;"></i> PIN
                                    </span>
                                @else
                                    <span style="color:var(--text-secondary); font-size:0.75rem;">Libre</span>
                                @endif
                                @if($room->admin_pin)
                                    <span class="codec-tag" style="color:#d29922; border-color:rgba(210,153,34,0.3);">Admin</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-family:'JetBrains Mono',monospace; font-size:0.78rem;">{{ $room->max_members }}</span>
                            </td>
                            <td>
                                @if($room->record)
                                    <i class="bi bi-record-circle" style="color:#ef4444; font-size:0.75rem;" title="Enregistrement"></i>
                                @endif
                                @if($room->mute_on_join)
                                    <i class="bi bi-mic-mute" style="color:#f0883e; font-size:0.75rem;" title="Mute a l'entree"></i>
                                @endif
                                @if($room->wait_for_leader)
                                    <i class="bi bi-hourglass-split" style="color:#bc8cff; font-size:0.75rem;" title="Attente moderateur"></i>
                                @endif
                                @if($room->announce_join_leave)
                                    <i class="bi bi-megaphone" style="color:#58a6ff; font-size:0.75rem;" title="Annonce entree/sortie"></i>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('conferences.toggle', $room) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon" style="border:none; background:none;">
                                        <span class="status-dot {{ $room->enabled ? 'online' : 'offline' }}"></span>
                                        <span style="font-size:0.75rem;">{{ $room->enabled ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('conferences.edit', $room) }}" class="btn-icon" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('conferences.destroy', $room) }}" method="POST"
                                          onsubmit="return confirm('Supprimer cette salle de conference ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $rooms->links() }}</div>
    @endif

    {{-- Info box --}}
    <div style="background:rgba(var(--accent-rgb), 0.08);border:1px solid rgba(var(--accent-rgb), 0.2);border-radius:10px;padding:0.75rem 1rem;margin-top:1rem;font-size:0.82rem;color:var(--text-secondary);">
        <i class="bi bi-info-circle me-1" style="color:var(--accent);"></i>
        Pour rejoindre une salle, composez le <strong>numero de conference</strong> depuis un poste interne.
        Les enregistrements sont stockes dans <code>/var/spool/asterisk/monitor/</code>.
    </div>
@endsection
