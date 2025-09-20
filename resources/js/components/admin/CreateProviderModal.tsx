import React, { useState } from 'react';
import axios from 'axios';

interface CreateProviderModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

interface ProviderFormData {
  // User data
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  address: string;
  password: string;
  password_confirmation: string;

  // Restaurant data
  restaurant_name: string;
  restaurant_description: string;
  restaurant_address: string;
  restaurant_phone: string;
  restaurant_email: string;
  restaurant_website: string;
  cuisine_type: string;
  delivery_radius: number;
}

const CreateProviderModal: React.FC<CreateProviderModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
}) => {
  const [formData, setFormData] = useState<ProviderFormData>({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    password: '',
    password_confirmation: '',
    restaurant_name: '',
    restaurant_description: '',
    restaurant_address: '',
    restaurant_phone: '',
    restaurant_email: '',
    restaurant_website: '',
    cuisine_type: '',
    delivery_radius: 5.0,
  });

  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const handleInputChange = (
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));

    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: [],
      }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await axios.post('/api/admin/providers', formData);

      if (response.data.status) {
        onSuccess();
        onClose();
        // Reset form
        setFormData({
          first_name: '',
          last_name: '',
          email: '',
          phone: '',
          address: '',
          password: '',
          password_confirmation: '',
          restaurant_name: '',
          restaurant_description: '',
          restaurant_address: '',
          restaurant_phone: '',
          restaurant_email: '',
          restaurant_website: '',
          cuisine_type: '',
          delivery_radius: 5.0,
        });
      }
    } catch (error: any) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({
          general: [error.response?.data?.message || 'An error occurred'],
        });
      }
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div
      className="modal fade show d-block"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
    >
      <div className="modal-dialog modal-lg">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title">Create Provider Profile</h5>
            <button
              type="button"
              className="btn-close"
              onClick={onClose}
              disabled={loading}
            ></button>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="modal-body">
              {errors.general && (
                <div className="alert alert-danger">
                  {errors.general.map((error, index) => (
                    <div key={index}>{error}</div>
                  ))}
                </div>
              )}

              <div className="row">
                <div className="col-12">
                  <h6 className="text-primary mb-3">User Information</h6>
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="first_name" className="form-label">
                    First Name *
                  </label>
                  <input
                    type="text"
                    className={`form-control ${errors.first_name ? 'is-invalid' : ''}`}
                    id="first_name"
                    name="first_name"
                    value={formData.first_name}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.first_name && (
                    <div className="invalid-feedback">
                      {errors.first_name.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="last_name" className="form-label">
                    Last Name *
                  </label>
                  <input
                    type="text"
                    className={`form-control ${errors.last_name ? 'is-invalid' : ''}`}
                    id="last_name"
                    name="last_name"
                    value={formData.last_name}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.last_name && (
                    <div className="invalid-feedback">
                      {errors.last_name.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="email" className="form-label">
                    Email *
                  </label>
                  <input
                    type="email"
                    className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.email && (
                    <div className="invalid-feedback">
                      {errors.email.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="phone" className="form-label">
                    Phone *
                  </label>
                  <input
                    type="tel"
                    className={`form-control ${errors.phone ? 'is-invalid' : ''}`}
                    id="phone"
                    name="phone"
                    value={formData.phone}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.phone && (
                    <div className="invalid-feedback">
                      {errors.phone.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-12 mb-3">
                  <label htmlFor="address" className="form-label">
                    Address *
                  </label>
                  <textarea
                    className={`form-control ${errors.address ? 'is-invalid' : ''}`}
                    id="address"
                    name="address"
                    value={formData.address}
                    onChange={handleInputChange}
                    rows={2}
                    required
                  />
                  {errors.address && (
                    <div className="invalid-feedback">
                      {errors.address.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="password" className="form-label">
                    Password *
                  </label>
                  <input
                    type="password"
                    className={`form-control ${errors.password ? 'is-invalid' : ''}`}
                    id="password"
                    name="password"
                    value={formData.password}
                    onChange={handleInputChange}
                    required
                    minLength={8}
                  />
                  {errors.password && (
                    <div className="invalid-feedback">
                      {errors.password.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="password_confirmation" className="form-label">
                    Confirm Password *
                  </label>
                  <input
                    type="password"
                    className={`form-control ${errors.password_confirmation ? 'is-invalid' : ''}`}
                    id="password_confirmation"
                    name="password_confirmation"
                    value={formData.password_confirmation}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.password_confirmation && (
                    <div className="invalid-feedback">
                      {errors.password_confirmation.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              <hr className="my-4" />

              <div className="row">
                <div className="col-12">
                  <h6 className="text-primary mb-3">Restaurant Information</h6>
                </div>

                <div className="col-12 mb-3">
                  <label htmlFor="restaurant_name" className="form-label">
                    Restaurant Name *
                  </label>
                  <input
                    type="text"
                    className={`form-control ${errors.restaurant_name ? 'is-invalid' : ''}`}
                    id="restaurant_name"
                    name="restaurant_name"
                    value={formData.restaurant_name}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.restaurant_name && (
                    <div className="invalid-feedback">
                      {errors.restaurant_name.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-12 mb-3">
                  <label
                    htmlFor="restaurant_description"
                    className="form-label"
                  >
                    Description
                  </label>
                  <textarea
                    className={`form-control ${errors.restaurant_description ? 'is-invalid' : ''}`}
                    id="restaurant_description"
                    name="restaurant_description"
                    value={formData.restaurant_description}
                    onChange={handleInputChange}
                    rows={3}
                  />
                  {errors.restaurant_description && (
                    <div className="invalid-feedback">
                      {errors.restaurant_description.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-12 mb-3">
                  <label htmlFor="restaurant_address" className="form-label">
                    Restaurant Address *
                  </label>
                  <textarea
                    className={`form-control ${errors.restaurant_address ? 'is-invalid' : ''}`}
                    id="restaurant_address"
                    name="restaurant_address"
                    value={formData.restaurant_address}
                    onChange={handleInputChange}
                    rows={2}
                    required
                  />
                  {errors.restaurant_address && (
                    <div className="invalid-feedback">
                      {errors.restaurant_address.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="restaurant_phone" className="form-label">
                    Restaurant Phone
                  </label>
                  <input
                    type="tel"
                    className={`form-control ${errors.restaurant_phone ? 'is-invalid' : ''}`}
                    id="restaurant_phone"
                    name="restaurant_phone"
                    value={formData.restaurant_phone}
                    onChange={handleInputChange}
                  />
                  {errors.restaurant_phone && (
                    <div className="invalid-feedback">
                      {errors.restaurant_phone.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="restaurant_email" className="form-label">
                    Restaurant Email *
                  </label>
                  <input
                    type="email"
                    className={`form-control ${errors.restaurant_email ? 'is-invalid' : ''}`}
                    id="restaurant_email"
                    name="restaurant_email"
                    value={formData.restaurant_email}
                    onChange={handleInputChange}
                    required
                  />
                  {errors.restaurant_email && (
                    <div className="invalid-feedback">
                      {errors.restaurant_email.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="restaurant_website" className="form-label">
                    Website
                  </label>
                  <input
                    type="url"
                    className={`form-control ${errors.restaurant_website ? 'is-invalid' : ''}`}
                    id="restaurant_website"
                    name="restaurant_website"
                    value={formData.restaurant_website}
                    onChange={handleInputChange}
                    placeholder="https://example.com"
                  />
                  {errors.restaurant_website && (
                    <div className="invalid-feedback">
                      {errors.restaurant_website.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="cuisine_type" className="form-label">
                    Cuisine Type
                  </label>
                  <input
                    type="text"
                    className={`form-control ${errors.cuisine_type ? 'is-invalid' : ''}`}
                    id="cuisine_type"
                    name="cuisine_type"
                    value={formData.cuisine_type}
                    onChange={handleInputChange}
                    placeholder="e.g., Italian, Mexican, Asian"
                  />
                  {errors.cuisine_type && (
                    <div className="invalid-feedback">
                      {errors.cuisine_type.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="delivery_radius" className="form-label">
                    Delivery Radius (km)
                  </label>
                  <input
                    type="number"
                    className={`form-control ${errors.delivery_radius ? 'is-invalid' : ''}`}
                    id="delivery_radius"
                    name="delivery_radius"
                    value={formData.delivery_radius}
                    onChange={handleInputChange}
                    min="0"
                    max="50"
                    step="0.1"
                  />
                  {errors.delivery_radius && (
                    <div className="invalid-feedback">
                      {errors.delivery_radius.map((error, index) => (
                        <div key={index}>{error}</div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button
                type="button"
                className="btn btn-secondary"
                onClick={onClose}
                disabled={loading}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="btn btn-primary"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <span
                      className="spinner-border spinner-border-sm me-2"
                      role="status"
                      aria-hidden="true"
                    ></span>
                    Creating...
                  </>
                ) : (
                  'Create Provider'
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CreateProviderModal;
