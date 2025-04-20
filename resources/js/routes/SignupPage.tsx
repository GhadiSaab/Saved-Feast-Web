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
        <div className="row justify-content-center">
            <div className="col-md-6 col-lg-5"> {/* Slightly narrower on larger screens */}
                <div className="card shadow-sm"> {/* Added shadow */}
                    <div className="card-header text-center fs-4"> {/* Centered header */}
                        Sign Up for SavedFeast
                    </div>
                    <div className="card-body p-4"> {/* Increased padding */}
                        {error && !Object.keys(validationErrors).length && (
                            <div className="alert alert-danger" role="alert">
                                {error}
                            </div>
                        )}
                        <form onSubmit={handleSubmit}>
                            <div className="mb-3">
                                <label htmlFor="first_name" className="form-label">First Name</label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.first_name ? 'is-invalid' : ''}`}
                                    id="first_name"
                                    value={firstName}
                                    onChange={(e) => setFirstName(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.first_name && (
                                    <div className="invalid-feedback">
                                        {validationErrors.first_name.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="mb-3">
                                <label htmlFor="last_name" className="form-label">Last Name</label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.last_name ? 'is-invalid' : ''}`}
                                    id="last_name"
                                    value={lastName}
                                    onChange={(e) => setLastName(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.last_name && (
                                    <div className="invalid-feedback">
                                        {validationErrors.last_name.join(', ')}
                                    </div>
                                )}
                            </div>

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
                                    aria-describedby="passwordHelp"
                                />
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm position-absolute end-0 top-50 translate-middle-y me-2 mt-1" // Position the button (adjusted margin)
                                    style={{ zIndex: 5 }} // Ensure button is clickable over input
                                    onClick={() => setShowPassword(!showPassword)}
                                >
                                    {showPassword ? 'Hide' : 'Show'}
                                </button>
                                <div id="passwordHelp" className="form-text">
                                    Minimum 8 characters, including uppercase, lowercase, numbers, and symbols. {/* Updated hint */}
                                </div>
                                {validationErrors.password && (
                                    <div className="invalid-feedback">
                                        {validationErrors.password.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="mb-3 position-relative"> {/* Added position-relative */}
                                <label htmlFor="password_confirmation" className="form-label">Confirm Password</label>
                                <input
                                    type={showPasswordConfirmation ? 'text' : 'password'} // Toggle input type
                                    className={`form-control ${validationErrors.password_confirmation ? 'is-invalid' : ''}`}
                                    id="password_confirmation"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm position-absolute end-0 top-50 translate-middle-y me-2" // Position the button
                                    style={{ zIndex: 5 }} // Ensure button is clickable over input
                                    onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                >
                                    {showPasswordConfirmation ? 'Hide' : 'Show'}
                                </button>
                                {validationErrors.password_confirmation && (
                                    <div className="invalid-feedback">
                                        {validationErrors.password_confirmation.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="mb-3">
                                <label htmlFor="phone" className="form-label">Phone</label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.phone ? 'is-invalid' : ''}`}
                                    id="phone"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    disabled={loading}
                                />
                                {validationErrors.phone && (
                                    <div className="invalid-feedback">
                                        {validationErrors.phone.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="mb-3">
                                <label htmlFor="address" className="form-label">Address</label>
                                <input
                                    type="text"
                                    className={`form-control ${validationErrors.address ? 'is-invalid' : ''}`}
                                    id="address"
                                    value={address}
                                    onChange={(e) => setAddress(e.target.value)}
                                    disabled={loading}
                                />
                                {validationErrors.address && (
                                    <div className="invalid-feedback">
                                        {validationErrors.address.join(', ')}
                                    </div>
                                )}
                            </div>

                            {error && Object.keys(validationErrors).length > 0 && (
                                <div className="alert alert-danger mt-3" role="alert">
                                    {error}
                                </div>
                            )}

                            <button type="submit" className="btn btn-primary w-100" disabled={loading}> {/* Use primary color, make full width */}
                                {loading ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Signing up...
                                    </>
                                ) : (
                                    'Sign Up'
                                )}
                            </button>
                            <div className="text-center mt-4"> {/* Centered link */}
                                <Link to="/login" className="d-block auth-link">Already have an account? Login</Link>
                            </div>
                            <p className="text-center text-muted small mt-4"> {/* Terms/Privacy text */}
                                By signing up, you agree to our <a href="#" onClick={(e) => e.preventDefault()}>Terms of Service</a> and <a href="#" onClick={(e) => e.preventDefault()}>Privacy Policy</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SignupPage;
