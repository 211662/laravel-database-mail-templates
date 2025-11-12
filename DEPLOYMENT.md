# TempEmail Production Deployment Guide

## Server: 15.235.203.107

### Prerequisites

1. **Server Requirements:**
   - Ubuntu 20.04+ / Debian 11+
   - Root or sudo access
   - Minimum 2GB RAM, 20GB storage
   - Docker & Docker Compose installed (auto-installed by script)

2. **Local Requirements:**
   - SSH access to server
   - rsync installed

### Quick Deploy

```bash
# 1. Make deploy script executable
chmod +x deploy.sh

# 2. Setup SSH key (if not already done)
ssh-copy-id root@15.235.203.107

# 3. Deploy
./deploy.sh
```

The script will:
- ✅ Install Docker & Docker Compose on server
- ✅ Copy all files to server
- ✅ Setup environment with secure passwords
- ✅ Build and start Docker containers
- ✅ Run migrations and seeders
- ✅ Configure firewall
- ✅ Optimize application

### Access Your Application

After deployment:
- **Frontend:** http://15.235.203.107
- **API:** http://15.235.203.107/api/v1
- **Health Check:** http://15.235.203.107/api/health

### Post-Deployment Steps

#### 1. Setup Domain Name (Optional)

```bash
# On server
ssh root@15.235.203.107

# Edit nginx config
nano /var/www/tempemail/docker/nginx/conf.d/production.conf
# Change: server_name 15.235.203.107;
# To: server_name yourdomain.com;

# Restart nginx
cd /var/www/tempemail
docker-compose -f docker-compose.prod.yml restart nginx
```

#### 2. Install SSL Certificate

```bash
# Make SSL script executable
chmod +x setup-ssl.sh

# Edit domain in script
nano setup-ssl.sh
# Change DOMAIN and EMAIL

# Run SSL setup
./setup-ssl.sh
```

#### 3. Configure Email Sending

```bash
# SSH to server
ssh root@15.235.203.107

# Edit .env
cd /var/www/tempemail
nano .env

# Add your SMTP settings:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="TempEmail"

# Restart containers
docker-compose -f docker-compose.prod.yml restart
```

#### 4. Setup Monitoring

```bash
# Install monitoring tools
ssh root@15.235.203.107

# Install htop, iotop
apt-get install -y htop iotop

# Monitor Docker containers
docker stats

# View application logs
cd /var/www/tempemail
docker-compose -f docker-compose.prod.yml logs -f app
```

#### 5. Setup Automated Backups

```bash
# Create backup script
ssh root@15.235.203.107

cat > /root/backup-tempemail.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backup/tempemail"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
docker exec tempemail_mysql mysqldump -u root -p$DB_ROOT_PASSWORD tempemail > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/tempemail

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
EOF

chmod +x /root/backup-tempemail.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /root/backup-tempemail.sh >> /var/log/backup.log 2>&1" | crontab -
```

### Useful Commands

```bash
# View logs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs -f'

# Restart services
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml restart'

# Stop services
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml down'

# Update application
./deploy.sh  # Run again

# Clear cache
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear'

# Run migrations
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml exec app php artisan migrate'

# View queue jobs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs -f queue'

# Access MySQL
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml exec mysql mysql -u tempemail -p tempemail'

# Check server resources
ssh root@15.235.203.107 'htop'
ssh root@15.235.203.107 'docker stats'
```

### Troubleshooting

#### Application not accessible
```bash
# Check if containers are running
ssh root@15.235.203.107 'docker ps'

# Check nginx logs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs nginx'

# Check app logs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs app'
```

#### Database connection errors
```bash
# Check MySQL logs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs mysql'

# Verify .env database settings
ssh root@15.235.203.107 'cd /var/www/tempemail && cat .env | grep DB_'
```

#### Queue not processing
```bash
# Check queue worker logs
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml logs queue'

# Restart queue workers
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml restart queue'
```

#### Storage permission issues
```bash
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage && docker-compose -f docker-compose.prod.yml exec app chmod -R 755 storage'
```

### Security Checklist

- ✅ Firewall configured (UFW)
- ✅ Only ports 22, 80, 443 open
- ✅ Strong database passwords (auto-generated)
- ✅ .env file not exposed
- ✅ Rate limiting enabled
- ✅ Security headers configured
- ⬜ SSL certificate installed (run setup-ssl.sh)
- ⬜ Regular backups configured
- ⬜ Monitoring setup
- ⬜ Fail2ban installed (recommended)

### Performance Optimization

Already configured:
- ✅ OPcache enabled
- ✅ Config/Route/View caching
- ✅ Composer autoload optimized
- ✅ Redis for cache and queues
- ✅ 3 queue workers for parallel processing
- ✅ Nginx caching for static assets
- ✅ Gzip compression enabled

### Scaling

To handle more load:

```bash
# Scale queue workers
ssh root@15.235.203.107 'cd /var/www/tempemail && docker-compose -f docker-compose.prod.yml up -d --scale queue=5'

# Add more app instances (requires load balancer)
# Setup Nginx as load balancer in front of multiple app containers
```

### Support

If you encounter issues:
1. Check logs: `docker-compose -f docker-compose.prod.yml logs`
2. Verify all containers are running: `docker ps`
3. Check server resources: `htop`, `df -h`
4. Review nginx error logs: `/var/log/nginx/tempemail_error.log`

---

**Your TempEmail system is now live at http://15.235.203.107** 🚀
