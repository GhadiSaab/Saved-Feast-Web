import React, { useState, useEffect } from 'react';
import { Order } from '../../types/orders';

interface AcceptOrderDialogProps {
  isOpen: boolean;
  order: Order | null;
  isLoading: boolean;
  onClose: () => void;
  onConfirm: (
    order: Order,
    pickupWindowStart: string,
    pickupWindowEnd: string
  ) => void;
}

const AcceptOrderDialog: React.FC<AcceptOrderDialogProps> = ({
  isOpen,
  order,
  isLoading,
  onClose,
  onConfirm,
}) => {
  const [pickupStart, setPickupStart] = useState('');
  const [pickupEnd, setPickupEnd] = useState('');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isOpen && order) {
      // Set default pickup window (30 minutes from now to 2 hours from now)
      const now = new Date();
      const defaultStart = new Date(now.getTime() + 30 * 60 * 1000); // 30 minutes from now
      const defaultEnd = new Date(now.getTime() + 2 * 60 * 60 * 1000); // 2 hours from now

      // Format for datetime-local input (YYYY-MM-DDTHH:MM)
      setPickupStart(defaultStart.toISOString().slice(0, 16));
      setPickupEnd(defaultEnd.toISOString().slice(0, 16));
      setError(null);
    }
  }, [isOpen, order]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!order || !pickupStart || !pickupEnd) {
      setError('Please select both pickup start and end times');
      return;
    }

    const startTime = new Date(pickupStart);
    const endTime = new Date(pickupEnd);
    const now = new Date();

    // Validation
    if (startTime <= now) {
      setError('Pickup start time must be in the future');
      return;
    }

    if (endTime <= startTime) {
      setError('Pickup end time must be after start time');
      return;
    }

    const timeDiff = endTime.getTime() - startTime.getTime();
    const minWindow = 30 * 60 * 1000; // 30 minutes
    const maxWindow = 24 * 60 * 60 * 1000; // 24 hours

    if (timeDiff < minWindow) {
      setError('Pickup window must be at least 30 minutes');
      return;
    }

    if (timeDiff > maxWindow) {
      setError('Pickup window cannot exceed 24 hours');
      return;
    }

    onConfirm(order, pickupStart, pickupEnd);
  };

  const handleClose = () => {
    setPickupStart('');
    setPickupEnd('');
    setError(null);
    onClose();
  };

  if (!isOpen || !order) return null;

  return (
    <div
      className="modal show d-block"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
    >
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 shadow-lg">
          <div className="modal-header bg-success text-white border-0">
            <h5 className="modal-title fw-bold">
              <i className="fas fa-check-circle me-2"></i>
              Accept Order
            </h5>
            <button
              type="button"
              className="btn-close btn-close-white"
              onClick={handleClose}
              disabled={isLoading}
            ></button>
          </div>

          <div className="modal-body p-4">
            <div className="text-center mb-4">
              <h6 className="text-muted">Order #{order.id}</h6>
              <p className="mb-0">Set the pickup window for this order.</p>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="row">
                <div className="col-md-6 mb-3">
                  <label
                    htmlFor="pickupStart"
                    className="form-label fw-semibold"
                  >
                    <i className="fas fa-clock me-2"></i>
                    Pickup Window Start
                  </label>
                  <input
                    type="datetime-local"
                    className="form-control"
                    id="pickupStart"
                    value={pickupStart}
                    onChange={e => setPickupStart(e.target.value)}
                    disabled={isLoading}
                    required
                  />
                  <small className="text-muted">
                    When customers can start picking up
                  </small>
                </div>

                <div className="col-md-6 mb-3">
                  <label htmlFor="pickupEnd" className="form-label fw-semibold">
                    <i className="fas fa-clock me-2"></i>
                    Pickup Window End
                  </label>
                  <input
                    type="datetime-local"
                    className="form-control"
                    id="pickupEnd"
                    value={pickupEnd}
                    onChange={e => setPickupEnd(e.target.value)}
                    disabled={isLoading}
                    required
                  />
                  <small className="text-muted">
                    When pickup window closes
                  </small>
                </div>
              </div>

              {error && (
                <div className="alert alert-danger border-0">
                  <i className="fas fa-exclamation-triangle me-2"></i>
                  {error}
                </div>
              )}

              <div className="alert alert-info border-0">
                <h6 className="alert-heading">
                  <i className="fas fa-info-circle me-2"></i>
                  Guidelines:
                </h6>
                <ul className="mb-0">
                  <li>Pickup window must be at least 30 minutes</li>
                  <li>Maximum pickup window is 24 hours</li>
                  <li>Start time must be in the future</li>
                  <li>End time must be after start time</li>
                </ul>
              </div>
            </form>
          </div>

          <div className="modal-footer border-0">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={handleClose}
              disabled={isLoading}
            >
              Cancel
            </button>
            <button
              type="button"
              className="btn btn-success"
              onClick={handleSubmit}
              disabled={isLoading || !pickupStart || !pickupEnd}
            >
              {isLoading ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Accepting...
                </>
              ) : (
                <>
                  <i className="fas fa-check me-2"></i>
                  Accept Order
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AcceptOrderDialog;
