import React, { useState, FormEvent } from 'react';
import { useNavigate, Link } from 'react-router-dom'; // Import Link
import auth from '../auth'; // Import the auth helper

const SignupPage: React.FC = () => {
    const [firstName, setFirstName] = useState<string>('');
    const [lastName, setLastName] = useState<string>('');
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [showPassword, setShowPassword] = useState<boolean>(false); // State for password visibility
    const [passwordConfirmation, setPasswordConfirmation] = useState<string>('');
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState<boolean>(false); // State for confirmation visibility
    const [phone, setPhone] = useState<string>('');
    const [address, setAddress] = useState<string>('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [validationErrors, setValidationErrors] = useState<{ [key: string]: string[] }>({});
    const navigate = useNavigate();

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setLoading(true);
        setError(null);
        setValidationErrors({});

        if (password !== passwordConfirmation) {
            setError("Passwords do not match.");
            setValidationErrors({ password_confirmation: ["Passwords do not match."] });
            setLoading(false);
            return;
        }

        // Build payload to match User model fillable attributes
        const userData = {
            first_name: firstName,
            last_name: lastName,
            email,
            password,
            password_confirmation: passwordConfirmation,
            phone,
            address,
        };

        try {
            await auth.register(userData);
            navigate('/'); // Redirect to feed page on successful registration
        } catch (err: any) {
            if (err.response && err.response.status === 422) {
                // Handle Laravel validation errors
                setValidationErrors(err.response.data.errors);
                setError("Please check the form for errors.");
            } else if (err.response && err.response.data && err.response.data.message) {
                // Handle other errors
                setError(err.response.data.message);
            } else {
                setError('An unexpected error occurred during registration. Please try again.');
                console.error("Signup error:", err);
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
                        <i className="fas fa-user-plus me-2"></i>
                        Join SavedFeast
                    </h3>
                    <p className="mb-0 opacity-75">Start your journey to save food and money</p>
                </div>
                <div className="card-body">
                    {error && !Object.keys(validationErrors).length && (
                        <div className="alert alert-danger fade-in-up" role="alert">
                            <i className="fas fa-exclamation-triangle me-2"></i>
                            {error}
                        </div>
                    )}
                    
                    <form onSubmit={handleSubmit}>
                        {/* Name Fields */}
                        <div className="row">
                            <div className="col-md-6 mb-3">
                                <label htmlFor="first_name" className="form-label">
                                    <i className="fas fa-user me-2"></i>
                                    First Name
                                </label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.first_name ? 'is-invalid' : ''}`}
                                    id="first_name"
                                    placeholder="Enter your first name"
                                    value={firstName}
                                    onChange={(e) => setFirstName(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.first_name && (
                                    <div className="invalid-feedback">
                                        <i className="fas fa-exclamation-circle me-1"></i>
                                        {validationErrors.first_name.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="col-md-6 mb-3">
                                <label htmlFor="last_name" className="form-label">
                                    <i className="fas fa-user me-2"></i>
                                    Last Name
                                </label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.last_name ? 'is-invalid' : ''}`}
                                    id="last_name"
                                    placeholder="Enter your last name"
                                    value={lastName}
                                    onChange={(e) => setLastName(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.last_name && (
                                    <div className="invalid-feedback">
                                        <i className="fas fa-exclamation-circle me-1"></i>
                                        {validationErrors.last_name.join(', ')}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Email Field */}
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

                        {/* Password Fields */}
                        <div className="mb-3">
                            <label htmlFor="password" className="form-label">
                                <i className="fas fa-lock me-2"></i>
                                Password
                            </label>
                            <div className="input-group">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    className={`form-control ${validationErrors.password ? 'is-invalid' : ''}`}
                                    id="password"
                                    placeholder="Create a strong password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    disabled={loading}
                                    aria-describedby="passwordHelp"
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
                            <div id="passwordHelp" className="form-text">
                                <i className="fas fa-info-circle me-1"></i>
                                Minimum 8 characters, including uppercase, lowercase, numbers, and symbols.
                            </div>
                            {validationErrors.password && (
                                <div className="invalid-feedback">
                                    <i className="fas fa-exclamation-circle me-1"></i>
                                    {validationErrors.password.join(', ')}
                                </div>
                            )}
                        </div>

                        <div className="mb-3">
                            <label htmlFor="password_confirmation" className="form-label">
                                <i className="fas fa-lock me-2"></i>
                                Confirm Password
                            </label>
                            <div className="input-group">
                                <input
                                    type={showPasswordConfirmation ? 'text' : 'password'}
                                    className={`form-control ${validationErrors.password_confirmation ? 'is-invalid' : ''}`}
                                    id="password_confirmation"
                                    placeholder="Confirm your password"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                    disabled={loading}
                                >
                                    <i className={`fas ${showPasswordConfirmation ? 'fa-eye-slash' : 'fa-eye'}`}></i>
                                </button>
                            </div>
                            {validationErrors.password_confirmation && (
                                <div className="invalid-feedback">
                                    <i className="fas fa-exclamation-circle me-1"></i>
                                    {validationErrors.password_confirmation.join(', ')}
                                </div>
                            )}
                        </div>

                        {/* Contact Fields */}
                        <div className="row">
                            <div className="col-md-6 mb-3">
                                <label htmlFor="phone" className="form-label">
                                    <i className="fas fa-phone me-2"></i>
                                    Phone (Optional)
                                </label>
                                <input
                                    type="tel"
                                    className={`form-control ${validationErrors.phone ? 'is-invalid' : ''}`}
                                    id="phone"
                                    placeholder="Enter your phone number"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    disabled={loading}
                                />
                                {validationErrors.phone && (
                                    <div className="invalid-feedback">
                                        <i className="fas fa-exclamation-circle me-1"></i>
                                        {validationErrors.phone.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="col-md-6 mb-3">
                                <label htmlFor="address" className="form-label">
                                    <i className="fas fa-map-marker-alt me-2"></i>
                                    Address (Optional)
                                </label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.address ? 'is-invalid' : ''}`}
                                    id="address"
                                    placeholder="Enter your address"
                                    value={address}
                                    onChange={(e) => setAddress(e.target.value)}
                                    disabled={loading}
                                />
                                {validationErrors.address && (
                                    <div className="invalid-feedback">
                                        <i className="fas fa-exclamation-circle me-1"></i>
                                        {validationErrors.address.join(', ')}
                                    </div>
                                )}
                            </div>
                        </div>

                        {error && Object.keys(validationErrors).length > 0 && (
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
                                    Creating account...
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-user-plus me-2"></i>
                                    Create Account
                                </>
                            )}
                        </button>
                        
                        <div className="text-center">
                            <Link to="/login" className="auth-link">
                                <i className="fas fa-sign-in-alt me-1"></i>
                                Already have an account? Sign in
                            </Link>
                        </div>
                        
                        <div className="text-center mt-3">
                            <small className="text-muted">
                                <i className="fas fa-shield-alt me-1"></i>
                                By signing up, you agree to our{' '}
                                <a href="#" onClick={(e) => e.preventDefault()} className="text-decoration-none">
                                    Terms of Service
                                </a>{' '}
                                and{' '}
                                <a href="#" onClick={(e) => e.preventDefault()} className="text-decoration-none">
                                    Privacy Policy
                                </a>.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
            
            {/* Benefits Section */}
            <div className="text-center mt-4">
                <h5 className="mb-3">Why join SavedFeast?</h5>
                <div className="row g-3">
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-utensils fa-2x text-primary mb-2"></i>
                            <h6>Delicious Meals</h6>
                            <small className="text-muted">Fresh food from local restaurants</small>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-percentage fa-2x text-success mb-2"></i>
                            <h6>Great Savings</h6>
                            <small className="text-muted">Up to 70% off regular prices</small>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="feature-item">
                            <i className="fas fa-globe fa-2x text-info mb-2"></i>
                            <h6>Eco-Friendly</h6>
                            <small className="text-muted">Help reduce food waste</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SignupPage;
