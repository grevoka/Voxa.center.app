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
        min-width: 0;
        min-height: 0;
    }
    .panel:first-child .panel-body {
        overflow-y: auto;
        min-height: 0;
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
    .canvas-fullscreen .fs-content {
        flex: 1; display: flex; overflow: hidden; min-height: 0;
    }
    .canvas-fullscreen .fs-panel {
        display: flex; flex-direction: column;
        background: var(--surface-2);
        flex-shrink: 0;
        min-width: 0;
    }
    .canvas-fullscreen .fs-panel-left {
        width: 260px; border-right: 1px solid var(--border);
    }
    .canvas-fullscreen .fs-panel-right {
        width: 260px; border-left: 1px solid var(--border);
    }
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
        width: 680px; max-width: 95vw; max-height: 92vh;
        display: flex; flex-direction: column;
        overflow: hidden;
    }
    .tpl-modal-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: .75rem;
        font-weight: 700; font-size: .9rem;
        color: #e2e4eb;
    }
    .tpl-modal-body {
        flex: 1; overflow-y: auto; padding: 1rem 1.25rem;
        min-height: 0;
    }
    .tpl-grid {
        display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem;
    }
    .tpl-card {
        border: 1px solid var(--border); border-radius: 8px;
        padding: .55rem .7rem; cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .tpl-card:hover, .tpl-card.selected { border-color: var(--accent); background: var(--surface-3); }
    .tpl-card.selected { box-shadow: 0 0 0 2px var(--accent); }
    .tpl-card .tpl-icon { font-size: 1rem; margin-bottom: .2rem; color: var(--accent); }
    .tpl-card h6 { margin: 0 0 .15rem; font-size: .75rem; font-weight: 700; color: #e2e4eb; }
    .tpl-card p { margin: 0; font-size: .65rem; color: #8b949e; line-height: 1.3; }
    .tpl-card .tpl-badge {
        display: inline-block; font-size: .6rem; padding: 1px 6px;
        border-radius: 4px; background: var(--accent); color: #000;
        margin-left: .4rem; vertical-align: middle;
    }
    .tpl-card .tpl-steps { font-size: .6rem; color: var(--text-secondary); margin-top: .2rem; }
    .tpl-footer {
        padding: .75rem 1.25rem;
        border-top: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0;
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
        color: #e2e4eb;
    }
    .wiz-step .wiz-subtitle {
        font-size: .72rem; color: #8b949e; margin-bottom: 1rem;
    }
    .wiz-check-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: .4rem;
    }
    .wiz-check-item {
        padding: .5rem .75rem; border-radius: 8px;
        border: 1px solid var(--border); background: var(--surface-2);
        transition: border-color .15s, background .15s;
        cursor: pointer; user-select: none;
        display: flex; align-items: center; gap: .6rem;
    }
    .wiz-check-item:hover { border-color: var(--text-secondary); }
    .wiz-check-item.checked {
        border-color: var(--accent); background: rgba(41,182,246,.1);
    }
    .wiz-check-item .wiz-check-box {
        width: 18px; height: 18px; border-radius: 4px;
        border: 2px solid var(--border); flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s; font-size: .7rem; color: #000;
    }
    .wiz-check-item.checked .wiz-check-box {
        background: var(--accent); border-color: var(--accent);
    }
    .wiz-ext-num {
        font-family: 'JetBrains Mono', monospace; font-weight: 700;
        font-size: .82rem; color: var(--text-primary);
    }
    .wiz-check-item.checked .wiz-ext-num { color: var(--accent); }
    .wiz-ext-name { font-size: .72rem; color: var(--text-secondary); }
    .wiz-steps-indicator {
        display: flex; gap: .5rem; align-items: center;
        font-size: .7rem; color: var(--text-secondary);
    }
    .wiz-steps-indicator .step-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--border);
    }
    .wiz-steps-indicator .step-dot.active { background: var(--accent); }
    .wiz-steps-indicator .step-dot.done { background: #29b6f6; }
    .wiz-feat-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: .5rem;
    }
    .wiz-feat-item {
        padding: .6rem .75rem; border-radius: 8px;
        border: 1px solid var(--border); background: var(--surface-2);
        cursor: pointer; user-select: none;
        display: flex; align-items: center; gap: .65rem;
        transition: border-color .15s, background .15s;
    }
    .wiz-feat-item:hover { border-color: var(--text-secondary); }
    .wiz-feat-item.checked { border-color: var(--accent); background: rgba(41,182,246,.1); }
    .wiz-feat-item .wiz-feat-icon {
        width: 32px; height: 32px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; flex-shrink: 0;
    }
    .wiz-feat-item .wiz-feat-text h6 { margin: 0; font-size: .78rem; font-weight: 700; }
    .wiz-feat-item .wiz-feat-text p { margin: 0; font-size: .65rem; color: var(--text-secondary); }
    .wiz-feat-item .wiz-check-box {
        width: 18px; height: 18px; border-radius: 4px;
        border: 2px solid var(--border); flex-shrink: 0; margin-left: auto;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s; font-size: .7rem; color: #000;
    }
    .wiz-feat-item.checked .wiz-check-box { background: var(--accent); border-color: var(--accent); }
    .wiz-or-divider {
        display: flex; align-items: center; gap: .75rem;
        margin: .65rem 0; color: var(--text-secondary); font-size: .72rem;
    }
    .wiz-or-divider::before, .wiz-or-divider::after {
        content: ''; flex: 1; height: 1px; background: var(--border);
    }

    /* ── Wizard timeline ── */
    .wiz-timeline { display: flex; flex-direction: column; gap: 0; }
    .wiz-tl-item {
        display: flex; align-items: center; gap: .65rem;
        padding: .5rem .75rem; border-radius: 8px;
        border: 1px solid var(--border); background: var(--surface-2);
        position: relative;
    }
    .wiz-tl-item.mandatory {
        border-color: var(--accent); background: rgba(41,182,246,.08);
    }
    .wiz-tl-item.auto {
        border-style: dashed; opacity: .7;
    }
    .wiz-tl-icon {
        width: 28px; height: 28px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; flex-shrink: 0;
    }
    .wiz-tl-text { flex: 1; }
    .wiz-tl-text h6 { margin: 0; font-size: .78rem; font-weight: 700; }
    .wiz-tl-text p { margin: 0; font-size: .65rem; color: var(--text-secondary); }
    .wiz-tl-remove {
        width: 22px; height: 22px; border-radius: 50%;
        background: var(--surface-3); border: 1px solid var(--border);
        color: var(--text-secondary); font-size: .6rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; flex-shrink: 0; transition: all .15s;
    }
    .wiz-tl-remove:hover { background: #f85149; color: #fff; border-color: #f85149; }
    .wiz-tl-connector {
        width: 2px; height: 18px; background: var(--border);
        margin-left: 1.1rem;
    }
    .wiz-tl-connector.accent { background: var(--accent); }
    .wiz-tl-label {
        font-size: .6rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .5px; color: var(--text-secondary); margin-bottom: .25rem;
    }
    .wiz-tl-drag {
        cursor: grab; color: var(--text-secondary); font-size: .75rem;
        flex-shrink: 0; opacity: .5; transition: opacity .15s;
    }
    .wiz-tl-drag:hover { opacity: 1; }
    .wiz-tl-item.dragging {
        opacity: .4; border-style: dashed;
    }
    .wiz-tl-item.drag-over {
        border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent);
    }

    .edges-svg {
        position: absolute;
        top: 0; left: 0;
        width: 1px; height: 1px;
        pointer-events: none;
        overflow: visible;
        z-index: 1;
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
    .zoom-btn:hover { border-color: #29b6f6; color: #29b6f6; }

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
        border-color: #29b6f6;
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
        width: 14px; height: 14px;
        border-radius: 50%;
        background: #29b6f6;
        border: 2px solid var(--surface-2);
        cursor: crosshair;
        z-index: 6;
        transition: transform .15s, box-shadow .15s;
    }
    .port:hover {
        transform: scale(1.6);
        box-shadow: 0 0 8px rgba(41,182,246,.6);
    }
    .port-out {
        bottom: -7px;
        left: 50%;
        transform: translateX(-50%);
    }
    .btn-tts-preview {
        margin-top: 4px;
        padding: 3px 10px;
        font-size: .7rem;
        font-weight: 600;
        border: 1px solid #3fb950;
        background: #3fb95015;
        color: #3fb950;
        border-radius: 6px;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-tts-preview:hover { background: #3fb95030; }
    .btn-tts-preview:disabled { opacity: .5; cursor: wait; }

    .port-branch {
        position: absolute;
        bottom: -7px;
        width: 14px; height: 14px;
        border-radius: 50%;
        background: #bc6ff1;
        border: 2px solid #0d1117;
        cursor: crosshair;
        z-index: 5;
        transition: transform .15s, box-shadow .15s;
    }
    .port-branch:hover { transform: scale(1.5); box-shadow: 0 0 8px rgba(188,111,241,.6); }
    .port-branch-label {
        position: absolute;
        bottom: -20px;
        font-size: .55rem;
        font-weight: 800;
        color: #bc6ff1;
        text-align: center;
        width: 20px;
        margin-left: -3px;
        pointer-events: none;
    }
    .port-in {
        top: -7px;
        left: 50%;
        transform: translateX(-50%);
    }
    /* Invisible larger hit area around port-in for easier drop */
    .port-in::after {
        content: '';
        position: absolute;
        top: -10px; left: -10px;
        width: 34px; height: 34px;
        border-radius: 50%;
    }
    .port-out:hover { transform: translateX(-50%) scale(1.6); box-shadow: 0 0 8px rgba(41,182,246,.6); }
    .port-in:hover { transform: translateX(-50%) scale(1.6); box-shadow: 0 0 8px rgba(41,182,246,.6); }
    .node:hover .port { box-shadow: 0 0 6px rgba(41,182,246,.3); }

    /* ── Start node (special) ── */
    .node-start {
        width: 180px;
        background: #29b6f6;
        border-color: #29b6f6;
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
        border-color: #29b6f6;
    }

    /* ── Node colors ── */
    .nc-answer .node-header   { background: #58a6ff15; }
    .nc-answer .node-icon     { background: #58a6ff25; color: #58a6ff; }
    .nc-ring .node-header     { background: #29b6f615; }
    .nc-ring .node-icon       { background: #29b6f625; color: #29b6f6; }
    .nc-queue .node-header    { background: #bc8cff15; }
    .nc-queue .node-icon      { background: #bc8cff25; color: #bc8cff; }
    .nc-voicemail .node-header { background: #d2992215; }
    .nc-voicemail .node-icon  { background: #d2992225; color: #d29922; }
    .nc-playback .node-header { background: #58a6ff15; }
    .nc-playback .node-icon   { background: #58a6ff25; color: #58a6ff; }
    .nc-moh .node-header      { background: #f0883e15; }
    .nc-moh .node-icon        { background: #f0883e25; color: #f0883e; }
    .nc-ai .node-header       { background: #10b98115; }
    .nc-ai .node-icon         { background: #10b98125; color: #10b981; }
    .nc-hangup .node-header   { background: #f8514915; }
    .nc-hangup .node-icon     { background: #f8514925; color: #f85149; }
    .nc-announcement .node-header { background: #d2992215; }
    .nc-announcement .node-icon   { background: #d2992225; color: #d29922; }
    .nc-goto .node-header     { background: #bc8cff15; }
    .nc-goto .node-icon       { background: #bc8cff25; color: #bc8cff; }
    .nc-ivr .node-header      { background: #e8671515; }
    .nc-ivr .node-icon        { background: #e8671525; color: #e86715; }
    .nc-time .node-header     { background: #f0883e15; }
    .nc-time .node-icon       { background: #f0883e25; color: #f0883e; }

    /* ── Palette ── */
    .pal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .35rem;
        margin-bottom: .6rem;
    }
    .pal-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.5rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface-3);
        cursor: pointer;
        transition: all .15s;
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--text-secondary);
    }
    .pal-item:hover { border-color: #29b6f6; color: #29b6f6; background: var(--accent-dim); }
    .pal-icon {
        width: 22px; height: 22px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    .cfg-toggle {
        display: flex; align-items: center;
        padding: .5rem .4rem; margin-bottom: .5rem;
        font-weight: 700; font-size: .68rem;
        letter-spacing: .5px; text-transform: uppercase;
        color: var(--text-secondary);
        cursor: pointer; user-select: none;
        border-top: 1px solid var(--border);
        padding-top: .65rem;
    }
    .cfg-toggle:hover { color: var(--text-primary); }
    .cfg-collapsed { display: none !important; }

    /* ── Config (right) ── */
    .cfg-section { margin-bottom: 1rem; position: relative; z-index: 2; }
    .cfg-section input, .cfg-section select { pointer-events: auto; }
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
        color: #29b6f6;
        font-size: 0.72rem;
    }

    /* ── Edit hub cards ── */
    .edit-hub-card {
        flex: 1; min-width: 180px; max-width: 260px;
        padding: 1.25rem; border-radius: 12px;
        border: 1px solid var(--border); background: var(--surface-2);
        cursor: pointer; transition: all .2s;
        text-align: center;
    }
    .edit-hub-card:hover { border-color: var(--accent); background: var(--surface-3); transform: translateY(-2px); }
    .edit-hub-card h6 { margin: .6rem 0 .3rem; font-size: .88rem; font-weight: 700; }
    .edit-hub-card p { margin: 0; font-size: .72rem; color: var(--text-secondary); line-height: 1.4; }

    @media (max-width: 1100px) {
        .builder-wrap { grid-template-columns: 1fr; height: auto; }
    }
</style>
@endpush

@section('content')
    @if(isset($callflow))
    {{-- EDIT MODE: simple hub page --}}
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">{{ $callflow->name }}</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">
                {{ $callflow->description ?: 'Scenario d\'appel' }}
                — <span style="color:{{ $callflow->enabled ? '#29b6f6' : '#f85149' }};">{{ $callflow->enabled ? 'Actif' : 'Inactif' }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('callflows.index') }}" class="btn-outline-custom">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>
    <div style="display:flex; gap:1rem; margin-top:1rem; flex-wrap:wrap;">
        <div class="edit-hub-card" onclick="openFullscreen()">
            <i class="bi bi-bounding-box" style="font-size:2rem; color:#29b6f6;"></i>
            <h6>Cartographie</h6>
            <p>Editeur visuel 2D — blocs, connexions, proprietes</p>
        </div>
        <div class="edit-hub-card" onclick="wizOpenEdit()">
            <i class="bi bi-magic" style="font-size:2rem; color:#bc8cff;"></i>
            <h6>Wizard</h6>
            <p>Modifier les etapes via l'assistant pas-a-pas</p>
        </div>
        <div class="edit-hub-card" onclick="document.getElementById('btnSaveTpl').click()">
            <i class="bi bi-bookmark-plus" style="font-size:2rem; color:#f0883e;"></i>
            <h6>Sauver en template</h6>
            <p>Enregistrer ce scenario comme modele reutilisable</p>
        </div>
        @if($callflow->trunk)
        <div class="edit-hub-card" style="cursor:default;">
            <i class="bi bi-info-circle" style="font-size:2rem; color:#58a6ff;"></i>
            <h6>Infos</h6>
            <p>Trunk: {{ $callflow->trunk->name }}<br>Contexte: {{ $callflow->inbound_context }}<br>{{ count($callflow->steps ?? []) }} etapes</p>
        </div>
        @endif
    </div>
    <button type="button" class="btn btn-accent" id="btnSave" style="display:none;">Enregistrer</button>
    <button type="button" class="btn-outline-custom" id="btnSaveTpl" style="display:none;">Template</button>
    @else
    {{-- CREATE MODE: same hub layout --}}
    <div class="section-header">
        <div>
            <h5 style="font-weight:700; margin:0;">Creer un scenario d'appel</h5>
            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0;">Choisissez un modele ou construisez votre flux</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('callflows.index') }}" class="btn-outline-custom">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>
    <div style="display:flex; gap:1rem; margin-top:1rem; flex-wrap:wrap;">
        <div class="edit-hub-card" onclick="openTplModal()">
            <i class="bi bi-magic" style="font-size:2rem; color:#bc8cff;"></i>
            <h6>Wizard</h6>
            <p>Assistant pas-a-pas pour creer votre scenario</p>
        </div>
        <div class="edit-hub-card" onclick="openFullscreen()">
            <i class="bi bi-bounding-box" style="font-size:2rem; color:#29b6f6;"></i>
            <h6>Cartographie</h6>
            <p>Editeur visuel 2D — blocs, connexions, proprietes</p>
        </div>
    </div>
    <button type="button" class="btn btn-accent" id="btnSave" style="display:none;">Creer</button>
    <button type="button" class="btn-outline-custom" id="btnSaveTpl" style="display:none;">Template</button>
    @endif

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
        <input type="hidden" name="record_calls" id="hidRecord">
        <input type="hidden" name="record_optout" id="hidOptout">
        <input type="hidden" name="record_optout_key" id="hidOptoutKey">
        <input type="hidden" name="caller_id_filter" id="hidCallerIdFilter">
        <input type="hidden" name="did_filter" id="hidDidFilter">
        <input type="hidden" name="positions" id="hidPositions">
        <input type="hidden" name="queue_members" id="hidQueueMembers">
    </form>

    <div class="builder-wrap" style="display:none;">
        {{-- LEFT: palette + config --}}
        <div class="panel">
            <div class="panel-head"><i class="bi bi-plus-circle"></i> Blocs</div>
            {{-- DID + CID filters (hidden fields synced from fullscreen panel) --}}
            <div style="display:none;">
                <select id="cfgDidFilter">
                    <option value="">Par defaut</option>
                    @foreach($callerIds ?? [] as $cid)
                        <option value="{{ $cid->number }}" {{ in_array($cid->number, old('did_filter', $callflow->did_filter ?? [])) ? 'selected' : '' }}>{{ $cid->number }}</option>
                    @endforeach
                </select>
                <select id="cfgCallerIdFilter">
                    <option value="">Par defaut</option>
                    @foreach($callerIds ?? [] as $cid)
                        <option value="{{ $cid->number }}" {{ in_array($cid->number, old('caller_id_filter', $callflow->caller_id_filter ?? [])) ? 'selected' : '' }}>{{ $cid->number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="panel-body" style="padding:.6rem;overflow-y:auto;">
                <div class="pal-grid">
                    <div class="pal-item" onclick="addNode('answer')">
                        <div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-inbound"></i></div> Repondre
                    </div>
                    <div class="pal-item" onclick="addNode('queue')">
                        <div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-people"></i></div> File
                    </div>
                    <div class="pal-item" onclick="addNode('ring')">
                        <div class="pal-icon" style="background:#29b6f625;color:#29b6f6;"><i class="bi bi-bell"></i></div> Sonnerie
                    </div>
                    <div class="pal-item" onclick="addNode('voicemail')">
                        <div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-voicemail"></i></div> Messagerie
                    </div>
                    <div class="pal-item" onclick="addNode('playback')">
                        <div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-volume-up"></i></div> Audio
                    </div>
                    <div class="pal-item" onclick="addNode('announcement')">
                        <div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-megaphone"></i></div> Annonce
                    </div>
                    <div class="pal-item" onclick="addNode('forward')">
                        <div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-forward"></i></div> Renvoi
                    </div>
                    <div class="pal-item" onclick="addNode('moh')">
                        <div class="pal-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-music-note-beamed"></i></div> Musique
                    </div>
                    <div class="pal-item" onclick="addNode('ivr')">
                        <div class="pal-icon" style="background:#e8671525;color:#e86715;"><i class="bi bi-grid-3x3-gap"></i></div> IVR
                    </div>
                    <div class="pal-item" onclick="addNode('time_condition')">
                        <div class="pal-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-clock-history"></i></div> Horaires
                    </div>
                    <div class="pal-item" onclick="addNode('ai_agent')">
                        <div class="pal-icon" style="background:#10b98125;color:#10b981;"><i class="bi bi-robot"></i></div> Agent IA
                    </div>
                    <div class="pal-item" onclick="addNode('goto')">
                        <div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-arrow-right-circle"></i></div> Goto
                    </div>
                    <div class="pal-item" onclick="addNode('hangup')">
                        <div class="pal-icon" style="background:#f8514925;color:#f85149;"><i class="bi bi-telephone-x"></i></div> Raccrocher
                    </div>
                </div>

                <div class="cfg-toggle" onclick="document.getElementById('cfgPanel').classList.toggle('cfg-collapsed')">
                    <i class="bi bi-sliders me-1"></i> Configuration
                    <i class="bi bi-chevron-down ms-auto" style="font-size:.6rem;"></i>
                </div>
                <div id="cfgPanel" style="max-height:calc(100vh - 500px);overflow-y:auto;">
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
                               value="{{ old('inbound_context', $callflow->inbound_context ?? 'from-trunk') }}" required readonly style="opacity:.7;cursor:not-allowed;">
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
                    <div class="cfg-section">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cfgRecord"
                                {{ old('record_calls', $callflow->record_calls ?? false) ? 'checked' : '' }}
                                onchange="toggleRecordOptions()">
                            <label class="form-check-label" for="cfgRecord"
                                   style="text-transform:none; font-size:0.8rem; color:var(--text-primary);">
                                <i class="bi bi-record-circle" style="color:#ef4444;"></i> Enregistrer les appels
                            </label>
                        </div>
                        <small style="color:var(--text-secondary);font-size:0.68rem;">MixMonitor — les conversations seront enregistrees en WAV</small>
                        <div id="recordOptions" style="display:{{ old('record_calls', $callflow->record_calls ?? false) ? '' : 'none' }};margin-top:0.5rem;padding:0.5rem;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);border-radius:8px;">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cfgOptout"
                                    {{ old('record_optout', $callflow->record_optout ?? false) ? 'checked' : '' }}
                                    onchange="toggleOptoutKey()">
                                <label class="form-check-label" for="cfgOptout"
                                       style="text-transform:none; font-size:0.78rem; color:var(--text-primary);">
                                    Permettre l'arret par l'appelant
                                </label>
                            </div>
                            <div id="optoutKeyGroup" style="display:{{ old('record_optout', $callflow->record_optout ?? false) ? '' : 'none' }};">
                                <div class="d-flex align-items-center gap-2">
                                    <small style="color:var(--text-secondary);font-size:0.72rem;white-space:nowrap;">Touche DTMF :</small>
                                    <select class="form-select form-select-sm" id="cfgOptoutKey" style="width:70px;">
                                        @foreach(['0','1','2','3','4','5','6','7','8','9','*','#'] as $k)
                                            <option value="{{ $k }}" {{ old('record_optout_key', $callflow->record_optout_key ?? '8') === $k ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <small style="color:var(--text-secondary);font-size:0.65rem;margin-top:0.25rem;display:block;">
                                    L'appelant pourra appuyer sur cette touche pour stopper l'enregistrement pendant la conversation
                                </small>
                            </div>
                        </div>
                    </div>
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
                    <pre id="dpCode" style="padding:.75rem; margin:0; font-family:'JetBrains Mono',monospace; font-size:.65rem; color:#29b6f6; overflow:auto; max-height:220px; background:var(--surface);"></pre>
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
            <div style="margin-left:auto; display:flex; gap:0.5rem; align-items:center;">
                <button class="zoom-btn" onclick="zoomIn()" title="Zoom +"><i class="bi bi-plus"></i></button>
                <button class="zoom-btn" onclick="zoomOut()" title="Zoom -"><i class="bi bi-dash"></i></button>
                <button class="zoom-btn" onclick="centerOnNodes()" title="Centrer"><i class="bi bi-bullseye"></i></button>
                @if(isset($callflow))
                <button class="btn-fs" onclick="closeFullscreen(); wizOpenEdit();" title="Wizard"><i class="bi bi-magic me-1"></i> Wizard</button>
                @endif
                <button class="btn btn-accent" style="font-size:.75rem; padding:4px 12px;" onclick="document.getElementById('btnSave').click()">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
                <button class="btn-fs" onclick="closeFullscreen()"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="fs-content">
            {{-- LEFT: palette + config --}}
            <div class="fs-panel fs-panel-left">
                <div style="flex:1; overflow-y:auto; padding:.75rem;">
                    <div style="font-weight:700; font-size:.68rem; letter-spacing:.5px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:.4rem; padding:0 .2rem;">
                        <i class="bi bi-plus-circle me-1"></i> Blocs
                    </div>
                    <div class="pal-grid">
                        <div class="pal-item" onclick="addNode('answer')"><div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-inbound"></i></div> Repondre</div>
                        <div class="pal-item" onclick="addNode('queue')"><div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-people"></i></div> File</div>
                        <div class="pal-item" onclick="addNode('ring')"><div class="pal-icon" style="background:#29b6f625;color:#29b6f6;"><i class="bi bi-bell"></i></div> Sonnerie</div>
                        <div class="pal-item" onclick="addNode('voicemail')"><div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-voicemail"></i></div> Messagerie</div>
                        <div class="pal-item" onclick="addNode('playback')"><div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-volume-up"></i></div> Audio</div>
                        <div class="pal-item" onclick="addNode('announcement')"><div class="pal-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-megaphone"></i></div> Annonce</div>
                        <div class="pal-item" onclick="addNode('forward')"><div class="pal-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-forward"></i></div> Renvoi</div>
                        <div class="pal-item" onclick="addNode('moh')"><div class="pal-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-music-note-beamed"></i></div> Musique</div>
                        <div class="pal-item" onclick="addNode('ivr')"><div class="pal-icon" style="background:#e8671525;color:#e86715;"><i class="bi bi-grid-3x3-gap"></i></div> IVR</div>
                        <div class="pal-item" onclick="addNode('time_condition')"><div class="pal-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-clock-history"></i></div> Horaires</div>
                        <div class="pal-item" onclick="addNode('goto')"><div class="pal-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-arrow-right-circle"></i></div> Goto</div>
                        <div class="pal-item" onclick="addNode('hangup')"><div class="pal-icon" style="background:#f8514925;color:#f85149;"><i class="bi bi-telephone-x"></i></div> Raccrocher</div>
                    </div>

                    <div class="cfg-toggle" onclick="document.getElementById('fsCfgPanel').classList.toggle('cfg-collapsed')">
                        <i class="bi bi-sliders me-1"></i> Configuration
                        <i class="bi bi-chevron-down ms-auto" style="font-size:.6rem;"></i>
                    </div>
                    <div id="fsCfgPanel">
                        <div class="cfg-section">
                            <label>Nom</label>
                            <input type="text" class="form-control form-control-sm fs-cfg-sync" data-target="cfgName"
                                   value="{{ old('name', $callflow->name ?? '') }}" placeholder="accueil-principal">
                        </div>
                        <div class="cfg-section">
                            <label>Description</label>
                            <input type="text" class="form-control form-control-sm fs-cfg-sync" data-target="cfgDesc"
                                   value="{{ old('description', $callflow->description ?? '') }}" placeholder="Optionnel">
                        </div>
                        <div class="cfg-section">
                            <label>Trunk entrant</label>
                            <select class="form-select form-select-sm fs-cfg-sync" data-target="cfgTrunk">
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
                            <label style="color:#29b6f6;"><i class="bi bi-telephone-inbound me-1"></i>Numero appele (DID)</label>
                            <select class="form-select form-select-sm fs-cfg-sync" data-target="cfgDidFilter" id="fsDid"
                                style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
                                <option value="">Par defaut (tous les numeros du trunk)</option>
                                @foreach($callerIds ?? [] as $cid)
                                    <option value="{{ $cid->number }}"
                                        {{ in_array($cid->number, old('did_filter', $callflow->did_filter ?? [])) ? 'selected' : '' }}>
                                        {{ $cid->label }} — {{ $cid->number }}{{ $cid->trunk ? ' ('.$cid->trunk->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:var(--text-secondary);font-size:0.62rem;">Declenche ce scenario uniquement pour ce numero appele.</small>
                        </div>
                        <div class="cfg-section">
                            <label style="color:#bc6ff1;"><i class="bi bi-funnel me-1"></i>Caller ID appelant</label>
                            <select class="form-select form-select-sm fs-cfg-sync" data-target="cfgCallerIdFilter" id="fsCid"
                                style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
                                <option value="">Par defaut (tous les appelants)</option>
                                @foreach($callerIds ?? [] as $cid)
                                    <option value="{{ $cid->number }}"
                                        {{ in_array($cid->number, old('caller_id_filter', $callflow->caller_id_filter ?? [])) ? 'selected' : '' }}>
                                        {{ $cid->label }} — {{ $cid->number }}{{ $cid->trunk ? ' ('.$cid->trunk->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:var(--text-secondary);font-size:0.62rem;">Filtre par numero de l'appelant entrant.</small>
                        </div>
                        <div class="cfg-section">
                            <label>Contexte</label>
                            <input type="text" class="form-control form-control-sm fs-cfg-sync" data-target="cfgCtx"
                                   value="{{ old('inbound_context', $callflow->inbound_context ?? 'from-trunk') }}" readonly style="opacity:.7;cursor:not-allowed;">
                        </div>
                        <div class="cfg-section">
                            <label>Priorite</label>
                            <input type="number" class="form-control form-control-sm fs-cfg-sync" data-target="cfgPrio"
                                   value="{{ old('priority', $callflow->priority ?? 1) }}" min="1" max="100">
                        </div>
                        <div class="cfg-section">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="fsCfgRecord"
                                    {{ old('record_calls', $callflow->record_calls ?? false) ? 'checked' : '' }}
                                    onchange="document.getElementById('cfgRecord').checked = this.checked; toggleRecordOptions();">
                                <label class="form-check-label" for="fsCfgRecord"
                                       style="text-transform:none; font-size:0.8rem; color:var(--text-primary);">
                                    <i class="bi bi-record-circle" style="color:#ef4444;"></i> Enregistrer les appels
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- CENTER: canvas --}}
            <div class="fs-body" id="fsBody"></div>
            {{-- RIGHT: properties --}}
            <div class="fs-panel fs-panel-right">
                <div class="panel-head" style="font-size:.72rem;padding:.6rem .75rem;"><i class="bi bi-gear me-1"></i> Proprietes</div>
                <div class="panel-body" id="fsPropPanel" style="flex:1; overflow-y:auto; padding:.6rem;">
                    <div class="cfg-empty">
                        <i class="bi bi-hand-index" style="font-size:1.2rem; display:block; margin-bottom:.4rem;"></i>
                        Cliquez sur un bloc
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template wizard overlay --}}
    <div class="tpl-overlay" id="tplOverlay">
        <div class="tpl-modal">
            <div class="tpl-modal-head">
                <i class="bi bi-magic"></i>
                <span id="wizTitle">Nouveau scenario</span>
                <div class="wiz-steps-indicator" style="margin-left:auto;">
                    <div class="step-dot active" id="wizDot1"></div>
                    <div class="step-dot" id="wizDot2"></div>
                    <div class="step-dot" id="wizDot3"></div>
                </div>
                <button class="btn-fs" onclick="closeTplModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="tpl-modal-body">

                {{-- Step 1: choose template OR custom --}}
                <div class="wiz-step active" id="wizStep1">
                    <h5>Modeles pre-configures</h5>
                    <div class="wiz-subtitle">Selectionnez un modele pour demarrer rapidement</div>
                    <div class="tpl-grid">
                        @foreach($templates as $tpl)
                        <div class="tpl-card" data-tpl-id="{{ $tpl->id }}" onclick="wizSelectTpl(this, {{ $tpl->id }})">
                            <div class="tpl-icon"><i class="bi {{ $tpl->icon }}"></i></div>
                            <h6>{{ $tpl->name }}</h6>
                            <p>{{ $tpl->description ?: 'Aucune description' }}</p>
                            <div class="tpl-steps">{{ count($tpl->steps) }} etape{{ count($tpl->steps) > 1 ? 's' : '' }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="wiz-or-divider">ou</div>
                    <div class="tpl-card selected-custom" style="border:2px dashed var(--border); background:var(--surface-1);" onclick="wizSelectCustom(this)">
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            <div class="tpl-icon" style="margin:0; font-size:1.4rem;"><i class="bi bi-sliders"></i></div>
                            <div>
                                <h6 style="font-size:.9rem;">Creer sur mesure</h6>
                                <p>Construisez votre scenario etape par etape</p>
                            </div>
                            <i class="bi bi-chevron-right" style="margin-left:auto; font-size:1rem; color:var(--text-secondary);"></i>
                        </div>
                    </div>
                </div>

                {{-- Step 2: step-by-step builder (custom only) --}}
                <div class="wiz-step" id="wizStep2">
                    <h5>Composez votre scenario</h5>
                    <div class="wiz-subtitle">Ajoutez les etapes dans l'ordre souhaite</div>

                    {{-- Timeline of added steps --}}
                    <div id="wizTimeline" style="margin-bottom:1rem;"></div>

                    {{-- Picker for next step --}}
                    <div id="wizPicker">
                        <div style="font-weight:700; font-size:.72rem; letter-spacing:.5px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:.5rem;">
                            <i class="bi bi-plus-circle me-1"></i> Ajouter une etape
                        </div>
                        <div class="wiz-feat-grid">
                            <div class="wiz-feat-item" onclick="wizAddStep('playback')">
                                <div class="wiz-feat-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-volume-up"></i></div>
                                <div class="wiz-feat-text"><h6>Message d'accueil</h6><p>Joue un fichier audio</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('announcement')">
                                <div class="wiz-feat-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-megaphone"></i></div>
                                <div class="wiz-feat-text"><h6>Annonce</h6><p>Message avant mise en attente</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('queue')">
                                <div class="wiz-feat-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-people"></i></div>
                                <div class="wiz-feat-text"><h6>File d'attente</h6><p>Distribue l'appel aux postes</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('ring')">
                                <div class="wiz-feat-icon" style="background:#29b6f625;color:#29b6f6;"><i class="bi bi-bell"></i></div>
                                <div class="wiz-feat-text"><h6>Sonnerie directe</h6><p>Sonne un poste sans file</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('moh')">
                                <div class="wiz-feat-icon" style="background:#f0883e25;color:#f0883e;"><i class="bi bi-music-note-beamed"></i></div>
                                <div class="wiz-feat-text"><h6>Musique d'attente</h6><p>Musique pendant l'attente</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('voicemail')">
                                <div class="wiz-feat-icon" style="background:#d2992225;color:#d29922;"><i class="bi bi-voicemail"></i></div>
                                <div class="wiz-feat-text"><h6>Messagerie vocale</h6><p>Redirige vers la boite vocale</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('ivr')">
                                <div class="wiz-feat-icon" style="background:#e8671525;color:#e86715;"><i class="bi bi-grid-3x3-gap"></i></div>
                                <div class="wiz-feat-text"><h6>Menu vocal (IVR)</h6><p>Touche 1, 2, 3... pour router</p></div>
                            </div>
                            <div class="wiz-feat-item" onclick="wizAddStep('goto')">
                                <div class="wiz-feat-icon" style="background:#bc8cff25;color:#bc8cff;"><i class="bi bi-arrow-right-circle"></i></div>
                                <div class="wiz-feat-text"><h6>Goto</h6><p>Redirige vers un autre contexte</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: configure scenario --}}
                <div class="wiz-step" id="wizStep3">
                    <h5>Configuration</h5>
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

                    {{-- Dynamic fields --}}
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

    {{-- Save as template modal --}}
    <div id="tplModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;">
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:1.5rem;width:380px;max-width:90vw;">
            <h6 style="margin:0 0 1rem;font-weight:700;"><i class="bi bi-bookmark-plus me-2" style="color:#f0883e;"></i>Sauver comme template</h6>
            <div class="cfg-section">
                <label>Nom du template *</label>
                <input type="text" class="form-control form-control-sm" id="tplModalName" placeholder="ex: Accueil standard" autofocus>
            </div>
            <div class="cfg-section">
                <label>Description</label>
                <input type="text" class="form-control form-control-sm" id="tplModalDesc" placeholder="Optionnel">
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
                <button class="btn-outline-custom" onclick="closeSaveTplModal()">Annuler</button>
                <button class="btn btn-accent" onclick="submitSaveTplModal()"><i class="bi bi-check-lg me-1"></i>Sauvegarder</button>
            </div>
        </div>
    </div>

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
    forward:      { label:'Renvoi',            icon:'bi-telephone-forward', color:'forward' },
    goto:         { label:'Goto',              icon:'bi-arrow-right-circle',color:'goto' },
    ivr:          { label:'Menu vocal',        icon:'bi-grid-3x3-gap',      color:'ivr' },
    time_condition:{ label:'Horaires',         icon:'bi-clock-history',     color:'time' },
    ai_agent:     { label:'Agent IA',          icon:'bi-robot',             color:'ai' },
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
    forward:      { dest_type:'extension', destination:'', timeout:20 },
    goto:         { target_context:'default' },
    ivr:          { sound:'custom/menu', timeout:5, options: { '1': '', '2': '', '3': '' } },
    time_condition: { time_start:'09:00', time_end:'18:00', days:'mon-fri', closed_sound:'custom/ferme', closed_action:'voicemail', closed_target:'1000' },
    ai_agent:     { ai_prompt:'Tu es un assistant telephonique professionnel pour notre entreprise. Reponds en francais de maniere concise et utile.', ai_voice:'alloy' },
    hangup:       {},
};
const QUEUES = @json($queues);
const LINES  = @json($lines);
@php
    $audioData = ($audioFiles ?? collect())->map(function($a) {
        return ['id' => $a->id, 'name' => $a->name, 'ref' => $a->getAsteriskRef(), 'category' => $a->category, 'moh_class' => $a->moh_class];
    })->values();
@endphp
const AUDIO_FILES = @json($audioData);

function _fillMohSel(selId, classes, current) {
    const sel = document.getElementById(selId);
    if (!sel) return;
    sel.innerHTML = '';
    const local = classes.filter(c => !c.is_stream && !c.is_playlist);
    const playlists = classes.filter(c => c.is_playlist);
    const streams = classes.filter(c => c.is_stream);
    [{label:'Fichiers locaux', items:local, suffix:f=>(f.files||[]).length+' fichiers'},
     {label:'Playlists', items:playlists, suffix:f=>(f.files||[]).length+' titres'},
     {label:'Flux streaming', items:streams, suffix:()=>'stream'}
    ].forEach(g => {
        if (!g.items.length) return;
        const grp = document.createElement('optgroup');
        grp.label = g.label;
        g.items.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.textContent = (c.display_name || c.name) + ' (' + g.suffix(c) + ')';
            if (c.name === current) opt.selected = true;
            grp.appendChild(opt);
        });
        sel.appendChild(grp);
    });
}

function audioSelect(nodeId, prop, currentVal, category = null) {
    const filtered = category ? AUDIO_FILES.filter(a => a.category === category) : AUDIO_FILES;
    let opts = '<option value="">— Choisir —</option>';
    filtered.forEach(a => {
        opts += `<option value="${a.ref}" ${currentVal === a.ref ? 'selected' : ''}>${a.name} (${a.ref})</option>`;
    });
    opts += `<option value="__custom__" ${(currentVal && !filtered.find(a => a.ref === currentVal)) ? 'selected' : ''}>Saisie manuelle…</option>`;
    let h = `<select class="form-select form-select-sm" onchange="handleAudioSelect(${nodeId},'${prop}',this)">` + opts + `</select>`;
    const isCustom = currentVal && !filtered.find(a => a.ref === currentVal);
    h += `<input type="text" class="form-control form-control-sm mt-1 audio-custom-input" id="audio-custom-${nodeId}-${prop}" value="${isCustom ? currentVal : ''}" placeholder="ex: custom/welcome" onchange="setProp(${nodeId},'${prop}',this.value)" style="display:${isCustom ? '' : 'none'}">`;
    return h;
}
function handleAudioSelect(nodeId, prop, sel) {
    const ci = document.getElementById('audio-custom-'+nodeId+'-'+prop);
    if (sel.value === '__custom__') {
        ci.style.display = '';
        ci.focus();
    } else {
        ci.style.display = 'none';
        setProp(nodeId, prop, sel.value);
    }
}

function toggleRecordOptions() {
    document.getElementById('recordOptions').style.display = document.getElementById('cfgRecord').checked ? '' : 'none';
}
function toggleOptoutKey() {
    document.getElementById('optoutKeyGroup').style.display = document.getElementById('cfgOptout').checked ? '' : 'none';
}

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

function loadSteps(steps, savedPositions) {
    nodes = [];
    selectedId = null;
    const startX = 400, startY = 60, gapY = 140;

    // Map old nodeId → new nodeId for branch reconnection
    const idMap = {};

    steps.forEach((s, i) => {
        // Use saved _nodeId if available, otherwise auto-increment
        const savedNodeId = s._nodeId || null;
        const nId = savedNodeId || (i + 1);
        if (nId >= nextId) nextId = nId + 1;

        const pos = savedPositions && (savedPositions[nId] || savedPositions[i + 1]);
        const n = {
            id: nId,
            type: s.type,
            x: pos ? pos.x : startX + (i % 3) * 250,
            y: pos ? pos.y : (startY + 120 + Math.floor(i / 3) * gapY),
            data: Object.assign({}, s),
            next: null
        };
        delete n.data.type;
        delete n.data._nodeId;

        // Restore branches
        if ((s.type === 'ivr' || s.type === 'time_condition') && s.branch_targets) {
            n.branches = Object.assign({}, s.branch_targets);
            delete n.data.branch_targets;
        }
        nodes.push(n);
        if (savedNodeId) idMap[savedNodeId] = nId;
    });

    // Rebuild linear chain (next) for non-branching nodes
    // Follow original order: each node links to the next, unless it's IVR/time_condition
    for (let i = 0; i < nodes.length - 1; i++) {
        if (nodes[i].type !== 'ivr' && nodes[i].type !== 'time_condition') {
            // Only link to next if next node is not already a branch target of something
            const nextNode = nodes[i + 1];
            const isBranchTarget = nodes.some(n => n.branches && Object.values(n.branches).includes(nextNode.id));
            if (!isBranchTarget) {
                nodes[i].next = nextNode.id;
            }
        }
    }

    if (nextId <= nodes.length) nextId = nodes.length + 1;
    render();
}

const WIZ_EDIT_MODE = {{ isset($callflow) ? 'true' : 'false' }};
let wizSelectedTplId = null;
let wizIsCustom = false;
let wizCurrentStep = 1;
let wizCustomSteps = []; // user-added steps for custom builder

function openTplModal() { document.getElementById('tplOverlay').classList.add('active'); }
function closeTplModal() { document.getElementById('tplOverlay').classList.remove('active'); }

function wizSelectTpl(card, id) {
    document.querySelectorAll('#wizStep1 .tpl-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    wizSelectedTplId = id;
    wizIsCustom = false;
    document.getElementById('wizBtnNext').disabled = false;
}
function wizSelectCustom(card) {
    document.querySelectorAll('#wizStep1 .tpl-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    wizSelectedTplId = null;
    wizIsCustom = true;
    document.getElementById('wizBtnNext').disabled = false;
}

function wizAddStep(type) {
    wizCustomSteps.push({ type, ...JSON.parse(JSON.stringify(DEFAULTS[type] || {})) });
    wizRenderTimeline();
    document.getElementById('wizBtnNext').disabled = false;
}

function wizRemoveStep(index) {
    wizCustomSteps.splice(index, 1);
    wizRenderTimeline();
    if (!wizCustomSteps.length) document.getElementById('wizBtnNext').disabled = true;
}

function wizRenderTimeline() {
    const el = document.getElementById('wizTimeline');
    let html = '';

    // Mandatory: Décrocher
    html += '<div class="wiz-tl-label">Debut obligatoire</div>';
    html += `<div class="wiz-tl-item mandatory">
        <div class="wiz-tl-icon" style="background:#58a6ff25;color:#58a6ff;"><i class="bi bi-telephone-inbound"></i></div>
        <div class="wiz-tl-text"><h6>Decrocher</h6><p>Repond a l'appel entrant</p></div>
        <span style="font-size:.6rem; color:var(--accent); font-weight:700;">OBLIGATOIRE</span>
    </div>`;

    // User steps (draggable)
    wizCustomSteps.forEach((s, i) => {
        const t = TYPES[s.type] || { label: s.type, icon: 'bi-circle' };
        html += '<div class="wiz-tl-connector accent"></div>';
        html += `<div class="wiz-tl-item" draggable="true" data-wiz-idx="${i}"
                      ondragstart="wizDragStart(event,${i})" ondragend="wizDragEnd(event)"
                      ondragover="wizDragOver(event)" ondrop="wizDrop(event,${i})" ondragleave="wizDragLeave(event)">
            <div class="wiz-tl-drag" title="Deplacer"><i class="bi bi-grip-vertical"></i></div>
            <div class="wiz-tl-icon" style="background:${colorBg(s.type)};color:${colorFg(s.type)};"><i class="bi ${t.icon}"></i></div>
            <div class="wiz-tl-text"><h6>${t.label}</h6><p>${wizStepDesc(s)}</p></div>
            <div class="wiz-tl-remove" onclick="wizRemoveStep(${i})" title="Supprimer"><i class="bi bi-x"></i></div>
        </div>`;
    });

    // Auto: Raccrocher
    html += '<div class="wiz-tl-connector"></div>';
    html += `<div class="wiz-tl-item auto">
        <div class="wiz-tl-icon" style="background:#f8514925;color:#f85149;"><i class="bi bi-telephone-x"></i></div>
        <div class="wiz-tl-text"><h6>Raccrocher</h6><p>Termine l'appel (ajoute automatiquement)</p></div>
        <span style="font-size:.6rem; color:var(--text-secondary); font-weight:700;">AUTO</span>
    </div>`;

    el.innerHTML = html;
}

let wizDragIdx = null;
function wizDragStart(e, idx) {
    wizDragIdx = idx;
    e.target.closest('.wiz-tl-item').classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}
function wizDragEnd(e) {
    wizDragIdx = null;
    document.querySelectorAll('.wiz-tl-item').forEach(el => el.classList.remove('dragging','drag-over'));
}
function wizDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const item = e.target.closest('.wiz-tl-item[data-wiz-idx]');
    if (item) item.classList.add('drag-over');
}
function wizDragLeave(e) {
    const item = e.target.closest('.wiz-tl-item[data-wiz-idx]');
    if (item) item.classList.remove('drag-over');
}
function wizDrop(e, toIdx) {
    e.preventDefault();
    if (wizDragIdx === null || wizDragIdx === toIdx) return;
    const moved = wizCustomSteps.splice(wizDragIdx, 1)[0];
    wizCustomSteps.splice(toIdx, 0, moved);
    wizDragIdx = null;
    wizRenderTimeline();
}

function wizStepDesc(s) {
    switch(s.type) {
        case 'playback': return s.sound || 'Fichier audio';
        case 'announcement': return s.sound || 'Message d\'annonce';
        case 'queue': return 'Distribution aux postes';
        case 'ring': return 'Sonnerie directe';
        case 'forward': return (s.dest_type==='external'?'Ext: ':'Poste ') + (s.destination||'?') + ' (' + (s.timeout||20) + 's)';
        case 'moh': return (s.moh_class || 'default') + ' (' + (s.duration || 10) + 's)';
        case 'voicemail': return 'Boite ' + (s.mailbox || '1000');
        case 'goto': return 'Vers ' + (s.target_context || 'default');
        case 'ivr': return 'Touches ' + Object.keys(s.options || {}).join(', ');
        case 'ai_agent': return 'Agent IA (' + (s.ai_voice || 'alloy') + ')';
        case 'time_condition': return (s.time_start||'09:00') + '-' + (s.time_end||'18:00') + ' ' + (s.days||'lun-ven');
        default: return '';
    }
}

function wizShowStep(n) {
    wizCurrentStep = n;
    document.getElementById('wizStep1').classList.toggle('active', n === 1);
    document.getElementById('wizStep2').classList.toggle('active', n === 2);
    document.getElementById('wizStep3').classList.toggle('active', n === 3);
    [1,2,3].forEach(i => {
        const dot = document.getElementById('wizDot'+i);
        dot.className = 'step-dot' + (i < n ? ' done' : (i === n ? ' active' : ''));
    });
    const showBack = WIZ_EDIT_MODE ? n > 2 : n > 1;
    document.getElementById('wizBtnBack').style.display = showBack ? '' : 'none';

    // For custom: step 2 = builder, step 3 = config. For template: skip step 2, go to step 3.
    const lastStep = 3;
    const applyLabel = WIZ_EDIT_MODE ? 'Enregistrer' : 'Creer le scenario';
    document.getElementById('wizBtnNext').innerHTML = n === lastStep
        ? `<i class="bi bi-check-lg me-1"></i> ${applyLabel}`
        : 'Suivant <i class="bi bi-arrow-right ms-1"></i>';
    const mainTitle = WIZ_EDIT_MODE ? 'Modifier le scenario' : 'Nouveau scenario';
    const titles = { 1: mainTitle, 2: 'Composez votre scenario', 3: 'Configuration' };
    document.getElementById('wizTitle').textContent = titles[n] || '';

    // On step 2, render the timeline and enable/disable next based on steps
    if (n === 2) {
        wizRenderTimeline();
        document.getElementById('wizBtnNext').disabled = wizCustomSteps.length === 0;
    }
}

function wizBack() {
    if (wizCurrentStep === 3) wizShowStep(wizIsCustom ? 2 : 1);
    else if (wizCurrentStep === 2 && !WIZ_EDIT_MODE) wizShowStep(1);
}

function wizNext() {
    if (wizCurrentStep === 1) {
        if (wizIsCustom) {
            // Go to feature picker
            wizShowStep(2);
        } else {
            // Template selected → go straight to config (step 3)
            wizBuildDynFields();
            wizShowStep(3);
            document.getElementById('wizName').focus();
        }
    } else if (wizCurrentStep === 2) {
        // Custom: features chosen → go to config
        wizBuildDynFields();
        wizShowStep(3);
        document.getElementById('wizName').focus();
    } else {
        wizApply();
    }
}

function wizBuildDynFields() {
    const dyn = document.getElementById('wizDynFields');
    dyn.innerHTML = '';

    // Determine which features are active
    let hasQueue = false, hasRing = false, hasVoicemail = false;
    if (wizIsCustom) {
        hasQueue = wizCustomSteps.some(s => s.type === 'queue');
        hasRing = wizCustomSteps.some(s => s.type === 'ring');
        hasVoicemail = wizCustomSteps.some(s => s.type === 'voicemail');
    } else if (wizSelectedTplId !== null) {
        const tpl = TEMPLATES.find(t => t.id === wizSelectedTplId);
        const steps = tpl ? (tpl.steps || []) : [];
        hasQueue = steps.some(s => s.type === 'queue');
        hasRing = steps.some(s => s.type === 'ring');
        hasVoicemail = steps.some(s => s.type === 'voicemail');
    }

    if (!hasQueue && !hasRing && !hasVoicemail) return;

    let html = '<hr style="border-color:var(--border); margin:.75rem 0;">';
    html += '<div style="font-weight:700; font-size:.72rem; letter-spacing:.5px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:.6rem;">Parametres</div>';

    if (hasQueue || hasRing) {
        html += `<div class="cfg-section">
            <label>${hasQueue ? "Membres de la file d'attente" : "Postes a faire sonner"}</label>
            <div class="wiz-check-grid" id="wizExtGrid">
                ${LINES.map(l => `
                    <div class="wiz-check-item" data-ext="${l.extension}" onclick="this.classList.toggle('checked')">
                        <div class="wiz-check-box"><i class="bi bi-check-lg"></i></div>
                        <span class="wiz-ext-num">${l.extension}</span>
                        <span class="wiz-ext-name">${l.display_name || ''}</span>
                    </div>
                `).join('')}
            </div>
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

function wizGetCheckedExts() {
    return [...document.querySelectorAll('.wiz-check-item.checked')].map(el => el.dataset.ext);
}

function wizApply() {
    const name = document.getElementById('wizName').value.trim();
    const trunk = document.getElementById('wizTrunk').value;
    if (!name) { alert('Veuillez saisir un nom pour le scenario.'); document.getElementById('wizName').focus(); return; }
    if (!trunk) { alert('Veuillez choisir un trunk entrant.'); document.getElementById('wizTrunk').focus(); return; }

    document.getElementById('cfgName').value = name;
    document.getElementById('cfgDesc').value = document.getElementById('wizDesc').value || '';
    document.getElementById('cfgTrunk').value = trunk;
    const trunkOpt = document.getElementById('wizTrunk').options[document.getElementById('wizTrunk').selectedIndex];
    if (trunkOpt && trunkOpt.dataset.context) {
        document.getElementById('cfgCtx').value = trunkOpt.dataset.context;
    }

    const checkedExts = wizGetCheckedExts();
    const mailboxEl = document.getElementById('wizMailbox');
    let finalSteps = [];

    if (wizIsCustom) {
        // Build: answer + user steps + hangup
        finalSteps.push({ type: 'answer', wait: 1 });
        wizCustomSteps.forEach(s => {
            const step = JSON.parse(JSON.stringify(s));
            if (step.type === 'queue' && checkedExts.length > 0) { /* queue_name set by server */ }
            if (step.type === 'ring' && checkedExts.length > 0) step.extensions = [...checkedExts];
            if (step.type === 'voicemail' && mailboxEl && mailboxEl.value) step.mailbox = mailboxEl.value;
            finalSteps.push(step);
        });
        finalSteps.push({ type: 'hangup' });
    } else if (wizSelectedTplId !== null) {
        const tpl = TEMPLATES.find(t => t.id === wizSelectedTplId);
        if (tpl && tpl.steps) {
            finalSteps = JSON.parse(JSON.stringify(tpl.steps));
            finalSteps.forEach(s => {
                if (s.type === 'voicemail' && mailboxEl && mailboxEl.value) s.mailbox = mailboxEl.value;
            });
        }
    }

    document.getElementById('stepsInput').value = JSON.stringify(finalSteps);
    document.getElementById('hidName').value = name;
    document.getElementById('hidDesc').value = document.getElementById('wizDesc').value || '';
    document.getElementById('hidTrunk').value = trunk;
    const trunkCtx = trunkOpt && trunkOpt.dataset.context ? trunkOpt.dataset.context : 'from-trunk';
    document.getElementById('hidCtx').value = trunkCtx;
    document.getElementById('hidPrio').value = document.getElementById('cfgPrio').value || '1';
    document.getElementById('hidEnabled').value = document.getElementById('cfgEnabled').checked ? '1' : '0';
    document.getElementById('hidQueueMembers').value = checkedExts.join(',');
    document.getElementById('flowForm').submit();
}

function wizOpenEdit() {
    const existing = @json(isset($callflow) ? ($callflow->steps ?? []) : []);
    wizIsCustom = true;
    wizCustomSteps = existing.filter(s => s.type !== 'answer' && s.type !== 'hangup')
        .map(s => JSON.parse(JSON.stringify(s)));
    document.getElementById('wizName').value = @json(isset($callflow) ? $callflow->name : '');
    document.getElementById('wizDesc').value = @json(isset($callflow) ? ($callflow->description ?? '') : '');
    document.getElementById('wizTrunk').value = String(@json(isset($callflow) ? $callflow->trunk_id : ''));
    openTplModal();
    wizShowStep(2);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!WIZ_EDIT_MODE) {
        openTplModal();
    } else {
        // Edit mode: always open fullscreen cartography directly
        requestAnimationFrame(() => { openFullscreen(); });
    }
});

// ════════════════════════════════════════
// AUTO-FILL context
// ════════════════════════════════════════
document.getElementById('cfgTrunk').addEventListener('change', function(){
    const o = this.options[this.selectedIndex];
    if (o && o.dataset.context) document.getElementById('cfgCtx').value = o.dataset.context;
});

// ════════════════════════════════════════
// SYNC fullscreen config ↔ main config
// ════════════════════════════════════════
document.querySelectorAll('.fs-cfg-sync').forEach(el => {
    el.addEventListener('input', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) target.value = this.value;
    });
    el.addEventListener('change', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) { target.value = this.value; target.dispatchEvent(new Event('change')); }
    });
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
    applyTransform();
    requestAnimationFrame(() => drawEdges());
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

    if (n.type === 'ivr') {
        // IVR: one port per option key only (no default out)
        const keys = Object.keys(n.data.options || {});
        if (!n.branches) n.branches = {};
        n.next = null; // IVR has no linear "next", only branches
        const total = keys.length;
        keys.forEach((key, i) => {
            const bp = document.createElement('div');
            bp.className = 'port port-branch';
            bp.dataset.owner = n.id;
            bp.dataset.dir = 'branch';
            bp.dataset.key = key;
            bp.style.left = total === 1 ? '50%' : Math.round(((i + 1) / (total + 1)) * 100) + '%';
            bp.addEventListener('mousedown', onPortDown);
            el.appendChild(bp);
            const lbl = document.createElement('div');
            lbl.className = 'port-branch-label';
            lbl.style.left = bp.style.left;
            lbl.textContent = key;
            el.appendChild(lbl);
        });
    } else if (n.type === 'time_condition') {
        // Time condition: 2 branches only — open (green) + closed (red)
        if (!n.branches) n.branches = {};
        n.next = null; // No linear "next", only branches
        const branchDefs = [
            { key: 'open', label: 'Ouvert', color: '#3fb950' },
            { key: 'closed', label: 'Ferme', color: '#f85149' }
        ];
        branchDefs.forEach((bd, i) => {
            const bp = document.createElement('div');
            bp.className = 'port port-branch';
            bp.style.background = bd.color;
            bp.dataset.owner = n.id;
            bp.dataset.dir = 'branch';
            bp.dataset.key = bd.key;
            bp.style.left = Math.round(((i + 1) / 3) * 100) + '%';
            bp.addEventListener('mousedown', onPortDown);
            el.appendChild(bp);
            const lbl = document.createElement('div');
            lbl.className = 'port-branch-label';
            lbl.style.left = bp.style.left;
            lbl.style.color = bd.color;
            lbl.textContent = bd.label;
            el.appendChild(lbl);
        });
    } else if (n.type !== 'hangup') {
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
        case 'forward':   return `${n.data.dest_type==='external'?'Externe':'Poste'}: ${n.data.destination||'<i>non defini</i>'} (${n.data.timeout||20}s)`;
        case 'ring':      return (n.data.extensions||[]).length ? `Postes: ${n.data.extensions.join(', ')}` : '<i>Aucun poste</i>';
        case 'queue':     return n.data.queue_name || '<i>Aucune file</i>';
        case 'voicemail': return `Boite ${n.data.mailbox||'1000'}`;
        case 'playback':  return n.data.sound||'hello-world';
        case 'announcement': return n.data.sound||'custom/welcome';
        case 'moh':       return `${n.data.moh_class||'default'} (${n.data.duration||10}s)`;
        case 'goto':      return `→ ${n.data.target_context||'default'}`;
        case 'ivr':       return `Menu: ${Object.keys(n.data.options||{}).join(', ')} (x${n.data.max_loops||3})`;
        case 'ai_agent':  return `<span style="color:#10b981;">OpenAI</span> ${(n.data.ai_voice||'alloy')}`;
        case 'time_condition': return `${n.data.time_start||'09:00'}-${n.data.time_end||'18:00'} ${n.data.days||'mon-fri'}`;
        case 'hangup':    return 'Fin';
        default: return '';
    }
}

// ════════════════════════════════════════
// SVG EDGES (bezier)
// ════════════════════════════════════════
// Port positions: always bottom-center (out) and top-center (in)
function startPortPos(){
    const el = canvasInner.querySelector(`[data-id="${startId}"]`);
    const w = el ? el.offsetWidth : 180;
    const h = el ? el.offsetHeight : 52;
    return { x: 400 + w / 2, y: 30 + h };
}
function nodePortPos(id, dir, branchKey){
    const n = nodes.find(x => x.id === id);
    if (!n) return {x:0,y:0};
    const el = canvasInner.querySelector(`[data-id="${id}"]`);
    const w = el ? el.offsetWidth : 220;
    const h = el ? el.offsetHeight : 70;
    if (branchKey !== undefined && (n.type === 'ivr' || n.type === 'time_condition')) {
        // Find the branch port position
        const port = el?.querySelector(`.port-branch[data-key="${branchKey}"]`);
        if (port) {
            const pctLeft = parseFloat(port.style.left) / 100;
            return { x: n.x + w * pctLeft, y: n.y + h };
        }
    }
    if (dir === 'out' && n.type === 'ivr') {
        // Default out port is at the rightmost position
        const port = el?.querySelector('.port-out');
        if (port) {
            const pctLeft = parseFloat(port.style.left) / 100;
            return { x: n.x + w * pctLeft, y: n.y + h };
        }
    }
    return {
        x: n.x + w / 2,
        y: dir === 'in' ? n.y : n.y + h
    };
}

function getOrCreateSvg(){
    let svg = canvasInner.querySelector('.edges-svg');
    if (!svg) {
        svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
        svg.classList.add('edges-svg');
        canvasInner.insertBefore(svg, canvasInner.firstChild);
    }
    return svg;
}

function drawEdges(){
    const svg = getOrCreateSvg();
    svg.innerHTML = '';

    const firstLinked = getStartNext();
    if (firstLinked !== null) {
        drawCurve(svg, startPortPos(), nodePortPos(firstLinked,'in'));
    }

    nodes.forEach(n => {
        if (n.next !== null && n.type !== 'ivr' && n.type !== 'time_condition') {
            const target = nodes.find(x => x.id === n.next);
            if (target) drawCurve(svg, nodePortPos(n.id,'out'), nodePortPos(target.id,'in'));
        }
        // IVR / time_condition branches
        if ((n.type === 'ivr' || n.type === 'time_condition') && n.branches) {
            Object.keys(n.branches).forEach(key => {
                const targetId = n.branches[key];
                const target = nodes.find(x => x.id === targetId);
                if (!target) return;
                let color = '#bc6ff1'; // IVR default
                if (n.type === 'time_condition') color = key === 'open' ? '#3fb950' : '#f85149';
                drawCurve(svg, nodePortPos(n.id, 'branch', key), nodePortPos(target.id, 'in'), false, color);
            });
        }
    });

    if (wiring) {
        drawCurve(svg, wiring.from, { x: wiring.mx, y: wiring.my }, true);
    }
}

function drawCurve(svg, a, b, temp, color){
    const dx = b.x - a.x, dy = b.y - a.y;
    const absDx = Math.abs(dx), absDy = Math.abs(dy);
    let d;
    if (absDy > absDx * 0.3) {
        // Mostly vertical: smooth S-curve
        const cp = Math.max(50, Math.min(absDy * 0.45, 180));
        d = `M${a.x},${a.y} C${a.x},${a.y + cp} ${b.x},${b.y - cp} ${b.x},${b.y}`;
    } else {
        // Mostly horizontal: use wider control points
        const cp = Math.max(60, absDx * 0.3);
        d = `M${a.x},${a.y} C${a.x},${a.y + cp} ${b.x},${b.y - cp} ${b.x},${b.y}`;
    }
    const path = document.createElementNS('http://www.w3.org/2000/svg','path');
    path.setAttribute('d', d);
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke', temp ? '#f0883e' : (color || '#29b6f6'));
    path.setAttribute('stroke-width', temp ? '3' : '2.5');
    path.setAttribute('stroke-opacity', temp ? '0.6' : '0.85');
    path.setAttribute('stroke-linecap', 'round');
    svg.appendChild(path);
}

// ════════════════════════════════════════
// WIRING (connect ports by dragging)
// ════════════════════════════════════════
let wiring = null;

function onPortDown(e){
    e.stopPropagation();
    e.preventDefault();
    const port = e.target.closest('.port, .port-branch');
    const ownerId = port.dataset.owner;
    const nId = ownerId === startId ? startId : parseInt(ownerId);
    const branchKey = port.dataset.key || null;
    const pos = (nId === startId) ? startPortPos() : nodePortPos(nId, branchKey ? 'branch' : 'out', branchKey);
    wiring = { fromId: nId, from: pos, mx: pos.x, my: pos.y, branchKey: branchKey };
    document.addEventListener('mousemove', onWireMove);
    document.addEventListener('mouseup', onWireUp);
}

function onWireMove(e){
    if (!wiring) return;
    const container = fsActive ? fsBody : canvasWrap;
    const rect = container.getBoundingClientRect();
    wiring.mx = (e.clientX - rect.left - camX) / zoom;
    wiring.my = (e.clientY - rect.top - camY) / zoom;
    drawEdges();
}

function onWireUp(e){
    document.removeEventListener('mousemove', onWireMove);
    document.removeEventListener('mouseup', onWireUp);
    if (!wiring) return;

    // Find nearest port-in to cursor (works in all modes)
    let targetId = null;
    const allPorts = canvasInner.querySelectorAll('.port-in');
    let minDist = 40;
    allPorts.forEach(p => {
        const rect = p.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const dist = Math.sqrt(Math.pow(e.clientX - cx, 2) + Math.pow(e.clientY - cy, 2));
        if (dist < minDist) {
            minDist = dist;
            targetId = parseInt(p.dataset.owner);
        }
    });

    if (targetId !== null && !isNaN(targetId) && targetId !== wiring.fromId) {
        if (wiring.fromId === startId) {
            setStartNext(targetId);
        } else if (wiring.branchKey) {
            const src = nodes.find(x => x.id === wiring.fromId);
            if (src) {
                if (!src.branches) src.branches = {};
                src.branches[wiring.branchKey] = targetId;
            }
        } else {
            const src = nodes.find(x => x.id === wiring.fromId);
            if (src) src.next = targetId;
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
    fsBody.addEventListener('wheel', fsWheel, { passive: false });
    fsBody.addEventListener('mousedown', fsMouseDown);
    document.getElementById('fsNodeCount').textContent =
        nodes.length + ' bloc' + (nodes.length !== 1 ? 's' : '');
    document.addEventListener('keydown', fsEscape);
    render();
    // Wait for layout to settle before centering
    setTimeout(() => centerOnNodes(), 100);
}

function centerOnNodes(){
    if (!nodes.length) { zoomReset(); return; }
    const container = fsActive ? fsBody : canvasWrap;
    const cw = container.clientWidth || 800, ch = container.clientHeight || 600;
    let minX = 400, minY = 30, maxX = 400 + 180, maxY = 30 + 52;
    nodes.forEach(n => {
        minX = Math.min(minX, n.x);
        minY = Math.min(minY, n.y);
        maxX = Math.max(maxX, n.x + 220);
        maxY = Math.max(maxY, n.y + 100);
    });
    const pad = 60;
    const nodesW = maxX - minX + pad * 2;
    const nodesH = maxY - minY + pad * 2;
    zoom = Math.min(1, Math.min(cw / nodesW, ch / nodesH));
    zoom = Math.max(0.35, zoom);
    const cx = (minX + maxX) / 2;
    const cy = (minY + maxY) / 2;
    camX = (cw / 2) - cx * zoom;
    camY = (ch / 2) - cy * zoom;
    applyTransform();
    drawEdges();
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
    const fsPanel = document.getElementById('fsPropPanel');
    const n = nodes.find(x => x.id === selectedId);
    if (!n) {
        const empty = `<div class="cfg-empty"><i class="bi bi-hand-index" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>Cliquez sur un bloc</div>`;
        panel.innerHTML = empty;
        if (fsPanel) fsPanel.innerHTML = empty;
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
        case 'forward':
            h += cfgF('Type', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'dest_type',this.value); renderProps();">
                <option value="extension" ${(n.data.dest_type||'extension')==='extension'?'selected':''}>Poste interne</option>
                <option value="external" ${n.data.dest_type==='external'?'selected':''}>Numero externe</option>
            </select>`);
            if ((n.data.dest_type||'extension') === 'extension') {
                let extOpts = '<option value="">— Choisir —</option>';
                LINES.forEach(l => { extOpts += `<option value="${l.extension}" ${n.data.destination===String(l.extension)?'selected':''}>${l.extension} — ${l.callerid_name||l.username||''}</option>`; });
                h += cfgF('Poste', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'destination',this.value)">${extOpts}</select>`);
            } else {
                h += cfgF('Numero', `<input type="tel" class="form-control form-control-sm" value="${n.data.destination||''}" placeholder="0612345678" onchange="setProp(${n.id},'destination',this.value)" style="font-family:'JetBrains Mono',monospace;">`);
            }
            h += cfgF('Timeout (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.timeout||20}" min="5" max="120" onchange="setProp(${n.id},'timeout',+this.value)">`);
            h += `<div style="margin-top:.5rem;font-size:.72rem;color:var(--text-secondary);"><i class="bi bi-info-circle me-1"></i>Si pas de reponse apres le timeout, le scenario continue au bloc suivant.</div>`;
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
            h += cfgF('Texte TTS', ttsField(n.id, n.data.tts_text, n.data.tts_voice));
            h += cfgF('Ou fichier audio', audioSelect(n.id, 'sound', n.data.sound||'hello-world', 'sound'));
            break;
        case 'announcement':
            h += cfgF('Texte TTS', ttsField(n.id, n.data.tts_text, n.data.tts_voice));
            h += cfgF('Ou fichier audio', audioSelect(n.id, 'sound', n.data.sound||'custom/welcome', 'sound'));
            break;
        case 'moh':
            {
                const selId = 'mohSel_'+n.id;
                const cur = n.data.moh_class||'default';
                h += cfgF('Classe', `<select class="form-select form-select-sm" id="${selId}" onchange="setProp(${n.id},'moh_class',this.value)"><option value="default">default</option></select>`);
                setTimeout(()=>{
                    if(!window._mohCache){
                        fetch('/api/moh').then(r=>r.json()).then(cls=>{window._mohCache=cls; _fillMohSel(selId,cls,cur);});
                    } else { _fillMohSel(selId,window._mohCache,cur); }
                },0);
            }
            h += cfgF('Duree (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.duration||10}" min="1" max="300" onchange="setProp(${n.id},'duration',+this.value)">`);
            break;
        case 'goto':
            h += cfgF('Contexte', `<input type="text" class="form-control form-control-sm" value="${n.data.target_context||'default'}" onchange="setProp(${n.id},'target_context',this.value)">`);
            break;
        case 'ivr':
            h += cfgF('Message vocal (TTS)', ttsField(n.id, n.data.tts_text, n.data.tts_voice));
            h += `<small style="color:var(--text-secondary);font-size:.6rem;display:block;margin-top:-0.3rem;margin-bottom:.4rem;">Synthese vocale Piper. Laissez vide pour utiliser un fichier audio.</small>`;
            h += cfgF('Ou fichier audio', audioSelect(n.id, 'sound', n.data.sound||'custom/menu', 'sound'));
            h += cfgF('Timeout (sec)', `<input type="number" class="form-control form-control-sm" value="${n.data.timeout||5}" min="1" max="30" onchange="setProp(${n.id},'timeout',+this.value)">`);
            h += cfgF('Repetitions', `<input type="number" class="form-control form-control-sm" value="${n.data.max_loops||3}" min="1" max="10" onchange="setProp(${n.id},'max_loops',+this.value)">`);
            h += `<small style="color:var(--text-secondary);font-size:.62rem;display:block;margin-top:-0.6rem;margin-bottom:.5rem;">Nombre de fois que le message est rejoue si pas de reponse</small>`;
            h += `<label style="font-weight:600;font-size:.7rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-top:.75rem;display:block;margin-bottom:.3rem;">Branches IVR</label>`;
            const opts = n.data.options || {};
            const branches = n.branches || {};
            Object.keys(opts).forEach(key => {
                // Find target block name
                const targetId = branches[key];
                const targetNode = targetId ? nodes.find(x => x.id === targetId) : null;
                const targetLabel = targetNode ? (TYPES[targetNode.type]?.label || targetNode.type) : '';
                const linkedTag = targetNode
                    ? `<span style="font-size:.6rem;background:#bc6ff120;color:#bc6ff1;border-radius:4px;padding:1px 5px;white-space:nowrap;">→ ${targetLabel}</span>`
                    : `<span style="font-size:.6rem;color:var(--text-secondary);font-style:italic;">non relie</span>`;
                h += `<div class="member-item" style="gap:.3rem;align-items:center;">
                    <span class="ext-badge" style="min-width:22px;text-align:center;background:#bc6ff1;color:#fff;">${key}</span>
                    ${linkedTag}
                    <input type="text" class="form-control form-control-sm" value="${opts[key]}" placeholder="destination"
                           style="flex:1;font-size:.75rem;" onchange="setIvrOpt(${n.id},'${key}',this.value)">
                    <button class="wiz-tl-remove" onclick="removeIvrOpt(${n.id},'${key}')" style="width:18px;height:18px;font-size:.5rem;"><i class="bi bi-x"></i></button>
                </div>`;
            });
            h += `<div style="margin-top:.4rem;display:flex;gap:.3rem;">
                <input type="text" class="form-control form-control-sm ivr-key-input" data-node="${n.id}" placeholder="Touche" style="width:55px;">
                <button class="btn-outline-custom" style="padding:2px 8px;font-size:.7rem;" onclick="addIvrOpt(${n.id}, this)">
                    <i class="bi bi-plus me-1"></i>Ajouter</button>
            </div>`;
            break;
        case 'time_condition':
            h += cfgF('Heure ouverture', `<input type="time" class="form-control form-control-sm" value="${n.data.time_start||'09:00'}" onchange="setProp(${n.id},'time_start',this.value)">`);
            h += cfgF('Heure fermeture', `<input type="time" class="form-control form-control-sm" value="${n.data.time_end||'18:00'}" onchange="setProp(${n.id},'time_end',this.value)">`);
            h += cfgF('Jours', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'days',this.value)">
                <option value="mon-fri" ${(n.data.days||'mon-fri')==='mon-fri'?'selected':''}>Lun — Ven</option>
                <option value="mon-sat" ${(n.data.days)==='mon-sat'?'selected':''}>Lun — Sam</option>
                <option value="mon-sun" ${(n.data.days)==='mon-sun'?'selected':''}>Lun — Dim (tous)</option>
                <option value="sat-sun" ${(n.data.days)==='sat-sun'?'selected':''}>Sam — Dim</option>
            </select>`);
            {
                h += `<hr style="border-color:var(--border);margin:.75rem 0;">`;
                h += `<label style="font-weight:600;font-size:.7rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.3rem;">Branches</label>`;
                const tcBranches = n.branches || {};
                const tcDefs = [{key:'open',label:'Ouvert',color:'#3fb950',icon:'bi-sun'},{key:'closed',label:'Ferme',color:'#f85149',icon:'bi-moon'}];
                tcDefs.forEach(bd => {
                    const tgt = tcBranches[bd.key] ? nodes.find(x => x.id === tcBranches[bd.key]) : null;
                    const tgtLabel = tgt ? (TYPES[tgt.type]?.label || tgt.type) : 'non relie';
                    h += `<div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem;padding:.3rem .5rem;border-radius:6px;border:1px solid ${bd.color}30;background:${bd.color}08;">
                        <i class="bi ${bd.icon}" style="color:${bd.color};font-size:.75rem;"></i>
                        <span style="font-weight:700;font-size:.75rem;color:${bd.color};">${bd.label}</span>
                        <span style="font-size:.7rem;color:var(--text-secondary);">→</span>
                        <span style="font-size:.72rem;font-weight:600;">${tgtLabel}</span>
                    </div>`;
                });
            }
            break;
        case 'ai_agent':
            h += cfgF('Instructions (prompt)', `<textarea class="form-control form-control-sm" rows="4" placeholder="Tu es un assistant telephonique..."
                style="font-size:.75rem;" onchange="setProp(${n.id},'ai_prompt',this.value)">${n.data.ai_prompt||''}</textarea>`);
            h += cfgF('Voix OpenAI', `<select class="form-select form-select-sm" onchange="setProp(${n.id},'ai_voice',this.value)">
                <option value="coral" ${(n.data.ai_voice||'coral')==='coral'?'selected':''}>Coral (femme)</option>
                <option value="alloy" ${n.data.ai_voice==='alloy'?'selected':''}>Alloy (neutre)</option>
                <option value="ash" ${n.data.ai_voice==='ash'?'selected':''}>Ash (homme)</option>
                <option value="ballad" ${n.data.ai_voice==='ballad'?'selected':''}>Ballad (doux)</option>
                <option value="echo" ${n.data.ai_voice==='echo'?'selected':''}>Echo (homme)</option>
                <option value="sage" ${n.data.ai_voice==='sage'?'selected':''}>Sage (calme)</option>
                <option value="shimmer" ${n.data.ai_voice==='shimmer'?'selected':''}>Shimmer (femme)</option>
                <option value="verse" ${n.data.ai_voice==='verse'?'selected':''}>Verse (expressif)</option>
            </select>`);
            h += cfgF('Dossier RAG', `<select class="form-select form-select-sm" id="ragFolder_${n.id}" onchange="setProp(${n.id},'ai_rag_folder',this.value)">
                <option value="" ${!n.data.ai_rag_folder?'selected':''}>General (tous les docs)</option>
            </select>`);
            // Load folders dynamically
            setTimeout(() => {
                fetch('/api/ai-context/folders').then(r=>r.json()).then(folders => {
                    const sel = document.getElementById('ragFolder_'+n.id);
                    if (!sel) return;
                    folders.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.name;
                        opt.textContent = f.name + ' (' + f.files + ' docs)';
                        if (n.data.ai_rag_folder === f.name) opt.selected = true;
                        sel.appendChild(opt);
                    });
                });
            }, 0);
            h += cfgF('Contexte supplementaire', `<textarea class="form-control form-control-sm" rows="3" placeholder="Infos additionnelles..."
                style="font-size:.72rem;" onchange="setProp(${n.id},'ai_context',this.value)">${n.data.ai_context||''}</textarea>`);
            h += `<small style="color:var(--text-secondary);font-size:.6rem;display:block;margin-top:-0.5rem;margin-bottom:.4rem;">Le dossier RAG + ce texte seront fournis a l'IA comme base de connaissances.</small>`;
            h += `<div style="margin-top:.5rem;padding:.5rem;background:#10b98110;border:1px solid #10b98130;border-radius:8px;">
                <div style="font-size:.7rem;font-weight:700;color:#10b981;margin-bottom:.3rem;"><i class="bi bi-shield-check me-1"></i>Cadrage automatique</div>
                <div style="font-size:.65rem;color:var(--text-secondary);line-height:1.4;">
                    L'IA refuse les sujets hors cadre (politique, personnel...) et ne revele pas qu'elle est une IA.
                    Les fichiers de contexte dans storage/app/ai-context/ sont charges automatiquement (RAG).
                </div>
            </div>`;
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
    if (fsPanel) fsPanel.innerHTML = h;
}

function cfgF(label, input){
    return `<div class="cfg-section"><label>${label}</label>${input}</div>`;
}

function colorBg(type){
    const m = {answer:'#58a6ff25',ring:'#29b6f625',queue:'#bc8cff25',voicemail:'#d2992225',playback:'#58a6ff25',moh:'#f0883e25',hangup:'#f8514925',announcement:'#d2992225',goto:'#bc8cff25',ivr:'#e8671525',forward:'#58a6ff25'};
    return m[type]||'#58a6ff25';
}
function colorFg(type){
    const m = {answer:'#58a6ff',ring:'#29b6f6',queue:'#bc8cff',voicemail:'#d29922',playback:'#58a6ff',moh:'#f0883e',hangup:'#f85149',announcement:'#d29922',goto:'#bc8cff',ivr:'#e86715',forward:'#58a6ff'};
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

// ════════════════════════════════════════
// TTS PREVIEW
// ════════════════════════════════════════
function ttsField(nodeId, text, voice) {
    return `<textarea class="form-control form-control-sm" rows="2" placeholder="Texte a synthetiser..."
        style="font-size:.75rem;" onchange="setProp(${nodeId},'tts_text',this.value)">${text||''}</textarea>
        <div style="display:flex;gap:4px;margin-top:4px;align-items:center;">
            <select class="form-select form-select-sm" style="font-size:.7rem;flex:1;" onchange="setProp(${nodeId},'tts_voice',this.value)">
                <option value="siwis" ${(voice||'siwis')==='siwis'?'selected':''}>Femme (Siwis)</option>
                <option value="upmc" ${voice==='upmc'?'selected':''}>Homme (UPMC)</option>
                <option value="mls" ${voice==='mls'?'selected':''}>Femme 2 (MLS)</option>
            </select>
            <button id="ttsBtn_${nodeId}" class="btn-tts-preview" onclick="ttsPreview(${nodeId})"><i class="bi bi-play-fill me-1"></i>Ecouter</button>
        </div>`;
}

let _ttsAudio = null;
function ttsPreview(nodeId) {
    const n = nodes.find(x => x.id === nodeId);
    if (!n || !n.data.tts_text || !n.data.tts_text.trim()) {
        alert('Saisissez un texte dans le champ TTS.');
        return;
    }
    // Stop any playing audio
    if (_ttsAudio) { _ttsAudio.pause(); _ttsAudio = null; }

    // Update button state
    const btn = document.getElementById('ttsBtn_' + nodeId);
    if (btn) { btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generation...'; btn.disabled = true; }

    fetch('{{ route("tts.preview") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'audio/wav'
        },
        body: JSON.stringify({ text: n.data.tts_text, voice: n.data.tts_voice || 'siwis' })
    })
    .then(r => {
        if (!r.ok) throw new Error('Erreur ' + r.status);
        return r.blob();
    })
    .then(blob => {
        const url = URL.createObjectURL(blob);
        _ttsAudio = new Audio(url);
        _ttsAudio.play();
        if (btn) {
            btn.innerHTML = '<i class="bi bi-stop-fill me-1"></i>Arreter';
            btn.disabled = false;
            btn.onclick = function() { _ttsAudio.pause(); _ttsAudio = null; btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Ecouter'; btn.onclick = function(){ ttsPreview(nodeId); }; };
        }
        _ttsAudio.onended = function() {
            if (btn) { btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Ecouter'; btn.onclick = function(){ ttsPreview(nodeId); }; }
        };
    })
    .catch(err => {
        console.error('TTS preview error:', err);
        if (btn) { btn.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Erreur'; btn.disabled = false; setTimeout(() => { btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Ecouter'; btn.onclick = function(){ ttsPreview(nodeId); }; }, 2000); }
    });
}

function setIvrOpt(id, key, val) {
    const n = nodes.find(x => x.id === id);
    if (!n) return;
    if (!n.data.options) n.data.options = {};
    n.data.options[key] = val;
    render();
}
function addIvrOpt(id, btn) {
    // Read all possible input locations (normal + fullscreen panels)
    const inputs = document.querySelectorAll('.ivr-key-input[data-node="' + id + '"]');
    let key = '';
    inputs.forEach(function(inp) { if (inp.value.trim()) key = inp.value.trim(); });
    if (!key) {
        // Fallback: try the input next to the button
        const input = btn.parentElement.querySelector('.ivr-key-input');
        key = input ? input.value.trim() : '';
    }
    if (!key) return;
    const n = nodes.find(x => x.id === id);
    if (!n) return;
    if (!n.data.options) n.data.options = {};
    if (n.data.options[key] !== undefined) return; // already exists
    n.data.options[key] = '';
    render();
    selectNode(id);
    renderProps();
}
function removeIvrOpt(id, key) {
    const n = nodes.find(x => x.id === id);
    if (!n || !n.data.options) return;
    delete n.data.options[key];
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
                inbound_context: document.getElementById('cfgCtx').value,
                record_calls: document.getElementById('cfgRecord').checked ? '1' : '0',
                record_optout: document.getElementById('cfgOptout').checked ? '1' : '0',
                record_optout_key: document.getElementById('cfgOptoutKey').value
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
    // Collect ALL nodes reachable from start, following next + branches
    const ordered = [];
    const visited = new Set();
    const queue = [];

    // Start with the first linked node
    const startNext = getStartNext();
    if (startNext !== null) queue.push(startNext);

    // BFS: follow next and branches
    while (queue.length > 0) {
        const currentId = queue.shift();
        if (visited.has(currentId)) continue;
        visited.add(currentId);

        const n = nodes.find(x => x.id === currentId);
        if (!n) continue;

        const step = Object.assign({ type: n.type, _nodeId: n.id }, n.data);

        // Include branch targets for IVR/time_condition
        if ((n.type === 'ivr' || n.type === 'time_condition') && n.branches) {
            step.branch_targets = {};
            Object.keys(n.branches).forEach(key => {
                step.branch_targets[key] = n.branches[key];
                // Add branch targets to processing queue
                if (!visited.has(n.branches[key])) queue.push(n.branches[key]);
            });
        }

        ordered.push(step);

        // Follow linear next
        if (n.next !== null && !visited.has(n.next)) {
            queue.push(n.next);
        }
    }

    // Also add orphan nodes (not connected but present on canvas)
    nodes.forEach(n => {
        if (!visited.has(n.id)) {
            const step = Object.assign({ type: n.type, _nodeId: n.id }, n.data);
            if ((n.type === 'ivr' || n.type === 'time_condition') && n.branches) {
                step.branch_targets = {};
                Object.keys(n.branches).forEach(key => { step.branch_targets[key] = n.branches[key]; });
            }
            ordered.push(step);
        }
    });

    return ordered;
}

// ════════════════════════════════════════
// SAVE
// ════════════════════════════════════════
document.getElementById('btnSave').addEventListener('click', () => {
    document.getElementById('stepsInput').value = JSON.stringify(buildSteps());
    // Serialize node positions
    const pos = {};
    nodes.forEach(n => { pos[n.id] = { x: Math.round(n.x), y: Math.round(n.y) }; });
    document.getElementById('hidPositions').value = JSON.stringify(pos);
    document.getElementById('hidName').value    = document.getElementById('cfgName').value;
    document.getElementById('hidDesc').value    = document.getElementById('cfgDesc').value;
    document.getElementById('hidTrunk').value   = document.getElementById('cfgTrunk').value;
    document.getElementById('hidCtx').value     = document.getElementById('cfgCtx').value;
    document.getElementById('hidPrio').value    = document.getElementById('cfgPrio').value;
    document.getElementById('hidEnabled').value = document.getElementById('cfgEnabled').checked ? '1' : '0';
    document.getElementById('hidRecord').value = document.getElementById('cfgRecord').checked ? '1' : '0';
    document.getElementById('hidOptout').value = document.getElementById('cfgOptout').checked ? '1' : '0';
    document.getElementById('hidOptoutKey').value = document.getElementById('cfgOptoutKey').value;
    // Caller ID filter: textarea lines → JSON array
    // Read from fullscreen selects (fsDid/fsCid) which are the visible ones
    var didVal = (document.getElementById('fsDid') || document.getElementById('cfgDidFilter')).value.trim();
    document.getElementById('hidDidFilter').value = didVal ? JSON.stringify([didVal]) : JSON.stringify([]);
    var cidVal = (document.getElementById('fsCid') || document.getElementById('cfgCallerIdFilter')).value.trim();
    document.getElementById('hidCallerIdFilter').value = cidVal ? JSON.stringify([cidVal]) : JSON.stringify([]);
    document.getElementById('flowForm').submit();
});

// ════════════════════════════════════════
// SAVE AS TEMPLATE
// ════════════════════════════════════════
document.getElementById('btnSaveTpl').addEventListener('click', () => {
    const steps = buildSteps();
    if (!steps.length) { alert('Ajoutez au moins un bloc avant de sauvegarder un template.'); return; }
    document.getElementById('tplModalName').value = '';
    document.getElementById('tplModalDesc').value = '';
    const modal = document.getElementById('tplModal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('tplModalName').focus(), 100);
});
function closeSaveTplModal() {
    document.getElementById('tplModal').style.display = 'none';
}
function submitSaveTplModal() {
    const name = document.getElementById('tplModalName').value.trim();
    if (!name) { document.getElementById('tplModalName').focus(); return; }
    const desc = document.getElementById('tplModalDesc').value.trim();
    document.getElementById('tplStepsInput').value = JSON.stringify(buildSteps());
    document.getElementById('tplNameInput').value = name;
    document.getElementById('tplDescInput').value = desc;
    document.getElementById('saveTplForm').submit();
}
// Close on backdrop click
document.getElementById('tplModal').addEventListener('click', function(e) {
    if (e.target === this) closeSaveTplModal();
});

// ════════════════════════════════════════
// BOOT
// ════════════════════════════════════════
(function initFromExisting(){
    const existing = @json(isset($callflow) ? ($callflow->steps ?? []) : (isset($templateSteps) && $templateSteps ? $templateSteps : []));
    if (!existing.length) return;
    const savedPos = @json(isset($callflow) ? ($callflow->positions ?? null) : null);
    loadSteps(existing, savedPos);
})();
render();
</script>
@endpush
