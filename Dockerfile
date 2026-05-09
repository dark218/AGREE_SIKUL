# ===========================================
# AGREE SIKUL - Multi-stage Dockerfile
# ===========================================

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

# Code de l'application
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data .env.production /var/www/html/.env

# Retirer le flag dev de Vite (sinon Laravel cherche le serveur Vite local)
RUN rm -f public/hot

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

# Entrypoint : attente DB + caches Laravel + migrations optionnelles
COPY <<'EOF' /usr/local/bin/docker-entrypoint.sh
#!/bin/sh
set -e

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database $DB_HOST:${DB_PORT:-3306}..."
    while ! nc -z $DB_HOST ${DB_PORT:-3306} 2>/dev/null; do
        sleep 1
    done
    echo "Database is ready!"
fi

# Caches Laravel (config/route/view)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations (active via env RUN_MIGRATIONS=true)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Seed (active via env RUN_SEED=true)
if [ "$RUN_SEED" = "true" ]; then
    echo "Running seeders..."
    php artisan db:seed --force
fi

# Storage symlink (idempotent)
php artisan storage:link || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
