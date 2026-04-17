{{-- Softphone WebRTC --}}
<div id="softphone" style="padding:0.75rem;margin:0.5rem 0.75rem;background:var(--surface-3);border:1px solid var(--border);border-radius:12px;">
    {{-- Status --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="d-flex align-items-center gap-2">
            <div id="phoneStatus" style="width:8px;height:8px;border-radius:50%;background:var(--text-secondary);"></div>
            <span id="phoneStatusText" style="font-size:0.7rem;color:var(--text-secondary);">{{ __("ui.phone_disconnected") }}</span>
        </div>
        <button id="phoneSettingsBtn" onclick="phoneToggleSettings()" class="btn-icon" style="width:24px;height:24px;font-size:0.65rem;" title="Audio settings">
            <i class="bi bi-gear"></i>
        </button>
        <button id="phoneToggle" onclick="phoneToggleConnect()" class="btn-icon" style="width:24px;height:24px;font-size:0.65rem;" title="Connecter">
            <i class="bi bi-power"></i>
        </button>
    </div>

    {{-- Audio settings panel --}}
    <div id="phoneSettingsPanel" style="display:none;padding:0.5rem;margin-bottom:0.4rem;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;">
        <div style="font-size:0.62rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:0.4rem;">
            <i class="bi bi-mic me-1"></i> {{ __('ui.audio_input') ?? 'Microphone' }}
        </div>
        <select id="phoneAudioInput" class="form-select form-select-sm" style="font-size:0.72rem;margin-bottom:0.5rem;" onchange="phoneSaveAudioSettings()">
            <option value="">{{ __('ui.default_callerid') ?? 'Default' }}</option>
        </select>
        <div style="font-size:0.62rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin-bottom:0.4rem;">
            <i class="bi bi-volume-up me-1"></i> {{ __('ui.audio_output') ?? 'Speaker' }}
        </div>
        <select id="phoneAudioOutput" class="form-select form-select-sm" style="font-size:0.72rem;" onchange="phoneSaveAudioSettings()">
            <option value="">{{ __('ui.default_callerid') ?? 'Default' }}</option>
        </select>
    </div>

    {{-- Call info --}}
    <div id="phoneCallInfo" style="display:none;text-align:center;margin-bottom:0.5rem;">
        <div id="phoneCallNumber" style="font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:700;"></div>
        <div id="phoneCallTimer" style="font-size:0.72rem;color:var(--text-secondary);font-family:'JetBrains Mono',monospace;">00:00</div>
    </div>

    {{-- Dialpad --}}
    <div id="phoneDialpad">
        <input type="text" id="phoneInput" placeholder="Numero..." style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:0.4rem 0.6rem;color:var(--text-primary);font-family:'JetBrains Mono',monospace;font-size:0.9rem;text-align:center;margin-bottom:0.4rem;outline:none;" autocomplete="off">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:3px;">
            <button class="dp-btn" onclick="phoneDial('1')">1</button>
            <button class="dp-btn" onclick="phoneDial('2')"><span>2</span><small>ABC</small></button>
            <button class="dp-btn" onclick="phoneDial('3')"><span>3</span><small>DEF</small></button>
            <button class="dp-btn" onclick="phoneDial('4')"><span>4</span><small>GHI</small></button>
            <button class="dp-btn" onclick="phoneDial('5')"><span>5</span><small>JKL</small></button>
            <button class="dp-btn" onclick="phoneDial('6')"><span>6</span><small>MNO</small></button>
            <button class="dp-btn" onclick="phoneDial('7')"><span>7</span><small>PQRS</small></button>
            <button class="dp-btn" onclick="phoneDial('8')"><span>8</span><small>TUV</small></button>
            <button class="dp-btn" onclick="phoneDial('9')"><span>9</span><small>WXYZ</small></button>
            <button class="dp-btn" onclick="phoneDial('*')">*</button>
            <button class="dp-btn" onclick="phoneDial('0')">0</button>
            <button class="dp-btn" onclick="phoneDial('#')">#</button>
        </div>
        {{-- Missed calls --}}
        <div id="phoneMissed" style="display:none;margin-top:0.4rem;margin-bottom:0.3rem;">
            <div style="font-size:0.62rem;color:var(--text-secondary);margin-bottom:3px;text-transform:uppercase;letter-spacing:0.5px;">{{ __("ui.phone_missed") }}</div>
            <div id="phoneMissedList" style="max-height:80px;overflow-y:auto;"></div>
        </div>
        {{-- Caller ID selector (toggle buttons) --}}
        <div id="phoneCidSelector" style="display:none;margin-top:0.4rem;margin-bottom:0.3rem;">
            <div style="font-size:0.62rem;color:var(--text-secondary);margin-bottom:3px;text-transform:uppercase;letter-spacing:0.5px;">{{ __("ui.phone_outgoing_number") }}</div>
            <div id="phoneCidBtns" style="display:flex;flex-wrap:wrap;gap:3px;"></div>
            <input type="hidden" id="phoneCidSelect" value="">
        </div>
        <div class="d-flex gap-2 mt-2">
            <button id="phoneCallBtn" onclick="phoneCall()" style="flex:1;background:var(--success);color:#fff;border:none;border-radius:8px;padding:0.45rem;font-size:0.82rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-telephone-fill me-1"></i>Appeler
            </button>
            <button id="phoneHangupBtn" onclick="phoneHangup()" style="flex:1;background:var(--danger);color:#fff;border:none;border-radius:8px;padding:0.45rem;font-size:0.82rem;font-weight:600;cursor:pointer;display:none;">
                <i class="bi bi-telephone-x-fill me-1"></i>Raccrocher
            </button>
        </div>
    </div>

    {{-- Incoming call --}}
    <div id="phoneIncoming" style="display:none;text-align:center;">
        <div style="font-size:0.78rem;color:var(--text-secondary);margin-bottom:0.3rem;">{{ __("ui.phone_incoming") }}</div>
        <div id="phoneIncomingNumber" style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:700;margin-bottom:0.5rem;"></div>
        <div class="d-flex gap-2">
            <button onclick="phoneAnswer()" style="flex:1;background:var(--success);color:#fff;border:none;border-radius:8px;padding:0.45rem;font-size:0.82rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-telephone-fill me-1"></i>Repondre
            </button>
            <button onclick="phoneReject()" style="flex:1;background:var(--danger);color:#fff;border:none;border-radius:8px;padding:0.45rem;font-size:0.82rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-telephone-x-fill me-1"></i>Refuser
            </button>
        </div>
    </div>

    {{-- VU-mètre --}}
    <div id="phoneVuMeter" style="display:none;margin-top:0.4rem;">
        <div class="d-flex align-items-center gap-2" style="font-size:0.65rem;color:var(--text-secondary);">
            <i class="bi bi-mic" style="font-size:0.7rem;"></i>
            <div style="flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                <div id="vuLocal" style="width:0%;height:100%;background:var(--accent);border-radius:2px;transition:width 0.1s;"></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-1" style="font-size:0.65rem;color:var(--text-secondary);">
            <i class="bi bi-volume-up" style="font-size:0.7rem;"></i>
            <div style="flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                <div id="vuRemote" style="width:0%;height:100%;background:var(--success);border-radius:2px;transition:width 0.1s;"></div>
            </div>
        </div>
    </div>

    {{-- Audio --}}
    <audio id="phoneRemoteAudio" autoplay></audio>
</div>

<style>
.dp-btn {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0.35rem 0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.1;
    transition: all .1s;
}
.dp-btn:hover { background: var(--accent-dim); border-color: var(--accent-mid); }
.dp-btn:active { transform: scale(0.95); }
.dp-btn small { font-size: 0.45rem; color: var(--text-secondary); font-weight: 400; letter-spacing: 1px; }
.cid-btn {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.cid-btn:hover { border-color: var(--accent-mid); color: var(--text-primary); }
.cid-btn.active { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
.missed-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 6px;
    border-radius: 6px;
    background: rgba(248,81,73,0.08);
    border: 1px solid rgba(248,81,73,0.2);
    margin-bottom: 2px;
    font-size: 0.7rem;
}
.missed-item .missed-num { font-family:'JetBrains Mono',monospace; font-weight:700; color:var(--text-primary); flex:1; }
.missed-item .missed-time { font-size:0.6rem; color:var(--text-secondary); }
.missed-item .missed-call-btn {
    background: var(--success);
    color: #fff;
    border: none;
    border-radius: 5px;
    width: 22px;
    height: 22px;
    font-size: 0.6rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.missed-item .missed-call-btn:hover { opacity: 0.85; }
.missed-item .missed-dismiss {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 0.6rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
</style>

<script>
JsSIP.debug.enable('JsSIP:*');
var _phone = null, _session = null, _timer = null, _seconds = 0, _iceServers = null, _callerIds = [], _defaultCallerId = null, _missedCalls = [];

// Unlock audio autoplay on first user interaction
(function() {
    var unlocked = false;
    function unlock() {
        if (unlocked) return;
        unlocked = true;
        var a = document.getElementById('phoneRemoteAudio');
        if (a) { a.play().then(function(){a.pause();}).catch(function(){}); }
        document.removeEventListener('click', unlock);
        document.removeEventListener('touchstart', unlock);
    }
    document.addEventListener('click', unlock);
    document.addEventListener('touchstart', unlock);
})();

// ── Audio device management ──
function phoneToggleSettings() {
    var panel = document.getElementById('phoneSettingsPanel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
    if (panel.style.display !== 'none') phoneEnumerateDevices();
}

function phoneEnumerateDevices() {
    navigator.mediaDevices.enumerateDevices().then(function(devices) {
        var inputSel = document.getElementById('phoneAudioInput');
        var outputSel = document.getElementById('phoneAudioOutput');
        var savedInput = localStorage.getItem('voxa_audio_input') || '';
        var savedOutput = localStorage.getItem('voxa_audio_output') || '';

        inputSel.innerHTML = '<option value="">Default</option>';
        outputSel.innerHTML = '<option value="">Default</option>';

        devices.forEach(function(d) {
            if (d.kind === 'audioinput') {
                var opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || ('Mic ' + (inputSel.options.length));
                if (d.deviceId === savedInput) opt.selected = true;
                inputSel.appendChild(opt);
            } else if (d.kind === 'audiooutput') {
                var opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || ('Speaker ' + (outputSel.options.length));
                if (d.deviceId === savedOutput) opt.selected = true;
                outputSel.appendChild(opt);
            }
        });
    }).catch(function() {});
}

function phoneSaveAudioSettings() {
    var inputId = document.getElementById('phoneAudioInput').value;
    var outputId = document.getElementById('phoneAudioOutput').value;
    localStorage.setItem('voxa_audio_input', inputId);
    localStorage.setItem('voxa_audio_output', outputId);
    phoneApplyOutputDevice();
}

function phoneApplyOutputDevice() {
    var outputId = localStorage.getItem('voxa_audio_output') || '';
    var audio = document.getElementById('phoneRemoteAudio');
    if (audio && typeof audio.setSinkId === 'function' && outputId) {
        audio.setSinkId(outputId).catch(function(e) {
            console.warn('Cannot set audio output:', e.message);
        });
    }
}

function phoneGetInputConstraints() {
    var inputId = localStorage.getItem('voxa_audio_input') || '';
    if (inputId) {
        return { audio: { deviceId: { exact: inputId } } };
    }
    return { audio: true };
}

// Apply saved output device on load
document.addEventListener('DOMContentLoaded', function() {
    phoneApplyOutputDevice();
});

// ── Ringtone (Web Audio API) ──
var _ringCtx = null, _ringInterval = null, _ringGain = null;
function phoneStartRing() {
    try {
        if (_ringInterval) return;
        _ringCtx = new (window.AudioContext || window.webkitAudioContext)();
        _ringGain = _ringCtx.createGain();
        _ringGain.gain.value = 0.15;
        _ringGain.connect(_ringCtx.destination);
        function playTone() {
            var o1 = _ringCtx.createOscillator();
            var o2 = _ringCtx.createOscillator();
            var g = _ringCtx.createGain();
            o1.type = 'sine'; o1.frequency.value = 440;
            o2.type = 'sine'; o2.frequency.value = 523;
            g.gain.setValueAtTime(0.3, _ringCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, _ringCtx.currentTime + 0.8);
            o1.connect(g); o2.connect(g); g.connect(_ringGain);
            o1.start(_ringCtx.currentTime);
            o2.start(_ringCtx.currentTime);
            o1.stop(_ringCtx.currentTime + 0.8);
            o2.stop(_ringCtx.currentTime + 0.8);
        }
        playTone();
        _ringInterval = setInterval(playTone, 2000);
    } catch(e) {}
}
function phoneStopRing() {
    if (_ringInterval) { clearInterval(_ringInterval); _ringInterval = null; }
    if (_ringCtx) { try { _ringCtx.close(); } catch(e){} _ringCtx = null; }
}

function phoneSetStatus(status, text) {
    var dot = document.getElementById('phoneStatus');
    var txt = document.getElementById('phoneStatusText');
    var sidebarDot = document.getElementById('phoneDotStatus');
    var colors = {offline:'var(--text-secondary)', connecting:'var(--warning)', online:'var(--success)', busy:'var(--danger)'};
    var c = colors[status] || colors.offline;
    dot.style.background = c;
    txt.textContent = text;
    if (sidebarDot) sidebarDot.style.background = c;
    // Auto-show popup on incoming call
    if (status === 'busy') {
        var popup = document.getElementById('softphonePopup');
        if (popup) popup.style.display = 'block';
    }
}

function phoneDial(d) {
    var inp = document.getElementById('phoneInput');
    inp.value += d;
    if (_session && _session.isEstablished()) _session.sendDTMF(d);
}

function phoneToggleConnect() {
    if (_phone && _phone.isRegistered()) {
        _phone.unregister();
        _phone.stop();
        _phone = null;
        phoneSetStatus('offline', 'Deconnecte');
        return;
    }
    phoneSetStatus('connecting', 'Connexion...');
    fetch('{{ route("operator.phone.config") }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        credentials: 'same-origin'
    })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(cfg => {
            _iceServers = cfg.ice_servers || [{urls: 'stun:stun.l.google.com:19302'}];
            _defaultCallerId = cfg.caller_id;
            _callerIds = cfg.caller_ids || [];
            phonePopulateCallerIds();
            var socket = new JsSIP.WebSocketInterface(cfg.ws_uri);
            _phone = new JsSIP.UA({
                sockets: [socket],
                uri: 'sip:' + cfg.extension + '@' + cfg.realm,
                password: cfg.password,
                display_name: cfg.name,
                register: true,
                session_timers: false,
            });
            _phone.on('registered', function() { phoneSetStatus('online', 'En ligne'); });
            _phone.on('unregistered', function() { phoneSetStatus('offline', 'Deconnecte'); });
            _phone.on('registrationFailed', function(e) { phoneSetStatus('offline', 'Echec: ' + (e.cause||'')); });
            _phone.on('newRTCSession', function(e) {
                if (e.originator === 'remote') phoneOnIncoming(e.session);
            });
            _phone.start();
        })
        .catch(function(e) { console.error('Softphone config error:', e); phoneSetStatus('offline', 'Erreur: ' + e.message); });
}

function phonePopulateCallerIds() {
    var container = document.getElementById('phoneCidSelector');
    var btnsDiv = document.getElementById('phoneCidBtns');
    btnsDiv.innerHTML = '';
    if (_callerIds.length === 0) return;

    // Default button
    var defBtn = document.createElement('button');
    defBtn.className = 'cid-btn active';
    defBtn.dataset.value = '';
    defBtn.innerHTML = '<i class="bi bi-phone" style="font-size:0.6rem;"></i> Par defaut';
    defBtn.onclick = function() { cidSelect('', this); };
    btnsDiv.appendChild(defBtn);

    // Caller ID buttons
    _callerIds.forEach(function(c) {
        var btn = document.createElement('button');
        btn.className = 'cid-btn';
        btn.dataset.value = c.number;
        btn.innerHTML = c.label;
        btn.title = c.number + (c.trunk ? ' (' + c.trunk + ')' : '');
        btn.onclick = function() { cidSelect(c.number, this); };
        btnsDiv.appendChild(btn);
    });

    container.style.display = 'block';
}

function cidSelect(value, btn) {
    document.getElementById('phoneCidSelect').value = value;
    document.querySelectorAll('.cid-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
}

function phoneCall() {
    if (!_phone || !_phone.isRegistered()) return;
    var num = document.getElementById('phoneInput').value.trim();
    if (!num) return;

    var selectedCid = document.getElementById('phoneCidSelect').value;
    var extraHeaders = [];
    if (selectedCid) {
        extraHeaders.push('P-Asserted-Identity: <sip:' + selectedCid + '@{{ request()->getHost() }}>');
        extraHeaders.push('X-Voxa-CallerID: ' + selectedCid);
    }

    var inputConstraints = phoneGetInputConstraints();
    var opts = {
        mediaConstraints: {audio: inputConstraints.audio, video: false},
        pcConfig: {iceServers: _iceServers || [{urls: 'stun:stun.l.google.com:19302'}]},
        extraHeaders: extraHeaders
    };
    _session = _phone.call('sip:' + num + '@' + '{{ request()->getHost() }}', opts);
    phoneBindSession(_session, num);
}

function phoneHangup() {
    if (_session) { _session.terminate(); _session = null; }
    phoneResetUI();
}

function phoneAnswer() {
    phoneStopRing();
    // Pre-warm the audio element with user gesture context
    var audio = document.getElementById('phoneRemoteAudio');
    audio.play().catch(function(){});

    if (_session) {
        var inputConstraints = phoneGetInputConstraints();
        _session.answer({
            mediaConstraints: {audio: inputConstraints.audio, video: false},
            pcConfig: {iceServers: _iceServers || [{urls: 'stun:stun.l.google.com:19302'}]}
        });
    }
    document.getElementById('phoneIncoming').style.display = 'none';
}

function phoneReject() {
    if (_session) { _session.terminate({status_code: 486}); _session = null; }
    document.getElementById('phoneIncoming').style.display = 'none';
    phoneResetUI();
}

function phoneOnIncoming(session) {
    _session = session;
    var caller = session.remote_identity.uri.user || 'Inconnu';
    session._voxaCaller = caller;
    session._voxaAnswered = false;
    document.getElementById('phoneIncomingNumber').textContent = caller;
    document.getElementById('phoneIncoming').style.display = 'block';
    document.getElementById('phoneDialpad').style.display = 'none';
    phoneStartRing();
    phoneBindSession(session, caller);

    session.on('confirmed', function() { session._voxaAnswered = true; });
    session.on('failed', function(e) {
        if (!session._voxaAnswered && caller && caller !== 'Inconnu') {
            phoneMissedAdd(caller);
        }
    });
    session.on('ended', function() {
        if (!session._voxaAnswered && caller && caller !== 'Inconnu') {
            phoneMissedAdd(caller);
        }
    });
}

function phoneMissedAdd(number) {
    // Avoid duplicates
    if (_missedCalls.find(function(m) { return m.number === number; })) return;
    _missedCalls.unshift({ number: number, time: new Date() });
    if (_missedCalls.length > 5) _missedCalls.pop();
    phoneMissedRender();
}

function phoneMissedRender() {
    var container = document.getElementById('phoneMissed');
    var list = document.getElementById('phoneMissedList');
    if (_missedCalls.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';
    list.innerHTML = '';
    _missedCalls.forEach(function(m, i) {
        var h = m.time.getHours(), min = m.time.getMinutes();
        var timeStr = (h < 10 ? '0' : '') + h + ':' + (min < 10 ? '0' : '') + min;
        var div = document.createElement('div');
        div.className = 'missed-item';
        div.innerHTML = '<i class="bi bi-telephone-x" style="color:#f85149;font-size:0.65rem;"></i>' +
            '<span class="missed-num">' + m.number + '</span>' +
            '<span class="missed-time">' + timeStr + '</span>' +
            '<button class="missed-call-btn" title="Rappeler" onclick="phoneCallback(\'' + m.number + '\',' + i + ')"><i class="bi bi-telephone-fill"></i></button>' +
            '<button class="missed-dismiss" title="Effacer" onclick="phoneMissedDismiss(' + i + ')"><i class="bi bi-x-lg"></i></button>';
        list.appendChild(div);
    });
}

function phoneCallback(number, index) {
    document.getElementById('phoneInput').value = number;
    _missedCalls.splice(index, 1);
    phoneMissedRender();
    phoneCall();
}

function phoneMissedDismiss(index) {
    _missedCalls.splice(index, 1);
    phoneMissedRender();
}

function phoneAttachStream(pc) {
    var audio = document.getElementById('phoneRemoteAudio');
    var receivers = pc.getReceivers();
    if (receivers.length > 0 && receivers[0].track) {
        var stream = new MediaStream([receivers[0].track]);
        console.log('Attaching remote stream, track:', receivers[0].track.kind, receivers[0].track.readyState);
        audio.srcObject = stream;
        phoneApplyOutputDevice();
        audio.play().catch(function(err) { console.warn('Audio play retry needed:', err.message); });
        startVuMeter(stream, 'vuRemote');
    }
}

function phoneBindSession(session, number) {
    document.getElementById('phoneCallBtn').style.display = 'none';
    document.getElementById('phoneHangupBtn').style.display = 'block';
    document.getElementById('phoneCallInfo').style.display = 'block';
    document.getElementById('phoneCallNumber').textContent = number;
    phoneSetStatus('busy', 'En communication...');

    session.on('confirmed', function() {
        document.getElementById('phoneIncoming').style.display = 'none';
        document.getElementById('phoneDialpad').style.display = 'block';
        _seconds = 0;
        _timer = setInterval(function() {
            _seconds++;
            var m = Math.floor(_seconds / 60), s = _seconds % 60;
            document.getElementById('phoneCallTimer').textContent =
                (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }, 1000);
        // Always (re)attach stream on confirmed
        try {
            if (session.connection) phoneAttachStream(session.connection);
        } catch(err) { console.warn('Stream attach on confirmed:', err); }
    });

    session.on('peerconnection', function(e) {
        var pc = e.peerconnection;
        console.log('PeerConnection created');

        pc.ontrack = function(ev) {
            console.log('ontrack fired, streams:', ev.streams.length, 'track:', ev.track.kind);
            var audio = document.getElementById('phoneRemoteAudio');
            audio.srcObject = ev.streams[0] || new MediaStream([ev.track]);
            audio.play().catch(function(err) {
                console.warn('Audio play blocked, retrying...', err.message);
                setTimeout(function() { audio.play().catch(function(){}); }, 500);
            });
            startVuMeter(audio.srcObject, 'vuRemote');
        };

        pc.onaddstream = function(ev) {
            console.log('onaddstream fired');
            var audio = document.getElementById('phoneRemoteAudio');
            audio.srcObject = ev.stream;
            audio.play().catch(function(){});
        };

        navigator.mediaDevices.getUserMedia(phoneGetInputConstraints()).then(function(stream) {
            startVuMeter(stream, 'vuLocal');
        }).catch(function() {});

        pc.oniceconnectionstatechange = function() {
            console.log('ICE state:', pc.iceConnectionState);
            // Re-attach stream when ICE connects (covers early media + confirmed)
            if (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') {
                phoneAttachStream(pc);
            }
        };

        pc.onconnectionstatechange = function() {
            console.log('Connection state:', pc.connectionState);
        };
    });

    // Handle early media (183 progress) for outbound calls
    session.on('progress', function(e) {
        console.log('Progress:', e.response?.status_code);
        if (e.response?.status_code === 183) {
            phoneSetStatus('busy', 'Sonnerie...');
        }
    });

    // Ignore duplicate 183 SDP errors gracefully
    session.on('peerconnection:setremotedescriptionfailed', function(e) {
        console.warn('setRemoteDescription failed (ignored):', e.error?.message);
    });

    session.on('ended', function() { phoneResetUI(); stopVuMeter(); });
    session.on('failed', function() { phoneResetUI(); stopVuMeter(); });
}

var _vuIntervals = [];
function startVuMeter(stream, barId) {
    try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var analyser = ctx.createAnalyser();
        analyser.fftSize = 256;
        var source = ctx.createMediaStreamSource(stream);
        source.connect(analyser);
        var data = new Uint8Array(analyser.frequencyBinCount);
        document.getElementById('phoneVuMeter').style.display = 'block';
        var iv = setInterval(function() {
            analyser.getByteFrequencyData(data);
            var avg = data.reduce(function(a,b){return a+b;}, 0) / data.length;
            var pct = Math.min(100, Math.round(avg * 1.5));
            var bar = document.getElementById(barId);
            if (bar) bar.style.width = pct + '%';
        }, 100);
        _vuIntervals.push({iv: iv, ctx: ctx});
    } catch(e) {}
}
function stopVuMeter() {
    _vuIntervals.forEach(function(v) { clearInterval(v.iv); v.ctx.close(); });
    _vuIntervals = [];
    document.getElementById('phoneVuMeter').style.display = 'none';
    var vl = document.getElementById('vuLocal'); if(vl) vl.style.width = '0%';
    var vr = document.getElementById('vuRemote'); if(vr) vr.style.width = '0%';
}

function phoneResetUI() {
    phoneStopRing();
    _session = null;
    if (_timer) { clearInterval(_timer); _timer = null; }
    _seconds = 0;
    document.getElementById('phoneCallBtn').style.display = 'block';
    document.getElementById('phoneHangupBtn').style.display = 'none';
    document.getElementById('phoneCallInfo').style.display = 'none';
    document.getElementById('phoneIncoming').style.display = 'none';
    document.getElementById('phoneDialpad').style.display = 'block';
    document.getElementById('phoneCallTimer').textContent = '00:00';
    if (_phone && _phone.isRegistered()) phoneSetStatus('online', 'En ligne');
    else phoneSetStatus('offline', 'Deconnecte');
}

// Auto-connect on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(phoneToggleConnect, 500);
});

// Handle Enter key on input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('phoneInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') phoneCall();
        if (e.key === 'Backspace' && this.value.length === 0) e.preventDefault();
    });
});
</script>
