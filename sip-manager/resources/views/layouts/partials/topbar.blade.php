<div class="top-bar">
    <div class="d-flex align-items-center gap-3">
        <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h4>@yield('page-title', 'Tableau de bord')</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn-icon" title="Notifications">
            <i class="bi bi-bell"></i>
        </button>
    </div>
</div>
