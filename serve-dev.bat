@echo off
REM SavedFeast Development Server Script for Windows
REM Starts both Laravel backend and frontend development servers

setlocal enabledelayedexpansion

REM Default values
set HOST=127.0.0.1
set BACKEND_PORT=8000
set FRONTEND_PORT=5173

REM Check if we're in the right directory
if not exist "artisan" (
    echo [ERROR] This script must be run from the Laravel project root directory
    pause
    exit /b 1
)

REM Check if package.json exists
if not exist "package.json" (
    echo [ERROR] Frontend dependencies not found. Please run: npm install
    pause
    exit /b 1
)

REM Check if node_modules exists
if not exist "node_modules" (
    echo [WARNING] Frontend dependencies not installed. Installing now...
    npm install
    if errorlevel 1 (
        echo [ERROR] Failed to install frontend dependencies
        pause
        exit /b 1
    )
)

echo [INFO] 🚀 Starting SavedFeast Development Servers...
echo [INFO] Backend: http://%HOST%:%BACKEND_PORT%
echo [INFO] Frontend: http://%HOST%:%FRONTEND_PORT%
echo [INFO] API: http://%HOST%:%BACKEND_PORT%/api
echo.

REM Start frontend server in background
echo [INFO] Starting frontend development server...
start "Frontend Server" cmd /c "npm run dev"

REM Wait a moment for frontend to start
timeout /t 3 /nobreak >nul

REM Start Laravel server
echo [INFO] Starting Laravel backend server...
start "Backend Server" cmd /c "php artisan serve --host=%HOST% --port=%BACKEND_PORT%"

REM Wait a moment for backend to start
timeout /t 2 /nobreak >nul

echo.
echo [SUCCESS] 🎉 Both servers are running!
echo [INFO] Frontend: http://%HOST%:%FRONTEND_PORT%
echo [INFO] Backend: http://%HOST%:%BACKEND_PORT%
echo [INFO] API: http://%HOST%:%BACKEND_PORT%/api
echo.
echo [INFO] Close the terminal windows to stop the servers
echo.
pause 