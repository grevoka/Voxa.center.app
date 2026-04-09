@extends('layouts.operator')

@section('title', 'Journal d\'appels')
@section('page-title', 'Journal d\'appels')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Journal d'appels</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Historique des appels du poste {{ $line->extension }}</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="disposition" class="form-select form-select-sm">
                <option value="">Tous statuts</option>
                <option value="ANSWERED" {{ request('disposition') == 'ANSWERED' ? 'selected' : '' }}>Repondu</option>
                <option value="NO ANSWER" {{ request('disposition') == 'NO ANSWER' ? 'selected' : '' }}>Sans reponse</option>
                <option value="BUSY" {{ request('disposition') == 'BUSY' ? 'selected' : '' }}>Occupe</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr><th>Date</th><th>Direction</th><th>Correspondant</th><th>Duree</th><th>Statut</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $call)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;">{{ $call->started_at?->format('d/m/Y H:i:s') }}</td>
                    <td>
                        @if($call->src == $line->extension)
                            <i class="bi bi-telephone-outbound-fill" style="color:var(--warning);font-size:0.75rem;"></i> <span style="font-size:0.72rem;">Sortant</span>
                        @else
                            <i class="bi bi-telephone-inbound-fill" style="color:var(--info);font-size:0.75rem;"></i> <span style="font-size:0.72rem;">Entrant</span>
                        @endif
                    </td>
                    <td style="font-size:0.82rem;">
                        <span style="font-family:'JetBrains Mono',monospace;font-weight:600;">{{ $call->src == $line->extension ? $call->dst : $call->src }}</span>
                        @if($call->src_name && $call->src != $line->extension)
                            <span style="color:var(--text-secondary);font-size:0.72rem;margin-left:0.3rem;">{{ $call->src_name }}</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $call->formatted_duration }}</td>
                    <td><span class="status-dot {{ $call->disposition_color }}"></span> <span style="font-size:0.75rem;">{{ $call->disposition_label }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">Aucun appel</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="mt-3 d-flex justify-content-center">{{ $logs->links() }}</div>
    @endif
@endsection
