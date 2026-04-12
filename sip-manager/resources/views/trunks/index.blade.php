@extends('layouts.app')

@section('title', __('ui.trunks'))
@section('page-title', __('ui.trunks'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.trunks") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Gerer les connexions vers les operateurs</p>
        </div>
        <a href="{{ route('trunks.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> {{ __('ui.new') }} trunk
        </a>
    </div>

    <div class="data-table">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __("ui.name") }}</th>
                    <th>{{ __("ui.type") }}</th>
                    <th>{{ __("ui.host") }}</th>
                    <th>Port</th>
                    <th>{{ __("ui.codecs") }}</th>
                    <th>{{ __("ui.status") }}</th>
                    <th>{{ __("ui.actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trunks as $trunk)
                    <tr>
                        <td style="font-weight:600;">{{ $trunk->name }}</td>
                        <td><span class="trunk-type {{ strtolower($trunk->type) }}">{{ $trunk->type }}</span></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">{{ $trunk->host }}</td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;">{{ $trunk->port }}</td>
                        <td>
                            @if($trunk->codecs)
                                @foreach($trunk->codecs as $codec)
                                    <span class="codec-tag">{{ $codec }}</span>
                                @endforeach
                            @else
                                <span style="color:var(--text-secondary);">—</span>
                            @endif
                        </td>
                        @php
                            $regId = strtolower($trunk->getAsteriskEndpointId());
                            $regIdWithSuffix = $regId . '-reg';
                            $liveStatus = $registrations[$regId] ?? ($registrations[$regIdWithSuffix] ?? null);
                            if (!$liveStatus) {
                                foreach ($registrations as $rName => $rStatus) {
                                    if (str_contains($rName, $regId) || str_contains($rName, strtolower($trunk->name))) {
                                        $liveStatus = $rStatus;
                                        break;
                                    }
                                }
                            }
                            $isRegistered = $liveStatus === 'registered';
                            $statusClass = $isRegistered ? 'online' : ($liveStatus === 'rejected' ? 'error' : ($trunk->status === 'online' ? 'busy' : 'offline'));
                            $statusLabel = $isRegistered ? 'Registered' : ($liveStatus === 'rejected' ? 'Rejete' : ($liveStatus ? ucfirst($liveStatus) : ($trunk->status === 'online' ? 'En ligne' : 'Hors ligne')));
                        @endphp
                        <td>
                            <span class="status-dot {{ $statusClass }}"></span>
                            {{ $statusLabel }}
                        </td>
                        <td>
                            <form action="{{ route('trunks.toggle', $trunk) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="Basculer statut">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>
                            <a href="{{ route('trunks.edit', $trunk) }}" class="btn-icon me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('trunks.destroy', $trunk) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer le trunk {{ $trunk->name }} ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-diagram-3 me-2"></i>{{ __('ui.no_trunks') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $trunks->links() }}
    </div>

    {{-- Auto-refresh: fast after toggle, then every 30s --}}
    <script>
        (function() {
            var toggled = {{ session('trunk_toggled') ? 'true' : 'false' }};
            if (toggled) {
                // Refresh quickly after toggle to pick up registration result
                setTimeout(function() { window.location.replace(window.location.pathname); }, 8000);
            } else {
                // Normal periodic refresh for live status
                setTimeout(function() { window.location.replace(window.location.pathname); }, 30000);
            }
        })();
    </script>
@endsection
