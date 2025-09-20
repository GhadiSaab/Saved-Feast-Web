import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { orderApi } from '../../api/orders';
import { orderUtils } from '../../utils/orderUtils';
import { Order, OrderFilters, OrderStatus } from '../../types/orders';
import StatusChip from '../../components/orders/StatusChip';
import Countdown from '../../components/orders/Countdown';
import CancelDialog from '../../components/orders/CancelDialog';

const MyOrders: React.FC = () => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<
    'in_progress' | 'completed' | 'cancelled'
  >('in_progress');

  // Rate limiting refs
  const lastFetchTime = useRef<number>(0);
  const isFetching = useRef<boolean>(false);
  const FETCH_COOLDOWN = 2000; // 2 seconds cooldown between fetches
  const [cancelDialog, setCancelDialog] = useState<{
    isOpen: boolean;
    order: Order | null;
    isLoading: boolean;
  }>({
    isOpen: false,
    order: null,
    isLoading: false,
  });

  const fetchOrders = useCallback(
    async (status?: OrderFilters['status'], force: boolean = false) => {
      const now = Date.now();

      // Rate limiting: prevent calls if we're already fetching or within cooldown period
      if (
        !force &&
        (isFetching.current || now - lastFetchTime.current < FETCH_COOLDOWN)
      ) {
        return;
      }

      try {
        isFetching.current = true;
        lastFetchTime.current = now;
        setLoading(true);
        setError(null);

        const normalizedStatus: OrderFilters['status'] =
          status === 'in_progress'
            ? (['PENDING', 'ACCEPTED', 'READY_FOR_PICKUP'] as OrderStatus[])
            : status === 'completed'
              ? 'COMPLETED'
              : status === 'cancelled'
                ? ([
                    'CANCELLED_BY_CUSTOMER',
                    'CANCELLED_BY_RESTAURANT',
                    'EXPIRED',
                  ] as OrderStatus[])
                : status;

        const response = await orderApi.getMyOrders({
          status: normalizedStatus,
        });
        if (response.success) {
          setOrders(response.data.data);
        } else {
          setError('Failed to fetch orders');
        }
      } catch (err: any) {
        setError(err.response?.data?.message || 'Failed to fetch orders');
      } finally {
        setLoading(false);
        isFetching.current = false;
      }
    },
    [FETCH_COOLDOWN]
  );

  useEffect(() => {
    fetchOrders(activeTab, true); // Force initial load
  }, [activeTab, fetchOrders]);

  const handleCancelOrder = async (order: Order, reason?: string) => {
    if (!order) return;

    setCancelDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const response = await orderApi.cancelMyOrder(order.id, { reason });
      if (response.success) {
        await fetchOrders(activeTab);
        setCancelDialog({ isOpen: false, order: null, isLoading: false });
      } else {
        setError('Failed to cancel order');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to cancel order');
    } finally {
      setCancelDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const formatCurrency = (amount: string | number) => {
    return `$${parseFloat(String(amount)).toFixed(2)}`;
  };

  if (loading && orders.length === 0) {
    return (
      <div className="container-fluid py-4">
        <div className="row">
          <div className="col-12">
            <div className="text-center py-5">
              <div
                className="spinner-border text-primary mb-3"
                style={{ width: '3rem', height: '3rem' }}
                role="status"
              >
                <span className="visually-hidden">Loading...</span>
              </div>
              <h5 className="text-muted">Loading your orders...</h5>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid py-4">
      <div className="row">
        <div className="col-12">
          {/* Header Section */}
          <div className="bg-gradient-primary text-white rounded-3 p-4 mb-4 shadow">
            <div className="row align-items-center">
              <div className="col-md-8">
                <h1 className="h2 mb-2 fw-bold">
                  <i className="fas fa-shopping-bag me-3"></i>
                  My Orders
                </h1>
                <p className="mb-0 opacity-75">
                  Track and manage your food orders
                </p>
              </div>
              <div className="col-md-4 text-md-end">
                <Link to="/" className="btn btn-light btn-lg">
                  <i className="fas fa-utensils me-2"></i>
                  Order More
                </Link>
              </div>
            </div>
          </div>

          {/* Error State */}
          {error && (
            <div
              className="alert alert-danger border-0 shadow-sm mb-4"
              role="alert"
            >
              <div className="d-flex align-items-center">
                <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                  <h6 className="alert-heading mb-1">Error Loading Orders</h6>
                  <p className="mb-0">{error}</p>
                </div>
              </div>
            </div>
          )}

          {/* Filter Tabs */}
          <div className="card border-0 shadow-sm mb-4">
            <div className="card-body p-0">
              <div className="nav nav-pills nav-fill" role="tablist">
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'in_progress' ? 'active bg-primary' : 'text-muted'}`}
                  onClick={() => setActiveTab('in_progress')}
                >
                  <i className="fas fa-clock me-2"></i>
                  In Progress
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'completed' ? 'active bg-success' : 'text-muted'}`}
                  onClick={() => setActiveTab('completed')}
                >
                  <i className="fas fa-check-circle me-2"></i>
                  Completed
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'cancelled' ? 'active bg-danger' : 'text-muted'}`}
                  onClick={() => setActiveTab('cancelled')}
                >
                  <i className="fas fa-times-circle me-2"></i>
                  Cancelled
                </button>
              </div>
            </div>
          </div>

          {/* Empty State */}
          {orders.length === 0 && (
            <div className="text-center py-5">
              <div
                className="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                style={{ width: '120px', height: '120px' }}
              >
                <i className="fas fa-shopping-bag fa-3x text-muted"></i>
              </div>
              <h3 className="text-muted mb-3">No orders found</h3>
              <p className="text-muted mb-4">
                {activeTab === 'in_progress'
                  ? "You don't have any orders in progress."
                  : `You don't have any ${activeTab} orders.`}
              </p>
              {activeTab === 'in_progress' && (
                <Link to="/" className="btn btn-primary btn-lg">
                  <i className="fas fa-utensils me-2"></i>
                  Browse Meals
                </Link>
              )}
            </div>
          )}

          {/* Orders List */}
          {orders.length > 0 && (
            <div className="row">
              {orders.map(order => (
                <div key={order.id} className="col-12 mb-4">
                  <div className="card border-0 shadow-sm h-100">
                    <div className="card-header bg-white border-0 pb-0">
                      <div className="d-flex justify-content-between align-items-center">
                        <div>
                          <h5 className="card-title mb-1 fw-bold">
                            Order #{order.id}
                          </h5>
                          <small className="text-muted">
                            <i className="fas fa-calendar me-1"></i>
                            {formatDate(order.created_at)}
                          </small>
                        </div>
                        <StatusChip status={order.status} />
                      </div>
                    </div>

                    <div className="card-body">
                      <div className="row">
                        <div className="col-md-8">
                          <h6 className="text-muted mb-3 fw-semibold">
                            <i className="fas fa-list me-2"></i>
                            Order Items
                          </h6>
                          {order.order_items.map(item => (
                            <div
                              key={item.id}
                              className="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded"
                            >
                              <div>
                                <span className="fw-medium text-dark">
                                  {item.quantity}x {item.meal.title}
                                </span>
                                <br />
                                <small className="text-muted">
                                  <i className="fas fa-store me-1"></i>
                                  {item.meal.restaurant.name}
                                </small>
                              </div>
                              <span className="text-end">
                                <strong className="text-primary">
                                  $
                                  {(
                                    item.quantity * parseFloat(item.price)
                                  ).toFixed(2)}
                                </strong>
                              </span>
                            </div>
                          ))}
                        </div>
                        <div className="col-md-4">
                          <div className="bg-light rounded p-3 h-100">
                            <h6 className="text-muted mb-2 fw-semibold">
                              <i className="fas fa-receipt me-2"></i>
                              Order Summary
                            </h6>
                            <h4 className="text-primary mb-3 fw-bold">
                              {formatCurrency(order.total_amount)}
                            </h4>

                            {order.pickup_window_start &&
                              order.pickup_window_end && (
                                <div className="mb-3">
                                  <h6 className="text-muted mb-2 fw-semibold">
                                    <i className="fas fa-clock me-2"></i>
                                    Pickup Window
                                  </h6>
                                  <Countdown
                                    targetDate={order.pickup_window_end}
                                    onExpire={() =>
                                      fetchOrders(activeTab, false)
                                    }
                                  />
                                </div>
                              )}

                            <div className="d-grid gap-2">
                              <Link
                                to={`/orders/${order.id}`}
                                className="btn btn-primary btn-sm"
                              >
                                <i className="fas fa-eye me-1"></i>
                                View Details
                              </Link>

                              {orderUtils.canCancel(order.status) && (
                                <button
                                  className="btn btn-outline-danger btn-sm"
                                  onClick={() =>
                                    setCancelDialog({
                                      isOpen: true,
                                      order,
                                      isLoading: false,
                                    })
                                  }
                                >
                                  <i className="fas fa-times me-1"></i>
                                  Cancel Order
                                </button>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <CancelDialog
        isOpen={cancelDialog.isOpen}
        order={cancelDialog.order}
        isLoading={cancelDialog.isLoading}
        onClose={() =>
          setCancelDialog({
            isOpen: false,
            order: null,
            isLoading: false,
          })
        }
        onConfirm={handleCancelOrder}
      />
    </div>
  );
};

export default MyOrders;
