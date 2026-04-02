#!/bin/bash
set -e

echo "[ASTERISK] Injecting environment into configs..."

# -- ODBC system config --
cat > /etc/odbcinst.ini <<EOFC
[MariaDB]
Description = MariaDB ODBC Connector
Driver = /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so
Setup = /usr/lib/x86_64-linux-gnu/odbc/libodbcmyS.so
UsageCount = 1
EOFC

cat > /etc/odbc.ini <<EOFC
[asterisk-connector]
Description = Asterisk Realtime DB
Driver = MariaDB
Server = ${DB_AST_HOST:-mariadb}
Port = 3306
Database = ${DB_AST_DATABASE}
User = ${DB_AST_USERNAME}
Password = ${DB_AST_PASSWORD}
Option = 3
EOFC

# -- Asterisk configs --
sed -i "s|__DB_USER__|${DB_AST_USERNAME}|g"           /etc/asterisk/res_odbc.conf
sed -i "s|__DB_PASS__|${DB_AST_PASSWORD}|g"           /etc/asterisk/res_odbc.conf

sed -i "s|__AMI_USER__|${AMI_USER}|g"                 /etc/asterisk/manager.conf
sed -i "s|__AMI_PASS__|${AMI_PASSWORD}|g"             /etc/asterisk/manager.conf

sed -i "s|__PUBLIC_IP__|${PUBLIC_IP:-127.0.0.1}|g"    /etc/asterisk/pjsip.conf
sed -i "s|__LOCAL_NET__|${LOCAL_NET:-172.16.0.0/12}|g" /etc/asterisk/pjsip.conf

sed -i "s|__RTP_START__|${RTP_START:-10000}|g"         /etc/asterisk/rtp.conf
sed -i "s|__RTP_END__|${RTP_END:-10100}|g"             /etc/asterisk/rtp.conf

# Attendre MariaDB
echo "[ASTERISK] Waiting for MariaDB at ${DB_AST_HOST:-mariadb}:3306..."
until mysqladmin ping -h "${DB_AST_HOST:-mariadb}" -u "${DB_AST_USERNAME}" -p"${DB_AST_PASSWORD}" --silent 2>/dev/null; do
    sleep 2
done
echo "[ASTERISK] MariaDB is ready"

# Permissions
chown -R asterisk:asterisk /var/{lib,log,spool,run}/asterisk
chown -R asterisk:asterisk /etc/asterisk

echo "[ASTERISK] Starting Asterisk..."
exec "$@"
