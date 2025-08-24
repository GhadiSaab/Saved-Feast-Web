// resources/js/routes/FeedPage.tsx
import React, { useState, useEffect } from 'react';
import axios from 'axios'; // Axios is already a dependency
import MealCard from '../components/MealCard'; // Import the actual component

// Define a type for the Meal data (adjust based on actual API response)
interface Meal {
    id: number;
    title: string; // Changed from name
    description: string;
    current_price: number; // Changed from price
    original_price?: number | null; // Added original_price
    image?: string | null; // Changed from image_url to image, matching backend
    available_from: string; // Add available_from (ISO string)
    available_until: string; // Add available_until (ISO string)
    restaurant?: { // Assuming restaurant is nested
        name: string;
    };
    category?: {
        id: number;
        name: string;
    };
    // Add other relevant fields
}

interface PaginationInfo {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    has_more_pages: boolean;
}

interface ApiResponse {
    status: boolean;
    message: string;
    data: Meal[];
    pagination: PaginationInfo;
    filters_applied: any;
}

interface FilterOptions {
    categories: Array<{ id: number; name: string }>;
    price_range: { min: number; max: number };
    sort_options: Array<{ value: string; label: string }>;
    sort_orders: Array<{ value: string; label: string }>;
}

const FeedPage: React.FC = () => {
    const [meals, setMeals] = useState<Meal[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [pagination, setPagination] = useState<PaginationInfo | null>(null);
    const [filters, setFilters] = useState<FilterOptions | null>(null);
    
    // Filter states

    const [searchTerm, setSearchTerm] = useState<string>('');
    const [selectedCategory, setSelectedCategory] = useState<number | ''>('');
    const [priceRange, setPriceRange] = useState<{ min: string; max: string }>({ min: '', max: '' });
    const [sortBy, setSortBy] = useState<string>('created_at');
    const [sortOrder, setSortOrder] = useState<string>('desc');

    // Fetch filter options
    useEffect(() => {
        const fetchFilters = async () => {
            try {
                const response = await axios.get<{ data: FilterOptions }>('/api/meals/filters');
                setFilters(response.data.data);
            } catch (err) {
                console.error("Error fetching filters:", err);
            }
        };
        fetchFilters();
    }, []);

    // Fetch meals with filters
    const fetchMeals = async (page: number = 1) => {
        setLoading(true);
        setError(null);
        
        try {
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: '12', // Show 12 meals per page
                sort_by: sortBy,
                sort_order: sortOrder,
            });

            if (searchTerm) params.append('search', searchTerm);
            if (typeof selectedCategory === 'number') params.append('category_id', selectedCategory.toString());
            if (priceRange.min && priceRange.min.trim() !== '') params.append('min_price', priceRange.min);
            if (priceRange.max && priceRange.max.trim() !== '') params.append('max_price', priceRange.max);
            params.append('available', 'true'); // Only show available meals by default

            const response = await axios.get<ApiResponse>(`/api/meals?${params.toString()}`);
            
            if (response.data.status) {
                setMeals(response.data.data);
                setPagination(response.data.pagination);

            } else {
                setError(response.data.message || 'Failed to fetch meals');
            }
        } catch (err: any) {
            if (err.response && err.response.status === 422) {
                // Validation error - show specific errors
                const validationErrors = err.response.data.errors;
                if (validationErrors) {
                    const errorMessages = Object.values(validationErrors).flat().join(', ');
                    setError(`Validation error: ${errorMessages}`);
                } else {
                    setError(err.response.data.message || 'Validation failed');
                }
            } else {
                setError('Failed to fetch meals. Please try again later.');
            }
            console.error("Error fetching meals:", err);
        } finally {
            setLoading(false);
        }
    };

    // Initial fetch
    useEffect(() => {
        fetchMeals(1);
    }, []); // Only run on mount

    // Handle filter changes
    const handleFilterChange = () => {
        fetchMeals(1); // Reset to first page when filters change
    };

    // Handle search
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        handleFilterChange();
    };

    // Handle pagination
    const handlePageChange = (page: number) => {
        fetchMeals(page);
    };

    return (
        <div className="feed-page">
            {/* Hero Section */}
            <section className="hero-section mb-5">
                <div className="container">
                    <div className="hero-content">
                        <h1>Save Food, Save Money, Save the Planet</h1>
                        <p>
                            Discover delicious meals from local restaurants at amazing prices. 
                            Help reduce food waste while enjoying great food and saving money.
                        </p>
                        <div className="hero-stats">
                            <div className="stat">
                                <div className="stat-number">500+</div>
                                <div className="stat-label">Meals Saved</div>
                            </div>
                            <div className="stat">
                                <div className="stat-number">€2,500+</div>
                                <div className="stat-label">Money Saved</div>
                            </div>
                            <div className="stat">
                                <div className="stat-number">25+</div>
                                <div className="stat-label">Restaurant Partners</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Main Content */}
            <div className="container">
                <div className="feed-page-container">
                    <h1 className="page-title">Today's Feasts</h1>
                    
                    {/* Enhanced Filters Section */}
                    <div className="filters-section">
                        <form onSubmit={handleSearch} className="row g-3">
                            {/* Search */}
                            <div className="col-md-4">
                                <div className="input-group">
                                    <span className="input-group-text">
                                        <i className="fas fa-search"></i>
                                    </span>
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Search for delicious meals..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                            </div>
                            
                            {/* Category Filter */}
                            <div className="col-md-2">
                                <select
                                    className="form-select"
                                    value={selectedCategory}
                                    onChange={(e) => {
                                        setSelectedCategory(e.target.value ? Number(e.target.value) : '');
                                        handleFilterChange();
                                    }}
                                >
                                    <option value="">All Categories</option>
                                    {filters?.categories.map(category => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            
                            {/* Price Range */}
                            <div className="col-md-2">
                                <input
                                    type="number"
                                    className="form-control"
                                    placeholder="Min Price"
                                    value={priceRange.min}
                                    onChange={(e) => {
                                        setPriceRange(prev => ({ ...prev, min: e.target.value }));
                                        handleFilterChange();
                                    }}
                                />
                            </div>
                            <div className="col-md-2">
                                <input
                                    type="number"
                                    className="form-control"
                                    placeholder="Max Price"
                                    value={priceRange.max}
                                    onChange={(e) => {
                                        setPriceRange(prev => ({ ...prev, max: e.target.value }));
                                        handleFilterChange();
                                    }}
                                />
                            </div>
                            
                            {/* Sort */}
                            <div className="col-md-2">
                                <select
                                    className="form-select"
                                    value={`${sortBy}-${sortOrder}`}
                                    onChange={(e) => {
                                        const [field, order] = e.target.value.split('-');
                                        setSortBy(field);
                                        setSortOrder(order);
                                        handleFilterChange();
                                    }}
                                >
                                    <option value="created_at-desc">Newest First</option>
                                    <option value="created_at-asc">Oldest First</option>
                                    <option value="current_price-asc">Price: Low to High</option>
                                    <option value="current_price-desc">Price: High to Low</option>
                                    <option value="title-asc">Name: A-Z</option>
                                    <option value="title-desc">Name: Z-A</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    {/* Loading State */}
                    {loading && (
                        <div className="d-flex justify-content-center py-5">
                            <div className="spinner-border" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    )}

                    {/* Error State */}
                    {error && (
                        <div className="alert alert-danger fade-in-up">
                            <i className="fas fa-exclamation-triangle me-2"></i>
                            {error}
                        </div>
                    )}

                    {/* Meals Grid */}
                    {!loading && !error && (
                        <>
                            <div className="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                                {meals.length > 0 ? (
                                    meals.map((meal, index) => (
                                        <div className="col fade-in-up" key={meal.id} style={{ animationDelay: `${index * 0.1}s` }}>
                                            <MealCard meal={meal} />
                                        </div>
                                    ))
                                ) : (
                                    <div className="col-12 text-center py-5">
                                        <div className="empty-state">
                                            <i className="fas fa-utensils fa-3x text-muted mb-3"></i>
                                            <h4 className="text-muted">No meals available</h4>
                                            <p className="text-muted">
                                                No meals match your current filters. Try adjusting your search criteria.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Pagination */}
                            {pagination && pagination.last_page > 1 && (
                                <nav className="mt-5">
                                    <ul className="pagination justify-content-center">
                                        {/* Previous Page */}
                                        <li className={`page-item ${pagination.current_page === 1 ? 'disabled' : ''}`}>
                                            <button
                                                className="page-link"
                                                onClick={() => handlePageChange(pagination.current_page - 1)}
                                                disabled={pagination.current_page === 1}
                                            >
                                                <i className="fas fa-chevron-left"></i> Previous
                                            </button>
                                        </li>

                                        {/* Page Numbers */}
                                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(page => (
                                            <li key={page} className={`page-item ${page === pagination.current_page ? 'active' : ''}`}>
                                                <button
                                                    className="page-link"
                                                    onClick={() => handlePageChange(page)}
                                                >
                                                    {page}
                                                </button>
                                            </li>
                                        ))}

                                        {/* Next Page */}
                                        <li className={`page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`}>
                                            <button
                                                className="page-link"
                                                onClick={() => handlePageChange(pagination.current_page + 1)}
                                                disabled={pagination.current_page === pagination.last_page}
                                            >
                                                Next <i className="fas fa-chevron-right"></i>
                                            </button>
                                        </li>
                                    </ul>
                                </nav>
                            )}

                            {/* Results Info */}
                            {pagination && (
                                <div className="text-center text-muted mt-4">
                                    <i className="fas fa-info-circle me-2"></i>
                                    Showing {pagination.from} to {pagination.to} of {pagination.total} meals
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default FeedPage;
