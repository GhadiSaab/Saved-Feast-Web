# Authorization System

This document describes the comprehensive role-based authorization system implemented using Laravel Policies, Gates, and Middleware.

## Overview

The authorization system ensures that:
- Only providers can create/edit meals and restaurants
- Users can only update their own resources
- Admins have full access to all resources
- Consumers can only access their own orders and profiles

## Roles

### Available Roles
1. **consumer** - Regular users who can place orders and manage their profiles
2. **provider** - Restaurant owners who can manage meals and restaurants
3. **admin** - Administrators with full system access

### Role Assignment
- New users automatically get the `consumer` role upon registration
- Existing users without roles can be assigned using: `php artisan users:assign-default-roles`

## Policies

### MealPolicy
**Location**: `app/Policies/MealPolicy.php`

**Permissions**:
- `viewAny` - Anyone can view meals (public listing)
- `view` - Anyone can view individual meals
- `create` - Only providers can create meals
- `update` - Only providers can update their own meals
- `delete` - Only providers can delete their own meals
- `restore` - Only providers can restore their own meals
- `forceDelete` - Only admins can permanently delete meals

### RestaurantPolicy
**Location**: `app/Policies/RestaurantPolicy.php`

**Permissions**:
- `viewAny` - Anyone can view restaurants (public listing)
- `view` - Anyone can view individual restaurants
- `create` - Only providers can create restaurants
- `update` - Only providers can update their own restaurants
- `delete` - Only providers can delete their own restaurants
- `restore` - Only providers can restore their own restaurants
- `forceDelete` - Only admins can permanently delete restaurants

### OrderPolicy
**Location**: `app/Policies/OrderPolicy.php`

**Permissions**:
- `viewAny` - Users can view their own orders, providers can view orders for their restaurants
- `view` - Users can view their own orders, providers can view orders for their restaurants, admins can view all
- `create` - Any authenticated user can create orders
- `update` - Users can update their own orders, providers can update orders for their restaurants, admins can update any
- `delete` - Only admins can delete orders
- `restore` - Only admins can restore orders
- `forceDelete` - Only admins can permanently delete orders

### UserPolicy
**Location**: `app/Policies/UserPolicy.php`

**Permissions**:
- `viewAny` - Only admins can view all users
- `view` - Users can view their own profile, admins can view any user
- `create` - Only admins can create users
- `update` - Users can update their own profile, admins can update any user
- `delete` - Only admins can delete users (users cannot delete themselves)
- `restore` - Only admins can restore users
- `forceDelete` - Only admins can permanently delete users (users cannot delete themselves)

## Gates

### Custom Gates
**Location**: `app/Providers/AuthServiceProvider.php`

**Available Gates**:
- `manage-meals` - Check if user can manage meals (provider role)
- `manage-restaurants` - Check if user can manage restaurants (provider role)
- `manage-orders` - Check if user can manage orders (provider role)
- `admin-access` - Check if user has admin access
- `provider-access` - Check if user has provider access
- `consumer-access` - Check if user has consumer access
- `own-meal` - Check if user owns a specific meal
- `own-restaurant` - Check if user owns a specific restaurant
- `own-order` - Check if user owns a specific order

## User Model Helper Methods

**Location**: `app/Models/User.php`

**Available Methods**:
- `hasRole(string $role)` - Check if user has a specific role
- `hasAnyRole(array $roles)` - Check if user has any of the specified roles
- `hasAllRoles(array $roles)` - Check if user has all of the specified roles
- `isAdmin()` - Check if user is an admin
- `isProvider()` - Check if user is a provider
- `isConsumer()` - Check if user is a consumer
- `ownsMeal(Meal $meal)` - Check if user owns a specific meal
- `ownsRestaurant(Restaurant $restaurant)` - Check if user owns a specific restaurant
- `ownsOrder(Order $order)` - Check if user owns a specific order

## Middleware

### CheckUserRole Middleware
**Location**: `app/Http/Middleware/CheckUserRole.php`

**Usage**: `middleware('role:provider')`

**Function**: Checks if the authenticated user has the specified role and returns 403 Forbidden if they don't.

## Controller Implementation

### Using Policies in Controllers

```php
class MealController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Meal::class, 'meal');
    }

    public function store(Request $request)
    {
        // Check if user can create meals
        $this->authorize('create', Meal::class);
        
        // Create meal logic...
    }

    public function update(Request $request, Meal $meal)
    {
        // Check if user can update this specific meal
        $this->authorize('update', $meal);
        
        // Update meal logic...
    }
}
```

### Using Gates in Controllers

```php
public function index(Request $request)
{
    if (Gate::allows('manage-meals')) {
        // Provider logic
    } else {
        // Consumer logic
    }
}
```

### Using Helper Methods

```php
public function index(Request $request)
{
    $user = $request->user();
    
    if ($user->isProvider()) {
        // Provider logic
    } elseif ($user->isConsumer()) {
        // Consumer logic
    }
}
```

## Route Protection

### Using Middleware
```php
// Protect routes with role middleware
Route::middleware(['auth:sanctum', 'role:provider'])->group(function () {
    Route::apiResource('meals', MealController::class);
});
```

### Using Policies
```php
// Routes automatically protected by policies
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('meals', MealController::class);
});
```

## Error Responses

When authorization fails, the system returns appropriate HTTP status codes:

- **401 Unauthorized** - User not authenticated
- **403 Forbidden** - User authenticated but lacks permission
- **404 Not Found** - Resource not found or user cannot access it

## Testing Authorization

### Testing Policies
```php
// In tests
$user = User::factory()->create();
$meal = Meal::factory()->create();

$user->assignRole('provider');
$this->assertTrue($user->can('update', $meal));
```

### Testing Gates
```php
// In tests
$user = User::factory()->create();
$user->assignRole('provider');

$this->assertTrue(Gate::forUser($user)->allows('manage-meals'));
```

## Security Best Practices

1. **Always use policies for model-based authorization**
2. **Use gates for simple role checks**
3. **Never trust client-side role information**
4. **Always validate ownership before allowing updates**
5. **Use middleware for route-level protection**
6. **Log authorization failures for security monitoring**

## Migration and Setup

1. **Run migrations**: `php artisan migrate`
2. **Assign roles to existing users**: `php artisan users:assign-default-roles`
3. **Verify policies are registered**: Check `AuthServiceProvider.php`
4. **Test authorization**: Use the provided test examples

## Common Patterns

### Checking Multiple Permissions
```php
if ($user->can('update', $meal) && $user->can('manage-meals')) {
    // User can update this specific meal and manage meals in general
}
```

### Conditional Logic Based on Role
```php
if ($user->isProvider()) {
    // Provider-specific logic
} elseif ($user->isAdmin()) {
    // Admin-specific logic
} else {
    // Consumer-specific logic
}
```

### Resource Ownership Check
```php
if ($user->ownsMeal($meal)) {
    // User owns this meal
}
``` 