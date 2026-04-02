@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon"><i class="bi bi-telephone-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['lines_online'] }}/{{ $stats['lines_total'] }}</div>
                <div class="stat-label">Lignes actives</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#58a6ff20;color:#58a6ff;"><i class="bi bi-diagram-3-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['trunks_online'] }}/{{ $stats['trunks_total'] }}</div>
                <div class="stat-label">Trunks configures</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#d2992220;color:#d29922;"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="stat-value">{{ $stats['active_calls'] }}</div>
                <div class="stat-label">Appels en cours</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#f8514920;color:#f85149;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['errors'] }}</div>
                <div class="stat-label">Erreurs</div>
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="data-table">
        <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border);">
            <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;">Activite recente</h6>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Evenement</th>
                    <th>Details</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-secondary);">
                            {{ $activity->created_at->format('H:i:s') }}
                        </td>
                        <td style="font-weight:500;">{{ $activity->event }}</td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;">{{ $activity->details }}</td>
                        <td>
                            @if($activity->level === 'success')
                                <span class="status-dot online"></span>
                            @elseif($activity->level === 'warning')
                                <span class="status-dot busy"></span>
                            @elseif($activity->level === 'error')
                                <span class="status-dot error"></span>
                            @else
                                <span class="status-dot offline"></span>
                            @endif
                            {{ ucfirst($activity->level) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4" style="color:var(--text-secondary);">
                            Aucune activite recente
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
