import React, { useState } from 'react';
import { orderApi } from '../../api/orders';

interface CodeDisplayProps {
  orderId: string;
  maskedCode?: string;
  canShowCode?: boolean;
  className?: string;
}

const CodeDisplay: React.FC<CodeDisplayProps> = ({
  orderId,
  maskedCode,
  canShowCode = false,
  className = '',
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [showFullCode, setShowFullCode] = useState(false);
  const [fullCode, setFullCode] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleShowCode = async () => {
    if (showFullCode) {
      setShowFullCode(false);
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await orderApi.showPickupCode(orderId);
      if (response.success) {
        setFullCode(response.data.code);
        setShowFullCode(true);
      } else {
        setError('Failed to retrieve pickup code');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to retrieve pickup code');
    } finally {
      setIsLoading(false);
    }
  };

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      // You could add a toast notification here
    } catch (err) {
      console.error('Failed to copy to clipboard:', err);
    }
  };

  if (!canShowCode && !maskedCode) {
    return null;
  }

  return (
    <div className={`bg-gray-50 rounded-lg p-4 ${className}`}>
      <div className="flex items-center justify-between">
        <div>
          <h4 className="text-sm font-medium text-gray-900">Pickup Code</h4>
          <div className="mt-2">
            {showFullCode && fullCode ? (
              <div className="flex items-center space-x-2">
                <span className="text-2xl font-mono font-bold text-indigo-600">
                  {fullCode}
                </span>
                <button
                  onClick={() => copyToClipboard(fullCode)}
                  className="text-gray-400 hover:text-gray-600"
                  title="Copy to clipboard"
                >
                  <svg
                    className="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                  </svg>
                </button>
              </div>
            ) : (
              <span className="text-2xl font-mono font-bold text-gray-600">
                {maskedCode || '••••••'}
              </span>
            )}
          </div>
          {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>

        {canShowCode && (
          <button
            onClick={handleShowCode}
            disabled={isLoading}
            className="ml-4 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-100 rounded-md hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? (
              <svg
                className="w-4 h-4 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                />
                <path
                  className="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
            ) : showFullCode ? (
              'Hide'
            ) : (
              'Show Code'
            )}
          </button>
        )}
      </div>

      {showFullCode && fullCode && (
        <div className="mt-4 p-3 bg-white rounded-md border border-indigo-200">
          <p className="text-xs text-gray-600 mb-2">
            Show this code to the restaurant staff when picking up your order.
          </p>
          <div className="flex items-center justify-center">
            <div className="text-center">
              <div className="text-3xl font-mono font-bold text-indigo-600 mb-2">
                {fullCode}
              </div>
              <button
                onClick={() => copyToClipboard(fullCode)}
                className="text-sm text-indigo-600 hover:text-indigo-800 underline"
              >
                Copy Code
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CodeDisplay;
