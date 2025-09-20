import React, { useState, useEffect, useRef, useCallback } from 'react';
import { providerOrderApi } from '../../api/orders';
import { orderUtils } from '../../utils/orderUtils';
import {
  Order,
  OrderFilters,
  OrderStatus,
  OrderStats,
} from '../../types/orders';
import StatusChip from '../../components/orders/StatusChip';
import Countdown from '../../components/orders/Countdown';
import CancelDialog from '../../components/orders/CancelDialog';
import EnterCodeDialog from '../../components/orders/EnterCodeDialog';
import AcceptOrderDialog from '../../components/orders/AcceptOrderDialog';

const ProviderOrders: React.FC = () => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<
    'PENDING' | 'ACCEPTED' | 'READY_FOR_PICKUP' | 'COMPLETED' | 'CANCELLED'
  >('PENDING');
  const [stats, setStats] = useState<OrderStats>({
    pending: 0,
    accepted: 0,
    ready: 0,
    completed_today: 0,
  });

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

  const [codeDialog, setCodeDialog] = useState<{
    isOpen: boolean;
    order: Order | null;
    isLoading: boolean;
    error?: string;
  }>({
    isOpen: false,
    order: null,
    isLoading: false,
  });

  const [acceptDialog, setAcceptDialog] = useState<{
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

        // Normalize status filter types
        const statusFilter: OrderFilters['status'] =
          status === 'cancelled'
            ? ([
                'CANCELLED_BY_CUSTOMER',
                'CANCELLED_BY_RESTAURANT',
                'EXPIRED',
              ] as OrderStatus[])
            : status;

        const response = await providerOrderApi.getOrders({
          status: statusFilter,
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

  const fetchStats = useCallback(
    async (force: boolean = false) => {
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

        const response = await providerOrderApi.getStats();
        if (response.success) {
          setStats(response.data);
        }
      } catch (err) {
        console.error('Failed to fetch stats:', err);
      } finally {
        isFetching.current = false;
      }
    },
    [FETCH_COOLDOWN]
  );

  useEffect(() => {
    // Map tab to filter value acceptable by API types
    const mappedStatus: OrderFilters['status'] =
      activeTab === 'CANCELLED' ? 'cancelled' : (activeTab as OrderStatus);
    fetchOrders(mappedStatus, true); // Force initial load
    fetchStats(true); // Force initial load
  }, [activeTab, fetchOrders, fetchStats]);

  const handleAcceptOrder = (order: Order) => {
    setAcceptDialog({
      isOpen: true,
      order,
      isLoading: false,
    });
  };

  const handleConfirmAcceptOrder = async (
    order: Order,
    pickupWindowStart: string,
    pickupWindowEnd: string
  ) => {
    setAcceptDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const response = await providerOrderApi.acceptOrder(order.id, {
        pickup_window_start: pickupWindowStart,
        pickup_window_end: pickupWindowEnd,
      });

      if (response.success) {
        setAcceptDialog({ isOpen: false, order: null, isLoading: false });
        await fetchOrders(
          activeTab === 'CANCELLED' ? 'cancelled' : (activeTab as OrderStatus),
          true
        );
        await fetchStats(true);
      } else {
        setError('Failed to accept order');
        setAcceptDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to accept order');
      setAcceptDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const handleMarkReady = async (order: Order) => {
    try {
      const response = await providerOrderApi.markReady(order.id);
      if (response.success) {
        await fetchOrders(
          activeTab === 'CANCELLED' ? 'cancelled' : (activeTab as OrderStatus),
          true
        );
        await fetchStats(true);
      } else {
        setError('Failed to mark order as ready');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to mark order as ready');
    }
  };

  const handleCompleteOrder = async (order: Order, code: string) => {
    setCodeDialog(prev => ({ ...prev, isLoading: true, error: undefined }));

    try {
      const response = await providerOrderApi.completeOrder(order.id, { code });
      if (response.success) {
        setCodeDialog({ isOpen: false, order: null, isLoading: false });
        await fetchOrders(
          activeTab === 'CANCELLED' ? 'cancelled' : (activeTab as OrderStatus),
          true
        );
        await fetchStats(true);
      } else {
        setCodeDialog(prev => ({
          ...prev,
          isLoading: false,
          error: response.message || 'Invalid pickup code',
        }));
      }
    } catch (err: any) {
      setCodeDialog(prev => ({
        ...prev,
        isLoading: false,
        error: err.response?.data?.message || 'Failed to complete order',
      }));
    }
  };

  const handleCancelOrder = async (order: Order, reason?: string) => {
    if (!order) return;

    setCancelDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const response = await providerOrderApi.cancelOrder(order.id, {
        reason: reason ?? 'Cancelled by restaurant',
      });
      if (response.success) {
        await fetchOrders(
          activeTab === 'CANCELLED' ? 'cancelled' : (activeTab as OrderStatus),
          true
        );
        await fetchStats(true);
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

  const getActionButton = (order: Order) => {
    switch (order.status) {
      case 'PENDING':
        return (
          <button
            className="btn btn-success btn-sm"
            onClick={() => handleAcceptOrder(order)}
          >
            <i className="fas fa-check me-1"></i>
            Accept Order
          </button>
        );
      case 'ACCEPTED':
        return (
          <button
            className="btn btn-warning btn-sm"
            onClick={() => handleMarkReady(order)}
          >
            <i className="fas fa-clock me-1"></i>
            Mark Ready
          </button>
        );
      case 'READY_FOR_PICKUP':
        return (
          <button
            className="btn btn-primary btn-sm"
            onClick={() =>
              setCodeDialog({
                isOpen: true,
                order,
                isLoading: false,
              })
            }
          >
            <i className="fas fa-key me-1"></i>
            Complete Order
          </button>
        );
      case 'CANCELLED_BY_CUSTOMER':
      case 'CANCELLED_BY_RESTAURANT':
      case 'EXPIRED':
      case 'COMPLETED':
      default:
        return null;
    }
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
              <h5 className="text-muted">Loading orders...</h5>
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
                  <i className="fas fa-clipboard-list me-3"></i>
                  Order Management
                </h1>
                <p className="mb-0 opacity-75">
                  Manage and track restaurant orders
                </p>
              </div>
              <div className="col-md-4 text-md-end">
                <div className="d-flex justify-content-md-end gap-2">
                  <button
                    className="btn btn-light btn-sm"
                    onClick={() => fetchStats(true)}
                  >
                    <i className="fas fa-sync-alt me-1"></i>
                    Refresh
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="row mb-4">
            <div className="col-md-3 mb-3">
              <div className="card border-0 shadow-sm bg-warning text-white">
                <div className="card-body text-center">
                  <i className="fas fa-clock fa-2x mb-2"></i>
                  <h4 className="fw-bold">{stats.pending}</h4>
                  <p className="mb-0">Pending Orders</p>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-3">
              <div className="card border-0 shadow-sm bg-info text-white">
                <div className="card-body text-center">
                  <i className="fas fa-check-circle fa-2x mb-2"></i>
                  <h4 className="fw-bold">{stats.accepted}</h4>
                  <p className="mb-0">Accepted Orders</p>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-3">
              <div className="card border-0 shadow-sm bg-primary text-white">
                <div className="card-body text-center">
                  <i className="fas fa-box fa-2x mb-2"></i>
                  <h4 className="fw-bold">{stats.ready}</h4>
                  <p className="mb-0">Ready for Pickup</p>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-3">
              <div className="card border-0 shadow-sm bg-success text-white">
                <div className="card-body text-center">
                  <i className="fas fa-trophy fa-2x mb-2"></i>
                  <h4 className="fw-bold">{stats.completed_today}</h4>
                  <p className="mb-0">Completed Today</p>
                </div>
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
                  className={`nav-link rounded-0 py-3 ${activeTab === 'PENDING' ? 'active bg-warning' : 'text-muted'}`}
                  onClick={() => setActiveTab('PENDING')}
                >
                  <i className="fas fa-clock me-2"></i>
                  Pending
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'ACCEPTED' ? 'active bg-info' : 'text-muted'}`}
                  onClick={() => setActiveTab('ACCEPTED')}
                >
                  <i className="fas fa-check-circle me-2"></i>
                  Accepted
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'READY_FOR_PICKUP' ? 'active bg-primary' : 'text-muted'}`}
                  onClick={() => setActiveTab('READY_FOR_PICKUP')}
                >
                  <i className="fas fa-box me-2"></i>
                  Ready
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'COMPLETED' ? 'active bg-success' : 'text-muted'}`}
                  onClick={() => setActiveTab('COMPLETED')}
                >
                  <i className="fas fa-trophy me-2"></i>
                  Completed
                </button>
                <button
                  className={`nav-link rounded-0 py-3 ${activeTab === 'CANCELLED' ? 'active bg-danger' : 'text-muted'}`}
                  onClick={() => setActiveTab('CANCELLED')}
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
                <i className="fas fa-clipboard-list fa-3x text-muted"></i>
              </div>
              <h3 className="text-muted mb-3">No orders found</h3>
              <p className="text-muted mb-4">
                {activeTab === 'PENDING'
                  ? "You don't have any pending orders."
                  : activeTab === 'CANCELLED'
                    ? "You don't have any cancelled or expired orders."
                    : `You don't have any ${activeTab.toLowerCase().replace('_', ' ')} orders.`}
              </p>
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
                                  <i className="fas fa-user me-1"></i>
                                  Customer: {order.user?.name || 'Unknown'}
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
                                      fetchOrders(
                                        activeTab === 'CANCELLED'
                                          ? 'cancelled'
                                          : (activeTab as OrderStatus),
                                        false
                                      )
                                    }
                                  />
                                </div>
                              )}

                            <div className="d-grid gap-2">
                              {getActionButton(order)}

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

      <EnterCodeDialog
        isOpen={codeDialog.isOpen}
        order={codeDialog.order}
        isLoading={codeDialog.isLoading}
        error={codeDialog.error}
        onClose={() =>
          setCodeDialog({
            isOpen: false,
            order: null,
            isLoading: false,
          })
        }
        onConfirm={handleCompleteOrder}
      />

      <AcceptOrderDialog
        isOpen={acceptDialog.isOpen}
        order={acceptDialog.order}
        isLoading={acceptDialog.isLoading}
        onClose={() =>
          setAcceptDialog({
            isOpen: false,
            order: null,
            isLoading: false,
          })
        }
        onConfirm={handleConfirmAcceptOrder}
      />
    </div>
  );
};

export default ProviderOrders;
