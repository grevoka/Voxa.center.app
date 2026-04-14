@extends('layouts.app')

@section('title', __('ui.conference_rooms'))
@section('page-title', __('ui.conference_rooms'))

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ __('ui.conference_rooms') }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">{{ __('ui.conf_desc') }}</p>
        </div>
        <a href="{{ route('conferences.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> {{ __('ui.new_room') }}
        </a>
    </div>

    @if($rooms->total())
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->total() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">{{ __('ui.rooms') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800; color:var(--accent);">{{ $rooms->where('enabled', true)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">{{ __('ui.active_routes') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->where('record', true)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">{{ __('ui.with_recording') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div style="font-size:1.5rem; font-weight:800;">{{ $rooms->where('pin', '!=', null)->count() }}</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">{{ __('ui.pin_protected') }}</div>
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
                        <th>{{ __("ui.conf_number") }}</th>
                        <th>PIN</th>
                        <th>Max</th>
                        <th>{{ __("ui.options") }}</th>
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
                                    <span style="color:var(--text-secondary); font-size:0.75rem;">{{ __('ui.pin_free') }}</span>
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
                                    <i class="bi bi-record-circle" style="color:#ef4444; font-size:0.75rem;" title="{{ __('ui.record_conference') }}"></i>
                                @endif
                                @if($room->mute_on_join)
                                    <i class="bi bi-mic-mute" style="color:#f0883e; font-size:0.75rem;" title="{{ __('ui.mute_on_join') }}"></i>
                                @endif
                                @if($room->wait_for_leader)
                                    <i class="bi bi-hourglass-split" style="color:#bc8cff; font-size:0.75rem;" title="{{ __('ui.wait_for_leader') }}"></i>
                                @endif
                                @if($room->announce_join_leave)
                                    <i class="bi bi-megaphone" style="color:#58a6ff; font-size:0.75rem;" title="{{ __('ui.announce_join_leave') }}"></i>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('conferences.toggle', $room) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon" style="border:none; background:none;">
                                        <span class="status-dot {{ $room->enabled ? 'online' : 'offline' }}"></span>
                                        <span style="font-size:0.75rem;">{{ $room->enabled ? __('ui.active') : __('ui.inactive') }}</span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('conferences.edit', $room) }}" class="btn-icon" title="{{ __('ui.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('conferences.destroy', $room) }}" method="POST"
                                          onsubmit="return confirm('{{ __('ui.confirm_delete_room') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="{{ __('ui.delete') }}">
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

    <div style="background:rgba(var(--accent-rgb), 0.08);border:1px solid rgba(var(--accent-rgb), 0.2);border-radius:10px;padding:0.75rem 1rem;margin-top:1rem;font-size:0.82rem;color:var(--text-secondary);">
        <i class="bi bi-info-circle me-1" style="color:var(--accent);"></i>
        {{ __('ui.dial_to_join') }}
        {{ __('ui.recordings_path') }} <code>/var/spool/asterisk/monitor/</code>.
    </div>
@endsection
