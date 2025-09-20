import React from 'react';
import { OrderStatus } from '../../types/orders';
import { orderUtils } from '../../utils/orderUtils';

interface StatusChipProps {
  status: OrderStatus;
  size?: 'sm' | 'md' | 'lg';
}

const StatusChip: React.FC<StatusChipProps> = ({ status, size = 'md' }) => {
  const color = orderUtils.getStatusColor(status);
  const text = orderUtils.getStatusText(status);
  const icon = orderUtils.getStatusIcon(status);

  const sizeClasses = {
    sm: 'badge-sm',
    md: '',
    lg: 'badge-lg',
  };

  return (
    <span
      className={`badge bg-${color} ${sizeClasses[size]} d-flex align-items-center`}
    >
      <i className={`${icon} me-1`}></i>
      {text}
    </span>
  );
};

export default StatusChip;
