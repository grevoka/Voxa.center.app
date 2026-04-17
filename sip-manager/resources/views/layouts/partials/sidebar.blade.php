<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand" style="padding:0.75rem 1rem;text-align:center;">
        <a href="{{ route('dashboard') }}" style="text-decoration:none;display:block;">
            <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="Voxa Center" style="height:48px;object-fit:contain;">
            <div style="font-size:1.05rem;font-weight:800;letter-spacing:-0.5px;margin-top:0.3rem;">
                <span style="background:linear-gradient(135deg,#58a6ff,#bc6ff1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">voxa</span><span style="color:var(--text-secondary);font-weight:400;">.</span><span style="color:#e2e4eb;font-weight:600;">center</span>
            </div>
        </a>
    </div>
    <div class="nav-section">
        <a class="nav-item-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i> {{ __("ui.dashboard") }}
        </a>

        <div class="nav-group" data-group="sip">
            <div class="nav-group-title">
                <span>{{ __("ui.sip_management") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('lines.*') ? 'active' : '' }}" href="{{ route('lines.index') }}">
                    <i class="bi bi-telephone-fill"></i> {{ __("ui.lines") }}
                    <span class="nav-badge">{{ \App\Models\SipLine::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('trunks.*') ? 'active' : '' }}" href="{{ route('trunks.index') }}">
                    <i class="bi bi-diagram-3-fill"></i> {{ __("ui.trunks") }}
                    <span class="nav-badge">{{ \App\Models\Trunk::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('operators.*') ? 'active' : '' }}" href="{{ route('operators.index') }}">
                    <i class="bi bi-headset"></i> {{ __("ui.operators") }}
                    <span class="nav-badge">{{ \App\Models\User::where('role', 'operator')->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('caller-ids.*') ? 'active' : '' }}" href="{{ route('caller-ids.index') }}">
                    <i class="bi bi-person-vcard-fill"></i> {{ __("ui.caller_id") }}
                    <span class="nav-badge">{{ \App\Models\CallerId::where('is_active', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('widgets.*') ? 'active' : '' }}" href="{{ route('widgets.index') }}">
                    <i class="bi bi-window-dock"></i> Call Widget
                    <span class="nav-badge">{{ \App\Models\WidgetToken::where('enabled', true)->count() }}</span>
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="routage">
            <div class="nav-group-title">
                <span>{{ __("ui.routing") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('callflows.*') ? 'active' : '' }}" href="{{ route('callflows.index') }}">
                    <i class="bi bi-diagram-2-fill"></i> {{ __("ui.scenarios") }}
                    <span class="nav-badge">{{ \App\Models\CallFlow::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('outbound.*') ? 'active' : '' }}" href="{{ route('outbound.index') }}">
                    <i class="bi bi-telephone-outbound-fill"></i> {{ __("ui.outbound_routes") }}
                    <span class="nav-badge">{{ \App\Models\CallContext::where('direction', 'outbound')->where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('contexts.*') ? 'active' : '' }}" href="{{ route('contexts.index') }}">
                    <i class="bi bi-signpost-split-fill"></i> {{ __("ui.contexts") }}
                    <span class="nav-badge">{{ \App\Models\CallContext::where('enabled', true)->count() }}</span>
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="services">
            <div class="nav-group-title">
                <span>{{ __("ui.services") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('queues.*') ? 'active' : '' }}" href="{{ route('queues.index') }}">
                    <i class="bi bi-people-fill"></i> {{ __("ui.queues") }}
                    <span class="nav-badge">{{ \App\Models\CallQueue::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('conferences.*') ? 'active' : '' }}" href="{{ route('conferences.index') }}">
                    <i class="bi bi-camera-video-fill"></i> {{ __("ui.conferences") }}
                    <span class="nav-badge">{{ \App\Models\ConferenceRoom::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('voicemail.*') ? 'active' : '' }}" href="{{ route('voicemail.index') }}">
                    <i class="bi bi-voicemail"></i> {{ __("ui.voicemail") }}
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="audio">
            <div class="nav-group-title">
                <span>{{ __("ui.audio") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('audio.*') ? 'active' : '' }}" href="{{ route('audio.index') }}">
                    <i class="bi bi-file-earmark-music-fill"></i> {{ __("ui.audio_files") }}
                    <span class="nav-badge">{{ \App\Models\AudioFile::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('moh.*') ? 'active' : '' }}" href="{{ route('moh.index') }}">
                    <i class="bi bi-music-note-list"></i> {{ __("ui.music_on_hold") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('codecs.*') ? 'active' : '' }}" href="{{ route('codecs.index') }}">
                    <i class="bi bi-soundwave"></i> {{ __("ui.codecs") }}
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="monitoring">
            <div class="nav-group-title">
                <span>{{ __("ui.monitoring") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('live.*') ? 'active' : '' }}" href="{{ route('live.index') }}">
                    <i class="bi bi-broadcast-pin"></i> {{ __("ui.live") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('asterisk.*') ? 'active' : '' }}" href="{{ route('asterisk.logs') }}">
                    <i class="bi bi-terminal-fill"></i> {{ __("ui.asterisk_console") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                    <i class="bi bi-journal-text"></i> {{ __("ui.call_log") }}
                    <span class="nav-badge">{{ \App\Models\CallLog::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('recordings.*') ? 'active' : '' }}" href="{{ route('recordings.index') }}">
                    <i class="bi bi-mic-fill"></i> {{ __("ui.recordings") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}">
                    <i class="bi bi-clock-history"></i> {{ __("ui.system_logs") }}
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="ai">
            <div class="nav-group-title">
                <span>{{ __("ui.ai") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('ai-history.*') ? 'active' : '' }}" href="{{ route('ai-history.index') }}">
                    <i class="bi bi-chat-text"></i> {{ __("ui.ai_conversations") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('ai-context.*') ? 'active' : '' }}" href="{{ route('ai-context.index') }}">
                    <i class="bi bi-book"></i> {{ __("ui.ai_knowledge") }}
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="config">
            <div class="nav-group-title">
                <span>{{ __("ui.config") }}</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('firewall.*') ? 'active' : '' }}" href="{{ route('firewall.index') }}">
                    <i class="bi bi-shield-lock-fill"></i> {{ __("ui.firewall") }}
                </a>
                <a class="nav-item-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear-fill"></i> {{ __("ui.settings") }}
                </a>
            </div>
        </div>
    </div>
    <div style="padding: 0.5rem 0.75rem; border-top: 1px solid var(--border);">
        <div class="d-flex align-items-center gap-2" style="padding:0.35rem 0.75rem;">
            <div style="width:28px;height:28px;border-radius:8px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-fill" style="color:var(--accent); font-size:0.8rem;"></i>
            </div>
            <a href="{{ route('profile.edit') }}" style="min-width:0;flex:1;text-decoration:none;color:inherit;" title="Mon profil">
                <div style="font-size:0.75rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div style="font-size:0.6rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->email ?? '' }}</div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="btn-icon" title="Deconnexion" style="width:24px;height:24px;font-size:0.65rem;">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const groups = document.querySelectorAll('.nav-group');
        const saved = JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}');

        groups.forEach(group => {
            const key = group.dataset.group;
            const title = group.querySelector('.nav-group-title');
            const hasActive = group.querySelector('.nav-item-custom.active');

            // Restore state: open if has active item, otherwise use saved state
            if (hasActive) {
                group.classList.remove('collapsed');
            } else if (saved[key]) {
                group.classList.add('collapsed');
            }

            title.addEventListener('click', function() {
                group.classList.toggle('collapsed');
                const state = JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}');
                state[key] = group.classList.contains('collapsed');
                localStorage.setItem('sidebar_collapsed', JSON.stringify(state));
            });
        });
    });
    </script>
</aside>
