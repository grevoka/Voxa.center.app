#!/bin/bash
# Wait for MySQL to be ready before reloading Asterisk ODBC modules
for i in $(seq 1 30); do
    if mysqladmin ping --silent 2>/dev/null; then break; fi
    sleep 1
done
sleep 3
/usr/sbin/asterisk -rx "module reload res_odbc.so" 2>/dev/null
sleep 1
/usr/sbin/asterisk -rx "module reload cdr_adaptive_odbc.so" 2>/dev/null
/usr/sbin/asterisk -rx "module reload res_pjsip.so" 2>/dev/null
/usr/sbin/asterisk -rx "module reload cdr" 2>/dev/null
# Fix sudoers for web-managed config files
echo "www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk, /usr/bin/tee /etc/asterisk/extensions.conf, /usr/bin/tee /etc/asterisk/queues.conf, /usr/bin/tee /etc/asterisk/pjsip.conf, /usr/bin/tee /etc/asterisk/musiconhold.conf" > /etc/sudoers.d/asterisk-cli
chmod 440 /etc/sudoers.d/asterisk-cli
# Fix Redis bgsave
redis-cli CONFIG SET stop-writes-on-bgsave-error no 2>/dev/null
echo "Post-start complete at $(date)"
