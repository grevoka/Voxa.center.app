/**
 * Voxa Center — Embeddable WebRTC Call Widget
 * Professional design with VU meter, animations, dark theme
 */
(function() {
    'use strict';

    var scriptTag = document.currentScript || document.querySelector('script[data-token]');
    var inlineDiv = document.querySelector('[data-mode="inline"][data-token]');
    var source = scriptTag || inlineDiv;
    if (!source) return;

    var TOKEN = source.getAttribute('data-token');
    var MODE = source.getAttribute('data-mode') || 'bubble';
    var COLOR = source.getAttribute('data-color') || '#58a6ff';
    var POSITION = source.getAttribute('data-position') || 'bottom-right';
    var LABEL = source.getAttribute('data-label') || 'Call us';
    var BASE_URL = scriptTag ? scriptTag.src.replace(/\/js\/voxa-widget\.js.*$/, '') : window.location.origin;
    if (!TOKEN) return;

    var _ua = null, _session = null, _state = 'idle', _config = null;
    var _timer = null, _seconds = 0, _vuCtx = null, _vuAnalyser = null, _vuInterval = null;

    var PHONE_SVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    var HANGUP_SVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.11 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function loadJsSIP(cb) {
        if (window.JsSIP) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/jssip/dist/jssip.min.js';
        s.onload = cb;
        s.onerror = function() { setStatus('Error loading...', '#f85149'); };
        document.head.appendChild(s);
    }

    function fetchConfig(cb) {
        fetch(BASE_URL + '/api/widget/' + TOKEN + '/config', { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'omit' })
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(cfg) { _config = cfg; cb(null, cfg); })
        .catch(function(err) { cb(err); });
    }

    // ── Build UI ──
    function buildUI() {
        var pos = POSITION === 'bottom-left' ? 'left' : 'right';
        var wrap = document.createElement('div');
        wrap.id = 'voxa-widget-root';

        if (MODE === 'inline') {
            var target = inlineDiv || document.getElementById('voxa-call');
            if (!target) return;
            target.appendChild(wrap);
        } else {
            document.body.appendChild(wrap);
        }

        wrap.innerHTML =
        // Floating button (bubble mode only)
        (MODE !== 'inline' ?
        '<div id="vx-fab" style="position:fixed;' + pos + ':20px;bottom:20px;z-index:99999;">' +
            '<button id="vx-fab-btn" style="width:56px;height:56px;border-radius:50%;border:none;background:' + COLOR + ';color:#fff;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,.35);transition:all .2s;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">' +
                PHONE_SVG +
                '<div id="vx-fab-pulse" style="position:absolute;inset:0;border-radius:50%;background:rgba(255,255,255,.15);transform:scale(0);"></div>' +
            '</button>' +
        '</div>' : '') +

        // Panel
        '<div id="vx-panel" style="' +
            (MODE !== 'inline' ? 'display:none;position:fixed;' + pos + ':20px;bottom:86px;z-index:99999;' : '') +
            'width:300px;background:#12141a;border:1px solid #2a2d35;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.6);overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">' +

            // Header
            '<div style="background:linear-gradient(135deg,' + COLOR + ',' + adjustColor(COLOR, -30) + ');padding:14px 16px;display:flex;align-items:center;justify-content:space-between;">' +
                '<div style="display:flex;align-items:center;gap:8px;">' +
                    '<div style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">' + PHONE_SVG + '</div>' +
                    '<div><div style="color:#fff;font-size:.82rem;font-weight:700;letter-spacing:-.3px;">Voxa Center</div>' +
                    '<div id="vx-header-status" style="color:rgba(255,255,255,.7);font-size:.6rem;font-weight:500;">Ready to call</div></div>' +
                '</div>' +
                (MODE !== 'inline' ? '<button id="vx-close" style="background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:1.1rem;padding:0;line-height:1;transition:color .15s;">&#10005;</button>' : '') +
            '</div>' +

            // Body
            '<div style="padding:20px 16px 16px;text-align:center;">' +

                // Avatar / animation
                '<div id="vx-avatar" style="width:80px;height:80px;border-radius:50%;margin:0 auto 12px;position:relative;display:flex;align-items:center;justify-content:center;">' +
                    '<div id="vx-ring1" style="position:absolute;inset:0;border-radius:50%;border:2px solid ' + COLOR + '30;transition:all .3s;"></div>' +
                    '<div id="vx-ring2" style="position:absolute;inset:-6px;border-radius:50%;border:2px solid ' + COLOR + '15;transition:all .3s;"></div>' +
                    '<div id="vx-ring3" style="position:absolute;inset:-12px;border-radius:50%;border:2px solid ' + COLOR + '08;transition:all .3s;"></div>' +
                    '<div style="width:56px;height:56px;border-radius:50%;background:#1c1f26;border:2px solid #2a2d35;display:flex;align-items:center;justify-content:center;position:relative;z-index:1;">' +
                        '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' + COLOR + '" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' +
                    '</div>' +
                '</div>' +

                // Status
                '<div id="vx-status" style="font-size:.78rem;color:#8b949e;margin-bottom:4px;font-weight:500;transition:color .2s;">Click to start a call</div>' +

                // Timer
                '<div id="vx-timer" style="display:none;font-family:JetBrains Mono,monospace;font-size:1.4rem;font-weight:700;color:#e6edf3;margin:4px 0;letter-spacing:1px;">00:00</div>' +

                // VU Meter
                '<div id="vx-vu" style="display:none;margin:12px auto 4px;width:200px;">' +
                    '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">' +
                        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#8b949e" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>' +
                        '<div style="flex:1;height:4px;background:#1c1f26;border-radius:2px;overflow:hidden;"><div id="vx-vu-local" style="height:100%;width:0%;background:' + COLOR + ';border-radius:2px;transition:width 50ms;"></div></div>' +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:6px;">' +
                        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#8b949e" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                        '<div style="flex:1;height:4px;background:#1c1f26;border-radius:2px;overflow:hidden;"><div id="vx-vu-remote" style="height:100%;width:0%;background:#3fb950;border-radius:2px;transition:width 50ms;"></div></div>' +
                    '</div>' +
                '</div>' +

                // Call button
                '<div style="margin-top:16px;">' +
                    '<button id="vx-call-btn" style="width:56px;height:56px;border-radius:50%;border:none;background:#3fb950;color:#fff;cursor:pointer;box-shadow:0 4px 16px rgba(63,185,80,.3);transition:all .2s;display:inline-flex;align-items:center;justify-content:center;">' +
                        PHONE_SVG +
                    '</button>' +
                '</div>' +

                // Powered by
                '<div style="margin-top:12px;font-size:.55rem;color:#484f58;letter-spacing:.5px;">POWERED BY VOXA CENTER</div>' +
            '</div>' +

            '<audio id="vx-audio" autoplay></audio>' +
        '</div>';

        // Events
        var fab = document.getElementById('vx-fab-btn');
        var close = document.getElementById('vx-close');
        var callBtn = document.getElementById('vx-call-btn');
        if (fab) fab.onclick = togglePanel;
        if (close) close.onclick = togglePanel;
        if (callBtn) callBtn.onclick = window._voxaCall;

        // Show panel in inline mode
        if (MODE === 'inline') {
            var panel = document.getElementById('vx-panel');
            if (panel) panel.style.display = '';
        }
    }

    function adjustColor(hex, amount) {
        hex = hex.replace('#', '');
        var r = Math.max(0, Math.min(255, parseInt(hex.substr(0,2),16) + amount));
        var g = Math.max(0, Math.min(255, parseInt(hex.substr(2,2),16) + amount));
        var b = Math.max(0, Math.min(255, parseInt(hex.substr(4,2),16) + amount));
        return '#' + [r,g,b].map(function(c){return c.toString(16).padStart(2,'0');}).join('');
    }

    function togglePanel() {
        var panel = document.getElementById('vx-panel');
        if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
    }
    window._voxaToggle = togglePanel;

    // ── Status updates ──
    function setStatus(text, color) {
        var el = document.getElementById('vx-status');
        var header = document.getElementById('vx-header-status');
        if (el) { el.textContent = text; if (color) el.style.color = color; else el.style.color = '#8b949e'; }
        if (header) header.textContent = text;
    }

    function setCallState(state) {
        _state = state;
        var btn = document.getElementById('vx-call-btn');
        var timer = document.getElementById('vx-timer');
        var vu = document.getElementById('vx-vu');
        var fab = document.getElementById('vx-fab-btn');
        var rings = [document.getElementById('vx-ring1'), document.getElementById('vx-ring2'), document.getElementById('vx-ring3')];

        switch (state) {
            case 'idle':
                if (btn) { btn.style.background = '#3fb950'; btn.innerHTML = PHONE_SVG; btn.onclick = window._voxaCall; btn.style.boxShadow = '0 4px 16px rgba(63,185,80,.3)'; }
                if (timer) timer.style.display = 'none';
                if (vu) vu.style.display = 'none';
                if (fab) fab.style.background = COLOR;
                rings.forEach(function(r) { if (r) r.style.borderColor = COLOR + '20'; });
                stopVU();
                setStatus('Click to start a call');
                break;
            case 'connecting':
                if (btn) { btn.style.background = '#d29922'; btn.style.boxShadow = '0 4px 16px rgba(210,153,34,.3)'; }
                if (fab) fab.style.background = '#d29922';
                setStatus('Connecting...', '#d29922');
                pulseRings(rings, '#d29922');
                break;
            case 'ringing':
                setStatus('Ringing...', '#d29922');
                pulseRings(rings, '#d29922');
                break;
            case 'incall':
                if (btn) { btn.style.background = '#f85149'; btn.innerHTML = HANGUP_SVG; btn.onclick = window._voxaHangup; btn.style.boxShadow = '0 4px 16px rgba(248,81,73,.3)'; }
                if (timer) { timer.style.display = ''; timer.textContent = '00:00'; }
                if (vu) vu.style.display = '';
                if (fab) fab.style.background = '#f85149';
                rings.forEach(function(r) { if (r) r.style.borderColor = '#3fb95040'; });
                setStatus('In call', '#3fb950');
                _seconds = 0;
                _timer = setInterval(function() {
                    _seconds++;
                    var m = Math.floor(_seconds / 60), s = _seconds % 60;
                    if (timer) timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                }, 1000);
                break;
            case 'ended':
                if (_timer) { clearInterval(_timer); _timer = null; }
                if (btn) { btn.style.background = '#3fb950'; btn.innerHTML = PHONE_SVG; btn.onclick = window._voxaCall; btn.style.boxShadow = '0 4px 16px rgba(63,185,80,.3)'; }
                if (timer) timer.style.display = 'none';
                if (vu) vu.style.display = 'none';
                if (fab) fab.style.background = COLOR;
                rings.forEach(function(r) { if (r) r.style.borderColor = COLOR + '20'; });
                stopVU();
                setStatus('Call ended');
                setTimeout(function() { if (_state === 'ended') setCallState('idle'); }, 3000);
                break;
        }
    }

    function pulseRings(rings, color) {
        var step = 0;
        var iv = setInterval(function() {
            step++;
            rings.forEach(function(r, i) {
                if (!r) return;
                r.style.borderColor = (step + i) % 2 === 0 ? color + '40' : color + '10';
            });
            if (_state !== 'connecting' && _state !== 'ringing') clearInterval(iv);
        }, 500);
    }

    // ── VU Meter ──
    function startVU(stream, barId) {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var analyser = ctx.createAnalyser();
            analyser.fftSize = 256;
            var source = ctx.createMediaStreamSource(stream);
            source.connect(analyser);
            var data = new Uint8Array(analyser.frequencyBinCount);
            var bar = document.getElementById(barId);
            var iv = setInterval(function() {
                analyser.getByteFrequencyData(data);
                var sum = 0;
                for (var i = 0; i < data.length; i++) sum += data[i];
                var avg = sum / data.length;
                var pct = Math.min(100, avg * 1.5);
                if (bar) bar.style.width = pct + '%';
            }, 50);
            return { iv: iv, ctx: ctx };
        } catch(e) { return null; }
    }

    var _vuHandles = [];
    function stopVU() {
        _vuHandles.forEach(function(h) { if (h) { clearInterval(h.iv); try { h.ctx.close(); } catch(e){} } });
        _vuHandles = [];
        var vl = document.getElementById('vx-vu-local'); if (vl) vl.style.width = '0%';
        var vr = document.getElementById('vx-vu-remote'); if (vr) vr.style.width = '0%';
    }

    // ── Call ──
    window._voxaCall = function() {
        if (_state !== 'idle') return;

        // Pre-warm audio element with user gesture context
        var audio = document.getElementById('vx-audio');
        if (audio) {
            audio.play().then(function() { audio.pause(); }).catch(function(){});
        }

        setCallState('connecting');

        fetchConfig(function(err, cfg) {
            if (err) { setStatus('Error: ' + err.message, '#f85149'); setTimeout(function() { setCallState('idle'); }, 3000); return; }
            loadJsSIP(function() {
                var socket = new JsSIP.WebSocketInterface(cfg.ws_uri);
                _ua = new JsSIP.UA({ sockets: [socket], uri: 'sip:' + cfg.endpoint + '@' + cfg.realm, password: cfg.password, display_name: 'Web Visitor', register: true, session_timers: false });

                _ua.on('registered', function() {
                    setCallState('ringing');

                    function attachRemoteAudio(stream) {
                        console.log('Voxa Widget: attaching remote audio stream');
                        var audio = document.getElementById('vx-audio');
                        if (audio) {
                            audio.srcObject = stream;
                            audio.play().then(function() {
                                console.log('Voxa Widget: audio playing!');
                            }).catch(function(err) {
                                console.warn('Voxa Widget: audio play blocked:', err.message);
                                setTimeout(function() { audio.play().catch(function(){}); }, 500);
                            });
                        }
                        var h = startVU(stream, 'vx-vu-remote');
                        if (h) _vuHandles.push(h);
                    }

                    var callOptions = {
                        mediaConstraints: { audio: true, video: false },
                        pcConfig: {
                            iceServers: cfg.ice_servers || [{ urls: 'stun:stun.l.google.com:19302' }],
                            iceTransportPolicy: 'relay',
                        },
                        eventHandlers: {
                            peerconnection: function(data) {
                                var pc = data.peerconnection;
                                console.log('Voxa Widget: peerconnection event fired');

                                pc.ontrack = function(ev) {
                                    console.log('Voxa Widget: ontrack fired');
                                    attachRemoteAudio(ev.streams[0] || new MediaStream([ev.track]));
                                };
                                pc.onaddstream = function(ev) {
                                    console.log('Voxa Widget: onaddstream fired');
                                    attachRemoteAudio(ev.stream);
                                };
                                pc.oniceconnectionstatechange = function() {
                                    console.log('Voxa Widget: ICE state:', pc.iceConnectionState);
                                };
                                pc.onconnectionstatechange = function() {
                                    console.log('Voxa Widget: Connection state:', pc.connectionState);
                                };
                            },
                            confirmed: function() {
                                setCallState('incall');
                                // Fallback: try getReceivers after confirmed
                                setTimeout(function() {
                                    if (_session && _session.connection) {
                                        var receivers = _session.connection.getReceivers();
                                        console.log('Voxa Widget: getReceivers count:', receivers.length);
                                        if (receivers.length > 0 && receivers[0].track) {
                                            var stream = new MediaStream([receivers[0].track]);
                                            attachRemoteAudio(stream);
                                        }
                                    }
                                    // Also force audio play
                                    var audio = document.getElementById('vx-audio');
                                    if (audio && audio.srcObject) {
                                        audio.play().catch(function(){});
                                    }
                                }, 300);
                            },
                            ended: function() { cleanupCall(); },
                            failed: function(e) { console.warn('Voxa Widget: call failed', e.cause); cleanupCall(); },
                        }
                    };

                    _session = _ua.call('sip:' + cfg.dial_target + '@' + cfg.realm, callOptions);

                    // Local VU (from mic)
                    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(localStream) {
                        var h = startVU(localStream, 'vx-vu-local');
                        if (h) _vuHandles.push(h);
                    }).catch(function(){});
                });

                _ua.on('registrationFailed', function(e) { setStatus('Connection failed', '#f85149'); setTimeout(function() { setCallState('idle'); }, 3000); });
                _ua.start();
            });
        });
    };

    window._voxaHangup = function() {
        if (_session) try { _session.terminate(); } catch(e) {}
        cleanupCall();
    };

    function cleanupCall() {
        setCallState('ended');
        _session = null;
        if (_ua) { try { _ua.unregister(); _ua.stop(); } catch(e) {} _ua = null; }
    }

    window.addEventListener('beforeunload', function() {
        if (_session) try { _session.terminate(); } catch(e) {}
        if (_ua) try { _ua.unregister(); _ua.stop(); } catch(e) {}
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', buildUI);
    else buildUI();
})();
