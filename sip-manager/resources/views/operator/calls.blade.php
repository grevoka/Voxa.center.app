@extends('layouts.operator')

@section('title', "Journal d'appels")
@section('page-title', "Journal d'appels")

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.call_log") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Historique des appels du poste {{ $line->extension }}</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="disposition" class="form-select form-select-sm">
                <option value="">Tous statuts</option>
                <option value="ANSWERED" {{ request('disposition') == 'ANSWERED' ? 'selected' : '' }}>Repondu</option>
                <option value="NO ANSWER" {{ request('disposition') == 'NO ANSWER' ? 'selected' : '' }}>Sans reponse</option>
                <option value="BUSY" {{ request('disposition') == 'BUSY' ? 'selected' : '' }}>Occupe</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr><th>{{ __("ui.date") }}</th><th>{{ __("ui.direction") }}</th><th>{{ __("ui.correspondent") }}</th><th>{{ __("ui.duration") }}</th><th>{{ __("ui.status") }}</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $call)
                @php
                    // The dialplan stamps CDR(direction)=inbound|outbound, so trust
                    // that rather than comparing src to the extension — outbound
                    // legs carry the operator's caller_id (e.g. 33352745112) in
                    // src, never "1001", which would mis-classify everything.
                    $isOutbound = $call->direction === 'outbound';
                    $correspondent = $isOutbound ? $call->dst : $call->src;
                    // Strip French international prefix for human readability
                    // (0033647635056 → 0647635056). Storage is untouched.
                    $digits = preg_replace('/\D/', '', (string) $correspondent);
                    if (str_starts_with($digits, '0033') && strlen($digits) === 13) {
                        $correspondent = '0' . substr($digits, 4);
                    } elseif (str_starts_with($digits, '33') && strlen($digits) === 11) {
                        $correspondent = '0' . substr($digits, 2);
                    }
                    // Hide the internal "->LABEL" DID marker if it leaked into src_name.
                    $shownName = $call->src_name && !str_starts_with($call->src_name, '->') ? $call->src_name : null;
                    // Which line was called? Look the DST up in the CallerId map.
                    $dstDigits = preg_replace('/\D/', '', (string) $call->dst);
                    $dstTail = strlen($dstDigits) >= 9 ? substr($dstDigits, -9) : $dstDigits;
                    $lineLabel = ($lineBadges ?? [])[$dstTail] ?? null;
                @endphp
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;">{{ $call->started_at?->copy()->setTimezone('Europe/Paris')->format('d/m/Y H:i:s') }}</td>
                    <td>
                        @if($isOutbound)
                            <i class="bi bi-telephone-outbound-fill" style="color:var(--warning);font-size:0.75rem;"></i> <span style="font-size:0.72rem;">{{ __("ui.outbound") }}</span>
                        @else
                            <i class="bi bi-telephone-inbound-fill" style="color:var(--info);font-size:0.75rem;"></i> <span style="font-size:0.72rem;">{{ __("ui.inbound") }}</span>
                        @endif
                        @if($lineLabel)
                            <span style="display:inline-block;padding:0.05rem 0.35rem;margin-left:0.35rem;border-radius:4px;background:var(--accent-dim);color:var(--accent);font-size:0.62rem;font-weight:700;letter-spacing:0.5px;">{{ $lineLabel }}</span>
                        @endif
                    </td>
                    <td style="font-size:0.82rem;">
                        <span style="font-family:'JetBrains Mono',monospace;font-weight:600;">{{ $correspondent }}</span>
                        @if($shownName && !$isOutbound)
                            <span style="color:var(--text-secondary);font-size:0.72rem;margin-left:0.3rem;">{{ $shownName }}</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">{{ $call->formatted_duration }}</td>
                    <td><span class="status-dot {{ $call->disposition_color }}"></span> <span style="font-size:0.75rem;">{{ $call->disposition_label }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">{{ __('ui.no_calls') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="mt-3 d-flex justify-content-center">{{ $logs->links() }}</div>
    @endif
@endsection
