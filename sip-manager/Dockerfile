FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    nginx supervisor curl git unzip sudo \
    mariadb-client redis \
    libxml2-dev oniguruma-dev libzip-dev \
    icu-dev linux-headers \
    asterisk asterisk-pjsip asterisk-sounds-en \
    && docker-php-ext-install pdo_mysql mbstring xml zip bcmath intl opcache pcntl \
    && pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

# App
WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan view:clear \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Asterisk permissions
RUN chown -R asterisk:asterisk /etc/asterisk /var/lib/asterisk /var/spool/asterisk /var/log/asterisk \
    && echo "www-data ALL=(ALL) NOPASSWD: /usr/sbin/asterisk, /usr/bin/tee" >> /etc/sudoers.d/www-data

EXPOSE 80 5060/udp 5060/tcp 10000-10100/udp

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
