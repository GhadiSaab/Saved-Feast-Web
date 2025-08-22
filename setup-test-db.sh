#!/bin/bash

echo "Setting up test database for SavedFeast..."

# Check if MySQL is running
echo "Checking MySQL connection..."
mysql -u root -e "SELECT 1;" >/dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "ERROR: MySQL is not running or not accessible"
    echo "Please start MySQL service and try again"
    exit 1
fi

# Create test database
echo "Creating test database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS savedfeast_test;"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to create test database"
    exit 1
fi

echo "Test database created successfully!"
echo "You can now run: php artisan test"
