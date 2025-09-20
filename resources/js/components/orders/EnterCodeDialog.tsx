import React, { useState } from 'react';
import { Order } from '../../types/orders';

interface EnterCodeDialogProps {
  isOpen: boolean;
  order: Order | null;
  isLoading: boolean;
  error?: string;
  onClose: () => void;
  onConfirm: (order: Order, code: string) => void;
}

const EnterCodeDialog: React.FC<EnterCodeDialogProps> = ({
  isOpen,
  order,
  isLoading,
  error,
  onClose,
  onConfirm,
}) => {
  const [code, setCode] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (order && code.trim()) {
      onConfirm(order, code.trim());
    }
  };

  const handleClose = () => {
    setCode('');
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
          <div className="modal-header bg-primary text-white border-0">
            <h5 className="modal-title fw-bold">
              <i className="fas fa-key me-2"></i>
              Complete Order
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
                Enter the claim code provided by the customer to complete this
                order.
              </p>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label htmlFor="claimCode" className="form-label fw-semibold">
                  <i className="fas fa-key me-2"></i>
                  Claim Code
                </label>
                <input
                  type="text"
                  className={`form-control form-control-lg text-center font-monospace ${error ? 'is-invalid' : ''}`}
                  id="claimCode"
                  value={code}
                  onChange={e => setCode(e.target.value.toUpperCase())}
                  placeholder="Enter 6-digit code"
                  maxLength={6}
                  disabled={isLoading}
                  autoFocus
                />
                {error && (
                  <div className="invalid-feedback">
                    <i className="fas fa-exclamation-triangle me-1"></i>
                    {error}
                  </div>
                )}
              </div>

              <div className="alert alert-info border-0">
                <h6 className="alert-heading">
                  <i className="fas fa-info-circle me-2"></i>
                  Instructions:
                </h6>
                <ul className="mb-0">
                  <li>Ask the customer for their claim code</li>
                  <li>Enter the 6-digit code above</li>
                  <li>Click "Complete Order" to finish</li>
                  <li>Codes expire after 5 minutes</li>
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
              onClick={() => onConfirm(order, code)}
              disabled={isLoading || !code.trim()}
            >
              {isLoading ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Completing...
                </>
              ) : (
                <>
                  <i className="fas fa-check me-2"></i>
                  Complete Order
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EnterCodeDialog;
