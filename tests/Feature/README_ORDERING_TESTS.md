# Ordering System Test Suite

This directory contains comprehensive tests for the SavedFeast ordering and tracking system. The test suite covers all aspects of the ordering workflow from creation to completion.

## Test Files Overview

### Core Order Tests
- **`OrderTest.php`** - Basic order creation, viewing, and cancellation functionality
- **`OrderTrackingTest.php`** - Order state management, pickup codes, and provider actions
- **`CompleteOrderFlowTest.php`** - End-to-end order flow testing

### Provider-Specific Tests
- **`ProviderOrderStatsTest.php`** - Provider dashboard statistics and order management
- **`CountdownTimerTest.php`** - Pickup window management and countdown functionality

### Rate Limiting Tests
- **`OrderRateLimitingTest.php`** - API rate limiting and concurrent request handling
- **`FrontendRateLimitingTest.php`** - Frontend component rate limiting and UI behavior

### Payment Tests
- **`CashOnPickupOrderTest.php`** - Cash on pickup payment method functionality

### Test Suite Documentation
- **`OrderingSystemTestSuite.php`** - Comprehensive test suite overview and documentation

## Test Coverage

### Order Lifecycle
1. **Order Creation**
   - Customer creates order with valid items
   - Order validation (meal availability, quantities)
   - Order state initialization (PENDING)

2. **Order Acceptance**
   - Provider views pending orders
   - Provider accepts order with pickup window
   - Pickup code generation and encryption
   - Order state transition (PENDING → ACCEPTED)

3. **Order Preparation**
   - Provider marks order as ready
   - Order state transition (ACCEPTED → READY_FOR_PICKUP)
   - Customer can view pickup code

4. **Order Completion**
   - Provider completes order with pickup code
   - Code validation and attempt tracking
   - Order state transition (READY_FOR_PICKUP → COMPLETED)

5. **Order Cancellation**
   - Customer cancellation (PENDING → CANCELLED_BY_CUSTOMER)
   - Provider cancellation (any state → CANCELLED_BY_RESTAURANT)

### Rate Limiting & Performance
- **API Rate Limiting**: 300 requests per minute for provider endpoints
- **Frontend Rate Limiting**: 2-second cooldown between API calls
- **Concurrent Request Handling**: Multiple simultaneous operations
- **Countdown Timer Optimization**: Prevents excessive API calls on expiry

### Security & Authorization
- **User Authentication**: All endpoints require valid authentication
- **Role-Based Access**: Providers can only access their restaurant's orders
- **Order Ownership**: Customers can only access their own orders
- **Pickup Code Security**: Encrypted codes with attempt tracking

### Data Integrity
- **State Transitions**: Enforced order state machine
- **Event Logging**: All order events are tracked
- **Atomic Operations**: Database transactions for critical operations
- **Validation**: Input validation for all order operations

## Running the Tests

### Run All Ordering Tests
```bash
php artisan test --filter="OrderTest|OrderTrackingTest|ProviderOrderStatsTest|OrderRateLimitingTest|FrontendRateLimitingTest|CompleteOrderFlowTest|CountdownTimerTest|CashOnPickupOrderTest"
```

### Run Specific Test Suites
```bash
# Core order functionality
php artisan test tests/Feature/OrderTest.php

# Order tracking and state management
php artisan test tests/Feature/OrderTrackingTest.php

# Provider statistics
php artisan test tests/Feature/ProviderOrderStatsTest.php

# Rate limiting
php artisan test tests/Feature/OrderRateLimitingTest.php

# Complete order flow
php artisan test tests/Feature/CompleteOrderFlowTest.php
```

### Run Individual Tests
```bash
# Test specific functionality
php artisan test --filter="test_user_can_create_order"
php artisan test --filter="test_provider_can_accept_order"
php artisan test --filter="test_complete_order_flow_from_creation_to_completion"
```

## Test Data Setup

Each test class includes proper setup with:
- **Users**: Customer and provider with appropriate roles
- **Restaurant**: Provider-owned restaurant
- **Meals**: Available meals with proper pricing
- **Categories**: Meal categories
- **Orders**: Test orders in various states

## Key Test Scenarios

### Happy Path
1. Customer creates order
2. Provider accepts with pickup window
3. Provider marks as ready
4. Provider completes with correct code
5. Order is completed successfully

### Error Handling
1. Invalid meal IDs
2. Insufficient quantities
3. Wrong pickup codes
4. Invalid state transitions
5. Unauthorized access attempts

### Edge Cases
1. Expired pickup windows
2. Concurrent order operations
3. Rapid API requests
4. Multiple failed code attempts
5. Orders without pickup windows

## Performance Considerations

The tests verify that the system handles:
- **High Request Volume**: Multiple rapid API calls
- **Concurrent Operations**: Simultaneous order actions
- **Rate Limiting**: Proper throttling implementation
- **Database Performance**: Efficient queries and transactions

## Security Testing

Tests ensure:
- **Authentication**: All endpoints require valid tokens
- **Authorization**: Users can only access authorized resources
- **Data Protection**: Sensitive data is properly encrypted
- **Input Validation**: All inputs are validated and sanitized

## Maintenance

When adding new features to the ordering system:
1. Add corresponding tests to the appropriate test file
2. Update this README with new test coverage
3. Ensure all tests pass before deployment
4. Consider adding integration tests for complex workflows

## Test Database

Tests use the `RefreshDatabase` trait to ensure:
- Clean database state for each test
- No interference between tests
- Consistent test results
- Proper data isolation
