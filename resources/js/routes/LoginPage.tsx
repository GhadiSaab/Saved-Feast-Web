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
        <div className="auth-container">
            <div className="card">
                <div className="card-header">
                    <h3>
                        <i className="fas fa-sign-in-alt me-2"></i>
                        Welcome Back
                    </h3>
                    <p className="mb-0 opacity-75">Sign in to continue saving food and money</p>
                </div>
                <div className="card-body">
                    {error && !validationErrors.email && !validationErrors.password && (
                        <div className="alert alert-danger fade-in-up" role="alert">
                            <i className="fas fa-exclamation-triangle me-2"></i>
                            {error}
                        </div>
                    )}
                    
                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label htmlFor="email" className="form-label">
                                <i className="fas fa-envelope me-2"></i>
                                Email address
                            </label>
                            <input
                                type="email"
                                className={`form-control ${validationErrors.email ? 'is-invalid' : ''}`}
                                id="email"
                                placeholder="Enter your email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                disabled={loading}
                            />
                            {validationErrors.email && (
                                <div className="invalid-feedback">
                                    <i className="fas fa-exclamation-circle me-1"></i>
                                    {validationErrors.email.join(', ')}
                                </div>
                            )}
                        </div>
                        
                        <div className="mb-3 position-relative">
                            <label htmlFor="password" className="form-label">
                                <i className="fas fa-lock me-2"></i>
                                Password
                            </label>
                            <div className="input-group">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    className={`form-control ${validationErrors.password ? 'is-invalid' : ''}`}
                                    id="password"
                                    placeholder="Enter your password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    onClick={() => setShowPassword(!showPassword)}
                                    disabled={loading}
                                >
                                    <i className={`fas ${showPassword ? 'fa-eye-slash' : 'fa-eye'}`}></i>
                                </button>
                            </div>
                            {validationErrors.password && (
                                <div className="invalid-feedback">
                                    <i className="fas fa-exclamation-circle me-1"></i>
                                    {validationErrors.password.join(', ')}
                                </div>
                            )}
                        </div>
                        
                        <div className="mb-3 form-check">
                            <input type="checkbox" className="form-check-input" id="rememberMe" />
                            <label className="form-check-label" htmlFor="rememberMe">
                                <i className="fas fa-check-circle me-1"></i>
                                Remember me
                            </label>
                        </div>
                        
                        {error && (validationErrors.email || validationErrors.password) && (
                            <div className="alert alert-danger fade-in-up" role="alert">
                                <i className="fas fa-exclamation-triangle me-2"></i>
                                {error}
                            </div>
                        )}
                        
                        <button 
                            type="submit" 
                            className="btn btn-primary w-100 mb-3" 
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Signing in...
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </>
                            )}
                        </button>
                        
                        <div className="text-center">
                            <div className="mb-2">
                                <Link to="/signup" className="auth-link">
                                    <i className="fas fa-user-plus me-1"></i>
                                    Don't have an account? Sign up
                                </Link>
                            </div>
                            <div>
                                <a 
                                    href="#" 
                                    className="auth-link text-muted" 
                                    onClick={(e) => {
                                        e.preventDefault(); 
                                        alert('Forgot Password functionality not yet implemented.');
                                    }}
                                >
                                    <i className="fas fa-key me-1"></i>
                                    Forgot your password?
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            {/* Additional Info Section */}
            <div className="text-center mt-4">
                <div className="row g-3">
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-leaf fa-2x text-success mb-2"></i>
                            <h6>Save Food</h6>
                            <small className="text-muted">Reduce food waste</small>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-euro-sign fa-2x text-warning mb-2"></i>
                            <h6>Save Money</h6>
                            <small className="text-muted">Great discounts</small>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-heart fa-2x text-danger mb-2"></i>
                            <h6>Save Planet</h6>
                            <small className="text-muted">Eco-friendly choice</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
