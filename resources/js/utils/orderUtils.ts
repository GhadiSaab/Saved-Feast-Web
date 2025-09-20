import { OrderStatus } from '../types/orders';

export const orderUtils = {
  /**
   * Check if an order can be cancelled
   */
  canCancel: (status: OrderStatus): boolean => {
    return ['PENDING', 'ACCEPTED'].includes(status);
  },

  /**
   * Check if an order is in an active state
   */
  isActive: (status: OrderStatus): boolean => {
    return ['PENDING', 'ACCEPTED', 'READY_FOR_PICKUP'].includes(status);
  },

  /**
   * Check if an order is completed
   */
  isCompleted: (status: OrderStatus): boolean => {
    return status === 'COMPLETED';
  },

  /**
   * Check if an order is cancelled
   */
  isCancelled: (status: OrderStatus): boolean => {
    return [
      'CANCELLED_BY_CUSTOMER',
      'CANCELLED_BY_RESTAURANT',
      'EXPIRED',
    ].includes(status);
  },

  /**
   * Get status color for UI display
   */
  getStatusColor: (status: OrderStatus): string => {
    switch (status) {
      case 'PENDING':
        return 'warning';
      case 'ACCEPTED':
        return 'info';
      case 'READY_FOR_PICKUP':
        return 'primary';
      case 'COMPLETED':
        return 'success';
      case 'CANCELLED_BY_CUSTOMER':
      case 'CANCELLED_BY_RESTAURANT':
      case 'EXPIRED':
        return 'danger';
      default:
        return 'secondary';
    }
  },

  /**
   * Get human-readable status text
   */
  getStatusText: (status: OrderStatus): string => {
    switch (status) {
      case 'PENDING':
        return 'Pending';
      case 'ACCEPTED':
        return 'Accepted';
      case 'READY_FOR_PICKUP':
        return 'Ready for Pickup';
      case 'COMPLETED':
        return 'Completed';
      case 'CANCELLED_BY_CUSTOMER':
        return 'Cancelled by Customer';
      case 'CANCELLED_BY_RESTAURANT':
        return 'Cancelled by Restaurant';
      case 'EXPIRED':
        return 'Expired';
      default:
        return status;
    }
  },

  /**
   * Check if order can show pickup code
   */
  canShowPickupCode: (status: OrderStatus): boolean => {
    return ['ACCEPTED', 'READY_FOR_PICKUP'].includes(status);
  },

  /**
   * Check if order can be claimed
   */
  canClaim: (status: OrderStatus): boolean => {
    return status === 'READY_FOR_PICKUP';
  },

  /**
   * Check if order can be accepted by provider
   */
  canAccept: (status: OrderStatus): boolean => {
    return status === 'PENDING';
  },

  /**
   * Check if order can be marked as ready
   */
  canMarkReady: (status: OrderStatus): boolean => {
    return status === 'ACCEPTED';
  },

  /**
   * Check if order can be completed
   */
  canComplete: (status: OrderStatus): boolean => {
    return status === 'READY_FOR_PICKUP';
  },

  /**
   * Format order status for display
   */
  formatStatus: (status: OrderStatus): string => {
    return orderUtils.getStatusText(status);
  },

  /**
   * Get status icon class
   */
  getStatusIcon: (status: OrderStatus): string => {
    switch (status) {
      case 'PENDING':
        return 'fas fa-clock';
      case 'ACCEPTED':
        return 'fas fa-check-circle';
      case 'READY_FOR_PICKUP':
        return 'fas fa-box';
      case 'COMPLETED':
        return 'fas fa-trophy';
      case 'CANCELLED_BY_CUSTOMER':
      case 'CANCELLED_BY_RESTAURANT':
        return 'fas fa-times-circle';
      case 'EXPIRED':
        return 'fas fa-hourglass-end';
      default:
        return 'fas fa-question-circle';
    }
  },
};
