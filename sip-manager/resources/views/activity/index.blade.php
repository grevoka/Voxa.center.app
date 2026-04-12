@extends('layouts.app')

@section('title', __('ui.system_logs'))
@section('page-title', __('ui.system_logs'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.system_logs") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.system_logs') }} de Voxa Center</p>
        </div>
    </div>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __("ui.date") }}</th>
                    <th>{{ __("ui.user") }}</th>
                    <th>{{ __("ui.event") }}</th>
                    <th>{{ __("ui.details") }}</th>
                    <th>{{ __("ui.status") }}</th>
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
                            {{ __('ui.no_activity') }}
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
