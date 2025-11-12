#!/bin/bash

echo "🚀 Setting up TempEmail System..."
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Copy environment file
if [ ! -f .env ]; then
    echo "📝 Copying .env.example to .env..."
    cp .env.example .env
else
    echo "✅ .env file already exists"
fi

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --optimize-autoloader

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker-compose exec -T app php artisan db:seed --force

# Set permissions
echo "🔧 Setting permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T app chmod -R 755 /var/www/html/storage

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Access the application at: http://localhost"
echo "📧 Access Mailpit at: http://localhost:8025"
echo ""
echo "📝 To view logs:"
echo "   docker-compose logs -f app"
echo ""
echo "🛑 To stop the application:"
echo "   docker-compose down"
echo ""
echo "🔄 To restart:"
echo "   docker-compose restart"
echo ""
