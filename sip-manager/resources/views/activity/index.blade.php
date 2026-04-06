@extends('layouts.app')

@section('title', 'Logs systeme')
@section('page-title', 'Logs systeme')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Logs systeme</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Journal d'activite de SIP.ctrl</p>
        </div>
    </div>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Evenement</th>
                    <th>Details</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-secondary);white-space:nowrap;">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td style="font-size:0.82rem;">
                            {{ $log->user?->name ?? 'Systeme' }}
                        </td>
                        <td style="font-weight:500;">{{ $log->event }}</td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;">{{ $log->details }}</td>
                        <td>
                            @if($log->level === 'success')
                                <span class="status-dot online"></span>
                            @elseif($log->level === 'warning')
                                <span class="status-dot busy"></span>
                            @elseif($log->level === 'error')
                                <span class="status-dot error"></span>
                            @else
                                <span class="status-dot offline"></span>
                            @endif
                            {{ ucfirst($log->level) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">
                            Aucune activite enregistree
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $logs->links() }}
    </div>
    @endif
@endsection
