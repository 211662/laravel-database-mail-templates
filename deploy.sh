#!/bin/bash

# TempEmail Production Deployment Script
# Server: 15.235.203.107

set -e

echo "🚀 TempEmail Production Deployment"
echo "=================================="
echo ""

# Configuration
SERVER_IP="15.235.203.107"
SERVER_USER="ubuntu"
REMOTE_PATH="/var/www/tempemail"
LOCAL_PATH=$(pwd)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check SSH connection
log_info "Checking SSH connection to $SERVER_IP..."
if ! ssh -o ConnectTimeout=5 $SERVER_USER@$SERVER_IP "echo 'SSH OK'" &>/dev/null; then
    log_error "Cannot connect to server via SSH"
    echo "Please ensure:"
    echo "  1. SSH key is added: ssh-copy-id $SERVER_USER@$SERVER_IP"
    echo "  2. Server is accessible"
    exit 1
fi
log_info "SSH connection OK"

# Install Docker on server if not installed
log_info "Checking Docker installation on server..."
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
    if ! command -v docker &> /dev/null; then
        echo "Installing Docker..."
        curl -fsSL https://get.docker.com -o get-docker.sh
        sudo sh get-docker.sh
        sudo systemctl enable docker
        sudo systemctl start docker
        
        # Add current user to docker group
        sudo usermod -aG docker ubuntu
        echo "User added to docker group."
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        echo "Installing Docker Compose..."
        sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
    fi
    
    docker --version
    docker-compose --version || echo "Docker Compose plugin available"
ENDSSH

# Create remote directory
log_info "Creating remote directory..."
ssh $SERVER_USER@$SERVER_IP "sudo mkdir -p $REMOTE_PATH && sudo chown -R ubuntu:ubuntu $REMOTE_PATH"

# Copy files to server
log_info "Copying files to server..."
rsync -avz --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude '.git' \
    --exclude 'storage/logs/*' \
    --exclude '.env' \
    $LOCAL_PATH/ $SERVER_USER@$SERVER_IP:$REMOTE_PATH/

# Setup environment on server
log_info "Setting up environment..."
ssh $SERVER_USER@$SERVER_IP << ENDSSH
    cd $REMOTE_PATH
    
    # Create .env if not exists
    if [ ! -f .env ]; then
        cp .env.example .env
        
        # Generate random passwords
        DB_PASSWORD=\$(openssl rand -base64 32)
        DB_ROOT_PASSWORD=\$(openssl rand -base64 32)
        APP_KEY=\$(openssl rand -base64 32)
        
        # Update .env
        sed -i "s|APP_ENV=local|APP_ENV=production|g" .env
        sed -i "s|APP_DEBUG=true|APP_DEBUG=false|g" .env
        sed -i "s|APP_URL=http://localhost|APP_URL=http://$SERVER_IP|g" .env
        sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=\$DB_PASSWORD|g" .env
        
        # Add production passwords to .env
        echo "" >> .env
        echo "# Production Passwords" >> .env
        echo "DB_ROOT_PASSWORD=\$DB_ROOT_PASSWORD" >> .env
        
        echo "✅ .env file created with secure passwords"
    fi
    
    # Create storage directories
    mkdir -p storage/app/public
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    
    # Set permissions
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
ENDSSH

# Deploy with Docker Compose
log_info "Starting Docker containers..."
ssh $SERVER_USER@$SERVER_IP << ENDSSH
    cd $REMOTE_PATH
    
    # Stop existing containers
    sudo docker-compose -f docker-compose.prod.yml down || true
    
    # Build and start
    sudo docker-compose -f docker-compose.prod.yml up -d --build
    
    # Wait for services to be ready
    echo "Waiting for services to be ready..."
    sleep 20
    
    # Package deployed successfully
    echo "TempEmail package deployed successfully!"
    
    # Set permissions
    sudo docker-compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage || true
    sudo docker-compose -f docker-compose.prod.yml exec -T app chmod -R 755 /var/www/html/storage || true
ENDSSH

# Setup firewall
log_info "Configuring firewall..."
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
    if command -v ufw &> /dev/null; then
        sudo ufw --force enable
        sudo ufw allow 22/tcp
        sudo ufw allow 80/tcp
        sudo ufw allow 443/tcp
        sudo ufw status
    fi
ENDSSH

echo ""
echo "=================================="
log_info "🎉 Deployment complete!"
echo ""
log_info "Application URL: http://$SERVER_IP"
log_info "API URL: http://$SERVER_IP/api/v1"
echo ""
log_info "Useful commands:"
echo "  View logs:     ssh $SERVER_USER@$SERVER_IP 'cd $REMOTE_PATH && docker-compose -f docker-compose.prod.yml logs -f'"
echo "  Restart:       ssh $SERVER_USER@$SERVER_IP 'cd $REMOTE_PATH && docker-compose -f docker-compose.prod.yml restart'"
echo "  Stop:          ssh $SERVER_USER@$SERVER_IP 'cd $REMOTE_PATH && docker-compose -f docker-compose.prod.yml down'"
echo "  SSH to server: ssh $SERVER_USER@$SERVER_IP"
echo ""
log_warn "Next steps:"
echo "  1. Setup domain name (if needed)"
echo "  2. Install SSL certificate with Let's Encrypt"
echo "  3. Configure email sending (SMTP/Mailgun/etc)"
echo "  4. Setup monitoring and backups"
echo ""
