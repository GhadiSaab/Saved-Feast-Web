// resources/js/routes/LoginPage.tsx
import React, { useState, FormEvent } from 'react';
import { useNavigate, Link } from 'react-router-dom'; // Import Link
import auth from '../auth'; // Import the auth helper

const LoginPage: React.FC = () => {
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [showPassword, setShowPassword] = useState<boolean>(false); // State for password visibility
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [validationErrors, setValidationErrors] = useState<{ [key: string]: string[] }>({});
    const navigate = useNavigate();

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setLoading(true);
        setError(null);
        setValidationErrors({});

        try {
            await auth.login({ email, password });
            navigate('/'); // Redirect to feed page on successful login
        } catch (err: any) {
            if (err.response && err.response.status === 422) {
                // Handle Laravel validation errors
                setValidationErrors(err.response.data.errors);
                setError("Please check the form for errors.");
            } else if (err.response && err.response.data && err.response.data.message) {
                // Handle other errors (e.g., invalid credentials)
                setError(err.response.data.message);
            } else {
                setError('An unexpected error occurred. Please try again.');
                console.error("Login error:", err);
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="row justify-content-center">
            <div className="col-md-6 col-lg-5"> {/* Slightly narrower on larger screens */}
                <div className="card shadow-sm"> {/* Added shadow */}
                    <div className="card-header text-center fs-4"> {/* Centered header */}
                        Login to SavedFeast
                    </div>
                    <div className="card-body p-4"> {/* Increased padding */}
                        {error && !validationErrors.email && !validationErrors.password && (
                            <div className="alert alert-danger" role="alert">
                                {error}
                            </div>
                        )}
                        <form onSubmit={handleSubmit}>
                            <div className="mb-3">
                                <label htmlFor="email" className="form-label">Email address</label>
                                <input
                                    type="email"
                                    className={`form-control ${validationErrors.email ? 'is-invalid' : ''}`}
                                    id="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.email && (
                                    <div className="invalid-feedback">
                                        {validationErrors.email.join(', ')}
                                    </div>
                                )}
                            </div>
                            <div className="mb-3 position-relative"> {/* Added position-relative */}
                                <label htmlFor="password" className="form-label">Password</label>
                                <input
                                    type={showPassword ? 'text' : 'password'} // Toggle input type
                                    className={`form-control ${validationErrors.password ? 'is-invalid' : ''}`}
                                    id="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm position-absolute end-0 top-50 translate-middle-y me-2 mt-3" // Position the button
                                    style={{ zIndex: 5 }} // Ensure button is clickable over input
                                    onClick={() => setShowPassword(!showPassword)}
                                >
                                    {showPassword ? 'Hide' : 'Show'}
                                </button>
                                {validationErrors.password && (
                                    <div className="invalid-feedback">
                                        {validationErrors.password.join(', ')}
                                    </div>
                                )}
                            </div>
                            <div className="mb-3 form-check"> {/* Remember Me Checkbox */}
                                <input type="checkbox" className="form-check-input" id="rememberMe" />
                                <label className="form-check-label" htmlFor="rememberMe">Remember Me</label>
                            </div>
                             {error && (validationErrors.email || validationErrors.password) && (
                                <div className="alert alert-danger mt-3" role="alert">
                                    {error}
                                </div>
                            )}
                            <button type="submit" className="btn btn-primary w-100" disabled={loading}> {/* Use primary color, make full width */}
                                {loading ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Logging in...
                                    </>
                                ) : (
                                    'Login'
                                )}
                            </button>
                            <div className="text-center mt-4"> {/* Centered links */}
                                <Link to="/signup" className="d-block mb-2 auth-link">Don't have an account? Sign Up</Link>
                                <a href="#" className="d-block auth-link text-muted" onClick={(e) => {e.preventDefault(); alert('Forgot Password functionality not yet implemented.');}}>
                                    Forgot Password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
