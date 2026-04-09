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
    <script src="https://cdn.jsdelivr.net/npm/jssip/dist/jssip.min.js"></script>
    @stack('styles')
</head>
<body>
    @include('layouts.partials.sidebar-operator')

    <main class="main-content">
        @include('layouts.partials.topbar')

        <div class="page-content">
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

    {{-- Softphone popup --}}
    @if(auth()->user()->sipLine)
    <div id="softphonePopup" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;z-index:9000;width:280px;background:var(--surface-2);border:1px solid var(--border);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.4);overflow:hidden;cursor:default;">
        <div id="softphoneHeader" style="background:var(--accent-gradient);padding:0.5rem 0.75rem;cursor:move;display:flex;align-items:center;justify-content:between;user-select:none;">
            <div style="flex:1;color:#fff;font-size:0.78rem;font-weight:600;"><i class="bi bi-telephone-fill me-1"></i>Telephone</div>
            <button onclick="document.getElementById('softphonePopup').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.7);cursor:pointer;font-size:0.85rem;padding:0;"><i class="bi bi-dash-lg"></i></button>
        </div>
        @include('operator.partials.softphone')
    </div>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    {{-- Draggable popup --}}
    <script>
    (function() {
        var el = document.getElementById('softphonePopup');
        var header = document.getElementById('softphoneHeader');
        if (!el || !header) return;
        var dx=0, dy=0, mx=0, my=0;
        header.addEventListener('mousedown', function(e) {
            e.preventDefault();
            mx = e.clientX; my = e.clientY;
            document.onmouseup = function() { document.onmouseup = null; document.onmousemove = null; };
            document.onmousemove = function(e) {
                dx = mx - e.clientX; dy = my - e.clientY;
                mx = e.clientX; my = e.clientY;
                el.style.top = (el.offsetTop - dy) + 'px';
                el.style.left = (el.offsetLeft - dx) + 'px';
                el.style.bottom = 'auto';
                el.style.right = 'auto';
            };
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
