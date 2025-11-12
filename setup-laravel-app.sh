#!/bin/bash

set -e  # Exit on error

echo "🚀 TempEmail Full Laravel Application Setup"
echo "=========================================="
echo ""

SERVER_IP="15.235.203.107"
SERVER_USER="ubuntu"
LARAVEL_DIR="/var/www/tempemail-laravel"
PACKAGE_DIR="/var/www/tempemail"

echo "[INFO] Connecting to server $SERVER_IP..."
echo ""

ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
set -e

LARAVEL_DIR="/var/www/tempemail-laravel"
PACKAGE_DIR="/var/www/tempemail"

echo "[STEP 1/10] Copying TempEmail files to Laravel app..."
# Models
sudo cp -r ${PACKAGE_DIR}/app/Models/Domain.php ${LARAVEL_DIR}/app/Models/
sudo cp -r ${PACKAGE_DIR}/app/Models/TempEmail.php ${LARAVEL_DIR}/app/Models/
sudo cp -r ${PACKAGE_DIR}/app/Models/InboxMessage.php ${LARAVEL_DIR}/app/Models/
sudo cp -r ${PACKAGE_DIR}/app/Models/Attachment.php ${LARAVEL_DIR}/app/Models/

# Controllers
sudo mkdir -p ${LARAVEL_DIR}/app/Http/Controllers/Api
sudo cp -r ${PACKAGE_DIR}/app/Http/Controllers/Api/* ${LARAVEL_DIR}/app/Http/Controllers/Api/

# Services, Jobs, Events, Mail
sudo cp -r ${PACKAGE_DIR}/app/Services ${LARAVEL_DIR}/app/
sudo cp -r ${PACKAGE_DIR}/app/Jobs ${LARAVEL_DIR}/app/
sudo cp -r ${PACKAGE_DIR}/app/Events ${LARAVEL_DIR}/app/
sudo cp -r ${PACKAGE_DIR}/app/Mail ${LARAVEL_DIR}/app/

# Migrations
sudo cp ${PACKAGE_DIR}/database/migrations/*_create_domains_table.php ${LARAVEL_DIR}/database/migrations/
sudo cp ${PACKAGE_DIR}/database/migrations/*_create_temp_emails_table.php ${LARAVEL_DIR}/database/migrations/
sudo cp ${PACKAGE_DIR}/database/migrations/*_create_inbox_messages_table.php ${LARAVEL_DIR}/database/migrations/
sudo cp ${PACKAGE_DIR}/database/migrations/*_create_attachments_table.php ${LARAVEL_DIR}/database/migrations/
sudo cp ${PACKAGE_DIR}/database/migrations/*_create_api_requests_table.php ${LARAVEL_DIR}/database/migrations/

# Seeders
sudo cp ${PACKAGE_DIR}/database/seeders/DomainSeeder.php ${LARAVEL_DIR}/database/seeders/
sudo cp ${PACKAGE_DIR}/database/seeders/MailTemplateSeeder.php ${LARAVEL_DIR}/database/seeders/

# Config
sudo cp ${PACKAGE_DIR}/config/temp-email.php ${LARAVEL_DIR}/config/

# Public files (Frontend)
sudo cp ${PACKAGE_DIR}/public/index.html ${LARAVEL_DIR}/public/
sudo mkdir -p ${LARAVEL_DIR}/public/js
sudo cp -r ${PACKAGE_DIR}/public/js/* ${LARAVEL_DIR}/public/js/

echo "✅ Files copied successfully"
echo ""

echo "[STEP 2/10] Installing Spatie package..."
cd ${LARAVEL_DIR}
sudo docker run --rm -v $(pwd):/app composer require spatie/laravel-database-mail-templates mustache/mustache
echo "✅ Package installed"
echo ""

echo "[STEP 3/10] Configuring environment..."
# Get password from old .env
DB_PASSWORD=$(grep DB_PASSWORD ${PACKAGE_DIR}/.env | cut -d '=' -f2)
DB_ROOT_PASSWORD=$(grep DB_ROOT_PASSWORD ${PACKAGE_DIR}/.env | cut -d '=' -f2)

# Update .env
sudo sed -i "s/DB_DATABASE=.*/DB_DATABASE=tempemail_db/" ${LARAVEL_DIR}/.env
sudo sed -i "s/DB_USERNAME=.*/DB_USERNAME=tempemail_user/" ${LARAVEL_DIR}/.env
sudo sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" ${LARAVEL_DIR}/.env
sudo sed -i "s/DB_HOST=.*/DB_HOST=mysql/" ${LARAVEL_DIR}/.env

# Add missing env vars
echo "" | sudo tee -a ${LARAVEL_DIR}/.env
echo "DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}" | sudo tee -a ${LARAVEL_DIR}/.env
echo "" | sudo tee -a ${LARAVEL_DIR}/.env
echo "CACHE_STORE=redis" | sudo tee -a ${LARAVEL_DIR}/.env
echo "SESSION_DRIVER=redis" | sudo tee -a ${LARAVEL_DIR}/.env
echo "QUEUE_CONNECTION=redis" | sudo tee -a ${LARAVEL_DIR}/.env
echo "" | sudo tee -a ${LARAVEL_DIR}/.env
echo "REDIS_HOST=redis" | sudo tee -a ${LARAVEL_DIR}/.env
echo "REDIS_PASSWORD=null" | sudo tee -a ${LARAVEL_DIR}/.env
echo "REDIS_PORT=6379" | sudo tee -a ${LARAVEL_DIR}/.env
echo "" | sudo tee -a ${LARAVEL_DIR}/.env
echo "# TempEmail Settings" | sudo tee -a ${LARAVEL_DIR}/.env
echo "TEMPEMAIL_ENABLED=true" | sudo tee -a ${LARAVEL_DIR}/.env
echo "TEMPEMAIL_SESSION_LIFETIME=60" | sudo tee -a ${LARAVEL_DIR}/.env
echo "TEMPEMAIL_MAX_INBOX_SIZE=100" | sudo tee -a ${LARAVEL_DIR}/.env

echo "✅ Environment configured"
echo ""

echo "[STEP 4/10] Updating routes..."
sudo tee ${LARAVEL_DIR}/routes/api.php > /dev/null << 'APIROUTES'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TempEmailController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\TemplateController;

Route::prefix('v1')->group(function () {
    // TempEmail endpoints
    Route::prefix('email')->group(function () {
        Route::post('generate', [TempEmailController::class, 'generate']);
        Route::get('{email}/inbox', [TempEmailController::class, 'getInbox']);
        Route::get('{email}/check-new', [TempEmailController::class, 'checkNew']);
        Route::delete('{email}', [TempEmailController::class, 'delete']);
        Route::get('{email}', [TempEmailController::class, 'show']);
    });
    
    // Message endpoints
    Route::prefix('messages')->group(function () {
        Route::get('{id}', [MessageController::class, 'show']);
        Route::post('{id}/mark-read', [MessageController::class, 'markAsRead']);
        Route::delete('{id}', [MessageController::class, 'delete']);
    });
    
    // Domain endpoints
    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index']);
        Route::get('active', [DomainController::class, 'active']);
    });
    
    // Mail Template endpoints (Admin)
    Route::prefix('templates')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [TemplateController::class, 'index']);
        Route::post('/', [TemplateController::class, 'store']);
        Route::get('{id}', [TemplateController::class, 'show']);
        Route::put('{id}', [TemplateController::class, 'update']);
        Route::delete('{id}', [TemplateController::class, 'destroy']);
    });
});
APIROUTES

echo "✅ Routes updated"
echo ""

echo "[STEP 5/10] Creating Dockerfile..."
sudo tee ${LARAVEL_DIR}/Dockerfile.prod > /dev/null << 'DOCKERFILE'
FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini

RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
DOCKERFILE

echo "✅ Dockerfile created"
echo ""

echo "[STEP 6/10] Creating docker-compose.prod.yml..."
sudo tee ${LARAVEL_DIR}/docker-compose.prod.yml > /dev/null << 'DOCKERCOMPOSE'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    container_name: tempemail_laravel_app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./storage:/var/www/html/storage
    networks:
      - tempemail_laravel
    depends_on:
      - mysql
      - redis
    environment:
      DB_CONNECTION: mysql
      DB_HOST: mysql
      REDIS_HOST: redis

  nginx:
    image: nginx:alpine
    container_name: tempemail_laravel_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/conf.d:/etc/nginx/conf.d:ro
    networks:
      - tempemail_laravel
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: tempemail_laravel_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - tempemail_laravel
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: tempemail_laravel_redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - tempemail_laravel

  queue:
    build:
      context: .
      dockerfile: Dockerfile.prod
    container_name: tempemail_laravel_queue
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - tempemail_laravel
    depends_on:
      - mysql
      - redis
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile.prod
    container_name: tempemail_laravel_scheduler
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - tempemail_laravel
    depends_on:
      - mysql
      - redis
    command: >
      sh -c "while true; do
        php artisan schedule:run --verbose --no-interaction &
        sleep 60
      done"

networks:
  tempemail_laravel:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
DOCKERCOMPOSE

echo "✅ Docker Compose created"
echo ""

echo "[STEP 7/10] Creating Nginx configuration..."
sudo mkdir -p ${LARAVEL_DIR}/docker/nginx/conf.d

sudo tee ${LARAVEL_DIR}/docker/nginx/conf.d/default.conf > /dev/null << 'NGINXCONF'
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.html index.php;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    client_max_body_size 10M;

    # Serve static HTML first, then PHP
    location / {
        try_files $uri $uri/ /index.html /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_intercept_errors on;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git|svn) {
        deny all;
        return 404;
    }

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;
}
NGINXCONF

echo "✅ Nginx config created"
echo ""

echo "[STEP 8/10] Stopping old containers..."
cd ${PACKAGE_DIR}
sudo docker-compose -f docker-compose.prod.yml down || true
echo "✅ Old containers stopped"
echo ""

echo "[STEP 9/10] Building new Laravel app containers..."
cd ${LARAVEL_DIR}
sudo docker-compose -f docker-compose.prod.yml build --no-cache
echo "✅ Containers built"
echo ""

echo "[STEP 10/10] Starting services and running migrations..."
sudo docker-compose -f docker-compose.prod.yml up -d

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
sleep 15

# Generate app key
echo "Generating application key..."
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan key:generate --force

# Run migrations
echo "Running migrations..."
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Run seeders
echo "Seeding database..."
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan db:seed --class=DomainSeeder --force

# Optimize application
echo "Optimizing application..."
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
sudo docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache

# Fix permissions
sudo docker-compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage
sudo docker-compose -f docker-compose.prod.yml exec -T app chmod -R 755 /var/www/html/storage

echo "✅ Application deployed and optimized"
echo ""

ENDSSH

echo ""
echo "=========================================="
echo "🎉 Setup Complete!"
echo "=========================================="
echo ""
echo "📊 Checking deployment status..."
echo ""

ssh ${SERVER_USER}@${SERVER_IP} "cd ${LARAVEL_DIR} && sudo docker-compose -f docker-compose.prod.yml ps"

echo ""
echo "✅ Application URL: http://${SERVER_IP}"
echo "✅ API URL: http://${SERVER_IP}/api/v1"
echo ""
echo "🔧 Useful commands:"
echo "  View logs:     ssh ${SERVER_USER}@${SERVER_IP} 'cd ${LARAVEL_DIR} && sudo docker-compose -f docker-compose.prod.yml logs -f'"
echo "  Restart:       ssh ${SERVER_USER}@${SERVER_IP} 'cd ${LARAVEL_DIR} && sudo docker-compose -f docker-compose.prod.yml restart'"
echo "  Test API:      curl http://${SERVER_IP}/api/v1/email/generate"
echo ""
echo "🎊 Full TempEmail application is now live!"
