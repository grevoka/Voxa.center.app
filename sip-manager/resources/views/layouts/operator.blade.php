<!DOCTYPE html>
<html lang="fr">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mon espace') — {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @if(session('impersonate_admin_id'))
    <div style="background:linear-gradient(135deg,#29b6f6,#ab47bc);color:#fff;text-align:center;padding:0.45rem;font-size:0.82rem;font-weight:600;position:fixed;top:0;left:0;right:0;z-index:9999;">
        <i class="bi bi-person-badge me-1"></i>
        Mode impersonation — {{ auth()->user()->name }} ({{ auth()->user()->sipLine?->extension }})
        <form action="{{ route('admin.impersonate.stop') }}" method="POST" class="d-inline ms-2">
            @csrf
            <button type="submit" style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:#fff;border-radius:4px;padding:0.15rem 0.6rem;font-size:0.75rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-arrow-return-left me-1"></i>Revenir admin
            </button>
        </form>
    </div>
    @endif

    @include('layouts.partials.sidebar-operator')

    <main class="main-content" style="{{ session('impersonate_admin_id') ? 'padding-top:3.5rem;' : '' }}">
        @include('layouts.partials.topbar')

        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center mb-3" style="border-radius:10px;font-size:0.85rem;">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center mb-3" style="border-radius:10px;font-size:0.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
