<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand" style="padding:0.75rem 1rem;text-align:center;">
        <a href="{{ route('operator.dashboard') }}" style="text-decoration:none;display:block;">
            <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="Voxa Center" style="height:48px;object-fit:contain;">
            <div style="font-size:1.05rem;font-weight:800;letter-spacing:-0.5px;margin-top:0.3rem;">
                <span style="background:linear-gradient(135deg,#58a6ff,#bc6ff1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">voxa</span><span style="color:var(--text-secondary);font-weight:400;">.</span><span style="color:var(--text-primary);font-weight:600;">center</span>
            </div>
        </a>
    </div>
    <div class="nav-section">
        {{-- Poste info --}}
        @if(auth()->user()->sipLine)
        <div style="padding:0.75rem;margin:0.5rem 0.75rem;background:var(--accent-dim);border:1px solid var(--accent-mid);border-radius:10px;">
            <div style="font-size:0.7rem;color:var(--text-secondary);text-transform:uppercase;font-weight:600;letter-spacing:.5px;">Mon poste</div>
            <div style="font-size:1.2rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--accent);">{{ auth()->user()->sipLine->extension }}</div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">{{ auth()->user()->sipLine->name }}</div>
        </div>
        @endif

        <a class="nav-item-custom {{ request()->routeIs('operator.dashboard') ? 'active' : '' }}" href="{{ route('operator.dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
        </a>
        <a class="nav-item-custom {{ request()->routeIs('operator.calls') ? 'active' : '' }}" href="{{ route('operator.calls') }}">
            <i class="bi bi-journal-text"></i> Journal d'appels
        </a>
        <a class="nav-item-custom {{ request()->routeIs('operator.voicemail*') ? 'active' : '' }}" href="{{ route('operator.voicemail') }}">
            <i class="bi bi-voicemail"></i> Messagerie vocale
        </a>
        <a class="nav-item-custom {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
            <i class="bi bi-person-gear"></i> Mon profil
        </a>
        <a class="nav-item-custom {{ request()->routeIs('help.*') ? 'active' : '' }}" href="{{ route('help.index') }}">
            <i class="bi bi-question-circle"></i> Aide
        </a>
    </div>

    {{-- Softphone toggle button --}}
    @if(auth()->user()->sipLine)
    <div style="padding:0.5rem 0.75rem;">
        <button onclick="document.getElementById('softphonePopup').style.display=document.getElementById('softphonePopup').style.display==='none'?'block':'none'"
                class="btn-icon" style="width:100%;padding:0.5rem;font-size:0.78rem;display:flex;align-items:center;justify-content:center;gap:0.4rem;border-radius:8px;">
            <i class="bi bi-telephone-fill" style="color:var(--accent);"></i>
            <span>Telephone</span>
            <span id="phoneDotStatus" style="width:7px;height:7px;border-radius:50%;background:var(--text-secondary);"></span>
        </button>
    </div>
    @endif
    <div style="padding: 0.5rem 0.75rem; border-top: 1px solid var(--border); margin-top:auto;">
        <div class="d-flex align-items-center gap-2" style="padding:0.35rem 0.75rem;">
            <div style="width:28px;height:28px;border-radius:8px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-fill" style="color:var(--accent); font-size:0.8rem;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:0.75rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->name ?? '' }}</div>
                <div style="font-size:0.6rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->email ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="btn-icon" title="Deconnexion" style="width:24px;height:24px;font-size:0.65rem;">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
