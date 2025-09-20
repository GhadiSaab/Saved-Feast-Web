#!/bin/bash

# Dependency Fix Script
# Run this to fix all dependency and composer issues

echo "🔧 Fixing Dependencies and Composer Issues..."

# 1. Clean and update composer
echo "📦 Cleaning and updating Composer dependencies..."
rm -rf vendor/
composer clear-cache
composer update --ignore-platform-req=php --no-interaction

if [ $? -ne 0 ]; then
    echo "❌ Composer update failed"
    exit 1
fi

echo "✅ Composer dependencies updated successfully"

# 2. Install production dependencies for CI
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

if [ $? -ne 0 ]; then
    echo "❌ Production dependency installation failed"
    exit 1
fi

echo "✅ Production dependencies installed successfully"

# 3. Clean and update npm dependencies
echo "📦 Cleaning and updating frontend dependencies..."
rm -rf node_modules/
rm -f package-lock.json
npm install

if [ $? -ne 0 ]; then
    echo "❌ Frontend dependency installation failed"
    exit 1
fi

echo "✅ Frontend dependencies updated successfully"

# 4. Generate Laravel key if needed
if [ ! -f ".env" ]; then
    echo "🔑 Generating Laravel application key..."
    cp env.example .env
    php artisan key:generate --force
fi

echo "🎉 All dependencies fixed! You can now run CI checks."
