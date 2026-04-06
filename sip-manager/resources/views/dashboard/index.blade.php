@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
    {{-- Stats rapides --}}
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
                <div class="stat-label">Trunks connectes</div>
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

    {{-- Graphique appels 7 jours + Appels du jour --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="data-table" style="padding:1.25rem;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-graph-up me-2" style="color:var(--accent);"></i>Trafic des appels — 7 derniers jours</h6>
                </div>
                <canvas id="callChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="data-table" style="padding:1.25rem; height:100%;">
                <h6 class="mb-3" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-calendar-day me-2" style="color:var(--accent);"></i>Aujourd'hui</h6>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.82rem;color:var(--text-secondary);">Total appels</span>
                        <span style="font-weight:700;font-size:1.1rem;">{{ $todayStats['total'] }}</span>
                    </div>
                    <div style="border-bottom:1px solid var(--border);"></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.82rem;"><span class="status-dot online"></span> Repondus</span>
                        <span style="font-weight:600;">{{ $todayStats['answered'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.82rem;"><span class="status-dot busy"></span> Manques</span>
                        <span style="font-weight:600;">{{ $todayStats['missed'] }}</span>
                    </div>
                    <div style="border-bottom:1px solid var(--border);"></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.82rem;"><i class="bi bi-telephone-inbound-fill me-1" style="color:#58a6ff;font-size:0.75rem;"></i> Entrants</span>
                        <span style="font-weight:600;">{{ $todayStats['inbound'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.82rem;"><i class="bi bi-telephone-outbound-fill me-1" style="color:#d29922;font-size:0.75rem;"></i> Sortants</span>
                        <span style="font-weight:600;">{{ $todayStats['outbound'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Raccourcis --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h6 class="mb-3" style="font-size:0.9rem;font-weight:700;color:var(--text-secondary);"><i class="bi bi-lightning-fill me-1"></i>Acces rapide</h6>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('lines.index') }}" class="shortcut-card">
                <div class="shortcut-icon"><i class="bi bi-telephone-fill"></i></div>
                <span>Lignes SIP</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('trunks.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#58a6ff20;color:#58a6ff;"><i class="bi bi-diagram-3-fill"></i></div>
                <span>Trunks</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('callflows.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#ab7df620;color:#ab7df6;"><i class="bi bi-signpost-split-fill"></i></div>
                <span>Scenarios</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('queues.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#d2992220;color:#d29922;"><i class="bi bi-people-fill"></i></div>
                <span>Files d'attente</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('logs.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#f0883e20;color:#f0883e;"><i class="bi bi-journal-text"></i></div>
                <span>Journal</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('moh.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#00e5a020;color:#00e5a0;"><i class="bi bi-music-note-beamed"></i></div>
                <span>Musiques</span>
            </a>
        </div>
    </div>

    {{-- Appels actifs --}}
    @if(count($activeCalls) > 0)
    <div class="data-table mb-4">
        <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
            <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-headset me-2" style="color:var(--accent);"></i>Appels en cours <span class="badge" style="background:rgba(var(--accent-rgb),0.12);color:var(--accent);font-size:0.65rem;">{{ count($activeCalls) }}</span></h6>
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Canal</th>
                    <th>Appelant</th>
                    <th>Destination</th>
                    <th>Duree</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeCalls as $call)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">{{ $call['channel'] ?? '-' }}</td>
                    <td>{{ $call['callerid'] ?? '-' }}</td>
                    <td>{{ $call['extension'] ?? '-' }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $call['duration'] ?? '-' }}</td>
                    <td><span class="status-dot online"></span> {{ $call['state'] ?? 'Up' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endsection

@push('styles')
<style>
    .shortcut-card {
        display:flex; flex-direction:column; align-items:center; gap:0.6rem;
        padding:1.1rem 0.75rem; border-radius:12px; text-decoration:none;
        background:var(--surface-2); border:1px solid var(--border);
        transition:all .15s; color:var(--text-primary); font-size:0.82rem; font-weight:600;
    }
    .shortcut-card:hover { border-color:var(--accent); background:rgba(var(--accent-rgb),0.04); transform:translateY(-2px); color:var(--text-primary); }
    .shortcut-icon {
        width:42px; height:42px; border-radius:12px;
        background:rgba(var(--accent-rgb),0.12); color:var(--accent);
        display:flex; align-items:center; justify-content:center; font-size:1.15rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('callChart').getContext('2d');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Entrants',
                    data: chartData.inbound,
                    backgroundColor: 'rgba(88,166,255,0.7)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Sortants',
                    data: chartData.outbound,
                    backgroundColor: 'rgba(210,153,34,0.7)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Manques',
                    data: chartData.missed,
                    backgroundColor: 'rgba(248,81,73,0.5)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#8b949e', font: { size: 11 }, padding: 15, usePointStyle: true, pointStyleWidth: 8 }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(139,148,158,0.08)' },
                    ticks: { color: '#8b949e', font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(139,148,158,0.08)' },
                    ticks: { color: '#8b949e', font: { size: 11 }, stepSize: 1 }
                }
            }
        }
    });
});
</script>
@endpush
