@extends('layouts.app')

@section('title', 'Supervision en direct')
@section('page-title', 'Supervision en direct')

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">
                <span class="live-dot"></span> Supervision en direct
            </h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">
                Appels actifs, postes en ligne — rafraichi toutes les 3s
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="lastUpdate" style="font-size:0.75rem; color:var(--text-secondary);"></span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                <label class="form-check-label" for="autoRefresh" style="font-size:0.8rem;">Auto</label>
            </div>
            <button class="btn-outline-custom" onclick="refresh()" style="padding:4px 12px; font-size:0.78rem;">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div id="statCalls" style="font-size:1.8rem; font-weight:800;">—</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Appels actifs</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div id="statInbound" style="font-size:1.8rem; font-weight:800; color:#00e5a0;">—</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Entrants</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div id="statOutbound" style="font-size:1.8rem; font-weight:800; color:#58a6ff;">—</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Sortants</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card text-center" style="padding:1rem;">
                <div id="statOnline" style="font-size:1.8rem; font-weight:800; color:var(--accent);">—</div>
                <div style="font-size:0.75rem; color:var(--text-secondary);">Postes en ligne</div>
            </div>
        </div>
    </div>

    {{-- Active calls --}}
    <div class="data-table mb-4" id="callsSection">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:0.85rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="bi bi-telephone-forward" style="color:var(--accent);"></i>
            Appels en cours
            <span id="callsBadge" class="nav-badge" style="font-size:0.7rem;">0</span>
        </div>
        <div id="callsBody" style="min-height:60px;">
            <div class="text-center py-3" style="color:var(--text-secondary); font-size:0.85rem;">
                <i class="bi bi-arrow-clockwise spin"></i> Chargement...
            </div>
        </div>
    </div>

    {{-- Endpoints status --}}
    <div class="data-table" id="endpointsSection">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:0.85rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="bi bi-telephone-fill" style="color:var(--accent);"></i>
            Postes SIP
        </div>
        <div id="endpointsBody">
            <div class="text-center py-3" style="color:var(--text-secondary); font-size:0.85rem;">
                <i class="bi bi-arrow-clockwise spin"></i> Chargement...
            </div>
        </div>
    </div>

    {{-- Trunk registrations --}}
    <div class="data-table mt-4" id="trunksSection">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:0.85rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="bi bi-diagram-3-fill" style="color:#58a6ff;"></i>
            Trunks SIP
        </div>
        <div id="trunksBody">
            <div class="text-center py-3" style="color:var(--text-secondary); font-size:0.85rem;">
                <i class="bi bi-arrow-clockwise spin"></i> Chargement...
            </div>
        </div>
    </div>

    <style>
        .live-dot {
            display: inline-block; width: 8px; height: 8px;
            border-radius: 50%; background: #00e5a0;
            animation: livePulse 1.5s ease-in-out infinite;
            margin-right: 4px;
        }
        @keyframes livePulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(0,229,160,0.5); }
            50% { opacity: 0.6; box-shadow: 0 0 0 6px rgba(0,229,160,0); }
        }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .spin { display: inline-block; animation: spin 1s linear infinite; }

        .call-card {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .call-card:last-child { border-bottom: none; }
        .call-card:hover { background: rgba(var(--accent-rgb), 0.03); }

        .call-dir {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; flex-shrink: 0;
        }
        .call-dir.inbound { background: rgba(0,229,160,0.12); color: #00e5a0; }
        .call-dir.outbound { background: rgba(88,166,255,0.12); color: #58a6ff; }
        .call-dir.internal { background: rgba(188,140,255,0.12); color: #bc8cff; }

        .call-info { flex: 1; min-width: 0; }
        .call-ext { font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }
        .call-detail { font-size: 0.75rem; color: var(--text-secondary); }
        .call-caller-badge {
            font-size: 0.6rem; font-weight: 600; padding: 1px 6px; border-radius: 4px;
            background: rgba(0,229,160,0.12); color: #00e5a0; text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .call-state {
            padding: 3px 10px; border-radius: 6px;
            font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
        }
        .call-state.up { background: rgba(0,229,160,0.12); color: #00e5a0; }
        .call-state.ring, .call-state.ringing { background: rgba(210,153,34,0.12); color: #d29922; }
        .call-state.dialing { background: rgba(88,166,255,0.12); color: #58a6ff; }
        .call-state.down { background: rgba(248,81,73,0.12); color: #f85149; }

        .call-duration {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem; font-weight: 600;
            color: var(--text-primary);
            min-width: 65px; text-align: right;
        }
        .call-app {
            font-size: 0.7rem; padding: 2px 8px; border-radius: 5px;
            background: var(--surface-3); border: 1px solid var(--border);
            color: var(--text-secondary); white-space: nowrap;
        }

        .ep-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.5rem;
            padding: 0.75rem;
        }
        .ep-card {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            transition: all .15s;
        }
        .ep-card.online { border-color: rgba(0,229,160,0.3); }
        .ep-card .ep-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .ep-card.online .ep-dot { background: #00e5a0; box-shadow: 0 0 6px rgba(0,229,160,0.4); }
        .ep-card.offline .ep-dot { background: var(--text-secondary); opacity: 0.3; }
        .ep-ext { font-weight: 700; font-size: 0.85rem; }
        .ep-name { font-size: 0.72rem; color: var(--text-secondary); }
        .ep-ip { font-size: 0.65rem; color: var(--text-secondary); font-family: 'JetBrains Mono', monospace; }

        .trunk-row {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        .trunk-row:last-child { border-bottom: none; }
    </style>

    <script>
        let timer = null;
        const POLL_URL = '{{ route("live.poll") }}';

        function refresh() {
            fetch(POLL_URL, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    renderCalls(data.channels || []);
                    renderEndpoints(data.endpoints || []);
                    renderTrunks(data.registrations || []);
                    document.getElementById('lastUpdate').textContent = data.timestamp;
                })
                .catch(() => {
                    document.getElementById('lastUpdate').textContent = 'Erreur';
                });
        }

        function renderCalls(channels) {
            const inbound = channels.filter(c => c.direction === 'inbound');
            const outbound = channels.filter(c => c.direction === 'outbound');
            const total = channels.length;

            document.getElementById('statCalls').textContent = total;
            document.getElementById('statInbound').textContent = inbound.length;
            document.getElementById('statOutbound').textContent = outbound.length;
            document.getElementById('callsBadge').textContent = total;

            const body = document.getElementById('callsBody');
            if (!channels.length) {
                body.innerHTML = '<div class="text-center py-3" style="color:var(--text-secondary); font-size:0.85rem;"><i class="bi bi-telephone-x me-1"></i> Aucun appel en cours</div>';
                return;
            }

            // Deduplicate bridged calls — show one card per bridge
            const seen = new Set();
            const unique = [];
            channels.forEach(c => {
                if (c.bridge_id && seen.has(c.bridge_id)) return;
                if (c.bridge_id) seen.add(c.bridge_id);
                unique.push(c);
            });

            let h = '';
            unique.forEach(c => {
                const dirIcon = c.direction === 'inbound' ? 'bi-telephone-inbound-fill'
                    : c.direction === 'outbound' ? 'bi-telephone-outbound-fill'
                    : 'bi-telephone-fill';
                const dirLabel = c.direction === 'inbound' ? 'Entrant'
                    : c.direction === 'outbound' ? 'Sortant' : 'Interne';
                const stateClass = (c.state || '').toLowerCase().replace(/\s/g, '');

                // Display number: caller ID for inbound, destination for outbound
                const mainNumber = c.display_number || c.caller_id || c.extension || c.channel;
                const label = c.display_label || dirLabel;
                const connectedTo = c.connected_to ? ` → ${c.connected_to}` : (c.exten ? ` → ${c.exten}` : '');

                h += `<div class="call-card">
                    <div class="call-dir ${c.direction}" title="${dirLabel}">
                        <i class="bi ${dirIcon}"></i>
                    </div>
                    <div class="call-info">
                        <div class="call-ext">
                            ${mainNumber}
                            ${c.caller_id && c.direction === 'inbound' ? '<span class="call-caller-badge">Appelant</span>' : ''}
                        </div>
                        <div class="call-detail">
                            ${label} — ${c.context}${connectedTo}
                        </div>
                    </div>
                    ${c.application ? '<span class="call-app">' + c.application + '</span>' : ''}
                    <span class="call-state ${stateClass}">${c.state || '—'}</span>
                    <div class="call-duration">${c.duration || '—'}</div>
                </div>`;
            });
            body.innerHTML = h;
        }

        function renderEndpoints(endpoints) {
            const online = endpoints.filter(e => e.status === 'online');
            document.getElementById('statOnline').textContent = online.length;

            const body = document.getElementById('endpointsBody');
            if (!endpoints.length) {
                body.innerHTML = '<div class="text-center py-3" style="color:var(--text-secondary);">Aucun poste configure</div>';
                return;
            }

            // Sort: online first, then by extension
            endpoints.sort((a, b) => {
                if (a.status === b.status) return a.extension.localeCompare(b.extension);
                return a.status === 'online' ? -1 : 1;
            });

            let h = '<div class="ep-grid">';
            endpoints.forEach(ep => {
                h += `<div class="ep-card ${ep.status}">
                    <div class="ep-dot"></div>
                    <div>
                        <div class="ep-ext">${ep.extension}</div>
                        <div class="ep-name">${ep.name}</div>
                        ${ep.ip ? '<div class="ep-ip">' + ep.ip + '</div>' : ''}
                    </div>
                </div>`;
            });
            h += '</div>';
            body.innerHTML = h;
        }

        function renderTrunks(regs) {
            const body = document.getElementById('trunksBody');
            if (!regs.length) {
                body.innerHTML = '<div class="text-center py-3" style="color:var(--text-secondary);">Aucun trunk enregistre</div>';
                return;
            }

            let h = '';
            regs.forEach(r => {
                const isUp = r.status === 'registered' || r.status === 'rejected' === false;
                const color = r.status === 'registered' ? '#00e5a0' : '#f85149';
                h += `<div class="trunk-row">
                    <span style="width:8px;height:8px;border-radius:50%;background:${color};display:inline-block;"></span>
                    <span style="font-weight:600; font-size:0.85rem; min-width:150px;">${r.name}</span>
                    <code style="font-size:0.78rem; color:var(--text-secondary); flex:1;">${r.server}</code>
                    <span class="codec-tag" style="color:${color}; border-color:${color}40;">${r.status}</span>
                </div>`;
            });
            body.innerHTML = h;
        }

        // Auto-refresh
        function startPolling() {
            stopPolling();
            refresh();
            timer = setInterval(refresh, 3000);
        }
        function stopPolling() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        document.getElementById('autoRefresh').addEventListener('change', function() {
            this.checked ? startPolling() : stopPolling();
        });

        // Boot
        startPolling();
    </script>
@endsection
