@extends('layouts.app')

@section('title', 'Asterisk Logs')
@section('page-title', 'Asterisk Logs')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">Asterisk — Console</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">Logs en temps reel et commandes CLI</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-custom" onclick="refreshLogs()" id="refreshBtn">
                <i class="bi bi-arrow-clockwise me-1"></i> Rafraichir
            </button>
            <div class="form-check form-switch d-flex align-items-center gap-2 ms-2">
                <input class="form-check-input" type="checkbox" id="autoRefresh">
                <label class="form-check-label" style="font-size:0.82rem;">Auto (5s)</label>
            </div>
        </div>
    </div>

    {{-- CLI Commands --}}
    <div class="data-table mb-4" style="padding:1rem 1.25rem;">
        <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;">
            <i class="bi bi-terminal me-1" style="color:var(--accent);"></i> Commandes rapides
        </h6>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('core show uptime')">Uptime</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('pjsip show endpoints')">Endpoints</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('pjsip show registrations')">Registrations</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('pjsip show contacts')">Contacts</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('core show channels')">Channels</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('core show calls')">Calls</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('odbc show all')">ODBC</button>
            <button class="btn btn-outline-custom btn-sm" onclick="runCmd('module show like res_pjsip')">Modules PJSIP</button>
        </div>
        <div id="cmdOutput" class="mt-3" style="display:none;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <code style="color:var(--warning);font-size:0.8rem;" id="cmdLabel"></code>
            </div>
            <pre id="cmdResult" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1rem;color:#e6edf3;font-family:'JetBrains Mono',monospace;font-size:0.75rem;overflow-x:auto;white-space:pre;margin:0;max-height:300px;overflow-y:auto;"></pre>
        </div>
    </div>

    {{-- Logs --}}
    <div class="data-table" style="padding:1rem 1.25rem;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 style="font-weight:700;font-size:0.85rem;margin:0;">
                <i class="bi bi-journal-code me-1" style="color:var(--accent);"></i> Logs (derniers 200)
            </h6>
            <select class="form-select" style="width:auto;font-size:0.78rem;padding:0.25rem 0.5rem;" id="logFilter">
                <option value="">Tout</option>
                <option value="VERBOSE">Verbose</option>
                <option value="WARNING">Warning</option>
                <option value="ERROR">Error</option>
                <option value="NOTICE">Notice</option>
                <option value="SECURITY">Security</option>
                <option value="pjsip" selected>PJSIP</option>
            </select>
        </div>

        @if(!$fileExists)
            <div class="text-center py-4" style="color:var(--text-secondary);">
                <i class="bi bi-exclamation-triangle me-2"></i>Fichier de log Asterisk introuvable — Asterisk n'est peut-etre pas demarre.
            </div>
        @else
            <pre id="logArea" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1rem;color:#e6edf3;font-family:'JetBrains Mono',monospace;font-size:0.72rem;overflow-x:auto;white-space:pre;margin:0;max-height:500px;overflow-y:auto;">@foreach($lines as $line)
{{ $line }}
@endforeach</pre>
        @endif
    </div>

    <script>
        let autoTimer = null;
        const logArea = document.getElementById('logArea');
        const logFilter = document.getElementById('logFilter');
        let allLines = [];

        function refreshLogs() {
            fetch('{{ route("asterisk.logs.tail") }}?lines=200')
                .then(r => r.json())
                .then(data => {
                    if (data.lines) {
                        allLines = data.lines;
                        applyFilter();
                        logArea.scrollTop = logArea.scrollHeight;
                    }
                })
                .catch(() => {});
        }

        function applyFilter() {
            const filter = logFilter.value;
            let filtered = allLines;
            if (filter) {
                filtered = allLines.filter(l => l.toUpperCase().includes(filter.toUpperCase()));
            }
            if (logArea) {
                logArea.textContent = filtered.join('\n');
                logArea.scrollTop = logArea.scrollHeight;
            }
        }

        logFilter?.addEventListener('change', applyFilter);

        document.getElementById('autoRefresh')?.addEventListener('change', function() {
            if (this.checked) {
                refreshLogs();
                autoTimer = setInterval(refreshLogs, 5000);
            } else {
                clearInterval(autoTimer);
            }
        });

        function runCmd(cmd) {
            document.getElementById('cmdOutput').style.display = '';
            document.getElementById('cmdLabel').textContent = 'asterisk -rx "' + cmd + '"';
            document.getElementById('cmdResult').textContent = 'Chargement...';

            fetch('{{ route("asterisk.command") }}?cmd=' + encodeURIComponent(cmd))
                .then(r => r.json())
                .then(data => {
                    document.getElementById('cmdResult').textContent = data.output || data.error || '(vide)';
                })
                .catch(e => {
                    document.getElementById('cmdResult').textContent = 'Erreur: ' + e.message;
                });
        }

        // Init: scroll to bottom
        if (logArea) {
            allLines = logArea.textContent.split('\n');
            logArea.scrollTop = logArea.scrollHeight;
            applyFilter();
        }
    </script>
@endsection
