# ============================================================
# Stage 1 : Build des assets front-end (Node.js)
# ============================================================
FROM node:20-alpine AS assets_builder

WORKDIR /app

# Installer les dépendances npm
COPY package.json package-lock.json* ./
RUN npm ci --no-audit

# Copier les sources front et compiler
COPY webpack.config.js ./
COPY assets/ ./assets/
RUN npm run build

# ============================================================
# Stage 2 : Installation des dépendances PHP (Composer)
# ============================================================
FROM composer:2 AS composer_builder

WORKDIR /app

COPY composer.json composer.lock* ./

# Générer un .env minimal pour le build (les vraies valeurs viennent des variables d'environnement Render)
RUN echo 'APP_ENV=prod' > .env \
    && echo 'APP_SECRET=build-placeholder' >> .env \
    && echo 'DATABASE_URL="postgresql://placeholder@localhost:5432/app?serverVersion=16"' >> .env \
    && echo 'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0' >> .env \
    && echo 'MERCURE_URL=https://example.com/.well-known/mercure' >> .env \
    && echo 'MERCURE_PUBLIC_URL=https://example.com/.well-known/mercure' >> .env \
    && echo 'MERCURE_JWT_SECRET=placeholder' >> .env \
    && echo 'MAILER_DSN=null://null' >> .env \
    && echo 'DEFAULT_URI=https://ulcoccaz.onrender.com' >> .env \
    && echo 'APP_SHARE_DIR=var/share' >> .env

COPY config/ ./config/
COPY src/ ./src/
COPY bin/ ./bin/
COPY public/index.php ./public/index.php
COPY templates/ ./templates/
COPY migrations/ ./migrations/
COPY translations/ ./translations/
COPY importmap.php ./importmap.php

# Installer les dépendances de production uniquement
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Exécuter les scripts post-install (cache:clear, assets:install)
ENV APP_ENV=prod
RUN composer run-script post-install-cmd --no-interaction || true

# ============================================================
# Stage 3 : Image finale (PHP-FPM + Nginx)
# ============================================================
FROM php:8.2-fpm-alpine

# Installer les extensions PHP nécessaires + Nginx + supervisord
RUN apk add --no-cache \
    nginx \
    supervisor \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    zip \
    mbstring \
    && rm -rf /var/cache/apk/*

# Configurer OPcache pour la production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Configurer PHP pour la production
RUN echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# Copier les sources de l'application
COPY bin/ ./bin/
COPY config/ ./config/
COPY migrations/ ./migrations/
COPY public/index.php ./public/index.php
COPY src/ ./src/
COPY templates/ ./templates/
COPY translations/ ./translations/
COPY importmap.php ./importmap.php
COPY composer.json ./composer.json

# Générer un .env minimal (les vraies valeurs viennent des variables d'environnement Render)
RUN echo 'APP_ENV=prod' > .env \
    && echo 'APP_SECRET=build-placeholder' >> .env \
    && echo 'DATABASE_URL="postgresql://placeholder@localhost:5432/app?serverVersion=16"' >> .env \
    && echo 'MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0' >> .env \
    && echo 'MERCURE_URL=https://example.com/.well-known/mercure' >> .env \
    && echo 'MERCURE_PUBLIC_URL=https://example.com/.well-known/mercure' >> .env \
    && echo 'MERCURE_JWT_SECRET=placeholder' >> .env \
    && echo 'MAILER_DSN=null://null' >> .env \
    && echo 'DEFAULT_URI=https://ulcoccaz.onrender.com' >> .env \
    && echo 'APP_SHARE_DIR=var/share' >> .env

# Copier les dépendances Composer depuis le builder
COPY --from=composer_builder /app/vendor/ ./vendor/

# Copier les assets compilés depuis le builder Node.js
COPY --from=assets_builder /app/public/build/ ./public/build/

# Copier les images statiques existantes
COPY public/images/ ./public/images/

# Créer les répertoires nécessaires
RUN mkdir -p var/cache var/log var/share public/uploads/annonces \
    && chown -R www-data:www-data var/ public/uploads/

# Copier les fichiers de configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Warmup du cache Symfony en production
ENV APP_ENV=prod
RUN php bin/console cache:clear --env=prod --no-debug || true \
    && php bin/console cache:warmup --env=prod --no-debug || true \
    && chown -R www-data:www-data var/

# Render utilise la variable PORT (par défaut 10000)
EXPOSE 10000

ENTRYPOINT ["/entrypoint.sh"]
