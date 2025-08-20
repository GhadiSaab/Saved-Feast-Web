@echo off
REM SavedFeast Setup Script for Windows
REM This script automates the setup process for the SavedFeast application

echo 🍽️  SavedFeast Setup Script
echo ================================
echo.

setlocal enabledelayedexpansion

REM Check if required tools are installed
echo [INFO] Checking requirements...

REM Check PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP is not installed. Please install PHP 8.2 or higher.
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('php --version 2^>^&1 ^| findstr "PHP"') do set PHP_VERSION=%%i
echo [SUCCESS] %PHP_VERSION%

REM Check Composer
composer --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer is not installed. Please install Composer.
    pause
    exit /b 1
)
echo [SUCCESS] Composer is installed

REM Check Node.js
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed. Please install Node.js 18 or higher.
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('node --version') do set NODE_VERSION=%%i
echo [SUCCESS] Node.js version: %NODE_VERSION%

REM Check npm
npm --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npm is not installed.
    pause
    exit /b 1
)
echo [SUCCESS] npm is installed

echo.

REM Install dependencies
echo [INFO] Installing dependencies...

echo [INFO] Installing PHP dependencies with Composer...
composer install --no-interaction
if errorlevel 1 (
    echo [ERROR] Failed to install PHP dependencies.
    pause
    exit /b 1
)

echo [INFO] Installing Node.js dependencies...
npm install
if errorlevel 1 (
    echo [ERROR] Failed to install Node.js dependencies.
    pause
    exit /b 1
)

echo [SUCCESS] Dependencies installed successfully
echo.

REM Setup environment
echo [INFO] Setting up environment...

REM Copy .env file if it doesn't exist
if not exist .env (
    copy .env.example .env
    echo [SUCCESS] .env file created from .env.example
) else (
    echo [WARNING] .env file already exists, skipping...
)

REM Generate application key
echo [INFO] Generating application key...
php artisan key:generate --no-interaction
if errorlevel 1 (
    echo [ERROR] Failed to generate application key.
    pause
    exit /b 1
)

echo [SUCCESS] Environment setup completed
echo.

REM Setup database
echo [INFO] Setting up database...

REM Check if .env exists
if not exist .env (
    echo [ERROR] .env file not found. Please run setup_environment first.
    pause
    exit /b 1
)

echo [WARNING] Please ensure your MySQL database is running and create the database manually.
echo [INFO] You can create the database using: CREATE DATABASE savedfeast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
pause

REM Run migrations
echo [INFO] Running database migrations...
php artisan migrate --no-interaction
if errorlevel 1 (
    echo [ERROR] Failed to run migrations.
    pause
    exit /b 1
)

REM Seed database
echo [INFO] Seeding database with demo data...
php artisan db:seed --no-interaction
if errorlevel 1 (
    echo [ERROR] Failed to seed database.
    pause
    exit /b 1
)

REM Assign default roles
echo [INFO] Assigning default roles to users...
php artisan users:assign-default-roles --no-interaction
if errorlevel 1 (
    echo [WARNING] Failed to assign default roles.
)

echo [SUCCESS] Database setup completed
echo.

REM Setup file storage
echo [INFO] Setting up file storage...
php artisan storage:link --no-interaction
if errorlevel 1 (
    echo [WARNING] Failed to create storage link.
)

echo [SUCCESS] File storage setup completed
echo.

REM Build frontend
echo [INFO] Building frontend assets...
npm run build
if errorlevel 1 (
    echo [ERROR] Failed to build frontend assets.
    pause
    exit /b 1
)

echo [SUCCESS] Frontend assets built successfully
echo.

REM Display final information
echo.
echo 🎉 Setup completed successfully!
echo ================================
echo.
echo Next steps:
echo 1. Start both development servers ^(recommended^):
echo    php artisan serve:full
echo.
echo 2. Or start servers separately:
echo    Terminal 1: php artisan serve
echo    Terminal 2: npm run dev
echo.
echo 3. Access the application:
echo    Backend API: http://localhost:8000/api
echo    Frontend: http://localhost:5173
echo.
echo Demo users:
echo    Admin: admin@savedfeast.com / password
echo    Provider: provider@savedfeast.com / password
echo.
echo API Documentation:
echo    OpenAPI: docs/api/openapi.yaml
echo    Postman: docs/api/SavedFeast_API.postman_collection.json
echo.
echo Happy coding! 🚀
echo.
pause 