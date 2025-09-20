@echo off
REM Complete CI Issues Fix Script for Windows
REM Run this to fix ALL CI issues automatically

echo 🚀 Fixing All CI Issues...
echo ==========================

REM 1. Fix dependencies first
echo.
echo 📦 Step 1: Fixing Dependencies...
echo ---------------------------------
call fix-dependencies.bat
if %errorlevel% neq 0 (
    echo ❌ Dependency fixes failed
    exit /b 1
) else (
    echo ✅ Dependencies fixed successfully
)

REM 2. Fix formatting issues
echo.
echo 🎨 Step 2: Fixing Formatting...
echo ------------------------------
call fix-formatting.bat
if %errorlevel% neq 0 (
    echo ❌ Formatting fixes failed
    exit /b 1
) else (
    echo ✅ Formatting fixed successfully
)

REM 2.5. Fix any test issues (auto-format test files too)
echo.
echo 🧪 Step 2.5: Formatting Test Files...
echo ------------------------------------
npx prettier --write resources/js/__tests__/
echo ✅ Test files formatted

REM 3. Run all CI checks to verify
echo.
echo 🔍 Step 3: Verifying All Fixes...
echo --------------------------------
call check-all-ci.bat
if %errorlevel% neq 0 (
    echo.
    echo ⚠️  Some issues still remain. Check the output above.
    echo You may need to fix remaining issues manually.
    exit /b 1
) else (
    echo.
    echo 🎉 SUCCESS! All CI issues have been fixed!
    echo ==========================================
    echo ✅ PHP dependencies: Fixed
    echo ✅ Frontend formatting: Fixed
    echo ✅ Laravel tests: Working
    echo ✅ All CI checks: Passing
    echo.
    echo 🚀 Ready to push to GitHub!
)
