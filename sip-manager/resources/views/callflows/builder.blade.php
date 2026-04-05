@extends('layouts.app')

@section('title', isset($callflow) ? 'Modifier scenario' : 'Nouveau scenario')
@section('page-title', isset($callflow) ? 'Modifier le scenario' : 'Creer un scenario d\'appel')

@push('styles')
<style>
    /* ── Layout ── */
    .builder-wrap {
        display: grid;
        grid-template-columns: 280px 1fr 300px;
        gap: 0;
        height: calc(100vh - 140px);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        background: var(--surface-2);
    }
    .panel {
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--border);
        overflow: hidden;
    }
    .panel:last-child { border-right: none; }
    .panel-head {
        padding: 0.85rem 1rem;
        background: var(--surface-3);
        border-bottom: 1px solid var(--border);
        font-weight: 700;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    .panel-body {
        flex: 1;
        overflow-y: auto;
        padding: 0.85rem;
    }

    /* ── Canvas (centre) ── */
    .canvas-wrap {
        flex: 1;
        overflow: hidden;
        position: relative;
        background:
            radial-gradient(circle, var(--border) 1px, transparent 1px);
        background-size: 24px 24px;
        cursor: grab;
    }
    .canvas-wrap.grabbing { cursor: grabbing; }
    .canvas-inner {
        position: absolute;
        top: 0; left: 0;
        transform-origin: 0 0;
    }
    /* ── Fullscreen modal ── */
    .canvas-fullscreen {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 9999;
        background: var(--surface-1, #0f1923);
        display: none;
        flex-direction: column;
    }
    .canvas-fullscreen.active { display: flex; }
    .canvas-fullscreen .fs-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1.25rem;
        background: var(--surface-3);
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .canvas-fullscreen .fs-header h3 { margin: 0; font-size: 0.95rem; }
    .canvas-fullscreen .fs-body {
        flex: 1;
        overflow: hidden;
        position: relative;
        background:
            radial-gradient(circle, var(--border) 1px, transparent 1px);
        background-size: 24px 24px;
        cursor: grab;
    }
    .canvas-fullscreen .fs-body.grabbing { cursor: grabbing; }
    .btn-fs {
        background: var(--surface-3);
        border: 1px solid var(--border);
        color: var(--text-primary);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.72rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .btn-fs:hover { border-color: var(--accent); color: var(--accent); }

    /* ── Template wizard ── */
    .tpl-overlay {
        position: fixed; inset: 0; z-index: 9998;
        background: rgba(0,0,0,.6); display: none;
        align-items: center; justify-content: center;
    }
    .tpl-overlay.active { display: flex; }
    .tpl-modal {
        background: var(--surface-1, #0f1923);
        border: 1px solid var(--border);
        border-radius: 12px;
        width: 680px; max-width: 95vw; max-height: 85vh;
        display: flex; flex-direction: column;
    }
    .tpl-modal-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: .75rem;
        font-weight: 700; font-size: .9rem;
    }
    .tpl-modal-body {
        flex: 1; overflow-y: auto; padding: 1rem 1.25rem;
    }
    .tpl-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: .75rem;
    }
    .tpl-card {
        border: 1px solid var(--border); border-radius: 8px;
        padding: .85rem 1rem; cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .tpl-card:hover, .tpl-card.selected { border-color: var(--accent); background: var(--surface-3); }
    .tpl-card.selected { box-shadow: 0 0 0 2px var(--accent); }
    .tpl-card .tpl-icon { font-size: 1.2rem; margin-bottom: .4rem; color: var(--accent); }
    .tpl-card h6 { margin: 0 0 .25rem; font-size: .82rem; font-weight: 700; }
    .tpl-card p { margin: 0; font-size: .72rem; color: var(--text-secondary); }
    .tpl-card .tpl-badge {
        display: inline-block; font-size: .6rem; padding: 1px 6px;
        border-radius: 4px; background: var(--accent); color: #000;
        margin-left: .4rem; vertical-align: middle;
    }
    .tpl-card .tpl-steps { font-size: .65rem; color: var(--text-secondary); margin-top: .4rem; }
    .tpl-footer {
        padding: .75rem 1.25rem;
        border-top: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }
    .wiz-step { display: none; }
    .wiz-step.active { display: block; }
    .wiz-step .cfg-section { margin-bottom: .85rem; }
    .wiz-step .cfg-section label {
        display: block; font-size: .72rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--text-secondary); margin-bottom: .3rem;
    }
    .wiz-step .form-control, .wiz-step .form-select {
        font-size: .82rem;
    }
    .wiz-step h5 {
        font-size: .85rem; font-weight: 700; margin: 0 0 .75rem;
        color: var(--text-primary);
    }
    .wiz-step .wiz-subtitle {
        font-size: .72rem; color: var(--text-secondary); margin-bottom: 1rem;
    }
    .wiz-ext-list { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
    .wiz-ext-tag {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: 2px 8px; border-radius: 4px; font-size: .75rem;
        background: var(--accent); color: #000; font-weight: 600;
    }
    .wiz-ext-tag .remove { cursor: pointer; opacity: .7; }
    .wiz-ext-tag .remove:hover { opacity: 1; }
    .wiz-steps-indicator {
        display: flex; gap: .5rem; align-items: center;
        font-size: .7rem; color: var(--text-secondary);
    }
    .wiz-steps-indicator .step-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--border);
    }
    .wiz-steps-indicator .step-dot.active { background: var(--accent); }
    .wiz-steps-indicator .step-dot.done { background: #22c55e; }

    .edge-line {
        position: absolute;
        width: 2px;
        background: #22c55e;
        opacity: 0.8;
        pointer-events: none;
        transform-origin: top center;
        border-radius: 1px;
    }

    /* ── Zoom bar ── */
    .zoom-bar {
        position: absolute;
        bottom: 12px;
        right: 12px;
        display: flex;
        gap: 4px;
        z-index: 10;
    }
    .zoom-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text-secondary);
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
    }
    .zoom-btn:hover { border-color: #22c55e; color: #22c55e; }

    /* ── Nodes ── */
    .node {
        position: absolute;
        width: 220px;
        background: var(--surface-2);
        border: 2px solid var(--border);
        border-radius: 12px;
        cursor: grab;
        user-select: none;
        transition: box-shadow .15s;
        z-index: 2;
    }
    .node:hover { box-shadow: 0 4px 20px rgba(0,0,0,.4); }
    .node.dragging { cursor: grabbing; z-index: 100; box-shadow: 0 8px 32px rgba(0,0,0,.5); }
    .node.selected {
        border-color: #22c55e;
        box-shadow: 0 0 0 3px var(--accent-dim), 0 4px 20px rgba(0,0,0,.3);
    }

    .node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid var(--border);
        border-radius: 10px 10px 0 0;
        font-weight: 700;
        font-size: 0.78rem;
    }
    .node-icon {
        width: 28px; height: 28px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .node-body {
        padding: 0.5rem 0.75rem;
        font-size: 0.72rem;
        color: var(--text-secondary);
        min-height: 28px;
    }
    .node-delete {
        position: absolute;
        top: -8px; right: -8px;
        width: 20px; height: 20px;
        border-radius: 50%;
        background: var(--danger);
        color: #fff;
        border: 2px solid var(--surface-2);
        font-size: 0.55rem;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 5;
    }
    .node:hover .node-delete { display: flex; }

    /* ── Ports (connection dots) ── */
    .port {
        position: absolute;
        width: 12px; height: 12px;
        border-radius: 50%;
        background: #22c55e;
        border: 2px solid var(--surface-2);
        cursor: crosshair;
        z-index: 6;
        transition: transform .1s;
    }
    .port:hover { transform: scale(1.4); }
    .port-out {
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
    }
    .port-out:hover { transform: translateX(-50%) scale(1.4); }
    .port-in {
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
    }
    .port-in:hover { transform: translateX(-50%) scale(1.4); }

    /* ── Start node (special) ── */
    .node-start {
        width: 180px;
        background: #22c55e;
        border-color: #22c55e;
        border-radius: 99px;
        text-align: center;
        cursor: default;
    }
    .node-start .node-header {
        border: none;
        justify-content: center;
        color: #000;
        font-weight: 800;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 0.55rem;
    }
    .node-start .port-out {
        background: #000;
        border-color: #22c55e;
    }

    /* ── Node colors ── */
    .nc-answer .node-header   { background: #58a6ff15; }
    .nc-answer .node-icon     { background: #58a6ff25; color: #58a6ff; }
    .nc-ring .node-header     { background: #00e5a015; }
    .nc-ring .node-icon       { background: #00e5a025; color: #00e5a0; }
    .nc-queue .node-header    { background: #bc8cff15; }
    .nc-queue .node-icon      { background: #bc8cff25; color: #bc8cff; }
    .nc-voicemail .node-header { background: #d2992215; }
    .nc-voicemail .node-icon  { background: #d2992225; color: #d29922; }
    .nc-playback .node-header { background: #58a6ff15; }
    .nc-playback .node-icon   { background: #58a6ff25; color: #58a6ff; }
    .nc-moh .node-header      { background: #f0883e15; }
    .nc-moh .node-icon        { background: #f0883e25; color: #f0883e; }
    .nc-hangup .node-header   { background: #f8514915; }
    .nc-hangup .node-icon     { background: #f8514925; color: #f85149; }
    .nc-announcement .node-header { background: #d2992215; }
    .nc-announcement .node-icon   { background: #d2992225; color: #d29922; }
    .nc-goto .node-header     { background: #bc8cff15; }
    .nc-goto .node-icon       { background: #bc8cff25; color: #bc8cff; }

    /* ── Palette ── */
    .pal-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0.65rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface-3);
        cursor: pointer;
        transition: all .15s;
        margin-bottom: 0.4rem;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--text-secondary);
    }
    .pal-item:hover { border-color: #22c55e; color: #22c55e; background: var(--accent-dim); }
    .pal-icon {
        width: 26px; height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    /* ── Config (right) ── */
    .cfg-section { margin-bottom: 1rem; }
    .cfg-section label {
        font-weight: 600;
        font-size: 0.7rem;
        color: var(--text-secondary);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
        display: block;
    }
    .cfg-empty {
        text-align: center;
        color: var(--text-secondary);
        opacity: .5;
        padding: 2rem 1rem;
        font-size: 0.82rem;
    }
    .member-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.5rem;
        border-radius: 6px;
        background: var(--surface-3);
        border: 1px solid var(--border);
        margin-bottom: 0.3rem;
        font-size: 0.78rem;
    }
    .member-item .ext-badge {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: #22c55e;
        font-size: 0.72rem;
    }

    @media (max-width: 1100px) {
        .builder-wrap { grid-template-columns: 1fr; height: auto; }
    }
</style>
@endpush

@section('content')
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ isset($callflow) ? 'Modifier' : 'Nouveau' }} scenario</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Cartographie 2D — glissez les blocs pour construire votre flux</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('callflows.index') }}" class="btn-outline-custom">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
            <button type="button" class="btn-outline-custom" id="btnSaveTpl" title="Sauvegarder comme template">
                <i class="bi bi-bookmark-plus me-1"></i> Template
            </button>
            <button type="button" class="btn btn-accent" id="btnSave">
                <i class="bi bi-check-lg me-1"></i> {{ isset($callflow) ? 'Enregistrer' : 'Creer' }}
            </button>
        </div>
    </div>

    <form id="flowForm" method="POST"
          action="{{ isset($callflow) ? route('callflows.update', $callflow) : route('callflows.store') }}">
        @csrf
        @if(isset($callflow)) @method('PUT') @endif
        <input type="hidden" name="steps" id="stepsInput">
        <input type="hidden" name="name" id="hidName">
        <input type="hidden" name="description" id="hidDesc">
        <input type="hidden" name="trunk_id" id="hidTrunk">
        <input type="hidden" name="inbound_context" id="hidCtx">
        <input type="hidden" name="priority" id="hidPrio">
        <input type="hidden" name="enabled" id="hidEnabled">
    </form>

    <div class="builder-wrap">
        {{-- LEFT: config + palette --}}
        <div class="panel">
            <div class="panel-head"><i class="bi bi-sliders"></i> Configuration</div>
            <div class="panel-body">
                <div class="cfg-section">
                    <label>Nom</label>
                    <input type="text" class="form-control form-control-sm" id="cfgName"
                           value="{{ old('name', $callflow->name ?? '') }}" required placeholder="accueil-principal">
                </div>
                <div class="cfg-section">
                    <label>Description</label>
                    <input type="text" class="form-control form-control-sm" id="cfgDesc"
                           value="{{ old('description', $callflow->description ?? '') }}" placeholder="Optionnel">
                </div>
                <div class="cfg-section">
                    <label>Trunk entrant</label>
                    <select class="form-select form-select-sm" id="cfgTrunk" required>
                        <option value="">— Choisir —</option>
                        @foreach($trunks as $trunk)
                            <option value="{{ $trunk->id }}"
                                data-context="{{ $trunk->getEffectiveInboundContext() }}"
                                {{ old('trunk_id', $callflow->trunk_id ?? '') == $trunk->id ? 'selected' : '' }}>
                                {{ $trunk->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cfg-section">
                    <label>Contexte</label>
                    <input type="text" class="form-control form-control-sm" id="cfgCtx"
                           value="{{ old('inbound_context', $callflow->inbound_context ?? 'from-trunk') }}" required>
                </div>
                <div class="cfg-section">
                    <label>Priorite</label>
                    <input type="number" class="form-control form-control-sm" id="cfgPrio"
                           value="{{ old('priority', $callflow->priority ?? 1) }}" min="1" max="100">
                </div>
                <div class="cfg-section">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="cfgEnabled"
                            {{ old('enabled', $callflow->enabled ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="cfgEnabled"
                               style="text-transform:none; font-size:0.8rem; color:var(--text-primary);">Actif</label>
                    </div>
                </div>

                <hr style="border-color:var(--border); margin:0.75rem 0;">
                <div style="font-weight:700; font-size:0.68rem; letter-spacing:1px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:0.6rem;">
                    <i class="bi bi-plus-circle me-1"></i> Ajouter un bloc
                </div>

                <div class="pal-item" onclick="addNode('answer')">
                    <div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-inbound"></i></div> Repondre
                </div>
                <div class="pal-item" onclick="addNode('ring')">
                    <div class="pal-icon" style="background:#00e5a025;color:#00e5a0;"><i class="bi bi-bell"></i></div> Sonnerie
                </div>
                <div class="pal-item" onclick="addNode('queue')">
                    <div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-people"></i></div> File d'attente
                </div>
                <div class="pal-item" onclick="addNode('voicemail')">
                    <div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-voicemail"></i></div> Messagerie
                </div>
                <div class="pal-item" onclick="addNode('playback')">
                    <div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-volume-up"></i></div> Lecture audio
                </div>
                <div class="pal-item" onclick="addNode('announcement')">
                    <div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-megaphone"></i></div> Annonce
                </div>
                <div class="pal-item" onclick="addNode('moh')">
                    <div class="pal-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-music-note-beamed"></i></div> Musique
                </div>
                <div class="pal-item" onclick="addNode('goto')">
                    <div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-arrow-right-circle"></i></div> Goto
                </div>
                <div class="pal-item" onclick="addNode('hangup')">
                    <div class="pal-icon" style="background:#f8514925;color:#f85149;"><i class="bi bi-telephone-x"></i></div> Raccrocher
                </div>
            </div>
        </div>

        {{-- CENTER: 2D Canvas --}}
        <div class="panel" style="border-right:1px solid var(--border);">
            <div class="panel-head">
                <i class="bi bi-bounding-box"></i> Cartographie
                <span style="margin-left:auto; font-size:0.68rem; color:var(--text-secondary);" id="nodeCount">0 blocs</span>
                <button class="btn-fs" onclick="openFullscreen()" title="Plein ecran"><i class="bi bi-arrows-fullscreen"></i></button>
            </div>
            <div class="canvas-wrap" id="canvasWrap">
                <div class="canvas-inner" id="canvasInner"></div>
                {{-- edges are drawn as divs inside canvasInner --}}
                <div class="zoom-bar">
                    <button class="zoom-btn" onclick="zoomIn()" title="Zoom +"><i class="bi bi-plus"></i></button>
                    <button class="zoom-btn" onclick="zoomOut()" title="Zoom -"><i class="bi bi-dash"></i></button>
                    <button class="zoom-btn" onclick="zoomReset()" title="Reset"><i class="bi bi-arrows-fullscreen"></i></button>
                </div>
            </div>
        </div>

        {{-- RIGHT: properties --}}
        <div class="panel">
            <div class="panel-head"><i class="bi bi-gear"></i> Proprietes</div>
            <div class="panel-body" id="propPanel">
                <div class="cfg-empty">
                    <i class="bi bi-hand-index" style="font-size:1.5rem; display:block; margin-bottom:.5rem;"></i>
                    Cliquez sur un bloc
                </div>
            </div>
            <div style="border-top:1px solid var(--border);">
                <div class="panel-head" style="cursor:pointer;" onclick="toggleDialplan()">
                    <i class="bi bi-code-slash"></i> Dialplan
                    <i class="bi bi-chevron-down ms-auto" id="dpChev" style="font-size:.65rem;"></i>
                </div>
                <div id="dpWrap" style="display:none;">
                    <pre id="dpCode" style="padding:.75rem; margin:0; font-family:'JetBrains Mono',monospace; font-size:.65rem; color:#22c55e; overflow:auto; max-height:220px; background:var(--surface);"></pre>
                </div>
            </div>
        </div>
    </div>

    {{-- Fullscreen modal --}}
    <div class="canvas-fullscreen" id="fsModal">
        <div class="fs-header">
            <i class="bi bi-bounding-box"></i>
            <h3>Cartographie</h3>
            <span style="font-size:0.7rem; color:var(--text-secondary);" id="fsNodeCount"></span>
            <div style="margin-left:auto; display:flex; gap:0.5rem;">
                <button class="zoom-btn" onclick="zoomIn()" title="Zoom +"><i class="bi bi-plus"></i></button>
                <button class="zoom-btn" onclick="zoomOut()" title="Zoom -"><i class="bi bi-dash"></i></button>
                <button class="zoom-btn" onclick="zoomReset()" title="Reset"><i class="bi bi-arrows-fullscreen"></i></button>
                <button class="btn-fs" onclick="closeFullscreen()"><i class="bi bi-x-lg"></i> Fermer</button>
            </div>
        </div>
        <div class="fs-body" id="fsBody"></div>
    </div>

    {{-- Template wizard overlay (only on create) --}}
    @unless(isset($callflow))
    <div class="tpl-overlay" id="tplOverlay">
        <div class="tpl-modal">
            <div class="tpl-modal-head">
                <i class="bi bi-magic"></i>
                <span id="wizTitle">Nouveau scenario</span>
                <div class="wiz-steps-indicator" style="margin-left:auto;">
                    <div class="step-dot active" id="wizDot1"></div>
                    <div class="step-dot" id="wizDot2"></div>
                </div>
                <button class="btn-fs" onclick="closeTplModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="tpl-modal-body">
                {{-- Step 1: choose template --}}
                <div class="wiz-step active" id="wizStep1">
                    <h5>Choisir un modele</h5>
                    <div class="wiz-subtitle">Selectionnez un modele de depart ou commencez de zero</div>
                    <div class="tpl-grid">
                        <div class="tpl-card" data-tpl-id="" onclick="wizSelectTpl(this, null)">
                            <div class="tpl-icon"><i class="bi bi-file-earmark-plus"></i></div>
                            <h6>Scenario vide</h6>
                            <p>Commencer de zero</p>
                        </div>
                        @foreach($templates as $tpl)
                        <div class="tpl-card" data-tpl-id="{{ $tpl->id }}" onclick="wizSelectTpl(this, {{ $tpl->id }})">
                            <div class="tpl-icon"><i class="bi {{ $tpl->icon }}"></i></div>
                            <h6>{{ $tpl->name }}
                                @if($tpl->is_system)<span class="tpl-badge">Systeme</span>@endif
                            </h6>
                            <p>{{ $tpl->description ?: 'Aucune description' }}</p>
                            <div class="tpl-steps">{{ count($tpl->steps) }} etape{{ count($tpl->steps) > 1 ? 's' : '' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Step 2: configure scenario --}}
                <div class="wiz-step" id="wizStep2">
                    <h5 id="wizStep2Title">Configuration du scenario</h5>
                    <div class="wiz-subtitle">Renseignez les informations de votre scenario</div>

                    <div class="cfg-section">
                        <label>Nom du scenario *</label>
                        <input type="text" class="form-control form-control-sm" id="wizName" required placeholder="ex: accueil-principal">
                    </div>
                    <div class="cfg-section">
                        <label>Description</label>
                        <input type="text" class="form-control form-control-sm" id="wizDesc" placeholder="Optionnel">
                    </div>
                    <div class="cfg-section">
                        <label>Trunk entrant *</label>
                        <select class="form-select form-select-sm" id="wizTrunk" required>
                            <option value="">— Choisir —</option>
                            @foreach($trunks as $trunk)
                            <option value="{{ $trunk->id }}" data-context="{{ $trunk->getEffectiveInboundContext() }}">{{ $trunk->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dynamic fields depending on template --}}
                    <div id="wizDynFields"></div>
                </div>
            </div>
            <div class="tpl-footer">
                <button class="btn-outline-custom" id="wizBtnBack" style="display:none;" onclick="wizBack()">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </button>
                <span style="flex:1;"></span>
                <button class="btn btn-accent" id="wizBtnNext" onclick="wizNext()" disabled>
                    Suivant <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
    @endunless

    {{-- Save as template form --}}
    <form id="saveTplForm" method="POST" action="{{ route('callflows.save-template') }}" style="display:none;">
        @csrf
        <input type="hidden" name="steps" id="tplStepsInput">
        <input type="hidden" name="name" id="tplNameInput">
        <input type="hidden" name="description" id="tplDescInput">
    </form>
@endsection

@push('scripts')
<script>
// ════════════════════════════════════════
// DATA
// ════════════════════════════════════════
const TYPES = {
    answer:       { label:'Repondre',         icon:'bi-telephone-inbound',  color:'answer' },
    ring:         { label:'Sonnerie',          icon:'bi-bell',              color:'ring' },
    queue:        { label:'File d\'attente',   icon:'bi-people',            color:'queue' },
    voicemail:    { label:'Messagerie',        icon:'bi-voicemail',         color:'voicemail' },
    playback:     { label:'Lecture audio',     icon:'bi-volume-up',         color:'playback' },
    announcement: { label:'Annonce',           icon:'bi-megaphone',         color:'announcement' },
    moh:          { label:'Musique',           icon:'bi-music-note-beamed', color:'moh' },
    goto:         { label:'Goto',              icon:'bi-arrow-right-circle',color:'goto' },
    hangup:       { label:'Raccrocher',        icon:'bi-telephone-x',       color:'hangup' },
};
const DEFAULTS = {
    answer:       { wait:1 },
    ring:         { extensions:[], timeout:25 },
    queue:        { queue_name:'', timeout:60 },
    voicemail:    { mailbox:'1000', vm_type:'u' },
    playback:     { sound:'hello-world' },
    announcement: { sound:'custom/welcome' },
    moh:          { moh_class:'default', duration:10 },
    goto:         { target_context:'default' },
    hangup:       {},
};
const QUEUES = @json($queues);
const LINES  = @json($lines);

// ── State ──
let nodes = [];        // {id, type, x, y, data:{}, next:null}
let selectedId = null;
let nextId = 1;
const startId = '__start__';

// Canvas transform
let camX = 0, camY = 0, zoom = 1;
const canvasWrap  = document.getElementById('canvasWrap');
const canvasInner = document.getElementById('canvasInner');
// Edges are drawn as HTML divs inside canvasInner

// ════════════════════════════════════════
// TEMPLATES
// ════════════════════════════════════════
const TEMPLATES = @json($templates ?? []);

function loadSteps(steps) {
    nodes = [];
    nextId = 1;
    selectedId = null;
    const startX = 400, startY = 60, gapY = 140;
    steps.forEach((s, i) => {
        const n = {
            id: nextId++,
            type: s.type,
            x: startX,
            y: startY + 120 + i * gapY,
            data: Object.assign({}, s),
            next: null
        };
        delete n.data.type;
        nodes.push(n);
    });
    for (let i = 0; i < nodes.length - 1; i++) nodes[i].next = nodes[i+1].id;
    render();
}

@unless(isset($callflow))
let wizSelectedTplId = null;
let wizCurrentStep = 1;

function openTplModal() { document.getElementById('tplOverlay').classList.add('active'); }
function closeTplModal() { document.getElementById('tplOverlay').classList.remove('active'); }

function wizSelectTpl(card, id) {
    document.querySelectorAll('#wizStep1 .tpl-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    wizSelectedTplId = id;
    document.getElementById('wizBtnNext').disabled = false;
}

function wizShowStep(n) {
    wizCurrentStep = n;
    document.getElementById('wizStep1').classList.toggle('active', n === 1);
    document.getElementById('wizStep2').classList.toggle('active', n === 2);
    document.getElementById('wizDot1').className = 'step-dot ' + (n >= 1 ? (n > 1 ? 'done' : 'active') : '');
    document.getElementById('wizDot2').className = 'step-dot ' + (n === 2 ? 'active' : '');
    document.getElementById('wizBtnBack').style.display = n > 1 ? '' : 'none';
    document.getElementById('wizBtnNext').textContent = n === 2 ? 'Creer le scenario' : 'Suivant';
    const icon = n === 2 ? ' bi-check-lg' : ' bi-arrow-right';
    document.getElementById('wizBtnNext').innerHTML = (n === 2 ? '<i class="bi bi-check-lg me-1"></i> Creer le scenario' : 'Suivant <i class="bi bi-arrow-right ms-1"></i>');
    document.getElementById('wizTitle').textContent = n === 1 ? 'Nouveau scenario' : 'Configuration';
}

function wizBack() { wizShowStep(1); }

function wizNext() {
    if (wizCurrentStep === 1) {
        wizBuildDynFields();
        wizShowStep(2);
        document.getElementById('wizName').focus();
    } else {
        wizApply();
    }
}

function wizBuildDynFields() {
    const dyn = document.getElementById('wizDynFields');
    dyn.innerHTML = '';
    if (wizSelectedTplId === null) return; // scenario vide

    const tpl = TEMPLATES.find(t => t.id === wizSelectedTplId);
    if (!tpl) return;

    const steps = tpl.steps || [];
    const hasRing = steps.some(s => s.type === 'ring');
    const hasQueue = steps.some(s => s.type === 'queue');
    const hasVoicemail = steps.some(s => s.type === 'voicemail');

    let html = '<hr style="border-color:var(--border); margin:.75rem 0;">';
    html += '<div style="font-weight:700; font-size:.72rem; letter-spacing:.5px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:.6rem;">Parametres du template</div>';

    if (hasRing) {
        html += `<div class="cfg-section">
            <label>Postes a faire sonner</label>
            <div style="display:flex; gap:.4rem;">
                <select class="form-select form-select-sm" id="wizExtSelect" style="flex:1;">
                    <option value="">— Ajouter un poste —</option>
                    ${LINES.map(l => `<option value="${l.extension}">${l.extension} — ${l.display_name || l.extension}</option>`).join('')}
                </select>
                <button type="button" class="btn-outline-custom" style="padding:4px 10px;" onclick="wizAddExt()"><i class="bi bi-plus"></i></button>
            </div>
            <div class="wiz-ext-list" id="wizExtList"></div>
        </div>`;
    }

    if (hasQueue) {
        html += `<div class="cfg-section">
            <label>File d'attente</label>
            <select class="form-select form-select-sm" id="wizQueue">
                <option value="">— Choisir —</option>
                ${QUEUES.map(q => `<option value="${q.name}">${q.name}</option>`).join('')}
            </select>
        </div>`;
    }

    if (hasVoicemail) {
        html += `<div class="cfg-section">
            <label>Boite vocale</label>
            <select class="form-select form-select-sm" id="wizMailbox">
                <option value="">— Choisir —</option>
                ${LINES.map(l => `<option value="${l.extension}">${l.extension} — ${l.display_name || l.extension}</option>`).join('')}
            </select>
        </div>`;
    }

    dyn.innerHTML = html;
}

const wizExtensions = [];
function wizAddExt() {
    const sel = document.getElementById('wizExtSelect');
    if (!sel) return;
    const val = sel.value;
    if (!val || wizExtensions.includes(val)) return;
    wizExtensions.push(val);
    wizRenderExts();
    sel.value = '';
}
function wizRemoveExt(ext) {
    const idx = wizExtensions.indexOf(ext);
    if (idx > -1) wizExtensions.splice(idx, 1);
    wizRenderExts();
}
function wizRenderExts() {
    const el = document.getElementById('wizExtList');
    if (!el) return;
    el.innerHTML = wizExtensions.map(e =>
        `<span class="wiz-ext-tag">${e} <span class="remove" onclick="wizRemoveExt('${e}')">&times;</span></span>`
    ).join('');
}

function wizApply() {
    const name = document.getElementById('wizName').value.trim();
    const trunk = document.getElementById('wizTrunk').value;
    if (!name) { alert('Veuillez saisir un nom pour le scenario.'); document.getElementById('wizName').focus(); return; }
    if (!trunk) { alert('Veuillez choisir un trunk entrant.'); document.getElementById('wizTrunk').focus(); return; }

    // Fill the main config fields
    document.getElementById('cfgName').value = name;
    document.getElementById('cfgDesc').value = document.getElementById('wizDesc').value || '';
    document.getElementById('cfgTrunk').value = trunk;
    // Auto-fill context from trunk
    const trunkOpt = document.getElementById('wizTrunk').options[document.getElementById('wizTrunk').selectedIndex];
    if (trunkOpt && trunkOpt.dataset.context) {
        document.getElementById('cfgCtx').value = trunkOpt.dataset.context;
    }

    // Build final steps from template with wizard values
    let finalSteps = [];
    if (wizSelectedTplId !== null) {
        const tpl = TEMPLATES.find(t => t.id === wizSelectedTplId);
        if (tpl && tpl.steps) {
            finalSteps = JSON.parse(JSON.stringify(tpl.steps));
            const queueEl = document.getElementById('wizQueue');
            const mailboxEl = document.getElementById('wizMailbox');
            finalSteps.forEach(s => {
                if (s.type === 'ring' && wizExtensions.length > 0) {
                    s.extensions = [...wizExtensions];
                }
                if (s.type === 'queue' && queueEl && queueEl.value) {
                    s.queue_name = queueEl.value;
                }
                if (s.type === 'voicemail' && mailboxEl && mailboxEl.value) {
                    s.mailbox = mailboxEl.value;
                }
            });
        }
    }

    // Submit the form directly
    document.getElementById('stepsInput').value = JSON.stringify(finalSteps);
    document.getElementById('hidName').value = name;
    document.getElementById('hidDesc').value = document.getElementById('wizDesc').value || '';
    document.getElementById('hidTrunk').value = trunk;
    const trunkCtx = trunkOpt && trunkOpt.dataset.context ? trunkOpt.dataset.context : 'from-trunk';
    document.getElementById('hidCtx').value = trunkCtx;
    document.getElementById('hidPrio').value = '1';
    document.getElementById('hidEnabled').value = '1';
    document.getElementById('flowForm').submit();
}

// Show wizard on page load (only for create)
document.addEventListener('DOMContentLoaded', () => openTplModal());
@endunless

// ════════════════════════════════════════
// INIT from existing callflow or template
// ════════════════════════════════════════
(function initFromExisting(){
    const existing = @json(isset($callflow) ? ($callflow->steps ?? []) : (isset($templateSteps) && $templateSteps ? $templateSteps : []));
    if (!existing.length) return;
    loadSteps(existing);
})();

// ════════════════════════════════════════
// AUTO-FILL context
// ════════════════════════════════════════
document.getElementById('cfgTrunk').addEventListener('change', function(){
    const o = this.options[this.selectedIndex];
    if (o && o.dataset.context) document.getElementById('cfgCtx').value = o.dataset.context;
});

// ════════════════════════════════════════
// RENDER
// ════════════════════════════════════════
function render(){
    canvasInner.innerHTML = '';
    const startEl = mkStart();
    canvasInner.appendChild(startEl);

    nodes.forEach(n => {
        canvasInner.appendChild(mkNode(n));
    });

    document.getElementById('nodeCount').textContent = nodes.length + ' bloc' + (nodes.length!==1?'s':'');
    drawEdges();
    applyTransform();
}

function mkStart(){
    const el = document.createElement('div');
    el.className = 'node node-start';
    el.style.left = '400px';
    el.style.top = '30px';
    el.dataset.id = startId;
    el.innerHTML = `<div class="node-header"><i class="bi bi-telephone-inbound-fill me-1"></i> APPEL ENTRANT</div>`;
    // out port
    const port = document.createElement('div');
    port.className = 'port port-out';
    port.dataset.owner = startId;
    port.dataset.dir = 'out';
    port.addEventListener('mousedown', onPortDown);
    el.appendChild(port);
    return el;
}

function mkNode(n){
    const t = TYPES[n.type] || {label:n.type, icon:'bi-circle', color:'answer'};
    const el = document.createElement('div');
    el.className = `node nc-${t.color} ${n.id == selectedId ? 'selected' : ''}`;
    el.style.left = n.x + 'px';
    el.style.top  = n.y + 'px';
    el.dataset.id = n.id;

    const detail = nodeDetail(n);
    el.innerHTML = `
        <div class="node-header">
            <div class="node-icon"><i class="bi ${t.icon}"></i></div>
            ${t.label}
        </div>
        <div class="node-body">${detail}</div>
        <div class="node-delete" onclick="event.stopPropagation(); deleteNode(${n.id})"><i class="bi bi-x"></i></div>
    `;

    // ports
    const pIn = document.createElement('div');
    pIn.className = 'port port-in';
    pIn.dataset.owner = n.id;
    pIn.dataset.dir = 'in';
    el.appendChild(pIn);

    if (n.type !== 'hangup') {
        const pOut = document.createElement('div');
        pOut.className = 'port port-out';
        pOut.dataset.owner = n.id;
        pOut.dataset.dir = 'out';
        pOut.addEventListener('mousedown', onPortDown);
        el.appendChild(pOut);
    }

    // drag
    el.addEventListener('mousedown', onNodeDown);
    el.addEventListener('click', (e) => {
        if (e.target.closest('.port') || e.target.closest('.node-delete')) return;
        selectNode(n.id);
    });

    return el;
}

function nodeDetail(n){
    switch(n.type){
        case 'answer':    return `Attente ${n.data.wait||1}s`;
        case 'ring':      return (n.data.extensions||[]).length ? `Postes: ${n.data.extensions.join(', ')}` : '<i>Aucun poste</i>';
        case 'queue':     return n.data.queue_name || '<i>Aucune file</i>';
        case 'voicemail': return `Boite ${n.data.mailbox||'1000'}`;
        case 'playback':  return n.data.sound||'hello-world';
        case 'announcement': return n.data.sound||'custom/welcome';
        case 'moh':       return `${n.data.moh_class||'default'} (${n.data.duration||10}s)`;
        case 'goto':      return `→ ${n.data.target_context||'default'}`;
        case 'hangup':    return 'Fin';
        default: return '';
    }
}

// ════════════════════════════════════════
// SVG EDGES (bezier)
// ════════════════════════════════════════
function drawEdges(){
    // Remove old edge lines
    canvasInner.querySelectorAll('.edge-line').forEach(el => el.remove());

    const firstLinked = getStartNext();
    if (firstLinked !== null) {
        drawLine(startPortPos(), nodePortPos(firstLinked,'in'));
    }

    nodes.forEach(n => {
        if (n.next !== null) {
            const target = nodes.find(x => x.id === n.next);
            if (target) drawLine(nodePortPos(n.id,'out'), nodePortPos(target.id,'in'));
        }
    });
}

function drawLine(a, b){
    const dx = b.x - a.x;
    const dy = b.y - a.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    if (len < 1) return;
    const angle = Math.atan2(dy, dx) - Math.PI / 2; // rotate so 0° = pointing down
    const div = document.createElement('div');
    div.className = 'edge-line';
    div.style.left = a.x + 'px';
    div.style.top  = a.y + 'px';
    div.style.height = len + 'px';
    div.style.transform = `rotate(${angle}rad)`;
    canvasInner.appendChild(div);
}

function startPortPos(){
    const el = canvasInner.querySelector(`[data-id="${startId}"]`);
    const w = el ? el.offsetWidth : 180;
    const h = el ? el.offsetHeight : 52;
    return { x: 400 + w / 2, y: 30 + h };
}
function nodePortPos(id, dir){
    const n = nodes.find(x => x.id === id);
    if (!n) return {x:0,y:0};
    const el = canvasInner.querySelector(`[data-id="${id}"]`);
    const w = el ? el.offsetWidth : 220;
    const h = el ? el.offsetHeight : 70;
    return {
        x: n.x + w / 2,
        y: dir === 'in' ? n.y : n.y + h
    };
}

// ════════════════════════════════════════
// WIRING (connect ports by dragging)
// ════════════════════════════════════════
let wiring = null;

function onPortDown(e){
    e.stopPropagation();
    e.preventDefault();
    const port = e.target.closest('.port');
    const ownerId = port.dataset.owner;
    const pos = (ownerId === startId) ? startPortPos() : nodePortPos(parseInt(ownerId), 'out');
    wiring = { fromId: ownerId === startId ? startId : parseInt(ownerId), from: pos, mx: pos.x, my: pos.y };
    document.addEventListener('mousemove', onWireMove);
    document.addEventListener('mouseup', onWireUp);
}

function onWireMove(e){
    if (!wiring) return;
    const rect = canvasWrap.getBoundingClientRect();
    wiring.mx = (e.clientX - rect.left - camX) / zoom;
    wiring.my = (e.clientY - rect.top - camY) / zoom;
    drawEdges();
}

function onWireUp(e){
    document.removeEventListener('mousemove', onWireMove);
    document.removeEventListener('mouseup', onWireUp);
    if (!wiring) return;

    // find target port-in under cursor
    const elUnder = document.elementFromPoint(e.clientX, e.clientY);
    const port = elUnder?.closest?.('.port-in');
    if (port) {
        const targetId = parseInt(port.dataset.owner);
        if (!isNaN(targetId) && targetId !== wiring.fromId) {
            if (wiring.fromId === startId) {
                setStartNext(targetId);
            } else {
                const src = nodes.find(x => x.id === wiring.fromId);
                if (src) src.next = targetId;
            }
        }
    }

    wiring = null;
    render();
    renderProps();
}

// start→first link (stored in a simple var)
let _startNext = null;
function getStartNext(){
    if (_startNext !== null) return _startNext;
    // auto-link to first node
    if (nodes.length) return nodes[0].id;
    return null;
}
function setStartNext(id){ _startNext = id; }

// ════════════════════════════════════════
// NODE DRAGGING
// ════════════════════════════════════════
let drag = null;

function onNodeDown(e){
    if (e.target.closest('.port') || e.target.closest('.node-delete')) return;
    const el = e.target.closest('.node');
    if (!el || el.classList.contains('node-start')) return;
    e.preventDefault();
    const id = parseInt(el.dataset.id);
    const n = nodes.find(x => x.id === id);
    if (!n) return;

    el.classList.add('dragging');
    drag = {
        id,
        startMX: e.clientX,
        startMY: e.clientY,
        startNX: n.x,
        startNY: n.y,
        el
    };
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragUp);
}

function onDragMove(e){
    if (!drag) return;
    const dx = (e.clientX - drag.startMX) / zoom;
    const dy = (e.clientY - drag.startMY) / zoom;
    const n = nodes.find(x => x.id === drag.id);
    if (!n) return;
    n.x = Math.round(drag.startNX + dx);
    n.y = Math.round(drag.startNY + dy);
    drag.el.style.left = n.x + 'px';
    drag.el.style.top  = n.y + 'px';
    drawEdges();
}

function onDragUp(){
    if (drag) drag.el.classList.remove('dragging');
    drag = null;
    document.removeEventListener('mousemove', onDragMove);
    document.removeEventListener('mouseup', onDragUp);
}

// ════════════════════════════════════════
// PAN & ZOOM
// ════════════════════════════════════════
let panning = false, panStart = null;

canvasWrap.addEventListener('mousedown', e => {
    if (e.target.closest('.node') || e.target.closest('.port') || e.target.closest('.zoom-btn')) return;
    panning = true;
    panStart = { x: e.clientX - camX, y: e.clientY - camY };
    canvasWrap.classList.add('grabbing');
});
document.addEventListener('mousemove', e => {
    if (!panning) return;
    camX = e.clientX - panStart.x;
    camY = e.clientY - panStart.y;
    applyTransform();
});
document.addEventListener('mouseup', () => {
    panning = false;
    canvasWrap.classList.remove('grabbing');
});

canvasWrap.addEventListener('wheel', e => {
    e.preventDefault();
    const d = e.deltaY > 0 ? -0.08 : 0.08;
    zoom = Math.min(2, Math.max(0.3, zoom + d));
    applyTransform();
}, { passive: false });

function applyTransform(){
    const t = `translate(${camX}px,${camY}px) scale(${zoom})`;
    canvasInner.style.transform = t;
}

function zoomIn(){  zoom = Math.min(2, zoom + 0.15); applyTransform(); }
function zoomOut(){ zoom = Math.max(0.3, zoom - 0.15); applyTransform(); }
function zoomReset(){ zoom = 1; camX = 0; camY = 0; applyTransform(); }

// ════════════════════════════════════════
// FULLSCREEN
// ════════════════════════════════════════
const fsModal = document.getElementById('fsModal');
const fsBody  = document.getElementById('fsBody');
let fsActive = false;

function openFullscreen(){
    fsBody.appendChild(canvasInner);
    fsModal.classList.add('active');
    fsActive = true;
    // Bind zoom/pan on fsBody
    fsBody.addEventListener('wheel', fsWheel, { passive: false });
    fsBody.addEventListener('mousedown', fsMouseDown);
    zoomReset();
    document.getElementById('fsNodeCount').textContent =
        nodes.length + ' bloc' + (nodes.length !== 1 ? 's' : '');
    document.addEventListener('keydown', fsEscape);
}

function closeFullscreen(){
    canvasWrap.appendChild(canvasInner);
    fsModal.classList.remove('active');
    fsActive = false;
    fsBody.removeEventListener('wheel', fsWheel);
    fsBody.removeEventListener('mousedown', fsMouseDown);
    document.removeEventListener('keydown', fsEscape);
    zoomReset();
}

function fsEscape(e){ if (e.key === 'Escape') closeFullscreen(); }

function fsWheel(e){
    e.preventDefault();
    const d = e.deltaY > 0 ? -0.08 : 0.08;
    zoom = Math.min(2, Math.max(0.3, zoom + d));
    applyTransform();
}

function fsMouseDown(e){
    if (e.target.closest('.node') || e.target.closest('.port')) return;
    panning = true;
    panStart = { x: e.clientX - camX, y: e.clientY - camY };
    fsBody.classList.add('grabbing');
    const up = () => { panning = false; fsBody.classList.remove('grabbing'); document.removeEventListener('mouseup', up); };
    document.addEventListener('mouseup', up);
}

// ════════════════════════════════════════
// ADD / DELETE / SELECT
// ════════════════════════════════════════
function addNode(type){
    const cx = (-camX + canvasWrap.clientWidth / 2) / zoom;
    const cy = (-camY + canvasWrap.clientHeight / 2) / zoom;
    const n = {
        id: nextId++,
        type,
        x: Math.round(cx - 110 + (Math.random() * 60 - 30)),
        y: Math.round(cy - 30 + (Math.random() * 60 - 30)),
        data: JSON.parse(JSON.stringify(DEFAULTS[type] || {})),
        next: null,
    };

    // auto-link: attach to last unlinked node
    const unlinked = nodes.filter(nd => nd.next === null && nd.type !== 'hangup');
    if (unlinked.length) {
        const last = unlinked[unlinked.length - 1];
        last.next = n.id;
        // position below
        n.x = last.x;
        n.y = last.y + 140;
    } else if (!nodes.length) {
        // first node: link from start
        _startNext = n.id;
        n.x = 400;
        n.y = 180;
    }

    nodes.push(n);
    selectedId = n.id;
    render();
    renderProps();
}

function deleteNode(id){
    // unlink references
    if (_startNext === id) _startNext = null;
    nodes.forEach(n => { if (n.next === id) n.next = null; });
    nodes = nodes.filter(n => n.id !== id);
    if (selectedId === id) selectedId = null;
    render();
    renderProps();
}

function selectNode(id){
    selectedId = (selectedId === id) ? null : id;
    render();
    renderProps();
}

// ════════════════════════════════════════
// PROPERTIES PANEL
// ════════════════════════════════════════
function renderProps(){
    const panel = document.getElementById('propPanel');
    const n = nodes.find(x => x.id === selectedId);
    if (!n) {
        panel.innerHTML = `<div class="cfg-empty"><i class="bi bi-hand-index" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>Cliquez sur un bloc</div>`;
        return;
    }
    const t = TYPES[n.type] || {};
    let h = `<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
        <div class="node-icon" style="width:26px;height:26px;font-size:.8rem;background:${colorBg(n.type)};color:${colorFg(n.type)};border-radius:6px;display:flex;align-items:center;justify-content:center;">
            <i class="bi ${t.icon||'bi-circle'}"></i></div>
        <strong style="font-size:.88rem;">${t.label||n.type}</strong>
        <span style="margin-left:auto;font-family:'JetBrains Mono',monospace;font-size:.65rem;color:var(--text-secondary);">id:${n.id}</span>
    </div>`;

    switch(n.type){
        case 'answer':
            h += cfgF('Delai (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.wait||1}" min="0" max="30" onchange="setProp(${n.id},'wait',+this.value)">`);
            break;
        case 'ring':
            h += cfgF('Timeout (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.timeout||25}" min="5" max="120" onchange="setProp(${n.id},'timeout',+this.value)">`);
            h += `<label style="font-weight:600;font-size:.7rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-top:.75rem;display:block;margin-bottom:.3rem;">Postes</label>`;
            LINES.forEach(l => {
                const ck = (n.data.extensions||[]).includes(String(l.extension)) ? 'checked' : '';
                h += `<div class="member-item">
                    <input type="checkbox" class="form-check-input" ${ck} onchange="toggleExt(${n.id},'${l.extension}',this.checked)" style="margin:0;">
                    <span class="ext-badge">${l.extension}</span>
                    <span style="color:var(--text-secondary);font-size:.72rem;">${l.callerid_name||l.username||''}</span>
                </div>`;
            });
            break;
        case 'queue':
            h += cfgF('File d\'attente', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'queue_name',this.value)">
                <option value="">— Choisir —</option>
                ${QUEUES.map(q => `<option value="${q.name}" ${n.data.queue_name===q.name?'selected':''}>${q.display_name||q.name}</option>`).join('')}
            </select>`);
            h += cfgF('Timeout (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.timeout||60}" min="10" max="300" onchange="setProp(${n.id},'timeout',+this.value)">`);
            break;
        case 'voicemail':
            h += cfgF('Boite', `<input type="text" class="form-control form-control-sm" value="${n.data.mailbox||'1000'}" onchange="setProp(${n.id},'mailbox',this.value)">`);
            h += cfgF('Type', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'vm_type',this.value)">
                <option value="u" ${n.data.vm_type==='u'?'selected':''}>Indisponible</option>
                <option value="b" ${n.data.vm_type==='b'?'selected':''}>Occupe</option>
                <option value="s" ${n.data.vm_type==='s'?'selected':''}>Standard</option>
            </select>`);
            break;
        case 'playback':
            h += cfgF('Fichier', `<input type="text" class="form-control form-control-sm" value="${n.data.sound||'hello-world'}" onchange="setProp(${n.id},'sound',this.value)">`);
            break;
        case 'announcement':
            h += cfgF('Fichier', `<input type="text" class="form-control form-control-sm" value="${n.data.sound||'custom/welcome'}" onchange="setProp(${n.id},'sound',this.value)">`);
            break;
        case 'moh':
            h += cfgF('Classe', `<input type="text" class="form-control form-control-sm" value="${n.data.moh_class||'default'}" onchange="setProp(${n.id},'moh_class',this.value)">`);
            h += cfgF('Duree (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.duration||10}" min="1" max="300" onchange="setProp(${n.id},'duration',+this.value)">`);
            break;
        case 'goto':
            h += cfgF('Contexte', `<input type="text" class="form-control form-control-sm" value="${n.data.target_context||'default'}" onchange="setProp(${n.id},'target_context',this.value)">`);
            break;
        case 'hangup':
            h += `<p style="color:var(--text-secondary);font-size:.8rem;">Termine l'appel.</p>`;
            break;
    }

    // connection info
    h += `<hr style="border-color:var(--border);margin:.75rem 0;">`;
    h += `<label style="font-weight:600;font-size:.7rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.3rem;">Connexion sortante</label>`;
    if (n.type === 'hangup') {
        h += `<span style="font-size:.78rem;color:var(--text-secondary);">Aucune (fin)</span>`;
    } else if (n.next) {
        const tgt = nodes.find(x => x.id === n.next);
        h += `<span style="font-size:.78rem;">→ ${tgt ? (TYPES[tgt.type]?.label||tgt.type) + ' #'+tgt.id : '?'}</span>
              <button class="btn-outline-custom" style="margin-left:.5rem;padding:2px 8px;font-size:.7rem;" onclick="setProp(${n.id},'__unlink',true)">Delier</button>`;
    } else {
        h += `<span style="font-size:.78rem;color:var(--text-secondary);">Non connecte — tirez depuis le port vert</span>`;
    }

    panel.innerHTML = h;
}

function cfgF(label, input){
    return `<div class="cfg-section"><label>${label}</label>${input}</div>`;
}

function colorBg(type){
    const m = {answer:'#58a6ff25',ring:'#00e5a025',queue:'#bc8cff25',voicemail:'#d2992225',playback:'#58a6ff25',moh:'#f0883e25',hangup:'#f8514925',announcement:'#d2992225',goto:'#bc8cff25'};
    return m[type]||'#58a6ff25';
}
function colorFg(type){
    const m = {answer:'#58a6ff',ring:'#00e5a0',queue:'#bc8cff',voicemail:'#d29922',playback:'#58a6ff',moh:'#f0883e',hangup:'#f85149',announcement:'#d29922',goto:'#bc8cff'};
    return m[type]||'#58a6ff';
}

function setProp(id, prop, val){
    const n = nodes.find(x => x.id === id);
    if (!n) return;
    if (prop === '__unlink') { n.next = null; }
    else { n.data[prop] = val; }
    render();
    renderProps();
}

function toggleExt(id, ext, checked){
    const n = nodes.find(x => x.id === id);
    if (!n) return;
    if (!n.data.extensions) n.data.extensions = [];
    if (checked && !n.data.extensions.includes(ext)) n.data.extensions.push(ext);
    else if (!checked) n.data.extensions = n.data.extensions.filter(e => e !== ext);
    render();
    renderProps();
}

// ════════════════════════════════════════
// DIALPLAN PREVIEW
// ════════════════════════════════════════
let dpVisible = false;
function toggleDialplan(){
    dpVisible = !dpVisible;
    document.getElementById('dpWrap').style.display = dpVisible ? 'block' : 'none';
    document.getElementById('dpChev').className = dpVisible ? 'bi bi-chevron-up ms-auto' : 'bi bi-chevron-down ms-auto';
    if (dpVisible) refreshDialplan();
}

let dpTimer = null;
function refreshDialplan(){
    if (!dpVisible) return;
    clearTimeout(dpTimer);
    dpTimer = setTimeout(() => {
        fetch('{{ route("callflows.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                steps: JSON.stringify(buildSteps()),
                trunk_id: document.getElementById('cfgTrunk').value,
                inbound_context: document.getElementById('cfgCtx').value
            })
        })
        .then(r => r.json())
        .then(d => { document.getElementById('dpCode').textContent = d.dialplan || '// Vide'; })
        .catch(() => { document.getElementById('dpCode').textContent = '// Erreur'; });
    }, 300);
}

// ════════════════════════════════════════
// BUILD STEPS (walk the graph in order)
// ════════════════════════════════════════
function buildSteps(){
    const ordered = [];
    let currentId = getStartNext();
    const visited = new Set();
    while (currentId !== null && !visited.has(currentId)) {
        visited.add(currentId);
        const n = nodes.find(x => x.id === currentId);
        if (!n) break;
        ordered.push(Object.assign({ type: n.type }, n.data));
        currentId = n.next;
    }
    return ordered;
}

// ════════════════════════════════════════
// SAVE
// ════════════════════════════════════════
document.getElementById('btnSave').addEventListener('click', () => {
    document.getElementById('stepsInput').value = JSON.stringify(buildSteps());
    document.getElementById('hidName').value    = document.getElementById('cfgName').value;
    document.getElementById('hidDesc').value    = document.getElementById('cfgDesc').value;
    document.getElementById('hidTrunk').value   = document.getElementById('cfgTrunk').value;
    document.getElementById('hidCtx').value     = document.getElementById('cfgCtx').value;
    document.getElementById('hidPrio').value    = document.getElementById('cfgPrio').value;
    document.getElementById('hidEnabled').value = document.getElementById('cfgEnabled').checked ? '1' : '0';
    document.getElementById('flowForm').submit();
});

// ════════════════════════════════════════
// SAVE AS TEMPLATE
// ════════════════════════════════════════
document.getElementById('btnSaveTpl').addEventListener('click', () => {
    const steps = buildSteps();
    if (!steps.length) { alert('Ajoutez au moins un bloc avant de sauvegarder un template.'); return; }
    const name = prompt('Nom du template :');
    if (!name) return;
    const desc = prompt('Description (optionnel) :') || '';
    document.getElementById('tplStepsInput').value = JSON.stringify(steps);
    document.getElementById('tplNameInput').value = name;
    document.getElementById('tplDescInput').value = desc;
    document.getElementById('saveTplForm').submit();
});

// ════════════════════════════════════════
// BOOT
// ════════════════════════════════════════
render();
</script>
@endpush
