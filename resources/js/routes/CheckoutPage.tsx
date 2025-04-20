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

    // Options for Stripe Elements (customize appearance)
    // const options = {
    //   clientSecret: 'YOUR_PAYMENT_INTENT_CLIENT_SECRET', // Fetch this from your backend
    // };

    return (
        <div>
            <h1>Checkout</h1>
            <p>Review your order and complete the payment.</p>

            {/*
            Stripe Elements will go here.
            Wrap the payment form with <Elements stripe={stripePromise} options={options}>
            <YourCheckoutForm />
            </Elements>
            */}

            <div className="alert alert-info mt-4">
                <strong>Note:</strong> Stripe integration is planned. Payment form will appear here.
            </div>

            {/* Order Summary Section */}
            <div className="card mt-4">
                <div className="card-header">Order Summary</div>
                <div className="card-body">
                    {cartItems.length === 0 ? (
                        <p className="text-muted">Your cart is empty.</p>
                    ) : (
                        <ul className="list-group list-group-flush mb-3">
                            {cartItems.map(item => (
                                <li key={item.id} className="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{item.name}</strong>
                                        <br />
                                        <small className="text-muted">
                                            ${item.price.toFixed(2)} each
                                        </small>
                                    </div>
                                    <div className="d-flex align-items-center">
                                        <input
                                            type="number"
                                            min="1"
                                            value={item.quantity}
                                            onChange={(e) => updateQuantity(item.id, parseInt(e.target.value) || 1)}
                                            className="form-control form-control-sm me-2"
                                            style={{ width: '60px' }}
                                            aria-label={`Quantity for ${item.name}`}
                                        />
                                        <span>
                                            ${(item.price * item.quantity).toFixed(2)}
                                        </span>
                                        <button
                                            onClick={() => removeFromCart(item.id)}
                                            className="btn btn-outline-danger btn-sm ms-3"
                                            aria-label={`Remove ${item.name}`}
                                        >
                                            &times; {/* Multiplication sign for remove icon */}
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    {cartItems.length > 0 && (
                        <>
                            <hr />
                            <div className="d-flex justify-content-end mb-3">
                                <h5><strong>Total: ${getCartTotal().toFixed(2)}</strong></h5>
                            </div>
                            {/* TODO: Add Stripe Payment Element Here */}
                            <div className="alert alert-info">
                                Stripe payment form will be integrated here.
                            </div>
                            <button
                                className="btn btn-success w-100"
                                disabled={isPlacingOrder || cartItems.length === 0} // Disable if placing or cart empty
                                onClick={handlePlaceOrder} // Attach the handler
                            >
                                {isPlacingOrder ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Placing Order...
                                    </>
                                ) : (
                                    'Place Order' // Updated button text
                                )}
                            </button>
                            {error && <div className="alert alert-danger mt-3">{error}</div>}
                        </>
                    )}
                </div> {/* Closing card-body */}
            </div> {/* Closing card */}
        </div> /* Closing top-level div */
    );
};

export default CheckoutPage;
