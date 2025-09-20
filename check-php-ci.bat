@echo off
REM PHP CI Checks Script for Windows
REM Run this to test PHP dependencies and code quality locally

echo 🔍 Running PHP CI Checks...

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: composer.json not found. Please run this script from the project root.
    exit /b 1
)

REM 1. Install/Update Dependencies
echo 📦 Installing PHP dependencies...
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

if %errorlevel% neq 0 (
    echo ❌ PHP dependency installation failed
    exit /b 1
)

echo ✅ PHP dependencies installed successfully

REM 2. Check for security vulnerabilities
echo 🔒 Checking for security vulnerabilities...
composer audit

if %errorlevel% neq 0 (
    echo ⚠️  Security vulnerabilities found. Check composer audit output above.
    REM Don't exit here, just warn
)

REM 3. Run PHP syntax check
echo 🔍 Checking PHP syntax...
set syntax_errors=0
for /r app\ %%f in (*.php) do (
    php -l "%%f" >nul 2>&1
    if %errorlevel% neq 0 (
        echo ❌ Syntax error in %%f
        php -l "%%f"
        set /a syntax_errors+=1
    )
)

if %syntax_errors% gtr 0 (
    echo ❌ Found %syntax_errors% PHP syntax errors
    exit /b 1
)

echo ✅ PHP syntax check passed

REM 4. Run code style checks (if phpcs is available)
where phpcs >nul 2>&1
if %errorlevel% equ 0 (
    echo 🎨 Running code style checks...
    phpcs --standard=PSR12 app/
    
    if %errorlevel% neq 0 (
        echo ❌ Code style issues found
        exit /b 1
    )
    
    echo ✅ Code style checks passed
) else (
    echo ⚠️  phpcs not found. Install with: composer global require squizlabs/php_codesniffer
)

REM 5. Run static analysis (if phpstan is available)
where phpstan >nul 2>&1
if %errorlevel% equ 0 (
    echo 🔍 Running static analysis...
    phpstan analyse app/ --level=5
    
    if %errorlevel% neq 0 (
        echo ❌ Static analysis issues found
        exit /b 1
    )
    
    echo ✅ Static analysis passed
) else (
    echo ⚠️  phpstan not found. Install with: composer global require phpstan/phpstan
)

echo 🎉 All PHP CI checks passed!
