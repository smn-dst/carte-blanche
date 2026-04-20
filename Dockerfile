# ══════════════════════════════════════════════════
# Dockerfile — Production (Railway)
# PHP 8.4 + Nginx dans un seul container
# ══════════════════════════════════════════════════

# ── Stage 1 : Composer ────────────────────────────
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

# ── Stage 2 : Assets (Node) ───────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
COPY --from=composer /app/vendor ./vendor

# Installe les vendor JS (importmap)
RUN ./vendor/bin/symfony-cmd importmap:install 2>/dev/null || php vendor/bin/console importmap:install 2>/dev/null || true

# Build Tailwind
RUN php vendor/bin/console tailwind:build --minify 2>/dev/null || true

# Compile les assets
RUN php vendor/bin/console asset-map:compile 2>/dev/null || true

# ── Stage 3 : Image finale ────────────────────────
FROM php:8.4-fpm-alpine

# Dépendances système
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    git \
    unzip \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    rabbitmq-c-dev \
    supervisor

# Extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    gd \
    zip \
    mbstring \
    intl

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install apcu redis amqp \
    && docker-php-ext-enable apcu redis amqp \
    && apk del .build-deps

# Configuration PHP prod
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

# Configuration Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configuration Supervisor (gère PHP-FPM + Nginx)
RUN mkdir -p /etc/supervisor/conf.d
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copie du code source
COPY . .

# Copie vendor depuis stage composer
COPY --from=composer /app/vendor ./vendor

# Copie assets compilés depuis stage assets
COPY --from=assets /app/public/assets ./public/assets
COPY --from=assets /app/public/build ./public/build 2>/dev/null || true

# Copie .env.prod
COPY .env.prod .env.local

# Permissions
RUN chown -R www-data:www-data /var/www/html/var \
    && chmod -R 775 /var/www/html/var

# Cache Symfony prod (sans connexion DB)
RUN APP_ENV=prod php bin/console cache:warmup --no-debug 2>/dev/null || true

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]