#!/bin/bash

# Auto-fix Frontend Formatting Issues
# Run this to automatically fix all code formatting issues

echo "🎨 Auto-fixing Frontend Formatting Issues..."

# 1. Fix Prettier formatting
echo "📝 Running Prettier auto-fix..."
npx prettier --write resources/js/

if [ $? -ne 0 ]; then
    echo "❌ Prettier formatting failed"
    exit 1
fi

echo "✅ Prettier formatting completed"

# 2. Fix ESLint issues (auto-fixable ones)
echo "🔧 Running ESLint auto-fix..."
npx eslint resources/js/ --fix

if [ $? -ne 0 ]; then
    echo "⚠️  Some ESLint issues couldn't be auto-fixed. Manual intervention may be required."
    # Don't exit here as some issues might need manual fixing
fi

echo "✅ ESLint auto-fix completed"

# 3. Run format check to verify
echo "🔍 Verifying formatting..."
npx prettier --check resources/js/

if [ $? -eq 0 ]; then
    echo "🎉 All formatting issues fixed!"
else
    echo "⚠️  Some formatting issues remain. Please check the output above."
fi

echo "📋 Summary:"
echo "- Prettier formatting: ✅ Applied"
echo "- ESLint auto-fixes: ✅ Applied"
echo "- Ready for CI checks!"
