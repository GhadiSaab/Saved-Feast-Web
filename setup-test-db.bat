@echo off
echo Setting up test database for SavedFeast...

REM Check if MySQL is running
echo Checking MySQL connection...
mysql -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: MySQL is not running or not accessible
    echo Please start MySQL service and try again
    pause
    exit /b 1
)

REM Create test database
echo Creating test database...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS savedfeast_test;"
if %errorlevel% neq 0 (
    echo ERROR: Failed to create test database
    pause
    exit /b 1
)

echo Test database created successfully!
echo You can now run: php artisan test
pause
