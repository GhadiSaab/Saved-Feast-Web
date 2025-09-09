// resources/js/components/Navbar.tsx
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import auth from '../auth'; // Import the auth helper
import { useCart } from '../context/CartContext'; // Import useCart

// Define User type based on backend response (including roles)
// Ensure these match the actual structure returned by your API (especially 'name' vs 'first_name')
interface Role {
  id: number;
  name: string;
}

interface User {
  id: number;
  name?: string; // Assuming 'name' is available, adjust if using first_name/last_name
  first_name?: string;
  last_name?: string;
  email: string;
  roles?: Role[]; // Roles array from the backend
}

const Navbar: React.FC = () => {
  // Use state to track authentication status, forcing re-render on change
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(
    auth.isAuthenticated()
  );
  const [currentUser, setCurrentUser] = useState<User | null>(null); // State for user object
  const { getItemCount } = useCart(); // Get cart item count
  const navigate = useNavigate();

  // Effect to update auth status and fetch user data
  useEffect(() => {
    const handleAuthChange = async () => {
      const currentAuthStatus = auth.isAuthenticated();
      setIsAuthenticated(currentAuthStatus); // Update auth state

      if (currentAuthStatus) {
        // Fetch user only if authenticated and not already loaded
        // Avoid fetching if currentUser already exists unless forced refresh is needed
        if (!currentUser) {
          try {
            const user = await auth.getUser();
            setCurrentUser(user);
          } catch (error) {
            console.error('Failed to fetch user in Navbar:', error);
            setCurrentUser(null); // Clear user on error
            // Consider removing token if error indicates invalid token
            // auth.removeToken();
            // setIsAuthenticated(false); // Update state if token removed
          }
        }
      } else {
        setCurrentUser(null); // Clear user if not authenticated
      }
    };

    handleAuthChange(); // Initial check on mount

    // Listen for changes that might affect auth status
    // These listeners will call handleAuthChange, which updates state
    // and fetches user *only if needed* based on the logic above.
    window.addEventListener('storage', handleAuthChange);
    window.addEventListener('authChange', handleAuthChange); // This event is dispatched on login/register/logout

    return () => {
      window.removeEventListener('storage', handleAuthChange);
      window.removeEventListener('authChange', handleAuthChange);
    };
    // Run only once on mount, rely on event listeners for updates
  }, []); // Empty dependency array ensures this runs only once on mount

  // Helper function to check user role
  const userHasRole = (roleName: string): boolean => {
    return !!currentUser?.roles?.some(role => role.name === roleName);
  };

  const handleLogout = async () => {
    try {
      await auth.logout();
      setIsAuthenticated(false); // Update local state immediately
      setCurrentUser(null); // Clear user data on logout
      window.dispatchEvent(new Event('authChange')); // Dispatch event for other components
      navigate('/login'); // Redirect to login page
    } catch (error) {
      console.error('Logout failed:', error);
      // Optionally show an error message to the user
    }
  };

  return (
    <nav className="navbar navbar-expand-lg mb-4 shadow-sm">
      <div className="container">
        <Link className="navbar-brand" to="/">
          SavedFeast
        </Link>
        <button
          className="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span className="navbar-toggler-icon"></span>
        </button>
        <div className="collapse navbar-collapse" id="navbarNav">
          <ul className="navbar-nav ms-auto align-items-center">
            <li className="nav-item">
              <Link className="nav-link" to="/">
                <i className="fas fa-home me-1"></i>
                Feed
              </Link>
            </li>
            <li className="nav-item">
              <Link className="nav-link" to="/partner-with-us">
                <i className="fas fa-handshake me-1"></i>
                Become a Partner
              </Link>
            </li>
            {isAuthenticated ? (
              <>
                {/* Conditionally show Dashboard link for providers */}
                {userHasRole('provider') && (
                  <>
                    <li className="nav-item">
                      <Link className="nav-link" to="/provider/dashboard">
                        <i className="fas fa-chart-line me-1"></i>
                        Restaurant Dashboard
                      </Link>
                    </li>
                    <li className="nav-item">
                      <Link className="nav-link" to="/provider/orders">
                        <i className="fas fa-list-alt me-1"></i>
                        Manage Orders
                      </Link>
                    </li>
                  </>
                )}
                {/* Conditionally show Admin Dashboard link for admins */}
                {userHasRole('admin') && (
                  <li className="nav-item">
                    <Link className="nav-link" to="/admin/dashboard">
                      <i className="fas fa-shield-alt me-1"></i>
                      Admin Dashboard
                    </Link>
                  </li>
                )}
                {/* Conditionally show My Orders link only for non-providers */}
                {!userHasRole('provider') && (
                  <li className="nav-item">
                    <Link className="nav-link" to="/orders">
                      <i className="fas fa-list-alt me-1"></i>
                      My Orders
                    </Link>
                  </li>
                )}
                <li className="nav-item">
                  <Link className="nav-link" to="/profile">
                    <i className="fas fa-user me-1"></i>
                    Profile
                  </Link>
                </li>
                <li className="nav-item">
                  {/* Use a button for actions like logout */}
                  <button
                    className="nav-link btn btn-link"
                    style={{ textDecoration: 'none' }}
                    onClick={handleLogout}
                  >
                    <i className="fas fa-sign-out-alt me-1"></i>
                    Logout
                  </button>
                </li>
              </>
            ) : (
              <>
                <li className="nav-item">
                  <Link className="nav-link" to="/login">
                    <i className="fas fa-sign-in-alt me-1"></i>
                    Login
                  </Link>
                </li>
                <li className="nav-item">
                  <Link className="nav-link" to="/signup">
                    <i className="fas fa-user-plus me-1"></i>
                    Sign Up
                  </Link>
                </li>
              </>
            )}
            {/* Conditionally render Cart link only for authenticated non-providers */}
            {isAuthenticated && !userHasRole('provider') && (
              <li className="nav-item ms-lg-2">
                <Link
                  className="btn btn-outline-primary btn-sm position-relative"
                  to="/checkout"
                >
                  <i className="fas fa-shopping-cart me-1"></i>
                  Cart
                  <span className="badge bg-primary rounded-pill position-absolute top-0 start-100 translate-middle">
                    {getItemCount()}
                  </span>
                </Link>
              </li>
            )}
          </ul>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
