#!/bin/bash
set -e

DB_USER="${DB_USERNAME:-root}"
AMI_USER="${ASTERISK_AMI_USER:-laravel_ami}"

# Generate random passwords on first install, reuse from .env on subsequent starts
ENV_FILE="/var/www/html/.env"
if [ -n "${DB_PASSWORD}" ]; then
    DB_PASS="${DB_PASSWORD}"
elif [ -f "$ENV_FILE" ] && grep -q "^DB_PASSWORD=" "$ENV_FILE"; then
    DB_PASS=$(grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d= -f2)
else
    DB_PASS=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)
    echo "[SIP] Generated random DB password"
fi

if [ -n "${ASTERISK_AMI_SECRET}" ]; then
    AMI_PASS="${ASTERISK_AMI_SECRET}"
elif [ -f "$ENV_FILE" ] && grep -q "^ASTERISK_AMI_SECRET=" "$ENV_FILE"; then
    AMI_PASS=$(grep "^ASTERISK_AMI_SECRET=" "$ENV_FILE" | cut -d= -f2)
else
    AMI_PASS=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 16)
    echo "[SIP] Generated random AMI password"
fi
LOCAL_NET="${LOCAL_NET:-172.16.0.0/12}"

# ── Detect public IP (with retries) ──
if [ -n "${PUBLIC_IP}" ]; then
    echo "[SIP] Using provided PUBLIC_IP=${PUBLIC_IP}"
else
    echo "[SIP] Detecting public IP..."
    for i in 1 2 3 4 5; do
        PUBLIC_IP=$(curl -s --max-time 5 https://api.ipify.org 2>/dev/null) && break
        echo "[SIP] IP detection attempt $i failed, retrying..."
        sleep 2
    done
    PUBLIC_IP="${PUBLIC_IP:-127.0.0.1}"
    echo "[SIP] Detected PUBLIC_IP=${PUBLIC_IP}"
fi
RTP_START="${RTP_START:-10000}"
RTP_END="${RTP_END:-10100}"
MARKER="/var/lib/mysql/.sip_initialized"

cd /var/www/html

# ── MariaDB : initialiser si premier lancement ──
if [ ! -f "$MARKER" ]; then
    echo "[SIP] First run — initializing MariaDB..."

    rm -rf /var/lib/mysql/*
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1

    /usr/bin/mariadbd-safe &
    sleep 3
    until mysqladmin ping --silent 2>/dev/null; do sleep 1; done

    mysql -u root <<-EOSQL
        CREATE DATABASE IF NOT EXISTS sip_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE DATABASE IF NOT EXISTS asterisk_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

        ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('${DB_PASS}');
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;

        CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('${DB_PASS}');
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;

        CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('${DB_PASS}');
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

        FLUSH PRIVILEGES;
EOSQL

    # ── Creer les tables PJSIP Realtime dans asterisk_rt ──
    echo "[SIP] Creating Asterisk Realtime tables..."
    mysql -u root -p"${DB_PASS}" asterisk_rt <<-'EORT'
        CREATE TABLE IF NOT EXISTS ps_endpoints (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            transport VARCHAR(40),
            aors VARCHAR(200),
            auth VARCHAR(40),
            context VARCHAR(40),
            disallow VARCHAR(200),
            allow VARCHAR(200),
            direct_media VARCHAR(3),
            force_rport VARCHAR(3),
            rewrite_contact VARCHAR(3),
            rtp_symmetric VARCHAR(3),
            callerid VARCHAR(200),
            dtmf_mode VARCHAR(10),
            media_encryption VARCHAR(10),
            ice_support VARCHAR(3),
            from_user VARCHAR(40),
            from_domain VARCHAR(40),
            trust_id_inbound VARCHAR(3),
            device_state_busy_at INT DEFAULT 1,
            language VARCHAR(10) DEFAULT 'fr'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS ps_auths (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            auth_type VARCHAR(10),
            username VARCHAR(40),
            password VARCHAR(80),
            realm VARCHAR(40)
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
            transport VARCHAR(40),
            outbound_auth VARCHAR(40),
            server_uri VARCHAR(255),
            client_uri VARCHAR(255),
            retry_interval INT DEFAULT 60,
            expiration INT DEFAULT 3600,
            contact_user VARCHAR(40),
            line VARCHAR(3),
            endpoint VARCHAR(40),
            auth_rejection_permanent VARCHAR(3) DEFAULT 'no'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS ps_domain_aliases (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            domain VARCHAR(80)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS ps_endpoint_id_ips (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            endpoint VARCHAR(40),
            `match` VARCHAR(80)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EORT

    touch "$MARKER"

    mysqladmin -u root -p"${DB_PASS}" shutdown 2>/dev/null || true
    sleep 2
    echo "[SIP] MariaDB initialized"
else
    echo "[SIP] MariaDB already initialized"
fi

# ── ODBC Configuration ──
echo "[SIP] Configuring ODBC..."
# Detecter le chemin ODBC driver (arm64 ou amd64)
ODBC_LIB=$(find /usr/lib -name 'libmaodbc.so' 2>/dev/null | head -1)
ODBC_SETUP=$(find /usr/lib -name 'libodbcmyS.so' 2>/dev/null | head -1)
[ -z "$ODBC_LIB" ] && ODBC_LIB="/usr/lib/odbc/libmaodbc.so"
[ -z "$ODBC_SETUP" ] && ODBC_SETUP="/usr/lib/odbc/libodbcmyS.so"

cat > /etc/odbcinst.ini <<EOF
[MariaDB]
Description = MariaDB ODBC Connector
Driver      = ${ODBC_LIB}
Setup       = ${ODBC_SETUP}
UsageCount  = 1
EOF

cat > /etc/odbc.ini <<EOF
[asterisk-connector]
Description = Asterisk Realtime
Driver      = MariaDB
Server      = 127.0.0.1
Port        = 3306
Database    = asterisk_rt
User        = ${DB_USER}
Password    = ${DB_PASS}
Option      = 3
EOF

# ── Asterisk config : remplacer les placeholders (une seule fois) ──
AST_MARKER="/etc/asterisk/.configured"
if [ ! -f "$AST_MARKER" ]; then
    echo "[SIP] Configuring Asterisk placeholders..."
    sed -i "s|__DB_USER__|${DB_USER}|g; s|__DB_PASS__|${DB_PASS}|g" /etc/asterisk/res_odbc.conf
    sed -i "s|__AMI_USER__|${AMI_USER}|g; s|__AMI_PASS__|${AMI_PASS}|g" /etc/asterisk/manager.conf
    sed -i "s|__RTP_START__|${RTP_START}|g; s|__RTP_END__|${RTP_END}|g" /etc/asterisk/rtp.conf
    touch "$AST_MARKER"
else
    echo "[SIP] Asterisk config already configured"
fi

# ── PUBLIC_IP + LOCAL_NET : toujours appliquer (IP peut changer) ──
echo "[SIP] Applying PUBLIC_IP=${PUBLIC_IP} to pjsip.conf..."
sed -i "s|external_media_address = .*|external_media_address = ${PUBLIC_IP}|g" /etc/asterisk/pjsip.conf
sed -i "s|external_signaling_address = .*|external_signaling_address = ${PUBLIC_IP}|g" /etc/asterisk/pjsip.conf
sed -i "s|__PUBLIC_IP__|${PUBLIC_IP}|g; s|__LOCAL_NET__|${LOCAL_NET}|g" /etc/asterisk/pjsip.conf

# Fix permissions (root car Docker, pas besoin de drop privileges)
chown -R root:root /etc/asterisk /var/lib/asterisk /var/log/asterisk \
                   /var/spool/asterisk /var/run/asterisk
# Permettre a www-data de lire les logs et executer asterisk CLI
chmod -R o+r /var/log/asterisk
# Permettre a www-data d'ecrire les fichiers de config geres par le builder
chown www-data:www-data /etc/asterisk/extensions.conf /etc/asterisk/queues.conf 2>/dev/null
touch /etc/asterisk/queues.conf
chown www-data:www-data /etc/asterisk/extensions.conf /etc/asterisk/queues.conf /etc/asterisk/pjsip.conf
chmod 664 /etc/asterisk/extensions.conf /etc/asterisk/queues.conf /etc/asterisk/pjsip.conf
echo "www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk, /usr/bin/tee /etc/asterisk/extensions.conf, /usr/bin/tee /etc/asterisk/queues.conf, /usr/bin/tee /etc/asterisk/pjsip.conf, /usr/bin/tee /etc/asterisk/musiconhold.conf" > /etc/sudoers.d/asterisk-cli
chmod 0440 /etc/sudoers.d/asterisk-cli

# ── Demarrer MariaDB + Redis temporairement pour les migrations ──
/usr/bin/mariadbd-safe &
redis-server --bind 127.0.0.1 --port 6379 --daemonize yes > /dev/null 2>&1
sleep 3

until mysqladmin ping -u root -p"${DB_PASS}" --silent 2>/dev/null; do
    sleep 1
done
echo "[SIP] MariaDB is ready"

# ── Creer les tables PJSIP Realtime (idempotent) ──
echo "[SIP] Ensuring Asterisk Realtime tables exist..."
mysql -u root -p"${DB_PASS}" asterisk_rt <<-'EORT'
    CREATE TABLE IF NOT EXISTS ps_endpoints (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        transport VARCHAR(40),
        aors VARCHAR(200),
        auth VARCHAR(40),
        context VARCHAR(40),
        disallow VARCHAR(200),
        allow VARCHAR(200),
        direct_media VARCHAR(3),
        force_rport VARCHAR(3),
        rewrite_contact VARCHAR(3),
        rtp_symmetric VARCHAR(3),
        callerid VARCHAR(200),
        dtmf_mode VARCHAR(10),
        media_encryption VARCHAR(10),
        ice_support VARCHAR(3),
        from_user VARCHAR(40),
        from_domain VARCHAR(40),
        trust_id_inbound VARCHAR(3),
        device_state_busy_at INT DEFAULT 1,
        language VARCHAR(10) DEFAULT 'fr',
        mailboxes VARCHAR(200)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Add mailboxes column if missing (for existing installs)
    SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='asterisk_rt' AND TABLE_NAME='ps_endpoints' AND COLUMN_NAME='mailboxes');
    SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE ps_endpoints ADD COLUMN mailboxes VARCHAR(200)', 'SELECT 1');
    PREPARE stmt FROM @sqlstmt;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    CREATE TABLE IF NOT EXISTS ps_auths (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        auth_type VARCHAR(10),
        username VARCHAR(40),
        password VARCHAR(80),
        realm VARCHAR(40)
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
        transport VARCHAR(40),
        outbound_auth VARCHAR(40),
        server_uri VARCHAR(255),
        client_uri VARCHAR(255),
        retry_interval INT DEFAULT 60,
        expiration INT DEFAULT 3600,
        contact_user VARCHAR(40),
        line VARCHAR(3),
        endpoint VARCHAR(40),
        auth_rejection_permanent VARCHAR(3) DEFAULT 'no'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Add auth_rejection_permanent column if missing (for existing installs)
    SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='asterisk_rt' AND TABLE_NAME='ps_registrations' AND COLUMN_NAME='auth_rejection_permanent');
    SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE ps_registrations ADD COLUMN auth_rejection_permanent VARCHAR(3) DEFAULT \'no\'', 'SELECT 1');
    PREPARE stmt FROM @sqlstmt;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    CREATE TABLE IF NOT EXISTS ps_domain_aliases (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        domain VARCHAR(80)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS ps_endpoint_id_ips (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        endpoint VARCHAR(40),
        `match` VARCHAR(80)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EORT
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
        INDEX idx_calldate (calldate),
        INDEX idx_src (src), INDEX idx_dst (dst)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EORT
echo "[SIP] Realtime tables OK"

# ── Laravel .env (generer si absent) ──
if [ ! -f ".env" ]; then
    echo "[SIP] Generating Laravel .env..."
    cat > .env <<ENVEOF
APP_NAME="SIP Manager"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
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
ASTERISK_AMI_USER=${AMI_USER}
ASTERISK_AMI_SECRET=${AMI_PASS}

SESSION_DRIVER=redis
SESSION_LIFETIME=120
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log

SIP_DEFAULT_CONTEXT=from-internal
SIP_DEFAULT_TRANSPORT=transport-udp
SIP_DEFAULT_CODECS=alaw,ulaw,g722
ENVEOF
    php artisan key:generate --force
    echo "[SIP] Laravel .env created with APP_KEY"
fi

# ── Garantir que les variables critiques existent dans .env ──
if ! grep -q "^DB_AST_DATABASE=" .env 2>/dev/null; then
    echo "[SIP] Adding missing DB_AST config to .env..."
    cat >> .env <<ASTEOF

DB_AST_CONNECTION=asterisk
DB_AST_HOST=127.0.0.1
DB_AST_PORT=3306
DB_AST_DATABASE=asterisk_rt
DB_AST_USERNAME=root
DB_AST_PASSWORD=${DB_PASS}
ASTEOF
fi

if ! grep -q "^ASTERISK_AMI_HOST=" .env 2>/dev/null; then
    echo "[SIP] Adding missing AMI config to .env..."
    cat >> .env <<AMIEOF

ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USER=${AMI_USER}
ASTERISK_AMI_SECRET=${AMI_PASS}
AMIEOF
fi

# ── Toujours vider le cache config ──
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# ── Composer install si necessaire ──
if [ ! -d "vendor" ]; then
    echo "[SIP] Installing Composer dependencies..."
    composer install --optimize-autoloader --no-interaction
fi

# ── Build assets Vite si necessaire ──
if [ ! -d "public/build" ]; then
    echo "[SIP] Building frontend assets..."
    npm install --no-fund --no-audit 2>&1 | tail -3
    npm run build
fi

# ── Migrations ──
echo "[SIP] Running migrations..."
php artisan migrate --force

# ── Import seed dump si present (premier deploiement) ──
SEED_MARKER="/var/lib/mysql/.seed_imported"
if [ ! -f "$SEED_MARKER" ] && [ -f "db-dump.sql" ]; then
    echo "[SIP] Importing database seed dump..."
    mysql -u root -p"${DB_PASS}" < db-dump.sql
    touch "$SEED_MARKER"
    echo "[SIP] Seed dump imported"
fi

# ── Permissions ──
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan storage:link 2>/dev/null || true

# ── Generate all Asterisk configs from DB ──
echo "[SIP] Generating pjsip.conf trunk sections..."
php artisan tinker --execute="app(\App\Services\SipProvisioningService::class)->writeIdentifyConf();" 2>/dev/null || true

echo "[SIP] Generating extensions.conf + queues.conf from CallFlows..."
php artisan tinker --execute="app(\App\Services\DialplanService::class)->writeAll();" 2>/dev/null || true

# ── Arreter les services temporaires ──
mysqladmin -u root -p"${DB_PASS}" shutdown 2>/dev/null || true
redis-cli shutdown 2>/dev/null || true
sleep 1

echo "[SIP] All ready — starting all services via supervisor..."
exec "$@"
