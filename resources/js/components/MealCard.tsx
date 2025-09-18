// resources/js/components/MealCard.tsx
import React, { useState, useRef } from 'react';
import { useCart } from '../context/CartContext'; // Import the useCart hook
import { useNavigate } from 'react-router-dom'; // Import useNavigate
import auth from '../auth'; // Import the auth helper

// Re-define or import the Meal type (ensure consistency with backend)
interface Meal {
  id: number;
  title: string; // Changed from name
  description: string;
  current_price: number; // Changed from price (Discounted price)
  original_price?: number | null; // Original price (optional)
  image?: string | null; // Changed from image_url to image
  available_from: string; // Add available_from (ISO string)
  available_until: string; // Add available_until (ISO string)
  restaurant?: {
    name: string;
  };
  category?: {
    name: string;
  };
  // Add other relevant fields as needed
}

interface MealCardProps {
  meal: Meal;
  // Removed onAddToCart prop - using context instead
}

const MealCard: React.FC<MealCardProps> = ({ meal }) => {
  const { addToCart } = useCart(); // Get addToCart function from context
  const navigate = useNavigate(); // Get navigate function
  const [showImageModal, setShowImageModal] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [buttonScale, setButtonScale] = useState(1);
  const buttonRef = useRef<HTMLButtonElement>(null);

  const animateButton = () => {
    setButtonScale(0.95);
    setTimeout(() => setButtonScale(1), 150);
  };

  const showSuccessFeedback = () => {
    setShowSuccess(true);
    setTimeout(() => {
      setShowSuccess(false);
    }, 1500);
  };

  const handleAddToCart = () => {
    animateButton();

    if (auth.isAuthenticated()) {
      // Ensure current_price is a valid number before adding
      const currentPrice =
        typeof meal.current_price === 'number'
          ? meal.current_price
          : parseFloat(String(meal.current_price));
      if (!isNaN(currentPrice)) {
        // Pass title and current_price to addToCart
        addToCart({ id: meal.id, name: meal.title, price: currentPrice });
        console.log(`Added ${meal.title} to cart (ID: ${meal.id})`);
        showSuccessFeedback();
      } else {
        console.error(`Invalid price for meal: ${meal.title}`);
        // Optionally: Show an error to the user
      }
    } else {
      // Redirect to login page if not authenticated
      navigate('/login');
    }
  };

  // Calculate savings percentage
  const calculateSavings = () => {
    if (meal.original_price && meal.original_price > meal.current_price) {
      const savings =
        ((meal.original_price - meal.current_price) / meal.original_price) *
        100;
      return Math.round(savings);
    }
    return 0;
  };

  // Format pickup time
  const formatPickupTime = () => {
    if (meal.available_from && meal.available_until) {
      const fromTime = new Date(meal.available_from).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      });
      const untilTime = new Date(meal.available_until).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      });
      return `${fromTime} - ${untilTime}`;
    }
    return 'Time TBD';
  };

  // Construct the full image URL if the path is relative
  const imageUrl = meal.image ? 
    (meal.image.startsWith('http') ? meal.image : `${window.location.origin}${meal.image}`) : 
    null;

  const savingsPercentage = calculateSavings();

  const handleCardClick = () => {
    setShowImageModal(true);
  };

  const handleModalClose = () => {
    setShowImageModal(false);
  };

  return (
    <>
      <div
        className="card h-100 meal-card"
        onClick={handleCardClick}
        style={{ cursor: 'pointer' }}
      >
        {/* Image Section */}
        <div className="card-image-placeholder position-relative">
          {imageUrl ? (
            <div className="meal-image-container">
              <img
                src={imageUrl}
                alt={meal.title}
                className="card-img-top"
                style={{ height: '150px', objectFit: 'cover' }}
                onError={(e) => {
                  // Fallback to placeholder if image fails to load
                  const target = e.target as HTMLImageElement;
                  target.style.display = 'none';
                  target.nextElementSibling?.classList.remove('d-none');
                }}
              />
              <div className="image-placeholder bg-gradient-primary d-flex align-items-center justify-content-center d-none">
                <div className="text-center">
                  <i className="fas fa-utensils fa-2x text-white opacity-75 mb-2"></i>
                  <div className="text-white opacity-75 small">
                    <i className="fas fa-eye me-1"></i>
                    Click to view
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="image-placeholder bg-gradient-primary d-flex align-items-center justify-content-center">
              <div className="text-center">
                <i className="fas fa-utensils fa-2x text-white opacity-75 mb-2"></i>
                <div className="text-white opacity-75 small">
                  <i className="fas fa-eye me-1"></i>
                  Click to view
                </div>
              </div>
            </div>
          )}

          {/* Savings Badge */}
          {savingsPercentage > 0 && (
            <div className="savings-badge position-absolute top-0 start-0 m-2">
              <span className="badge bg-success fs-6 fw-bold">
                <i className="fas fa-percentage me-1"></i>
                {savingsPercentage}% OFF
              </span>
            </div>
          )}

          {/* Restaurant Badge */}
          {meal.restaurant && (
            <div className="restaurant-badge position-absolute top-0 end-0 m-2">
              <span className="badge bg-primary fs-6 fw-bold">
                <i className="fas fa-store me-1"></i>
                {meal.restaurant.name}
              </span>
            </div>
          )}
        </div>

        {/* Card Body */}
        <div className="card-body d-flex flex-column">
          {/* Title and Restaurant */}
          <div className="mb-3">
            <h5 className="card-title fw-bold mb-1 text-primary">
              {meal.title}
            </h5>
            {meal.restaurant && (
              <p className="card-subtitle text-muted mb-0 small">
                <i className="fas fa-store me-1"></i>
                {meal.restaurant.name}
              </p>
            )}
          </div>

          {/* Description */}
          <div className="flex-grow-1 mb-3">
            <p className="card-text small text-muted">
              {meal.description ? (
                meal.description.length > 120 ? (
                  `${meal.description.substring(0, 117)}...`
                ) : (
                  meal.description
                )
              ) : (
                <span className="text-muted">
                  <i className="fas fa-info-circle me-1"></i>
                  No description available.
                </span>
              )}
            </p>
          </div>

          {/* Pickup Time */}
          <div className="mb-3">
            <div className="pickup-time d-inline-block">
              <i className="fas fa-clock me-2"></i>
              <strong>Pickup:</strong> {formatPickupTime()}
            </div>
          </div>

          {/* Price and Action Section */}
          <div className="mt-auto">
            <div className="d-flex justify-content-between align-items-center">
              <div className="price-display flex-grow-1 me-3">
                {meal.original_price &&
                meal.original_price > meal.current_price ? (
                  <div className="d-flex flex-column">
                    <div className="d-flex align-items-baseline">
                      <span className="current-price fw-bold text-success me-2">
                        €{meal.current_price.toFixed(2)}
                      </span>
                      <span className="original-price text-muted small">
                        <del>€{meal.original_price.toFixed(2)}</del>
                      </span>
                    </div>
                    <small className="text-success fw-bold">
                      Save €{(meal.original_price - meal.current_price).toFixed(2)}
                    </small>
                  </div>
                ) : (
                  <span className="current-price fw-bold text-primary">
                    {typeof meal.current_price === 'number' ||
                    !isNaN(parseFloat(String(meal.current_price)))
                      ? `€${parseFloat(String(meal.current_price)).toFixed(2)}`
                      : 'Price N/A'}
                  </span>
                )}
              </div>

              <div className="d-flex align-items-center">
                <button
                  ref={buttonRef}
                  className="btn btn-primary btn-sm fw-bold position-relative"
                  onClick={e => {
                    e.stopPropagation();
                    handleAddToCart();
                  }}
                  style={{
                    transform: `scale(${buttonScale})`,
                    transition: 'transform 0.15s ease',
                    backgroundColor: showSuccess ? '#27AE60' : undefined,
                    borderColor: showSuccess ? '#27AE60' : undefined,
                  }}
                >
                  <i className="fas fa-cart-plus me-1"></i>
                  {showSuccess ? 'Added!' : 'Add to Cart'}
                </button>
                {showSuccess && (
                  <div className="ms-2 text-success">
                    <i className="fas fa-check-circle"></i>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Card Footer with Additional Info */}
        <div className="card-footer bg-transparent border-top-0 pt-0">
          <div className="d-flex justify-content-between align-items-center">
            <small className="text-muted">
              <i className="fas fa-leaf me-1 text-success"></i>
              Food saved from waste
            </small>
            <small className="text-muted">
              <i className="fas fa-heart me-1 text-danger"></i>
              Fresh & delicious
            </small>
          </div>
        </div>
      </div>

      {/* Professional Meal Detail Modal */}
      {showImageModal && (
        <div className="meal-detail-modal-overlay" onClick={handleModalClose}>
          <div
            className="meal-detail-modal-content"
            onClick={e => e.stopPropagation()}
          >
            {/* Close Button */}
            <button
              className="modal-close-btn"
              onClick={handleModalClose}
              aria-label="Close"
            >
              <i className="fas fa-times close-icon"></i>
            </button>

            <div className="meal-detail-modal-body">
              {/* Left Side - Image */}
              <div className="modal-image-section">
                <div className="meal-image-container">
                  {imageUrl ? (
                    <img
                      src={imageUrl}
                      alt={meal.title}
                    />
                  ) : (
                    <div className="image-placeholder">
                      <div className="placeholder-content">
                        <i className="fas fa-utensils placeholder-icon"></i>
                        <div className="placeholder-text">No image available</div>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Right Side - Content */}
              <div className="modal-content-section">
                <div className="meal-header">
                  <h1 className="meal-title">{meal.title}</h1>
                  <p className="meal-description">{meal.description}</p>
                  
                  <div className="meal-meta">
                    {meal.restaurant && (
                      <div className="meta-item">
                        <i className="fas fa-store meta-icon"></i>
                        <div className="meta-text">
                          <span className="meta-label">Restaurant:</span>
                          {meal.restaurant.name}
                        </div>
                      </div>
                    )}
                    
                    <div className="meta-item">
                      <i className="fas fa-clock meta-icon"></i>
                      <div className="meta-text">
                        <span className="meta-label">Pickup:</span>
                        {formatPickupTime()}
                      </div>
                    </div>

                    {meal.category && (
                      <div className="meta-item">
                        <i className="fas fa-tag meta-icon"></i>
                        <div className="meta-text">
                          <span className="meta-label">Category:</span>
                          {meal.category.name}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                <div className="meal-footer">
                  <div className="price-section">
                    <div className="price-label">Price</div>
                    <div className="price-display">
                      {meal.original_price && meal.original_price > meal.current_price ? (
                        <>
                          <div className="current-price">
                            €{meal.current_price.toFixed(2)}
                          </div>
                          <div className="original-price">
                            €{meal.original_price.toFixed(2)}
                          </div>
                          <div className="savings">
                            Save €{(meal.original_price - meal.current_price).toFixed(2)} ({savingsPercentage}% OFF)
                          </div>
                        </>
                      ) : (
                        <div className="current-price">
                          €{meal.current_price.toFixed(2)}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="action-section">
                    <button
                      className="add-to-cart-btn"
                      onClick={e => {
                        e.stopPropagation();
                        handleAddToCart();
                      }}
                      style={{
                        backgroundColor: showSuccess ? '#10b981' : undefined,
                      }}
                    >
                      <i className="fas fa-cart-plus btn-icon"></i>
                      {showSuccess ? 'Added to Cart!' : 'Add to Cart'}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default MealCard;
