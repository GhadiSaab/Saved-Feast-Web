import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { orderApi } from '../../api/orders';
import { orderUtils } from '../../utils/orderUtils';
import { Order } from '../../types/orders';
import StatusChip from '../../components/orders/StatusChip';
import Countdown from '../../components/orders/Countdown';
import CancelDialog from '../../components/orders/CancelDialog';
import ClaimCodeDialog from '../../components/orders/ClaimCodeDialog';

const OrderDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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

  const [claimDialog, setClaimDialog] = useState<{
    isOpen: boolean;
    order: Order | null;
    isLoading: boolean;
    code?: string;
    expiresAt?: Date;
  }>({
    isOpen: false,
    order: null,
    isLoading: false,
  });

  const fetchOrder = useCallback(async (force: boolean = false) => {
    if (!id) return;

    const now = Date.now();
    
    // Rate limiting: prevent calls if we're already fetching or within cooldown period
    if (!force && (isFetching.current || (now - lastFetchTime.current < FETCH_COOLDOWN))) {
      return;
    }

    try {
      isFetching.current = true;
      lastFetchTime.current = now;
      setLoading(true);
      setError(null);
      
      const response = await orderApi.getOrder(id);
      if (response.success) {
        setOrder(response.data);
      } else {
        setError('Failed to fetch order details');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch order details');
    } finally {
      setLoading(false);
      isFetching.current = false;
    }
  }, [id, FETCH_COOLDOWN]);

  useEffect(() => {
    fetchOrder(true); // Force initial load
  }, [id, fetchOrder]);

  const handleCancelOrder = async (order: Order, reason?: string) => {
    if (!order) return;

    setCancelDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const response = await orderApi.cancelMyOrder(order.id, { reason });
      if (response.success) {
        await fetchOrder();
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

  const handleClaimOrder = async (order: Order) => {
    if (!order) return;

    setClaimDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const response = await orderApi.claimOrder(order.id);
      if (response.success) {
        const expiresAt = new Date();
        expiresAt.setMinutes(expiresAt.getMinutes() + 5); // 5 minutes from now
        
        setClaimDialog({
          isOpen: true,
          order,
          isLoading: false,
          code: response.data.code,
          expiresAt
        });
      } else {
        setError('Failed to generate claim code');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to generate claim code');
      setClaimDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatCurrency = (amount: string | number) => {
    return `$${parseFloat(String(amount)).toFixed(2)}`;
  };

  if (loading) {
    return (
      <div className="container-fluid py-4">
        <div className="row">
          <div className="col-12">
            <div className="text-center py-5">
              <div className="spinner-border text-primary mb-3" style={{width: '3rem', height: '3rem'}} role="status">
                <span className="visually-hidden">Loading...</span>
              </div>
              <h5 className="text-muted">Loading order details...</h5>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="container-fluid py-4">
        <div className="row">
          <div className="col-12">
            <div className="alert alert-danger border-0 shadow-sm" role="alert">
              <div className="d-flex align-items-center">
                <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                  <h6 className="alert-heading mb-1">Error Loading Order</h6>
                  <p className="mb-0">{error || 'Order not found'}</p>
                </div>
              </div>
            </div>
            <div className="text-center mt-4">
              <Link to="/orders" className="btn btn-primary">
                <i className="fas fa-arrow-left me-2"></i>
                Back to Orders
              </Link>
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
                  <i className="fas fa-receipt me-3"></i>
                  Order #{order.id}
                </h1>
                <p className="mb-0 opacity-75">Order details and tracking information</p>
              </div>
              <div className="col-md-4 text-md-end">
                <Link to="/orders" className="btn btn-light btn-lg">
                  <i className="fas fa-arrow-left me-2"></i>
                  Back to Orders
                </Link>
              </div>
            </div>
          </div>

          {/* Error State */}
          {error && (
            <div className="alert alert-danger border-0 shadow-sm mb-4" role="alert">
              <div className="d-flex align-items-center">
                <i className="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                  <h6 className="alert-heading mb-1">Error</h6>
                  <p className="mb-0">{error}</p>
                </div>
              </div>
            </div>
          )}

          <div className="row">
            {/* Order Information */}
            <div className="col-lg-8 mb-4">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-header bg-white border-0">
                  <div className="d-flex justify-content-between align-items-center">
                    <h5 className="card-title mb-0 fw-bold">
                      <i className="fas fa-info-circle me-2"></i>
                      Order Information
                    </h5>
                    <StatusChip status={order.status} />
                  </div>
                </div>
                <div className="card-body">
                  <div className="row">
                    <div className="col-md-6 mb-3">
                      <h6 className="text-muted fw-semibold">Order Date</h6>
                      <p className="mb-0">
                        <i className="fas fa-calendar me-2"></i>
                        {formatDate(order.created_at)}
                      </p>
                    </div>
                    <div className="col-md-6 mb-3">
                      <h6 className="text-muted fw-semibold">Total Amount</h6>
                      <p className="mb-0">
                        <i className="fas fa-dollar-sign me-2"></i>
                        <strong className="text-primary">{formatCurrency(order.total_amount)}</strong>
                      </p>
                    </div>
                  </div>

                  {order.pickup_window_start && order.pickup_window_end && (
                    <div className="row">
                      <div className="col-md-6 mb-3">
                        <h6 className="text-muted fw-semibold">Pickup Window Start</h6>
                        <p className="mb-0">
                          <i className="fas fa-clock me-2"></i>
                          {formatDate(order.pickup_window_start)}
                        </p>
                      </div>
                      <div className="col-md-6 mb-3">
                        <h6 className="text-muted fw-semibold">Pickup Window End</h6>
                        <p className="mb-0">
                          <i className="fas fa-clock me-2"></i>
                          {formatDate(order.pickup_window_end)}
                        </p>
                      </div>
                    </div>
                  )}

                  {/* Order Items */}
                  <div className="mt-4">
                    <h6 className="text-muted fw-semibold mb-3">
                      <i className="fas fa-list me-2"></i>
                      Order Items
                    </h6>
                    {order.order_items.map((item) => (
                      <div key={item.id} className="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                        <div>
                          <span className="fw-medium text-dark">{item.quantity}x {item.meal.title}</span>
                          <br />
                          <small className="text-muted">
                            <i className="fas fa-store me-1"></i>
                            {item.meal.restaurant.name}
                          </small>
                        </div>
                        <span className="text-end">
                          <strong className="text-primary">${(item.quantity * parseFloat(item.price)).toFixed(2)}</strong>
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Order Actions */}
            <div className="col-lg-4 mb-4">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-header bg-white border-0">
                  <h5 className="card-title mb-0 fw-bold">
                    <i className="fas fa-cogs me-2"></i>
                    Order Actions
                  </h5>
                </div>
                <div className="card-body">
                  {order.pickup_window_start && order.pickup_window_end && (
                    <div className="mb-4">
                      <h6 className="text-muted fw-semibold mb-2">
                        <i className="fas fa-clock me-2"></i>
                        Pickup Window
                      </h6>
                      <Countdown 
                        targetDate={order.pickup_window_end}
                        onExpire={() => fetchOrder(false)}
                      />
                    </div>
                  )}

                  <div className="d-grid gap-2">
                    {order.status === 'READY_FOR_PICKUP' && (
                      <button
                        className="btn btn-success btn-lg"
                        onClick={() => handleClaimOrder(order)}
                      >
                        <i className="fas fa-key me-2"></i>
                        Claim Order
                      </button>
                    )}

                    {orderUtils.canCancel(order.status) && (
                      <button
                        className="btn btn-outline-danger"
                        onClick={() => setCancelDialog({
                          isOpen: true,
                          order,
                          isLoading: false
                        })}
                      >
                        <i className="fas fa-times me-2"></i>
                        Cancel Order
                      </button>
                    )}

                    <Link to="/" className="btn btn-outline-primary">
                      <i className="fas fa-utensils me-2"></i>
                      Order More
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <CancelDialog
        isOpen={cancelDialog.isOpen}
        order={cancelDialog.order}
        isLoading={cancelDialog.isLoading}
        onClose={() => setCancelDialog({
          isOpen: false,
          order: null,
          isLoading: false
        })}
        onConfirm={handleCancelOrder}
      />

      <ClaimCodeDialog
        isOpen={claimDialog.isOpen}
        order={claimDialog.order}
        isLoading={claimDialog.isLoading}
        code={claimDialog.code}
        expiresAt={claimDialog.expiresAt}
        onClose={() => setClaimDialog({
          isOpen: false,
          order: null,
          isLoading: false
        })}
      />
    </div>
  );
};

export default OrderDetail;