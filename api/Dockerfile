# syntax=docker/dockerfile:1
# ─────────────────────────────────────────────────────────────────────────────
# API showcase-recruiter-webapp-saas — image DEV (FrankenPHP : Caddy + PHP embarqués, défaut API Platform 4).
# Le code (vendor/ inclus) est monté par docker-compose (./api:/app) : cette image
# ne fournit que le runtime PHP 8.4 + extensions + composer.
# ─────────────────────────────────────────────────────────────────────────────
FROM dunglas/frankenphp:1-php8.4

# Extensions PHP (install-php-extensions est fourni par l'image FrankenPHP), puis marge
# mémoire de confort dev : l'écriture des traces Web Profiler des grosses collections
# dépasse les 128M par défaut (OOM constaté). L'image prod garde le défaut.
RUN install-php-extensions \
      intl \
      opcache \
      pdo_pgsql \
      zip \
      pcov \
      @composer \
    && echo 'memory_limit = 512M' > /usr/local/etc/php/conf.d/zz-dev-memory-limit.ini

# FrankenPHP écoute en clair sur :80 (le TLS est géré par Caddy frontal en preprod/prod).
ENV SERVER_NAME=:80

WORKDIR /app

# En dev, l'app est montée par compose ; la commande par défaut de l'image
# (frankenphp run) sert /app/public via le Caddyfile par défaut.
