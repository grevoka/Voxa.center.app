#!/bin/bash
#
# Voxa Center — Script d'installation automatique
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
echo "  ║     Voxa Center — Installation          ║"
echo "  ║     Telecom Management Platform      ║"
echo "  ╚══════════════════════════════════════╝"
echo -e "${NC}"
echo ""

# ── Demander le hostname ──
read -p "Nom de domaine (ex: sipctrl.example.com): " HOSTNAME
if [ -z "$HOSTNAME" ]; then
    err "Le nom de domaine est obligatoire."
fi

read -p "Email pour Let's Encrypt (ex: admin@example.com): " LE_EMAIL
if [ -z "$LE_EMAIL" ]; then
    err "L'email est obligatoire pour Let's Encrypt."
fi

read -p "Port HTTP du container Docker [8080]: " DOCKER_PORT
DOCKER_PORT=${DOCKER_PORT:-8080}

INSTALL_DIR="/var/www/${HOSTNAME}"

echo ""
log "Configuration:"
echo "  Domaine:     ${HOSTNAME}"
echo "  Email:       ${LE_EMAIL}"
echo "  Port Docker: ${DOCKER_PORT}"
echo "  Repertoire:  ${INSTALL_DIR}"
echo ""
read -p "Continuer ? [O/n] " CONFIRM
if [[ "$CONFIRM" =~ ^[nN] ]]; then
    echo "Annule."; exit 0
fi

# ══════════════════════════════════════
# Phase 1 : Dependances systeme
# ══════════════════════════════════════
log "Verification des dependances..."

# Detect OS
if [ -f /etc/debian_version ]; then
    OS="debian"
elif [ -f /etc/redhat-release ]; then
    OS="redhat"
else
    err "OS non supporte. Debian/Ubuntu ou RHEL/CentOS requis."
fi

# Update package list
log "Mise a jour des paquets..."
if [ "$OS" = "debian" ]; then
    apt-get update -qq
else
    yum check-update -q || true
fi

# ── Docker ──
if command -v docker &>/dev/null; then
    log "Docker deja installe: $(docker --version)"
else
    log "Installation de Docker..."
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq ca-certificates curl gnupg > /dev/null
        install -m 0755 -d /etc/apt/keyrings
        curl -fsSL https://download.docker.com/linux/$(. /etc/os-release && echo "$ID")/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg 2>/dev/null
        chmod a+r /etc/apt/keyrings/docker.gpg
        echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/$(. /etc/os-release && echo "$ID") $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list
        apt-get update -qq
        apt-get install -yqq docker-ce docker-ce-cli containerd.io docker-compose-plugin > /dev/null
    else
        yum install -y yum-utils > /dev/null
        yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo > /dev/null
        yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin > /dev/null
    fi
    systemctl enable docker
    systemctl start docker
    log "Docker installe avec succes."
fi

# ── Docker Compose plugin ──
if docker compose version &>/dev/null; then
    log "Docker Compose disponible."
else
    err "Docker Compose plugin non trouve. Installez docker-compose-plugin."
fi

# ── Nginx ──
if command -v nginx &>/dev/null; then
    log "Nginx deja installe."
else
    log "Installation de Nginx..."
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq nginx > /dev/null
    else
        yum install -y nginx > /dev/null
    fi
    systemctl enable nginx
    systemctl start nginx
    log "Nginx installe."
fi

# ── Certbot (Let's Encrypt) ──
if command -v certbot &>/dev/null; then
    log "Certbot deja installe."
else
    log "Installation de Certbot..."
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq certbot python3-certbot-nginx > /dev/null
    else
        yum install -y certbot python3-certbot-nginx > /dev/null
    fi
    log "Certbot installe."
fi

# ── Git ──
if command -v git &>/dev/null; then
    log "Git disponible."
else
    log "Installation de Git..."
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq git > /dev/null
    else
        yum install -y git > /dev/null
    fi
fi

# ── Fail2ban ──
if command -v fail2ban-server &>/dev/null; then
    log "Fail2ban deja installe."
else
    log "Installation de Fail2ban..."
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq fail2ban > /dev/null
    else
        yum install -y fail2ban > /dev/null
    fi
    systemctl enable fail2ban
    log "Fail2ban installe."
fi

# ══════════════════════════════════════
# Phase 2 : Cloner le projet
# ══════════════════════════════════════
if [ -d "$INSTALL_DIR/.git" ]; then
    log "Repertoire existant, mise a jour..."
    cd "$INSTALL_DIR"
    git pull || warn "Git pull echoue — utilisation des fichiers existants."
elif [ -d "$INSTALL_DIR/docker-compose.yml" ] || [ -f "$INSTALL_DIR/docker-compose.yml" ]; then
    log "Repertoire existant sans git, utilisation tel quel."
    cd "$INSTALL_DIR"
else
    log "Clonage du depot Voxa Center..."
    # Try SSH first (no password needed if key is configured), fallback to HTTPS
    if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
        git clone git@github.com:grevoka/SIP.ctrl.git "$INSTALL_DIR"
    else
        git clone https://github.com/grevoka/SIP.ctrl.git "$INSTALL_DIR"
    fi
    cd "$INSTALL_DIR"
fi

# ══════════════════════════════════════
# Phase 3 : Asterisk natif (compilation)
# ══════════════════════════════════════
if command -v asterisk &>/dev/null; then
    log "Asterisk deja installe: $(asterisk -V)"
else
    log "Installation d'Asterisk 20 depuis les sources..."

    # Build dependencies
    if [ "$OS" = "debian" ]; then
        apt-get install -yqq build-essential wget libssl-dev libncurses5-dev libnewt-dev \
            libxml2-dev linux-headers-$(uname -r) libsqlite3-dev uuid-dev libjansson-dev \
            libsrtp2-dev libcurl4-openssl-dev libedit-dev unixodbc-dev odbc-mariadb \
            sox mpg123 ffmpeg > /dev/null
    fi

    AST_VERSION="20.13.0"
    cd /usr/src
    if [ ! -d "asterisk-${AST_VERSION}" ]; then
        wget -q "https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${AST_VERSION}.tar.gz"
        tar xzf "asterisk-${AST_VERSION}.tar.gz"
        rm -f "asterisk-${AST_VERSION}.tar.gz"
    fi
    cd "asterisk-${AST_VERSION}"

    # Install pjproject bundled + compile
    contrib/scripts/install_prereq install > /dev/null 2>&1 || true
    ./configure --with-jansson-bundled --with-pjproject-bundled > /dev/null 2>&1
    make menuselect.makeopts
    menuselect/menuselect --enable codec_opus menuselect.makeopts 2>/dev/null || true
    make -j$(nproc) > /dev/null 2>&1
    make install > /dev/null 2>&1
    make samples > /dev/null 2>&1

    log "Asterisk compile et installe."
fi

# ── Systemd service for Asterisk ──
if [ ! -f /etc/systemd/system/asterisk.service ]; then
    cat > /etc/systemd/system/asterisk.service << 'ASTSERVICE'
[Unit]
Description=Asterisk PBX
After=network.target

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
    log "Service systemd asterisk cree."
fi

# ══════════════════════════════════════
# Phase 3b : Build & lancement Docker (Laravel + MariaDB)
# ══════════════════════════════════════
log "Build de l'image Docker (peut prendre quelques minutes)..."
cd "$INSTALL_DIR"

# Update docker-compose with correct port
sed -i "s|\"8080:80\"|\"${DOCKER_PORT}:80\"|g" docker-compose.yml

docker compose build
log "Build termine."

log "Lancement du container..."
docker compose up -d
log "Container demarre."

# Wait for services
log "Attente du demarrage des services (60s max)..."
for i in $(seq 1 60); do
    if docker exec sip-manager curl -sf http://127.0.0.1/login > /dev/null 2>&1; then
        log "Application prete."
        break
    fi
    sleep 1
    [ $i -eq 60 ] && warn "Timeout — verifiez les logs: docker compose logs -f"
done

# ══════════════════════════════════════
# Phase 3c : ODBC (Asterisk natif → MariaDB dans Docker)
# ══════════════════════════════════════
log "Configuration ODBC pour Asterisk Realtime..."

ODBC_LIB=$(find /usr/lib -name 'libmaodbc.so' 2>/dev/null | head -1)
ODBC_SETUP=$(find /usr/lib -name 'libodbcmyS.so' 2>/dev/null | head -1)
[ -z "$ODBC_LIB" ] && ODBC_LIB="/usr/lib/odbc/libmaodbc.so"

cat > /etc/odbcinst.ini << ODBCEOF
[MariaDB]
Description = MariaDB ODBC Connector
Driver      = ${ODBC_LIB}
UsageCount  = 1
ODBCEOF

# DB password from container .env
DB_PASS=$(docker exec sip-manager grep '^DB_PASSWORD=' /var/www/html/.env | cut -d= -f2)
cat > /etc/odbc.ini << ODBCEOF
[asterisk-connector]
Description = Asterisk Realtime
Driver      = MariaDB
Server      = 127.0.0.1
Port        = 3306
Database    = asterisk_rt
User        = root
Password    = ${DB_PASS}
Option      = 3
ODBCEOF

log "ODBC configure."

# ══════════════════════════════════════
# Phase 4 : Configuration Nginx (reverse proxy + HTTPS)
# ══════════════════════════════════════
log "Configuration du vhost Nginx..."

VHOST_FILE="/etc/nginx/sites-available/${HOSTNAME}"
cat > "$VHOST_FILE" << NGINXEOF
server {
    listen 80;
    server_name ${HOSTNAME};

    # WebSocket proxy → Asterisk natif (port 8088)
    location /ws {
        proxy_pass http://127.0.0.1:8088;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }

    # Application Laravel → Docker container
    location / {
        proxy_pass http://127.0.0.1:${DOCKER_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Port 443;
        proxy_redirect http:// https://;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
    }

    client_max_body_size 50M;
}
NGINXEOF

# Enable site
if [ -d /etc/nginx/sites-enabled ]; then
    ln -sf "$VHOST_FILE" "/etc/nginx/sites-enabled/${HOSTNAME}"
    # Remove default if exists
    rm -f /etc/nginx/sites-enabled/default
else
    # For systems without sites-enabled
    ln -sf "$VHOST_FILE" "/etc/nginx/conf.d/${HOSTNAME}.conf"
fi

# Test nginx config
nginx -t 2>&1 || err "Configuration Nginx invalide."
systemctl reload nginx
log "Vhost Nginx configure."

# ══════════════════════════════════════
# Phase 5 : Let's Encrypt SSL
# ══════════════════════════════════════
log "Configuration du certificat SSL Let's Encrypt..."
certbot --nginx -d "$HOSTNAME" --email "$LE_EMAIL" --agree-tos --non-interactive --redirect 2>&1 || {
    warn "Certbot a echoue — verifiez que le DNS de ${HOSTNAME} pointe vers ce serveur."
    warn "Vous pourrez relancer: certbot --nginx -d ${HOSTNAME}"
}

# ══════════════════════════════════════
# Phase 6 : Fail2ban pour SIP
# ══════════════════════════════════════
log "Configuration de Fail2ban pour la protection SIP..."

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
echo -e "  Container:  sip-manager"
echo ""
echo -e "  ${YELLOW}Ouvrez https://${HOSTNAME}/install pour finaliser${NC}"
echo -e "  ${YELLOW}la configuration (base de donnees + compte admin).${NC}"
echo ""
echo -e "  Commandes utiles:"
echo -e "    docker compose -f ${INSTALL_DIR}/docker-compose.yml logs -f"
echo -e "    docker exec -it sip-manager bash"
echo -e "    asterisk -rvvv"
echo -e "    fail2ban-client status asterisk-sip"
echo ""
echo -e "  Architecture:"
echo -e "    Asterisk PBX:  natif sur la VM (systemd)"
echo -e "    Laravel + DB:  Docker container (sip-manager)"
echo -e "    Nginx:         /ws → Asterisk:8088, / → Docker:${DOCKER_PORT}"
echo ""
