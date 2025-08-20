// resources/js/components/MealCard.tsx
import React from 'react';
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

    // Construct the full image URL if the path is relative
    // Assumes the backend provides a URL like '/storage/meals/image.jpg'
    // If it provides just 'meals/image.jpg', you might need process.env.REACT_APP_API_URL or similar
    const imageUrl = meal.image ? `${meal.image}` : null; // Use the image field directly

    return (
        <div className="card h-100 shadow-sm"> {/* Use h-100 for equal height cards in a row */}
            {imageUrl ? ( // Use the derived imageUrl
                <img
                    src={imageUrl} // Use the derived imageUrl
                    className="card-img-top"
                    alt={meal.title} // Use title for alt text
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
                {/* Top section for Title and Restaurant */}
                <div className="mb-2"> {/* Add some bottom margin */}
                    <h5 className="card-title fw-bold">{meal.title}</h5> {/* Make title bold */}
                    {meal.restaurant && (
                        <p className="card-subtitle text-muted">
                            <small>From: {meal.restaurant.name}</small>
                        </p>
                    )}
                </div>

                {/* Middle section for Description and Pickup */}
                <div className="flex-grow-1 mb-3"> {/* Allow this section to grow, add bottom margin */}
                    <p className="card-text small mb-2"> {/* Smaller text, add bottom margin */}
                        {meal.description ? (meal.description.length > 100 ? `${meal.description.substring(0, 97)}...` : meal.description) : <span className="text-muted">No description available.</span>}
                    </p>
                    {/* Display Pickup Time Window */}
                    <p className="card-text mb-0">
                        <small className="text-muted">
                            Pickup: {
                                meal.available_from && meal.available_until
                                ? `${new Date(meal.available_from).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} - ${new Date(meal.available_until).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
                                : '(Time window unavailable)'
                            }
                        </small>
                    </p>
                </div>

                {/* Footer section for Price and Button */}
                <div className="mt-auto d-flex justify-content-between align-items-center pt-2 border-top"> {/* Keep top padding and border */}
                    <div className="price-display lh-1"> {/* Keep line height 1 */}
                        {/* Display current price and original price if available and different */}
                        {meal.original_price && meal.original_price > meal.current_price ? (
                            <>
                                <strong className="fs-5 text-danger me-2"> {/* Larger discounted price */}
                                    €{meal.current_price.toFixed(2)}
                                </strong>
                                <small className="text-muted align-baseline"> {/* Align baseline */}
                                    <del>€{meal.original_price.toFixed(2)}</del>
                                </small>
                            </>
                        ) : (
                            /* Otherwise, just display the current price */
                            <strong className="fs-5"> {/* Larger standard price */}
                                {typeof meal.current_price === 'number' || !isNaN(parseFloat(String(meal.current_price)))
                                    ? `€${parseFloat(String(meal.current_price)).toFixed(2)}`
                                    : 'Price N/A'}
                            </strong>
                        )}
                    </div>
                    <button className="btn btn-primary btn-sm" onClick={handleAddToCart}>
                        Add to Cart
                    </button> {/* Keep button */}
                </div>
            </div>
        </div>
    );
};

export default MealCard;
