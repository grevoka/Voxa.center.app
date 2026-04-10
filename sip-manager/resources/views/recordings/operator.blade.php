@extends('layouts.operator')

@section('title', 'Mes enregistrements')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Mes enregistrements</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Conversations enregistrees sur votre poste{{ $ext ? " ({$ext})" : '' }}</p>
        </div>
    </div>

    @if(!$ext)
        <div class="data-table" style="padding:2rem;text-align:center;color:var(--text-secondary);">
            <i class="bi bi-exclamation-triangle me-1"></i>Aucune ligne SIP associee a votre compte.
        </div>
    @else
        <div class="data-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Direction</th>
                        <th>Correspondant</th>
                        <th>Duree</th>
                        <th style="width:250px;">Ecouter</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                    <tr>
                        <td style="font-size:0.78rem;white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($r->calldate)->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            @if($r->direction === 'inbound')
                                <span style="font-size:0.68rem;background:#29b6f620;color:#29b6f6;border-radius:4px;padding:1px 6px;font-weight:600;">
                                    <i class="bi bi-telephone-inbound-fill" style="font-size:0.6rem;"></i> Entrant
                                </span>
                            @elseif($r->direction === 'outbound')
                                <span style="font-size:0.68rem;background:#58a6ff20;color:#58a6ff;border-radius:4px;padding:1px 6px;font-weight:600;">
                                    <i class="bi bi-telephone-outbound-fill" style="font-size:0.6rem;"></i> Sortant
                                </span>
                            @else
                                <span style="font-size:0.68rem;background:var(--surface-2);color:var(--text-secondary);border-radius:4px;padding:1px 6px;font-weight:600;">
                                    <i class="bi bi-arrow-left-right" style="font-size:0.6rem;"></i> Interne
                                </span>
                            @endif
                        </td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:0.85rem;font-weight:600;">
                            {{ $r->direction === 'inbound' ? $r->src : $r->dst }}
                        </td>
                        <td style="font-size:0.8rem;font-family:'JetBrains Mono',monospace;">
                            {{ gmdate('i:s', $r->billsec) }}
                        </td>
                        <td>
                            @if($r->has_recording)
                                <audio controls preload="none" style="height:28px;width:220px;">
                                    <source src="{{ route('operator.recordings.play', $r->uniqueid) }}" type="audio/wav">
                                </audio>
                            @else
                                <span style="font-size:0.72rem;color:var(--text-secondary);"><i class="bi bi-slash-circle me-1"></i>Non disponible</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4" style="color:var(--text-secondary);">
                        <i class="bi bi-mic-mute me-1"></i>Aucun enregistrement pour le moment
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($records->hasPages())
                <div class="px-3 py-2">{{ $records->links() }}</div>
            @endif
        </div>
    @endif
@endsection
