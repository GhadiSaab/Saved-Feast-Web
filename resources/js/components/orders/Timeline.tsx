import React from 'react';
import { Order } from '../../types/orders';

interface TimelineProps {
  order: Order;
  className?: string;
}

const Timeline: React.FC<TimelineProps> = ({ order, className = '' }) => {
  const getTimelineItems = () => {
    const items = [];

    // Order created
    items.push({
      id: 'created',
      title: 'Order Placed',
      description: 'Your order has been placed',
      timestamp: order.created_at,
      status: 'completed',
      icon: '📝',
    });

    // Order accepted
    if (order.accepted_at) {
      items.push({
        id: 'accepted',
        title: 'Order Accepted',
        description: 'Restaurant has accepted your order',
        timestamp: order.accepted_at,
        status: 'completed',
        icon: '✅',
      });
    }

    // Order ready
    if (order.ready_at) {
      items.push({
        id: 'ready',
        title: 'Ready for Pickup',
        description: 'Your order is ready for pickup',
        timestamp: order.ready_at,
        status: 'completed',
        icon: '🍽️',
      });
    }

    // Order completed
    if (order.completed_at) {
      items.push({
        id: 'completed',
        title: 'Order Completed',
        description: 'Order has been picked up',
        timestamp: order.completed_at,
        status: 'completed',
        icon: '🎉',
      });
    }

    // Order cancelled
    if (order.cancelled_at) {
      items.push({
        id: 'cancelled',
        title: 'Order Cancelled',
        description: order.cancel_reason || 'Order has been cancelled',
        timestamp: order.cancelled_at,
        status: 'cancelled',
        icon: '❌',
      });
    }

    // Order expired
    if (order.expired_at) {
      items.push({
        id: 'expired',
        title: 'Order Expired',
        description: 'Order expired after pickup window',
        timestamp: order.expired_at,
        status: 'cancelled',
        icon: '⏰',
      });
    }

    return items;
  };

  const formatTimestamp = (timestamp: string) => {
    return new Date(timestamp).toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    });
  };

  const timelineItems = getTimelineItems();

  return (
    <div className={`space-y-4 ${className}`}>
      <h3 className="text-lg font-medium text-gray-900">Order Timeline</h3>
      <div className="flow-root">
        <ul className="-mb-8">
          {timelineItems.map((item, itemIdx) => (
            <li key={item.id}>
              <div className="relative pb-8">
                {itemIdx !== timelineItems.length - 1 ? (
                  <span
                    className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                    aria-hidden="true"
                  />
                ) : null}
                <div className="relative flex space-x-3">
                  <div>
                    <span
                      className={`h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white text-sm ${
                        item.status === 'completed'
                          ? 'bg-green-500 text-white'
                          : 'bg-red-500 text-white'
                      }`}
                    >
                      {item.icon}
                    </span>
                  </div>
                  <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p className="text-sm text-gray-900 font-medium">{item.title}</p>
                      <p className="text-sm text-gray-500">{item.description}</p>
                    </div>
                    <div className="text-right text-sm whitespace-nowrap text-gray-500">
                      {formatTimestamp(item.timestamp)}
                    </div>
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default Timeline;
