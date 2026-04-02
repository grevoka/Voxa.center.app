# SIP.ctrl — PBX Asterisk + Laravel

PBX SIP complet dans un seul container Docker : Asterisk 20, Laravel, MariaDB, Redis, Nginx.

## Prerequis

- **OS** : Linux (Ubuntu/Debian recommande) avec IP publique
- **Docker** >= 24 + Docker Compose v2
- **Ports ouverts** sur le firewall :

| Port | Protocole | Usage |
|------|-----------|-------|
| 8080 | TCP | Interface web |
| 5060 | UDP + TCP | SIP signaling |
| 10000-10100 | UDP | RTP media (audio) |
| 3307 | TCP | MariaDB (optionnel, acces externe) |

## Installation

### 1. Cloner le projet

```bash
git clone <repo-url> sip-manager
cd sip-manager
```

### 2. Configurer l'environnement

Editer le fichier `.env` :

```env
# Mot de passe root MariaDB
DB_ROOT_PASSWORD=wawa2346

# Laisser vide pour auto-detection de l'IP publique
# Ou forcer une IP specifique
PUBLIC_IP=

# Credentials AMI Asterisk
AMI_USER=laravel_ami
AMI_PASSWORD=ami_secret
```

> Si `PUBLIC_IP` est vide, l'IP publique est detectee automatiquement au demarrage via `api.ipify.org`.

### 3. Production (serveur Linux avec IP publique)

Pour un serveur avec IP publique directe, utiliser `network_mode: host` dans `docker-compose.yml` pour eviter les problemes de NAT SIP :

```yaml
services:
  sip:
    network_mode: host
    # Supprimer la section "ports:" (inutile en host mode)
```

Les services seront directement accessibles sur les ports de la machine.

### 4. Build et lancement

```bash
docker compose build
docker compose up -d
```

Le premier demarrage prend environ 2 minutes :
- Initialisation MariaDB (bases `sip_manager` + `asterisk_rt`)
- Creation des tables PJSIP Realtime
- Installation des dependances Composer
- Migrations Laravel
- Generation automatique des configs Asterisk (pjsip.conf, extensions.conf, queues.conf)

### 5. Verifier le demarrage

```bash
docker logs -f sip-manager

# Attendre ces messages :
# [SIP] All ready — starting all services via supervisor...
# success: asterisk entered RUNNING state
```

### 6. Acceder a l'interface

Ouvrir `http://<IP_SERVEUR>:8080`

Compte par defaut :
- **Email** : `test@example.com`
- **Mot de passe** : `password`

> Changer le mot de passe immediatement apres la premiere connexion.

## Architecture

```
Container unique (sip-manager)
|-- Nginx          :80    -> Interface web Laravel
|-- PHP-FPM        :9000  -> Application backend
|-- MariaDB        :3306  -> Base de donnees (sip_manager + asterisk_rt)
|-- Redis          :6379  -> Cache + queues
|-- Asterisk 20    :5060  -> PBX SIP (PJSIP)
|-- Queue Worker          -> Jobs asynchrones Laravel
`-- Cron                  -> Scheduler Laravel
```

### Provisioning automatique

Toute modification dans l'interface (lignes SIP, trunks, call flows) est automatiquement :
1. Ecrite dans la base Realtime Asterisk (`asterisk_rt`)
2. Generee en fichiers de config (`pjsip.conf`, `extensions.conf`, `queues.conf`)
3. Rechargee dans Asterisk (sans redemarrage)

## Configuration d'un trunk SIP (ex: OVH)

1. Dans l'interface web : **Trunks** > **Creer**
2. Remplir :
   - **Nom** : `OVH-SIP`
   - **Host** : `sbc6.fr.sip.ovh` (ou votre SBC OVH)
   - **Port** : `5060`
   - **Username** : votre numero SIP OVH (ex: `0033185090002`)
   - **Secret** : mot de passe SIP
   - **Registration** : Oui
   - **IPs entrantes** : `91.121.129.0/24`, `91.121.128.0/24`
3. Le trunk est automatiquement provisionne dans Asterisk

### Verification

```bash
docker exec sip-manager asterisk -rx "pjsip show registrations"
docker exec sip-manager asterisk -rx "pjsip show identifies"
docker exec sip-manager asterisk -rx "pjsip show endpoints"
```

## Configuration d'une ligne SIP

1. **Lignes SIP** > **Creer**
2. Remplir extension (ex: `1001`), nom, mot de passe
3. Configurer le softphone (Linphone, Zoiper...) :
   - **Serveur** : `<IP_SERVEUR>`
   - **Username** : `1001`
   - **Password** : le mot de passe defini
   - **Transport** : TCP ou UDP

## Scenarios d'appels (Call Flows)

1. **Call Flows** > **Creer**
2. Associer au trunk entrant
3. Definir les etapes :
   - `answer` — Decrocher
   - `ring` — Sonner des postes
   - `queue` — File d'attente
   - `voicemail` — Repondeur
   - `playback` — Jouer un son
   - `hangup` — Raccrocher
4. Activer : le dialplan est genere et recharge automatiquement

## Commandes utiles

```bash
# Console Asterisk en direct
docker exec sip-manager asterisk -rvvv

# Status des endpoints
docker exec sip-manager asterisk -rx "pjsip show endpoints"

# Status des registrations
docker exec sip-manager asterisk -rx "pjsip show registrations"

# Recharger le dialplan
docker exec sip-manager asterisk -rx "dialplan reload"

# Recharger PJSIP
docker exec sip-manager asterisk -rx "pjsip reload"

# Logs Laravel
docker exec sip-manager tail -f storage/logs/laravel.log

# Console MySQL
docker exec sip-manager mysql -u root -pwawa2346 sip_manager

# Redemarrer Asterisk seul
docker exec sip-manager asterisk -rx "core restart now"
```

## Persistance des donnees

| Donnee | Stockage | Persistant |
|--------|----------|------------|
| Base MariaDB | Volume `mariadb-data` | Oui |
| Code Laravel | Bind mount `./sip-manager` | Oui |
| Configs Asterisk | Generes au demarrage | Regeneres automatiquement |
| Logs Asterisk | `/var/log/asterisk/` | Non (perdu au rebuild) |

### Backup

```bash
# Dump de la base
docker exec sip-manager mysqldump -u root -pwawa2346 --databases sip_manager asterisk_rt > backup.sql

# Restore
docker exec -i sip-manager mysql -u root -pwawa2346 < backup.sql
```

## Mise a jour

```bash
git pull
docker compose build
docker compose up -d
```

Les migrations Laravel s'executent automatiquement au demarrage. Les configs Asterisk sont regenerees depuis la base de donnees.

## Troubleshooting

### Registration trunk "Rejected" ou "No response"

- Verifier que le port **5060/UDP** est ouvert dans le firewall
- Verifier les credentials du trunk dans l'interface
- Sur Docker Desktop (Mac/Windows) : la registration UDP ne fonctionne pas a cause du NAT de la VM Docker. Deployer sur un serveur Linux

### Appels entrants "Failed to authenticate"

- Verifier que les **IPs entrantes** du trunk sont correctes
- Verifier : `docker exec sip-manager asterisk -rx "pjsip show identifies"`

### Poste SIP ne s'enregistre pas

- Verifier que le softphone utilise le bon username (= extension, ex: `1001`)
- Tester en TCP si UDP ne fonctionne pas

### Le scenario ne s'applique pas

- Verifier que le Call Flow est **active** dans l'interface
- Verifier le contexte : `docker exec sip-manager asterisk -rx "dialplan show <context>"`
