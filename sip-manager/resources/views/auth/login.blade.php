<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — SIP.ctrl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #22c55e;
            --accent-dim: rgba(34,197,94,0.12);
            --surface-1: #0f1117;
            --surface-2: #1a1d27;
            --border: #2a2d3a;
            --text-secondary: #8b8fa3;
        }
        body {
            background: var(--surface-1);
            color: #e2e4eb;
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container { width: 420px; max-width: 95vw; }
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand h2 { font-weight: 800; font-size: 1.6rem; }
        .login-brand h2 span { color: var(--accent); }
        .login-brand small {
            display: block;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }
        .login-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 2rem;
        }
        .login-card h5 {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.3rem;
        }
        .login-card .subtitle {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
        }
        .form-control {
            background: var(--surface-1);
            border-color: var(--border);
            color: #e2e4eb;
            font-size: 0.88rem;
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
            background: var(--surface-1);
            color: #e2e4eb;
        }
        .form-control::placeholder { color: var(--text-secondary); opacity: 0.6; }
        .btn-accent {
            background: var(--accent);
            color: #fff;
            border: none;
            font-weight: 700;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            width: 100%;
            transition: background 0.15s;
        }
        .btn-accent:hover { background: #1ea74e; color: #fff; }
        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        .form-check-label { font-size: 0.82rem; color: var(--text-secondary); }
        .forgot-link {
            font-size: 0.8rem;
            color: var(--accent);
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; color: #1ea74e; }
        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            border-radius: 8px;
            font-size: 0.82rem;
        }
        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
            border-radius: 8px;
            font-size: 0.82rem;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .input-icon-wrap .form-control {
            padding-left: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <h2><span>//</span> SIP<span>.</span>ctrl</h2>
            <small>Telecom Management</small>
        </div>

        <div class="login-card">
            <h5>Connexion</h5>
            <div class="subtitle">Accedez a votre espace de gestion</div>

            @if(session('status'))
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope"></i>
                        <input id="email" type="email" name="email" class="form-control"
                               value="{{ old('email') }}" placeholder="admin@example.com"
                               required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input id="password" type="password" name="password" class="form-control"
                               placeholder="Votre mot de passe"
                               required autocomplete="current-password">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublie ?</a>
                    @endif
                </div>

                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                </button>
            </form>
        </div>

        <div class="text-center mt-3" style="font-size:0.72rem;color:var(--text-secondary);">
            SIP.ctrl — Telecom Management
        </div>
    </div>
</body>
</html>
