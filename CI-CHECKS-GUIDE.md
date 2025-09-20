# 🚀 Local CI Checks Guide

This guide helps you run the same checks that GitHub CI runs locally, so you can catch issues before pushing.

## 📋 Available CI Check Scripts

### **Individual Checks**
```bash
# PHP Dependencies & Code Quality
./check-php-ci.sh          # Linux/Mac
check-php-ci.bat           # Windows

# Frontend Dependencies & Formatting  
./check-frontend-ci.sh     # Linux/Mac
check-frontend-ci.bat      # Windows

# All Checks Combined
./check-all-ci.sh          # Linux/Mac
check-all-ci.bat           # Windows
```

### **Using NPM Scripts (Recommended)**
```bash
# Run individual checks
npm run ci:php             # PHP checks only
npm run ci:frontend         # Frontend checks only
npm run ci:all              # All checks

# Fix formatting issues
npm run format              # Auto-fix code formatting
npm run lint:fix            # Auto-fix linting issues
```

## 🔍 What Each Check Does

### **PHP CI Checks** (`check-php-ci.sh`)
- ✅ Installs PHP dependencies (`composer install`)
- ✅ Checks for security vulnerabilities (`composer audit`)
- ✅ Validates PHP syntax
- ✅ Runs code style checks (PSR12)
- ✅ Runs static analysis (if phpstan installed)

### **Frontend CI Checks** (`check-frontend-ci.sh`)
- ✅ Installs frontend dependencies (`npm ci`)
- ✅ Checks for security vulnerabilities (`npm audit`)
- ✅ TypeScript type checking
- ✅ ESLint code quality checks
- ✅ Prettier formatting checks
- ✅ Runs frontend tests
- ✅ Tests build process

### **Combined Checks** (`check-all-ci.sh`)
- ✅ Runs all PHP checks
- ✅ Runs all frontend checks
- ✅ Runs Laravel tests
- ✅ Checks database migrations

## 🛠️ Quick Fixes for Common Issues

### **🚨 FIRST: Fix All Dependencies (If CI is failing)**
```bash
# Run this FIRST if you're getting dependency errors
npm run fix:deps
```

### **Frontend Formatting Issues**
```bash
# Auto-fix formatting
npm run format

# Auto-fix linting issues
npm run lint:fix
```

### **PHP Code Style Issues**
```bash
# Install phpcs globally (if not installed)
composer global require squizlabs/php_codesniffer

# Fix code style issues
phpcs --standard=PSR12 app/ --report=diff
```

### **Dependency Issues**
```bash
# Clean install PHP dependencies
rm -rf vendor/
composer install

# Clean install frontend dependencies
rm -rf node_modules/
npm ci
```

## 🚨 Common CI Failures & Solutions

### **1. PHP Dependency Installation Fails**
**Error**: `composer install` fails
**Solution**: 
```bash
# Clear composer cache
composer clear-cache
composer install --no-dev --optimize-autoloader
```

### **2. Frontend Format Check Fails**
**Error**: `Prettier format check failed`
**Solution**:
```bash
npm run format
```

### **3. ESLint Issues**
**Error**: `ESLint issues found`
**Solution**:
```bash
npm run lint:fix
```

### **4. TypeScript Errors**
**Error**: `TypeScript type errors found`
**Solution**:
```bash
# Check specific files
npx tsc --noEmit

# Fix type issues in your code
```

## 📝 Pre-Push Checklist

Before pushing to GitHub, run:

```bash
# Option 1: Run all checks at once
npm run ci:all

# Option 2: Run checks individually
npm run ci:php
npm run ci:frontend
php artisan test
```

## 🔧 Installing Required Tools

### **For Enhanced PHP Checks**
```bash
# Install code style checker
composer global require squizlabs/php_codesniffer

# Install static analyzer
composer global require phpstan/phpstan
```

### **For Enhanced Frontend Checks**
```bash
# Install additional linting tools (if needed)
npm install -g eslint prettier typescript
```

## 🎯 GitHub CI Workflow

Your GitHub CI should run these same checks:

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  php:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      - name: Run tests
        run: php artisan test
  
  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Install dependencies
        run: npm ci
      - name: Run linting
        run: npm run lint
      - name: Check formatting
        run: npx prettier --check resources/js/
      - name: Run tests
        run: npm test
```

## 🚀 Quick Start

1. **First time or if getting dependency errors**, run:
   ```bash
   npm run fix:deps
   ```

2. **Before every push**, run:
   ```bash
   npm run ci:all
   ```

3. **If checks fail**, fix the issues and run again

4. **Only push when all checks pass** ✅

This ensures your GitHub CI will always pass! 🎉
