@extends('layouts.app')

@section('title', __('ui.ai_history'))
@section('page-title', __('ui.ai_history'))

@section('content')
    <div class="section-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.ai_history") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __("ui.ai_conversations_title") }}</p>
        </div>
    </div>

    {{-- Totals --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="text-align:center;padding:0.75rem;">
                <div style="font-size:1.3rem;font-weight:800;">{{ $totals['count'] }}</div>
                <div style="font-size:0.68rem;color:var(--text-secondary);text-transform:uppercase;">{{ __('ui.ai_conversations') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="text-align:center;padding:0.75rem;">
                <div style="font-size:1.3rem;font-weight:800;font-family:'JetBrains Mono',monospace;">{{ gmdate('H:i:s', $totals['duration']) }}</div>
                <div style="font-size:0.68rem;color:var(--text-secondary);text-transform:uppercase;">{{ __('ui.duration') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="text-align:center;padding:0.75rem;">
                <div style="font-size:1.3rem;font-weight:800;color:#d29922;font-family:'JetBrains Mono',monospace;">${{ number_format($totals['cost'], 2) }}</div>
                <div style="font-size:0.68rem;color:var(--text-secondary);text-transform:uppercase;">{{ __('ui.ai_cost') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="text-align:center;padding:0.75rem;">
                <div style="font-size:1.3rem;font-weight:800;color:#58a6ff;">{{ $totals['turns'] }}</div>
                <div style="font-size:0.68rem;color:var(--text-secondary);text-transform:uppercase;">{{ __('ui.ai_turns') }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap align-items-end">
        <div>
            <label style="font-size:0.68rem;color:var(--text-secondary);">{{ __('ui.search') }}</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('ui.number_keyword') }}" value="{{ request('search') }}" style="min-width:180px;">
        </div>
        <div>
            <label style="font-size:0.68rem;color:var(--text-secondary);">{{ __('ui.ai_model') }}</label>
            <select name="model" class="form-control form-control-sm">
                <option value="">{{ __('ui.all_models') }}</option>
                <option value="gpt-4o-realtime" {{ request('model') == 'gpt-4o-realtime' ? 'selected' : '' }}>GPT-4o</option>
                <option value="mini" {{ request('model') == 'mini' ? 'selected' : '' }}>GPT-4o Mini</option>
            </select>
        </div>
        <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-funnel me-1"></i>{{ __("ui.filter") }}</button>
        @if(request()->hasAny(['search','model']))
            <a href="{{ route('ai-history.index') }}" class="btn btn-sm" style="background:var(--surface-2);color:var(--text-secondary);border:1px solid var(--border);">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="data-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __("ui.date") }}</th>
                    <th>{{ __("ui.caller") }}</th>
                    <th>{{ __('ui.ai_model') }}</th>
                    <th>{{ __('ui.ai_voice') }}</th>
                    <th>{{ __("ui.duration") }}</th>
                    <th>{{ __('ui.ai_turns') }}</th>
                    <th>{{ __('ui.ai_cost') }}</th>
                    <th>{{ __('ui.end_reason') }}</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $c)
                <tr>
                    <td style="font-size:0.78rem;white-space:nowrap;">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;font-weight:600;">{{ $c->caller_number ?: '—' }}</td>
                    <td>
                        @if(str_contains($c->model, 'mini'))
                            <span style="font-size:0.65rem;background:#58a6ff20;color:#58a6ff;border-radius:4px;padding:1px 6px;font-weight:600;">Mini</span>
                        @else
                            <span style="font-size:0.65rem;background:#10b98120;color:#10b981;border-radius:4px;padding:1px 6px;font-weight:600;">4o</span>
                        @endif
                    </td>
                    <td style="font-size:0.78rem;">{{ $c->voice }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;">{{ gmdate('i:s', $c->duration_sec) }}</td>
                    <td style="font-weight:600;">{{ $c->turns }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:#d29922;font-weight:700;">${{ number_format($c->cost_estimated, 3) }}</td>
                    <td>
                        @php
                            $reasons = [
                                'normal' => __('ui.reason_normal'),
                                'caller_hangup' => __('ui.reason_hangup'),
                                'ai_goodbye' => __('ui.reason_goodbye'),
                                'timeout' => __('ui.reason_timeout'),
                                'max_turns' => __('ui.reason_max_turns'),
                                'api_error' => __('ui.reason_error'),
                                'session_error' => __('ui.reason_error'),
                            ];
                            $colors = ['normal'=>'#3fb950','caller_hangup'=>'#58a6ff','ai_goodbye'=>'#3fb950','timeout'=>'#d29922','max_turns'=>'#d29922','api_error'=>'#f85149','session_error'=>'#f85149'];
                        @endphp
                        <span style="font-size:0.65rem;color:{{ $colors[$c->hangup_reason] ?? 'var(--text-secondary)' }};">{{ $reasons[$c->hangup_reason] ?? $c->hangup_reason }}</span>
                    </td>
                    <td>
                        <button class="btn-icon" title="{{ __('ui.view_conversation') }}" style="width:28px;height:28px;font-size:0.75rem;"
                            onclick="showTranscript({{ $c->id }})">
                            <i class="bi bi-chat-text"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4" style="color:var(--text-secondary);">
                    <i class="bi bi-robot me-1"></i>{{ __('ui.no_ai_conv') }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($conversations->hasPages())
            <div class="px-3 py-2">{{ $conversations->links() }}</div>
        @endif
    </div>

    {{-- Transcript modal --}}
    <div id="transcriptModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.6);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="width:550px;max-width:95vw;max-height:85vh;background:#1c1f26;border:1px solid var(--border);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5);display:flex;flex-direction:column;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:between;">
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:0.9rem;" id="trHead">{{ __('ui.conversation') }}</div>
                    <div style="font-size:0.72rem;color:var(--text-secondary);" id="trMeta"></div>
                </div>
                <button onclick="document.getElementById('transcriptModal').style.display='none'" style="background:none;border:none;color:var(--text-secondary);font-size:1.2rem;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="trBody" style="flex:1;overflow-y:auto;padding:1rem 1.25rem;"></div>
        </div>
    </div>

    <script>
    function showTranscript(id) {
        document.getElementById('trBody').innerHTML = '<div style="text-align:center;color:var(--text-secondary);padding:2rem;"><i class="bi bi-hourglass-split"></i> {{ __("ui.loading") }}</div>';
        document.getElementById('transcriptModal').style.display = 'flex';

        fetch('/ai-history/' + id)
            .then(r => r.json())
            .then(data => {
                document.getElementById('trHead').textContent = data.caller + ' — ' + data.date;
                document.getElementById('trMeta').innerHTML =
                    '<span style="color:#10b981;">' + data.model.replace('gpt-4o-realtime-preview-2024-12-17','GPT-4o').replace('gpt-4o-mini-realtime-preview-2024-12-17','GPT-4o Mini') + '</span>' +
                    ' · ' + data.voice +
                    ' · ' + Math.floor(data.duration/60) + 'min' + (data.duration%60) + 's' +
                    ' · <span style="color:#d29922;">$' + parseFloat(data.cost).toFixed(3) + '</span>' +
                    ' · ' + data.turns + ' {{ __("ui.exchanges") }}';

                let html = '';
                (data.transcript || []).forEach(t => {
                    const isAI = t.role === 'assistant';
                    html += '<div style="display:flex;gap:0.5rem;margin-bottom:0.6rem;' + (isAI ? '' : 'flex-direction:row-reverse;') + '">' +
                        '<div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;' +
                        (isAI ? 'background:#10b98120;color:#10b981;' : 'background:#58a6ff20;color:#58a6ff;') + '">' +
                        '<i class="bi ' + (isAI ? 'bi-robot' : 'bi-person') + '"></i></div>' +
                        '<div style="max-width:80%;padding:0.5rem 0.75rem;border-radius:10px;font-size:0.78rem;line-height:1.4;' +
                        (isAI ? 'background:#262a33;color:#e2e4eb;border-bottom-left-radius:2px;' : 'background:#58a6ff20;color:#e2e4eb;border-bottom-right-radius:2px;') + '">' +
                        t.text +
                        '<div style="font-size:0.58rem;color:var(--text-secondary);margin-top:0.2rem;">' + t.time + 's</div>' +
                        '</div></div>';
                });

                if (!data.transcript || data.transcript.length === 0) {
                    html = '<div style="text-align:center;color:var(--text-secondary);padding:2rem;">{{ __("ui.no_transcript") }}</div>';
                }

                document.getElementById('trBody').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('trBody').innerHTML = '<div style="color:#f85149;padding:1rem;">{{ __("ui.error_label") }}: ' + err.message + '</div>';
            });
    }
    </script>
@endsection
