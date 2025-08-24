# Contributing to SavedFeast Web Platform 🤝

Thank you for your interest in contributing to SavedFeast Web Platform! This document provides guidelines and information for contributors.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Feature Requests](#feature-requests)

## 📜 Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

### Our Standards

- **Respectful Communication**: Use welcoming and inclusive language
- **Professional Behavior**: Be respectful of differing viewpoints and experiences
- **Constructive Feedback**: Gracefully accept constructive criticism
- **Focus on Impact**: Focus on what is best for the community
- **Empathy**: Show empathy towards other community members

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer 2.0+
- Node.js 18+ and npm
- MySQL 8.0+
- Git
- A code editor (VS Code recommended)

### Fork and Clone

1. **Fork the repository**
   - Go to [SavedFeast Web Platform](https://github.com/yourusername/savedfeast-web)
   - Click the "Fork" button in the top right

2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR_USERNAME/savedfeast-web.git
   cd savedfeast-web
   ```

3. **Add upstream remote**
   ```bash
   git remote add upstream https://github.com/yourusername/savedfeast-web.git
   ```

## 🔧 Development Setup

### 1. Install Dependencies

```bash
# Backend dependencies
composer install

# Frontend dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Update database configuration in .env
DB_DATABASE=savedfeast
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE savedfeast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seed data
php artisan migrate:fresh --seed

# Create storage link
php artisan storage:link
```

### 4. Start Development Servers

```bash
# Start both backend and frontend
npm run serve:full

# Or start separately
npm run serve:backend  # Backend only
npm run serve:frontend # Frontend only
```

## 📝 Coding Standards

### PHP/Laravel Guidelines

- **PSR-12**: Follow PSR-12 coding standards
- **Laravel Conventions**: Follow Laravel naming conventions
- **Type Hints**: Use type hints for all method parameters and return types
- **DocBlocks**: Add proper DocBlocks for public methods

```php
// ✅ Good
class MealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $meals = Meal::with(['category', 'restaurant'])
            ->filter($request->all())
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => true,
            'message' => 'Meals retrieved successfully',
            'data' => $meals->items(),
            'pagination' => [
                'current_page' => $meals->currentPage(),
                'last_page' => $meals->lastPage(),
                'per_page' => $meals->perPage(),
                'total' => $meals->total(),
            ],
        ]);
    }
}

// ❌ Avoid
class mealcontroller extends controller
{
    public function index($request)
    {
        $meals = meal::all();
        return $meals;
    }
}
```

### React/TypeScript Guidelines

- **Functional Components**: Use functional components with hooks
- **TypeScript**: Define proper types for all props and state
- **Custom Hooks**: Extract reusable logic into custom hooks
- **Performance**: Use React.memo for expensive components

```typescript
// ✅ Good
import React, { memo } from 'react';

interface MealCardProps {
  meal: Meal;
  onAddToCart: (meal: Meal) => void;
  isLoading?: boolean;
}

export const MealCard = memo<MealCardProps>(({ meal, onAddToCart, isLoading }) => {
  const handleAddToCart = () => {
    onAddToCart(meal);
  };

  return (
    <div className="meal-card">
      <img src={meal.image} alt={meal.title} />
      <h3>{meal.title}</h3>
      <p>{meal.description}</p>
      <button 
        onClick={handleAddToCart}
        disabled={isLoading}
      >
        Add to Cart
      </button>
    </div>
  );
});

// ❌ Avoid
const MealCard = (props: any) => {
  return <div>{props.meal.title}</div>;
};
```

### File and Folder Naming

- **PHP Classes**: PascalCase (e.g., `MealController.php`)
- **PHP Methods**: camelCase (e.g., `getMealById()`)
- **React Components**: PascalCase (e.g., `MealCard.tsx`)
- **React Hooks**: camelCase with `use` prefix (e.g., `useMealData.ts`)
- **CSS/SCSS**: kebab-case (e.g., `meal-card.scss`)

### Import Organization

```typescript
// 1. React imports
import React, { useState, useEffect } from 'react';

// 2. Third-party library imports
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

// 3. Local imports (absolute paths)
import { MealCard } from '@/components/MealCard';
import { useAuth } from '@/context/AuthContext';

// 4. Relative imports
import { styles } from './styles';
```

## 🧪 Testing Guidelines

### PHP Testing

```php
// ✅ Good test example
class MealTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_meals(): void
    {
        // Arrange
        $meals = Meal::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/meals');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'price',
                    ],
                ],
            ]);
    }

    public function test_can_filter_meals_by_category(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $meals = Meal::factory()->count(3)->create(['category_id' => $category->id]);

        // Act
        $response = $this->getJson("/api/meals?category_id={$category->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
```

### React Testing

```typescript
// ✅ Good test example
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { MealCard } from '../MealCard';

describe('MealCard', () => {
  const mockMeal = {
    id: 1,
    title: 'Margherita Pizza',
    description: 'Classic tomato and mozzarella',
    price: 15.99,
    originalPrice: 25.99,
  };

  const mockOnAddToCart = jest.fn();

  it('renders meal information correctly', () => {
    render(
      <MealCard meal={mockMeal} onAddToCart={mockOnAddToCart} />
    );

    expect(screen.getByText('Margherita Pizza')).toBeInTheDocument();
    expect(screen.getByText('Classic tomato and mozzarella')).toBeInTheDocument();
    expect(screen.getByText('$15.99')).toBeInTheDocument();
  });

  it('calls onAddToCart when add to cart button is clicked', () => {
    render(
      <MealCard meal={mockMeal} onAddToCart={mockOnAddToCart} />
    );

    fireEvent.click(screen.getByText('Add to Cart'));
    expect(mockOnAddToCart).toHaveBeenCalledWith(mockMeal);
  });
});
```

### Running Tests

```bash
# PHP tests
composer test
composer test:coverage
composer test:parallel

# Frontend tests
npm test
npm run test:coverage
npm run test:watch
```

## 🔄 Pull Request Process

### 1. Create a Feature Branch

```bash
git checkout -b feature/amazing-feature
```

### 2. Make Your Changes

- Write clean, well-documented code
- Add tests for new functionality
- Update documentation if needed
- Follow the coding standards

### 3. Commit Your Changes

Use conventional commit messages:

```bash
# Format: type(scope): description
git commit -m 'feat(meals): add meal filtering functionality'
git commit -m 'fix(auth): resolve token refresh issue'
git commit -m 'docs(readme): update installation instructions'
```

**Commit Types:**
- `feat`: New features
- `fix`: Bug fixes
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Test changes
- `chore`: Build process or auxiliary tool changes

### 4. Push to Your Fork

```bash
git push origin feature/amazing-feature
```

### 5. Create a Pull Request

1. Go to your fork on GitHub
2. Click "New Pull Request"
3. Select your feature branch
4. Fill out the PR template
5. Submit the PR

### 6. PR Review Process

- **Code Review**: At least one maintainer must approve
- **CI Checks**: All tests must pass
- **Code Coverage**: Maintain >90% coverage
- **Documentation**: Update docs for new features

## 🐛 Issue Reporting

### Before Creating an Issue

1. **Search existing issues** to avoid duplicates
2. **Check the documentation** for solutions
3. **Try the latest version** of the application

### Issue Template

When creating an issue, please use the provided template and include:

- **Description**: Clear description of the problem
- **Steps to Reproduce**: Detailed steps to reproduce the issue
- **Expected Behavior**: What you expected to happen
- **Actual Behavior**: What actually happened
- **Environment**: OS, PHP version, Node.js version
- **Screenshots**: If applicable
- **Logs**: Error logs or console output

### Example Issue

```markdown
## Bug Report

### Description
The meal filtering doesn't work correctly when multiple filters are applied.

### Steps to Reproduce
1. Go to the meals page
2. Select a category filter
3. Set a price range filter
4. Notice the filtering doesn't work as expected

### Expected Behavior
The meals should be filtered by both category and price range.

### Actual Behavior
Only the last applied filter is working.

### Environment
- OS: Ubuntu 22.04
- PHP: 8.2.0
- Node.js: 18.0.0
- Database: MySQL 8.0

### Screenshots
[Add screenshots here]

### Logs
[Add relevant logs here]
```

## 💡 Feature Requests

### Before Submitting a Feature Request

1. **Check existing issues** for similar requests
2. **Consider the impact** on the overall application
3. **Think about implementation** complexity

### Feature Request Template

```markdown
## Feature Request

### Problem Statement
[Describe the problem this feature would solve]

### Proposed Solution
[Describe your proposed solution]

### Alternative Solutions
[Describe any alternative solutions you've considered]

### Additional Context
[Add any other context, screenshots, or examples]
```

## 📚 Additional Resources

### Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Testing Library Documentation](https://testing-library.com/docs/)

### Tools and Extensions

**VS Code Extensions:**
- Laravel Extension Pack
- PHP Intelephense
- ESLint
- Prettier
- TypeScript Importer
- Auto Rename Tag

**Development Tools:**
- Laravel Telescope
- Laravel Debugbar
- React Developer Tools
- Redux DevTools

## 🎉 Recognition

Contributors will be recognized in:

- **README.md**: Contributors section
- **Release Notes**: Feature contributors
- **GitHub**: Contributor graph and profile

## 📞 Getting Help

If you need help with contributing:

- **GitHub Discussions**: Use the Discussions tab
- **Issues**: Create an issue for questions
- **Documentation**: Check the docs folder
- **Community**: Join our community channels

---

Thank you for contributing to SavedFeast Web Platform! 🚀
