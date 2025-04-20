import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth'; // Assuming auth helper is needed for token

// Define interfaces for Meal and Category based on backend structure
interface Category {
    id: number;
    name: string;
    // Add other fields if necessary from your Category model
}

interface Meal {
    id: number;
    name: string; // Mapped from 'title' in backend for consistency if needed
    description: string;
    price: number; // Matches backend type
    quantity: number; // Add quantity field
    category_id: number;
    image_url?: string | null; // Optional
    restaurant_id: number;
    created_at: string;
    updated_at: string;
    category?: Category; // Optional eager loaded category from backend
}

// Interface for the form data - use strings for input fields
interface MealFormData {
    id?: number | null; // Present when editing
    name: string;
    description: string;
    price: string; // Use string for form input, convert on submit
    quantity: string; // Add quantity field for form
    category_id: string; // Use string for form select, convert on submit
    image_url?: string;
}


const RestaurantDashboardPage: React.FC = () => {
    const [dashboardMessage, setDashboardMessage] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(true); // Overall loading for initial dashboard message
    const [error, setError] = useState<string | null>(null); // Overall error

    // State for Meal Management
    const [meals, setMeals] = useState<Meal[]>([]);
    const [categories, setCategories] = useState<Category[]>([]); // To populate dropdowns
    const [mealsLoading, setMealsLoading] = useState<boolean>(true);
    const [mealsError, setMealsError] = useState<string | null>(null);

    // State for Add/Edit Form/Modal (if using one)
    const [isFormVisible, setIsFormVisible] = useState<boolean>(false);
    const [formMode, setFormMode] = useState<'add' | 'edit'>('add'); // 'add' or 'edit'
    const [formData, setFormData] = useState<MealFormData>({
        name: '',
        description: '',
        price: '',
        quantity: '', // Initialize quantity
        category_id: '',
        image_url: '',
    });
    const [formSubmitting, setFormSubmitting] = useState<boolean>(false); // Loading state for form submission
    const [formError, setFormError] = useState<string | null>(null); // Errors specific to the form submission

    // --- Placeholder functions for CRUD operations ---
    const fetchMealsAndCategories = async () => {
        setMealsLoading(true);
        setMealsError(null);
        const token = auth.getToken();
        const headers = { Authorization: `Bearer ${token}` };

        try {
            // Fetch meals and categories concurrently
            // Assuming a general '/api/categories' endpoint exists and is accessible
            // If categories are provider-specific, adjust the endpoint
            const [mealsRes, categoriesRes] = await Promise.all([
                axios.get('/api/provider/meals', { headers }),
                axios.get('/api/categories', { headers }) // Adjust if needed
            ]);

            setMeals(mealsRes.data || []);
            setCategories(categoriesRes.data || []); // Ensure categories are fetched

        } catch (err: any) {
            console.error("Error fetching meals/categories:", err);
            if (err.response && (err.response.status === 401 || err.response.status === 403)) {
                setMealsError("Authorization error fetching meals/categories.");
            } else {
                setMealsError("Failed to load meals or categories.");
            }
        } finally {
            setMealsLoading(false);
        }
    };


    const handleAddMealClick = () => {
        setFormMode('add');
        setFormData({ // Reset form
            name: '', description: '', price: '', quantity: '', category_id: categories[0]?.id.toString() || '', image_url: '' // Reset quantity, Default to first category if available
        });
        setFormError(null);
        setIsFormVisible(true);
        // If using a modal, trigger modal show here
    };

    const handleEditMealClick = (meal: Meal) => {
        setFormMode('edit');
        setFormData({ // Pre-fill form
            id: meal.id,
            name: meal.name, // Assuming 'name' is used consistently in frontend state, maps to 'title' on backend save
            description: meal.description,
            price: String(meal.price), // Convert number to string for input
            quantity: String(meal.quantity), // Convert number to string for input
            category_id: String(meal.category_id), // Convert number to string for select
            image_url: meal.image_url || '',
        });
        setFormError(null);
        setIsFormVisible(true);
        // If using a modal, trigger modal show here
    };

    const handleDeleteMeal = async (mealId: number) => {
        if (!window.confirm('Are you sure you want to delete this meal?')) {
            return;
        }

        const token = auth.getToken();
        if (!token) {
            setMealsError("Authentication error. Please log in again."); // Use mealsError or a dedicated delete error state
            return;
        }
        const headers = { Authorization: `Bearer ${token}` };

        // Optional: Add a temporary loading state for the specific row or disable buttons

        try {
            await axios.delete(`/api/provider/meals/${mealId}`, { headers });

            // On successful deletion (status 204), update the local state
            setMeals(prevMeals => prevMeals.filter(meal => meal.id !== mealId));
            // Optionally show a success message

        } catch (err: any) {
            console.error(`Error deleting meal with ID ${mealId}:`, err);
            let errorMsg = "Failed to delete meal.";
            if (err.response) {
                 if (err.response.status === 401 || err.response.status === 403) {
                    errorMsg = "Authorization error. You might not have permission to delete this meal.";
                } else if (err.response.data && err.response.data.message) {
                    errorMsg = `Error: ${err.response.data.message}`;
                }
            }
            // Display the error - using mealsError or a temporary alert/toast
            setMealsError(errorMsg); // Or use a more specific error state/display method
            // alert(errorMsg); // Simple alert for now
        } finally {
            // Optional: Reset loading state if used
        }
    };

    const handleFormSubmit = async (event: React.FormEvent) => {
        event.preventDefault();
        event.preventDefault();
        setFormError(null);
        setFormSubmitting(true);

        const token = auth.getToken();
        if (!token) {
            setFormError("Authentication error. Please log in again.");
            setFormSubmitting(false);
            return;
        }
        const headers = { Authorization: `Bearer ${token}` };

        // Prepare data for API (convert types)
        const mealDataPayload = {
            name: formData.name, // Will be mapped to 'title' in backend controller if needed
            description: formData.description,
            price: parseFloat(formData.price), // Convert string to number
            quantity: parseInt(formData.quantity, 10), // Convert string to number
            category_id: parseInt(formData.category_id, 10), // Convert string to number
            image_url: formData.image_url || null, // Send null if empty
        };

        // Basic frontend validation (optional, backend validation is primary)
        if (isNaN(mealDataPayload.price) || isNaN(mealDataPayload.quantity) || isNaN(mealDataPayload.category_id)) {
            setFormError("Invalid price, quantity, or category. Please enter valid numbers.");
            setFormSubmitting(false);
            return;
        }

        try {
            let response;
            if (formMode === 'add') {
                // POST request to create a new meal
                response = await axios.post('/api/provider/meals', mealDataPayload, { headers });
            } else {
                // PUT request to update an existing meal
                if (!formData.id) {
                    throw new Error("Meal ID is missing for update.");
                }
                response = await axios.put(`/api/provider/meals/${formData.id}`, mealDataPayload, { headers });
            }

            // Success
            closeForm(); // Close the form
            await fetchMealsAndCategories(); // Refresh the meals list

        } catch (err: any) {
            console.error(`Error ${formMode === 'add' ? 'adding' : 'updating'} meal:`, err);
            if (err.response) {
                if (err.response.status === 422) {
                    // Handle validation errors
                    const errors = err.response.data.errors;
                    const errorMessages = Object.values(errors).flat().join(' '); // Combine validation messages
                    setFormError(`Validation failed: ${errorMessages}`);
                } else if (err.response.status === 401 || err.response.status === 403) {
                    setFormError("Authorization error. You might not have permission.");
                } else {
                    setFormError(`An error occurred: ${err.response.data.message || 'Please try again.'}`);
                }
            } else {
                setFormError("An unexpected error occurred. Please check your connection and try again.");
            }
        } finally {
            setFormSubmitting(false);
        }
    };

    const handleFormChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = event.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const closeForm = () => {
        setIsFormVisible(false);
        setFormError(null); // Clear errors when closing
        // If using a modal, trigger modal hide here
    }
    // --- End Placeholder functions ---


    useEffect(() => {
        const fetchDashboardData = async () => {
            if (!auth.isAuthenticated()) {
                setError("Please log in as a provider.");
                setLoading(false);
                setMealsLoading(false); // Also stop meals loading
                return;
            }

            setLoading(true);
            setError(null);
            try {
                // Fetch initial dashboard data
                const response = await axios.get('/api/provider/dashboard-data', {
                    headers: { Authorization: `Bearer ${auth.getToken()}` }
                });
                setDashboardMessage(response.data.message || 'Welcome!');

                // If dashboard data fetch is successful, fetch meals and categories
                await fetchMealsAndCategories(); // Call the function to load meals/cats

            } catch (err: any) {
                console.error("Error fetching dashboard data:", err);
                if (err.response && (err.response.status === 401 || err.response.status === 403)) {
                    setError("You are not authorized to view this page.");
                } else {
                    setError("Failed to load dashboard data.");
                }
            } finally {
                setLoading(false);
            }
        };

        // Initial fetch on component mount
        fetchDashboardData();

    }, []); // Empty dependency array means this runs once on mount

    if (loading) {
        return (
            <div className="d-flex justify-content-center mt-5">
                <div className="spinner-border" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    if (error) {
        return <div className="alert alert-danger">{error}</div>;
    }

    return (
        <div>
            <h1>Restaurant Dashboard</h1>
            <p>{dashboardMessage}</p>
            <hr />

            {/* Meal Management Section */}
            <div className="mb-4">
                <div className="d-flex justify-content-between align-items-center mb-3">
                    <h2>Manage Your Meals</h2>
                    <button className="btn btn-primary" onClick={handleAddMealClick}>
                        <i className="fas fa-plus me-2"></i>Add New Meal
                    </button>
                </div>

                {/* Add/Edit Meal Form (Conditionally Rendered) */}
                {isFormVisible && (
                    <div className="card mb-4 shadow-sm">
                        <div className="card-header">
                            <h5 className="mb-0">{formMode === 'add' ? 'Add New Meal' : 'Edit Meal'}</h5>
                        </div>
                        <div className="card-body">
                            {formError && <div className="alert alert-danger">{formError}</div>}
                            <form onSubmit={handleFormSubmit}>
                                <div className="mb-3">
                                    <label htmlFor="name" className="form-label">Meal Name</label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="name"
                                        name="name"
                                        value={formData.name}
                                        onChange={handleFormChange}
                                        required
                                        disabled={formSubmitting}
                                    />
                                </div>
                                <div className="mb-3">
                                    <label htmlFor="description" className="form-label">Description</label>
                                    <textarea
                                        className="form-control"
                                        id="description"
                                        name="description"
                                        rows={3}
                                        value={formData.description}
                                        onChange={handleFormChange}
                                        required
                                        disabled={formSubmitting}
                                    ></textarea>
                                </div>
                                <div className="row mb-3">
                                    <div className="col-md-6">
                                        <label htmlFor="price" className="form-label">Price ($)</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="form-control"
                                            id="price"
                                            name="price"
                                            value={formData.price}
                                            onChange={handleFormChange}
                                            required
                                            disabled={formSubmitting}
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label htmlFor="quantity" className="form-label">Quantity Available</label>
                                        <input
                                            type="number"
                                            step="1"
                                            min="0"
                                            className="form-control"
                                            id="quantity"
                                            name="quantity"
                                            value={formData.quantity}
                                            onChange={handleFormChange}
                                            required
                                            disabled={formSubmitting}
                                        />
                                    </div>
                                </div>
                                <div className="row mb-3">
                                    <div className="col-md-6">
                                        <label htmlFor="category_id" className="form-label">Category</label>
                                        <select
                                            className="form-select"
                                            id="category_id"
                                            name="category_id"
                                            value={formData.category_id}
                                            onChange={handleFormChange}
                                            required
                                            disabled={formSubmitting || categories.length === 0}
                                        >
                                            <option value="" disabled>Select a category</option>
                                            {categories.map(cat => (
                                                <option key={cat.id} value={cat.id}>
                                                    {cat.name}
                                                </option>
                                            ))}
                                        </select>
                                        {categories.length === 0 && !mealsLoading && (
                                            <div className="form-text text-warning">No categories found. Please add categories first.</div>
                                        )}
                                    </div>
                                </div>
                                <div className="mb-3">
                                    <label htmlFor="image_url" className="form-label">Image URL (Optional)</label>
                                    <input
                                        type="url"
                                        className="form-control"
                                        id="image_url"
                                        name="image_url"
                                        value={formData.image_url || ''}
                                        onChange={handleFormChange}
                                        disabled={formSubmitting}
                                    />
                                </div>
                                <div className="d-flex justify-content-end">
                                    <button
                                        type="button"
                                        className="btn btn-secondary me-2"
                                        onClick={closeForm}
                                        disabled={formSubmitting}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        className="btn btn-success"
                                        disabled={formSubmitting}
                                    >
                                        {formSubmitting ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                {formMode === 'add' ? 'Adding...' : 'Updating...'}
                                            </>
                                        ) : (
                                            formMode === 'add' ? 'Add Meal' : 'Update Meal'
                                        )}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
                {/* End Add/Edit Meal Form */}


                {mealsLoading && (
                    <div className="d-flex justify-content-center mt-3">
                        <div className="spinner-border text-primary" role="status">
                            <span className="visually-hidden">Loading meals...</span>
                        </div>
                    </div>
                )}

                {mealsError && <div className="alert alert-danger">{mealsError}</div>}

                {!mealsLoading && !mealsError && (
                    <table className="table table-striped table-hover">
                        <thead className="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {meals.length > 0 ? (
                                meals.map((meal) => (
                                    <tr key={meal.id}>
                                        <td>{meal.name}</td>
                                        <td>{meal.category?.name || 'N/A'}</td>
                                        <td>
                                            {typeof meal.price === 'number'
                                                ? `$${meal.price.toFixed(2)}`
                                                : 'N/A' }
                                        </td>
                                         <td>
                                            {typeof meal.quantity === 'number'
                                                ? meal.quantity
                                                : 'N/A'}
                                        </td>
                                        <td>
                                            <button
                                                className="btn btn-sm btn-outline-secondary me-2"
                                                onClick={() => handleEditMealClick(meal)}
                                                title="Edit Meal"
                                            >
                                                <i className="fas fa-edit"></i>
                                            </button>
                                            <button
                                                className="btn btn-sm btn-outline-danger"
                                                onClick={() => handleDeleteMeal(meal.id)}
                                                title="Delete Meal"
                                            >
                                                 <i className="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                ))
                                ) : (
                                <tr>
                                    <td colSpan={5} className="text-center text-muted">No meals found. Add your first meal!</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                )}
            </div>
            {/* End Meal Management Section */}

        </div>
    );
};

export default RestaurantDashboardPage;
