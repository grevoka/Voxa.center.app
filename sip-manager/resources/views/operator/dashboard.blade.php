@extends('layouts.operator')

@section('title', 'Mon espace')
@section('page-title', 'Mon espace')

@section('content')
    <div class="row g-3 mb-4">
        {{-- Info poste --}}
        <div class="col-lg-4">
            <div class="stat-card" style="padding:1.25rem;height:100%;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-telephone-fill" style="font-size:1.3rem;color:var(--accent);"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-weight:800;font-family:'JetBrains Mono',monospace;line-height:1;">{{ $line->extension }}</div>
                        <div style="font-size:0.82rem;color:var(--text-secondary);">{{ $line->name }}</div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-1" style="font-size:0.8rem;">
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Protocole</span><span class="codec-tag" style="font-size:0.62rem;">{{ $line->protocol }}</span></div>
                    @if($line->caller_id)
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Caller ID</span><span style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $line->caller_id }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between"><span style="color:var(--text-secondary);">Messagerie</span><span style="color:{{ $line->voicemail_enabled ? 'var(--success)' : 'var(--text-secondary)' }};">{{ $line->voicemail_enabled ? 'Active' : 'Desactivee' }}</span></div>
                </div>
            </div>
        </div>

        {{-- Stats du jour --}}
        <div class="col-lg-8">
            <div class="row g-3 h-100">
                <div class="col-4 col-md">
                    <div class="stat-card" style="text-align:center;padding:1rem;height:100%;">
                        <div style="font-size:1.8rem;font-weight:800;">{{ $todayStats['total'] }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Appels</div>
                    </div>
                </div>
                <div class="col-4 col-md">
                    <div class="stat-card" style="text-align:center;padding:1rem;height:100%;">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--success);">{{ $todayStats['answered'] }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Repondus</div>
                    </div>
                </div>
                <div class="col-4 col-md">
                    <div class="stat-card" style="text-align:center;padding:1rem;height:100%;">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--danger);">{{ $todayStats['missed'] }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Manques</div>
                    </div>
                </div>
                <div class="col-4 col-md">
                    <div class="stat-card" style="text-align:center;padding:1rem;height:100%;">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--info);">{{ $todayStats['inbound'] }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Entrants</div>
                    </div>
                </div>
                <div class="col-4 col-md">
                    <div class="stat-card" style="text-align:center;padding:1rem;height:100%;">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--warning);">{{ $todayStats['outbound'] }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Sortants</div>
                    </div>
                </div>
                <div class="col-4 col-md">
                    <a href="{{ route('operator.voicemail') }}" class="stat-card d-block" style="text-align:center;padding:1rem;height:100%;text-decoration:none;color:inherit;{{ $vmCount > 0 ? 'border-color:var(--purple);' : '' }}">
                        <div style="font-size:1.8rem;font-weight:800;color:{{ $vmCount > 0 ? 'var(--purple)' : 'var(--text-secondary)' }};">{{ $vmCount }}</div>
                        <div style="font-size:0.68rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Messages</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Derniers appels --}}
        <div class="col-lg-8">
            <div class="data-table">
                <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
                    <h6 class="mb-0" style="font-size:0.88rem;font-weight:700;"><i class="bi bi-journal-text me-2" style="color:var(--accent);"></i>{{ __('ui.last_calls') }}</h6>
                    <a href="{{ route('operator.calls') }}" style="font-size:0.72rem;color:var(--accent);text-decoration:none;">Voir tout <i class="bi bi-arrow-right"></i></a>
                </div>
                <table class="table mb-0">
                    <thead>
                        <tr><th>{{ __("ui.date") }}</th><th>Dir.</th><th>{{ __("ui.correspondent") }}</th><th>{{ __("ui.duration") }}</th><th>{{ __("ui.status") }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentCalls as $call)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:var(--text-secondary);white-space:nowrap;">{{ $call->started_at?->format('d/m H:i') }}</td>
                            <td>
                                @if($call->src == $line->extension)
                                    <i class="bi bi-arrow-up-right" style="color:var(--warning);font-size:0.8rem;"></i>
                                @else
                                    <i class="bi bi-arrow-down-left" style="color:var(--info);font-size:0.8rem;"></i>
                                @endif
                            </td>
                            <td style="font-size:0.82rem;">
                                <span style="font-family:'JetBrains Mono',monospace;font-weight:600;">{{ $call->src == $line->extension ? $call->dst : $call->src }}</span>
                                @if($call->src_name && $call->src != $line->extension)
                                    <span style="color:var(--text-secondary);font-size:0.7rem;margin-left:0.25rem;">{{ $call->src_name }}</span>
                                @endif
                            </td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">{{ $call->formatted_duration }}</td>
                            <td><span class="status-dot {{ $call->disposition_color }}"></span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);font-size:0.82rem;">{{ __('ui.no_calls') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Queues --}}
        <div class="col-lg-4">
            @if($queues->count())
            <div class="stat-card" style="padding:1.25rem;">
                <h6 style="font-weight:700;font-size:0.88rem;margin-bottom:0.75rem;"><i class="bi bi-people me-2" style="color:var(--purple);"></i>{{ __("ui.queues") }}</h6>
                @foreach($queues as $queue)
                <div style="padding:0.5rem 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}" class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-weight:600;font-size:0.85rem;">{{ $queue->display_name ?: $queue->name }}</div>
                        <div style="font-size:0.7rem;color:var(--text-secondary);">{{ count($queue->members ?? []) }} membres</div>
                    </div>
                    <span class="codec-tag" style="font-size:0.6rem;">{{ $queue->strategy }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
@endsection
