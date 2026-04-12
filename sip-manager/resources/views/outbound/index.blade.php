@extends('layouts.app')

@section('title', __('ui.outbound_routes'))
@section('page-title', __('ui.outbound_routes'))

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">{{ __("ui.outbound_routes") }}</h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">{{ __('ui.outbound_desc') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contexts.dialplan') }}" class="btn btn-outline-custom">
                <i class="bi bi-code-slash me-1"></i> Dialplan
            </a>
            <button class="btn btn-accent" onclick="openWizard()">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle route
            </button>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="ob-stat-card">
                <div class="ob-stat-value">{{ $routes->count() }}</div>
                <div class="ob-stat-label">Routes totales</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <div class="ob-stat-value" style="color:var(--accent);">{{ $routes->where('enabled', true)->count() }}</div>
                <div class="ob-stat-label">Actives</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <div class="ob-stat-value">{{ $routes->pluck('trunk_id')->unique()->count() }}</div>
                <div class="ob-stat-label">Trunks utilises</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <div class="ob-stat-value">{{ $routes->where('record_calls', true)->count() }}</div>
                <div class="ob-stat-label">Enregistrement</div>
            </div>
        </div>
    </div>

    {{-- Info --}}
    <div style="background:rgba(var(--accent-rgb), 0.08);border:1px solid rgba(var(--accent-rgb), 0.2);border-radius:10px;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.82rem;color:var(--text-secondary);">
        <i class="bi bi-info-circle me-1" style="color:var(--accent);"></i>
        Priorite croissante (1 = testee en premier). Le premier pattern qui matche est utilise.
    </div>

    {{-- Routes table --}}
    <div class="data-table">
        <table class="table" id="routesTable">
            <thead>
                <tr>
                    <th style="width:60px;">Prio</th>
                    <th>{{ __("ui.th_route") }}</th>
                    <th>{{ __("ui.pattern") }}</th>
                    <th>{{ __("ui.th_trunk") }}</th>
                    <th>Prefixe</th>
                    <th>CallerID</th>
                    <th style="width:50px;">Rec</th>
                    <th style="width:70px;">Statut</th>
                    <th style="width:120px;">{{ __("ui.actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($routes as $route)
                    <tr data-id="{{ $route->id }}" class="{{ !$route->enabled ? 'row-disabled' : '' }}">
                        <td><span class="ext-number">{{ $route->priority }}</span></td>
                        <td>
                            <div style="font-weight:600;">{{ $route->name }}</div>
                            @if($route->description)
                                <div style="font-size:0.72rem;color:var(--text-secondary);">{{ $route->description }}</div>
                            @endif
                        </td>
                        <td>
                            <code style="color:var(--accent);font-size:0.85rem;font-weight:600;">{{ $route->dial_pattern ?: '_X.' }}</code>
                            @php
                                $hints = ['_0XXXXXXXXX'=>'National FR','_+33X.'=>'International +33','_+X.'=>'International','_00X.'=>'International 00','_1X'=>'Urgences','_1XXX'=>'Services courts','_X.'=>'Tout numero'];
                            @endphp
                            @if($route->dial_pattern && !empty($hints[$route->dial_pattern]))
                                <div style="font-size:0.7rem;color:var(--text-secondary);">{{ $hints[$route->dial_pattern] }}</div>
                            @endif
                        </td>
                        <td>
                            @if($route->trunk)
                                <span style="font-size:0.82rem;"><i class="bi bi-diagram-3 me-1" style="color:var(--accent);"></i>{{ $route->trunk->name }}</span>
                            @else
                                <span style="color:var(--text-secondary);font-size:0.82rem;">—</span>
                            @endif
                        </td>
                        <td style="font-size:0.82rem;">
                            @if($route->prefix_strip || $route->prefix_add)
                                @if($route->prefix_strip)
                                    <span class="codec-tag" style="background:rgba(239,68,68,0.15);color:#ef4444;">-{{ $route->prefix_strip }}</span>
                                @endif
                                @if($route->prefix_add)
                                    <span class="codec-tag" style="background:rgba(41,182,246,0.15);color:#29b6f6;">+{{ $route->prefix_add }}</span>
                                @endif
                            @else
                                <span style="color:var(--text-secondary);">—</span>
                            @endif
                        </td>
                        <td style="font-size:0.82rem;">
                            @if($route->caller_id_override)
                                <code style="font-size:0.78rem;">{{ $route->caller_id_override }}</code>
                            @else
                                <span style="color:var(--text-secondary);">Defaut</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($route->record_calls)
                                <i class="bi bi-record-circle" style="color:#ef4444;" title="Enregistrement actif"></i>
                            @else
                                <span style="color:var(--text-secondary);">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-dot {{ $route->enabled ? 'online' : 'offline' }}"></span>
                            {{ $route->enabled ? 'Actif' : 'Inactif' }}
                        </td>
                        <td>
                            <form action="{{ route('outbound.toggle', $route) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-icon me-1" title="{{ $route->enabled ? 'Desactiver' : 'Activer' }}"><i class="bi bi-power"></i></button>
                            </form>
                            <a href="{{ route('outbound.edit', $route) }}" class="btn-icon me-1" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('outbound.destroy', $route) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer la route {{ $route->name }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon danger" title="Supprimer"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4" style="color:var(--text-secondary);">
                            <i class="bi bi-telephone-outbound me-2"></i>{{ __('ui.no_outbound') }}
                            <br><a href="#" onclick="openWizard();return false;" style="color:var(--accent);font-size:0.82rem;">{{ __('ui.create_first_route') }}</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ==================== WIZARD MODAL ==================== --}}
    <div id="obWizard" class="ob-wizard-overlay" style="display:none;">
        <div class="ob-wizard-box">
            <div class="ob-wizard-header">
                <h6 style="font-weight:700;margin:0;">
                    <i class="bi bi-telephone-outbound me-2" style="color:var(--accent);"></i>
                    Nouvelle route sortante
                </h6>
                <button class="btn-icon" onclick="closeWizard()" title="Fermer"><i class="bi bi-x-lg"></i></button>
            </div>

            {{-- Step 1: Choose type --}}
            <div class="ob-step" id="step1">
                <div class="ob-step-title">1. Type de route</div>
                <div class="ob-presets">
                    <div class="ob-preset" onclick="pickPreset('national')">
                        <div class="ob-preset-icon"><i class="bi bi-flag-fill"></i></div>
                        <div class="ob-preset-name">National FR</div>
                        <div class="ob-preset-desc">06, 01, 09... (10 chiffres)</div>
                        <code class="ob-preset-pattern">_0XXXXXXXXX</code>
                    </div>
                    <div class="ob-preset" onclick="pickPreset('international')">
                        <div class="ob-preset-icon"><i class="bi bi-globe2"></i></div>
                        <div class="ob-preset-name">International</div>
                        <div class="ob-preset-desc">Format E.164 (+XX...)</div>
                        <code class="ob-preset-pattern">_+X.</code>
                    </div>
                    <div class="ob-preset" onclick="pickPreset('international00')">
                        <div class="ob-preset-icon"><i class="bi bi-globe-americas"></i></div>
                        <div class="ob-preset-name">International 00</div>
                        <div class="ob-preset-desc">Prefixe 00 classique</div>
                        <code class="ob-preset-pattern">_00X.</code>
                    </div>
                    <div class="ob-preset" onclick="pickPreset('urgences')">
                        <div class="ob-preset-icon" style="color:#ef4444;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="ob-preset-name">Urgences</div>
                        <div class="ob-preset-desc">15, 17, 18, 112...</div>
                        <code class="ob-preset-pattern">_1X</code>
                    </div>
                    <div class="ob-preset" onclick="pickPreset('services')">
                        <div class="ob-preset-icon"><i class="bi bi-headset"></i></div>
                        <div class="ob-preset-name">Services courts</div>
                        <div class="ob-preset-desc">3XXX, ex: 3615</div>
                        <code class="ob-preset-pattern">_NXXX</code>
                    </div>
                    <div class="ob-preset" onclick="pickPreset('custom')">
                        <div class="ob-preset-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="ob-preset-name">Personnalise</div>
                        <div class="ob-preset-desc">Pattern libre</div>
                        <code class="ob-preset-pattern">_...</code>
                    </div>
                </div>
            </div>

            {{-- Step 2: Configure --}}
            <div class="ob-step" id="step2" style="display:none;">
                <div class="ob-step-title">2. Configuration</div>
                <form id="wizardForm" action="{{ route('outbound.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="direction" value="outbound">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="name" id="wName" class="form-control" required
                                   placeholder="ex: outbound-national">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priorite</label>
                            <input type="number" name="priority" id="wPriority" class="form-control" value="10" min="1" max="99">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Timeout (s)</label>
                            <input type="number" name="timeout" class="form-control" value="45" min="5" max="120">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pattern *</label>
                            <input type="text" name="dial_pattern" id="wPattern" class="form-control" required>
                            <small id="wPatternHint" style="color:var(--text-secondary);font-size:0.72rem;"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trunk SIP *</label>
                            <select name="trunk_id" id="wTrunk" class="form-select" required>
                                <option value="">— Choisir —</option>
                                @foreach($trunks as $trunk)
                                    <option value="{{ $trunk->id }}">{{ $trunk->name }} ({{ $trunk->host }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Retirer prefixe</label>
                            <input type="text" name="prefix_strip" id="wStrip" class="form-control" placeholder="ex: 0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ajouter prefixe</label>
                            <input type="text" name="prefix_add" id="wAdd" class="form-control" placeholder="ex: +33">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CallerID override</label>
                            <input type="text" name="caller_id_override" class="form-control" placeholder="Vide = defaut">
                        </div>

                        {{-- Preview --}}
                        <div class="col-12" id="wPreviewWrap" style="display:none;">
                            <div style="padding:0.6rem 0.85rem;background:var(--surface-1);border:1px solid var(--border);border-radius:8px;font-size:0.82rem;">
                                <strong style="color:var(--accent);">Apercu :</strong>
                                <span id="wPreviewText"></span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" id="wDesc" class="form-control" placeholder="Description courte">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="wEnabled" checked>
                                <label class="form-check-label" for="wEnabled" style="font-size:0.85rem;">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="record_calls" value="1" id="wRecord">
                                <label class="form-check-label" for="wRecord" style="font-size:0.85rem;">Enregistrer les appels</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-outline-custom" onclick="goStep(1)">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="submit" class="btn btn-accent ms-auto">
                            <i class="bi bi-check-lg me-1"></i> Creer la route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .ob-stat-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            text-align: center;
        }
        .ob-stat-value { font-size: 1.5rem; font-weight: 800; }
        .ob-stat-label { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.15rem; }
        .row-disabled { opacity: 0.5; }

        /* Wizard */
        .ob-wizard-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
        }
        .ob-wizard-box {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 14px; width: 680px; max-width: 95vw;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .ob-wizard-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
        }
        .ob-step { padding: 1.25rem; }
        .ob-step-title {
            font-size: 0.78rem; font-weight: 700; color: var(--accent);
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        /* Preset cards */
        .ob-presets {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
        }
        .ob-preset {
            background: var(--surface-1); border: 1px solid var(--border);
            border-radius: 10px; padding: 1rem; cursor: pointer;
            text-align: center; transition: all 0.15s;
        }
        .ob-preset:hover {
            border-color: var(--accent);
            background: rgba(var(--accent-rgb), 0.06);
            transform: translateY(-2px);
        }
        .ob-preset-icon { font-size: 1.4rem; color: var(--accent); margin-bottom: 0.4rem; }
        .ob-preset-name { font-weight: 700; font-size: 0.85rem; }
        .ob-preset-desc { font-size: 0.72rem; color: var(--text-secondary); margin: 0.2rem 0 0.4rem; }
        .ob-preset-pattern {
            font-size: 0.72rem; color: var(--accent);
            background: rgba(var(--accent-rgb), 0.1);
            padding: 0.15rem 0.5rem; border-radius: 4px;
        }

        @media (max-width: 576px) {
            .ob-presets { grid-template-columns: repeat(2, 1fr); }
        }
    </style>

    <script>
        const presets = {
            national:        { name: 'outbound-national',      pattern: '_0XXXXXXXXX', strip: '0', add: '+33', desc: 'Appels nationaux France', hint: 'Numeros a 10 chiffres (06, 01, 09...)', prio: 10 },
            international:   { name: 'outbound-international', pattern: '_+X.',        strip: '',  add: '',    desc: 'Appels internationaux E.164', hint: 'Format +XX... (E.164)', prio: 20 },
            international00: { name: 'outbound-intl-00',       pattern: '_00X.',       strip: '00', add: '+',  desc: 'Appels internationaux prefixe 00', hint: 'Prefixe 00 classique', prio: 20 },
            urgences:        { name: 'outbound-urgences',      pattern: '_1X',         strip: '',  add: '',    desc: 'Numeros d\'urgence (15, 17, 18)', hint: 'Numeros courts 1X', prio: 1 },
            services:        { name: 'outbound-services',      pattern: '_NXXX',       strip: '',  add: '',    desc: 'Services courts (3XXX)', hint: 'Numeros courts 2-9 + 3 chiffres', prio: 15 },
            custom:          { name: '',                        pattern: '',            strip: '',  add: '',    desc: '', hint: 'Saisissez votre pattern Asterisk', prio: 10 },
        };

        function openWizard() {
            document.getElementById('obWizard').style.display = 'flex';
            goStep(1);
        }
        function closeWizard() {
            document.getElementById('obWizard').style.display = 'none';
        }
        function goStep(n) {
            document.getElementById('step1').style.display = n === 1 ? '' : 'none';
            document.getElementById('step2').style.display = n === 2 ? '' : 'none';
        }

        function pickPreset(key) {
            const p = presets[key];
            document.getElementById('wName').value = p.name;
            document.getElementById('wPattern').value = p.pattern;
            document.getElementById('wStrip').value = p.strip;
            document.getElementById('wAdd').value = p.add;
            document.getElementById('wDesc').value = p.desc;
            document.getElementById('wPriority').value = p.prio;
            document.getElementById('wPatternHint').textContent = p.hint;

            // Auto-select first trunk if only one
            const trunkSel = document.getElementById('wTrunk');
            if (trunkSel.options.length === 2) trunkSel.selectedIndex = 1;

            goStep(2);
            updateWizardPreview();

            if (key === 'custom') {
                document.getElementById('wName').focus();
            }
        }

        // Live preview
        function updateWizardPreview() {
            const strip = document.getElementById('wStrip').value;
            const add = document.getElementById('wAdd').value;
            const pattern = document.getElementById('wPattern').value;
            const wrap = document.getElementById('wPreviewWrap');
            const text = document.getElementById('wPreviewText');

            if (!strip && !add) { wrap.style.display = 'none'; return; }

            const example = patternToExample(pattern);
            if (!example) { wrap.style.display = 'none'; return; }

            let result = example;
            if (strip && result.startsWith(strip)) result = result.substring(strip.length);
            if (add) result = add + result;

            wrap.style.display = '';
            text.innerHTML = '<code>' + example + '</code> &rarr; <code style="color:var(--accent);">' + result + '</code>';
        }

        function patternToExample(p) {
            if (!p) return null;
            let s = p.replace(/^_/, ''), out = '';
            for (let c of s) {
                if (c === 'X') out += '5';
                else if (c === 'Z') out += '3';
                else if (c === 'N') out += '7';
                else if (c === '.') out += '123';
                else if (c === '!') {}
                else out += c;
            }
            return out || null;
        }

        ['wStrip','wAdd','wPattern'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', updateWizardPreview);
        });

        // Close on overlay click
        document.getElementById('obWizard')?.addEventListener('click', function(e) {
            if (e.target === this) closeWizard();
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('obWizard').style.display !== 'none') {
                closeWizard();
            }
        });
    </script>
@endsection
