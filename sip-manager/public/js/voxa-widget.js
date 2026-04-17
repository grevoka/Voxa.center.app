/**
 * Voxa Center — Embeddable WebRTC Call Widget
 *
 * Usage (bubble mode):
 *   <script src="https://voxa.example.com/js/voxa-widget.js"
 *           data-token="YOUR_TOKEN"
 *           data-mode="bubble"
 *           data-color="#58a6ff"
 *           data-position="bottom-right"></script>
 *
 * Usage (inline mode):
 *   <div id="voxa-call" data-token="YOUR_TOKEN" data-mode="inline" data-label="Call us"></div>
 *   <script src="https://voxa.example.com/js/voxa-widget.js"></script>
 */
(function() {
    'use strict';

    // ── Config from script tag or inline div ──
    var scriptTag = document.currentScript || document.querySelector('script[data-token]');
    var inlineDiv = document.querySelector('[data-mode="inline"][data-token]');
    var source = scriptTag || inlineDiv;

    if (!source) { console.error('Voxa Widget: no data-token found'); return; }

    var TOKEN = source.getAttribute('data-token');
    var MODE = source.getAttribute('data-mode') || 'bubble';
    var COLOR = source.getAttribute('data-color') || '#58a6ff';
    var POSITION = source.getAttribute('data-position') || 'bottom-right';
    var LABEL = source.getAttribute('data-label') || 'Call';
    var BASE_URL = scriptTag ? scriptTag.src.replace(/\/js\/voxa-widget\.js.*$/, '') : window.location.origin;

    if (!TOKEN) { console.error('Voxa Widget: data-token is required'); return; }

    // ── State ──
    var _ua = null, _session = null, _state = 'idle'; // idle, connecting, ringing, incall, ended
    var _config = null, _timer = null, _seconds = 0;

    // ── Load JsSIP ──
    function loadJsSIP(cb) {
        if (window.JsSIP) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/jssip@3.10.1/dist/jssip.min.js';
        s.onload = cb;
        s.onerror = function() { console.error('Voxa Widget: failed to load JsSIP'); };
        document.head.appendChild(s);
    }

    // ── Fetch config from Voxa API ──
    function fetchConfig(cb) {
        fetch(BASE_URL + '/api/widget/' + TOKEN + '/config', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'omit'
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(cfg) { _config = cfg; cb(null, cfg); })
        .catch(function(err) { cb(err); });
    }

    // ── Build UI ──
    function buildUI() {
        if (MODE === 'inline') {
            buildInline();
        } else {
            buildBubble();
        }
    }

    function buildBubble() {
        var wrap = document.createElement('div');
        wrap.id = 'voxa-widget-wrap';
        wrap.innerHTML = '<div id="voxa-bubble" style="' +
            'position:fixed;' + (POSITION === 'bottom-left' ? 'left' : 'right') + ':20px;bottom:20px;z-index:99999;">' +
            '<button id="voxa-btn" style="width:56px;height:56px;border-radius:50%;border:none;background:' + COLOR + ';color:#fff;font-size:1.4rem;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.3);transition:transform .15s;display:flex;align-items:center;justify-content:center;" onclick="window._voxaToggle()">' +
                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
            '</button>' +
            '<div id="voxa-panel" style="display:none;position:absolute;' + (POSITION === 'bottom-left' ? 'left' : 'right') + ':0;bottom:70px;width:280px;background:#1c1f26;border:1px solid #30363d;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.5);overflow:hidden;">' +
                '<div style="padding:.75rem 1rem;background:' + COLOR + ';display:flex;align-items:center;justify-content:space-between;">' +
                    '<span style="color:#fff;font-size:.82rem;font-weight:700;">Voxa Center</span>' +
                    '<button onclick="window._voxaToggle()" style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1rem;">✕</button>' +
                '</div>' +
                '<div id="voxa-body" style="padding:1.25rem;text-align:center;">' +
                    '<div id="voxa-status" style="font-size:.78rem;color:#8b949e;margin-bottom:1rem;">Ready</div>' +
                    '<button id="voxa-call-btn" onclick="window._voxaCall()" style="width:64px;height:64px;border-radius:50%;border:none;background:#3fb950;color:#fff;font-size:1.5rem;cursor:pointer;box-shadow:0 4px 12px rgba(63,185,80,.3);transition:transform .15s;">' +
                        '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
                    '</button>' +
                    '<div id="voxa-timer" style="display:none;font-family:monospace;font-size:1.2rem;font-weight:700;color:#e6edf3;margin-top:.75rem;">00:00</div>' +
                '</div>' +
                '<audio id="voxa-audio" autoplay></audio>' +
            '</div>' +
        '</div>';
        document.body.appendChild(wrap);
    }

    function buildInline() {
        var target = inlineDiv || document.getElementById('voxa-call');
        if (!target) return;
        target.innerHTML =
            '<button id="voxa-call-btn" onclick="window._voxaCall()" style="padding:10px 24px;border-radius:8px;border:none;background:' + COLOR + ';color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
                LABEL +
            '</button>' +
            '<div id="voxa-status" style="display:none;font-size:.75rem;color:#8b949e;margin-top:4px;"></div>' +
            '<div id="voxa-timer" style="display:none;font-family:monospace;font-size:1rem;font-weight:700;margin-top:4px;">00:00</div>' +
            '<audio id="voxa-audio" autoplay></audio>';
    }

    // ── UI Updates ──
    function setStatus(text) {
        var el = document.getElementById('voxa-status');
        if (el) { el.textContent = text; el.style.display = ''; }
    }

    function setCallState(state) {
        _state = state;
        var btn = document.getElementById('voxa-call-btn');
        var timer = document.getElementById('voxa-timer');
        var bubble = document.getElementById('voxa-btn');

        switch (state) {
            case 'idle':
                if (btn) { btn.style.background = '#3fb950'; btn.onclick = window._voxaCall; }
                if (timer) timer.style.display = 'none';
                if (bubble) bubble.style.background = COLOR;
                setStatus('Ready');
                break;
            case 'connecting':
                if (btn) btn.style.background = '#d29922';
                if (bubble) bubble.style.background = '#d29922';
                setStatus('Connecting...');
                break;
            case 'ringing':
                if (btn) btn.style.background = '#d29922';
                setStatus('Ringing...');
                break;
            case 'incall':
                if (btn) {
                    btn.style.background = '#f85149';
                    btn.onclick = window._voxaHangup;
                    btn.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M16.5 13.5c.83.73 1.75 1.3 2.75 1.72A2 2 0 0 0 21.25 14l1-2.66a2 2 0 0 0-1-2.5A16.92 16.92 0 0 0 12 6c-3.18 0-6.14.87-8.68 2.38a2 2 0 0 0-.93 2.46L3.4 13.5a2 2 0 0 0 2 1.26c1-.35 1.92-.87 2.77-1.53"/></svg>';
                }
                if (timer) timer.style.display = '';
                if (bubble) bubble.style.background = '#f85149';
                setStatus('In call');
                _seconds = 0;
                _timer = setInterval(function() {
                    _seconds++;
                    var m = Math.floor(_seconds / 60), s = _seconds % 60;
                    if (timer) timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                }, 1000);
                break;
            case 'ended':
                if (_timer) { clearInterval(_timer); _timer = null; }
                if (btn) {
                    btn.style.background = '#3fb950';
                    btn.onclick = window._voxaCall;
                    btn.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
                }
                if (timer) timer.style.display = 'none';
                if (bubble) bubble.style.background = COLOR;
                setStatus('Call ended');
                setTimeout(function() { if (_state === 'ended') setCallState('idle'); }, 3000);
                break;
        }
    }

    // ── Toggle panel (bubble mode) ──
    window._voxaToggle = function() {
        var panel = document.getElementById('voxa-panel');
        if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
    };

    // ── Start call ──
    window._voxaCall = function() {
        if (_state !== 'idle') return;
        setCallState('connecting');

        fetchConfig(function(err, cfg) {
            if (err) {
                setStatus('Error: ' + err.message);
                setTimeout(function() { setCallState('idle'); }, 3000);
                return;
            }

            loadJsSIP(function() {
                // Register
                var socket = new JsSIP.WebSocketInterface(cfg.ws_uri);
                _ua = new JsSIP.UA({
                    sockets: [socket],
                    uri: 'sip:' + cfg.endpoint + '@' + cfg.realm,
                    password: cfg.password,
                    display_name: 'Web Visitor',
                    register: true,
                    session_timers: false,
                });

                _ua.on('registered', function() {
                    setCallState('ringing');
                    // Make the call
                    var target = 'sip:' + cfg.dial_target + '@' + cfg.realm;
                    _session = _ua.call(target, {
                        mediaConstraints: { audio: true, video: false },
                        pcConfig: { iceServers: cfg.ice_servers || [{ urls: 'stun:stun.l.google.com:19302' }] },
                    });

                    _session.on('confirmed', function() {
                        setCallState('incall');
                    });

                    _session.on('ended', function() {
                        cleanupCall();
                    });

                    _session.on('failed', function(e) {
                        console.warn('Voxa Widget: call failed', e.cause);
                        cleanupCall();
                    });

                    // Attach remote audio
                    _session.on('peerconnection', function(data) {
                        data.peerconnection.ontrack = function(ev) {
                            var audio = document.getElementById('voxa-audio');
                            if (audio) {
                                audio.srcObject = ev.streams[0] || new MediaStream([ev.track]);
                                audio.play().catch(function() {});
                            }
                        };
                    });
                });

                _ua.on('registrationFailed', function(e) {
                    console.error('Voxa Widget: registration failed', e.cause);
                    setStatus('Connection failed');
                    setTimeout(function() { setCallState('idle'); }, 3000);
                });

                _ua.start();
            });
        });
    };

    // ── Hangup ──
    window._voxaHangup = function() {
        if (_session) {
            try { _session.terminate(); } catch(e) {}
        }
        cleanupCall();
    };

    function cleanupCall() {
        setCallState('ended');
        _session = null;
        if (_ua) {
            try { _ua.unregister(); _ua.stop(); } catch(e) {}
            _ua = null;
        }
    }

    // ── Cleanup on page unload ──
    window.addEventListener('beforeunload', function() {
        if (_session) try { _session.terminate(); } catch(e) {}
        if (_ua) try { _ua.unregister(); _ua.stop(); } catch(e) {}
    });

    // ── Init ──
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildUI);
    } else {
        buildUI();
    }
})();
