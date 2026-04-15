<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation — Voxa Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #29b6f6;
            --accent-dim: rgba(41,182,246,0.12);
            --accent-gradient: linear-gradient(135deg, #29b6f6, #ab47bc);
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
        .install-container {
            width: 620px;
            max-width: 95vw;
        }
        .install-brand {
            text-align: center;
            margin-bottom: 1rem;
        }
        .install-brand img {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            margin-bottom: 0;
        }
        .install-brand h2 {
            font-weight: 800;
            font-size: 1.1rem;
            margin: 0;
        }
        .install-brand h2 span { color: var(--accent); }
        .install-brand small {
            display: block;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }
        .install-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        .install-steps {
            display: flex;
            border-bottom: 1px solid var(--border);
        }
        .install-step-tab {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: transparent;
            border-right: 1px solid var(--border);
            position: relative;
        }
        .install-step-tab:last-child { border-right: none; }
        .install-step-tab.active {
            color: var(--accent);
            background: var(--accent-dim);
        }
        .install-step-tab.done {
            color: var(--accent);
        }
        .install-step-tab .step-num {
            display: inline-flex;
            width: 22px; height: 22px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            margin-right: 0.3rem;
            background: var(--border);
            color: var(--text-secondary);
        }
        .install-step-tab.active .step-num {
            background: var(--accent);
            color: #fff;
        }
        .install-step-tab.done .step-num {
            background: var(--accent);
            color: #fff;
        }
        .install-body {
            padding: 1.5rem;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
        }
        .form-control, .form-select {
            background: var(--surface-1);
            border-color: var(--border);
            color: #e2e4eb;
            font-size: 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-dim);
            background: var(--surface-1);
            color: #e2e4eb;
        }
        .btn-accent {
            background: var(--accent);
            color: #fff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
        }
        .btn-accent:hover { background: #1e96d0; color: #fff; }
        .btn-outline-custom {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }
        .btn-outline-custom:hover { border-color: var(--accent); color: var(--accent); }
        .req-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border);
        }
        .req-item:last-child { border-bottom: none; }
        .req-ok { color: var(--accent); }
        .req-fail { color: #ef4444; }
        .section-title {
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
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
        .db-section {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .db-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .finish-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--accent-dim);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-brand">
            <img src="/img/voxa-logo.png" alt="Voxa Center">
            <h2>Voxa<span>.</span>Center</h2>
            <small>{{ __('ui.install_wizard') ?? 'Installation wizard' }}</small>
        </div>

        <div class="install-card">
            {{-- Steps tabs --}}
            <div class="install-steps">
                <div class="install-step-tab {{ $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' }}">
                    <span class="step-num">{{ $step > 1 ? '✓' : '1' }}</span> Prerequis
                </div>
                <div class="install-step-tab {{ $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' }}">
                    <span class="step-num">{{ $step > 2 ? '✓' : '2' }}</span> Base de donnees
                </div>
                <div class="install-step-tab {{ $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' }}">
                    <span class="step-num">{{ $step > 3 ? '✓' : '3' }}</span> Administrateur
                </div>
                <div class="install-step-tab {{ $step >= 4 ? 'active' : '' }}">
                    <span class="step-num">4</span> Termine
                </div>
            </div>

            <div class="install-body">
                {{-- Flash messages --}}
                @if(session('error'))
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    </div>
                @endif

                {{-- ==================== STEP 1: Requirements ==================== --}}
                @if($step === 1)
                    <div class="section-title">Verification du systeme</div>
                    <div style="margin-bottom:1rem;">
                        @foreach($requirements as $name => $ok)
                            <div class="req-item">
                                <i class="bi {{ $ok ? 'bi-check-circle-fill req-ok' : 'bi-x-circle-fill req-fail' }}"></i>
                                <span>{{ $name }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if(in_array(false, $requirements, true))
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Corrigez les elements en rouge avant de continuer.
                        </div>
                    @endif

                    <form action="{{ route('install.requirements') }}" method="POST">
                        @csrf
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-accent" {{ in_array(false, $requirements, true) ? 'disabled' : '' }}>
                                Continuer <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>

                {{-- ==================== STEP 2: Database ==================== --}}
                @elseif($step === 2)
                    <form action="{{ route('install.database') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">URL de l'application</label>
                            @php
                                $appUrl = str_replace('http://', 'https://', request()->root());
                            @endphp
                            <input type="url" name="app_url" class="form-control" value="{{ $appUrl }}" readonly style="background:var(--surface-1);cursor:default;">
                        </div>

                        @php
                            $dbReady = false;
                            try {
                                \Illuminate\Support\Facades\DB::connection()->getPdo();
                                \Illuminate\Support\Facades\DB::connection('asterisk')->getPdo();
                                $dbReady = true;
                            } catch (\Throwable $e) {}
                        @endphp

                        @if($dbReady)
                            {{-- DB already configured --}}
                            <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill" style="color:var(--accent);font-size:1.1rem;"></i>
                                    <span style="font-weight:700;font-size:0.9rem;">Base de donnees configuree automatiquement</span>
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-secondary);">
                                    Les deux bases de donnees (Voxa Center et Asterisk Realtime) sont connectees et operationnelles.
                                    La configuration des bases de donnees est operationnelle.
                                </div>
                            </div>

                            {{-- Hidden fields with actual .env values --}}
                            <input type="hidden" name="db_host" value="{{ config('database.connections.mysql.host') }}">
                            <input type="hidden" name="db_port" value="{{ config('database.connections.mysql.port') }}">
                            <input type="hidden" name="db_database" value="{{ config('database.connections.mysql.database') }}">
                            <input type="hidden" name="db_username" value="{{ config('database.connections.mysql.username') }}">
                            <input type="hidden" name="db_password" value="{{ config('database.connections.mysql.password') }}">
                            <input type="hidden" name="db_ast_host" value="{{ config('database.connections.asterisk.host') }}">
                            <input type="hidden" name="db_ast_port" value="{{ config('database.connections.asterisk.port') }}">
                            <input type="hidden" name="db_ast_database" value="{{ config('database.connections.asterisk.database') }}">
                            <input type="hidden" name="db_ast_username" value="{{ config('database.connections.asterisk.username') }}">
                            <input type="hidden" name="db_ast_password" value="{{ config('database.connections.asterisk.password') }}">
                        @else
                            {{-- Manual mode: show DB forms --}}
                            <div class="db-section">
                                <div class="db-section-title">
                                    <i class="bi bi-database me-1" style="color:var(--accent);"></i> Base de donnees principale
                                </div>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <label class="form-label">Hote</label>
                                        <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Port</label>
                                        <input type="number" name="db_port" class="form-control" value="{{ old('db_port', 3306) }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Nom de la base</label>
                                        <input type="text" name="db_database" class="form-control" value="{{ old('db_database', 'sip_manager') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Utilisateur</label>
                                        <input type="text" name="db_username" class="form-control" value="{{ old('db_username', 'root') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Mot de passe</label>
                                        <input type="password" name="db_password" class="form-control" value="{{ old('db_password') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="db-section">
                                <div class="db-section-title">
                                    <i class="bi bi-database me-1" style="color:var(--accent);"></i> Base Asterisk Realtime
                                </div>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <label class="form-label">Hote</label>
                                        <input type="text" name="db_ast_host" class="form-control" value="{{ old('db_ast_host', '127.0.0.1') }}" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Port</label>
                                        <input type="number" name="db_ast_port" class="form-control" value="{{ old('db_ast_port', 3306) }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Nom de la base</label>
                                        <input type="text" name="db_ast_database" class="form-control" value="{{ old('db_ast_database', 'asterisk_rt') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Utilisateur</label>
                                        <input type="text" name="db_ast_username" class="form-control" value="{{ old('db_ast_username', 'root') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Mot de passe</label>
                                        <input type="password" name="db_ast_password" class="form-control" value="{{ old('db_ast_password') }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-accent">
                                @if($dbReady)
                                    <i class="bi bi-arrow-right me-1"></i> Continuer
                                @else
                                    <i class="bi bi-database-check me-1"></i> Tester & migrer <i class="bi bi-arrow-right ms-1"></i>
                                @endif
                            </button>
                        </div>
                    </form>

                {{-- ==================== STEP 3: Admin ==================== --}}
                @elseif($step === 3)
                    <div class="section-title">Compte administrateur</div>
                    <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:1rem;">
                        Ce compte servira a vous connecter a l'interface de gestion.
                    </p>

                    <form action="{{ route('install.admin') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nom complet</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Jean Dupont" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="admin@example.com" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Min. 8 caracteres" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmer</label>
                                <input type="password" name="password_confirmation" class="form-control"
                                       placeholder="Retaper le mot de passe" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-accent">
                                <i class="bi bi-person-check me-1"></i> Creer & finaliser <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>

                {{-- ==================== STEP 4: Done ==================== --}}
                @elseif($step === 4)
                    <div class="text-center py-3">
                        <div class="finish-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <h5 style="font-weight:700;">Installation terminee !</h5>
                        <p style="font-size:0.85rem;color:var(--text-secondary);margin:1rem 0;">
                            Voxa Center est pret. La base de donnees est configuree, les migrations executees
                            et votre compte administrateur cree.
                        </p>
                        <p style="font-size:0.78rem;color:var(--text-secondary);">
                            L'URL <code>/install</code> sera desactivee apres cette etape.
                        </p>

                        <form action="{{ route('install.finalize') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-accent btn-lg">
                                <i class="bi bi-rocket-takeoff me-2"></i> Lancer Voxa Center
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="text-center mt-3" style="font-size:0.72rem;color:var(--text-secondary);">
            Voxa Center — Telecom Management
        </div>
    </div>
</body>
</html>
