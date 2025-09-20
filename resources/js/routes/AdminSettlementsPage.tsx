import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth';
import { RestaurantInvoice, PaginatedResponse } from '../types/settlements';

const AdminSettlementsPage: React.FC = () => {
  const [invoices, setInvoices] = useState<RestaurantInvoice[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [generating, setGenerating] = useState(false);

  useEffect(() => {
    fetchInvoices();
  }, []);

  const fetchInvoices = async (page = 1) => {
    try {
      const response = await axios.get(
        `/api/admin/settlements/invoices?page=${page}`,
        {
          headers: {
            Authorization: `Bearer ${auth.getToken()}`,
          },
        }
      );
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

  const handleGenerateInvoices = async () => {
    setGenerating(true);
    try {
      const response = await axios.post(
        '/api/admin/settlements/generate?period=weekly',
        {},
        {
          headers: {
            Authorization: `Bearer ${auth.getToken()}`,
          },
        }
      );

      if (response.data.status) {
        alert(
          `Invoice generation completed! Created ${response.data.data.invoices_created} invoices for ${response.data.data.orders_processed} orders.`
        );
        fetchInvoices(); // Refresh the list
      }
    } catch (err: any) {
      alert(
        'Failed to generate invoices: ' +
          (err.response?.data?.message || err.message)
      );
    } finally {
      setGenerating(false);
    }
  };

  const handleStatusChange = async (
    invoiceId: number,
    action: 'sent' | 'paid' | 'overdue'
  ) => {
    try {
      const response = await axios.post(
        `/api/admin/settlements/invoices/${invoiceId}/mark-${action}`,
        {},
        {
          headers: {
            Authorization: `Bearer ${auth.getToken()}`,
          },
        }
      );

      if (response.data.status) {
        fetchInvoices(); // Refresh the list
      }
    } catch (err: any) {
      alert(
        'Failed to update invoice status: ' +
          (err.response?.data?.message || err.message)
      );
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
              Admin - Settlements & Invoices
            </h1>
            <button
              className="btn btn-primary"
              onClick={handleGenerateInvoices}
              disabled={generating}
            >
              {generating ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Generating...
                </>
              ) : (
                <>
                  <i className="fas fa-plus me-2"></i>
                  Generate Weekly Invoices
                </>
              )}
            </button>
          </div>

          {error && (
            <div className="alert alert-danger">
              <i className="fas fa-exclamation-triangle me-2"></i>
              {error}
            </div>
          )}

          {/* Invoices Table */}
          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-list me-2"></i>
                All Restaurant Invoices
              </h5>
            </div>
            <div className="card-body">
              {invoices.length === 0 ? (
                <div className="text-center py-5">
                  <i className="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                  <h5 className="text-muted">No invoices found</h5>
                  <p className="text-muted">
                    Generate weekly invoices to see them here.
                  </p>
                </div>
              ) : (
                <>
                  <div className="table-responsive">
                    <table className="table table-hover">
                      <thead>
                        <tr>
                          <th>Restaurant</th>
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
                        {invoices.map(invoice => (
                          <tr key={invoice.id}>
                            <td>
                              <div>
                                <strong>
                                  {invoice.restaurant?.name || 'Unknown'}
                                </strong>
                                <br />
                                <small className="text-muted">
                                  {invoice.restaurant?.email}
                                </small>
                              </div>
                            </td>
                            <td>
                              <div>
                                <strong>
                                  {formatDate(invoice.period_start)}
                                </strong>
                                <br />
                                <small className="text-muted">
                                  to {formatDate(invoice.period_end)}
                                </small>
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
                              <span
                                className={`badge ${getStatusBadge(invoice.status)}`}
                              >
                                {invoice.status.toUpperCase()}
                              </span>
                            </td>
                            <td>{formatDate(invoice.created_at)}</td>
                            <td>
                              <div className="btn-group" role="group">
                                <button
                                  className="btn btn-outline-primary btn-sm"
                                  onClick={() =>
                                    window.open(
                                      `/api/admin/settlements/invoices/${invoice.id}`,
                                      '_blank'
                                    )
                                  }
                                  title="View Details"
                                >
                                  <i className="fas fa-eye"></i>
                                </button>
                                {invoice.pdf_path && (
                                  <button
                                    className="btn btn-outline-success btn-sm"
                                    onClick={() =>
                                      window.open(
                                        `/api/admin/settlements/invoices/${invoice.id}/download`,
                                        '_blank'
                                      )
                                    }
                                    title="Download PDF"
                                  >
                                    <i className="fas fa-download"></i>
                                  </button>
                                )}
                                {invoice.status === 'draft' && (
                                  <button
                                    className="btn btn-outline-info btn-sm"
                                    onClick={() =>
                                      handleStatusChange(invoice.id, 'sent')
                                    }
                                    title="Mark as Sent"
                                  >
                                    <i className="fas fa-paper-plane"></i>
                                  </button>
                                )}
                                {['sent', 'overdue'].includes(
                                  invoice.status
                                ) && (
                                  <button
                                    className="btn btn-outline-success btn-sm"
                                    onClick={() =>
                                      handleStatusChange(invoice.id, 'paid')
                                    }
                                    title="Mark as Paid"
                                  >
                                    <i className="fas fa-check"></i>
                                  </button>
                                )}
                                {invoice.status === 'sent' && (
                                  <button
                                    className="btn btn-outline-warning btn-sm"
                                    onClick={() =>
                                      handleStatusChange(invoice.id, 'overdue')
                                    }
                                    title="Mark as Overdue"
                                  >
                                    <i className="fas fa-exclamation-triangle"></i>
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
                        <li
                          className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}
                        >
                          <button
                            className="page-link"
                            onClick={() => handlePageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                          >
                            Previous
                          </button>
                        </li>
                        {Array.from(
                          { length: totalPages },
                          (_, i) => i + 1
                        ).map(page => (
                          <li
                            key={page}
                            className={`page-item ${currentPage === page ? 'active' : ''}`}
                          >
                            <button
                              className="page-link"
                              onClick={() => handlePageChange(page)}
                            >
                              {page}
                            </button>
                          </li>
                        ))}
                        <li
                          className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}
                        >
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

export default AdminSettlementsPage;
