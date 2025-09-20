@echo off
REM Frontend CI Checks Script for Windows
REM Run this to test frontend dependencies, formatting, and code quality locally

echo 🔍 Running Frontend CI Checks...

REM Check if we're in the right directory
if not exist "package.json" (
    echo ❌ Error: package.json not found. Please run this script from the project root.
    exit /b 1
)

REM 1. Install/Update Dependencies
echo 📦 Installing frontend dependencies...
npm ci

if %errorlevel% neq 0 (
    echo ❌ Frontend dependency installation failed
    exit /b 1
)

echo ✅ Frontend dependencies installed successfully

REM 2. Check for security vulnerabilities
echo 🔒 Checking for security vulnerabilities...
npm audit

if %errorlevel% neq 0 (
    echo ⚠️  Security vulnerabilities found. Check npm audit output above.
    REM Don't exit here, just warn
)

REM 3. Run TypeScript type checking
echo 🔍 Running TypeScript type checking...
npx tsc --noEmit

if %errorlevel% neq 0 (
    echo ❌ TypeScript type errors found
    exit /b 1
)

echo ✅ TypeScript type checking passed

REM 4. Run ESLint (allow warnings, but fail on errors)
echo 🎨 Running ESLint...
npx eslint resources/js/ --max-warnings=200

if %errorlevel% neq 0 (
    echo ❌ ESLint errors found (warnings allowed)
    exit /b 1
)

echo ✅ ESLint checks passed (warnings allowed)

REM 5. Run Prettier format check
echo 🎨 Checking code formatting with Prettier...
npx prettier --check resources/js/

if %errorlevel% neq 0 (
    echo ❌ Code formatting issues found
    echo 💡 Run 'npm run format' to fix formatting issues
    exit /b 1
)

echo ✅ Code formatting check passed

REM 6. Run tests (if available)
if exist "vitest.config.ts" (
    echo 🧪 Running frontend tests...
    npm test
    
    if %errorlevel% neq 0 (
        echo ❌ Frontend tests failed
        exit /b 1
    )
    
    echo ✅ Frontend tests passed
) else if exist "jest.config.js" (
    echo 🧪 Running frontend tests...
    npm test
    
    if %errorlevel% neq 0 (
        echo ❌ Frontend tests failed
        exit /b 1
    )
    
    echo ✅ Frontend tests passed
)

REM 7. Build check
echo 🏗️  Testing build process...
npm run build

if %errorlevel% neq 0 (
    echo ❌ Build failed
    exit /b 1
)

echo ✅ Build test passed

echo 🎉 All Frontend CI checks passed!
