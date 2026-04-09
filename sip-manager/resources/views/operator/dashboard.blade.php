@extends('layouts.operator')

@section('title', 'Mon espace')
@section('page-title', 'Mon espace')

@section('content')
    {{-- Stats du jour --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;">{{ $todayStats['total'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Total appels</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--success);">{{ $todayStats['answered'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Repondus</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--danger);">{{ $todayStats['missed'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Manques</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--info);">{{ $todayStats['inbound'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Entrants</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--warning);">{{ $todayStats['outbound'] }}</div>
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Sortants</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card" style="text-align:center;padding:1rem;">
                @if($vmCount > 0)
                    <div style="font-size:1.4rem;font-weight:800;color:var(--purple);">{{ $vmCount }}</div>
                @else
                    <div style="font-size:1.4rem;font-weight:800;color:var(--text-secondary);">0</div>
                @endif
                <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Messages vocaux</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Derniers appels --}}
        <div class="col-lg-8">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.9rem;font-weight:700;"><i class="bi bi-journal-text me-2" style="color:var(--accent);"></i>Derniers appels</h6>
                    <a href="{{ route('operator.calls') }}" style="font-size:0.72rem;color:var(--accent);text-decoration:none;">Voir tout <i class="bi bi-arrow-right"></i></a>
                </div>
                <table class="table mb-0">
                    <thead>
                        <tr><th>Date</th><th>Direction</th><th>Correspondant</th><th>Duree</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentCalls as $call)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--text-secondary);">{{ $call->started_at?->format('d/m H:i') }}</td>
                            <td>
                                @if($call->src == $line->extension)
                                    <i class="bi bi-telephone-outbound-fill" style="color:var(--warning);font-size:0.75rem;"></i>
                                @else
                                    <i class="bi bi-telephone-inbound-fill" style="color:var(--info);font-size:0.75rem;"></i>
                                @endif
                            </td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">
                                {{ $call->src == $line->extension ? $call->dst : $call->src }}
                                @if($call->src_name && $call->src != $line->extension)
                                    <span style="color:var(--text-secondary);font-size:0.72rem;margin-left:0.3rem;">{{ $call->src_name }}</span>
                                @endif
                            </td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $call->formatted_duration }}</td>
                            <td><span class="status-dot {{ $call->disposition_color }}"></span> <span style="font-size:0.75rem;">{{ $call->disposition_label }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3" style="color:var(--text-secondary);font-size:0.82rem;">Aucun appel aujourd'hui</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Infos poste + queues --}}
        <div class="col-lg-4">
            {{-- Info poste --}}
            <div class="stat-card mb-3" style="padding:1rem;">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;"><i class="bi bi-telephone me-1" style="color:var(--accent);"></i>Mon poste</h6>
                <div class="d-flex flex-column gap-2" style="font-size:0.82rem;">
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Extension</span><span style="font-weight:600;font-family:'JetBrains Mono',monospace;">{{ $line->extension }}</span></div>
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Nom</span><span style="font-weight:600;">{{ $line->name }}</span></div>
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Protocole</span><span class="codec-tag" style="font-size:0.65rem;">{{ $line->protocol }}</span></div>
                    @if($line->caller_id)
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Caller ID</span><span style="font-weight:600;">{{ $line->caller_id }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Messagerie</span><span>{{ $line->voicemail_enabled ? 'Active' : 'Desactivee' }}</span></div>
                </div>
            </div>

            {{-- Queues --}}
            @if($queues->count())
            <div class="stat-card" style="padding:1rem;">
                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;"><i class="bi bi-people me-1" style="color:var(--purple);"></i>Files d'attente</h6>
                @foreach($queues as $queue)
                <div style="padding:0.4rem 0;border-bottom:1px solid var(--border);font-size:0.82rem;" class="d-flex justify-content-between">
                    <span style="font-weight:600;">{{ $queue->display_name ?: $queue->name }}</span>
                    <span class="codec-tag" style="font-size:0.6rem;">{{ $queue->strategy }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
@endsection
