FROM dunglas/frankenphp:1-php8.4-bookworm AS base

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions bcmath gd pdo_pgsql zip

WORKDIR /app

FROM node:24-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
RUN mkdir -p public && npm run build

FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

COPY . .
RUN mkdir -p bootstrap/cache \
    && APP_ENV=local composer dump-autoload --no-dev --classmap-authoritative

FROM base AS runtime

COPY --from=dependencies --chown=www-data:www-data /app/app /app/app
COPY --from=dependencies --chown=www-data:www-data /app/artisan /app/artisan
COPY --from=dependencies --chown=www-data:www-data /app/bootstrap /app/bootstrap
COPY --from=dependencies --chown=www-data:www-data /app/config /app/config
COPY --from=dependencies --chown=www-data:www-data /app/database /app/database
COPY --from=dependencies --chown=www-data:www-data /app/lang /app/lang
COPY --from=dependencies --chown=www-data:www-data /app/public /app/public
COPY --from=dependencies --chown=www-data:www-data /app/resources/views /app/resources/views
COPY --from=dependencies --chown=www-data:www-data /app/routes /app/routes
COPY --from=dependencies --chown=www-data:www-data /app/vendor /app/vendor
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile

ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data \
    LARAVEL_STORAGE_PATH=/tmp/laravel/storage

USER www-data

CMD ["sh", "-c", "mkdir -p \"$XDG_CONFIG_HOME/caddy\" \"$XDG_DATA_HOME/caddy\" \"$LARAVEL_STORAGE_PATH/framework/cache\" \"$LARAVEL_STORAGE_PATH/framework/sessions\" \"$LARAVEL_STORAGE_PATH/framework/views\" \"$LARAVEL_STORAGE_PATH/app/private/pdf-temp\" && exec frankenphp run --config /etc/frankenphp/Caddyfile"]
