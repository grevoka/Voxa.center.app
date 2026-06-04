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
        <div id="softphoneHeader" style="background:var(--accent-gradient);padding:0.5rem 0.75rem;cursor:move;display:flex;align-items:center;justify-content:space-between;user-select:none;">
            <div style="flex:1;color:#fff;font-size:0.78rem;font-weight:600;"><i class="bi bi-telephone-fill me-1"></i>{{ __("ui.phone") }}</div>
            <button onclick="document.getElementById('softphonePopup').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.7);cursor:pointer;font-size:0.85rem;padding:0;"><i class="bi bi-dash-lg"></i></button>
        </div>
        @include('operator.partials.softphone')
    </div>

    {{-- Missed-calls floating button --}}
    <button id="missedFab" type="button" data-bs-toggle="modal" data-bs-target="#missedModal"
            style="display:none;position:fixed;bottom:1.5rem;left:1.5rem;z-index:8999;background:var(--danger);color:#fff;border:none;border-radius:24px;padding:0.55rem 1rem;font-size:0.78rem;font-weight:700;box-shadow:0 4px 16px rgba(248,81,73,0.4);cursor:pointer;">
        <i class="bi bi-telephone-x-fill me-1"></i><span>Appels manqu&eacute;s</span>
        <span id="missedBadge" class="badge bg-light text-danger ms-1" style="font-size:0.7rem;">0</span>
    </button>

    {{-- Missed-calls modal --}}
    <div class="modal fade" id="missedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--surface-2);color:var(--text-primary);border:1px solid var(--border);">
                <div class="modal-header" style="border-bottom:1px solid var(--border);">
                    <h6 class="modal-title" style="font-weight:700;"><i class="bi bi-telephone-x-fill me-1" style="color:var(--danger);"></i>Appels manqu&eacute;s</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:0;">
                    <div id="missedListEmpty" style="display:none;padding:1.5rem;text-align:center;color:var(--text-secondary);font-size:0.85rem;">
                        <i class="bi bi-check2-circle" style="font-size:1.6rem;color:var(--success);"></i><br>
                        Aucun appel manqu&eacute; non rappel&eacute;.
                    </div>
                    <ul id="missedList" class="list-group list-group-flush" style="max-height:60vh;overflow-y:auto;"></ul>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border);font-size:0.7rem;color:var(--text-secondary);">
                    Cliquez sur un appel pour rappeler. L'entr&eacute;e dispara&icirc;t d&egrave;s que le rappel d&eacute;croche.
                </div>
            </div>
        </div>
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
