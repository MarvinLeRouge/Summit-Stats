# =============================================================================
# Stage 1 — Build frontend assets (Vue.js / Vite)
# =============================================================================
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci --prefer-offline

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# =============================================================================
# Stage 2a — PHP dependencies with dev (for development image)
# =============================================================================
FROM composer:2.8 AS composer-dev-builder

WORKDIR /app

COPY composer*.json ./
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=php

COPY . .
RUN mkdir -p bootstrap/cache && composer dump-autoload --optimize --ignore-platform-req=php

# =============================================================================
# Stage 2b — PHP dependencies without dev (for production image)
# =============================================================================
FROM composer:2.8 AS composer-builder

WORKDIR /app

COPY composer*.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=php

COPY . .
RUN mkdir -p bootstrap/cache && composer dump-autoload --optimize --no-dev --ignore-platform-req=php

# =============================================================================
# Stage 3 — Base PHP image (shared between dev and production)
# =============================================================================
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        libpq-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .build-deps \
        autoconf g++ make $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo pdo_pgsql pgsql zip opcache pcntl intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /app

# =============================================================================
# Stage 4 — Development image
# PHP-FPM sans opcache, sans COPY du code source (bind-mount en dev)
# =============================================================================
FROM base AS development

# Vendor avec dépendances dev (écrasé par le named volume au runtime)
COPY --from=composer-dev-builder /app/vendor ./vendor

COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh

# Workers PHP-FPM en root pour les volumes bind-mount en dev
COPY docker/php/www-dev.conf /usr/local/etc/php-fpm.d/www-dev.conf

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint-dev.sh"]
CMD ["php-fpm"]

# =============================================================================
# Stage 5 — Production image
# =============================================================================
FROM base AS production

# OPcache — tuned for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --chown=www-data:www-data . .
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

# =============================================================================
# Stage 6 — Nginx image avec assets Vite baked-in (production)
# =============================================================================
FROM nginx:alpine AS nginx-prod

COPY --from=node-builder /app/public /app/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
