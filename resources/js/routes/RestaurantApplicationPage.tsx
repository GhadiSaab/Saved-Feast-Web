import React, { useState, FormEvent } from 'react';
import axios from 'axios';

const RestaurantApplicationPage: React.FC = () => {
  const [restaurantName, setRestaurantName] = useState('');
  const [address, setAddress] = useState('');
  const [contactName, setContactName] = useState('');
  const [contactEmail, setContactEmail] = useState('');
  const [contactPhone, setContactPhone] = useState('');
  const [cuisineType, setCuisineType] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(null);

    const applicationData = {
      restaurant_name: restaurantName,
      address,
      contact_name: contactName,
      contact_email: contactEmail,
      contact_phone: contactPhone,
      cuisine_type: cuisineType,
      description,
    };

    try {
      // Call the actual API endpoint
      const response = await axios.post(
        '/api/restaurant-applications',
        applicationData
      );

      if (response.data.success) {
        setSuccess(
          response.data.message ||
            'Thank you for your application! We will review it and get back to you soon.'
        );
        // Optionally clear the form or redirect after a delay
        // setTimeout(() => navigate('/'), 3000);
      } else {
        setError(
          response.data.message ||
            'An unknown error occurred during submission.'
        );
      }
    } catch (err: any) {
      console.error('Application submission error:', err);
      if (err.response && err.response.data && err.response.data.message) {
        setError(err.response.data.message);
      } else {
        setError('An unexpected error occurred. Please try again later.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container-fluid py-4">
      <div className="row">
        <div className="col-12">
          {/* Header Section */}
          <div className="bg-gradient-primary text-white rounded-3 p-4 mb-4 shadow">
            <div className="row align-items-center">
              <div className="col-md-8">
                <h1 className="h2 mb-2 fw-bold">
                  <i className="fas fa-handshake me-3"></i>
                  Partner with SavedFeast
                </h1>
                <p className="mb-0 opacity-75">
                  Join our mission to reduce food waste and help customers save
                  money
                </p>
              </div>
              <div className="col-md-4 text-md-end">
                <div className="d-flex justify-content-md-end gap-2">
                  <a href="/" className="btn btn-light btn-sm">
                    <i className="fas fa-home me-1"></i>
                    Back to Home
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div className="row justify-content-center">
            <div className="col-md-8 col-lg-7">
              <div className="card border-0 shadow-sm">
                <div className="card-header bg-transparent border-0 text-center">
                  <h5 className="mb-0 fw-bold">
                    <i className="fas fa-store me-2 text-primary"></i>
                    Restaurant Application
                  </h5>
                  <p className="text-muted mt-2 mb-0">
                    Interested in selling your surplus meals on SavedFeast? Fill
                    out the form below to apply.
                  </p>
                </div>
                <div className="card-body p-4">
                  {success && (
                    <div
                      className="alert alert-success border-0 shadow-sm"
                      role="alert"
                    >
                      <div className="d-flex align-items-center">
                        <i className="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                          <h6 className="alert-heading mb-1">
                            Application Submitted!
                          </h6>
                          <p className="mb-0">{success}</p>
                        </div>
                      </div>
                    </div>
                  )}
                  {error && (
                    <div
                      className="alert alert-danger border-0 shadow-sm"
                      role="alert"
                    >
                      <div className="d-flex align-items-center">
                        <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                          <h6 className="alert-heading mb-1">Error</h6>
                          <p className="mb-0">{error}</p>
                        </div>
                      </div>
                    </div>
                  )}

                  {!success && ( // Hide form on success
                    <form onSubmit={handleSubmit}>
                      <div className="mb-3">
                        <label htmlFor="restaurant_name" className="form-label">
                          Restaurant Name
                        </label>
                        <input
                          type="text"
                          className="form-control"
                          id="restaurant_name"
                          value={restaurantName}
                          onChange={e => setRestaurantName(e.target.value)}
                          required
                          disabled={loading}
                        />
                      </div>
                      <div className="mb-3">
                        <label htmlFor="address" className="form-label">
                          Full Address
                        </label>
                        <input
                          type="text"
                          className="form-control"
                          id="address"
                          value={address}
                          onChange={e => setAddress(e.target.value)}
                          required
                          disabled={loading}
                        />
                      </div>
                      <div className="row">
                        <div className="col-md-6 mb-3">
                          <label htmlFor="contact_name" className="form-label">
                            Contact Person Name
                          </label>
                          <input
                            type="text"
                            className="form-control"
                            id="contact_name"
                            value={contactName}
                            onChange={e => setContactName(e.target.value)}
                            required
                            disabled={loading}
                          />
                        </div>
                        <div className="col-md-6 mb-3">
                          <label htmlFor="contact_email" className="form-label">
                            Contact Email
                          </label>
                          <input
                            type="email"
                            className="form-control"
                            id="contact_email"
                            value={contactEmail}
                            onChange={e => setContactEmail(e.target.value)}
                            required
                            disabled={loading}
                          />
                        </div>
                      </div>
                      <div className="mb-3">
                        <label htmlFor="contact_phone" className="form-label">
                          Contact Phone
                        </label>
                        <input
                          type="tel"
                          className="form-control"
                          id="contact_phone"
                          value={contactPhone}
                          onChange={e => setContactPhone(e.target.value)}
                          required
                          disabled={loading}
                        />
                      </div>
                      <div className="mb-3">
                        <label htmlFor="cuisine_type" className="form-label">
                          Cuisine Type (e.g., Italian, Lebanese, Cafe)
                        </label>
                        <input
                          type="text"
                          className="form-control"
                          id="cuisine_type"
                          value={cuisineType}
                          onChange={e => setCuisineType(e.target.value)}
                          required
                          disabled={loading}
                        />
                      </div>
                      <div className="mb-3">
                        <label htmlFor="description" className="form-label">
                          Brief Description / Why you want to join
                        </label>
                        <textarea
                          className="form-control"
                          id="description"
                          rows={3}
                          value={description}
                          onChange={e => setDescription(e.target.value)}
                          disabled={loading}
                        ></textarea>
                      </div>

                      <button
                        type="submit"
                        className="btn btn-primary w-100"
                        disabled={loading}
                      >
                        {loading ? (
                          <>
                            <span
                              className="spinner-border spinner-border-sm me-2"
                              role="status"
                              aria-hidden="true"
                            ></span>
                            Submitting Application...
                          </>
                        ) : (
                          'Submit Application'
                        )}
                      </button>
                    </form>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default RestaurantApplicationPage;
