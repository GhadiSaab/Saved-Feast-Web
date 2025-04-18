import React from 'react';
import { Routes, Route, Link } from 'react-router-dom';
import LoginPage from './routes/LoginPage'; // Import the actual component
import SignupPage from './routes/SignupPage'; // Import the actual component
import FeedPage from './routes/FeedPage'; // Import the actual component
import Navbar from './components/Navbar'; // Import the actual Navbar
import CheckoutPage from './routes/CheckoutPage'; // Import the actual component
import OrdersPage from './routes/OrdersPage'; // Import the actual component
import ProfilePage from './routes/ProfilePage'; // Import the actual component

// Placeholder components for routes - will be implemented later
// const LoginPage = () => <div>Login Page Placeholder</div>; // Removed placeholder
// const SignupPage = () => <div>Signup Page Placeholder</div>; // Removed placeholder
// const FeedPage = () => <div>Meal Feed Page Placeholder</div>; // Removed placeholder
// const CheckoutPage = () => <div>Checkout Page Placeholder</div>; // Removed placeholder
// const OrdersPage = () => <div>Orders Page Placeholder</div>; // Removed placeholder
// const ProfilePage = () => <div>Profile Page Placeholder</div>; // Removed placeholder
const NotFoundPage = () => <div>404 Not Found</div>;

// Placeholder Navbar removed - using imported component

function App() {
  return (
    <div>
      <Navbar />
      <div className="container">
        <Routes>
          <Route path="/" element={<FeedPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/signup" element={<SignupPage />} />
          <Route path="/checkout" element={<CheckoutPage />} />
          <Route path="/orders" element={<OrdersPage />} />
          <Route path="/profile" element={<ProfilePage />} />
          <Route path="*" element={<NotFoundPage />} /> {/* Catch-all route */}
        </Routes>
      </div>
    </div>
  );
}

export default App;
