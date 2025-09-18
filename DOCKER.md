# Docker Setup for SavedFeast Web Application

This document provides comprehensive instructions for running the SavedFeast web application using Docker and Docker Compose.

## 🐳 Overview

The SavedFeast web application is containerized using Docker with the following services:

- **Laravel Application** (PHP 8.1 + Nginx)
- **MySQL 8.0** Database
- **Redis 7** Cache
- **Nginx** Reverse Proxy (Optional)

## 📋 Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 4GB RAM available
- 10GB free disk space

## 🚀 Quick Start

### 1. Clone and Navigate

```bash
cd SavedFeast-Web
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Edit environment variables for Docker
nano .env
```

Update the following variables in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=savedfeast
DB_USERNAME=savedfeast_user
DB_PASSWORD=savedfeast_password
REDIS_HOST=redis
REDIS_PORT=6379
```

### 3. Build and Start Services

```bash
# Build and start all services
docker-compose up -d --build

# View logs
docker-compose logs -f
```

### 4. Application Setup

```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Run database migrations
docker-compose exec app php artisan migrate

# Seed the database
docker-compose exec app php artisan db:seed

# Create storage link
docker-compose exec app php artisan storage:link

# Set proper permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 5. Access the Application

- **Web Application**: https://savedfeast.app
- **API Endpoints**: https://savedfeast.app/api
- **Health Check**: https://savedfeast.app/health

## 🔧 Service Configuration

### Laravel Application

**Port**: 8000  
**Container**: `savedfeast-app`

```yaml
# Key features:
- PHP 8.1 with FPM
- Nginx web server
- OPcache enabled
- Supervisor process management
- Optimized for production
```

### MySQL Database

**Port**: 3306  
**Container**: `savedfeast-mysql`

```yaml
# Configuration:
- Database: savedfeast
- User: savedfeast_user
- Password: savedfeast_password
- Root Password: root_password
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci
```

### Redis Cache

**Port**: 6379  
**Container**: `savedfeast-redis`

```yaml
# Features:
- In-memory caching
- Session storage
- Queue processing
- Persistent data storage
```

### Nginx (Optional)

**Ports**: 80, 443  
**Container**: `savedfeast-nginx`

```yaml
# Features:
- Reverse proxy
- SSL termination
- Static file serving
- Gzip compression
- Security headers
```

## 🛠️ Development Commands

### Basic Operations

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart services
docker-compose restart

# View logs
docker-compose logs -f [service_name]

# Access container shell
docker-compose exec app bash
docker-compose exec mysql mysql -u root -p
docker-compose exec redis redis-cli
```

### Laravel Commands

```bash
# Run artisan commands
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Database Operations

```bash
# Access MySQL
docker-compose exec mysql mysql -u savedfeast_user -p savedfeast

# Backup database
docker-compose exec mysql mysqldump -u savedfeast_user -p savedfeast > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u savedfeast_user -p savedfeast < backup.sql
```

### Testing

```bash
# Run PHPUnit tests
docker-compose exec app php artisan test

# Run tests with coverage
docker-compose exec app php artisan test --coverage

# Run specific test
docker-compose exec app php artisan test --filter TestName
```

## 🔒 Security Configuration

### Environment Variables

```env
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://savedfeast.app

# Database security
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=savedfeast
DB_USERNAME=savedfeast_user
DB_PASSWORD=strong_password_here

# Cache configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=redis_password_here

# Session security
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
```

### Security Headers

The Nginx configuration includes security headers:

- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- X-Content-Type-Options: nosniff
- Referrer-Policy: no-referrer-when-downgrade
- Content-Security-Policy: default-src 'self'

## 📊 Monitoring and Logs

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
docker-compose logs -f redis

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Health Checks

```bash
# Application health
curl https://savedfeast.app/health

# Database connectivity
docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();"

# Redis connectivity
docker-compose exec app php artisan tinker --execute="Redis::ping();"
```

## 🚀 Production Deployment

### Environment Setup

1. **Update Environment Variables**
   ```bash
   # Copy production environment
   cp .env.example .env.production
   
   # Edit with production values
   nano .env.production
   ```

2. **Security Considerations**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   DB_PASSWORD=very_strong_password
   REDIS_PASSWORD=very_strong_redis_password
   ```

3. **SSL Configuration**
   ```bash
   # Add SSL certificates to docker/ssl/
   # Update nginx.conf for SSL
   ```

### Deployment Commands

```bash
# Production build
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Database migration
docker-compose exec app php artisan migrate --force

# Cache optimization
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 🔧 Troubleshooting

### Common Issues

#### Port Conflicts
```bash
# Check port usage
netstat -tulpn | grep :8000

# Change ports in docker-compose.yml
ports:
  - "8080:80"  # Change 8000 to 8080
```

#### Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

#### Database Connection Issues
```bash
# Check database status
docker-compose exec mysql mysqladmin ping -u root -p

# Reset database
docker-compose down -v
docker-compose up -d
```

#### Memory Issues
```bash
# Increase Docker memory limit
# Docker Desktop: Settings > Resources > Memory > 4GB
```

### Performance Optimization

```bash
# Enable OPcache
docker-compose exec app php -m | grep opcache

# Optimize Composer autoloader
docker-compose exec app composer dump-autoload --optimize

# Clear all caches
docker-compose exec app php artisan optimize:clear
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

## 🤝 Support

For Docker-related issues:

1. Check the troubleshooting section above
2. Review Docker and Laravel logs
3. Ensure all prerequisites are met
4. Verify environment configuration
5. Check GitHub Issues for known problems

---

**Happy Containerizing! 🐳**
