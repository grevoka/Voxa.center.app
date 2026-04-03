FROM php:8.4-fpm-bookworm

LABEL maintainer="SIP Project"

ENV DEBIAN_FRONTEND=noninteractive
ENV ASTERISK_VERSION=20-current

# ── Phase 1 : PHP, Nginx, MariaDB, Redis, Supervisor + deps pour compiler Asterisk ──
RUN apt-get update && apt-get install -y --no-install-recommends \
        # PHP deps
        git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
        libicu-dev supervisor cron procps \
        # Nginx
        nginx \
        # MariaDB server
        mariadb-server mariadb-client \
        # Redis
        redis-server \
        # Node 20 (pour Vite)
        ca-certificates gnupg \
        # ODBC + capabilities + sudo
        odbc-mariadb unixodbc unixodbc-dev libcap2 sudo \
        # Build deps pour Asterisk
        build-essential wget pkg-config \
        libedit-dev libjansson-dev libsqlite3-dev uuid-dev \
        libssl-dev libsrtp2-dev libspeex-dev libspeexdsp-dev \
        libogg-dev libvorbis-dev libopus-dev libgsm1-dev \
        libncurses5-dev libnewt-dev libpopt-dev libcap-dev \
        libsndfile1-dev liburiparser-dev \
        xmlstarlet subversion \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring xml curl zip gd intl bcmath opcache pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Phase 2 : Compiler Asterisk 20 from source ──
RUN cd /usr/src \
    && wget -q https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}.tar.gz \
    && tar xzf asterisk-${ASTERISK_VERSION}.tar.gz \
    && rm asterisk-${ASTERISK_VERSION}.tar.gz \
    && cd asterisk-20.*/ \
    && contrib/scripts/get_mp3_source.sh || true \
    && ./configure --with-jansson-bundled --with-pjproject-bundled --with-crypto --with-ssl --disable-xmldoc \
    && make menuselect.makeopts \
    && menuselect/menuselect \
        --enable codec_opus \
        --enable codec_g722 \
        --enable res_odbc \
        --enable res_config_odbc \
        --enable cdr_odbc \
        --enable cdr_adaptive_odbc \
        --enable res_pjsip \
        --enable res_pjsip_authenticator_digest \
        --enable res_pjsip_endpoint_identifier_ip \
        --enable res_pjsip_endpoint_identifier_user \
        --enable res_pjsip_outbound_registration \
        --enable res_pjsip_registrar \
        --enable res_pjsip_session \
        --enable app_voicemail \
        --enable app_mixmonitor \
        --enable CORE-SOUNDS-FR-WAV \
        --disable chan_sip \
        menuselect.makeopts \
    && make -j$(nproc) \
    && make install \
    && make samples \
    && make config \
    && ldconfig \
    && cd / && rm -rf /usr/src/asterisk-20.*

# ── Creer user asterisk + permissions ──
RUN groupadd -f asterisk \
    && useradd -r -g asterisk -d /var/lib/asterisk -s /sbin/nologin asterisk 2>/dev/null || true \
    && mkdir -p /var/lib/asterisk /var/log/asterisk /var/spool/asterisk \
               /var/run/asterisk /var/spool/asterisk/voicemail /etc/asterisk \
    && chown -R root:root /var/lib/asterisk /var/log/asterisk \
                         /var/spool/asterisk /var/run/asterisk \
                         /etc/asterisk

# ── Composer ──
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── PHP config ──
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-sip.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-sip.conf

# ── Nginx config ──
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx/conf.d/app.conf /etc/nginx/sites-enabled/sip.conf
RUN sed -i 's/server php:9000/server 127.0.0.1:9000/' /etc/nginx/sites-enabled/sip.conf

# ── MariaDB config ──
COPY docker/mariadb/my.cnf /etc/mysql/conf.d/custom.cnf
RUN mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld

# ── Redis config ──
RUN mkdir -p /var/run/redis && chown redis:redis /var/run/redis

# ── Asterisk config (ecrase les samples) ──
COPY docker/asterisk/configs/ /etc/asterisk/

# ── Cron scheduler ──
RUN echo "* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler

# ── Supervisor (orchestre tout) ──
COPY docker/allinone/supervisord.conf /etc/supervisor/conf.d/sip.conf

# ── Entrypoint ──
COPY docker/allinone/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 80 3306 5060/udp 5060/tcp 10000-10100/udp

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/sip.conf"]
