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
# Stage 2 — Install PHP dependencies (production only, no dev)
# =============================================================================
FROM composer:2.8 AS composer-builder

WORKDIR /app

COPY composer*.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 3 — PHP-FPM application image
# =============================================================================
FROM php:8.3-fpm-alpine AS production

# System dependencies
RUN apk add --no-cache \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    pcntl \
    intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# OPcache — tuned for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

# Application source
COPY --chown=www-data:www-data . .

# PHP dependencies (without dev)
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor

# Compiled frontend assets
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

# Writable directories required by Laravel
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
# Stage 4 — Nginx image with baked-in static assets
# =============================================================================
FROM nginx:alpine AS nginx-prod

# Static files from the Vite build
COPY --from=node-builder /app/public /app/public

# Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
