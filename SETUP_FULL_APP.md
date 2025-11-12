# 🚀 Hướng Dẫn Setup Full TempEmail Application

## 📋 Tình Trạng Hiện Tại

**Vấn đề:** 
- Website http://15.235.203.107 chỉ hiển thị giao diện HTML tĩnh
- API không hoạt động vì không có Laravel backend
- Database chưa có tables
- Package `laravel-database-mail-templates` cần Laravel app để hoạt động

**Đã có:**
- ✅ Laravel 11 app mới tại `/var/www/tempemail-laravel/` trên server
- ✅ Giao diện TempEmail (HTML/Vue.js)
- ✅ TempEmail models, controllers, migrations trong package

## 🎯 2 Lựa Chọn

### Option A: Deploy Laravel App Đầy Đủ (Khuyến nghị) ⭐

Tạo một TempEmail service hoàn chỉnh với Laravel backend.

### Option B: Sử dụng Package Hiện Tại (Đơn giản)

Chỉ demo giao diện HTML, không có backend thực sự.

---

## 🔥 OPTION A: FULL LARAVEL APPLICATION

### Bước 1: Copy Files vào Laravel App

```bash
# Trên server
ssh ubuntu@15.235.203.107

# Copy các file TempEmail vào Laravel app
sudo cp -r /var/www/tempemail/app/Models/* /var/www/tempemail-laravel/app/Models/
sudo cp -r /var/www/tempemail/app/Http/Controllers/Api /var/www/tempemail-laravel/app/Http/Controllers/
sudo cp -r /var/www/tempemail/app/Services /var/www/tempemail-laravel/app/
sudo cp -r /var/www/tempemail/app/Jobs /var/www/tempemail-laravel/app/
sudo cp -r /var/www/tempemail/app/Events /var/www/tempemail-laravel/app/
sudo cp -r /var/www/tempemail/app/Mail /var/www/tempemail-laravel/app/
sudo cp -r /var/www/tempemail/database/migrations/* /var/www/tempemail-laravel/database/migrations/
sudo cp -r /var/www/tempemail/database/seeders/* /var/www/tempemail-laravel/database/seeders/
sudo cp -r /var/www/tempemail/config/temp-email.php /var/www/tempemail-laravel/config/
sudo cp /var/www/tempemail/routes/api.php /var/www/tempemail-laravel/routes/api.php
sudo cp /var/www/tempemail/public/index.html /var/www/tempemail-laravel/public/
sudo cp -r /var/www/tempemail/public/js /var/www/tempemail-laravel/public/

# Set permissions
sudo chown -R www-data:www-data /var/www/tempemail-laravel
sudo chmod -R 755 /var/www/tempemail-laravel/storage
sudo chmod -R 755 /var/www/tempemail-laravel/bootstrap/cache
```

### Bước 2: Install Spatie Package

```bash
cd /var/www/tempemail-laravel

# Install package qua composer
sudo docker run --rm -v $(pwd):/app composer require spatie/laravel-database-mail-templates
```

### Bước 3: Cấu hình .env

```bash
sudo nano /var/www/tempemail-laravel/.env
```

Thêm/sửa các dòng:

```env
APP_NAME="TempEmail Service"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://15.235.203.107

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tempemail_db
DB_USERNAME=tempemail_user
DB_PASSWORD=<password-from-current-.env>

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# TempEmail Settings
TEMPEMAIL_ENABLED=true
TEMPEMAIL_SESSION_LIFETIME=60
TEMPEMAIL_MAX_INBOX_SIZE=100
TEMPEMAIL_CLEANUP_ENABLED=true
TEMPEMAIL_WEBHOOK_ENABLED=false
```

### Bước 4: Tạo Docker Compose Mới

Tạo file `/var/www/tempemail-laravel/docker-compose.prod.yml`:

```yaml
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
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - REDIS_HOST=redis

  nginx:
    image: nginx:alpine
    container_name: tempemail_laravel_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
      - "8443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
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
    command: php artisan queue:work --sleep=3 --tries=3

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
```

### Bước 5: Tạo Dockerfile.prod

```bash
sudo nano /var/www/tempemail-laravel/Dockerfile.prod
```

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP settings
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
```

### Bước 6: Setup Nginx cho Laravel

```bash
sudo mkdir -p /var/www/tempemail-laravel/docker/nginx/conf.d
sudo nano /var/www/tempemail-laravel/docker/nginx/conf.d/default.conf
```

```nginx
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

    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.html /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;
}
```

### Bước 7: Build & Deploy

```bash
cd /var/www/tempemail-laravel

# Build containers
sudo docker-compose -f docker-compose.prod.yml build

# Start services
sudo docker-compose -f docker-compose.prod.yml up -d

# Generate app key
sudo docker-compose -f docker-compose.prod.yml exec app php artisan key:generate

# Run migrations
sudo docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Seed database
sudo docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# Optimize
sudo docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
sudo docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
sudo docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### Bước 8: Update Nginx Port Forwarding

Nếu muốn dùng port 80 (thay vì 8080):

```bash
# Stop old containers
cd /var/www/tempemail
sudo docker-compose -f docker-compose.prod.yml down

# Start new app on port 80
cd /var/www/tempemail-laravel
sudo docker-compose -f docker-compose.prod.yml down
sudo docker-compose -f docker-compose.prod.yml up -d
```

### Bước 9: Test

```bash
# Check containers
sudo docker-compose -f docker-compose.prod.yml ps

# Test API
curl http://15.235.203.107:8080/api/v1/email/generate

# View logs
sudo docker-compose -f docker-compose.prod.yml logs -f
```

---

## 🎨 OPTION B: SỬ DỤNG PACKAGE HIỆN TẠI (DEMO MODE)

Nếu chỉ muốn demo giao diện không cần backend:

### 1. Tạo Mock API trong JavaScript

Edit file `/var/www/tempemail/public/js/tempemail-api.js` và thêm mock data:

```javascript
// Mock API responses for demo
const MOCK_MODE = true;

if (MOCK_MODE) {
    window.TempEmailAPI = {
        generateEmail: async () => ({
            email: `temp${Math.random().toString(36).substr(2, 9)}@tempmail.com`,
            expires_at: new Date(Date.now() + 3600000).toISOString()
        }),
        
        getInbox: async (email) => {
            // Mock messages
            return {
                data: [
                    {
                        id: 1,
                        from: 'noreply@github.com',
                        subject: 'Verify your email address',
                        body_text: 'Your verification code is: 123456',
                        two_fa_code: '123456',
                        received_at: new Date().toISOString()
                    },
                    {
                        id: 2,
                        from: 'support@service.com',
                        subject: 'Welcome to our service',
                        body_text: 'Thank you for signing up!',
                        received_at: new Date(Date.now() - 300000).toISOString()
                    }
                ]
            };
        },
        
        checkNew: async (email) => ({ count: 0 }),
        deleteEmail: async (email) => ({ success: true })
    };
}
```

### 2. Upload và Test

```bash
# Upload mock API
scp /var/www/tempemail/public/js/tempemail-api.js ubuntu@15.235.203.107:/var/www/tempemail/public/js/

# Restart nginx
ssh ubuntu@15.235.203.107 'cd /var/www/tempemail && sudo docker-compose -f docker-compose.prod.yml restart nginx'
```

Giờ website sẽ hoạt động với mock data (không lưu database thật).

---

## 🎯 KHUYẾN NGHỊ

**Nên chọn Option A** nếu:
- ✅ Muốn TempEmail service thật sự hoạt động
- ✅ Cần lưu trữ emails trong database
- ✅ Cần API backend cho mobile app hoặc integrations
- ✅ Muốn có queue workers và scheduled jobs

**Chọn Option B** nếu:
- ✅ Chỉ cần demo giao diện
- ✅ Không cần database
- ✅ Chỉ test UI/UX
- ✅ Prototype nhanh

---

## 📞 Hỗ Trợ

Sau khi chọn option, bạn có thể:

1. **Option A:** Tôi sẽ tạo script tự động deploy full Laravel app
2. **Option B:** Tôi sẽ tạo mock API hoàn chỉnh cho demo

Bạn muốn option nào? 🚀
