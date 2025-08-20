import React from 'react';
import { Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import ProtectedRoute from './components/ProtectedRoute';
import LoadingSpinner from './components/LoadingSpinner';
import Navbar from './components/Navbar';

// Import pages
import LoginPage from './routes/LoginPage';
import SignupPage from './routes/SignupPage';
import FeedPage from './routes/FeedPage';
import CheckoutPage from './routes/CheckoutPage';
import OrdersPage from './routes/OrdersPage';
import ProfilePage from './routes/ProfilePage';
import RestaurantApplicationPage from './routes/RestaurantApplicationPage';
import RestaurantDashboardPage from './routes/RestaurantDashboardPage';

// 404 Page
const NotFoundPage = () => (
    <div className="container mt-5">
        <div className="row justify-content-center">
            <div className="col-md-6 text-center">
                <h1 className="display-1">404</h1>
                <h2>Page Not Found</h2>
                <p className="text-muted">The page you're looking for doesn't exist.</p>
                <a href="/" className="btn btn-primary">Go Home</a>
            </div>
        </div>
    </div>
);

function App() {
    return (
        <AuthProvider>
            <CartProvider>
                <div className="app">
                    <Navbar />
                    <main className="container mt-4">
                        <Routes>
                            {/* Public routes */}
                            <Route path="/" element={<FeedPage />} />
                            <Route 
                                path="/login" 
                                element={
                                    <ProtectedRoute requireAuth={false}>
                                        <LoginPage />
                                    </ProtectedRoute>
                                } 
                            />
                            <Route 
                                path="/signup" 
                                element={
                                    <ProtectedRoute requireAuth={false}>
                                        <SignupPage />
                                    </ProtectedRoute>
                                } 
                            />
                            <Route path="/partner-with-us" element={<RestaurantApplicationPage />} />

                            {/* Protected routes */}
                            <Route 
                                path="/checkout" 
                                element={
                                    <ProtectedRoute>
                                        <CheckoutPage />
                                    </ProtectedRoute>
                                } 
                            />
                            <Route 
                                path="/orders" 
                                element={
                                    <ProtectedRoute>
                                        <OrdersPage />
                                    </ProtectedRoute>
                                } 
                            />
                            <Route 
                                path="/profile" 
                                element={
                                    <ProtectedRoute>
                                        <ProfilePage />
                                    </ProtectedRoute>
                                } 
                            />

                            {/* Provider routes */}
                            <Route 
                                path="/provider/dashboard" 
                                element={
                                    <ProtectedRoute requireRole="provider">
                                        <RestaurantDashboardPage />
                                    </ProtectedRoute>
                                } 
                            />

                            {/* 404 route */}
                            <Route path="*" element={<NotFoundPage />} />
                        </Routes>
                    </main>
                </div>
            </CartProvider>
        </AuthProvider>
    );
}

export default App;
