// resources/js/routes/CheckoutPage.tsx
import React from 'react';
// TODO: run "npm i @stripe/react-stripe-js @stripe/stripe-js" (already added to package.json)
// import { loadStripe } from '@stripe/stripe-js';
// import { Elements } from '@stripe/react-stripe-js';

// const stripePromise = loadStripe('YOUR_STRIPE_PUBLISHABLE_KEY'); // Replace with your key

const CheckoutPage: React.FC = () => {
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

            {/* Placeholder for order summary */}
            <div className="card mt-4">
                <div className="card-header">Order Summary</div>
                <div className="card-body">
                    <p>Item 1 - $10.00</p>
                    <p>Item 2 - $5.50</p>
                    <hr />
                    <p><strong>Total: $15.50</strong></p>
                    <button className="btn btn-success" disabled>Place Order (Stripe Pending)</button>
                </div>
            </div>
        </div>
    );
};

export default CheckoutPage;
