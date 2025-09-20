import React, { useState } from 'react';
import { Order } from '../../types/orders';

interface CancelDialogProps {
  isOpen: boolean;
  order: Order | null;
  isLoading: boolean;
  onClose: () => void;
  onConfirm: (order: Order, reason?: string) => void;
}

const CancelDialog: React.FC<CancelDialogProps> = ({
  isOpen,
  order,
  isLoading,
  onClose,
  onConfirm,
}) => {
  const [reason, setReason] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (order) {
      onConfirm(order, reason);
    }
  };

  const handleClose = () => {
    setReason('');
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
          <div className="modal-header bg-danger text-white border-0">
            <h5 className="modal-title fw-bold">
              <i className="fas fa-times-circle me-2"></i>
              Cancel Order
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
              <p className="mb-0">
                Are you sure you want to cancel this order?
              </p>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label
                  htmlFor="cancelReason"
                  className="form-label fw-semibold"
                >
                  <i className="fas fa-comment me-2"></i>
                  Reason for cancellation (optional)
                </label>
                <textarea
                  className="form-control"
                  id="cancelReason"
                  rows={3}
                  value={reason}
                  onChange={e => setReason(e.target.value)}
                  placeholder="Please let us know why you're cancelling this order..."
                  disabled={isLoading}
                />
              </div>

              <div className="alert alert-warning border-0">
                <h6 className="alert-heading">
                  <i className="fas fa-exclamation-triangle me-2"></i>
                  Important:
                </h6>
                <ul className="mb-0">
                  <li>This action cannot be undone</li>
                  <li>You may be charged a cancellation fee</li>
                  <li>Refunds will be processed within 3-5 business days</li>
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
              Keep Order
            </button>
            <button
              type="button"
              className="btn btn-danger"
              onClick={() => onConfirm(order, reason)}
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Cancelling...
                </>
              ) : (
                <>
                  <i className="fas fa-times me-2"></i>
                  Cancel Order
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CancelDialog;
