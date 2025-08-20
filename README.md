<p align="center"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></p>

# SavedFeast 🍽️🇱🇧

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About SavedFeast

SavedFeast is an innovative food delivery platform designed to combat food waste by connecting restaurants with consumers. The platform enables businesses to sell surplus meals at discounted prices, reducing waste while making food more affordable for customers.

## 🚀 Current Status

### ✅ **Completed Features**
- **Authentication System**: User registration, login, logout with Laravel Sanctum
- **Role-Based Authorization**: Consumer, Provider, and Admin roles with policies
- **Meal Management**: CRUD operations for meals with image upload
- **Order System**: Complete order lifecycle with status tracking
- **API Documentation**: OpenAPI/Swagger specs and Postman collection
- **Frontend SPA**: React application with proper routing and state management
- **Security**: Rate limiting, CORS configuration, input validation
- **Database**: Complete schema with relationships and migrations

### 🔄 **In Progress**
- Payment integration
- Real-time notifications
- Mobile app development

### 📋 **Roadmap**
- Push notifications
- Advanced analytics
- Multi-language support
- Delivery tracking
- Restaurant reviews

## 🛠️ Technology Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: React 18 + TypeScript + Vite
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **File Storage**: Laravel Storage (local/cloud)
- **UI Framework**: Bootstrap 5
- **API Documentation**: OpenAPI 3.0

## 📦 Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer 2.0+
- Node.js 18+ and npm
- MySQL 8.0+
- Git

### Quick Setup (Recommended)

#### Linux/Mac
```bash
# Clone and run automated setup
git clone https://github.com/GhadiSaab/Saved-Feast-Web.git
cd Saved-Feast-Web
chmod +x setup.sh
./setup.sh
```

#### Windows
```cmd
# Clone and run automated setup
git clone https://github.com/GhadiSaab/Saved-Feast-Web.git
cd Saved-Feast-Web
setup.bat
```

### Manual Setup

### 1. Clone Repository
```bash
git clone https://github.com/GhadiSaab/Saved-Feast-Web.git
cd Saved-Feast-Web
```

### 2. Install Dependencies
```bash
# Backend dependencies
composer install

# Frontend dependencies
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

#### Sample `.env` Configuration
```env
# Application
APP_NAME="SavedFeast"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=savedfeast
DB_USERNAME=root
DB_PASSWORD=

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@savedfeast.com"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local

# Future Payment Integration
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# Queue (for background jobs)
QUEUE_CONNECTION=sync

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE savedfeast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed

# Assign default roles to existing users (if any)
php artisan users:assign-default-roles
```

### 6. File Storage Setup
```bash
# Create storage link for public file access
php artisan storage:link
```

### 7. Start Development Servers

#### Option 1: Custom Artisan Command (Recommended)
```bash
# Start both servers with one command
php artisan serve:full

# With custom ports
php artisan serve:full --port=8000 --frontend-port=5173
```

#### Option 2: NPM Script (Requires concurrently)
```bash
# Install concurrently first
npm install

# Start both servers
npm run serve:full
```

#### Option 3: Shell Scripts
```bash
# Linux/Mac
chmod +x serve-dev.sh
./serve-dev.sh

# Windows
serve-dev.bat
```

#### Option 4: Manual (Two Terminals)
```bash
# Terminal 1: Backend server
php artisan serve

# Terminal 2: Frontend development server
npm run dev
```

## 🌱 Demo Data

The application includes comprehensive demo data:

### **Users & Roles**
- **Admin User**: `admin@savedfeast.com` / `password`
- **Provider User**: `provider@savedfeast.com` / `password`
- **Consumer Users**: 10 random users with `password` (check database for emails)

### **Sample Data**
- **Categories**: Pizza, Burgers, Sushi, Desserts, Beverages
- **Restaurants**: 5 sample restaurants with complete profiles
- **Meals**: 20+ sample meals with images and pricing
- **Orders**: Sample order history for testing

### **Seed Commands**
```bash
# Seed all demo data
php artisan db:seed

# Seed specific data
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=RestaurantSeeder
php artisan db:seed --class=MealSeeder
php artisan db:seed --class=UserSeeder

# Reset and reseed
php artisan migrate:fresh --seed
```

## 📚 API Documentation

### **Interactive Documentation**
- **OpenAPI/Swagger**: `docs/api/openapi.yaml`
- **Postman Collection**: `docs/api/SavedFeast_API.postman_collection.json`

### **Quick API Reference**

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/register` | POST | User registration | No |
| `/api/login` | POST | User authentication | No |
| `/api/logout` | POST | User logout | Yes |
| `/api/meals` | GET | List meals with filters | No |
| `/api/meals/filters` | GET | Get filter options | No |
| `/api/categories` | GET | List categories | No |
| `/api/orders` | GET/POST | User orders | Yes |
| `/api/orders/{id}` | GET/PUT | Specific order | Yes |
| `/api/user/profile` | POST | Update profile | Yes |
| `/api/provider/meals` | GET/POST/PUT/DELETE | Provider meal management | Yes (Provider) |
| `/api/admin/dashboard` | GET | Admin dashboard | Yes (Admin) |

### **Authentication**
```bash
# Include Bearer token in requests
Authorization: Bearer {your_access_token}
```

### **Rate Limits**
- **Auth endpoints**: 6 requests/minute
- **Public endpoints**: 60 requests/minute
- **Authenticated endpoints**: 120 requests/minute
- **Provider endpoints**: 300 requests/minute
- **Admin endpoints**: 600 requests/minute

## 🎯 Usage Examples

### **Testing with Postman**
1. Import `docs/api/SavedFeast_API.postman_collection.json`
2. Set environment variables:
   - `base_url`: `http://localhost:8000/api`
   - `access_token`: (will be set after login)
3. Start with "Register User" or "Login User"
4. Use returned token for authenticated requests

### **Testing with cURL**
```bash
# Register a new user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
  }'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!"
  }'

# Get meals (with token)
curl -X GET http://localhost:8000/api/meals \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🔧 Development

### **Available Commands**
```bash
# Database
php artisan migrate                    # Run migrations
php artisan migrate:fresh --seed       # Reset and seed
php artisan db:seed                    # Seed demo data
php artisan users:assign-default-roles # Assign roles to users

# Development Servers
php artisan serve:full                 # Start both backend and frontend servers
npm run serve:full                     # Start both servers (npm script)
./serve-dev.sh                         # Start both servers (Linux/Mac script)
serve-dev.bat                          # Start both servers (Windows script)

# Frontend
npm run dev                           # Start frontend development server
npm run build                         # Build for production
npm run preview                       # Preview production build

# Backend
php artisan serve                     # Start Laravel server only
php artisan route:list               # List all routes
php artisan make:controller          # Create controller
php artisan make:model               # Create model
php artisan make:migration           # Create migration
```

### **File Structure**
```
├── app/
│   ├── Http/Controllers/API/        # API controllers
│   ├── Models/                      # Eloquent models
│   ├── Policies/                    # Authorization policies
│   └── Console/Commands/            # Artisan commands
├── resources/js/
│   ├── components/                  # React components
│   ├── context/                     # React contexts
│   ├── routes/                      # React pages
│   └── App.tsx                      # Main app component
├── docs/api/                        # API documentation
├── database/
│   ├── migrations/                  # Database migrations
│   └── seeders/                     # Database seeders
└── routes/api.php                   # API routes
```

## 🔒 Security Features

- **Rate Limiting**: Comprehensive rate limiting on all endpoints
- **CORS Protection**: Secure cross-origin resource sharing
- **Input Validation**: All inputs validated and sanitized
- **Role-Based Access**: Policies and gates for authorization
- **Token Authentication**: Laravel Sanctum for secure API access
- **File Upload Security**: Validated and secure file uploads

## 🚀 Deployment

### **Production Environment Variables**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

FILESYSTEM_DISK=s3  # or other cloud storage
```

### **Deployment Steps**
1. Set production environment variables
2. Run `composer install --optimize-autoloader --no-dev`
3. Run `npm run build`
4. Run `php artisan migrate --force`
5. Set up web server (Apache/Nginx)
6. Configure SSL certificate
7. Set up queue workers (if using queues)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### **Development Guidelines**
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for API changes
- Use conventional commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: Check `docs/` folder for detailed guides
- **API Docs**: Use OpenAPI spec or Postman collection
- **Issues**: Report bugs via GitHub Issues
- **Discussions**: Use GitHub Discussions for questions

## 🙏 Acknowledgments

- Laravel team for the amazing framework
- React team for the frontend library
- Bootstrap team for the UI framework
- All contributors and supporters
