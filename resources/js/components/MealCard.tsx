// resources/js/components/MealCard.tsx
import React, { useState } from 'react';
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

    const handleAddToCart = () => {
        if (auth.isAuthenticated()) {
            // Ensure current_price is a valid number before adding
            const currentPrice = typeof meal.current_price === 'number' ? meal.current_price : parseFloat(String(meal.current_price));
            if (!isNaN(currentPrice)) {
                // Pass title and current_price to addToCart
                addToCart({ id: meal.id, name: meal.title, price: currentPrice });
                console.log(`Added ${meal.title} to cart (ID: ${meal.id})`);
                // Optionally: Add user feedback (e.g., toast notification)
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
            const savings = ((meal.original_price - meal.current_price) / meal.original_price) * 100;
            return Math.round(savings);
        }
        return 0;
    };

    // Format pickup time
    const formatPickupTime = () => {
        if (meal.available_from && meal.available_until) {
            const fromTime = new Date(meal.available_from).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            const untilTime = new Date(meal.available_until).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            return `${fromTime} - ${untilTime}`;
        }
        return 'Time TBD';
    };

    // Construct the full image URL if the path is relative
    const imageUrl = meal.image ? `${meal.image}` : null;

    const savingsPercentage = calculateSavings();

    const handleCardClick = () => {
        if (imageUrl) {
            setShowImageModal(true);
        }
    };

    const handleModalClose = () => {
        setShowImageModal(false);
    };

    return (
        <>
            <div className="card h-100 meal-card" onClick={handleCardClick} style={{ cursor: imageUrl ? 'pointer' : 'default' }}>
                {/* Image Placeholder Section */}
                <div className="card-image-placeholder position-relative">
                    <div className="image-placeholder bg-gradient-primary d-flex align-items-center justify-content-center">
                        <div className="text-center">
                            <i className="fas fa-utensils fa-2x text-white opacity-75 mb-2"></i>
                            {imageUrl && (
                                <div className="text-white opacity-75 small">
                                    <i className="fas fa-eye me-1"></i>
                                    Click to view
                                </div>
                            )}
                        </div>
                    </div>
                    
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
                        <h5 className="card-title fw-bold mb-1 text-primary">{meal.title}</h5>
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
                                meal.description.length > 120 
                                    ? `${meal.description.substring(0, 117)}...` 
                                    : meal.description
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
                            <div className="price-display">
                                {meal.original_price && meal.original_price > meal.current_price ? (
                                    <div className="d-flex align-items-baseline">
                                        <span className="current-price me-2 fw-bold">
                                            €{meal.current_price.toFixed(2)}
                                        </span>
                                        <span className="original-price text-muted">
                                            <del>€{meal.original_price.toFixed(2)}</del>
                                        </span>
                                    </div>
                                ) : (
                                    <span className="current-price fw-bold">
                                        {typeof meal.current_price === 'number' || !isNaN(parseFloat(String(meal.current_price)))
                                            ? `€${parseFloat(String(meal.current_price)).toFixed(2)}`
                                            : 'Price N/A'}
                                    </span>
                                )}
                            </div>
                            
                            <button 
                                className="btn btn-primary btn-sm fw-bold" 
                                onClick={(e) => {
                                    e.stopPropagation();
                                    handleAddToCart();
                                }}
                            >
                                <i className="fas fa-cart-plus me-1"></i>
                                Add to Cart
                            </button>
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

            {/* Image Modal */}
            {showImageModal && imageUrl && (
                <div className="image-modal-overlay" onClick={handleModalClose}>
                    <div className="image-modal-content" onClick={(e) => e.stopPropagation()}>
                        <div className="image-modal-header">
                            <h5 className="text-white mb-0">{meal.title}</h5>
                            <button 
                                className="btn-close btn-close-white" 
                                onClick={handleModalClose}
                                aria-label="Close"
                            ></button>
                        </div>
                        <div className="image-modal-body">
                            <img 
                                src={imageUrl} 
                                alt={meal.title} 
                                className="img-fluid rounded"
                            />
                        </div>
                        <div className="image-modal-footer">
                            <p className="text-white mb-0 small">
                                <i className="fas fa-store me-1"></i>
                                {meal.restaurant?.name}
                            </p>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};

export default MealCard;
