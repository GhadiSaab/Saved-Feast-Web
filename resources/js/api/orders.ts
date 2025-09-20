import axios from 'axios';
import {
  OrdersResponse,
  OrderResponse,
  PickupCodeResponse,
  CreateOrderRequest,
  CancelOrderRequest,
  AcceptOrderRequest,
  CompleteOrderRequest,
  CancelOrderByRestaurantRequest,
  OrderFilters,
  OrderStatsResponse,
} from '../types/orders';

const API_BASE = '/api';

// Customer order management
export const orderApi = {
  // Get customer's orders
  getMyOrders: async (filters?: OrderFilters): Promise<OrdersResponse> => {
    const params = new URLSearchParams();

    if (filters?.status) {
      if (Array.isArray(filters.status)) {
        filters.status.forEach(status => params.append('status[]', status));
      } else {
        params.append('status', filters.status);
      }
    }

    if (filters?.date_from) params.append('date_from', filters.date_from);
    if (filters?.date_to) params.append('date_to', filters.date_to);
    if (filters?.per_page)
      params.append('per_page', filters.per_page.toString());

    const response = await axios.get(
      `${API_BASE}/me/orders?${params.toString()}`
    );
    return response.data;
  },

  // Get order details
  getOrder: async (orderId: string): Promise<OrderResponse> => {
    const response = await axios.get(`${API_BASE}/orders/${orderId}/details`);
    return response.data;
  },

  // Create new order
  createOrder: async (
    orderData: CreateOrderRequest
  ): Promise<OrderResponse> => {
    const response = await axios.post(`${API_BASE}/orders`, orderData);
    return response.data;
  },

  // Cancel order by customer
  cancelMyOrder: async (
    orderId: string,
    data: CancelOrderRequest
  ): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/orders/${orderId}/cancel-my-order`,
      data
    );
    return response.data;
  },

  // Resend pickup code
  resendCode: async (orderId: string): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/orders/${orderId}/resend-code`
    );
    return response.data;
  },

  // Show pickup code
  showPickupCode: async (orderId: string): Promise<PickupCodeResponse> => {
    const response = await axios.get(`${API_BASE}/orders/${orderId}/show-code`);
    return response.data;
  },

  // Claim order (generate claim code)
  claimOrder: async (orderId: string): Promise<PickupCodeResponse> => {
    const response = await axios.post(`${API_BASE}/orders/${orderId}/claim`);
    return response.data;
  },
};

// Provider order management
export const providerOrderApi = {
  // Get provider's orders
  getOrders: async (filters?: OrderFilters): Promise<OrdersResponse> => {
    const params = new URLSearchParams();

    if (filters?.status) {
      if (Array.isArray(filters.status)) {
        filters.status.forEach(status => params.append('status[]', status));
      } else {
        params.append('status', filters.status);
      }
    }

    if (filters?.date_from) params.append('date_from', filters.date_from);
    if (filters?.date_to) params.append('date_to', filters.date_to);
    if (filters?.per_page)
      params.append('per_page', filters.per_page.toString());

    const response = await axios.get(
      `${API_BASE}/provider/orders?${params.toString()}`
    );
    return response.data;
  },

  // Get order details
  getOrder: async (orderId: string): Promise<OrderResponse> => {
    const response = await axios.get(`${API_BASE}/provider/orders/${orderId}`);
    return response.data;
  },

  // Accept order
  acceptOrder: async (
    orderId: string,
    data: AcceptOrderRequest
  ): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/provider/orders/${orderId}/accept`,
      data
    );
    return response.data;
  },

  // Mark order as ready
  markReady: async (orderId: string): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/provider/orders/${orderId}/mark-ready`
    );
    return response.data;
  },

  // Complete order with code
  completeOrder: async (
    orderId: string,
    data: CompleteOrderRequest
  ): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/provider/orders/${orderId}/complete`,
      data
    );
    return response.data;
  },

  // Cancel order by restaurant
  cancelOrder: async (
    orderId: string,
    data: CancelOrderByRestaurantRequest
  ): Promise<OrderResponse> => {
    const response = await axios.post(
      `${API_BASE}/provider/orders/${orderId}/cancel`,
      data
    );
    return response.data;
  },

  // Get order statistics
  getStats: async (): Promise<OrderStatsResponse> => {
    const response = await axios.get(`${API_BASE}/provider/orders/stats`);
    return response.data;
  },
};

// Utility functions
export const orderUtils = {
  // Get status display text
  getStatusText: (status: string): string => {
    const statusMap: Record<string, string> = {
      PENDING: 'Pending',
      ACCEPTED: 'Accepted',
      READY_FOR_PICKUP: 'Ready for Pickup',
      COMPLETED: 'Completed',
      CANCELLED_BY_CUSTOMER: 'Cancelled by Customer',
      CANCELLED_BY_RESTAURANT: 'Cancelled by Restaurant',
      EXPIRED: 'Expired',
    };
    return statusMap[status] || status;
  },

  // Get status color class
  getStatusColor: (status: string): string => {
    const colorMap: Record<string, string> = {
      PENDING: 'warning',
      ACCEPTED: 'info',
      READY_FOR_PICKUP: 'primary',
      COMPLETED: 'success',
      CANCELLED_BY_CUSTOMER: 'danger',
      CANCELLED_BY_RESTAURANT: 'danger',
      EXPIRED: 'secondary',
    };
    return colorMap[status] || 'secondary';
  },

  // Check if order can be cancelled by customer
  canCancelByCustomer: (status: string): boolean => {
    return ['PENDING', 'ACCEPTED'].includes(status);
  },

  // Check if order can be cancelled by restaurant
  canCancelByRestaurant: (status: string): boolean => {
    return ['PENDING', 'ACCEPTED', 'READY_FOR_PICKUP'].includes(status);
  },

  // Check if order is active (not completed/cancelled/expired)
  isActive: (status: string): boolean => {
    return ['PENDING', 'ACCEPTED', 'READY_FOR_PICKUP'].includes(status);
  },

  // Format pickup window
  formatPickupWindow: (start?: string, end?: string): string => {
    if (!start || !end) return 'Not set';

    const startTime = new Date(start).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    });

    const endTime = new Date(end).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    });

    return `${startTime} - ${endTime}`;
  },

  // Calculate time until pickup window ends
  getTimeUntilPickupEnd: (pickupWindowEnd?: string): string | null => {
    if (!pickupWindowEnd) return null;

    const endTime = new Date(pickupWindowEnd);
    const now = new Date();
    const diffMs = endTime.getTime() - now.getTime();

    if (diffMs <= 0) return 'Expired';

    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMinutes / 60);
    const remainingMinutes = diffMinutes % 60;

    if (diffHours > 0) {
      return `${diffHours}h ${remainingMinutes}m`;
    } else {
      return `${remainingMinutes}m`;
    }
  },

  // Check if order is in pickup window
  isInPickupWindow: (start?: string, end?: string): boolean => {
    if (!start || !end) return false;

    const now = new Date();
    const startTime = new Date(start);
    const endTime = new Date(end);

    return now >= startTime && now <= endTime;
  },

  // Check if order has exceeded pickup window
  hasExceededPickupWindow: (end?: string): boolean => {
    if (!end) return false;

    const now = new Date();
    const endTime = new Date(end);

    return now > endTime;
  },
};
