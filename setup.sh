#!/bin/bash

# SavedFeast Setup Script
# This script automates the setup process for the SavedFeast application

set -e  # Exit on any error

echo "🍽️  SavedFeast Setup Script"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required tools are installed
check_requirements() {
    print_status "Checking requirements..."
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP 8.2 or higher."
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_success "PHP version: $PHP_VERSION"
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed. Please install Composer."
        exit 1
    fi
    print_success "Composer is installed"
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js 18 or higher."
        exit 1
    fi
    
    NODE_VERSION=$(node --version)
    print_success "Node.js version: $NODE_VERSION"
    
    # Check npm
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed."
        exit 1
    fi
    print_success "npm is installed"
    
    # Check MySQL
    if ! command -v mysql &> /dev/null; then
        print_warning "MySQL client is not installed. You'll need to create the database manually."
    else
        print_success "MySQL client is installed"
    fi
}

# Install dependencies
install_dependencies() {
    print_status "Installing dependencies..."
    
    # Install PHP dependencies
    print_status "Installing PHP dependencies with Composer..."
    composer install --no-interaction
    
    # Install Node.js dependencies
    print_status "Installing Node.js dependencies..."
    npm install
    
    print_success "Dependencies installed successfully"
}

# Setup environment
setup_environment() {
    print_status "Setting up environment..."
    
    # Copy .env file if it doesn't exist
    if [ ! -f .env ]; then
        cp .env.example .env
        print_success ".env file created from .env.example"
    else
        print_warning ".env file already exists, skipping..."
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate --no-interaction
    
    print_success "Environment setup completed"
}

# Setup database
setup_database() {
    print_status "Setting up database..."
    
    # Check if .env exists
    if [ ! -f .env ]; then
        print_error ".env file not found. Please run setup_environment first."
        exit 1
    fi
    
    # Extract database configuration from .env
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
    DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
    
    # Create database if MySQL client is available
    if command -v mysql &> /dev/null; then
        print_status "Creating database '$DB_DATABASE'..."
        
        if [ -z "$DB_PASSWORD" ]; then
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        else
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        fi
        
        print_success "Database created successfully"
    else
        print_warning "MySQL client not found. Please create the database '$DB_DATABASE' manually."
        read -p "Press Enter after creating the database..."
    fi
    
    # Run migrations
    print_status "Running database migrations..."
    php artisan migrate --no-interaction
    
    # Seed database
    print_status "Seeding database with demo data..."
    php artisan db:seed --no-interaction
    
    # Assign default roles
    print_status "Assigning default roles to users..."
    php artisan users:assign-default-roles --no-interaction
    
    print_success "Database setup completed"
}

# Setup file storage
setup_storage() {
    print_status "Setting up file storage..."
    
    # Create storage link
    php artisan storage:link --no-interaction
    
    print_success "File storage setup completed"
}

# Build frontend
build_frontend() {
    print_status "Building frontend assets..."
    
    npm run build
    
    print_success "Frontend assets built successfully"
}

# Display final information
display_info() {
    echo ""
    echo "🎉 Setup completed successfully!"
    echo "================================"
    echo ""
    echo "Next steps:"
    echo "1. Start both development servers (recommended):"
    echo "   php artisan serve:full"
    echo ""
    echo "2. Or start servers separately:"
    echo "   Terminal 1: php artisan serve"
    echo "   Terminal 2: npm run dev"
    echo ""
    echo "3. Access the application:"
    echo "   Backend API: https://savedfeast.app/api"
    echo "   Frontend: https://savedfeast.app"
    echo ""
    echo "Demo users:"
    echo "   Admin: admin@savedfeast.com / password"
    echo "   Provider: provider@savedfeast.com / password"
    echo ""
    echo "API Documentation:"
    echo "   OpenAPI: docs/api/openapi.yaml"
    echo "   Postman: docs/api/SavedFeast_API.postman_collection.json"
    echo ""
    echo "Happy coding! 🚀"
}

# Main setup function
main() {
    echo "Starting SavedFeast setup..."
    echo ""
    
    check_requirements
    echo ""
    
    install_dependencies
    echo ""
    
    setup_environment
    echo ""
    
    setup_database
    echo ""
    
    setup_storage
    echo ""
    
    build_frontend
    echo ""
    
    display_info
}

# Run main function
main "$@" 