// resources/js/components/MealCard.tsx
import React, { useState, useRef, useEffect } from 'react';
import { useCart } from '../context/CartContext';
import { useNavigate } from 'react-router-dom';
import auth from '../auth';

interface Meal {
  id: number;
  title: string;
  description: string;
  current_price: number;
  original_price?: number | null;
  image?: string | null;
  image_url?: string | null;
  available_from: string;
  available_until: string;
  restaurant?: {
    name: string;
  };
  category?: {
    name: string;
  };
}

interface MealCardProps {
  meal: Meal;
}

const currencyFormatter = new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const parseToNumber = (
  value: number | string | null | undefined
): number | null => {
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  return null;
};

const getAssetBaseUrl = (): string => {
  const env = import.meta.env as Record<string, string | undefined>;
  const configured = env['VITE_ASSET_URL'] || env['VITE_API_URL'] || '';

  if (configured) {
    return configured.replace(/\/$/, '');
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin;
  }

  return '';
};

const resolveImageUrl = (
  image?: string | null,
  imageUrl?: string | null
): string | null => {
  const candidate = imageUrl || image;

  if (!candidate || candidate.trim() === '') {
    return null;
  }

  if (/^https?:\/\//i.test(candidate)) {
    return candidate;
  }

  if (candidate.startsWith('//')) {
    const protocol =
      typeof window !== 'undefined' && window.location?.protocol
        ? window.location.protocol
        : 'https:';
    return `${protocol}${candidate}`;
  }

  const baseUrl = getAssetBaseUrl();
  const normalizedPath = candidate.startsWith('/')
    ? candidate
    : `/${candidate}`;

  return baseUrl ? `${baseUrl}${normalizedPath}` : normalizedPath;
};

const formatCurrency = (value: number | null): string | null => {
  if (value == null) {
    return null;
  }

  return currencyFormatter.format(value);
};

const MealCard: React.FC<MealCardProps> = ({ meal }) => {
  const { addToCart } = useCart();
  const navigate = useNavigate();
  const [showImageModal, setShowImageModal] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [buttonScale, setButtonScale] = useState(1);
  const timeoutRefs = useRef<Array<ReturnType<typeof setTimeout>>>([]);

  useEffect(() => {
    return () => {
      timeoutRefs.current.forEach(timeout => clearTimeout(timeout));
    };
  }, []);

  const animateButton = () => {
    setButtonScale(0.95);
    const timeout = setTimeout(() => setButtonScale(1), 150);
    timeoutRefs.current.push(timeout);
  };

  const showSuccessFeedback = () => {
    setShowSuccess(true);
    const timeout = setTimeout(() => {
      setShowSuccess(false);
    }, 1500);
    timeoutRefs.current.push(timeout);
  };

  const currentPriceValue = parseToNumber(meal.current_price);
  const originalPriceValue = parseToNumber(meal.original_price ?? null);
  const hasDiscount =
    originalPriceValue != null &&
    currentPriceValue != null &&
    originalPriceValue > currentPriceValue;
  const savingsAmount = hasDiscount
    ? originalPriceValue - currentPriceValue
    : null;
  const savingsPercentage =
    hasDiscount && originalPriceValue
      ? Math.round((savingsAmount! / originalPriceValue) * 100)
      : 0;

  const formattedCurrentPrice = formatCurrency(currentPriceValue);
  const formattedOriginalPrice = formatCurrency(originalPriceValue);
  const formattedSavings = formatCurrency(savingsAmount);

  const handleAddToCart = () => {
    animateButton();

    if (auth.isAuthenticated()) {
      if (currentPriceValue != null) {
        addToCart({ id: meal.id, name: meal.title, price: currentPriceValue });
        console.log(`Added ${meal.title} to cart (ID: ${meal.id})`);
        showSuccessFeedback();
      } else {
        console.error(`Invalid price for meal: ${meal.title}`);
      }
    } else {
      navigate('/login');
    }
  };

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

  const imageUrl = resolveImageUrl(meal.image, meal.image_url);

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
        <div className="card-image-placeholder position-relative">
          {imageUrl ? (
            <div className="meal-image-container">
              <img
                src={imageUrl}
                alt={meal.title}
                className="card-img-top"
                style={{ height: '150px', objectFit: 'cover' }}
                onError={e => {
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

          {savingsPercentage > 0 && (
            <div className="savings-badge position-absolute top-0 start-0 m-2">
              <span className="badge bg-success fs-6 fw-bold">
                <i className="fas fa-percentage me-1"></i>
                {savingsPercentage}% OFF
              </span>
            </div>
          )}

          {meal.restaurant && (
            <div className="restaurant-badge position-absolute top-0 end-0 m-2">
              <span className="badge bg-primary fs-6 fw-bold">
                <i className="fas fa-store me-1"></i>
                {meal.restaurant.name}
              </span>
            </div>
          )}
        </div>

        <div className="card-body d-flex flex-column">
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

          <div className="mb-3">
            <div className="pickup-time d-inline-block">
              <i className="fas fa-clock me-2"></i>
              <strong>Pickup:</strong> {formatPickupTime()}
            </div>
          </div>

          <div className="mt-auto">
            <div className="d-flex justify-content-between align-items-center">
              <div className="price-display flex-grow-1 me-3">
                {hasDiscount ? (
                  <div className="d-flex flex-column">
                    <div className="d-flex align-items-baseline">
                      <span className="current-price fw-bold text-success me-2">
                        {formattedCurrentPrice ?? 'Price N/A'}
                      </span>
                      {formattedOriginalPrice && (
                        <span className="original-price text-muted small">
                          <del>{formattedOriginalPrice}</del>
                        </span>
                      )}
                    </div>
                    {formattedSavings && (
                      <small className="text-success fw-bold">
                        {`Save ${formattedSavings}`}
                      </small>
                    )}
                  </div>
                ) : (
                  <span className="current-price fw-bold text-primary">
                    {formattedCurrentPrice ?? 'Price N/A'}
                  </span>
                )}
              </div>

              <div className="d-flex align-items-center">
                <button
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

      {showImageModal && (
        <div className="meal-detail-modal-overlay" onClick={handleModalClose}>
          <div
            className="meal-detail-modal-content"
            onClick={e => e.stopPropagation()}
          >
            <button
              className="modal-close-btn"
              onClick={handleModalClose}
              aria-label="Close"
            >
              <i className="fas fa-times close-icon"></i>
            </button>

            <div className="meal-detail-modal-body">
              <div className="modal-image-section">
                <div className="meal-image-container">
                  {imageUrl ? (
                    <img src={imageUrl} alt={meal.title} />
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
                      {hasDiscount ? (
                        <>
                          <div className="current-price">
                            {formattedCurrentPrice ?? 'Price N/A'}
                          </div>
                          {formattedOriginalPrice && (
                            <div className="original-price">
                              {formattedOriginalPrice}
                            </div>
                          )}
                          {formattedSavings && (
                            <div className="savings">
                              {`Save ${formattedSavings}`}
                              {savingsPercentage > 0
                                ? ` (${savingsPercentage}% OFF)`
                                : ''}
                            </div>
                          )}
                        </>
                      ) : (
                        <div className="current-price">
                          {formattedCurrentPrice ?? 'Price N/A'}
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
