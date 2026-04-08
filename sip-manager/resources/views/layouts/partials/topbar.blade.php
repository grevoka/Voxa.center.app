<div class="top-bar">
    <div class="d-flex align-items-center gap-3">
        <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h4>@yield('page-title', 'Tableau de bord')</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
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
function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    var icon = document.getElementById('themeIcon');
    if (icon) icon.className = next === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
}
</script>
