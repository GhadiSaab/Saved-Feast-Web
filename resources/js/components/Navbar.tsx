// resources/js/components/Navbar.tsx
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import auth from '../auth'; // Import the auth helper

const Navbar: React.FC = () => {
    // Use state to track authentication status, forcing re-render on change
    const [isAuthenticated, setIsAuthenticated] = useState<boolean>(auth.isAuthenticated());
    const navigate = useNavigate();

    // Effect to update auth status if it changes elsewhere (e.g., after login/logout)
    // A simple way is to listen for storage events or custom events.
    useEffect(() => {
        const checkAuth = () => {
            const currentAuthStatus = auth.isAuthenticated();
            if (currentAuthStatus !== isAuthenticated) {
                setIsAuthenticated(currentAuthStatus);
            }
        };

        // Check on mount and listen for storage changes which might indicate login/logout in another tab
        checkAuth();
        window.addEventListener('storage', checkAuth);
        // Also listen for custom events dispatched after login/logout within the app
        window.addEventListener('authChange', checkAuth);

        return () => {
            window.removeEventListener('storage', checkAuth);
            window.removeEventListener('authChange', checkAuth); // Cleanup listener
        };
    }, [isAuthenticated]); // Re-run effect if isAuthenticated state changes

    const handleLogout = async () => {
        try {
            await auth.logout();
            setIsAuthenticated(false); // Update local state immediately
            window.dispatchEvent(new Event('authChange')); // Dispatch event for other components
            navigate('/login'); // Redirect to login page
        } catch (error) {
            console.error("Logout failed:", error);
            // Optionally show an error message to the user
        }
    };

    return (
        <nav className="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm">
            <div className="container">
                <Link className="navbar-brand" to="/">SavedFeast</Link>
                <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span className="navbar-toggler-icon"></span>
                </button>
                <div className="collapse navbar-collapse" id="navbarNav">
                    <ul className="navbar-nav ms-auto align-items-center"> {/* align-items-center for vertical alignment */}
                        <li className="nav-item">
                            <Link className="nav-link" to="/">Feed</Link>
                        </li>
                        {isAuthenticated ? (
                            <>
                                <li className="nav-item">
                                    <Link className="nav-link" to="/orders">My Orders</Link>
                                </li>
                                <li className="nav-item">
                                    <Link className="nav-link" to="/profile">Profile</Link>
                                </li>
                                <li className="nav-item">
                                    {/* Use a button for actions like logout */}
                                    <button className="nav-link btn btn-link" style={{ textDecoration: 'none' }} onClick={handleLogout}>Logout</button>
                                </li>
                            </>
                        ) : (
                            <>
                                <li className="nav-item">
                                    <Link className="nav-link" to="/login">Login</Link>
                                </li>
                                <li className="nav-item">
                                    <Link className="nav-link" to="/signup">Sign Up</Link>
                                </li>
                            </>
                        )}
                         <li className="nav-item ms-lg-2"> {/* Add some margin for the cart */}
                            {/* Cart link/button - implement later */}
                            <Link className="btn btn-outline-primary btn-sm" to="/checkout">
                                Cart <span className="badge bg-primary ms-1">0</span> {/* Placeholder count */}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    );
};

export default Navbar;
