import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth';
import { 
  SettlementsSummary, 
  RestaurantInvoice, 
  PaginatedResponse
} from '../types/settlements';

const ProviderSettlementsPage: React.FC = () => {
  const [summary, setSummary] = useState<SettlementsSummary | null>(null);
  const [invoices, setInvoices] = useState<RestaurantInvoice[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchSummary();
    fetchInvoices();
  }, []);

  const fetchSummary = async () => {
    try {
      const response = await axios.get('/api/provider/settlements/summary', {
        headers: {
          Authorization: `Bearer ${auth.getToken()}`,
        },
      });
      setSummary(response.data.data);
    } catch (err: any) {
      console.error('Error fetching summary:', err);
    }
  };

  const fetchInvoices = async (page = 1) => {
    try {
      const response = await axios.get(`/api/provider/settlements/invoices?page=${page}`, {
        headers: {
          Authorization: `Bearer ${auth.getToken()}`,
        },
      });
      const data = response.data.data as PaginatedResponse<RestaurantInvoice>;
      setInvoices(data.data);
      setCurrentPage(data.current_page);
      setTotalPages(data.last_page);
    } catch (err: any) {
      setError('Failed to fetch invoices');
      console.error('Error fetching invoices:', err);
    } finally {
      setLoading(false);
    }
  };

  const handlePageChange = (page: number) => {
    fetchInvoices(page);
  };

  const getStatusBadge = (status: string) => {
    const statusClasses = {
      draft: 'bg-secondary',
      sent: 'bg-info',
      paid: 'bg-success',
      overdue: 'bg-danger',
      void: 'bg-dark',
    };
    return `badge ${statusClasses[status as keyof typeof statusClasses] || 'bg-secondary'}`;
  };

  const formatCurrency = (amount: string) => {
    return `€${parseFloat(amount).toFixed(2)}`;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  if (loading) {
    return (
      <div className="container mt-4">
        <div className="d-flex justify-content-center">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container mt-4">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="page-title">
              <i className="fas fa-receipt me-3"></i>
              Settlements & Invoices
            </h1>
          </div>

          {error && (
            <div className="alert alert-danger">
              <i className="fas fa-exclamation-triangle me-2"></i>
              {error}
            </div>
          )}

          {/* Summary Cards */}
          {summary && (
            <div className="row mb-4">
              <div className="col-md-4">
                <div className="card bg-primary text-white">
                  <div className="card-body">
                    <div className="d-flex justify-content-between">
                      <div>
                        <h6 className="card-title">Amount Owed</h6>
                        <h3 className="mb-0">{summary.amount_owed}</h3>
                      </div>
                      <div className="align-self-center">
                        <i className="fas fa-euro-sign fa-2x"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="card bg-info text-white">
                  <div className="card-body">
                    <div className="d-flex justify-content-between">
                      <div>
                        <h6 className="card-title">Last Invoice</h6>
                        <h5 className="mb-0">
                          {summary.last_invoice_status ? (
                            <span className={`badge ${getStatusBadge(summary.last_invoice_status)}`}>
                              {summary.last_invoice_status.toUpperCase()}
                            </span>
                          ) : (
                            'No invoices yet'
                          )}
                        </h5>
                        {summary.last_invoice_date && (
                          <small>{formatDate(summary.last_invoice_date)}</small>
                        )}
                      </div>
                      <div className="align-self-center">
                        <i className="fas fa-file-invoice fa-2x"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="card bg-success text-white">
                  <div className="card-body">
                    <div className="d-flex justify-content-between">
                      <div>
                        <h6 className="card-title">Next Invoice</h6>
                        <h5 className="mb-0">{formatDate(summary.next_invoice_date)}</h5>
                        <small>Every Monday</small>
                      </div>
                      <div className="align-self-center">
                        <i className="fas fa-calendar-alt fa-2x"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Invoices Table */}
          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-list me-2"></i>
                Invoice History
              </h5>
            </div>
            <div className="card-body">
              {invoices.length === 0 ? (
                <div className="text-center py-5">
                  <i className="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                  <h5 className="text-muted">No invoices yet</h5>
                  <p className="text-muted">
                    Invoices are generated weekly for completed cash-on-pickup orders.
                  </p>
                </div>
              ) : (
                <>
                  <div className="table-responsive">
                    <table className="table table-hover">
                      <thead>
                        <tr>
                          <th>Period</th>
                          <th>Orders</th>
                          <th>Subtotal</th>
                          <th>Commission</th>
                          <th>Status</th>
                          <th>Created</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {invoices.map((invoice) => (
                          <tr key={invoice.id}>
                            <td>
                              <div>
                                <strong>{formatDate(invoice.period_start)}</strong>
                                <br />
                                <small className="text-muted">to {formatDate(invoice.period_end)}</small>
                              </div>
                            </td>
                            <td>
                              <span className="badge bg-light text-dark">
                                {invoice.orders_count} orders
                              </span>
                            </td>
                            <td>{formatCurrency(invoice.subtotal_sales)}</td>
                            <td>
                              <div>
                                {formatCurrency(invoice.commission_total)}
                                <br />
                                <small className="text-muted">
                                  ({invoice.commission_rate}% rate)
                                </small>
                              </div>
                            </td>
                            <td>
                              <span className={`badge ${getStatusBadge(invoice.status)}`}>
                                {invoice.status.toUpperCase()}
                              </span>
                            </td>
                            <td>{formatDate(invoice.created_at)}</td>
                            <td>
                              <div className="btn-group" role="group">
                                <button
                                  className="btn btn-outline-primary btn-sm"
                                  onClick={() => window.open(`/api/provider/settlements/invoices/${invoice.id}`, '_blank')}
                                  title="View Details"
                                >
                                  <i className="fas fa-eye"></i>
                                </button>
                                {invoice.pdf_path && (
                                  <button
                                    className="btn btn-outline-success btn-sm"
                                    onClick={() => window.open(`/api/provider/settlements/invoices/${invoice.id}/download`, '_blank')}
                                    title="Download PDF"
                                  >
                                    <i className="fas fa-download"></i>
                                  </button>
                                )}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  {/* Pagination */}
                  {totalPages > 1 && (
                    <nav aria-label="Invoice pagination">
                      <ul className="pagination justify-content-center">
                        <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                          <button
                            className="page-link"
                            onClick={() => handlePageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                          >
                            Previous
                          </button>
                        </li>
                        {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                          <li key={page} className={`page-item ${currentPage === page ? 'active' : ''}`}>
                            <button
                              className="page-link"
                              onClick={() => handlePageChange(page)}
                            >
                              {page}
                            </button>
                          </li>
                        ))}
                        <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                          <button
                            className="page-link"
                            onClick={() => handlePageChange(currentPage + 1)}
                            disabled={currentPage === totalPages}
                          >
                            Next
                          </button>
                        </li>
                      </ul>
                    </nav>
                  )}
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProviderSettlementsPage;
