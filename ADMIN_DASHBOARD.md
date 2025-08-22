# Admin Dashboard

The SavedFeast Admin Dashboard provides comprehensive insights and management capabilities for administrators to monitor and control the platform.

## Features

### 📊 Overview Dashboard
- **Key Metrics**: Total users, revenue, orders, and restaurants at a glance
- **User Statistics**: New users this month, active users, role distribution
- **Order Statistics**: Orders this month, revenue this month, order status distribution
- **Recent Activity**: Latest orders and user registrations
- **System Health**: Active meals, restaurants, and average ratings

### 👥 User Management
- **User List**: View all users with pagination
- **Search & Filter**: Search by name/email and filter by role
- **Role Management**: Change user roles (admin, provider, consumer)
- **User Analytics**: View order count and review count per user

### 🏪 Restaurant Management
- **Restaurant List**: View all restaurants with performance metrics
- **Search**: Find restaurants by name
- **Performance Metrics**: Meals count, orders count, total revenue
- **Owner Information**: View restaurant owner details

### 📦 Order Management
- **Order List**: View all orders with customer details
- **Status Filtering**: Filter by order status (pending, completed, cancelled)
- **Order Details**: Customer information, amounts, and dates
- **Order Tracking**: Monitor order status changes

### 🍽️ Meal Management
- **Meal List**: View all meals across all restaurants
- **Search**: Find meals by title
- **Availability Status**: See which meals are in stock
- **Pricing Information**: Current prices and availability

### 📈 Analytics (Future Enhancement)
- **Revenue Trends**: Chart showing revenue over time
- **User Growth**: User registration trends
- **Top Performing Items**: Best-selling meals and restaurants
- **Advanced Reporting**: Custom date ranges and export capabilities

## Access Control

### Admin Role Required
- Only users with the `admin` role can access the dashboard
- Access is controlled via Laravel Gates (`admin-access`)
- Protected routes require authentication and admin role

### Security Features
- Rate limiting on all admin API endpoints
- Authentication required for all operations
- Role-based access control
- Audit trail for user role changes

## API Endpoints

### Dashboard Data
- `GET /api/admin/dashboard` - Get overview statistics and analytics

### User Management
- `GET /api/admin/users` - Get paginated user list with search/filter
- `PUT /api/admin/users/{user}/role` - Update user role
- `PUT /api/admin/users/{user}/status` - Toggle user status

### Restaurant Management
- `GET /api/admin/restaurants` - Get paginated restaurant list with search

### Order Management
- `GET /api/admin/orders` - Get paginated order list with status filter

### Meal Management
- `GET /api/admin/meals` - Get paginated meal list with search

### Analytics
- `GET /api/admin/analytics` - Get detailed analytics data

## Setup Instructions

### 1. Create Admin User
Run the admin seeder to create a default admin user:

```bash
php artisan db:seed --class=AdminSeeder
```

Default admin credentials:
- Email: `admin@savedfeast.com`
- Password: `admin123`

### 2. Access Dashboard
1. Login with admin credentials
2. Navigate to `/admin/dashboard` in the application
3. Or click "Admin Dashboard" in the navigation menu (visible only to admin users)

### 3. Database Requirements
Ensure the following tables exist and are properly seeded:
- `users` - User accounts
- `roles` - User roles (admin, provider, consumer)
- `role_user` - User-role relationships
- `restaurants` - Restaurant information
- `meals` - Meal listings
- `orders` - Order records
- `order_items` - Order details
- `categories` - Meal categories
- `reviews` - User reviews

## Customization

### Adding New Metrics
To add new metrics to the dashboard:

1. Update the `AdminController@dashboard` method
2. Add new data to the response array
3. Update the frontend component to display the new metrics

### Adding New Management Sections
To add new management sections:

1. Create new controller methods in `AdminController`
2. Add new API routes in `routes/api.php`
3. Create new React components for the frontend
4. Update the admin dashboard navigation

### Styling
The dashboard uses Bootstrap 5 classes and follows the existing application aesthetic:
- Primary colors: Bootstrap primary, success, info, warning
- Card-based layout for metrics and data
- Responsive design for mobile compatibility
- Consistent iconography using Font Awesome

## Troubleshooting

### Common Issues

1. **Access Denied**: Ensure user has admin role assigned
2. **Empty Dashboard**: Check if database has data (run seeders)
3. **API Errors**: Verify authentication token is valid
4. **Missing Data**: Ensure all required relationships exist in models

### Debug Mode
Enable debug mode in `.env` to see detailed error messages:

```
APP_DEBUG=true
```

## Future Enhancements

- [ ] Real-time notifications for new orders/users
- [ ] Export functionality for reports
- [ ] Advanced filtering and sorting options
- [ ] Bulk operations for user/restaurant management
- [ ] Email notifications for admin actions
- [ ] Audit logging for all admin actions
- [ ] Custom dashboard widgets
- [ ] Mobile-responsive admin interface
