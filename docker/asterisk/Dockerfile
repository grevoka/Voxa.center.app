FROM debian:bookworm-slim

LABEL maintainer="SIP Project"

ENV ASTERISK_VERSION=20
ENV DEBIAN_FRONTEND=noninteractive

# Dependances de build
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential wget curl gnupg ca-certificates \
        libssl-dev libncurses5-dev libnewt-dev libxml2-dev \
        uuid-dev libjansson-dev libcurl4-openssl-dev libedit-dev \
        libsqlite3-dev pkg-config autoconf automake libtool \
        libmariadb-dev libmariadb-dev-compat \
        odbc-mariadb unixodbc unixodbc-dev \
        sngrep net-tools procps default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Compiler Asterisk
WORKDIR /usr/src

RUN wget -q "https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}-current.tar.gz" \
    && tar xzf asterisk-${ASTERISK_VERSION}-current.tar.gz \
    && rm asterisk-${ASTERISK_VERSION}-current.tar.gz \
    && cd asterisk-${ASTERISK_VERSION}.*/ \
    && ./configure --with-pjproject-bundled --with-jansson-bundled --with-crypto --with-ssl \
    && make menuselect.makeopts \
    && menuselect/menuselect \
        --enable res_odbc \
        --enable res_config_odbc \
        --enable cdr_odbc \
        --enable cdr_adaptive_odbc \
        --enable res_pjsip \
        --enable res_pjsip_registrar \
        --enable res_pjsip_authenticator_digest \
        --enable res_pjsip_outbound_registration \
        --enable res_pjsip_endpoint_identifier_ip \
        --enable res_pjsip_endpoint_identifier_user \
        --enable res_realtime \
        --enable codec_g722 \
        --enable app_voicemail \
        --enable app_queue \
        --enable app_dial \
        --disable chan_sip \
        --disable BUILD_NATIVE \
        menuselect.makeopts \
    && make -j$(nproc) \
    && make install \
    && make config \
    && make install-logrotate \
    && cd /usr/src && rm -rf asterisk-*

# Utilisateur asterisk
RUN groupadd -r asterisk \
    && useradd -r -d /var/lib/asterisk -g asterisk asterisk \
    && chown -R asterisk:asterisk /var/lib/asterisk \
    && chown -R asterisk:asterisk /var/log/asterisk \
    && chown -R asterisk:asterisk /var/spool/asterisk \
    && chown -R asterisk:asterisk /var/run/asterisk \
    && chown -R asterisk:asterisk /etc/asterisk \
    && chown -R asterisk:asterisk /usr/lib/asterisk

# Copier les configs
COPY configs/ /etc/asterisk/
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R asterisk:asterisk /etc/asterisk

EXPOSE 5060/udp 5060/tcp 5061/tcp 8089/tcp 5038/tcp
EXPOSE 10000-10100/udp

ENTRYPOINT ["entrypoint.sh"]
CMD ["asterisk", "-f", "-U", "asterisk", "-G", "asterisk", "-vvv"]
