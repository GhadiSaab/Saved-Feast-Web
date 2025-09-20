#!/bin/bash

# PHP CI Checks Script
# Run this to test PHP dependencies and code quality locally

echo "🔍 Running PHP CI Checks..."

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from the project root."
    exit 1
fi

# 1. Install/Update Dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

if [ $? -ne 0 ]; then
    echo "❌ PHP dependency installation failed"
    exit 1
fi

echo "✅ PHP dependencies installed successfully"

# 2. Check for security vulnerabilities
echo "🔒 Checking for security vulnerabilities..."
composer audit

if [ $? -ne 0 ]; then
    echo "⚠️  Security vulnerabilities found. Check composer audit output above."
    # Don't exit here, just warn
fi

# 3. Run PHP syntax check
echo "🔍 Checking PHP syntax..."
syntax_errors=0
for file in $(find app/ -name "*.php"); do
    if ! php -l "$file" > /dev/null 2>&1; then
        echo "❌ Syntax error in: $file"
        php -l "$file"
        syntax_errors=$((syntax_errors + 1))
    fi
done

if [ $syntax_errors -gt 0 ]; then
    echo "❌ Found $syntax_errors PHP syntax errors"
    exit 1
fi

echo "✅ PHP syntax check passed"

# 4. Run code style checks (if phpcs is available)
if command -v phpcs &> /dev/null; then
    echo "🎨 Running code style checks..."
    phpcs --standard=PSR12 app/
    
    if [ $? -ne 0 ]; then
        echo "❌ Code style issues found"
        exit 1
    fi
    
    echo "✅ Code style checks passed"
else
    echo "⚠️  phpcs not found. Install with: composer global require squizlabs/php_codesniffer"
fi

# 5. Run static analysis (if phpstan is available)
if command -v phpstan &> /dev/null; then
    echo "🔍 Running static analysis..."
    phpstan analyse app/ --level=5
    
    if [ $? -ne 0 ]; then
        echo "❌ Static analysis issues found"
        exit 1
    fi
    
    echo "✅ Static analysis passed"
else
    echo "⚠️  phpstan not found. Install with: composer global require phpstan/phpstan"
fi

echo "🎉 All PHP CI checks passed!"
