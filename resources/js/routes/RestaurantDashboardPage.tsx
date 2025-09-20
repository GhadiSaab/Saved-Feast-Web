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
  title: string; // Changed from name to match backend
  description: string;
  current_price: number; // Changed from price
  original_price?: number | null; // Added original_price
  quantity: number;
  category_id: number;
  image?: string | null; // Changed from image_url, matches MealCard
  available_from: string; // Add available_from (ISO string from backend)
  available_until: string; // Add available_until (ISO string from backend)
  restaurant_id: number;
  created_at: string;
  updated_at: string;
  category?: Category; // Optional eager loaded category from backend
}

// Interface for the form data - use strings for input fields
interface MealFormData {
  id?: number | null; // Present when editing
  title: string; // Changed from name
  description: string;
  current_price: string; // Changed from price
  original_price: string; // Added original_price (use string for input)
  quantity: string;
  category_id: string; // Use string for form select, convert on submit
  available_from: string; // Add available_from (string for datetime-local input)
  available_until: string; // Add available_until (string for datetime-local input)
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
    title: '', // Changed from name
    description: '',
    current_price: '', // Changed from price
    original_price: '', // Initialize original_price
    quantity: '',
    category_id: '',
    available_from: '', // Initialize available_from
    available_until: '', // Initialize available_until
  });
  const [formSubmitting, setFormSubmitting] = useState<boolean>(false); // Loading state for form submission
  const [formError, setFormError] = useState<string | null>(null); // Errors specific to the form submission
  const [imageFile, setImageFile] = useState<File | null>(null); // State for the image file

  // --- Placeholder functions for CRUD operations ---
  const fetchMealsAndCategories = async () => {
    setMealsLoading(true);
    setMealsError(null);
    const token = auth.getToken();
    const headers = { Authorization: `Bearer ${token}` };

    try {
      // Fetch meals and categories concurrently
      // Categories endpoint is public, so no auth headers needed
      const [mealsRes, categoriesRes] = await Promise.all([
        axios.get('/api/provider/meals', { headers }),
        axios.get('/api/categories'), // No auth headers needed for public endpoint
      ]);

      setMeals(mealsRes.data || []);
      setCategories(categoriesRes.data || []); // Ensure categories are fetched

      // Debug logging
      console.log('Categories loaded:', categoriesRes.data);
      console.log('Categories count:', categoriesRes.data?.length || 0);
    } catch (err: any) {
      console.error('Error fetching meals/categories:', err);
      if (
        err.response &&
        (err.response.status === 401 || err.response.status === 403)
      ) {
        setMealsError('Authorization error fetching meals/categories.');
      } else {
        // More specific error message
        const errorMsg =
          err.response?.data?.message ||
          err.message ||
          'Failed to load meals or categories.';
        setMealsError(`Error: ${errorMsg}`);
      }
    } finally {
      setMealsLoading(false);
    }
  };

  const handleAddMealClick = () => {
    setFormMode('add');
    setFormData({
      // Reset form
      title: '', // Changed from name
      description: '',
      current_price: '', // Changed from price
      original_price: '', // Reset original_price
      quantity: '',
      category_id: categories[0]?.id.toString() || '', // Default to first category if available
      available_from: '', // Reset available_from
      available_until: '', // Reset available_until
    });
    setImageFile(null); // Clear selected file
    setFormError(null);
    setIsFormVisible(true);
    // If using a modal, trigger modal show here
  };

  const handleEditMealClick = (meal: Meal) => {
    setFormMode('edit');
    setFormData({
      // Pre-fill form
      id: meal.id,
      title: meal.title, // Changed from name
      description: meal.description,
      current_price: String(meal.current_price), // Changed from price
      original_price: meal.original_price ? String(meal.original_price) : '', // Handle null/undefined
      quantity: String(meal.quantity),
      category_id: String(meal.category_id),
      // Format ISO string from backend (e.g., 2023-10-27T10:00:00.000000Z) to datetime-local format (YYYY-MM-DDTHH:mm)
      available_from: meal.available_from
        ? meal.available_from.slice(0, 16)
        : '',
      available_until: meal.available_until
        ? meal.available_until.slice(0, 16)
        : '',
    });
    setImageFile(null); // Clear previous file selection on edit
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
      setMealsError('Authentication error. Please log in again.'); // Use mealsError or a dedicated delete error state
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
      let errorMsg = 'Failed to delete meal.';
      if (err.response) {
        if (err.response.status === 401 || err.response.status === 403) {
          errorMsg =
            'Authorization error. You might not have permission to delete this meal.';
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
      setFormError('Authentication error. Please log in again.');
      setFormSubmitting(false);
      return;
    }
    // Use FormData for file uploads
    const apiFormData = new FormData();
    apiFormData.append('title', formData.title); // Changed from name
    apiFormData.append('description', formData.description);
    apiFormData.append('current_price', formData.current_price); // Changed from price
    // Only append original_price if it's not empty, let backend handle null/default
    if (formData.original_price && formData.original_price.trim() !== '') {
      apiFormData.append('original_price', formData.original_price);
    }
    apiFormData.append('quantity', formData.quantity);
    apiFormData.append('category_id', formData.category_id);
    apiFormData.append('available_from', formData.available_from); // Add available_from
    apiFormData.append('available_until', formData.available_until); // Add available_until

    if (imageFile) {
      apiFormData.append('image', imageFile); // 'image' is the key the backend will expect
    }

    // Basic frontend validation (optional, backend validation is primary)
    // Validate current_price is a number. original_price can be empty or a number.
    if (
      isNaN(parseFloat(formData.current_price)) ||
      (formData.original_price.trim() !== '' &&
        isNaN(parseFloat(formData.original_price))) ||
      isNaN(parseInt(formData.quantity, 10)) ||
      isNaN(parseInt(formData.category_id, 10))
    ) {
      setFormError(
        'Invalid current price, original price (if provided), quantity, or category. Please enter valid numbers.'
      );
      setFormSubmitting(false);
      return;
    }
    // Optional: Add validation rule: original_price >= current_price if original_price is set
    if (
      formData.original_price.trim() !== '' &&
      parseFloat(formData.original_price) < parseFloat(formData.current_price)
    ) {
      setFormError(
        'Original price cannot be less than the current selling price.'
      );
      setFormSubmitting(false);
      return;
    }

    // Axios headers for FormData are usually set automatically, but ensure Authorization is included
    const headers = {
      Authorization: `Bearer ${token}`,
      // 'Content-Type': 'multipart/form-data' // Axios sets this automatically for FormData
    };

    try {
      if (formMode === 'add') {
        // POST request to create a new meal
        await axios.post('/api/provider/meals', apiFormData, { headers });
      } else {
        // PUT request to update an existing meal - NOTE: PUT doesn't always work well with FormData.
        // Laravel handles this using a _method field.
        if (!formData.id) {
          throw new Error('Meal ID is missing for update.');
        }
        apiFormData.append('_method', 'PUT'); // Spoof PUT method for Laravel
        await axios.post(`/api/provider/meals/${formData.id}`, apiFormData, {
          headers,
        }); // Use POST with _method
      }

      // Success
      setImageFile(null); // Clear file after successful upload
      closeForm(); // Close the form
      await fetchMealsAndCategories(); // Refresh the meals list
    } catch (err: any) {
      console.error(
        `Error ${formMode === 'add' ? 'adding' : 'updating'} meal:`,
        err
      );
      if (err.response) {
        if (err.response.status === 422) {
          // Handle validation errors
          const errors = err.response.data.errors;
          const errorMessages = Object.values(errors).flat().join(' '); // Combine validation messages
          setFormError(`Validation failed: ${errorMessages}`);
        } else if (err.response.status === 401 || err.response.status === 403) {
          setFormError('Authorization error. You might not have permission.');
        } else {
          setFormError(
            `An error occurred: ${err.response.data.message || 'Please try again.'}`
          );
        }
      } else {
        setFormError(
          'An unexpected error occurred. Please check your connection and try again.'
        );
      }
    } finally {
      setFormSubmitting(false);
    }
  };

  const handleFormChange = (
    event: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
    const { name, value, type } = event.target;

    if (type === 'file') {
      const input = event.target as HTMLInputElement;
      if (input.files && input.files.length > 0) {
        setImageFile(input.files[0]); // Store the selected file object
      } else {
        setImageFile(null);
      }
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }
  };

  const closeForm = () => {
    setIsFormVisible(false);
    setFormError(null); // Clear errors when closing
    // If using a modal, trigger modal hide here
  };
  // --- End Placeholder functions ---

  useEffect(() => {
    const fetchDashboardData = async () => {
      if (!auth.isAuthenticated()) {
        setError('Please log in as a provider.');
        setLoading(false);
        setMealsLoading(false); // Also stop meals loading
        return;
      }

      setLoading(true);
      setError(null);
      try {
        // Fetch initial dashboard data
        const response = await axios.get('/api/provider/dashboard-data', {
          headers: { Authorization: `Bearer ${auth.getToken()}` },
        });
        setDashboardMessage(response.data.message || 'Welcome!');

        // If dashboard data fetch is successful, fetch meals and categories
        await fetchMealsAndCategories(); // Call the function to load meals/cats
      } catch (err: any) {
        console.error('Error fetching dashboard data:', err);
        if (
          err.response &&
          (err.response.status === 401 || err.response.status === 403)
        ) {
          setError('You are not authorized to view this page.');
        } else {
          setError('Failed to load dashboard data.');
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
    <div className="container-fluid py-4">
      <div className="row">
        <div className="col-12">
          {/* Header Section */}
          <div className="bg-gradient-primary text-white rounded-3 p-4 mb-4 shadow">
            <div className="row align-items-center">
              <div className="col-md-8">
                <h1 className="h2 mb-2 fw-bold">
                  <i className="fas fa-store me-3"></i>
                  Restaurant Dashboard
                </h1>
                <p className="mb-0 opacity-75">{dashboardMessage}</p>
              </div>
              <div className="col-md-4 text-md-end">
                <button
                  className="btn btn-light btn-lg"
                  onClick={handleAddMealClick}
                >
                  <i className="fas fa-plus me-2"></i>
                  Add New Meal
                </button>
              </div>
            </div>
          </div>

          {/* Error State */}
          {error && (
            <div
              className="alert alert-danger border-0 shadow-sm mb-4"
              role="alert"
            >
              <div className="d-flex align-items-center">
                <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                  <h6 className="alert-heading mb-1">
                    Error Loading Dashboard
                  </h6>
                  <p className="mb-0">{error}</p>
                </div>
              </div>
            </div>
          )}

          {/* Meal Management Section */}
          <div className="card border-0 shadow-sm mb-4">
            <div className="card-header bg-transparent border-0">
              <h5 className="mb-0 fw-bold">
                <i className="fas fa-utensils me-2 text-primary"></i>
                Manage Your Meals
              </h5>
            </div>

            {/* Add/Edit Meal Form (Conditionally Rendered) */}
            {isFormVisible && (
              <div className="card border-0 shadow-sm mb-4">
                <div className="card-header bg-transparent border-0">
                  <h5 className="mb-0 fw-bold">
                    <i className="fas fa-edit me-2 text-primary"></i>
                    {formMode === 'add' ? 'Add New Meal' : 'Edit Meal'}
                  </h5>
                </div>
                <div className="card-body">
                  {formError && (
                    <div className="alert alert-danger">{formError}</div>
                  )}
                  <form onSubmit={handleFormSubmit}>
                    <div className="mb-3">
                      <label htmlFor="title" className="form-label">
                        Meal Title
                      </label>{' '}
                      {/* Changed label and htmlFor */}
                      <input
                        type="text"
                        className="form-control"
                        id="title" // Changed id
                        name="title" // Changed name
                        value={formData.title} // Changed value binding
                        onChange={handleFormChange}
                        required
                        disabled={formSubmitting}
                      />
                    </div>
                    <div className="mb-3">
                      <label htmlFor="description" className="form-label">
                        Description
                      </label>
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
                      {/* Original Price (Optional) */}
                      <div className="col-md-4">
                        <label htmlFor="original_price" className="form-label">
                          Original Price (€){' '}
                          <small className="text-muted">(Optional)</small>
                        </label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          className="form-control"
                          id="original_price"
                          name="original_price"
                          value={formData.original_price}
                          onChange={handleFormChange}
                          placeholder="e.g., 15.00"
                          disabled={formSubmitting}
                        />
                      </div>
                      {/* Current Selling Price */}
                      <div className="col-md-4">
                        <label htmlFor="current_price" className="form-label">
                          Current Selling Price (€)
                        </label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          className="form-control"
                          id="current_price"
                          name="current_price"
                          value={formData.current_price}
                          onChange={handleFormChange}
                          required
                          placeholder="e.g., 9.99"
                          disabled={formSubmitting}
                        />
                      </div>
                      {/* Quantity */}
                      <div className="col-md-4">
                        <label htmlFor="quantity" className="form-label">
                          Quantity Available
                        </label>
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
                        <label htmlFor="category_id" className="form-label">
                          Category
                        </label>
                        <select
                          className="form-select"
                          id="category_id"
                          name="category_id"
                          value={formData.category_id}
                          onChange={handleFormChange}
                          required
                          disabled={formSubmitting || categories.length === 0}
                        >
                          <option value="" disabled>
                            Select a category
                          </option>
                          {categories.map(cat => (
                            <option key={cat.id} value={cat.id}>
                              {cat.name}
                            </option>
                          ))}
                        </select>
                        {categories.length === 0 && !mealsLoading && (
                          <div className="form-text text-warning">
                            No categories found. Please add categories first.
                          </div>
                        )}
                        {/* Debug info */}
                        {process.env.NODE_ENV === 'development' && (
                          <div className="form-text text-info">
                            Debug: Categories loaded: {categories.length} |
                            Loading: {mealsLoading ? 'Yes' : 'No'}
                          </div>
                        )}
                      </div>
                      {/* Removed category column div end */}
                    </div>{' '}
                    {/* End row mb-3 for category */}
                    {/* Pickup Time Window */}
                    <div className="row mb-3">
                      <div className="col-md-6">
                        <label htmlFor="available_from" className="form-label">
                          Available From (Pickup Start)
                        </label>
                        <input
                          type="datetime-local"
                          className="form-control"
                          id="available_from"
                          name="available_from"
                          value={formData.available_from}
                          onChange={handleFormChange}
                          required
                          disabled={formSubmitting}
                        />
                      </div>
                      <div className="col-md-6">
                        <label htmlFor="available_until" className="form-label">
                          Available Until (Pickup End)
                        </label>
                        <input
                          type="datetime-local"
                          className="form-control"
                          id="available_until"
                          name="available_until"
                          value={formData.available_until}
                          onChange={handleFormChange}
                          required
                          disabled={formSubmitting}
                        />
                      </div>
                    </div>
                    {/* End Pickup Time Window */}
                    <div className="mb-3">
                      <label htmlFor="image" className="form-label">
                        Meal Image (Optional)
                      </label>
                      <input
                        type="file"
                        className="form-control"
                        id="image"
                        name="image" // Name matches the key used in FormData
                        accept="image/png, image/jpeg, image/gif" // Accept common image types
                        onChange={handleFormChange} // Use the updated handler
                        disabled={formSubmitting}
                      />
                      {imageFile && (
                        <div className="mt-2 text-muted">
                          Selected: {imageFile.name}
                        </div>
                      )}
                      {/* Display existing image preview if editing and image exists? Needs image_url from meal data */}
                      {formMode === 'edit' &&
                        formData.id /* && mealBeingEdited?.image_url */ && (
                          <div className="mt-2">
                            {/* Placeholder for showing current image - requires fetching meal data with image_url */}
                            {/* <img src={mealBeingEdited.image_url} alt="Current meal image" style={{ maxWidth: '100px', maxHeight: '100px' }} /> */}
                            <small className="d-block text-muted">
                              Uploading a new image will replace the current
                              one.
                            </small>
                          </div>
                        )}
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
                            <span
                              className="spinner-border spinner-border-sm me-2"
                              role="status"
                              aria-hidden="true"
                            ></span>
                            {formMode === 'add' ? 'Adding...' : 'Updating...'}
                          </>
                        ) : formMode === 'add' ? (
                          'Add Meal'
                        ) : (
                          'Update Meal'
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

            {mealsError && (
              <div
                className="alert alert-danger border-0 shadow-sm mb-4"
                role="alert"
              >
                <div className="d-flex align-items-center">
                  <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                  <div>
                    <h6 className="alert-heading mb-1">Error Loading Meals</h6>
                    <p className="mb-0">{mealsError}</p>
                  </div>
                </div>
              </div>
            )}

            {!mealsLoading && !mealsError && (
              <div className="card-body">
                <div className="table-responsive">
                  <table className="table table-hover">
                    <thead className="table-light">
                      <tr>
                        <th>
                          <i className="fas fa-utensils me-2 text-primary"></i>
                          Title
                        </th>
                        <th>
                          <i className="fas fa-tag me-2 text-primary"></i>
                          Category
                        </th>
                        <th>
                          <i className="fas fa-euro-sign me-2 text-primary"></i>
                          Price
                        </th>
                        <th>
                          <i className="fas fa-box me-2 text-primary"></i>
                          Quantity
                        </th>
                        <th>
                          <i className="fas fa-cogs me-2 text-primary"></i>
                          Actions
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {meals.length > 0 ? (
                        meals.map(meal => (
                          <tr key={meal.id}>
                            <td>{meal.title}</td>
                            <td>{meal.category?.name || 'N/A'}</td>
                            <td>
                              {meal.original_price &&
                              meal.original_price > meal.current_price ? (
                                <>
                                  <strong className="text-danger me-1">
                                    €{meal.current_price.toFixed(2)}
                                  </strong>
                                  <small className="text-muted">
                                    <del>€{meal.original_price.toFixed(2)}</del>
                                  </small>
                                </>
                              ) : (
                                <strong>
                                  €{meal.current_price.toFixed(2)}
                                </strong>
                              )}
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
                          <td colSpan={5} className="text-center text-muted">
                            No meals found. Add your first meal!
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default RestaurantDashboardPage;
