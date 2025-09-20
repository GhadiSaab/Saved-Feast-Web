#!/bin/bash

# Complete CI Checks Script
# Run this to test all CI checks locally before pushing to GitHub

echo "🚀 Running Complete CI Checks..."
echo "=================================="

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "package.json" ]; then
    echo "❌ Error: composer.json or package.json not found. Please run this script from the project root."
    exit 1
fi

# Track overall success
OVERALL_SUCCESS=true

# 1. PHP CI Checks
echo ""
echo "🔍 Running PHP CI Checks..."
echo "---------------------------"
if ./check-php-ci.sh; then
    echo "✅ PHP CI checks passed"
else
    echo "❌ PHP CI checks failed"
    OVERALL_SUCCESS=false
fi

# 2. Frontend CI Checks
echo ""
echo "🔍 Running Frontend CI Checks..."
echo "--------------------------------"
if ./check-frontend-ci.sh; then
    echo "✅ Frontend CI checks passed"
else
    echo "❌ Frontend CI checks failed"
    OVERALL_SUCCESS=false
fi

# 3. Laravel Tests
echo ""
echo "🧪 Running Laravel Tests..."
echo "---------------------------"
if [ -f "vendor/bin/phpunit" ]; then
    if vendor/bin/phpunit; then
        echo "✅ Laravel tests passed"
    else
        echo "❌ Laravel tests failed"
        OVERALL_SUCCESS=false
    fi
else
    echo "⚠️  PHPUnit not found in vendor/bin. Installing dev dependencies..."
    composer install --ignore-platform-req=php --no-interaction
    if vendor/bin/phpunit; then
        echo "✅ Laravel tests passed"
    else
        echo "❌ Laravel tests failed"
        OVERALL_SUCCESS=false
    fi
fi

# 4. Database Migration Check
echo ""
echo "🗄️  Checking Database Migrations..."
echo "------------------------------------"
if php artisan migrate:status; then
    echo "✅ Database migrations are up to date"
else
    echo "❌ Database migration issues found"
    OVERALL_SUCCESS=false
fi

# Final Result
echo ""
echo "=================================="
if [ "$OVERALL_SUCCESS" = true ]; then
    echo "🎉 All CI checks passed! Ready to push to GitHub."
    exit 0
else
    echo "❌ Some CI checks failed. Please fix the issues above before pushing."
    exit 1
fi
