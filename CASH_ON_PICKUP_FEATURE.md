# Cash on Pickup Feature Documentation

## Overview

The Cash on Pickup feature allows customers to place orders and pay when they collect their food from the restaurant. This feature includes commission tracking, weekly invoice generation, and comprehensive settlement management for both providers and administrators.

## Features Implemented

### 1. Payment Method Selection
- **Cash on Pickup**: Default payment method where customers pay at the restaurant
- **Online Payment**: Reserved for future implementation
- Payment method is stored with each order and used for commission calculations

### 2. Commission Tracking
- **Per-order commission**: Calculated and stored at order creation time
- **Restaurant-specific rates**: Each restaurant can have its own commission rate
- **Default fallback**: Uses configurable default rate (7%) if restaurant rate not set
- **Rate snapshots**: Commission rate is stored with each order for historical accuracy

### 3. Weekly Invoice Generation
- **Automatic generation**: Runs every Monday at 00:05 for the previous week
- **Manual trigger**: Admins can generate invoices manually via API
- **Period-based**: Invoices cover Monday to Sunday of the previous week
- **Status tracking**: Invoices have states: draft, sent, paid, overdue, void

### 4. Provider Dashboard
- **Settlements summary**: Shows amount owed, last invoice status, next invoice date
- **Invoice history**: Paginated list of all invoices with status and totals
- **Order tracking**: View recent completed orders not yet invoiced
- **PDF downloads**: Download invoice PDFs when available

### 5. Admin Dashboard
- **Invoice management**: View all restaurant invoices with filtering
- **Status updates**: Mark invoices as sent, paid, or overdue
- **Manual generation**: Trigger weekly invoice generation
- **Bulk operations**: Manage multiple invoices efficiently

## Database Schema

### New Tables

#### `restaurant_invoices`
- `id`: Primary key
- `restaurant_id`: Foreign key to restaurants table
- `period_start`: Start date of invoice period
- `period_end`: End date of invoice period
- `status`: Invoice status (draft, sent, paid, overdue, void)
- `subtotal_sales`: Total sales amount for the period
- `commission_rate`: Commission rate used for the period
- `commission_total`: Total commission amount
- `orders_count`: Number of orders included
- `pdf_path`: Path to generated PDF file
- `meta`: JSON metadata (currency, notes, etc.)

#### `restaurant_invoice_items`
- `id`: Primary key
- `invoice_id`: Foreign key to restaurant_invoices
- `order_id`: Foreign key to orders table
- `order_total`: Order total amount (snapshot)
- `commission_rate`: Commission rate used (snapshot)
- `commission_amount`: Commission amount (snapshot)

### Modified Tables

#### `orders`
- `payment_method`: Payment method (CASH_ON_PICKUP, ONLINE)
- `commission_rate`: Commission rate used for this order
- `commission_amount`: Calculated commission amount
- `completed_at`: Timestamp when order was completed
- `invoiced_at`: Timestamp when order was included in an invoice

#### `restaurants`
- `commission_rate`: Restaurant-specific commission rate (default: 7%)

## API Endpoints

### Provider Endpoints (Protected by provider role)

#### GET `/api/provider/settlements/summary`
Returns settlement summary for the authenticated provider's restaurant.

**Response:**
```json
{
  "status": true,
  "data": {
    "amount_owed": "150.00",
    "last_invoice_status": "sent",
    "last_invoice_date": "2024-01-15",
    "next_invoice_date": "2024-01-22",
    "restaurant_id": 1,
    "restaurant_name": "Demo Restaurant"
  }
}
```

#### GET `/api/provider/settlements/invoices`
Returns paginated list of invoices for the provider's restaurant.

#### GET `/api/provider/settlements/invoices/{id}`
Returns detailed invoice information with line items.

#### GET `/api/provider/settlements/invoices/{id}/download`
Downloads the invoice PDF file.

#### GET `/api/provider/settlements/orders`
Returns recent completed orders not yet invoiced.

### Admin Endpoints (Protected by admin role)

#### POST `/api/admin/settlements/generate?period=weekly`
Manually triggers weekly invoice generation for the previous week.

**Response:**
```json
{
  "status": true,
  "message": "Invoice generation completed",
  "data": {
    "invoices_created": 5,
    "orders_processed": 25,
    "errors": []
  }
}
```

#### GET `/api/admin/settlements/invoices`
Returns all invoices with optional filtering by restaurant, status, or date range.

#### POST `/api/admin/settlements/invoices/{id}/mark-sent`
Marks an invoice as sent.

#### POST `/api/admin/settlements/invoices/{id}/mark-paid`
Marks an invoice as paid.

#### POST `/api/admin/settlements/invoices/{id}/mark-overdue`
Marks an invoice as overdue.

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# SavedFeast Commission & Invoicing Settings
SF_COMMISSION_DEFAULT_RATE=7
SF_INVOICE_PERIOD=WEEKLY
SF_INVOICE_WEEKLY_DAY=monday
```

### Configuration File

The feature uses `config/savedfeast.php`:

```php
return [
    'commission' => [
        'default_rate' => (float) env('SF_COMMISSION_DEFAULT_RATE', 7.0),
    ],
    'invoicing' => [
        'period' => env('SF_INVOICE_PERIOD', 'WEEKLY'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Beirut'),
        'invoice_day' => env('SF_INVOICE_WEEKLY_DAY', 'monday'),
    ],
];
```

## Console Commands

### Generate Weekly Invoices

```bash
# Generate invoices for the previous week
php artisan invoices:generate-weekly --period=previous

# Generate invoices for the current week
php artisan invoices:generate-weekly --period=current
```

### Backfill Commission Rates

```bash
# Update existing restaurants with default commission rate
php artisan db:seed --class=BackfillCommissionRatesSeeder
```

### Demo Data

```bash
# Create demo cash-on-pickup orders for testing
php artisan db:seed --class=DemoCashOnPickupOrdersSeeder
```

## Scheduler

The weekly invoice generation is automatically scheduled in `app/Console/Kernel.php`:

```php
$schedule->command('invoices:generate-weekly --period=previous')
    ->weeklyOn(1, '00:05')
    ->timezone(config('savedfeast.invoicing.timezone', 'Asia/Beirut'))
    ->withoutOverlapping()
    ->runInBackground();
```

## Frontend Components

### Checkout Page Updates
- Added payment method selection with radio buttons
- Cash on Pickup is selected by default
- Online payment option is disabled (coming soon)
- Clear explanation of cash on pickup process

### Provider Settlements Page
- Summary cards showing amount owed, last invoice status, next invoice date
- Invoice history table with pagination
- Status badges and action buttons
- PDF download functionality

### Admin Settlements Page
- Invoice generation button
- Comprehensive invoice management table
- Status update buttons (sent, paid, overdue)
- Filtering and search capabilities

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test classes
php artisan test --filter=CashOnPickupOrderTest
php artisan test --filter=CommissionServiceTest

# Run with coverage
php artisan test --coverage
```

### Test Coverage

The test suite covers:
- Order creation with cash on pickup payment method
- Commission calculation accuracy
- Invoice generation and management
- API endpoint authorization and responses
- Status transitions and business rules
- Provider and admin access controls

## Business Rules

### Commission Calculation
- Commission is calculated at order creation time
- Rate is determined by restaurant's commission_rate or default rate
- Amount is calculated as: `(order_total * commission_rate) / 100`
- Both rate and amount are stored as snapshots with the order

### Invoice Generation
- Only completed cash-on-pickup orders are included
- Orders must be completed within the specified period
- Orders already invoiced are excluded
- One invoice is created per restaurant per period

### Status Transitions
- **draft** → **sent**: Invoice can be marked as sent
- **sent** → **paid**: Invoice can be marked as paid
- **sent** → **overdue**: Invoice can be marked as overdue
- **overdue** → **paid**: Overdue invoice can be marked as paid
- No other transitions are allowed

## Security

### Authorization
- Providers can only access their own restaurant's data
- Admins have full access to all invoices and settlements
- All endpoints are protected by Laravel Sanctum authentication
- Role-based access control using Laravel Gates

### Data Protection
- Commission rates and amounts are stored as decimal values
- All monetary calculations use proper decimal precision
- Invoice data is immutable once created (no modifications allowed)

## Troubleshooting

### Common Issues

1. **Commission not calculated**: Ensure restaurant has a commission_rate set
2. **Invoices not generating**: Check that orders are completed and not already invoiced
3. **PDF not available**: PDF generation is not yet implemented (placeholder)
4. **Permission denied**: Verify user has correct role (provider/admin)

### Debug Commands

```bash
# Check commission rates for all restaurants
php artisan tinker
>>> App\Models\Restaurant::select('id', 'name', 'commission_rate')->get();

# Check orders ready for invoicing
>>> App\Models\Order::where('payment_method', 'CASH_ON_PICKUP')
    ->where('status', 'completed')
    ->whereNull('invoiced_at')
    ->count();
```

## Future Enhancements

### Planned Features
1. **PDF Generation**: Implement invoice PDF generation using DomPDF
2. **Email Notifications**: Send invoice notifications to restaurants
3. **Payment Integration**: Add online payment processing
4. **Advanced Reporting**: Detailed analytics and reporting
5. **Multi-currency Support**: Support for different currencies
6. **Credit Notes**: Handle refunds and adjustments

### API Improvements
1. **Webhook Support**: Real-time notifications for invoice status changes
2. **Bulk Operations**: Batch processing for multiple invoices
3. **Export Functionality**: CSV/Excel export of invoice data
4. **Advanced Filtering**: More sophisticated search and filter options

## Support

For technical support or questions about this feature, please refer to:
- API documentation in `/docs/api/`
- Test files in `/tests/Feature/` and `/tests/Unit/`
- Configuration files in `/config/`
- Database migrations in `/database/migrations/`
