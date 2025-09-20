#!/bin/bash

# Complete CI Issues Fix Script
# Run this to fix ALL CI issues automatically

echo "🚀 Fixing All CI Issues..."
echo "=========================="

# 1. Fix dependencies first
echo ""
echo "📦 Step 1: Fixing Dependencies..."
echo "---------------------------------"
if ./fix-dependencies.sh; then
    echo "✅ Dependencies fixed successfully"
else
    echo "❌ Dependency fixes failed"
    exit 1
fi

# 2. Fix formatting issues  
echo ""
echo "🎨 Step 2: Fixing Formatting..."
echo "------------------------------"
if ./fix-formatting.sh; then
    echo "✅ Formatting fixed successfully"
else
    echo "❌ Formatting fixes failed"
    exit 1
fi

# 2.5. Fix any test issues (auto-format test files too)
echo ""
echo "🧪 Step 2.5: Formatting Test Files..."
echo "------------------------------------"
npx prettier --write resources/js/__tests__/
echo "✅ Test files formatted"

# 3. Run all CI checks to verify
echo ""
echo "🔍 Step 3: Verifying All Fixes..."
echo "--------------------------------"
if ./check-all-ci.sh; then
    echo ""
    echo "🎉 SUCCESS! All CI issues have been fixed!"
    echo "=========================================="
    echo "✅ PHP dependencies: Fixed"
    echo "✅ Frontend formatting: Fixed"
    echo "✅ Laravel tests: Working"
    echo "✅ All CI checks: Passing"
    echo ""
    echo "🚀 Ready to push to GitHub!"
else
    echo ""
    echo "⚠️  Some issues still remain. Check the output above."
    echo "You may need to fix remaining issues manually."
    exit 1
fi
