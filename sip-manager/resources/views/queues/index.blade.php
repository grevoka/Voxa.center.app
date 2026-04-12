@extends('layouts.app')

@section('title', 'Files d\')
@section('page-title', 'Files d\')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ __("ui.queues") }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Gerez les files d'attente pour vos appels entrants</p>
        </div>
        <a href="{{ route('queues.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Nouvelle file
        </a>
    </div>

    @if($queues->isEmpty())
        <div class="stat-card text-center" style="padding:3rem;">
            <i class="bi bi-people" style="font-size:3rem; color:var(--text-secondary); opacity:.3;"></i>
            <p style="color:var(--text-secondary); margin-top:1rem;">{{ __('ui.no_queues') }}.</p>
            <a href="{{ route('queues.create') }}" class="btn btn-accent mt-2">
                <i class="bi bi-plus-lg me-1"></i> {{ __('ui.create_queue') }}
            </a>
        </div>
    @else
        <div class="data-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __("ui.name") }}</th>
                        <th>{{ __("ui.strategy") }}</th>
                        <th>Membres</th>
                        <th>Timeout</th>
                        <th>Musique</th>
                        <th>{{ __("ui.status") }}</th>
                        <th>{{ __("ui.actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($queues as $queue)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $queue->display_name ?: $queue->name }}</div>
                                @if($queue->display_name)
                                    <small style="color:var(--text-secondary); font-family:'JetBrains Mono',monospace; font-size:0.7rem;">{{ $queue->name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="trunk-type sip">{{ \App\Models\CallQueue::$strategies[$queue->strategy] ?? $queue->strategy }}</span>
                            </td>
                            <td>
                                @php $members = $queue->members ?? []; @endphp
                                @if(count($members))
                                    @foreach(array_slice($members, 0, 4) as $m)
                                        <span class="codec-tag" style="color:var(--accent); border-color:var(--accent-mid);">{{ $m['extension'] ?? '?' }}</span>
                                    @endforeach
                                    @if(count($members) > 4)
                                        <span style="color:var(--text-secondary); font-size:0.72rem;">+{{ count($members) - 4 }}</span>
                                    @endif
                                @else
                                    <span style="color:var(--text-secondary); font-size:0.75rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-family:'JetBrains Mono',monospace; font-size:0.78rem;">{{ $queue->timeout }}s</span>
                            </td>
                            <td>
                                <code style="font-size:0.75rem; color:var(--text-secondary);">{{ $queue->music_on_hold }}</code>
                            </td>
                            <td>
                                <span class="status-dot {{ $queue->enabled ? 'online' : 'offline' }}"></span>
                                <span style="font-size:0.75rem;">{{ $queue->enabled ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('queues.edit', $queue) }}" class="btn-icon" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('queues.destroy', $queue) }}" method="POST"
                                          onsubmit="return confirm('Supprimer cette file d\'attente ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $queues->links() }}</div>
    @endif
@endsection
