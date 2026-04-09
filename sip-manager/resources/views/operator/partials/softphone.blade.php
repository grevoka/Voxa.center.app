{{-- Softphone WebRTC --}}
<div id="softphone" style="padding:0.75rem;margin:0.5rem 0.75rem;background:var(--surface-3);border:1px solid var(--border);border-radius:12px;">
    {{-- Status --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="d-flex align-items-center gap-2">
            <div id="phoneStatus" style="width:8px;height:8px;border-radius:50%;background:var(--text-secondary);"></div>
            <span id="phoneStatusText" style="font-size:0.7rem;color:var(--text-secondary);">Deconnecte</span>
        </div>
        <button id="phoneToggle" onclick="phoneToggleConnect()" class="btn-icon" style="width:24px;height:24px;font-size:0.65rem;" title="Connecter">
            <i class="bi bi-power"></i>
        </button>
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
        <div style="font-size:0.78rem;color:var(--text-secondary);margin-bottom:0.3rem;">Appel entrant</div>
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

    {{-- Audio --}}
    <audio id="phoneRemoteAudio" autoplay></audio>
    <audio id="phoneRingAudio" loop preload="auto">
        <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=" type="audio/wav">
    </audio>
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
</style>

<script>
var _phone = null, _session = null, _timer = null, _seconds = 0;

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

function phoneCall() {
    if (!_phone || !_phone.isRegistered()) return;
    var num = document.getElementById('phoneInput').value.trim();
    if (!num) return;

    var opts = {
        mediaConstraints: {audio: true, video: false},
        pcConfig: {iceServers: [{urls: 'stun:stun.l.google.com:19302'}]}
    };
    _session = _phone.call('sip:' + num + '@' + '{{ request()->getHost() }}', opts);
    phoneBindSession(_session, num);
}

function phoneHangup() {
    if (_session) { _session.terminate(); _session = null; }
    phoneResetUI();
}

function phoneAnswer() {
    if (_session) {
        _session.answer({
            mediaConstraints: {audio: true, video: false},
            pcConfig: {iceServers: [{urls: 'stun:stun.l.google.com:19302'}]}
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
    document.getElementById('phoneIncomingNumber').textContent = caller;
    document.getElementById('phoneIncoming').style.display = 'block';
    document.getElementById('phoneDialpad').style.display = 'none';
    phoneBindSession(session, caller);
}

function phoneBindSession(session, number) {
    document.getElementById('phoneCallBtn').style.display = 'none';
    document.getElementById('phoneHangupBtn').style.display = 'block';
    document.getElementById('phoneCallInfo').style.display = 'block';
    document.getElementById('phoneCallNumber').textContent = number;
    phoneSetStatus('busy', 'En appel');

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
    });

    session.on('peerconnection', function(e) {
        e.peerconnection.ontrack = function(ev) {
            document.getElementById('phoneRemoteAudio').srcObject = ev.streams[0];
        };
    });

    session.on('ended', function() { phoneResetUI(); });
    session.on('failed', function() { phoneResetUI(); });
}

function phoneResetUI() {
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
