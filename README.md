# Voxa Center

**Plateforme de gestion de telephonie SIP / Asterisk PBX avec Agent IA**

Voxa Center est une application web de gestion de PBX Asterisk deployable nativement sur Debian/Ubuntu. Elle permet de configurer et superviser un systeme de telephonie IP complet depuis une interface moderne, avec un agent conversationnel IA integre (OpenAI Realtime).

## Fonctionnalites

### Gestion SIP
- **Lignes SIP** — creation, edition, provisioning PJSIP Realtime automatique
- **Trunks SIP** — connexion aux fournisseurs (OVH, etc.) avec support proxy sortant, register/unregister
- **Operateurs** — comptes operateurs avec role-based access, impersonation admin
- **Caller ID** — gestion des numeros sortants, groupes d'acces par operateur
- **WebRTC Softphone** — telephone virtuel integre dans l'espace operateur (JsSIP + DTLS-SRTP + ICE)
- **Codecs** — configuration alaw, ulaw, g722, opus

### Routage d'appels
- **Scenarios (Call Flows)** — editeur visuel 2D drag & drop avec blocs connectables :
  - Sonnerie, File d'attente, Messagerie vocale, Renvoi, Musique d'attente
  - **IVR** — menu vocal avec branches visuelles par touche DTMF, boucle configurable
  - **Horaires** — branches ouvert/ferme avec ports visuels vert/rouge
  - **Agent IA** — conversation temps reel avec OpenAI Realtime
  - **TTS** — synthese vocale Piper integree avec preview audio dans l'editeur
- **Filtres DID/CID** — routage par numero appele et/ou Caller ID appelant
- **Routes sortantes** — regles de routage par pattern avec priorites
- **Contextes** — gestion des contextes Asterisk (entrant, sortant, interne)

### Intelligence Artificielle
- **Agent IA OpenAI Realtime** — conversation telephonique temps reel via AudioSocket
  - Full-duplex bidirectionnel (l'appelant peut interrompre l'IA)
  - Choix de voix (Coral, Alloy, Ash, Echo, Sage, Shimmer, Verse, Ballad)
  - Guardrails automatiques (cadrage sujet, pas de divulgation IA)
  - Detection de fin de conversation ("au revoir" → raccrochage)
- **Base de connaissances RAG** — documents de contexte par dossier
  - Upload/creation/edition de fichiers .txt/.md depuis l'interface
  - Dossiers specifiques assignables a chaque bloc Agent IA
  - Documents generaux charges par tous les agents
- **Historique AI** — conversations avec transcription complete (bulles chat)
  - Cout estime par conversation, duree, nombre d'echanges
  - Filtre par modele (GPT-4o / GPT-4o Mini)
- **Facturation live** — dashboard cout jour/semaine/mois avec barre de budget
- **Piper TTS** — synthese vocale locale (3 voix francaises) pour IVR et annonces
  - Preview audio dans le builder (generation + ecoute dans le navigateur)

### Services
- **Files d'attente** — configuration des queues avec strategies, musique d'attente, timeouts
- **Conferences** — salles de conference ConfBridge
- **Messagerie vocale** — ecoute, suppression, notification email SMTP
- **Musiques d'attente** — fichiers locaux, playlists personnalisees, flux streaming HTTP (ffmpeg)

### Monitoring & Securite
- **Dashboard** — graphique MRTG des appels (7 jours), stats du jour, appels manques par poste, duree par operateur
- **Supervision live** — appels en cours en temps reel
- **Journal d'appels** — CDR avec filtres, recherche, pagination
- **Enregistrements** — ecoute et suppression des conversations (MixMonitor)
- **Logs systeme** — journal d'activite de l'application
- **Console Asterisk** — acces CLI depuis l'interface web (fond terminal sombre)
- **Firewall SIP** — whitelist/blacklist IP, 3 modes (whitelist, fail2ban, off)
- **Fail2ban** — protection automatique contre le brute-force SIP

### Configuration
- **Parametres** avec onglets :
  - **SIP & Securite** — serveur, ports, transport, SRTP, TLS
  - **Email / SMTP** — configuration email avec test integre
  - **AI & TTS** — cle API OpenAI, modele, voix, VAD, budget, Piper TTS
- **Multi-langue** — interface francais/anglais avec switch drapeau, extensible

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | 8.4 (PHP-FPM) |
| Laravel | 13 |
| Asterisk | 20 (PJSIP Realtime, compile depuis les sources) |
| MariaDB | 10.11 |
| Redis | 7 |
| Nginx | 1.22 |
| Piper TTS | 1.2 (voix francaises siwis/upmc/mls) |
| OpenAI | GPT-4o Realtime / GPT-4o Mini Realtime |
| Python | 3.11 (websockets, AudioSocket) |

## Architecture (full-native, sans Docker)

Tous les services tournent nativement sur la VM :

```
Serveur Debian 12
├── Nginx           :443   → PHP-FPM (Laravel) + /ws → Asterisk:8088
├── PHP 8.4-FPM     socket → Application Laravel
├── Asterisk 20     :5060  → PBX SIP (PJSIP Realtime)
│   └── AudioSocket :9092  → voxa-ai (OpenAI Realtime bridge)
├── MariaDB         :3306  → Bases sip_manager + asterisk_rt
├── Redis           :6379  → Sessions, cache, queues
├── Piper TTS       /opt/piper → Synthese vocale locale
├── voxa-ai         systemd → AudioSocket server (Python/OpenAI)
├── Fail2ban                → Protection brute-force SIP
└── Let's Encrypt           → Certificats SSL automatiques
```

### Flux audio Agent IA

```
Appelant → Asterisk → AudioSocket(TCP:9092) → Python → OpenAI Realtime (WebSocket)
              ↕ audio full-duplex ↕                    ↕ audio full-duplex ↕
```

### Provisioning automatique

Toute modification dans l'interface (lignes, trunks, scenarios) est automatiquement :
1. Ecrite dans la base Realtime Asterisk (`asterisk_rt`)
2. Generee en fichiers de config (`pjsip.conf`, `extensions.conf`, `queues.conf`)
3. Rechargee dans Asterisk sans redemarrage

## Installation

### Installation automatique (recommande)

```bash
# Sur un serveur Debian 12 avec IP publique
curl -sSL https://raw.githubusercontent.com/grevoka/SIP.ctrl/main/install.sh | bash
```

Le script interactif demande :
- Le **nom de domaine** (ex: `sipctrl.example.com`)
- L'**email** pour Let's Encrypt

Puis installe automatiquement :
- MariaDB, Redis, PHP 8.4-FPM, Composer, Nginx
- Asterisk 20 (compile depuis les sources avec pjproject)
- Piper TTS + modele vocal francais
- ODBC, certificats SSL, Fail2ban
- Service systemd `voxa-ai` (AudioSocket server)

### Installation manuelle

```bash
git clone git@github.com:grevoka/SIP.ctrl.git /var/www/html
cd /var/www/html
composer install --no-dev
php artisan key:generate
php artisan migrate
```

### Assistant d'installation

Au premier lancement, ouvrir `https://<domaine>/install` :
1. **Prerequis** — verification automatique (PHP, extensions, permissions)
2. **Base de donnees** — configuration automatique
3. **Compte admin** — creation du compte administrateur
4. **Finalisation** — seed des donnees, creation du lock

## Ports

| Port | Protocole | Usage |
|------|-----------|-------|
| 443 | TCP | Interface web (HTTPS) |
| 80 | TCP | Redirect HTTP → HTTPS |
| 5060 | UDP/TCP | SIP signaling |
| 8088 | TCP | WebSocket (WebRTC softphone) |
| 9092 | TCP | AudioSocket (Agent IA, localhost only) |
| 10000-10100 | UDP | RTP media |

## Commandes utiles

```bash
# Services
systemctl status asterisk mariadb redis-server php8.4-fpm nginx voxa-ai

# Console Asterisk
asterisk -rvvv

# Status des endpoints / registrations
asterisk -rx "pjsip show endpoints"
asterisk -rx "pjsip show registrations"
asterisk -rx "pjsip show contacts"

# Reload configs
asterisk -rx "dialplan reload"
asterisk -rx "pjsip reload"

# Logs
tail -f /var/log/asterisk/full
tail -f /var/www/html/storage/logs/laravel.log
journalctl -u voxa-ai -f

# Fail2ban
fail2ban-client status asterisk-sip

# Cache Laravel
php8.4 artisan config:clear
php8.4 artisan view:clear
php8.4 artisan route:clear
systemctl restart php8.4-fpm
```

## Mise a jour

```bash
cd /var/www/html
git pull
composer install --no-dev
php8.4 artisan migrate --force
php8.4 artisan view:clear
systemctl restart php8.4-fpm voxa-ai
```

## Structure du projet

```
SIP.ctrl/
├── install.sh                        # Script d'installation full-native
├── sip-manager/                      # Application Laravel 13
│   ├── app/
│   │   ├── Http/Controllers/         # Dashboard, Trunk, CallFlow, AI, TTS, Recordings...
│   │   ├── Models/                   # SipLine, Trunk, CallFlow, CallerId, AiConversation...
│   │   └── Services/                 # SipProvisioningService, DialplanService
│   ├── resources/views/              # Blade templates (dark/light theme)
│   │   ├── dashboard/                # Stats, graphiques, raccourcis
│   │   ├── callflows/                # Builder visuel 2D
│   │   ├── operator/                 # Espace operateur + softphone WebRTC
│   │   ├── ai-history/               # Historique conversations IA
│   │   ├── ai-context/               # Base de connaissances RAG
│   │   ├── recordings/               # Enregistrements audio
│   │   ├── settings/                 # Parametres (onglets SIP/SMTP/AI)
│   │   └── caller-ids/               # Gestion Caller ID
│   ├── scripts/
│   │   ├── audiosocket-openai.py     # AudioSocket server (OpenAI Realtime)
│   │   └── openai-realtime.py        # EAGI script (legacy)
│   ├── lang/
│   │   ├── fr/ui.php                 # Traductions francais
│   │   └── en/ui.php                 # Traductions anglais
│   ├── routes/web.php                # Routes
│   └── database/migrations/          # Schema (26 migrations)
└── docker/                           # Config Docker (legacy, non utilise)
    ├── allinone/entrypoint.sh
    └── asterisk/configs/
```

## Licence

Projet prive — tous droits reserves.
