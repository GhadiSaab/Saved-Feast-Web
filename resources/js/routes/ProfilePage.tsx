// resources/js/routes/ProfilePage.tsx
import React, { useState, useEffect, FormEvent, useMemo } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext'; // Use AuthContext instead of direct auth import
import { Order, ApiResponse as OrderApiResponse } from './OrdersPage'; // Correctly import named export OrderItem
import ProviderProfile from '../components/ProviderProfile'; // Reverted to standard import
import { Bar } from 'react-chartjs-2'; // Import Bar chart
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

// Register Chart.js components needed for Bar chart
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);


// Define a type for User data (adjust based on actual API response from /api/user)
interface UserProfile {
    id: number;
    name: string;
    email: string;
    roles: { name: string }[]; // Add roles to the user profile type
    // Add other relevant fields like phone_number, address, etc.
}

const ProfilePage: React.FC = () => {
    const { user, isAuthenticated, isLoading: authLoading } = useAuth(); // Use AuthContext
    const [profile, setProfile] = useState<UserProfile | null>(null);
    const [orders, setOrders] = useState<Order[]>([]); // State for orders
    const [loadingProfile, setLoadingProfile] = useState<boolean>(true);
    const [loadingOrders, setLoadingOrders] = useState<boolean>(true);
    const [isSaving, setIsSaving] = useState<boolean>(false); // State for save operation
    const [error, setError] = useState<string | null>(null); // Combined error state for simplicity
    const [isEditingInfo, setIsEditingInfo] = useState<boolean>(false); // State for editing basic info
    // Add state for editable fields if implementing edit functionality
    const [name, setName] = useState<string>('');
    const [email, setEmail] = useState<string>(''); // Usually email is not editable, but included for example

    // State for password change
    const [currentPassword, setCurrentPassword] = useState<string>('');
    const [newPassword, setNewPassword] = useState<string>('');
    const [confirmPassword, setConfirmPassword] = useState<string>('');
    const [isChangingPassword, setIsChangingPassword] = useState<boolean>(false);
    const [passwordChangeError, setPasswordChangeError] = useState<string | null>(null);
    const [passwordChangeSuccess, setPasswordChangeSuccess] = useState<string | null>(null);

    // Fetch Profile Data
    useEffect(() => {
        const fetchProfile = async () => {
            if (!isAuthenticated) {
                setError("Please log in to view your profile.");
                setLoadingProfile(false);
                setLoadingOrders(false); // Ensure orders loading also stops
                return;
            }

            if (!user) {
                setError("Failed to fetch profile data.");
                setLoadingProfile(false);
                setLoadingOrders(false);
                return;
            }

            setLoadingProfile(true);
            setError(null); // Reset error on new fetch attempt
            try {
                // Use the user data from AuthContext instead of making another API call
                const userProfile: UserProfile = {
                    id: user.id,
                    name: `${user.first_name} ${user.last_name}`,
                    email: user.email,
                    roles: user.roles || []
                };
                setProfile(userProfile);
                setName(userProfile.name);
                setEmail(userProfile.email);
            } catch (err: any) {
                setError('Failed to fetch profile. Please try again later.');
                console.error("Error fetching profile:", err);
            } finally {
                setLoadingProfile(false);
            }
        };

        // Only fetch profile when auth loading is complete and user is authenticated
        if (!authLoading) {
            fetchProfile();
        }
    }, [isAuthenticated, user, authLoading]);

    // Fetch Order Data (runs after profile is potentially loaded or failed)
    useEffect(() => {
        // Only fetch orders if authenticated and profile loading is finished
        if (!isAuthenticated || loadingProfile || authLoading) {
            // If not authenticated or profile still loading, don't fetch orders yet
            // If profile failed, error state will be set, so we might not need orders.
             if (!isAuthenticated && !loadingProfile && !authLoading) {
                 setLoadingOrders(false); // Ensure loading stops if not logged in
             }
            return;
        }

        const fetchOrders = async () => {
            setLoadingOrders(true);
            // Don't reset main error here, let profile error persist if it occurred
            try {
                const response = await axios.get<OrderApiResponse>('/api/orders', {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
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
    }, [loadingProfile, error, isAuthenticated, authLoading]); // Re-run when profile loading finishes or if an error occurred

    // --- Statistics Calculation ---
    const completedOrders = useMemo(() => orders.filter(order => order.status === 'completed'), [orders]);

    const totalFoodSaved = useMemo(() => {
        return completedOrders.reduce((total, order) => {
            return total + order.order_items.reduce((itemTotal, item) => itemTotal + item.quantity, 0);
        }, 0);
    }, [completedOrders]);

    const totalMoneySaved = useMemo(() => {
        return completedOrders.reduce((total, order) => {
            return total + order.order_items.reduce((itemTotal, item) => {
                const originalPrice = item.original_price ?? item.price; // Use original, fallback to price if null
                const savedPerItem = originalPrice - item.price;
                return itemTotal + (savedPerItem > 0 ? savedPerItem * item.quantity : 0);
            }, 0);
        }, 0);
    }, [completedOrders]);
    // --- End Statistics Calculation ---

    const handleEditInfoToggle = () => {
        if (isEditingInfo && profile) {
            // Reset fields if canceling edit
            setName(profile.name);
            setEmail(profile.email);
        }
        setIsEditingInfo(!isEditingInfo);
    };

    const handleInfoSave = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!profile) return;

        setIsSaving(true); // Use general saving state for info save
        setError(null);
        try {
            // Use the new POST route for updating profile info
            const response = await axios.post('/api/user/profile', { name, email /* other fields */ }, {
                 headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
            });
            
            // Update local profile state on success
            if (profile && response.data.user) {
                const updatedProfile: UserProfile = {
                    id: response.data.user.id,
                    name: `${response.data.user.first_name} ${response.data.user.last_name}`,
                    email: response.data.user.email,
                    roles: response.data.user.roles || []
                };
                setProfile(updatedProfile);
                setName(updatedProfile.name);
                setEmail(updatedProfile.email);
            }
            setIsEditingInfo(false); // Exit edit mode
            setError(null); // Clear general error on success

        } catch (err: any) {
             if (err.response && err.response.status === 422) {
                setError("Validation failed. Please check your input."); // Use general error state
             } else if (err.response && err.response.data && err.response.data.message) {
                 setError(err.response.data.message);
             } else {
                setError('Failed to update profile info. Please try again.');
             }
             console.error("Error updating profile info:", err);
        } finally {
            setIsSaving(false);
        }
    };

    const handlePasswordChange = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setPasswordChangeError(null);
        setPasswordChangeSuccess(null);

        if (newPassword !== confirmPassword) {
            setPasswordChangeError("New passwords do not match.");
            return;
        }
        if (!currentPassword || !newPassword) {
             setPasswordChangeError("All password fields are required.");
            return;
        }

        setIsChangingPassword(true);
        try {
            // TODO: Implement actual API call to change password (e.g., POST /api/user/change-password)
            await axios.post('/api/user/change-password', {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: confirmPassword,
            }, {
                 headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
            });

            setPasswordChangeSuccess("Password changed successfully!");
            // Clear fields on success
            setCurrentPassword('');
            setNewPassword('');
            setConfirmPassword('');

        } catch (err: any) {
            if (err.response && err.response.status === 422) {
                 // Handle validation errors specifically for password change
                 const messages = Object.values(err.response.data.errors).flat().join(' ');
                 setPasswordChangeError(`Password change failed: ${messages}`);
            } else if (err.response && err.response.data && err.response.data.message) {
                 setPasswordChangeError(err.response.data.message);
            } else {
                setPasswordChangeError('Failed to change password. Please try again.');
            }
            console.error("Error changing password:", err);
        } finally {
            setIsChangingPassword(false);
        }
    };


    // Show loading while auth is being checked
    if (authLoading) {
        return (
            <div className="d-flex justify-content-center mt-5">
                <div className="spinner-border" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    // Redirect to login if not authenticated
    if (!isAuthenticated) {
        return (
            <div className="container mt-5">
                <div className="row justify-content-center">
                    <div className="col-md-6 text-center">
                        <h2>Authentication Required</h2>
                        <p className="text-muted mb-4">Please log in or sign up to view your profile.</p>
                        <div className="d-grid gap-2 d-md-block">
                            <a href="/login" className="btn btn-primary me-md-2">Login</a>
                            <a href="/signup" className="btn btn-outline-primary">Sign Up</a>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

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

    // Check if the user is a provider
    const isProvider = profile.roles?.some(role => role.name === 'provider');

    if (isProvider) {
        // Render the ProviderProfile component if the user is a provider
        return <ProviderProfile />;
    }

    // Otherwise, render the standard user profile
    return (
        <div>
            <h1>My Profile</h1>

            {/* General Error Display */}
            {error && <div className="alert alert-danger">{error}</div>}

            <div className="row">
                {/* Column 1: Profile Info & Password Change */}
                <div className="col-md-6">
                    {/* Profile Info Card */}
                    <div className="card mb-4">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            Profile Information
                            <button className={`btn btn-sm ${isEditingInfo ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={handleEditInfoToggle} disabled={isSaving}>
                                {isEditingInfo ? 'Cancel' : 'Edit Info'}
                            </button>
                        </div>
                        <div className="card-body">
                            {isEditingInfo ? (
                                <form onSubmit={handleInfoSave}>
                                    <div className="mb-3">
                                        <label htmlFor="profileName" className="form-label">Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            id="profileName"
                                            value={name}
                                            onChange={(e) => setName(e.target.value)}
                                            required
                                            disabled={isSaving}
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
                                            disabled={isSaving} // Consider if email should be editable
                                        />
                                    </div>
                                    {/* Add other editable fields here if needed */}
                                    <button type="submit" className="btn btn-primary" disabled={isSaving}>
                                        {isSaving ? 'Saving...' : 'Save Info'}
                                    </button>
                                </form>
                            ) : (
                                <>
                                    <h5 className="card-title">{profile.name}</h5>
                                    <p className="card-text"><strong>Email:</strong> {profile.email}</p>
                                    {/* Display other non-editable profile fields here */}
                                </>
                            )}
                        </div>
                    </div>

                    {/* Change Password Card */}
                    <div className="card">
                        <div className="card-header">Change Password</div>
                        <div className="card-body">
                            <form onSubmit={handlePasswordChange}>
                                <div className="mb-3">
                                    <label htmlFor="currentPassword" >Current Password</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        id="currentPassword"
                                        value={currentPassword}
                                        onChange={(e) => setCurrentPassword(e.target.value)}
                                        required
                                        disabled={isChangingPassword}
                                    />
                                </div>
                                <div className="mb-3">
                                    <label htmlFor="newPassword" >New Password</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        id="newPassword"
                                        value={newPassword}
                                        onChange={(e) => setNewPassword(e.target.value)}
                                        required
                                        disabled={isChangingPassword}
                                    />
                                </div>
                                <div className="mb-3">
                                    <label htmlFor="confirmPassword" >Confirm New Password</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        id="confirmPassword"
                                        value={confirmPassword}
                                        onChange={(e) => setConfirmPassword(e.target.value)}
                                        required
                                        disabled={isChangingPassword}
                                    />
                                </div>
                                {passwordChangeError && <div className="alert alert-danger">{passwordChangeError}</div>}
                                {passwordChangeSuccess && <div className="alert alert-success">{passwordChangeSuccess}</div>}
                                <button type="submit" className="btn btn-primary" disabled={isChangingPassword}>
                                    {isChangingPassword ? 'Changing...' : 'Change Password'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                 {/* Column 2: Statistics & Graphs */}
                 <div className="col-md-6">
                    <div className="card">
                        <div className="card-header">Your Impact</div>
                        <div className="card-body">
                             <div className="row text-center mb-3">
                                <div className="col-6">
                                    <h5>{totalFoodSaved}</h5>
                                    <p className="text-muted">Food Items Saved</p>
                                </div>
                                <div className="col-6">
                                    <h5>€{totalMoneySaved.toFixed(2)}</h5>
                                    <p className="text-muted">Money Saved</p>
                                </div>
                            </div>
                            <hr/>
                            <h6>Visualizations</h6>
                             {/* Add Charts Here */}
                             {completedOrders.length > 0 ? (
                                <Bar
                                    options={{
                                        responsive: true,
                                        plugins: { legend: { display: false }, title: { display: true, text: 'Savings Overview' } },
                                        scales: { y: { beginAtZero: true } }
                                    }}
                                    data={{
                                        labels: ['Food Items Saved', 'Money Saved (€)'],
                                        datasets: [{
                                            label: 'Value',
                                            data: [totalFoodSaved, totalMoneySaved],
                                            backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(54, 162, 235, 0.6)'],
                                            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(54, 162, 235, 1)'],
                                            borderWidth: 1,
                                        }]
                                    }}
                                />
                             ) : (
                                <p className="text-center text-muted">No completed orders to display statistics.</p>
                             )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProfilePage;
