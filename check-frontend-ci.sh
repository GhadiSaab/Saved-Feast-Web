#!/bin/bash

# Frontend CI Checks Script
# Run this to test frontend dependencies, formatting, and code quality locally

echo "🔍 Running Frontend CI Checks..."

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    echo "❌ Error: package.json not found. Please run this script from the project root."
    exit 1
fi

# 1. Install/Update Dependencies
echo "📦 Installing frontend dependencies..."
npm ci

if [ $? -ne 0 ]; then
    echo "❌ Frontend dependency installation failed"
    exit 1
fi

echo "✅ Frontend dependencies installed successfully"

# 2. Check for security vulnerabilities
echo "🔒 Checking for security vulnerabilities..."
npm audit

if [ $? -ne 0 ]; then
    echo "⚠️  Security vulnerabilities found. Check npm audit output above."
    # Don't exit here, just warn
fi

# 3. Run TypeScript type checking
echo "🔍 Running TypeScript type checking..."
npx tsc --noEmit

if [ $? -ne 0 ]; then
    echo "❌ TypeScript type errors found"
    exit 1
fi

echo "✅ TypeScript type checking passed"

# 4. Run ESLint (allow warnings, but fail on errors)
echo "🎨 Running ESLint..."
npx eslint resources/js/ --max-warnings=200

if [ $? -ne 0 ]; then
    echo "❌ ESLint errors found (warnings allowed)"
    exit 1
fi

echo "✅ ESLint checks passed (warnings allowed)"

# 5. Run Prettier format check
echo "🎨 Checking code formatting with Prettier..."
npx prettier --check resources/js/

if [ $? -ne 0 ]; then
    echo "❌ Code formatting issues found"
    echo "💡 Run 'npm run format' to fix formatting issues"
    exit 1
fi

echo "✅ Code formatting check passed"

# 6. Run tests (if available)
if [ -f "vitest.config.ts" ] || [ -f "jest.config.js" ]; then
    echo "🧪 Running frontend tests..."
    npm test
    
    if [ $? -ne 0 ]; then
        echo "❌ Frontend tests failed"
        exit 1
    fi
    
    echo "✅ Frontend tests passed"
fi

# 7. Build check
echo "🏗️  Testing build process..."
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Build failed"
    exit 1
fi

echo "✅ Build test passed"

echo "🎉 All Frontend CI checks passed!"
