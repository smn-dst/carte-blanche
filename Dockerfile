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
    --prefer-dist \
    --ignore-platform-reqs

# ── Stage 2 : Node (npm uniquement) ───────────────
FROM node:22-alpine AS node

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

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
    supervisor \
    nodejs \
    npm

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

# Configuration Supervisor
RUN mkdir -p /etc/supervisor/conf.d
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copie du code source
COPY . .

# Copie vendor depuis stage composer
COPY --from=composer /app/vendor ./vendor

# Copie node_modules depuis stage node
COPY --from=node /app/node_modules ./node_modules

# Crée les dossiers assets
RUN mkdir -p public/assets public/build

# Build des assets avec PHP (qui a accès à Symfony)
RUN php bin/console importmap:install 2>/dev/null || true
RUN php bin/console tailwind:build --minify 2>/dev/null || true
RUN php bin/console asset-map:compile 2>/dev/null || true

# Permissions
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Cache Symfony prod
RUN APP_ENV=prod php bin/console cache:warmup --no-debug 2>/dev/null || true

# Nettoie node_modules pour alléger l'image finale
RUN rm -rf node_modules

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]