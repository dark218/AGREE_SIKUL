# syntax=docker/dockerfile:1.4
# ===========================================
# AGREE SIKUL - Multi-stage Dockerfile
# ===========================================
# La directive `syntax` ci-dessus est REQUISE pour que les COPY <<EOF
# (heredoc) fonctionnent. Sans elle, Docker plante avec "unknown instruction".

# Stage 1: Composer dependencies (pour récupérer Ziggy notamment)
FROM composer:latest AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Stage 2: Build des assets frontend (Vue 3 + Vite)
FROM node:20-alpine AS frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./
COPY resources ./resources
COPY public ./public
COPY Modules ./Modules

# Récupérer Ziggy depuis le stage composer (utilisé par les routes nommées dans Vue)
COPY --from=composer-builder /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

RUN npm run build

# Stage 3: Image PHP de production
FROM php:8.3-fpm-alpine AS production

# Dépendances système
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    mysql-dev \
    sqlite-dev \
    zip \
    unzip \
    git \
    curl \
    bash \
    netcat-openbsd \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev

# Extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite bcmath opcache gd zip pcntl exif

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration PHP production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "memory_limit=512M" >> "$PHP_INI_DIR/conf.d/zz-app.ini" && \
    echo "upload_max_filesize=100M" >> "$PHP_INI_DIR/conf.d/zz-app.ini" && \
    echo "post_max_size=100M" >> "$PHP_INI_DIR/conf.d/zz-app.ini" && \
    echo "max_execution_time=300" >> "$PHP_INI_DIR/conf.d/zz-app.ini" && \
    echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" && \
    echo "opcache.memory_consumption=256" >> "$PHP_INI_DIR/conf.d/opcache.ini" && \
    echo "opcache.interned_strings_buffer=16" >> "$PHP_INI_DIR/conf.d/opcache.ini" && \
    echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" && \
    echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini"

WORKDIR /var/www/html

# Code de l'application (le .env n'est PAS copié — il est généré par l'entrypoint
# depuis les variables d'environnement du container, que Dokploy injecte via son UI)
COPY --chown=www-data:www-data . .

# Retirer le flag dev de Vite (sinon Laravel cherche le serveur Vite local)
RUN rm -f public/hot

# Si .env.production existe (build local hors gitignore), on le copie comme fallback
# Sinon l'entrypoint le générera depuis ENV au démarrage
RUN if [ -f .env.production ]; then cp .env.production .env; fi

# Récupérer les assets buildés depuis le stage frontend
COPY --from=frontend-builder --chown=www-data:www-data /app/public/build ./public/build

# Dépendances PHP production uniquement
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permissions storage / bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuration Nginx
COPY <<EOF /etc/nginx/http.d/default.conf
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~* \\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }

    location = /health {
        access_log off;
        return 200 "OK\n";
        add_header Content-Type text/plain;
    }
}
EOF

# Configuration Supervisor (php-fpm + nginx + queue worker)
COPY <<EOF /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/dev/stdout
logfile_maxbytes=0
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g 'daemon off;'
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stopwaitsecs=3600
EOF

# Entrypoint : génération .env + attente DB + caches Laravel + migrations optionnelles
COPY <<'EOF' /usr/local/bin/docker-entrypoint.sh
#!/bin/sh
set -e

cd /var/www/html

# Si pas de .env (cas Dokploy avec .env.production gitignored), on en génère un
# minimal à partir des variables d'env injectées dans le container
if [ ! -f .env ]; then
    echo "[entrypoint] No .env found — generating from container ENV..."
    : > .env
    # Liste des vars qu'on persiste dans .env (Laravel lit aussi getenv mais
    # `php artisan config:cache` se base sur le fichier .env)
    for var in APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL APP_TIMEZONE APP_LOCALE APP_TUNNEL \
               APP_URL_LOCAL APP_URL_NGROK \
               LOG_CHANNEL LOG_LEVEL \
               DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
               BROADCAST_CONNECTION BROADCAST_DRIVER \
               CACHE_STORE CACHE_PREFIX CACHE_DRIVER \
               FILESYSTEM_DISK QUEUE_CONNECTION \
               SESSION_DRIVER SESSION_LIFETIME SESSION_DOMAIN SESSION_SECURE_COOKIE SESSION_SAME_SITE SESSION_COOKIE \
               REDIS_HOST REDIS_PASSWORD REDIS_PORT REDIS_CLIENT \
               MAIL_MAILER MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME \
               JWT_SECRET JWT_TTL JWT_REFRESH_TTL JWT_ALGO \
               PUSHER_APP_ID PUSHER_APP_KEY PUSHER_APP_SECRET PUSHER_APP_CLUSTER PUSHER_HOST PUSHER_PORT PUSHER_SCHEME \
               FIREBASE_SERVER_KEY FIREBASE_CREDENTIALS FIREBASE_API_KEY FIREBASE_AUTH_DOMAIN FIREBASE_PROJECT_ID FIREBASE_MESSAGING_SENDER_ID FIREBASE_APP_ID FIREBASE_VAPID_KEY \
               SMS_API_AUTHORIZATION SMSPRO_API_AUTHORIZATION SMS_SENDER_NAME SMSPRO_SENDER_NAME SMS_API_URL SMSPRO_API_URL \
               WIREPICK_API_URL WIREPICK_CLIENT WIREPICK_PASSWORD WIREPICK_SENDER_ID COUNTRY_SENDER_NUMBER \
               PISPI_BASE_URL PISPI_CLIENT_ID PISPI_SECRET PISPI_API_KEY PISPI_WEBHOOK_SECRET \
               TRUSTED_PROXIES; do
        eval "val=\$$var"
        if [ -n "$val" ]; then
            # Quote pour gérer espaces / chars spéciaux
            printf '%s="%s"\n' "$var" "$val" >> .env
        fi
    done
fi

# Génère APP_KEY si absente (premier démarrage sans secret configuré)
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null && [ -z "$APP_KEY" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

# Attente DB
if [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Waiting for database $DB_HOST:${DB_PORT:-3306}..."
    while ! nc -z $DB_HOST ${DB_PORT:-3306} 2>/dev/null; do
        sleep 1
    done
    echo "[entrypoint] Database is ready!"
fi

# Caches Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations (RUN_MIGRATIONS=true)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
fi

# Seed (RUN_SEED=true) — à activer UNIQUEMENT au 1er déploiement
if [ "$RUN_SEED" = "true" ]; then
    echo "[entrypoint] Running seeders..."
    php artisan db:seed --force
fi

php artisan storage:link || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
