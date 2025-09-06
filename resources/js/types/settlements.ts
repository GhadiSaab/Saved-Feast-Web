// TypeScript types for settlements and invoicing

export type PaymentMethod = 'CASH_ON_PICKUP' | 'ONLINE';

export interface RestaurantInvoice {
  id: number;
  restaurant_id: number;
  period_start: string;
  period_end: string;
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'void';
  subtotal_sales: string;
  commission_rate: string;
  commission_total: string;
  orders_count: number;
  pdf_path?: string;
  meta?: {
    currency?: string;
    generated_at?: string;
    notes?: string;
  };
  created_at: string;
  updated_at: string;
  restaurant?: {
    id: number;
    name: string;
    email: string;
  };
  items?: RestaurantInvoiceItem[];
}

export interface RestaurantInvoiceItem {
  id: number;
  invoice_id: number;
  order_id: number;
  order_total: string;
  commission_rate: string;
  commission_amount: string;
  created_at: string;
  updated_at: string;
  order?: {
    id: number;
    user_id: number;
    total_amount: string;
    status: string;
    payment_method: PaymentMethod;
    completed_at: string;
    order_items?: Array<{
      id: number;
      meal_id: number;
      quantity: number;
      price: string;
      meal?: {
        id: number;
        title: string;
        restaurant_id: number;
      };
    }>;
  };
}

export interface SettlementsSummary {
  amount_owed: string;
  last_invoice_status: string | null;
  last_invoice_date: string | null;
  next_invoice_date: string;
  restaurant_id: number;
  restaurant_name: string;
}

export interface ApiResponse<T> {
  status: boolean;
  message?: string;
  data: T;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}
