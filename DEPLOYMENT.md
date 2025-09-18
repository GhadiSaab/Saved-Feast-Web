# Deployment Guide

## CI/CD Pipeline

The application includes comprehensive CI/CD checks that ensure code quality and functionality.

### Frontend Checks
- **TypeScript Compilation**: `npm run type-check`
- **ESLint**: `npm run ci:lint` (allows up to 150 warnings)
- **Tests**: `npm run ci:test` (using Vitest)
- **Build**: `npm run build`

### Backend Checks
- **PHP Compatibility**: Supports PHP 8.1+ and 8.2+
- **Composer Install**: All dependencies resolved
- **PHPUnit Tests**: Full test suite using SQLite in-memory database
- **Laravel Artisan Tests**: Additional Laravel-specific tests

### Local Development
```bash
# Install dependencies
npm install
composer install

# Run quality checks
npm run type-check
npm run lint
npm run test

# Build for production
npm run build
```

### Deployment Commands
```bash
# Production build
npm run ci:build

# Run full test suite
npm run ci:test
php artisan test

# Deploy to production
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Environment Setup

### Required Environment Variables
```bash
# Application
APP_URL=https://savedfeast.app
VITE_API_URL=https://savedfeast.app

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

### Node.js Version
The application requires Node.js 18.19.0+ (see `.nvmrc`).
