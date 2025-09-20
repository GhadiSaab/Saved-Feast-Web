@echo off
REM Complete CI Checks Script for Windows
REM Run this to test all CI checks locally before pushing to GitHub

echo 🚀 Running Complete CI Checks...
echo ==================================

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: composer.json not found. Please run this script from the project root.
    exit /b 1
)

if not exist "package.json" (
    echo ❌ Error: package.json not found. Please run this script from the project root.
    exit /b 1
)

REM Track overall success
set OVERALL_SUCCESS=true

REM 1. PHP CI Checks
echo.
echo 🔍 Running PHP CI Checks...
echo ---------------------------
call check-php-ci.bat
if %errorlevel% neq 0 (
    echo ❌ PHP CI checks failed
    set OVERALL_SUCCESS=false
) else (
    echo ✅ PHP CI checks passed
)

REM 2. Frontend CI Checks
echo.
echo 🔍 Running Frontend CI Checks...
echo --------------------------------
call check-frontend-ci.bat
if %errorlevel% neq 0 (
    echo ❌ Frontend CI checks failed
    set OVERALL_SUCCESS=false
) else (
    echo ✅ Frontend CI checks passed
)

REM 3. Laravel Tests
echo.
echo 🧪 Running Laravel Tests...
echo ---------------------------
if exist "vendor\bin\phpunit.bat" (
    vendor\bin\phpunit.bat
    if %errorlevel% neq 0 (
        echo ❌ Laravel tests failed
        set OVERALL_SUCCESS=false
    ) else (
        echo ✅ Laravel tests passed
    )
) else (
    echo ⚠️  PHPUnit not found in vendor\bin. Installing dev dependencies...
    composer install --ignore-platform-req=php --no-interaction
    vendor\bin\phpunit.bat
    if %errorlevel% neq 0 (
        echo ❌ Laravel tests failed
        set OVERALL_SUCCESS=false
    ) else (
        echo ✅ Laravel tests passed
    )
)

REM 4. Database Migration Check
echo.
echo 🗄️  Checking Database Migrations...
echo ------------------------------------
php artisan migrate:status
if %errorlevel% neq 0 (
    echo ❌ Database migration issues found
    set OVERALL_SUCCESS=false
) else (
    echo ✅ Database migrations are up to date
)

REM Final Result
echo.
echo ==================================
if "%OVERALL_SUCCESS%"=="true" (
    echo 🎉 All CI checks passed! Ready to push to GitHub.
    exit /b 0
) else (
    echo ❌ Some CI checks failed. Please fix the issues above before pushing.
    exit /b 1
)
