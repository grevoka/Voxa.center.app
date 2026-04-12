@extends('layouts.app')

@section('title', __('ui.recordings'))
@section('page-title', __('ui.recordings'))

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.recordings") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __("ui.recordings") }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap align-items-end">
        <div>
            <label style="font-size:0.68rem;color:var(--text-secondary);display:block;">Operateur</label>
            <select name="operator" class="form-control form-control-sm" style="min-width:120px;">
                <option value="">Tous</option>
                @foreach($extensions as $ext)
                    <option value="{{ $ext }}" {{ request('operator') == $ext ? 'selected' : '' }}>{{ $ext }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:0.68rem;color:var(--text-secondary);display:block;">Direction</label>
            <select name="direction" class="form-control form-control-sm" style="min-width:120px;">
                <option value="">Toutes</option>
                <option value="inbound" {{ request('direction') == 'inbound' ? 'selected' : '' }}>{{ __("ui.inbound") }}</option>
                <option value="outbound" {{ request('direction') == 'outbound' ? 'selected' : '' }}>{{ __("ui.outbound") }}</option>
                <option value="internal" {{ request('direction') == 'internal' ? 'selected' : '' }}>{{ __("ui.internal") }}</option>
            </select>
        </div>
        <div>
            <label style="font-size:0.68rem;color:var(--text-secondary);display:block;">Recherche</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Numero..." value="{{ request('search') }}" style="min-width:150px;">
        </div>
        <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-funnel me-1"></i>{{ __("ui.filter") }}</button>
        @if(request()->hasAny(['operator','direction','search']))
            <a href="{{ route('recordings.index') }}" class="btn btn-sm" style="background:var(--surface-2);color:var(--text-secondary);border:1px solid var(--border);">{{ __("ui.reset") }}</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Direction</th>
                    <th>Operateur</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Duree</th>
                    <th>Statut</th>
                    <th style="width:250px;">{{ __("ui.listen") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td style="font-size:0.78rem;white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($r->calldate)->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        @if($r->direction === 'inbound')
                            <span style="font-size:0.68rem;background:#29b6f620;color:#29b6f6;border-radius:4px;padding:1px 6px;font-weight:600;">
                                <i class="bi bi-telephone-inbound-fill" style="font-size:0.6rem;"></i> Entrant
                            </span>
                        @elseif($r->direction === 'outbound')
                            <span style="font-size:0.68rem;background:#58a6ff20;color:#58a6ff;border-radius:4px;padding:1px 6px;font-weight:600;">
                                <i class="bi bi-telephone-outbound-fill" style="font-size:0.6rem;"></i> Sortant
                            </span>
                        @else
                            <span style="font-size:0.68rem;background:var(--surface-2);color:var(--text-secondary);border-radius:4px;padding:1px 6px;font-weight:600;">
                                <i class="bi bi-arrow-left-right" style="font-size:0.6rem;"></i> Interne
                            </span>
                        @endif
                    </td>
                    <td><span class="codec-tag">{{ $r->operator_ext }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">{{ $r->src }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">{{ $r->dst }}</td>
                    <td style="font-size:0.8rem;font-family:'JetBrains Mono',monospace;">
                        {{ gmdate('i:s', $r->billsec) }}
                    </td>
                    <td>
                        <span class="status-badge {{ $r->disposition === 'ANSWERED' ? 'status-online' : 'status-offline' }}">
                            {{ $r->disposition === 'ANSWERED' ? 'Repondu' : $r->disposition }}
                        </span>
                    </td>
                    <td>
                        @if($r->has_recording)
                            <div class="d-flex align-items-center gap-1">
                                <audio controls preload="none" style="height:28px;width:200px;">
                                    <source src="{{ route('recordings.play', $r->uniqueid) }}" type="audio/wav">
                                </audio>
                                <form action="{{ route('recordings.destroy', $r->uniqueid) }}" method="POST" onsubmit="return confirm('Supprimer cet enregistrement ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Supprimer" style="width:26px;height:26px;font-size:0.7rem;color:#f85149;">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <span style="font-size:0.72rem;color:var(--text-secondary);"><i class="bi bi-slash-circle me-1"></i>Pas d'enregistrement</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4" style="color:var(--text-secondary);">
                    <i class="bi bi-mic-mute me-1"></i>Aucun enregistrement trouve
                </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($records->hasPages())
            <div class="px-3 py-2">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
