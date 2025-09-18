#!/bin/bash

# SavedFeast Docker Development Setup Script
# This script helps developers quickly set up the Docker environment

set -e

echo "🐳 SavedFeast Docker Development Setup"
echo "======================================"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed. Please install Docker Compose."
    exit 1
fi

echo "✅ Docker environment check passed"

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi

# Build and start services
echo "🔨 Building and starting Docker services..."
docker-compose up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check if services are running
if docker-compose ps | grep -q "Up"; then
    echo "✅ All services are running"
else
    echo "❌ Some services failed to start"
    docker-compose logs
    exit 1
fi

# Setup Laravel application
echo "🔧 Setting up Laravel application..."

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate --no-interaction

# Run migrations
echo "🗄️ Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker-compose exec -T app php artisan db:seed --force

# Create storage link
echo "📁 Creating storage link..."
docker-compose exec -T app php artisan storage:link

# Set permissions
echo "🔐 Setting proper permissions..."
docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec -T app chmod -R 755 storage bootstrap/cache

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📱 Access your application:"
echo "   🌐 Web Application: https://savedfeast.app"
echo "   🔌 API Endpoints: https://savedfeast.app/api"
echo "   🏥 Health Check: https://savedfeast.app/health"
echo ""
echo "🐳 Docker Commands:"
echo "   📋 View logs: docker-compose logs -f"
echo "   🛑 Stop services: docker-compose down"
echo "   🔄 Restart services: docker-compose restart"
echo "   🐚 Access container: docker-compose exec app bash"
echo ""
echo "📚 For more information, see DOCKER.md"
