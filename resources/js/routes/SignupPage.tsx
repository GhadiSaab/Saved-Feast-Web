import React, { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import auth from '../auth'; // Import the auth helper

const SignupPage: React.FC = () => {
    const [firstName, setFirstName] = useState<string>('');
    const [lastName, setLastName] = useState<string>('');
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [passwordConfirmation, setPasswordConfirmation] = useState<string>('');
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
            <div className="col-md-6">
                <div className="card">
                    <div className="card-header">Sign Up</div>
                    <div className="card-body">
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

                            <div className="mb-3">
                                <label htmlFor="password" className="form-label">Password</label>
                                <input
                                    type="password"
                                    className={`form-control ${validationErrors.password ? 'is-invalid' : ''}`}
                                    id="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    disabled={loading}
                                />
                                {validationErrors.password && (
                                    <div className="invalid-feedback">
                                        {validationErrors.password.join(', ')}
                                    </div>
                                )}
                            </div>

                            <div className="mb-3">
                                <label htmlFor="password_confirmation" className="form-label">Confirm Password</label>
                                <input
                                    type="password"
                                    className={`form-control ${validationErrors.password_confirmation ? 'is-invalid' : ''}`}
                                    id="password_confirmation"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    required
                                    disabled={loading}
                                />
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

                            <button type="submit" className="btn btn-primary" disabled={loading}>
                                {loading ? 'Signing up...' : 'Sign Up'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SignupPage;
