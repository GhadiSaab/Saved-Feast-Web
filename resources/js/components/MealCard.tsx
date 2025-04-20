// resources/js/components/MealCard.tsx
import React from 'react';
import { useCart } from '../context/CartContext'; // Import the useCart hook
import { useNavigate } from 'react-router-dom'; // Import useNavigate
import auth from '../auth'; // Import the auth helper

// Re-define or import the Meal type (ensure consistency with FeedPage.tsx)
interface Meal {
    id: number;
    name: string;
    description: string;
    price: number;
    image_url?: string; // Reverted back to image_url
    restaurant?: {
        name: string;
    };
    // Add other relevant fields as needed
}

interface MealCardProps {
    meal: Meal;
    // Removed onAddToCart prop - using context instead
}

const MealCard: React.FC<MealCardProps> = ({ meal }) => { // Removed onAddToCart from props
    const { addToCart } = useCart(); // Get addToCart function from context
    const navigate = useNavigate(); // Get navigate function

    const handleAddToCart = () => {
        if (auth.isAuthenticated()) {
            // Ensure price is a valid number before adding
            const price = typeof meal.price === 'number' ? meal.price : parseFloat(String(meal.price));
            if (!isNaN(price)) {
                addToCart({ id: meal.id, name: meal.name, price: price });
                console.log(`Added ${meal.name} to cart (ID: ${meal.id})`);
                // Optionally: Add user feedback (e.g., toast notification)
            } else {
                console.error(`Invalid price for meal: ${meal.name}`);
                // Optionally: Show an error to the user
            }
        } else {
            // Redirect to login page if not authenticated
            navigate('/login');
        }
    };

    return (
        <div className="card h-100 shadow-sm"> {/* Use h-100 for equal height cards in a row */}
            {meal.image_url ? ( // Reverted back to image_url
                <img
                    src={meal.image_url} // Reverted back to image_url
                    className="card-img-top"
                    alt={meal.name}
                    style={{ height: '200px', objectFit: 'cover' }} // Consistent image height
                />
            ) : (
                <div
                    className="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                    style={{ height: '200px' }}
                >
                    <span className="text-light">No Image</span>
                </div>
            )}
            <div className="card-body d-flex flex-column"> {/* Flex column for footer alignment */}
                <h5 className="card-title">{meal.name}</h5>
                {meal.restaurant && (
                    <p className="card-subtitle mb-2 text-muted">
                        <small>{meal.restaurant.name}</small>
                    </p>
                )}
                <p className="card-text flex-grow-1 mb-2"> {/* Allow description to grow, add small bottom margin */}
                    {meal.description.substring(0, 80)}{meal.description.length > 80 ? '...' : ''}
                </p>
                 {/* Pickup Time Placeholder - Requires backend data */}
                 <p className="card-text mb-2">
                    <small className="text-muted">Pickup: (Time window unavailable)</small>
                 </p>
                <div className="mt-auto d-flex justify-content-between align-items-center"> {/* Push price and button to bottom */}
                    <p className="card-text mb-0"> {/* Remove bottom margin */}
                        <strong>
                            {/* Ensure price is a number before formatting */}
                            {typeof meal.price === 'number' || !isNaN(parseFloat(String(meal.price)))
                                ? `$${parseFloat(String(meal.price)).toFixed(2)}`
                                : 'Price N/A'}
                        </strong>
                    </p>
                    <button className="btn btn-primary btn-sm" onClick={handleAddToCart}>
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    );
};

export default MealCard;
