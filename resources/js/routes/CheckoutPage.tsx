// resources/js/routes/CheckoutPage.tsx
import React, { useState } from 'react'; // Added useState for potential loading/error states later
import { useCart } from '../context/CartContext'; // Import useCart
import axios from 'axios'; // Import axios for placing the order later
import auth from '../auth'; // Import auth for getting token later
import { useNavigate } from 'react-router-dom'; // Import for redirecting after order

// TODO: run "npm i @stripe/react-stripe-js @stripe/stripe-js" (already added to package.json)
// import { loadStripe } from '@stripe/stripe-js';
// import { Elements } from '@stripe/react-stripe-js';

// const stripePromise = loadStripe('YOUR_STRIPE_PUBLISHABLE_KEY'); // Replace with your key

const CheckoutPage: React.FC = () => {
    const { cartItems, removeFromCart, updateQuantity, getCartTotal, clearCart } = useCart(); // Get cart data and functions
    const navigate = useNavigate(); // Hook for navigation
    const [isPlacingOrder, setIsPlacingOrder] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Function to handle placing the order
    const handlePlaceOrder = async () => {
        if (cartItems.length === 0 || isPlacingOrder) {
            return; // Don't place order if cart is empty or already processing
        }

        setIsPlacingOrder(true);
        setError(null);

        // Prepare data for the API
        const orderData = {
            total_amount: getCartTotal(),
            // Map cart items to the format expected by the backend API
            order_items: cartItems.map(item => ({
                meal_id: item.id,
                quantity: item.quantity,
                price: item.price // Send the price per item at time of order
            })),
            // Add other required fields if any (e.g., delivery address - not implemented yet)
        };

        try {
            const response = await axios.post('/api/orders', orderData, {
                headers: {
                    Authorization: `Bearer ${auth.getToken()}`, // Include auth token
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            });

            if (response.data.status) {
                console.log('Order placed successfully:', response.data.data);
                clearCart(); // Clear the cart after successful order
                // Optionally: Show a success message/toast
                navigate('/orders'); // Redirect to the orders page
            } else {
                setError(response.data.message || 'Failed to place order.');
            }
        } catch (err: any) {
            console.error("Error placing order:", err);
            setError(err.response?.data?.message || 'An error occurred while placing the order.');
        } finally {
            setIsPlacingOrder(false);
        }
    };

    // Calculate total savings
    const calculateTotalSavings = () => {
        // This would need to be calculated based on original vs current prices
        // For now, we'll estimate 30% savings
        return getCartTotal() * 0.3;
    };

    return (
        <div className="checkout-page">
            <div className="container">
                <div className="row">
                    {/* Main Checkout Content */}
                    <div className="col-lg-8">
                        <div className="checkout-header mb-4">
                            <h1 className="page-title">
                                <i className="fas fa-shopping-cart me-3"></i>
                                Checkout
                            </h1>
                            <p className="text-muted">
                                Review your order and complete the payment to save food and money.
                            </p>
                        </div>

                        {/* Order Summary Card */}
                        <div className="card shadow-sm mb-4">
                            <div className="card-header bg-primary text-white">
                                <h5 className="mb-0">
                                    <i className="fas fa-list-alt me-2"></i>
                                    Order Summary
                                </h5>
                            </div>
                            <div className="card-body">
                                {cartItems.length === 0 ? (
                                    <div className="text-center py-5">
                                        <i className="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <h5 className="text-muted">Your cart is empty</h5>
                                        <p className="text-muted">Add some delicious meals to get started!</p>
                                        <button 
                                            className="btn btn-primary"
                                            onClick={() => navigate('/')}
                                        >
                                            <i className="fas fa-utensils me-2"></i>
                                            Browse Meals
                                        </button>
                                    </div>
                                ) : (
                                    <div className="order-items">
                                        {cartItems.map((item, index) => (
                                            <div 
                                                key={item.id} 
                                                className={`order-item d-flex justify-content-between align-items-center p-3 ${index !== cartItems.length - 1 ? 'border-bottom' : ''}`}
                                            >
                                                <div className="item-details flex-grow-1">
                                                    <h6 className="mb-1 fw-bold">{item.name}</h6>
                                                    <div className="d-flex align-items-center">
                                                        <span className="text-success fw-bold me-2">
                                                            €{item.price.toFixed(2)}
                                                        </span>
                                                        <small className="text-muted">
                                                            per item
                                                        </small>
                                                    </div>
                                                </div>
                                                <div className="item-controls d-flex align-items-center">
                                                    <div className="quantity-controls me-3">
                                                        <label className="form-label small mb-1">Qty:</label>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            max="10"
                                                            value={item.quantity}
                                                            onChange={(e) => updateQuantity(item.id, parseInt(e.target.value) || 1)}
                                                            className="form-control form-control-sm"
                                                            style={{ width: '70px' }}
                                                            aria-label={`Quantity for ${item.name}`}
                                                        />
                                                    </div>
                                                    <div className="item-total me-3 text-end">
                                                        <div className="fw-bold">
                                                            €{(item.price * item.quantity).toFixed(2)}
                                                        </div>
                                                    </div>
                                                    <button
                                                        onClick={() => removeFromCart(item.id)}
                                                        className="btn btn-outline-danger btn-sm"
                                                        aria-label={`Remove ${item.name}`}
                                                        title="Remove item"
                                                    >
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Payment Information */}
                        <div className="card shadow-sm mb-4">
                            <div className="card-header">
                                <h5 className="mb-0">
                                    <i className="fas fa-credit-card me-2"></i>
                                    Payment Information
                                </h5>
                            </div>
                            <div className="card-body">
                                <div className="alert alert-info">
                                    <i className="fas fa-info-circle me-2"></i>
                                    <strong>Payment Integration Coming Soon!</strong> Stripe payment processing will be integrated here for secure transactions.
                                </div>
                                
                                {/* Placeholder for Stripe Elements */}
                                <div className="payment-placeholder p-4 border rounded bg-light text-center">
                                    <i className="fas fa-credit-card fa-2x text-muted mb-3"></i>
                                    <p className="text-muted mb-0">Secure payment form will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Order Summary Sidebar */}
                    <div className="col-lg-4">
                        <div className="order-summary-sidebar">
                            <div className="card shadow-sm sticky-top" style={{ top: '2rem' }}>
                                <div className="card-header bg-success text-white">
                                    <h5 className="mb-0">
                                        <i className="fas fa-receipt me-2"></i>
                                        Order Total
                                    </h5>
                                </div>
                                <div className="card-body">
                                    {cartItems.length > 0 ? (
                                        <>
                                            {/* Savings Summary */}
                                            <div className="savings-summary mb-3 p-3 bg-light rounded">
                                                <div className="d-flex justify-content-between align-items-center mb-2">
                                                    <span className="text-success">
                                                        <i className="fas fa-leaf me-1"></i>
                                                        Food Saved:
                                                    </span>
                                                    <span className="fw-bold text-success">
                                                        {cartItems.length} {cartItems.length === 1 ? 'meal' : 'meals'}
                                                    </span>
                                                </div>
                                                <div className="d-flex justify-content-between align-items-center">
                                                    <span className="text-success">
                                                        <i className="fas fa-euro-sign me-1"></i>
                                                        Money Saved:
                                                    </span>
                                                    <span className="fw-bold text-success">
                                                        ~€{calculateTotalSavings().toFixed(2)}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Price Breakdown */}
                                            <div className="price-breakdown">
                                                <div className="d-flex justify-content-between mb-2">
                                                    <span>Subtotal:</span>
                                                    <span>€{getCartTotal().toFixed(2)}</span>
                                                </div>
                                                <div className="d-flex justify-content-between mb-2">
                                                    <span>Service Fee:</span>
                                                    <span>€0.00</span>
                                                </div>
                                                <div className="d-flex justify-content-between mb-2">
                                                    <span>Tax:</span>
                                                    <span>€0.00</span>
                                                </div>
                                                <hr />
                                                <div className="d-flex justify-content-between fw-bold fs-5">
                                                    <span>Total:</span>
                                                    <span className="text-primary">€{getCartTotal().toFixed(2)}</span>
                                                </div>
                                            </div>

                                            {/* Place Order Button */}
                                            <button
                                                className="btn btn-success w-100 mt-3"
                                                disabled={isPlacingOrder || cartItems.length === 0}
                                                onClick={handlePlaceOrder}
                                            >
                                                {isPlacingOrder ? (
                                                    <>
                                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                        Processing Order...
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="fas fa-check-circle me-2"></i>
                                                        Place Order
                                                    </>
                                                )}
                                            </button>

                                            {error && (
                                                <div className="alert alert-danger mt-3">
                                                    <i className="fas fa-exclamation-triangle me-2"></i>
                                                    {error}
                                                </div>
                                            )}
                                        </>
                                    ) : (
                                        <div className="text-center py-4">
                                            <i className="fas fa-shopping-bag fa-2x text-muted mb-3"></i>
                                            <p className="text-muted">No items in cart</p>
                                        </div>
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

export default CheckoutPage;
