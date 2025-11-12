# TempEmail System - Complete Setup Guide

A complete temporary email service built with Laravel, featuring database-driven mail templates using Spatie's package.

## 🚀 Features

- ✅ Generate temporary email addresses
- ✅ Auto-expire after configurable time (default 2 hours)
- ✅ Real-time inbox with auto-refresh
- ✅ Automatic 2FA code extraction
- ✅ Multiple domain support
- ✅ Custom domain capability
- ✅ Attachment handling
- ✅ REST API with rate limiting
- ✅ Database-driven email templates for notifications
- ✅ Broadcasting support for real-time updates
- ✅ Docker deployment ready

## 📋 Requirements

- PHP 8.1+
- MySQL 8.0+ or PostgreSQL 14+
- Redis
- Composer
- Docker (optional)

## 🛠️ Installation

### Option 1: Docker (Recommended)

```bash
# Clone the repository
git clone <your-repo>
cd laravel-database-mail-templates

# Copy environment file
cp .env.example .env

# Start Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed
```

Access the application at:
- App: http://localhost
- Mailpit: http://localhost:8025

### Option 2: Manual Installation

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure .env file with your database credentials
# Edit: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start queue worker
php artisan queue:work

# Start scheduler (in crontab)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

# Serve application
php artisan serve
```

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── TempEmailController.php
│   │   ├── MessageController.php
│   │   ├── DomainController.php
│   │   └── TemplateController.php
│   ├── Models/
│   │   ├── TempEmail.php
│   │   ├── InboxMessage.php
│   │   ├── Domain.php
│   │   └── Attachment.php
│   ├── Mail/
│   │   ├── NewDomainAdded.php
│   │   ├── SystemAlert.php
│   │   └── DailyReport.php
│   ├── Jobs/
│   │   ├── CheckMailboxJob.php
│   │   └── CleanupExpiredEmailsJob.php
│   ├── Events/
│   │   └── NewEmailReceived.php
│   └── Services/
│       └── MailReceiver.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── index.html          # Frontend UI
│   └── js/
│       └── tempemail-api.js
├── routes/
│   └── api.php
├── config/
│   └── temp-email.php
└── docker-compose.yml
```

## 🔌 API Endpoints

### Public Endpoints

```bash
# Generate new temp email
POST /api/v1/email/generate
Body: { "domain_id": 1, "lifetime_hours": 2 }

# Get email info
GET /api/v1/email/{email}

# Get inbox
GET /api/v1/email/{email}/inbox

# Check for new messages
GET /api/v1/email/{email}/check

# Delete email
DELETE /api/v1/email/{email}

# Get message
GET /api/v1/message/{id}

# Get message HTML
GET /api/v1/message/{id}/html

# Download attachment
GET /api/v1/attachment/{id}/download

# List domains
GET /api/v1/domains
```

### Admin Endpoints (Require Authentication)

```bash
# Manage domains
POST /api/v1/admin/domains
PUT /api/v1/admin/domains/{id}
DELETE /api/v1/admin/domains/{id}

# Manage templates
GET /api/v1/admin/templates
POST /api/v1/admin/templates
PUT /api/v1/admin/templates/{id}
DELETE /api/v1/admin/templates/{id}
POST /api/v1/admin/templates/{id}/preview
```

## 🎨 Frontend Usage

The system includes a ready-to-use Vue.js frontend at `public/index.html`:

```javascript
// Initialize API client
const api = new TempEmailAPI('http://localhost/api/v1');

// Generate email
const { data } = await api.generateEmail();
console.log(data.email); // e.g., abc123@tempmail.xyz

// Get inbox
const { data } = await api.getInbox('abc123@tempmail.xyz');
console.log(data.messages);

// Check for new messages
await api.checkNewMessages('abc123@tempmail.xyz');
```

## ⚙️ Configuration

Edit `.env` file:

```env
# Email lifetime
TEMP_EMAIL_LIFETIME=2
TEMP_EMAIL_MAX_LIFETIME=24

# Rate limiting
TEMP_EMAIL_RATE_LIMIT_ENABLED=true
TEMP_EMAIL_MAX_REQUESTS_PER_HOUR=10
TEMP_EMAIL_MAX_PER_IP=5

# 2FA detection
TEMP_EMAIL_2FA_DETECTION=true

# Cleanup
TEMP_EMAIL_CLEANUP_ENABLED=true
TEMP_EMAIL_DELETE_AFTER_DAYS=7
```

## 📧 Mail Templates

This system uses Spatie's Laravel Database Mail Templates for notifications:

### Creating a new template:

```php
use Spatie\MailTemplates\Models\MailTemplate;

MailTemplate::create([
    'mailable' => \App\Mail\YourMailable::class,
    'subject' => 'Hello {{ name }}',
    'html_template' => '<p>Welcome {{ name }}!</p>',
    'text_template' => 'Welcome {{ name }}!',
]);
```

### Sending notification:

```php
use App\Mail\NewDomainAdded;

Mail::to('admin@example.com')
    ->send(new NewDomainAdded($domain, $user));
```

## 🔄 Background Jobs

### Queue Workers

```bash
# Start queue worker
php artisan queue:work

# With supervisor (production)
[program:tempemail-worker]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
```

### Scheduled Tasks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup expired emails daily
    $schedule->job(new CleanupExpiredEmailsJob)
        ->daily();
    
    // Send daily report
    $schedule->job(new SendDailyReportJob)
        ->dailyAt('09:00');
}
```

## 🚀 Deployment

### Production Checklist

```bash
# Set environment
APP_ENV=production
APP_DEBUG=false

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue worker with Supervisor
# See supervisor config in docs

# Setup HTTPS with Let's Encrypt
# Configure nginx for SSL
```

### Docker Production

```bash
# Build for production
docker-compose -f docker-compose.prod.yml up -d

# Scale queue workers
docker-compose up -d --scale queue=3
```

## 🧪 Testing

```bash
# Run tests
php artisan test

# With coverage
php artisan test --coverage
```

## 📊 Monitoring

- Queue: Use Laravel Horizon for queue monitoring
- Logs: `storage/logs/laravel.log`
- Mailpit: http://localhost:8025 (development)

## 🔒 Security

- Rate limiting enabled by default
- CORS configured for API
- SQL injection protection via Eloquent
- XSS protection on user inputs
- File upload validation for attachments

## 📝 License

MIT License

## 🤝 Contributing

Contributions welcome! Please read CONTRIBUTING.md first.

## 📞 Support

For issues and questions, please use GitHub Issues.
