# // SIP.ctrl

**Plateforme de gestion de telephonie SIP / Asterisk PBX**

SIP.ctrl est une application web de gestion de PBX Asterisk deployable en un clic via Docker. Elle permet de configurer et superviser un systeme de telephonie IP complet depuis une interface moderne.

## Fonctionnalites

### Gestion SIP
- **Lignes SIP** — creation, edition, provisioning PJSIP Realtime automatique
- **Trunks SIP** — connexion aux fournisseurs (OVH, etc.) avec support proxy sortant, register/unregister
- **Codecs** — configuration alaw, ulaw, g722, opus

### Routage d'appels
- **Scenarios (Call Flows)** — editeur visuel drag & drop avec blocs : sonnerie, file d'attente, messagerie vocale, renvoi, IVR, musique d'attente, horaires, etc.
- **Routes sortantes** — regles de routage par pattern avec priorites
- **Contextes** — gestion des contextes Asterisk (entrant, sortant, interne)

### Services
- **Files d'attente** — configuration des queues avec strategies, musique d'attente, timeouts
- **Conferences** — salles de conference ConfBridge
- **Messagerie vocale** — ecoute, suppression, notification email SMTP
- **Musiques d'attente** — fichiers locaux, playlists personnalisees, flux streaming HTTP (ffmpeg)

### Monitoring & Securite
- **Dashboard** — graphique MRTG des appels (7 jours), stats du jour, derniers appels, raccourcis
- **Supervision live** — appels en cours en temps reel
- **Journal d'appels** — CDR avec filtres, recherche, pagination
- **Logs systeme** — journal d'activite de l'application
- **Console Asterisk** — acces CLI depuis l'interface web
- **Firewall SIP** — whitelist/blacklist IP, 3 modes (whitelist, fail2ban, off)
- **Fail2ban** — protection automatique contre le brute-force SIP

### Configuration
- **Parametres SIP** — serveur, ports, transport, securite (SRTP, TLS)
- **SMTP** — configuration email pour notifications de messagerie vocale
- **Profil** — changement de mot de passe administrateur

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | 8.4 |
| Laravel | 13 |
| Asterisk | 20 (PJSIP Realtime) |
| MariaDB | 10.11 |
| Redis | 7 |
| Nginx | 1.22 |
| Node.js | 20 (Vite) |

## Installation

### Installation automatique (recommande)

```bash
# Sur un serveur Debian/Ubuntu avec IP publique
curl -sSL https://raw.githubusercontent.com/grevoka/SIP.ctrl/main/install.sh | bash
```

Le script interactif demande :
- Le **nom de domaine** (ex: `sipctrl.example.com`)
- L'**email** pour Let's Encrypt

Puis installe automatiquement Docker, Nginx, Let's Encrypt, Fail2ban, clone le projet, build et lance le container.

### Installation manuelle

```bash
git clone git@github.com:grevoka/SIP.ctrl.git
cd SIP.ctrl
docker compose build
docker compose up -d
```

L'application est accessible sur le port `8080` par defaut.

### Assistant d'installation

Au premier lancement, ouvrir `https://<domaine>/install` :
1. **Prerequis** — verification automatique (PHP, extensions, permissions)
2. **Base de donnees** — auto-detectee en Docker, pas de configuration manuelle
3. **Compte admin** — creation du compte administrateur
4. **Finalisation** — seed des donnees, creation du lock

## Architecture Docker

Container all-in-one avec supervisord :

```
Container (sip-manager)
|-- Nginx            :80    -> Interface web
|-- PHP-FPM          :9000  -> Application Laravel
|-- Asterisk 20      :5060  -> PBX SIP (PJSIP Realtime)
|-- MariaDB          :3306  -> Bases sip_manager + asterisk_rt
|-- Redis            :6379  -> Sessions, cache, queues
|-- Fail2ban                -> Protection brute-force SIP
|-- Queue Worker            -> Jobs asynchrones
|-- Cron                    -> Scheduler (CDR sync chaque minute)
`-- Post-start              -> Reload ODBC/PJSIP apres MySQL ready
```

Les mots de passe (DB, AMI) sont generes aleatoirement au premier lancement et persistes sur le volume MariaDB.

### Provisioning automatique

Toute modification dans l'interface (lignes, trunks, scenarios) est automatiquement :
1. Ecrite dans la base Realtime Asterisk (`asterisk_rt`)
2. Generee en fichiers de config (`pjsip.conf`, `extensions.conf`, `queues.conf`)
3. Rechargee dans Asterisk sans redemarrage

## Ports

| Port | Protocole | Usage |
|------|-----------|-------|
| 80 (8080) | TCP | Interface web |
| 5060 | UDP/TCP | SIP signaling |
| 5061 | TCP | SIP TLS |
| 10000-10100 | UDP | RTP media |

## Configuration d'un trunk SIP (OVH)

1. **Trunks** > **Creer**
2. Remplir :
   - **Nom** : `OVH`
   - **Host** : `sip-domain.io` (domaine OVH)
   - **Proxy sortant** : `ml835941-ovh-1.sip-proxy.io` (proxy OVH)
   - **Username** : numero SIP (ex: `0033185090002`)
   - **Secret** : mot de passe SIP (defini dans le manager OVH)
   - **Registration** : Oui
   - **IPs entrantes** : `91.121.129.0/24`, `91.121.128.0/24`

## Commandes utiles

```bash
# Console Asterisk
docker exec -it sip-manager asterisk -rvvv

# Status des endpoints / registrations
docker exec sip-manager asterisk -rx "pjsip show endpoints"
docker exec sip-manager asterisk -rx "pjsip show registrations"

# Reload configs
docker exec sip-manager asterisk -rx "dialplan reload"
docker exec sip-manager asterisk -rx "pjsip reload"

# Logs
docker compose logs -f
docker exec sip-manager tail -f /var/www/html/storage/logs/laravel.log

# Fail2ban status
fail2ban-client status asterisk-sip
```

## Persistance

| Donnee | Stockage | Persistant |
|--------|----------|------------|
| Base MariaDB | Volume `mariadb-data` | Oui |
| Code Laravel | Bind mount `./sip-manager` | Oui |
| Configs Asterisk | Generes au demarrage | Regeneres automatiquement |
| Mots de passe | `/var/lib/mysql/.sip_passwords` | Oui |

## Mise a jour

```bash
cd /var/www/<domaine>
git pull
docker compose build
docker compose up -d
```

Les migrations s'executent automatiquement au demarrage.

## Structure du projet

```
SIP.ctrl/
  Dockerfile                    # Image Docker all-in-one
  docker-compose.yml            # Orchestration
  install.sh                    # Script d'installation automatique
  docker/
    allinone/
      entrypoint.sh             # Init DB, ODBC, .env, migrations
      supervisord.conf          # Processus supervises
      asterisk-post-start.sh    # Reload ODBC/PJSIP post-MySQL
      fail2ban/                 # Filtres et jails
    asterisk/configs/           # Config Asterisk de base
    nginx/                      # Vhost Nginx
    php/                        # PHP-FPM + OPcache
  sip-manager/                  # Application Laravel 13
    app/
      Http/Controllers/         # Dashboard, Trunk, CallFlow, MOH, Firewall...
      Models/                   # SipLine, Trunk, CallFlow, MohPlaylist...
      Services/                 # SipProvisioningService, DialplanService
    resources/views/            # Blade templates (dark theme)
    routes/web.php              # Routes
    database/migrations/        # Schema (20 migrations)
```

## Licence

Projet prive — tous droits reserves.
