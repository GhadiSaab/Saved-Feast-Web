// resources/js/components/MealCard.tsx
import React from 'react';

// Re-define or import the Meal type (ensure consistency with FeedPage.tsx)
interface Meal {
    id: number;
    name: string;
    description: string;
    price: number;
    image_url?: string;
    restaurant?: {
        name: string;
    };
    // Add other relevant fields as needed
}

interface MealCardProps {
    meal: Meal;
    // Add an onAddToCart function prop later for cart functionality
    // onAddToCart: (meal: Meal) => void;
}

const MealCard: React.FC<MealCardProps> = ({ meal /*, onAddToCart */ }) => {
    const handleAddToCart = () => {
        console.log(`Adding ${meal.name} to cart (ID: ${meal.id})`);
        // Call onAddToCart(meal) when implemented
    };

    return (
        <div className="card h-100 shadow-sm"> {/* Use h-100 for equal height cards in a row */}
            {meal.image_url ? (
                <img
                    src={meal.image_url}
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
                <p className="card-text flex-grow-1"> {/* Allow description to grow */}
                    {meal.description.substring(0, 80)}{meal.description.length > 80 ? '...' : ''}
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
