// resources/js/routes/ProfilePage.tsx
import React, { useState, useEffect, FormEvent } from 'react';
import axios from 'axios';
import auth from '../auth'; // Assuming auth helper is needed
import { Order, ApiResponse as OrderApiResponse } from './OrdersPage'; // Import types from OrdersPage

// Define a type for User data (adjust based on actual API response from /api/user)
interface UserProfile {
    id: number;
    name: string;
    email: string;
    // Add other relevant fields like phone_number, address, etc.
}

const ProfilePage: React.FC = () => {
    const [profile, setProfile] = useState<UserProfile | null>(null);
    const [orders, setOrders] = useState<Order[]>([]); // State for orders
    const [loadingProfile, setLoadingProfile] = useState<boolean>(true);
    const [loadingOrders, setLoadingOrders] = useState<boolean>(true);
    const [isSaving, setIsSaving] = useState<boolean>(false); // State for save operation
    const [error, setError] = useState<string | null>(null); // Combined error state for simplicity
    const [isEditing, setIsEditing] = useState<boolean>(false);
    // Add state for editable fields if implementing edit functionality
    const [name, setName] = useState<string>('');
    const [email, setEmail] = useState<string>(''); // Usually email is not editable, but included for example

    // Fetch Profile Data
    useEffect(() => {
        const fetchProfile = async () => {
            if (!auth.isAuthenticated()) {
                setError("Please log in to view your profile.");
                setLoadingProfile(false);
                setLoadingOrders(false); // Ensure orders loading also stops
                return;
            }

            setLoadingProfile(true);
            setError(null); // Reset error on new fetch attempt
            try {
                const user = await auth.getUser();
                if (user) {
                    setProfile(user);
                    setName(user.name);
                    setEmail(user.email);
                } else {
                    setError("Failed to fetch profile data.");
                }
            } catch (err: any) {
                setError('Failed to fetch profile. Please try again later.');
                console.error("Error fetching profile:", err);
            } finally {
                setLoadingProfile(false);
            }
        };

        fetchProfile();
    }, []);

    // Fetch Order Data (runs after profile is potentially loaded or failed)
    useEffect(() => {
        // Only fetch orders if authenticated and profile loading is finished
        if (!auth.isAuthenticated() || loadingProfile) {
            // If not authenticated or profile still loading, don't fetch orders yet
            // If profile failed, error state will be set, so we might not need orders.
             if (!auth.isAuthenticated() && !loadingProfile) {
                 setLoadingOrders(false); // Ensure loading stops if not logged in
             }
            return;
        }

        const fetchOrders = async () => {
            setLoadingOrders(true);
            // Don't reset main error here, let profile error persist if it occurred
            try {
                const response = await axios.get<OrderApiResponse>('/api/orders', {
                    headers: { Authorization: `Bearer ${auth.getToken()}` }
                });
                if (response.data.status && response.data.data) {
                    setOrders(response.data.data);
                } else {
                    // Set error only if profile fetch succeeded but orders failed
                    if (!error) setError(response.data.message || 'Failed to fetch order history.');
                }
            } catch (err: any) {
                 // Set error only if profile fetch succeeded but orders failed
                if (!error) setError('An error occurred while fetching order history.');
                console.error("Error fetching orders:", err);
            } finally {
                setLoadingOrders(false);
            }
        };

        fetchOrders();
    }, [loadingProfile, error]); // Re-run when profile loading finishes or if an error occurred

    // Calculate total items ordered
    const totalItemsOrdered = React.useMemo(() => {
        return orders.reduce((total, order) => {
            return total + order.order_items.reduce((itemTotal, item) => itemTotal + item.quantity, 0);
        }, 0);
    }, [orders]); // Recalculate only when orders change

    const handleEditToggle = () => {
        if (isEditing && profile) {
            // Reset fields if canceling edit
            setName(profile.name);
            setEmail(profile.email);
        }
        setIsEditing(!isEditing);
    };

    const handleSave = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!profile) return;

        setIsSaving(true); // Start saving state
        setError(null); // Clear previous save errors
        // const currentLoading = loadingProfile || loadingOrders; // No longer needed here
        try {
            // TODO: Define and implement the actual API endpoint for updating user profile
            // Example: await axios.put(`/api/user/${profile.id}`, { name, email /* other fields */ });

            await new Promise(resolve => setTimeout(resolve, 500)); // Simulate network delay
            console.log("Profile update simulated for:", { name, email });

            // Update local profile state on success
            setProfile({ ...profile, name, email });
            setIsEditing(false); // Exit edit mode

        } catch (err: any) {
             if (err.response && err.response.status === 422) {
                // Handle validation errors if backend provides them
                setError("Validation failed. Please check your input.");
                // Optionally set specific validation errors state
            } else {
                setError('Failed to update profile. Please try again.');
                console.error("Error updating profile:", err);
            }
        } finally {
            setIsSaving(false); // End saving state regardless of success/failure
        }
    };

    // Combined loading state
    const isLoading = loadingProfile || loadingOrders;

    if (isLoading) {
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

    if (!profile) {
         return <div className="alert alert-warning">Could not load profile data.</div>;
    }

    return (
        <div>
            <h1>My Profile</h1>
            <div className="card">
                <div className="card-body">
                    {isEditing ? (
                        <form onSubmit={handleSave}>
                            <div className="mb-3">
                                <label htmlFor="profileName" className="form-label">Name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="profileName"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    required
                                    disabled={isSaving} // Disable during save
                                />
                            </div>
                            <div className="mb-3">
                                <label htmlFor="profileEmail" className="form-label">Email address</label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="profileEmail"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    disabled={isSaving} // Disable during save (or disable email editing entirely)
                                />
                            </div>
                            {/* Add other editable fields here */}
                            <button type="submit" className="btn btn-primary me-2" disabled={isSaving}>
                                {isSaving ? 'Saving...' : 'Save Changes'}
                            </button>
                            <button type="button" className="btn btn-secondary" onClick={handleEditToggle} disabled={isSaving}>
                                Cancel
                            </button>
                             {error && <div className="alert alert-danger mt-3">{error}</div>}
                        </form>
                    ) : (
                        <>
                            <h5 className="card-title">{profile.name}</h5>
                            <p className="card-text"><strong>Email:</strong> {profile.email}</p>
                            {/* Display other profile fields here */}

                            {/* Statistics Section */}
                            <div className="mt-4 pt-3 border-top">
                                <h5>Your Impact</h5>
                                <p>Meals Saved: <strong>{totalItemsOrdered}</strong></p>
                                <p className="text-muted small">
                                    Money Saved: (Calculation coming soon!)<br />
                                    Economic Impact: (Calculation coming soon!)
                                </p>
                            </div>

                            <button className="btn btn-outline-secondary mt-3" onClick={handleEditToggle}>
                                Edit Profile
                            </button>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ProfilePage;
