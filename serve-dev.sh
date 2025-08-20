#!/bin/bash

# SavedFeast Development Server Script
# Starts both Laravel backend and frontend development servers

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
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

# Default values
HOST=${1:-127.0.0.1}
BACKEND_PORT=${2:-8000}
FRONTEND_PORT=${3:-5173}

# Function to cleanup background processes on exit
cleanup() {
    print_info "Shutting down servers..."
    if [ ! -z "$FRONTEND_PID" ]; then
        kill $FRONTEND_PID 2>/dev/null || true
    fi
    if [ ! -z "$BACKEND_PID" ]; then
        kill $BACKEND_PID 2>/dev/null || true
    fi
    print_success "Servers stopped"
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

# Check if package.json exists
if [ ! -f "package.json" ]; then
    print_error "Frontend dependencies not found. Please run: npm install"
    exit 1
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    print_warning "Frontend dependencies not installed. Installing now..."
    npm install
fi

print_info "🚀 Starting SavedFeast Development Servers..."
print_info "Backend: http://$HOST:$BACKEND_PORT"
print_info "Frontend: http://$HOST:$FRONTEND_PORT"
print_info "API: http://$HOST:$BACKEND_PORT/api"
echo ""

# Start frontend server in background
print_info "Starting frontend development server..."
npm run dev &
FRONTEND_PID=$!

# Wait a moment for frontend to start
sleep 3

# Check if frontend started successfully
if ! kill -0 $FRONTEND_PID 2>/dev/null; then
    print_error "Failed to start frontend server"
    exit 1
fi

print_success "Frontend server started (PID: $FRONTEND_PID)"

# Start Laravel server
print_info "Starting Laravel backend server..."
php artisan serve --host=$HOST --port=$BACKEND_PORT &
BACKEND_PID=$!

# Wait a moment for backend to start
sleep 2

# Check if backend started successfully
if ! kill -0 $BACKEND_PID 2>/dev/null; then
    print_error "Failed to start backend server"
    kill $FRONTEND_PID 2>/dev/null || true
    exit 1
fi

print_success "Backend server started (PID: $BACKEND_PID)"
echo ""
print_success "🎉 Both servers are running!"
print_info "Press Ctrl+C to stop both servers"
echo ""

# Wait for user to stop
wait 