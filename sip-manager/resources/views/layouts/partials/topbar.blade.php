<style>
.lang-dropdown.show { display:block !important; }
.lang-opt { display:flex;align-items:center;gap:6px;padding:8px 14px;text-decoration:none;color:var(--text-primary);font-size:0.8rem;transition:background .1s; }
.lang-opt:hover { background:var(--surface-3);color:var(--text-primary); }
.lang-opt.active { background:var(--accent-dim);color:var(--accent);font-weight:600; }
</style>
<div class="top-bar">
    <div class="d-flex align-items-center gap-3">
        <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h4>@yield('page-title', __('ui.dashboard'))</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(session('impersonate_admin_id'))
        <form action="{{ route('admin.impersonate.stop') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" style="background:linear-gradient(135deg,#29b6f6,#ab47bc);border:none;color:#fff;border-radius:6px;padding:0.3rem 0.75rem;font-size:0.75rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.3rem;">
                <i class="bi bi-arrow-return-left"></i> {{ __("ui.back") }} admin
            </button>
        </form>
        @endif
        <div class="dropdown" style="position:relative;">
            <button class="btn-icon" title="{{ __('ui.language') }}" onclick="this.nextElementSibling.classList.toggle('show')" style="font-size:1rem;">
                {{ app()->getLocale() === 'en' ? '🇬🇧' : '🇫🇷' }}
            </button>
            <div class="lang-dropdown" style="display:none;position:absolute;right:0;top:100%;margin-top:4px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.3);z-index:100;min-width:130px;overflow:hidden;">
                <a href="{{ route('lang.switch', 'fr') }}" class="lang-opt {{ app()->getLocale() === 'fr' ? 'active' : '' }}">
                    🇫🇷 Francais
                </a>
                <a href="{{ route('lang.switch', 'en') }}" class="lang-opt {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    🇬🇧 English
                </a>
            </div>
        </div>
        <button class="btn-icon" title="Changer le theme" onclick="toggleTheme()" id="themeToggle">
            <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>
        <a href="{{ route('help.index') }}" class="btn-icon" title="Documentation">
            <i class="bi bi-question-circle"></i>
        </a>
        <button class="btn-icon" title="Notifications">
            <i class="bi bi-bell"></i>
        </button>
    </div>
</div>
<script>
(function() {
    var saved = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    document.addEventListener('DOMContentLoaded', function() {
        var icon = document.getElementById('themeIcon');
        if (icon) icon.className = saved === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    });
})();
// Close lang dropdown on click outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('.lang-dropdown').forEach(function(d) {
        if (!d.parentElement.contains(e.target)) d.classList.remove('show');
    });
});

function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    var icon = document.getElementById('themeIcon');
    if (icon) icon.className = next === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
}
</script>
