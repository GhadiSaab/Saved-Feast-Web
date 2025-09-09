// Order status types
export type OrderStatus = 
  | 'PENDING'
  | 'ACCEPTED'
  | 'READY_FOR_PICKUP'
  | 'COMPLETED'
  | 'CANCELLED_BY_CUSTOMER'
  | 'CANCELLED_BY_RESTAURANT'
  | 'EXPIRED';

export type CancelledBy = 'customer' | 'restaurant' | 'system';

export type PaymentMethod = 'CASH_ON_PICKUP' | 'ONLINE';

// Order item interface
export interface OrderItem {
  id: string;
  meal_id: string;
  quantity: number;
  price: string;
  original_price?: string;
  meal: {
    id: string;
    title: string;
    description?: string;
    current_price: string;
    original_price?: string;
    image_url?: string;
    restaurant: {
      id: string;
      name: string;
      address?: string;
      phone?: string;
    };
  };
}

// Order event interface
export interface OrderEvent {
  id: string;
  order_id: string;
  type: string;
  meta?: Record<string, any>;
  created_at: string;
}

// Main order interface
export interface Order {
  id: string;
  user_id: string;
  total_amount: string;
  status: OrderStatus;
  pickup_time?: string;
  notes?: string;
  payment_method: PaymentMethod;
  commission_rate?: string;
  commission_amount?: string;
  completed_at?: string;
  invoiced_at?: string;
  
  // New tracking fields
  pickup_window_start?: string;
  pickup_window_end?: string;
  accepted_at?: string;
  ready_at?: string;
  cancelled_at?: string;
  expired_at?: string;
  cancel_reason?: string;
  cancelled_by?: CancelledBy;
  pickup_code_encrypted?: string;
  pickup_code_attempts: number;
  pickup_code_last_sent_at?: string;
  
  // Computed fields
  pickup_code_masked?: string;
  can_show_code?: boolean;
  
  // Relationships
  order_items: OrderItem[];
  events: OrderEvent[];
  user?: {
    id: string;
    name: string;
    email: string;
    phone?: string;
  };
  
  // Timestamps
  created_at: string;
  updated_at: string;
}

// API response interfaces
export interface OrdersResponse {
  success: boolean;
  data: {
    data: Order[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface OrderResponse {
  success: boolean;
  data: Order;
  message?: string;
}

export interface PickupCodeResponse {
  success: boolean;
  data: {
    code: string;
    masked_code: string;
  };
}

export interface ClaimCodeResponse {
  success: boolean;
  message: string;
  data: {
    code: string;
    expires_at: string;
  };
}

// Request interfaces
export interface CreateOrderRequest {
  items: Array<{
    meal_id: string;
    quantity: number;
  }>;
  pickup_time?: string;
  notes?: string;
  payment_method?: PaymentMethod;
}

export interface CancelOrderRequest {
  reason?: string;
}

export interface AcceptOrderRequest {
  pickup_window_start?: string;
  pickup_window_end?: string;
}

export interface CompleteOrderRequest {
  code: string;
}

export interface CancelOrderByRestaurantRequest {
  reason: string;
}

// Filter interfaces
export interface OrderFilters {
  status?: OrderStatus | OrderStatus[] | 'in_progress' | 'completed' | 'cancelled';
  date_from?: string;
  date_to?: string;
  per_page?: number;
}

// Statistics interface
export interface OrderStats {
  pending: number;
  accepted: number;
  ready: number;
  completed_today: number;
}

export interface OrderStatsResponse {
  success: boolean;
  data: OrderStats;
}
