#!/bin/bash

# SSL Setup Script with Let's Encrypt
# Run this AFTER deploy.sh

SERVER_IP="15.235.203.107"
SERVER_USER="root"
DOMAIN="yourdomain.com"  # Change this to your domain
EMAIL="admin@yourdomain.com"  # Change this

echo "🔒 Setting up SSL with Let's Encrypt"
echo "====================================="
echo ""

ssh $SERVER_USER@$SERVER_IP << ENDSSH
    cd /var/www/tempemail
    
    # Install certbot
    apt-get update
    apt-get install -y certbot
    
    # Stop nginx temporarily
    docker-compose -f docker-compose.prod.yml stop nginx
    
    # Get certificate
    certbot certonly --standalone \
        -d $DOMAIN \
        --non-interactive \
        --agree-tos \
        --email $EMAIL
    
    # Copy certificates to docker volume
    mkdir -p docker/ssl
    cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem docker/ssl/cert.pem
    cp /etc/letsencrypt/live/$DOMAIN/privkey.pem docker/ssl/key.pem
    
    # Update nginx config to use HTTPS
    sed -i 's|server_name 15.235.203.107;|server_name $DOMAIN;|g' docker/nginx/conf.d/production.conf
    
    # Restart nginx
    docker-compose -f docker-compose.prod.yml start nginx
    
    # Setup auto-renewal
    echo "0 0,12 * * * root certbot renew --quiet && cp /etc/letsencrypt/live/$DOMAIN/*.pem /var/www/tempemail/docker/ssl/ && docker-compose -f /var/www/tempemail/docker-compose.prod.yml restart nginx" | tee -a /etc/crontab > /dev/null
    
    echo "✅ SSL certificate installed!"
    echo "Your site is now available at: https://$DOMAIN"
ENDSSH
