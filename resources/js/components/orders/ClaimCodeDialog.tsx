import React, { useState, useEffect } from 'react';
import { Order } from '../../types/orders';

interface ClaimCodeDialogProps {
  isOpen: boolean;
  order: Order | null;
  isLoading: boolean;
  code?: string;
  expiresAt?: Date;
  onClose: () => void;
}

const ClaimCodeDialog: React.FC<ClaimCodeDialogProps> = ({
  isOpen,
  order,
  isLoading,
  code,
  expiresAt,
  onClose,
}) => {
  const [timeLeft, setTimeLeft] = useState<number>(0);

  useEffect(() => {
    if (!expiresAt) return;

    const updateTimer = () => {
      const now = new Date().getTime();
      const expiry = expiresAt.getTime();
      const difference = expiry - now;

      if (difference > 0) {
        setTimeLeft(Math.ceil(difference / 1000));
      } else {
        setTimeLeft(0);
      }
    };

    updateTimer();
    const interval = setInterval(updateTimer, 1000);

    return () => clearInterval(interval);
  }, [expiresAt]);

  const formatTime = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
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
              <i className="fas fa-key me-2"></i>
              Claim Code Generated
            </h5>
            <button
              type="button"
              className="btn-close btn-close-white"
              onClick={onClose}
              disabled={isLoading}
            ></button>
          </div>

          <div className="modal-body text-center p-4">
            {isLoading ? (
              <div className="py-4">
                <div className="spinner-border text-success mb-3" role="status">
                  <span className="visually-hidden">Generating code...</span>
                </div>
                <h6 className="text-muted">Generating your claim code...</h6>
              </div>
            ) : code ? (
              <div>
                <div className="mb-4">
                  <h6 className="text-muted mb-3">
                    Show this code to the restaurant to claim your order:
                  </h6>
                  <div className="bg-light rounded-3 p-4 mb-3">
                    <h1 className="display-4 fw-bold text-success mb-0 font-monospace">
                      {code}
                    </h1>
                  </div>

                  {timeLeft > 0 && (
                    <div className="alert alert-warning border-0">
                      <i className="fas fa-clock me-2"></i>
                      <strong>Code expires in: {formatTime(timeLeft)}</strong>
                    </div>
                  )}

                  {timeLeft === 0 && (
                    <div className="alert alert-danger border-0">
                      <i className="fas fa-exclamation-triangle me-2"></i>
                      <strong>Code has expired!</strong>
                    </div>
                  )}
                </div>

                <div className="alert alert-info border-0 text-start">
                  <h6 className="alert-heading">
                    <i className="fas fa-info-circle me-2"></i>
                    Instructions:
                  </h6>
                  <ol className="mb-0">
                    <li>Go to the restaurant</li>
                    <li>Show this code to the staff</li>
                    <li>They will enter the code to complete your order</li>
                    <li>Code is valid for 5 minutes only</li>
                  </ol>
                </div>
              </div>
            ) : null}
          </div>

          <div className="modal-footer border-0">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onClose}
              disabled={isLoading}
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ClaimCodeDialog;
