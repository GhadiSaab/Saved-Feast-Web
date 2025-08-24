// resources/js/routes/OrdersPage.tsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth'; // Import the auth helper

// Define types based on the actual API response from OrderController
interface Meal {
  id: number;
  name: string;
  // Add other meal properties if needed
}

// Export the OrderItem interface
export interface OrderItem {
  id: number;
  meal_id: number;
  quantity: number;
  price: number; // Price per item at the time of order (discounted price paid)
  original_price?: number | null; // Original price at time of order (make optional for backward compatibility)
  meal: Meal; // Nested meal object
}

// Export the Order interface
export interface Order {
  id: number;
  user_id: number;
  total_amount: number;
  status: string; // e.g., 'pending', 'completed', 'cancelled'
  created_at: string; // ISO date string
  updated_at: string; // ISO date string
  order_items: OrderItem[]; // Renamed from 'items' and matches backend
}

// Export the ApiResponse interface
export interface ApiResponse {
  status: boolean;
  message: string;
  data: Order[];
}

// Helper function to determine badge color based on status
const getStatusColor = (status: string): string => {
  switch (status.toLowerCase()) {
    case 'completed':
    case 'delivered': // Assuming 'delivered' is a possible completed status
      return 'success';
    case 'pending':
    case 'processing':
      return 'warning';
    case 'cancelled':
      return 'danger';
    default:
      return 'secondary'; // Default color for unknown statuses
  }
};

const OrdersPage: React.FC = () => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchOrders = async () => {
      if (!auth.isAuthenticated()) {
        // Redirect to login or show message if not authenticated
        setError('Please log in to view your orders.');
        setLoading(false);
        return;
      }

      setLoading(true);
      setError(null);
      try {
        // Fetch orders from the API
        const response = await axios.get<ApiResponse>('/api/orders', {
          headers: {
            Authorization: `Bearer ${auth.getToken()}`, // Add authorization token
          },
        });

        if (response.data.status && response.data.data) {
          setOrders(response.data.data);
        } else {
          setError(response.data.message || 'Failed to fetch orders.');
        }
      } catch (err: any) {
        setError(
          'An error occurred while fetching orders. Please try again later.'
        );
        console.error('Error fetching orders:', err);
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
      {!loading &&
        !error &&
        (orders.length > 0 ? (
          orders.map(order => (
            <div className="card mb-3" key={order.id}>
              <div className="card-header d-flex justify-content-between">
                {/* Use created_at for the date */}
                <span>
                  Order #{order.id} -{' '}
                  {new Date(order.created_at).toLocaleDateString()}
                </span>
                {/* Adjust badge based on actual status values */}
                <span
                  className={`badge bg-${getStatusColor(order.status)} text-capitalize`}
                >
                  {order.status}
                </span>
              </div>
              <div className="card-body">
                <ul className="list-group list-group-flush">
                  {/* Use order_items and item.meal.name */}
                  {order.order_items.map(item => (
                    <li
                      key={item.id}
                      className="list-group-item d-flex justify-content-between"
                    >
                      <span>
                        {item.quantity} x {item.meal.name}
                      </span>
                      {/* Calculate item total based on stored price */}
                      <span>${(item.quantity * item.price).toFixed(2)}</span>
                    </li>
                  ))}
                </ul>
              </div>
              <div className="card-footer">
                {/* Ensure total_amount is treated as a number before formatting */}
                <strong>
                  Total: $
                  {!isNaN(parseFloat(String(order.total_amount)))
                    ? parseFloat(String(order.total_amount)).toFixed(2)
                    : 'N/A'}
                </strong>
              </div>
            </div>
          ))
        ) : (
          <p className="text-center text-muted">
            You haven't placed any orders yet.
          </p>
        ))}
    </div>
  );
};

export default OrdersPage;
