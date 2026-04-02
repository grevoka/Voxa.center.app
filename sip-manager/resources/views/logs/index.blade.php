@extends('layouts.app')

@section('title', 'Journal d\'appels')
@section('page-title', 'Journal d\'appels')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Journal d'appels</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Historique et CDR de tous les appels</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon"><i class="bi bi-telephone-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total appels</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#00e5a020;color:#00e5a0;"><i class="bi bi-check-circle-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['answered'] }}</div>
                <div class="stat-label">Repondus</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#d2992220;color:#d29922;"><i class="bi bi-telephone-x-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['missed'] }}</div>
                <div class="stat-label">Manques</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#f8514920;color:#f85149;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['failed'] }}</div>
                <div class="stat-label">Echoues</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="data-table mb-4" style="padding:1rem 1.25rem;">
        <form method="GET" action="{{ route('logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Numero, nom, contexte...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Direction</label>
                <select name="direction" class="form-select">
                    <option value="">Toutes</option>
                    <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Entrant</option>
                    <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Sortant</option>
                    <option value="internal" {{ request('direction') === 'internal' ? 'selected' : '' }}>Interne</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="disposition" class="form-select">
                    <option value="">Tous</option>
                    <option value="ANSWERED" {{ request('disposition') === 'ANSWERED' ? 'selected' : '' }}>Repondu</option>
                    <option value="NO ANSWER" {{ request('disposition') === 'NO ANSWER' ? 'selected' : '' }}>Sans reponse</option>
                    <option value="BUSY" {{ request('disposition') === 'BUSY' ? 'selected' : '' }}>Occupe</option>
                    <option value="FAILED" {{ request('disposition') === 'FAILED' ? 'selected' : '' }}>Echoue</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Du</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Au</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-accent w-100"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Direction</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Contexte</th>
                    <th>Duree</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-secondary);">
                            {{ $log->started_at ? $log->started_at->format('d/m/Y H:i:s') : '—' }}
                        </td>
                        <td>
                            <i class="bi {{ $log->direction_icon }}" style="color:{{ $log->direction_color }};"></i>
                            <span style="font-size:0.8rem;">
                                {{ $log->direction === 'inbound' ? 'Entrant' : ($log->direction === 'outbound' ? 'Sortant' : 'Interne') }}
                            </span>
                        </td>
                        <td>
                            <span class="ext-number">{{ $log->src ?: '—' }}</span>
                            @if($log->src_name)
                                <span style="font-size:0.78rem;color:var(--text-secondary);"> {{ $log->src_name }}</span>
                            @endif
                        </td>
                        <td><span class="ext-number">{{ $log->dst ?: '—' }}</span></td>
                        <td><span class="codec-tag">{{ $log->context ?: '—' }}</span></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">
                            {{ $log->formatted_duration }}
                        </td>
                        <td>
                            <span class="status-dot {{ $log->disposition_color }}"></span>
                            {{ $log->disposition_label }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-journal-x me-2"></i>Aucun appel enregistre
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
@endsection
