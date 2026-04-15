#!/bin/bash
#
# Voxa Center — Script d'installation automatique (full-native, sans Docker)
# Usage: curl -sSL https://raw.githubusercontent.com/grevoka/SIP.ctrl/main/install.sh | bash
#    ou: bash install.sh
#
set -e

# ── Couleurs ──
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

log()  { echo -e "${GREEN}[Voxa Center]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()  { echo -e "${RED}[ERREUR]${NC} $1"; exit 1; }

# ── Verifier root ──
if [ "$EUID" -ne 0 ]; then
    err "Ce script doit etre lance en root (sudo bash install.sh)"
fi

echo ""
echo -e "${CYAN}${BOLD}"
echo "  ╔══════════════════════════════════════╗"
echo "  ║     Voxa Center — Installation       ║"
echo "  ║     Telecom Management Platform      ║"
echo "  ╚══════════════════════════════════════╝"
echo -e "${NC}"
echo ""

# ── Demander le hostname ──
read -p "Nom de domaine (ex: sipctrl.example.com): " HOSTNAME < /dev/tty
if [ -z "$HOSTNAME" ]; then
    err "Le nom de domaine est obligatoire."
fi

read -p "Email pour Let's Encrypt (ex: admin@example.com): " LE_EMAIL < /dev/tty
if [ -z "$LE_EMAIL" ]; then
    err "L'email est obligatoire pour Let's Encrypt."
fi

INSTALL_DIR="/var/www/html"

echo ""
log "Configuration:"
echo "  Domaine:     ${HOSTNAME}"
echo "  Email:       ${LE_EMAIL}"
echo "  Repertoire:  ${INSTALL_DIR}"
echo ""
read -p "Continuer ? [O/n] " CONFIRM < /dev/tty
if [[ "$CONFIRM" =~ ^[nN] ]]; then
    echo "Annule."; exit 0
fi

# ── Detect OS ──
if [ ! -f /etc/os-release ]; then
    err "OS non supporte. Debian 12 ou Ubuntu 22.04/24.04 requis."
fi

. /etc/os-release

SUPPORTED=false
if [ "$ID" = "debian" ] && [ "$VERSION_ID" = "12" ]; then
    SUPPORTED=true
elif [ "$ID" = "ubuntu" ] && [[ "$VERSION_ID" =~ ^(22.04|24.04)$ ]]; then
    SUPPORTED=true
fi

if [ "$SUPPORTED" = false ]; then
    err "OS non supporte: $PRETTY_NAME. Systemes compatibles: Debian 12 (Bookworm), Ubuntu 22.04 (Jammy), Ubuntu 24.04 (Noble)."
fi

OS="$ID"
log "OS detecte: $PRETTY_NAME"

# ══════════════════════════════════════
# Phase 1 : Paquets systeme
# ══════════════════════════════════════
log "Mise a jour des paquets..."
apt-get update -qq

log "Installation des dependances de base..."
DEBIAN_FRONTEND=noninteractive apt-get install -yqq \
    nginx certbot python3-certbot-nginx git fail2ban curl wget \
    mariadb-server redis-server \
    build-essential libssl-dev libncurses5-dev libnewt-dev libxml2-dev \
    libsqlite3-dev uuid-dev libjansson-dev libsrtp2-dev libcurl4-openssl-dev \
    libedit-dev unixodbc-dev odbc-mariadb sox mpg123 ffmpeg > /dev/null

# ── PHP 8.4 via sury.org ──
if ! php8.4 -v &>/dev/null; then
    log "Installation de PHP 8.4..."
    curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
    dpkg -i /tmp/debsuryorg-archive-keyring.deb
    echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt-get update -qq
    DEBIAN_FRONTEND=noninteractive apt-get install -yqq \
        php8.4-fpm php8.4-cli php8.4-mysql php8.4-redis php8.4-xml \
        php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-intl \
        php8.4-gd php8.4-opcache php8.4-readline php8.4-odbc > /dev/null
    log "PHP 8.4 installe."
else
    log "PHP 8.4 deja installe."
fi

# ── Composer ──
if ! command -v composer &>/dev/null; then
    log "Installation de Composer..."
    curl -sS https://getcomposer.org/installer | php8.4 -- --install-dir=/usr/local/bin --filename=composer > /dev/null
fi

# ── Demarrer les services ──
systemctl enable --now mariadb redis-server nginx php8.4-fpm

log "Dependances installees."

# ══════════════════════════════════════
# Phase 2 : MariaDB — initialisation
# ══════════════════════════════════════
log "Configuration de MariaDB..."

# Generate password
DB_PASS=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)

mysql -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS sip_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS asterisk_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_PASS}';
    FLUSH PRIVILEGES;
EOSQL

# ── Creer les tables PJSIP Realtime ──
mysql -u root -p"${DB_PASS}" asterisk_rt <<-'EORT'
    CREATE TABLE IF NOT EXISTS ps_endpoints (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        transport VARCHAR(40), aors VARCHAR(200), auth VARCHAR(40),
        outbound_auth VARCHAR(40), context VARCHAR(40),
        disallow VARCHAR(200), allow VARCHAR(200),
        direct_media VARCHAR(3), force_rport VARCHAR(3),
        rewrite_contact VARCHAR(3), rtp_symmetric VARCHAR(3),
        callerid VARCHAR(200), dtmf_mode VARCHAR(10),
        media_encryption VARCHAR(10), ice_support VARCHAR(3),
        from_user VARCHAR(40), from_domain VARCHAR(40),
        trust_id_inbound VARCHAR(3),
        device_state_busy_at INT DEFAULT 1,
        language VARCHAR(10) DEFAULT 'fr',
        mailboxes VARCHAR(200),
        rtcp_mux VARCHAR(3), use_avpf VARCHAR(3),
        media_use_received_transport VARCHAR(3),
        dtls_auto_generate_cert VARCHAR(3), dtls_verify VARCHAR(20),
        dtls_setup VARCHAR(20), media_address VARCHAR(40),
        bind_rtp_to_media_address VARCHAR(3)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_auths (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        auth_type VARCHAR(10), username VARCHAR(40),
        password VARCHAR(80), realm VARCHAR(40)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_aors (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        max_contacts INT DEFAULT 1,
        remove_existing VARCHAR(3) DEFAULT 'yes',
        default_expiration INT DEFAULT 3600,
        qualify_frequency INT DEFAULT 60,
        contact VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_registrations (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        transport VARCHAR(40), outbound_auth VARCHAR(40),
        outbound_proxy VARCHAR(255),
        server_uri VARCHAR(255), client_uri VARCHAR(255),
        retry_interval INT DEFAULT 60, expiration INT DEFAULT 3600,
        contact_user VARCHAR(40), line VARCHAR(3),
        endpoint VARCHAR(40),
        auth_rejection_permanent VARCHAR(3) DEFAULT 'no'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_domain_aliases (
        id VARCHAR(40) NOT NULL PRIMARY KEY, domain VARCHAR(80)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_endpoint_id_ips (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        endpoint VARCHAR(40), `match` VARCHAR(80)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS cdr (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        calldate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        clid VARCHAR(80) DEFAULT '', src VARCHAR(80) DEFAULT '',
        dst VARCHAR(80) DEFAULT '', dcontext VARCHAR(80) DEFAULT '',
        channel VARCHAR(80) DEFAULT '', dstchannel VARCHAR(80) DEFAULT '',
        lastapp VARCHAR(80) DEFAULT '', lastdata VARCHAR(80) DEFAULT '',
        duration INT DEFAULT 0, billsec INT DEFAULT 0,
        disposition VARCHAR(45) DEFAULT '', amaflags INT DEFAULT 0,
        accountcode VARCHAR(20) DEFAULT '', uniqueid VARCHAR(150) DEFAULT '',
        userfield VARCHAR(255) DEFAULT '',
        INDEX idx_calldate (calldate), INDEX idx_src (src), INDEX idx_dst (dst)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EORT

log "Base de donnees initialisee."

# ══════════════════════════════════════
# Phase 3 : Asterisk (compilation depuis les sources)
# ══════════════════════════════════════
if command -v asterisk &>/dev/null; then
    log "Asterisk deja installe: $(asterisk -V 2>/dev/null || echo 'version inconnue')"
else
    log "Compilation d'Asterisk 20 (peut prendre 5-10 min)..."

    AST_VERSION="20.13.0"
    cd /usr/src
    if [ ! -d "asterisk-${AST_VERSION}" ]; then
        wget -q "https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${AST_VERSION}.tar.gz"
        tar xzf "asterisk-${AST_VERSION}.tar.gz"
        rm -f "asterisk-${AST_VERSION}.tar.gz"
    fi
    cd "asterisk-${AST_VERSION}"

    contrib/scripts/install_prereq install > /dev/null 2>&1 || true
    ./configure --with-jansson-bundled --with-pjproject-bundled > /dev/null 2>&1
    make menuselect.makeopts
    menuselect/menuselect --enable codec_opus menuselect.makeopts 2>/dev/null || true
    make -j$(nproc) > /dev/null 2>&1
    make install > /dev/null 2>&1
    make samples > /dev/null 2>&1

    log "Asterisk compile et installe."
fi

# ── Detect public IP ──
PUBLIC_IP=$(curl -s --max-time 5 https://api.ipify.org 2>/dev/null || echo "127.0.0.1")
log "IP publique detectee: ${PUBLIC_IP}"

# ── Asterisk config (pjsip, rtp, http, odbc) ──
log "Configuration d'Asterisk..."

cat > /etc/asterisk/pjsip.conf << PJEOF
[global]
type = global
max_forwards = 70
user_agent = Voxa-PBX
keep_alive_interval = 90
endpoint_identifier_order = ip,username,anonymous
default_from_user = voxa
default_realm = ${PUBLIC_IP}

[transport-udp]
type = transport
protocol = udp
bind = 0.0.0.0:5060
local_net = 127.0.0.0/8
local_net = 10.0.0.0/8
local_net = 172.16.0.0/12
local_net = 192.168.0.0/16
external_media_address = ${PUBLIC_IP}
external_signaling_address = ${PUBLIC_IP}

[transport-tcp]
type = transport
protocol = tcp
bind = 0.0.0.0:5060
local_net = 127.0.0.0/8
local_net = 10.0.0.0/8
local_net = 172.16.0.0/12
local_net = 192.168.0.0/16
external_media_address = ${PUBLIC_IP}
external_signaling_address = ${PUBLIC_IP}

[transport-wss]
type = transport
protocol = wss
bind = 0.0.0.0:8089
local_net = 127.0.0.0/8
local_net = 10.0.0.0/8
local_net = 172.16.0.0/12
local_net = 192.168.0.0/16
external_media_address = ${PUBLIC_IP}
external_signaling_address = ${PUBLIC_IP}
allow_reload = yes

; === AUTO-GENERATED TRUNKS BY Voxa Center ===
PJEOF

cat > /etc/asterisk/rtp.conf << RTPEOF
[general]
rtpstart=10000
rtpend=10100
strictrtp=no
icesupport=yes
stunaddr=stun.l.google.com:19302
ice_blacklist=172.16.0.0/12
ice_blacklist=10.0.0.0/8
ice_blacklist=192.168.0.0/16
ice_blacklist=fe80::/10
ice_blacklist=::1/128
RTPEOF

cat > /etc/asterisk/http.conf << HTTPEOF
[general]
enabled=yes
bindaddr=0.0.0.0
bindport=8088
HTTPEOF

# ── ODBC ──
ODBC_LIB=$(find /usr/lib -name 'libmaodbc.so' 2>/dev/null | head -1)
[ -z "$ODBC_LIB" ] && ODBC_LIB="/usr/lib/odbc/libmaodbc.so"

cat > /etc/odbcinst.ini << EOF
[MariaDB]
Description = MariaDB ODBC Connector
Driver      = ${ODBC_LIB}
UsageCount  = 1
EOF

cat > /etc/odbc.ini << EOF
[asterisk-connector]
Description = Asterisk Realtime
Driver      = MariaDB
Server      = 127.0.0.1
Port        = 3306
Database    = asterisk_rt
User        = root
Password    = ${DB_PASS}
Option      = 3
EOF

cat > /etc/asterisk/res_odbc.conf << ODBCEOF
[asterisk]
enabled => yes
dsn => asterisk-connector
username => root
password => ${DB_PASS}
pre-connect => yes
max_connections => 5
ODBCEOF

cat > /etc/asterisk/sorcery.conf << 'SORCEOF'
[res_pjsip]
endpoint=realtime,ps_endpoints
auth=realtime,ps_auths
aor=realtime,ps_aors
domain_alias=realtime,ps_domain_aliases
contact=astdb,registrar
registration=realtime,ps_registrations

[res_pjsip_endpoint_identifier_ip]
identify=realtime,ps_endpoint_id_ips
SORCEOF

cat > /etc/asterisk/extconfig.conf << 'EXTEOF'
[settings]
ps_endpoints => odbc,asterisk,ps_endpoints
ps_auths => odbc,asterisk,ps_auths
ps_aors => odbc,asterisk,ps_aors
ps_registrations => odbc,asterisk,ps_registrations
ps_domain_aliases => odbc,asterisk,ps_domain_aliases
ps_endpoint_id_ips => odbc,asterisk,ps_endpoint_id_ips
EXTEOF

# ── AMI config ──
AMI_PASS=$(head -c 16 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 16)
cat > /etc/asterisk/manager.conf << AMIEOF
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1

[laravel_ami]
secret = ${AMI_PASS}
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
read = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
AMIEOF

# ── Permissions ──
chown -R root:root /etc/asterisk /var/lib/asterisk /var/log/asterisk /var/spool/asterisk
chmod -R o+r /var/log/asterisk

# ── Sudoers for www-data ──
cat > /etc/sudoers.d/asterisk-cli << 'SUDOEOF'
www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk, /usr/bin/tee /etc/asterisk/extensions.conf, /usr/bin/tee /etc/asterisk/queues.conf, /usr/bin/tee /etc/asterisk/pjsip.conf, /usr/bin/tee /etc/asterisk/musiconhold.conf
SUDOEOF
chmod 0440 /etc/sudoers.d/asterisk-cli
chown www-data:www-data /etc/asterisk/extensions.conf /etc/asterisk/queues.conf /etc/asterisk/pjsip.conf 2>/dev/null || true
chmod 664 /etc/asterisk/extensions.conf /etc/asterisk/queues.conf /etc/asterisk/pjsip.conf 2>/dev/null || true

# ── Systemd service ──
cat > /etc/systemd/system/asterisk.service << 'ASTSERVICE'
[Unit]
Description=Asterisk PBX
After=network.target mariadb.service
Wants=mariadb.service

[Service]
Type=simple
ExecStart=/usr/sbin/asterisk -f -U root -G root
ExecReload=/usr/sbin/asterisk -rx "core reload"
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
ASTSERVICE
systemctl daemon-reload
systemctl enable asterisk
systemctl start asterisk

log "Asterisk configure et demarre."

# ══════════════════════════════════════
# Phase 4 : Application Laravel
# ══════════════════════════════════════
log "Deploiement de l'application Laravel..."

cd /var/www
if [ -d "${INSTALL_DIR}/.git" ]; then
    cd "$INSTALL_DIR"
    git pull || warn "Git pull echoue."
else
    # Clone sip-manager subdirectory
    git clone https://github.com/grevoka/SIP.ctrl.git /tmp/voxa-src 2>/dev/null || true
    rm -rf "${INSTALL_DIR}"
    cp -r /tmp/voxa-src/sip-manager "${INSTALL_DIR}"
    rm -rf /tmp/voxa-src
fi

cd "$INSTALL_DIR"

# ── .env ──
cat > .env << ENVEOF
APP_NAME="Voxa Center"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${HOSTNAME}

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sip_manager
DB_USERNAME=root
DB_PASSWORD=${DB_PASS}

DB_AST_CONNECTION=asterisk
DB_AST_HOST=127.0.0.1
DB_AST_PORT=3306
DB_AST_DATABASE=asterisk_rt
DB_AST_USERNAME=root
DB_AST_PASSWORD=${DB_PASS}

ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USER=laravel_ami
ASTERISK_AMI_SECRET=${AMI_PASS}

SESSION_DRIVER=redis
SESSION_LIFETIME=120
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log

SIP_DEFAULT_CONTEXT=from-internal
SIP_DEFAULT_TRANSPORT=transport-udp
SIP_DEFAULT_CODECS=alaw,ulaw,g722
ENVEOF

# ── Install deps, generate key, migrate ──
COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction --no-dev 2>&1 | tail -3
php8.4 artisan key:generate --force

# ── Permissions ──
chown -R www-data:www-data storage bootstrap/cache .env
chmod -R 775 storage bootstrap/cache
php8.4 artisan storage:link 2>/dev/null || true

# ── Migrations ──
php8.4 artisan migrate --force
php8.4 artisan config:clear
php8.4 artisan cache:clear

log "Laravel deploye."

# ══════════════════════════════════════
# Phase 5 : PHP-FPM
# ══════════════════════════════════════
log "Configuration de PHP-FPM..."

cat > /etc/php/8.4/fpm/pool.d/voxa.conf << 'FPMEOF'
[voxa]
user = www-data
group = www-data
listen = /run/php/php8.4-voxa.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500
php_admin_flag[log_errors] = on
php_value[upload_max_filesize] = 50M
php_value[post_max_size] = 50M
php_value[memory_limit] = 256M
php_value[max_execution_time] = 120
FPMEOF

systemctl restart php8.4-fpm
log "PHP-FPM configure."

# ══════════════════════════════════════
# Phase 6 : Nginx (PHP-FPM + WSS)
# ══════════════════════════════════════
log "Configuration du vhost Nginx..."

VHOST_FILE="/etc/nginx/sites-available/${HOSTNAME}"
cat > "$VHOST_FILE" << 'NGINXEOF'
server {
    listen 80;
    server_name HOSTNAME_PLACEHOLDER;

    root /var/www/html/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # WebSocket proxy → Asterisk (port 8088)
    location /ws {
        proxy_pass http://127.0.0.1:8088;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-voxa.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 50M;
}
NGINXEOF

sed -i "s|HOSTNAME_PLACEHOLDER|${HOSTNAME}|g" "$VHOST_FILE"

ln -sf "$VHOST_FILE" "/etc/nginx/sites-enabled/${HOSTNAME}"
rm -f /etc/nginx/sites-enabled/default
nginx -t 2>&1 || err "Configuration Nginx invalide."
systemctl reload nginx
log "Nginx configure."

# ══════════════════════════════════════
# Phase 7 : Let's Encrypt SSL
# ══════════════════════════════════════
log "Configuration du certificat SSL..."
certbot --nginx -d "$HOSTNAME" --email "$LE_EMAIL" --agree-tos --non-interactive --redirect 2>&1 || {
    warn "Certbot a echoue — verifiez que le DNS pointe vers ce serveur."
    warn "Relancez: certbot --nginx -d ${HOSTNAME}"
}

# ══════════════════════════════════════
# Phase 8 : Fail2ban
# ══════════════════════════════════════
log "Configuration de Fail2ban..."

cat > /etc/fail2ban/filter.d/asterisk-sip.conf << 'F2BFILTER'
[Definition]
failregex = NOTICE.* Request .* failed for '<HOST>(:\d+)?' .* - No matching endpoint found
            NOTICE.* Request .* failed for '<HOST>(:\d+)?' .* - Failed to authenticate
ignoreregex =
F2BFILTER

cat > /etc/fail2ban/jail.local << 'F2BJAIL'
[DEFAULT]
bantime = 600
findtime = 300
maxretry = 5

[sshd]
enabled = false

[asterisk-sip]
enabled  = true
filter   = asterisk-sip
logpath  = /var/log/asterisk/messages
maxretry = 5
findtime = 300
bantime  = 600
action   = iptables-allports[name=asterisk-sip]
ignoreip = 127.0.0.1/8 91.121.128.0/23
F2BJAIL

systemctl restart fail2ban 2>/dev/null || true
log "Fail2ban configure."

# ══════════════════════════════════════
# Termine !
# ══════════════════════════════════════
echo ""
echo -e "${GREEN}${BOLD}══════════════════════════════════════════${NC}"
echo -e "${GREEN}${BOLD}  Installation terminee !${NC}"
echo -e "${GREEN}${BOLD}══════════════════════════════════════════${NC}"
echo ""
echo -e "  URL:        ${CYAN}https://${HOSTNAME}${NC}"
echo -e "  Repertoire: ${INSTALL_DIR}"
echo ""
echo -e "  ${YELLOW}Ouvrez https://${HOSTNAME}/install pour finaliser${NC}"
echo -e "  ${YELLOW}la configuration (compte admin).${NC}"
echo ""
echo -e "  Architecture (full-native, sans Docker):"
echo -e "    Asterisk PBX:   systemctl status asterisk"
echo -e "    MariaDB:        systemctl status mariadb"
echo -e "    Redis:          systemctl status redis-server"
echo -e "    PHP-FPM:        systemctl status php8.4-fpm"
echo -e "    Nginx:          /ws → Asterisk:8088, / → PHP-FPM"
echo ""
echo -e "  Commandes utiles:"
echo -e "    asterisk -rvvv"
echo -e "    tail -f /var/log/asterisk/messages"
echo -e "    fail2ban-client status asterisk-sip"
echo -e "    journalctl -u asterisk -f"
echo ""
echo -e "  Mot de passe DB: ${YELLOW}${DB_PASS}${NC}"
echo -e "  (sauvegarde dans ${INSTALL_DIR}/.env)"
echo ""
