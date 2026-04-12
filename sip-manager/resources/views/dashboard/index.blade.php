@extends('layouts.app')

@section('title', __('ui.dashboard'))
@section('page-title', __('ui.dashboard'))

@section('content')
    {{-- Stats rapides --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon"><i class="bi bi-telephone-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['lines_online'] }}/{{ $stats['lines_total'] }}</div>
                <div class="stat-label">{{ __("ui.active_lines") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#58a6ff20;color:#58a6ff;"><i class="bi bi-diagram-3-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['trunks_online'] }}/{{ $stats['trunks_total'] }}</div>
                <div class="stat-label">{{ __("ui.connected_trunks") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#d2992220;color:#d29922;"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="stat-value">{{ $stats['active_calls'] }}</div>
                <div class="stat-label">{{ __("ui.active_calls") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon" style="background:#f8514920;color:#f85149;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                </div>
                <div class="stat-value">{{ $stats['errors'] }}</div>
                <div class="stat-label">{{ __("ui.errors") }}</div>
            </div>
        </div>
    </div>

    {{-- Graphique MRTG appels 7 jours --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="data-table" style="padding:1.25rem;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-graph-up me-2" style="color:var(--accent);"></i>{{ __('ui.call_traffic') }}</h6>
                    <div class="d-flex align-items-center gap-3" style="font-size:0.72rem;">
                        <span><span style="display:inline-block;width:10px;height:3px;background:#58a6ff;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>{{ __("ui.inbound") }}</span>
                        <span><span style="display:inline-block;width:10px;height:3px;background:#29b6f6;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>{{ __("ui.outbound") }}</span>
                        <span><span style="display:inline-block;width:10px;height:3px;background:#f85149;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>{{ __("ui.missed") }}</span>
                    </div>
                </div>
                <div style="position:relative;height:240px;">
                    <canvas id="callChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats du jour --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--text-primary);">{{ $todayStats['total'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.calls_today") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:#29b6f6;">{{ $todayStats['answered'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.answered") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:#f85149;">{{ $todayStats['missed'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.missed") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:#58a6ff;">{{ $todayStats['inbound'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.inbound") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:#d29922;">{{ $todayStats['outbound'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.outbound") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                @php $rate = $todayStats['total'] > 0 ? round(($todayStats['answered'] / $todayStats['total']) * 100) : 0; @endphp
                <div style="font-size:1.4rem;font-weight:800;color:{{ $rate >= 80 ? '#29b6f6' : ($rate >= 50 ? '#d29922' : '#f85149') }};">{{ $rate }}%</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.response_rate") }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--text-primary);font-family:'JetBrains Mono',monospace;">{{ gmdate('i:s', $todayStats['avg_duration']) }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ __("ui.avg_duration") }}</div>
            </div>
        </div>
    </div>

    {{-- Stats par poste : Manqués + Durées --}}
    <div class="row g-3 mb-4">
        {{-- Appels manqués par poste --}}
        <div class="col-lg-6">
            <div class="data-table" style="padding:0;">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-telephone-x-fill me-1" style="color:#f85149;"></i> {{ __('ui.missed_by_ext') }}
                        <span style="font-size:0.65rem;color:var(--text-secondary);font-weight:400;margin-left:0.3rem;">{{ __('ui.today') }}</span>
                    </h6>
                </div>
                @if(count($missedByExt) > 0)
                <div style="padding:0.75rem 1rem;">
                    @foreach($missedByExt as $m)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="codec-tag" style="min-width:50px;text-align:center;">{{ $m['ext'] }}</span>
                        <span style="font-size:0.72rem;font-weight:600;min-width:70px;">{{ $m['name'] }}</span>
                        <div style="flex:1;height:20px;background:var(--surface);border-radius:4px;overflow:hidden;position:relative;">
                            @php $maxMissed = max(array_column($missedByExt, 'missed')); $pct = $maxMissed > 0 ? ($m['missed'] / $maxMissed) * 100 : 0; @endphp
                            <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,rgba(248,81,73,0.3),rgba(248,81,73,0.6));border-radius:4px;transition:width .3s;"></div>
                            <span style="position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:0.7rem;font-weight:700;color:#f85149;">{{ $m['missed'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="px-3 py-3 text-center" style="color:var(--text-secondary);font-size:0.82rem;">
                    <i class="bi bi-check-circle me-1" style="color:var(--success);"></i>{{ __('ui.no_missed_calls') }} {{ __('ui.today') }}
                </div>
                @endif
            </div>
        </div>

        {{-- Durée par poste --}}
        <div class="col-lg-6">
            <div class="data-table" style="padding:0;">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.85rem;font-weight:700;">
                        <i class="bi bi-clock-fill me-1" style="color:#d29922;"></i> {{ __('ui.time_by_ext') }}
                        <span style="font-size:0.65rem;color:var(--text-secondary);font-weight:400;margin-left:0.3rem;">{{ __('ui.today') }}</span>
                    </h6>
                    <div style="font-size:0.68rem;color:var(--text-secondary);">
                        Moy: <span style="font-weight:700;color:var(--text-primary);">{{ gmdate('i:s', $todayStats['avg_duration']) }}</span>
                        &middot; Total: <span style="font-weight:700;color:var(--text-primary);">{{ gmdate('H:i:s', $todayStats['total_duration']) }}</span>
                    </div>
                </div>
                @if(count($durationByExt) > 0)
                <table class="table mb-0">
                    <thead>
                        <tr><th>{{ __("ui.lines") }}</th><th>{{ __("ui.operators") }}</th><th>{{ __("ui.calls_today") }}</th><th>{{ __("ui.total") }}</th><th>{{ __("ui.average") }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($durationByExt as $d)
                        <tr>
                            <td><span class="codec-tag">{{ $d['ext'] }}</span></td>
                            <td style="font-size:0.78rem;font-weight:600;">{{ $d['name'] }}</td>
                            <td style="font-weight:600;">{{ $d['calls'] }}</td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">{{ gmdate('H:i:s', $d['total_sec']) }}</td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--text-secondary);">{{ gmdate('i:s', $d['avg_sec']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="px-3 py-3 text-center" style="color:var(--text-secondary);font-size:0.82rem;">
                    {{ __('ui.no_answered_calls') }} {{ __('ui.today') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Raccourcis --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h6 class="mb-3" style="font-size:0.9rem;font-weight:700;color:var(--text-secondary);"><i class="bi bi-lightning-fill me-1"></i>{{ __("ui.quick_access") }}</h6>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('lines.index') }}" class="shortcut-card">
                <div class="shortcut-icon"><i class="bi bi-telephone-fill"></i></div>
                <span>{{ __('ui.lines') }}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('trunks.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#58a6ff20;color:#58a6ff;"><i class="bi bi-diagram-3-fill"></i></div>
                <span>{{ __('ui.trunks') }}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('callflows.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#ab7df620;color:#ab7df6;"><i class="bi bi-signpost-split-fill"></i></div>
                <span>{{ __('ui.scenarios') }}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('queues.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#d2992220;color:#d29922;"><i class="bi bi-people-fill"></i></div>
                <span>{{ __('ui.queues') }}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('logs.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#f0883e20;color:#f0883e;"><i class="bi bi-journal-text"></i></div>
                <span>{{ __('ui.call_log') }}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('moh.index') }}" class="shortcut-card">
                <div class="shortcut-icon" style="background:#29b6f620;color:#29b6f6;"><i class="bi bi-music-note-beamed"></i></div>
                <span>{{ __('ui.music_on_hold') }}</span>
            </a>
        </div>
    </div>

    {{-- Derniers appels --}}
    @if($recentCalls->count())
    <div class="data-table mb-4">
        <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
            <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-journal-text me-2" style="color:var(--accent);"></i>{{ __("ui.recent_calls") }}</h6>
            <a href="{{ route('logs.index') }}" style="font-size:0.72rem;color:var(--accent);text-decoration:none;">{{ __('ui.view_all') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __("ui.date") }}</th>
                    <th>{{ __("ui.direction") }}</th>
                    <th>{{ __("ui.caller") }}</th>
                    <th>{{ __("ui.destination") }}</th>
                    <th>{{ __("ui.trunks") }}</th>
                    <th>{{ __("ui.duration") }}</th>
                    <th>{{ __("ui.status") }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentCalls as $call)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;">
                        {{ $call->started_at?->format('d/m H:i:s') ?? '-' }}
                    </td>
                    <td>
                        @if($call->direction === 'inbound')
                            <i class="bi bi-telephone-inbound-fill" style="color:#58a6ff;font-size:0.75rem;"></i>
                        @elseif($call->direction === 'outbound')
                            <i class="bi bi-telephone-outbound-fill" style="color:#d29922;font-size:0.75rem;"></i>
                        @else
                            <i class="bi bi-arrow-left-right" style="color:var(--text-secondary);font-size:0.75rem;"></i>
                        @endif
                    </td>
                    <td style="font-size:0.82rem;">
                        <span style="font-family:'JetBrains Mono',monospace;font-weight:600;">{{ $call->src ?: '-' }}</span>
                        @if($call->src_name)
                            <span style="color:var(--text-secondary);font-size:0.72rem;margin-left:0.3rem;">{{ $call->src_name }}</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;font-weight:600;">{{ $call->dst ?: '-' }}</td>
                    <td>
                        @if($call->trunk_name)
                            <span class="codec-tag" style="font-size:0.65rem;">{{ $call->trunk_name }}</span>
                        @else
                            <span style="color:var(--text-secondary);font-size:0.72rem;">—</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $call->formatted_duration }}</td>
                    <td>
                        <span class="status-dot {{ $call->disposition_color }}"></span>
                        <span style="font-size:0.75rem;">{{ $call->disposition_label }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

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
                    <th>{{ __("ui.caller") }}</th>
                    <th>{{ __("ui.destination") }}</th>
                    <th>{{ __("ui.duration") }}</th>
                    <th>{{ __("ui.status") }}</th>
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
    const ctx = document.getElementById('callChart');
    const chartData = @json($chartData);

    const inGrad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
    inGrad.addColorStop(0, 'rgba(88,166,255,0.25)');
    inGrad.addColorStop(1, 'rgba(88,166,255,0)');

    const outGrad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
    outGrad.addColorStop(0, 'rgba(41,182,246,0.2)');
    outGrad.addColorStop(1, 'rgba(41,182,246,0)');

    const missGrad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
    missGrad.addColorStop(0, 'rgba(248,81,73,0.15)');
    missGrad.addColorStop(1, 'rgba(248,81,73,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Entrants',
                    data: chartData.inbound,
                    borderColor: '#58a6ff',
                    backgroundColor: inGrad,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#58a6ff',
                    pointBorderColor: '#0d1117',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Sortants',
                    data: chartData.outbound,
                    borderColor: '#29b6f6',
                    backgroundColor: outGrad,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#29b6f6',
                    pointBorderColor: '#0d1117',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Manques',
                    data: chartData.missed,
                    borderColor: '#f85149',
                    backgroundColor: missGrad,
                    borderWidth: 2,
                    borderDash: [4, 3],
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#f85149',
                    pointBorderColor: '#0d1117',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(22,27,34,0.95)',
                    borderColor: 'rgba(139,148,158,0.2)',
                    borderWidth: 1,
                    titleColor: '#e6edf3',
                    bodyColor: '#8b949e',
                    titleFont: { size: 12, weight: 600 },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    boxPadding: 4,
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(139,148,158,0.06)', drawTicks: false },
                    ticks: { color: '#484f58', font: { size: 11, weight: 500 }, padding: 8 },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(139,148,158,0.06)', drawTicks: false },
                    ticks: { color: '#484f58', font: { size: 11 }, padding: 8, stepSize: 1 },
                    border: { display: false }
                }
            }
        }
    });
});
</script>
@endpush
