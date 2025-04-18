// resources/js/routes/LoginPage.tsx
import React, { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import auth from '../auth'; // Import the auth helper

const LoginPage: React.FC = () => {
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
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
            <div className="col-md-6">
                <div className="card">
                    <div className="card-header">Login</div>
                    <div className="card-body">
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
                             {error && (validationErrors.email || validationErrors.password) && (
                                <div className="alert alert-danger mt-3" role="alert">
                                    {error}
                                </div>
                            )}
                            <button type="submit" className="btn btn-primary" disabled={loading}>
                                {loading ? 'Logging in...' : 'Login'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
