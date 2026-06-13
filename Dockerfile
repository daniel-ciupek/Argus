# Multi-stage build dla Hermes Cockpit.
# Szkielet Fazy 0 — stages `vendor`/`assets`/`prod` zaczną realnie kopiować pliki
# dopiero gdy w Fazie 1 pojawi się aplikacja Laravel. `dev` jest używany przez
# docker-compose.yml (target: dev) i wystarcza do scaffoldingu oraz pracy lokalnej.

# ─────────────────────────────────────────────────────────────
# Stage 1: base — PHP 8.3 + rozszerzenia wymagane przez aplikację
# ─────────────────────────────────────────────────────────────
FROM php:8.3-cli-alpine AS base

WORKDIR /var/www/html

# Biblioteki systemowe potrzebne do kompilacji rozszerzeń PHP.
RUN apk add --no-cache \
        git curl unzip libzip-dev icu-dev postgresql-dev \
        oniguruma-dev libpng-dev $PHPIZE_DEPS \
    # Rozszerzenia PHP: baza (pgsql), liczenie kosztów (bcmath), wydajność (opcache),
    # współbieżność dla Reverb/queue (pcntl), pozostałe standardowe.
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql bcmath intl zip gd opcache pcntl \
    # Redis instalujemy przez PECL (nie ma go w docker-php-ext-install).
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Sprzątamy build-deps, by warstwa była lekka.
    && apk del $PHPIZE_DEPS

# ─────────────────────────────────────────────────────────────
# Stage 2: dev — base + Composer (praca lokalna i scaffolding w kontenerze)
# ─────────────────────────────────────────────────────────────
FROM base AS dev

# Composer kopiujemy z oficjalnego obrazu (czysto i powtarzalnie).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Kod montujemy bind-mountem z docker-compose.yml, więc tu nic nie kopiujemy.
EXPOSE 8000 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ─────────────────────────────────────────────────────────────
# Stage 3: vendor — zależności PHP dla produkcji (bez dev-deps)
# ─────────────────────────────────────────────────────────────
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Najpierw tylko manifesty — cache warstwy działa, dopóki zależności się nie zmienią.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# ─────────────────────────────────────────────────────────────
# Stage 4: assets — build frontu (Vite) dla produkcji
# ─────────────────────────────────────────────────────────────
FROM node:20-alpine AS assets
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 5: prod — szczupły obraz runtime (bez Composera i narzędzi build)
# ─────────────────────────────────────────────────────────────
FROM base AS prod

# Kod aplikacji + zależności + gotowe assety z poprzednich stage'y.
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /var/www/html/public/build ./public/build

# Dociągamy autoloader i optymalizacje (skrypty composera teraz mają już cały kod).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev \
    && apk del git || true

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
