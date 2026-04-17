@extends('layouts.app')

@section('title', 'Call Widget')
@section('page-title', 'Call Widget')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Call Widget</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.widget_desc') ?? 'Embeddable WebRTC call buttons for external websites' }}</p>
        </div>
        <a href="{{ route('widgets.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> {{ __('ui.new') }} Widget
        </a>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('ui.name') }}</th>
                    <th>Token</th>
                    <th>{{ __('ui.domain') ?? 'Domain' }}</th>
                    <th>{{ __('ui.destination') }}</th>
                    <th>{{ __('ui.calls') ?? 'Calls' }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($widgets as $widget)
                    <tr style="{{ !$widget->enabled ? 'opacity:0.5;' : '' }}">
                        <td style="font-weight:600;">{{ $widget->name }}</td>
                        <td>
                            <code style="font-size:0.72rem;color:var(--accent);">{{ substr($widget->token, 0, 12) }}...</code>
                        </td>
                        <td style="font-size:0.82rem;color:var(--text-secondary);">{{ $widget->domain }}</td>
                        <td>
                            @if($widget->callflow_id)
                                <span class="codec-tag" style="color:#bc8cff;border-color:#bc8cff40;">
                                    <i class="bi bi-diagram-3 me-1"></i>{{ $widget->callflow?->name ?? 'CallFlow #'.$widget->callflow_id }}
                                </span>
                            @elseif($widget->extension)
                                <span class="codec-tag" style="color:var(--accent);border-color:var(--accent-mid);">
                                    <i class="bi bi-telephone me-1"></i>{{ $widget->extension }}
                                </span>
                            @endif
                        </td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">
                            {{ number_format($widget->call_count) }}
                            @if($widget->last_used_at)
                                <br><small style="color:var(--text-secondary);font-size:0.65rem;">{{ $widget->last_used_at->diffForHumans() }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="status-dot {{ $widget->enabled ? 'online' : 'offline' }}"></span>
                            {{ $widget->enabled ? __('ui.active') : __('ui.inactive') }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <form action="{{ route('widgets.toggle', $widget) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="{{ __('ui.toggle_status') }}"><i class="bi bi-power"></i></button>
                                </form>
                                <a href="{{ route('widgets.edit', $widget) }}" class="btn-icon" title="{{ __('ui.edit') }}"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('widgets.destroy', $widget) }}" method="POST" onsubmit="return confirm('Delete widget {{ $widget->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon danger" title="{{ __('ui.delete') }}"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-window-dock me-2" style="font-size:1.5rem;"></i><br>
                            No widgets configured yet.
                            <br><a href="{{ route('widgets.create') }}" style="color:var(--accent);font-size:0.82rem;">Create your first widget</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $widgets->links() }}</div>
@endsection
