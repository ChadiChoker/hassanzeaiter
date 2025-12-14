#!/bin/bash

set -e

echo "🐳 Setting up Docker environment for Classified Ads API..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed. Please install docker-compose first."
    exit 1
fi

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.docker..."
    cp .env.docker .env
else
    echo "✓ .env file already exists"
fi

# Build and start containers
echo "🏗️  Building Docker containers..."
docker-compose build

echo "🚀 Starting Docker containers..."
docker-compose up -d

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Install composer dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate application key if not set
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗃️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database with categories and fields
echo "🌱 Seeding database with categories and fields..."
docker-compose exec -T app php artisan db:seed --class=CategoriesAndFieldsSeeder

# Clear and cache config
echo "🧹 Clearing and caching configuration..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan config:cache

echo "
✅ Setup complete!

Your application is now running at:
- API: http://localhost:8000
- PhpMyAdmin: http://localhost:8080

To view logs:
  docker-compose logs -f

To stop the application:
  docker-compose down

To restart the application:
  docker-compose restart

To run artisan commands:
  docker-compose exec app php artisan <command>

To access the application container:
  docker-compose exec app bash
"
