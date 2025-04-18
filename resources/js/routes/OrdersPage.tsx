// resources/js/routes/OrdersPage.tsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth'; // Assuming auth helper is needed to fetch user-specific orders

// Define a type for Order data (adjust based on actual API response)
interface OrderItem {
    id: number;
    meal_name: string; // Example field
    quantity: number;
    price: number;
}

interface Order {
    id: number;
    order_date: string; // Or Date object
    status: string;
    total_amount: number;
    items: OrderItem[]; // Assuming items are nested
    // Add other relevant fields like restaurant name, etc.
}

const OrdersPage: React.FC = () => {
    const [orders, setOrders] = useState<Order[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchOrders = async () => {
            if (!auth.isAuthenticated()) {
                // Redirect to login or show message if not authenticated
                setError("Please log in to view your orders.");
                setLoading(false);
                return;
            }

            setLoading(true);
            setError(null);
            try {
                // TODO: Define and implement the actual API endpoint for fetching user orders
                // Example: const response = await axios.get<{ data: Order[] }>('/api/orders');
                // setOrders(response.data.data || response.data);

                // Placeholder data for now:
                await new Promise(resolve => setTimeout(resolve, 500)); // Simulate network delay
                setOrders([
                    { id: 1, order_date: '2025-04-17', status: 'Delivered', total_amount: 15.50, items: [{ id: 101, meal_name: 'Sample Meal 1', quantity: 1, price: 10.00 }, { id: 102, meal_name: 'Sample Meal 2', quantity: 1, price: 5.50 }] },
                    { id: 2, order_date: '2025-04-18', status: 'Processing', total_amount: 8.00, items: [{ id: 103, meal_name: 'Another Meal', quantity: 1, price: 8.00 }] },
                ]);

            } catch (err: any) {
                setError('Failed to fetch orders. Please try again later.');
                console.error("Error fetching orders:", err);
            } finally {
                setLoading(false);
            }
        };

        fetchOrders();
    }, []);

    return (
        <div>
            <h1>My Orders</h1>
            {loading && (
                <div className="d-flex justify-content-center">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            )}
            {error && <div className="alert alert-danger">{error}</div>}
            {!loading && !error && (
                orders.length > 0 ? (
                    orders.map(order => (
                        <div className="card mb-3" key={order.id}>
                            <div className="card-header d-flex justify-content-between">
                                <span>Order #{order.id} - {new Date(order.order_date).toLocaleDateString()}</span>
                                <span className={`badge bg-${order.status === 'Delivered' ? 'success' : 'warning'}`}>{order.status}</span>
                            </div>
                            <div className="card-body">
                                <ul className="list-group list-group-flush">
                                    {order.items.map(item => (
                                        <li key={item.id} className="list-group-item d-flex justify-content-between">
                                            <span>{item.quantity} x {item.meal_name}</span>
                                            <span>${(item.quantity * item.price).toFixed(2)}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="card-footer">
                                <strong>Total: ${order.total_amount.toFixed(2)}</strong>
                            </div>
                        </div>
                    ))
                ) : (
                    <p className="text-center text-muted">You haven't placed any orders yet.</p>
                )
            )}
        </div>
    );
};

export default OrdersPage;
