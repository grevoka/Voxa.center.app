FROM php:8.4-fpm-bookworm

LABEL maintainer="SIP Project"

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
        libicu-dev libpq-dev supervisor cron procps \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring xml curl zip gd intl bcmath opcache pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP-FPM Healthcheck
RUN curl -o /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Config PHP
COPY php.ini /usr/local/etc/php/conf.d/99-sip.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-sip.conf

# Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/sip.conf

# Cron pour le scheduler
RUN echo "* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler

WORKDIR /var/www/html

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/sip.conf"]
