@echo off
REM Dependency Fix Script for Windows
REM Run this to fix all dependency and composer issues

echo 🔧 Fixing Dependencies and Composer Issues...

REM 1. Clean and update composer
echo 📦 Cleaning and updating Composer dependencies...
if exist "vendor" rmdir /s /q vendor
composer clear-cache
composer update --ignore-platform-req=php --no-interaction

if %errorlevel% neq 0 (
    echo ❌ Composer update failed
    exit /b 1
)

echo ✅ Composer dependencies updated successfully

REM 2. Install production dependencies for CI
echo 📦 Installing production dependencies...
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

if %errorlevel% neq 0 (
    echo ❌ Production dependency installation failed
    exit /b 1
)

echo ✅ Production dependencies installed successfully

REM 3. Clean and update npm dependencies
echo 📦 Cleaning and updating frontend dependencies...
if exist "node_modules" rmdir /s /q node_modules
if exist "package-lock.json" del package-lock.json
npm install

if %errorlevel% neq 0 (
    echo ❌ Frontend dependency installation failed
    exit /b 1
)

echo ✅ Frontend dependencies updated successfully

REM 4. Generate Laravel key if needed
if not exist ".env" (
    echo 🔑 Generating Laravel application key...
    copy env.example .env
    php artisan key:generate --force
)

echo 🎉 All dependencies fixed! You can now run CI checks.
