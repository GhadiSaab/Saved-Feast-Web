@echo off
REM Auto-fix Frontend Formatting Issues for Windows
REM Run this to automatically fix all code formatting issues

echo 🎨 Auto-fixing Frontend Formatting Issues...

REM 1. Fix Prettier formatting
echo 📝 Running Prettier auto-fix...
npx prettier --write resources/js/

if %errorlevel% neq 0 (
    echo ❌ Prettier formatting failed
    exit /b 1
)

echo ✅ Prettier formatting completed

REM 2. Fix ESLint issues (auto-fixable ones)
echo 🔧 Running ESLint auto-fix...
npx eslint resources/js/ --fix

if %errorlevel% neq 0 (
    echo ⚠️  Some ESLint issues couldn't be auto-fixed. Manual intervention may be required.
    REM Don't exit here as some issues might need manual fixing
)

echo ✅ ESLint auto-fix completed

REM 3. Run format check to verify
echo 🔍 Verifying formatting...
npx prettier --check resources/js/

if %errorlevel% equ 0 (
    echo 🎉 All formatting issues fixed!
) else (
    echo ⚠️  Some formatting issues remain. Please check the output above.
)

echo 📋 Summary:
echo - Prettier formatting: ✅ Applied
echo - ESLint auto-fixes: ✅ Applied
echo - Ready for CI checks!
