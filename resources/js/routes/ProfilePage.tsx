// resources/js/routes/ProfilePage.tsx
import React, { useState, useEffect, FormEvent } from 'react';
import axios from 'axios';
import auth from '../auth'; // Assuming auth helper is needed

// Define a type for User data (adjust based on actual API response from /api/user)
interface UserProfile {
    id: number;
    name: string;
    email: string;
    // Add other relevant fields like phone_number, address, etc.
}

const ProfilePage: React.FC = () => {
    const [profile, setProfile] = useState<UserProfile | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [isEditing, setIsEditing] = useState<boolean>(false);
    // Add state for editable fields if implementing edit functionality
    const [name, setName] = useState<string>('');
    const [email, setEmail] = useState<string>(''); // Usually email is not editable, but included for example

    useEffect(() => {
        const fetchProfile = async () => {
            if (!auth.isAuthenticated()) {
                setError("Please log in to view your profile.");
                setLoading(false);
                return;
            }

            setLoading(true);
            setError(null);
            try {
                // Use the getUser function from auth helper
                const user = await auth.getUser();
                if (user) {
                    setProfile(user);
                    // Initialize editable fields
                    setName(user.name);
                    setEmail(user.email);
                } else {
                    setError("Failed to fetch profile data.");
                }
            } catch (err: any) {
                setError('Failed to fetch profile. Please try again later.');
                console.error("Error fetching profile:", err);
            } finally {
                setLoading(false);
            }
        };

        fetchProfile();
    }, []);

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

        setLoading(true);
        setError(null);
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
            setLoading(false);
        }
    };


    if (loading) {
        return (
            <div className="d-flex justify-content-center">
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
                                    disabled={loading}
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
                                    disabled={loading} // Or disable email editing entirely
                                />
                            </div>
                            {/* Add other editable fields here */}
                            <button type="submit" className="btn btn-primary me-2" disabled={loading}>
                                {loading ? 'Saving...' : 'Save Changes'}
                            </button>
                            <button type="button" className="btn btn-secondary" onClick={handleEditToggle} disabled={loading}>
                                Cancel
                            </button>
                             {error && <div className="alert alert-danger mt-3">{error}</div>}
                        </form>
                    ) : (
                        <>
                            <h5 className="card-title">{profile.name}</h5>
                            <p className="card-text"><strong>Email:</strong> {profile.email}</p>
                            {/* Display other profile fields here */}
                            <button className="btn btn-outline-secondary" onClick={handleEditToggle}>
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
