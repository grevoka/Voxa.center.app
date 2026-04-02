<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h5><span>//</span> SIP<span>.</span>ctrl</h5>
        <small>Telecom Management</small>
    </div>
    <div class="nav-section">
        <div class="nav-section-title">General</div>
        <a class="nav-item-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
        </a>

        <div class="nav-section-title mt-3">Gestion SIP</div>
        <a class="nav-item-custom {{ request()->routeIs('lines.*') ? 'active' : '' }}" href="{{ route('lines.index') }}">
            <i class="bi bi-telephone-fill"></i> Lignes
            <span class="nav-badge">{{ \App\Models\SipLine::count() }}</span>
        </a>
        <a class="nav-item-custom {{ request()->routeIs('trunks.*') ? 'active' : '' }}" href="{{ route('trunks.index') }}">
            <i class="bi bi-diagram-3-fill"></i> Trunks SIP
            <span class="nav-badge">{{ \App\Models\Trunk::count() }}</span>
        </a>

        <div class="nav-section-title mt-3">Routage</div>
        <a class="nav-item-custom {{ request()->routeIs('callflows.*') ? 'active' : '' }}" href="{{ route('callflows.index') }}">
            <i class="bi bi-diagram-2-fill"></i> Scenarios
            <span class="nav-badge">{{ \App\Models\CallFlow::where('enabled', true)->count() }}</span>
        </a>
        <a class="nav-item-custom {{ request()->routeIs('queues.*') ? 'active' : '' }}" href="{{ route('queues.index') }}">
            <i class="bi bi-people-fill"></i> Files d'attente
            <span class="nav-badge">{{ \App\Models\CallQueue::where('enabled', true)->count() }}</span>
        </a>
        <a class="nav-item-custom {{ request()->routeIs('contexts.*') ? 'active' : '' }}" href="{{ route('contexts.index') }}">
            <i class="bi bi-signpost-split-fill"></i> Contextes
            <span class="nav-badge">{{ \App\Models\CallContext::where('enabled', true)->count() }}</span>
        </a>
        <a class="nav-item-custom {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
            <i class="bi bi-journal-text"></i> Journal d'appels
            <span class="nav-badge">{{ \App\Models\CallLog::count() }}</span>
        </a>

        <div class="nav-section-title mt-3">Monitoring</div>
        <a class="nav-item-custom {{ request()->routeIs('asterisk.*') ? 'active' : '' }}" href="{{ route('asterisk.logs') }}">
            <i class="bi bi-terminal-fill"></i> Asterisk Console
        </a>

        <div class="nav-section-title mt-3">Configuration</div>
        <a class="nav-item-custom {{ request()->routeIs('codecs.*') ? 'active' : '' }}" href="{{ route('codecs.index') }}">
            <i class="bi bi-soundwave"></i> Codecs
        </a>
        <a class="nav-item-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
            <i class="bi bi-gear-fill"></i> Parametres
        </a>
    </div>
    <div style="padding: 1rem; border-top: 1px solid var(--border);">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:8px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-fill" style="color:var(--accent);"></i>
            </div>
            <div>
                <div style="font-size:0.8rem;font-weight:600;">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div style="font-size:0.65rem;color:var(--text-secondary);">{{ auth()->user()->email ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="btn-icon" title="Deconnexion" style="width:28px;height:28px;font-size:0.75rem;">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
