<!DOCTYPE html>
<html lang="fr">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Voxa') — {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @include('layouts.partials.sidebar')

    <div class="main-content">
        @include('layouts.partials.topbar')

        <div class="page-content fade-up">
            @if(session('success'))
                <div class="alert-flash" role="alert">
                    <i class="bi bi-check-circle-fill me-2" style="color: var(--accent);"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert-flash danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="color: var(--danger);"></i>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
