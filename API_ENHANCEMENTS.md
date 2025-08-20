# API Enhancements

## Logout Route

The logout route is now properly implemented with token revocation:

### Route
- **Method**: POST
- **URL**: `/api/logout`
- **Middleware**: `auth:sanctum`
- **Controller**: `AuthController@logout`

### Implementation
- Revokes the current user's access token
- Clears local storage on frontend
- Dispatches `authChange` event for UI updates
- Handles errors gracefully (clears local state even if server request fails)

## Enhanced Meals API

The meals API has been significantly enhanced with pagination, filtering, and validation:

### Base Route
- **Method**: GET
- **URL**: `/api/meals`
- **Controller**: `MealController@index`

### Query Parameters

#### Pagination
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page, 1-100 (default: 15)

#### Filters
- `category_id` (optional): Filter by category ID
- `restaurant_id` (optional): Filter by restaurant ID
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter (must be >= min_price)
- `available` (optional): Filter by availability (true/false)
- `search` (optional): Search in title, description, category name, or restaurant name

#### Sorting
- `sort_by` (optional): Sort field (`title`, `current_price`, `created_at`)
- `sort_order` (optional): Sort direction (`asc`, `desc`)

### Validation
All query parameters are validated with proper error responses (422 status code) for invalid values.

### Response Format
```json
{
  "status": true,
  "message": "Meals retrieved successfully",
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15,
    "has_more_pages": true
  },
  "filters_applied": {
    "category_id": null,
    "restaurant_id": null,
    "min_price": null,
    "max_price": null,
    "available": null,
    "search": null,
    "sort_by": "created_at",
    "sort_order": "desc"
  }
}
```

### Filters Endpoint
- **Method**: GET
- **URL**: `/api/meals/filters`
- **Controller**: `MealController@filters`

Returns available filter options:
```json
{
  "status": true,
  "message": "Filters retrieved successfully",
  "data": {
    "categories": [...],
    "price_range": {
      "min": 5.00,
      "max": 50.00
    },
    "sort_options": [...],
    "sort_orders": [...]
  }
}
```

## Example Usage

### Basic pagination
```
GET /api/meals?page=1&per_page=10
```

### Filtered search
```
GET /api/meals?category_id=1&min_price=10&max_price=30&available=true&search=pizza
```

### Sorted results
```
GET /api/meals?sort_by=current_price&sort_order=asc
```

### Combined filters
```
GET /api/meals?page=2&per_page=20&category_id=1&min_price=15&available=true&sort_by=title&sort_order=asc
``` 