@extends(auth()->user()?->isOperator() || session('impersonate_admin_id') ? 'layouts.operator' : 'layouts.app')

@section('title', 'Documentation')
@section('page-title', 'Documentation utilisateur')

@section('content')
    <div class="section-header">
        <div>
            <h5 class="mb-1" style="font-weight:700;">
                <i class="bi bi-book me-1" style="color:var(--accent);"></i> Documentation utilisateur
            </h5>
            <p class="mb-0" style="font-size:0.82rem;color:var(--text-secondary);">
                Guide d'utilisation des fonctionnalites telephoniques
            </p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Table of contents --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="stat-card" style="position:sticky; top:1rem; padding:1rem;">
                <h6 style="font-weight:700; font-size:0.85rem; margin-bottom:0.75rem;">Sommaire</h6>
                <nav class="doc-toc">
                    <a href="#transfers">Transferts d'appels</a>
                    <a href="#conferences">Salles de conference</a>
                    <a href="#voicemail">Messagerie vocale</a>
                    <a href="#recording">Enregistrement d'appels</a>
                    <a href="#queues">Files d'attente</a>
                    <a href="#pickup">Interception d'appels</a>
                    <a href="#codes">Resume des codes</a>
                </nav>
            </div>
        </div>

        {{-- Documentation content --}}
        <div class="col-lg-9">

            {{-- ===== TRANSFERTS ===== --}}
            <div class="stat-card doc-section" id="transfers">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(88,166,255,0.12); color:#58a6ff;">
                        <i class="bi bi-telephone-forward-fill"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Transferts d'appels</h5>
                        <p class="doc-subtitle">Rediriger un appel vers un autre poste</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <h6><i class="bi bi-lightning-fill me-1" style="color:#d29922;"></i> Transfert aveugle (Blind Transfer)</h6>
                        <p>Le transfert aveugle redirige l'appel immediatement vers le destinataire, sans attendre qu'il decroche.</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Pendant un appel, composez <kbd>##</kbd></span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Composez le <strong>numero du poste</strong> destinataire (ex: <kbd>1002</kbd>)</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>L'appel est immediatement transfere</span>
                            </div>
                        </div>
                        <div class="doc-note">
                            <i class="bi bi-info-circle me-1"></i>
                            Vous etes deconnecte de l'appel des que le transfert est initie. Si le destinataire ne repond pas, l'appel est perdu.
                        </div>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-chat-dots-fill me-1" style="color:#58a6ff;"></i> Transfert assiste (Attended Transfer)</h6>
                        <p>Le transfert assiste vous permet de parler au destinataire avant de lui transferer l'appel.</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Pendant un appel, composez <kbd>*2</kbd></span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Composez le <strong>numero du poste</strong> destinataire</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>Parlez au destinataire pour annoncer l'appel</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">4</span>
                                <span>Raccrochez pour finaliser le transfert</span>
                            </div>
                        </div>
                        <div class="doc-note">
                            <i class="bi bi-info-circle me-1"></i>
                            Si le destinataire refuse, vous pouvez reprendre l'appel initial en raccrochant le deuxieme appel.
                        </div>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-x-circle-fill me-1" style="color:#f85149;"></i> Deconnexion</h6>
                        <p>Pour raccrocher proprement un appel en cours :</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Composez <kbd>*0</kbd> pendant l'appel</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== CONFERENCES ===== --}}
            <div class="stat-card doc-section" id="conferences">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(188,140,255,0.12); color:#bc8cff;">
                        <i class="bi bi-camera-video-fill"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Salles de conference</h5>
                        <p class="doc-subtitle">Appels a plusieurs participants</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <h6><i class="bi bi-telephone-plus-fill me-1" style="color:#bc8cff;"></i> Rejoindre une conference</h6>
                        <p>Pour rejoindre une salle de conference, composez le numero attribue a la salle depuis votre poste.</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Composez le <strong>numero de la salle</strong> (ex: <kbd>800</kbd>)</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Si un <strong>code PIN</strong> est demande, saisissez-le suivi de <kbd>#</kbd></span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>Vous etes connecte a la conference</span>
                            </div>
                        </div>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-shield-lock-fill me-1" style="color:#d29922;"></i> Roles dans une conference</h6>
                        <div class="doc-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th>Description</th>
                                        <th>Code PIN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="codec-tag">Participant</span></td>
                                        <td>Participe a la conference normalement</td>
                                        <td>PIN utilisateur (si configure)</td>
                                    </tr>
                                    <tr>
                                        <td><span class="codec-tag" style="color:#d29922;border-color:rgba(210,153,34,0.3);">Admin</span></td>
                                        <td>Peut gerer la conference (si « attendre l'admin » est active, la conference demarre a son arrivee)</td>
                                        <td>PIN administrateur</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-gear-fill me-1" style="color:var(--text-secondary);"></i> Options disponibles</h6>
                        <div class="doc-features">
                            <div class="doc-feature">
                                <i class="bi bi-mic-mute-fill"></i>
                                <div>
                                    <strong>Couper le micro a l'entree</strong>
                                    <small>Les participants sont en sourdine en rejoignant</small>
                                </div>
                            </div>
                            <div class="doc-feature">
                                <i class="bi bi-record-circle"></i>
                                <div>
                                    <strong>Enregistrement</strong>
                                    <small>La conference est enregistree automatiquement</small>
                                </div>
                            </div>
                            <div class="doc-feature">
                                <i class="bi bi-megaphone-fill"></i>
                                <div>
                                    <strong>Annonce entree/sortie</strong>
                                    <small>Un son signale les arrivees et departs</small>
                                </div>
                            </div>
                            <div class="doc-feature">
                                <i class="bi bi-hourglass-split"></i>
                                <div>
                                    <strong>Attendre l'administrateur</strong>
                                    <small>La conference ne demarre qu'a l'arrivee d'un admin</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== MESSAGERIE VOCALE ===== --}}
            <div class="stat-card doc-section" id="voicemail">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(41,182,246,0.12); color:#29b6f6;">
                        <i class="bi bi-voicemail"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Messagerie vocale</h5>
                        <p class="doc-subtitle">Consulter et gerer vos messages</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <h6><i class="bi bi-telephone-fill me-1" style="color:#29b6f6;"></i> Consulter depuis le telephone</h6>
                        <p>Vous pouvez ecouter vos messages vocaux directement depuis votre poste.</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Composez <kbd>*98</kbd> depuis votre poste</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Saisissez votre <strong>mot de passe</strong> de messagerie suivi de <kbd>#</kbd></span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>Suivez les instructions vocales pour naviguer dans vos messages</span>
                            </div>
                        </div>

                        <div class="doc-note mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Navigation dans la messagerie :</strong>
                        </div>

                        <div class="doc-table mt-2">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Touche</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><kbd>1</kbd></td><td>Ecouter les nouveaux messages</td></tr>
                                    <tr><td><kbd>2</kbd></td><td>Changer le dossier (anciens messages, messages sauvegardes...)</td></tr>
                                    <tr><td><kbd>3</kbd></td><td>Options avancees</td></tr>
                                    <tr><td><kbd>0</kbd></td><td>Options de la messagerie (modifier l'annonce, le mot de passe...)</td></tr>
                                    <tr><td><kbd>*</kbd></td><td>Aide</td></tr>
                                    <tr><td><kbd>#</kbd></td><td>Quitter</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="doc-table mt-3">
                            <p style="font-weight:600; font-size:0.85rem; margin-bottom:0.5rem;">Pendant l'ecoute d'un message :</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Touche</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><kbd>5</kbd></td><td>Reecouter le message</td></tr>
                                    <tr><td><kbd>6</kbd></td><td>Message suivant</td></tr>
                                    <tr><td><kbd>4</kbd></td><td>Message precedent</td></tr>
                                    <tr><td><kbd>7</kbd></td><td>Supprimer le message</td></tr>
                                    <tr><td><kbd>9</kbd></td><td>Sauvegarder le message</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-globe me-1" style="color:#58a6ff;"></i> Consulter depuis l'interface web</h6>
                        <p>Vous pouvez egalement ecouter et gerer vos messages depuis l'interface Voxa Center.</p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Allez dans <strong>Routage &gt; Messagerie vocale</strong></span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Selectionnez le poste concerne</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>Ecoutez ou supprimez les messages</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== ENREGISTREMENT ===== --}}
            <div class="stat-card doc-section" id="recording">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(248,81,73,0.12); color:#f85149;">
                        <i class="bi bi-record-circle"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Enregistrement d'appels</h5>
                        <p class="doc-subtitle">Enregistrement automatique et droit de refus</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <h6><i class="bi bi-record-fill me-1" style="color:#f85149;"></i> Fonctionnement</h6>
                        <p>
                            L'enregistrement des appels peut etre active par scenario entrant.
                            Lorsqu'il est active, tous les appels du scenario sont enregistres automatiquement
                            des le debut de la conversation.
                        </p>
                        <p>Les enregistrements sont stockes au format WAV et accessibles par l'administrateur.</p>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-shield-exclamation me-1" style="color:#d29922;"></i> Droit de refus (opt-out)</h6>
                        <p>
                            Si l'option est activee dans le scenario, l'appelant peut <strong>desactiver l'enregistrement</strong>
                            en appuyant sur une touche pendant l'appel.
                        </p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>L'appelant est informe que l'appel est enregistre (annonce d'accueil)</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>Pour refuser l'enregistrement, appuyez sur la touche configuree (par defaut <kbd>8</kbd>)</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">3</span>
                                <span>L'enregistrement est immediatement arrete</span>
                            </div>
                        </div>
                        <div class="doc-note">
                            <i class="bi bi-info-circle me-1"></i>
                            La touche de refus est configurable dans les parametres du scenario d'appel entrant.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== FILES D'ATTENTE ===== --}}
            <div class="stat-card doc-section" id="queues">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(88,166,255,0.12); color:#58a6ff;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Files d'attente</h5>
                        <p class="doc-subtitle">Distribution des appels aux agents</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <h6><i class="bi bi-diagram-3-fill me-1" style="color:#58a6ff;"></i> Principe</h6>
                        <p>
                            Une file d'attente distribue les appels entrants vers un groupe d'agents (postes)
                            selon une strategie definie (sonnerie tous, aleatoire, le moins occupe, etc.).
                        </p>
                        <p>
                            L'appelant entend une musique d'attente et/ou des annonces periodiques
                            jusqu'a ce qu'un agent prenne l'appel.
                        </p>
                    </div>

                    <div class="doc-block">
                        <h6><i class="bi bi-list-ol me-1" style="color:var(--text-secondary);"></i> Strategies de distribution</h6>
                        <div class="doc-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Strategie</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="codec-tag">ringall</span></td><td>Tous les agents sonnent en meme temps</td></tr>
                                    <tr><td><span class="codec-tag">leastrecent</span></td><td>L'agent qui n'a pas pris d'appel depuis le plus longtemps</td></tr>
                                    <tr><td><span class="codec-tag">fewestcalls</span></td><td>L'agent avec le moins d'appels pris</td></tr>
                                    <tr><td><span class="codec-tag">random</span></td><td>Agent selectionne aleatoirement</td></tr>
                                    <tr><td><span class="codec-tag">rrmemory</span></td><td>Tour par tour avec memoire</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== INTERCEPTION ===== --}}
            <div class="stat-card doc-section" id="pickup">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(210,153,34,0.12); color:#d29922;">
                        <i class="bi bi-hand-index-fill"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Interception d'appels</h5>
                        <p class="doc-subtitle">Prendre un appel qui sonne sur un autre poste</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-block">
                        <p>
                            Si un telephone sonne a proximite et que personne ne repond,
                            vous pouvez intercepter l'appel depuis votre poste.
                        </p>
                        <div class="doc-steps">
                            <div class="doc-step">
                                <span class="step-num">1</span>
                                <span>Composez <kbd>*8</kbd> depuis votre poste</span>
                            </div>
                            <div class="doc-step">
                                <span class="step-num">2</span>
                                <span>L'appel en attente est automatiquement redirige vers vous</span>
                            </div>
                        </div>
                        <div class="doc-note">
                            <i class="bi bi-info-circle me-1"></i>
                            L'interception fonctionne pour les appels du meme groupe de postes (pickup group).
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== RESUME DES CODES ===== --}}
            <div class="stat-card doc-section" id="codes">
                <div class="doc-section-header">
                    <div class="doc-icon" style="background:rgba(var(--accent-rgb),0.12); color:var(--accent);">
                        <i class="bi bi-hash"></i>
                    </div>
                    <div>
                        <h5 class="doc-title">Resume des codes</h5>
                        <p class="doc-subtitle">Tous les codes a retenir</p>
                    </div>
                </div>

                <div class="doc-body">
                    <div class="doc-table">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:100px;">Code</th>
                                    <th>Fonction</th>
                                    <th>Quand</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><kbd>##</kbd></td>
                                    <td>Transfert aveugle</td>
                                    <td>Pendant un appel</td>
                                </tr>
                                <tr>
                                    <td><kbd>*2</kbd></td>
                                    <td>Transfert assiste</td>
                                    <td>Pendant un appel</td>
                                </tr>
                                <tr>
                                    <td><kbd>*0</kbd></td>
                                    <td>Deconnexion</td>
                                    <td>Pendant un appel</td>
                                </tr>
                                <tr>
                                    <td><kbd>*8</kbd></td>
                                    <td>Interception d'appel (pickup)</td>
                                    <td>Quand un poste sonne</td>
                                </tr>
                                <tr>
                                    <td><kbd>*98</kbd></td>
                                    <td>Consulter la messagerie vocale</td>
                                    <td>A tout moment</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        .doc-toc {
            display: flex; flex-direction: column; gap: 2px;
        }
        .doc-toc a {
            display: block; padding: 6px 10px; border-radius: 6px;
            font-size: 0.8rem; color: var(--text-secondary);
            text-decoration: none; transition: all .15s;
        }
        .doc-toc a:hover {
            background: rgba(var(--accent-rgb), 0.08);
            color: var(--accent);
        }

        .doc-section {
            padding: 0 !important;
            overflow: hidden;
            margin-bottom: 0;
        }
        .doc-section-header {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
        }
        .doc-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .doc-title {
            font-weight: 700; font-size: 1rem; margin: 0;
        }
        .doc-subtitle {
            font-size: 0.8rem; color: var(--text-secondary); margin: 0;
        }

        .doc-body {
            padding: 1.5rem;
        }
        .doc-block {
            margin-bottom: 1.5rem;
        }
        .doc-block:last-child { margin-bottom: 0; }
        .doc-block h6 {
            font-weight: 700; font-size: 0.9rem; margin-bottom: 0.5rem;
        }
        .doc-block p {
            font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .doc-steps {
            display: flex; flex-direction: column; gap: 0.5rem;
            margin: 0.75rem 0;
        }
        .doc-step {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            background: var(--surface-3);
            font-size: 0.85rem;
        }
        .step-num {
            width: 24px; height: 24px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
        }

        .doc-note {
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            background: rgba(88,166,255,0.06);
            border: 1px solid rgba(88,166,255,0.15);
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .doc-table {
            overflow-x: auto;
        }
        .doc-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .doc-table th {
            text-align: left;
            padding: 0.5rem 0.75rem;
            background: var(--surface-3);
            font-weight: 600;
            font-size: 0.78rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }
        .doc-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .doc-table tr:last-child td { border-bottom: none; }

        kbd {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 5px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--accent);
            box-shadow: 0 1px 0 var(--border);
        }

        .doc-features {
            display: flex; flex-direction: column; gap: 0.5rem;
        }
        .doc-feature {
            display: flex; align-items: flex-start; gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            background: var(--surface-3);
            font-size: 0.85rem;
        }
        .doc-feature i {
            margin-top: 2px; color: var(--text-secondary); font-size: 0.9rem;
        }
        .doc-feature strong {
            display: block; font-size: 0.82rem;
        }
        .doc-feature small {
            color: var(--text-secondary); font-size: 0.75rem;
        }
    </style>
@endsection
