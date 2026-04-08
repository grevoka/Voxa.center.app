<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand" style="padding:0.75rem 1rem;">
        <a href="{{ route('dashboard') }}" style="text-decoration:none;display:block;">
            <img src="{{ asset('images/logo.png') }}" alt="Voxa Center" style="width:100%;max-width:200px;height:auto;object-fit:contain;">
        </a>
    </div>
    <div class="nav-section">
        <a class="nav-item-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
        </a>

        <div class="nav-group" data-group="sip">
            <div class="nav-group-title">
                <span>Gestion SIP</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('lines.*') ? 'active' : '' }}" href="{{ route('lines.index') }}">
                    <i class="bi bi-telephone-fill"></i> Lignes
                    <span class="nav-badge">{{ \App\Models\SipLine::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('trunks.*') ? 'active' : '' }}" href="{{ route('trunks.index') }}">
                    <i class="bi bi-diagram-3-fill"></i> Trunks SIP
                    <span class="nav-badge">{{ \App\Models\Trunk::count() }}</span>
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="routage">
            <div class="nav-group-title">
                <span>Routage</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('callflows.*') ? 'active' : '' }}" href="{{ route('callflows.index') }}">
                    <i class="bi bi-diagram-2-fill"></i> Scenarios
                    <span class="nav-badge">{{ \App\Models\CallFlow::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('outbound.*') ? 'active' : '' }}" href="{{ route('outbound.index') }}">
                    <i class="bi bi-telephone-outbound-fill"></i> Routes sortantes
                    <span class="nav-badge">{{ \App\Models\CallContext::where('direction', 'outbound')->where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('contexts.*') ? 'active' : '' }}" href="{{ route('contexts.index') }}">
                    <i class="bi bi-signpost-split-fill"></i> Contextes
                    <span class="nav-badge">{{ \App\Models\CallContext::where('enabled', true)->count() }}</span>
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="services">
            <div class="nav-group-title">
                <span>Services</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('queues.*') ? 'active' : '' }}" href="{{ route('queues.index') }}">
                    <i class="bi bi-people-fill"></i> Files d'attente
                    <span class="nav-badge">{{ \App\Models\CallQueue::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('conferences.*') ? 'active' : '' }}" href="{{ route('conferences.index') }}">
                    <i class="bi bi-camera-video-fill"></i> Conferences
                    <span class="nav-badge">{{ \App\Models\ConferenceRoom::where('enabled', true)->count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('voicemail.*') ? 'active' : '' }}" href="{{ route('voicemail.index') }}">
                    <i class="bi bi-voicemail"></i> Messagerie vocale
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="audio">
            <div class="nav-group-title">
                <span>Audio</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('audio.*') ? 'active' : '' }}" href="{{ route('audio.index') }}">
                    <i class="bi bi-file-earmark-music-fill"></i> Fichiers audio
                    <span class="nav-badge">{{ \App\Models\AudioFile::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('moh.*') ? 'active' : '' }}" href="{{ route('moh.index') }}">
                    <i class="bi bi-music-note-list"></i> Musiques d'attente
                </a>
                <a class="nav-item-custom {{ request()->routeIs('codecs.*') ? 'active' : '' }}" href="{{ route('codecs.index') }}">
                    <i class="bi bi-soundwave"></i> Codecs
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="monitoring">
            <div class="nav-group-title">
                <span>Monitoring</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('live.*') ? 'active' : '' }}" href="{{ route('live.index') }}">
                    <i class="bi bi-broadcast-pin"></i> Supervision live
                </a>
                <a class="nav-item-custom {{ request()->routeIs('asterisk.*') ? 'active' : '' }}" href="{{ route('asterisk.logs') }}">
                    <i class="bi bi-terminal-fill"></i> Asterisk Console
                </a>
                <a class="nav-item-custom {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                    <i class="bi bi-journal-text"></i> Journal d'appels
                    <span class="nav-badge">{{ \App\Models\CallLog::count() }}</span>
                </a>
                <a class="nav-item-custom {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}">
                    <i class="bi bi-clock-history"></i> Logs systeme
                </a>
            </div>
        </div>

        <div class="nav-group" data-group="config">
            <div class="nav-group-title">
                <span>Configuration</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-items">
                <a class="nav-item-custom {{ request()->routeIs('firewall.*') ? 'active' : '' }}" href="{{ route('firewall.index') }}">
                    <i class="bi bi-shield-lock-fill"></i> Firewall SIP
                </a>
                <a class="nav-item-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear-fill"></i> Parametres
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
