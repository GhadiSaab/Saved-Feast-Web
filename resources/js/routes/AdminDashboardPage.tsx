import React, { useState, useEffect } from 'react';
import axios from 'axios';
import auth from '../auth';

// Interfaces for TypeScript
interface DashboardData {
  overview: {
    users: {
      total: number;
      new_this_month: number;
      active: number;
      role_distribution: Array<{ name: string; count: number }>;
    };
    orders: {
      total: number;
      this_month: number;
      status_distribution: Array<{ status: string; count: number }>;
    };
    revenue: {
      total: number;
      this_month: number;
    };
    restaurants: {
      total: number;
      active: number;
    };
    meals: {
      total: number;
      active: number;
      categories: number;
    };
    reviews: {
      total: number;
      average_rating: number;
    };
  };
  recent_activity: {
    orders: Array<any>;
    users: Array<any>;
  };
  analytics: {
    daily_sales: Array<any>;
    top_restaurants: Array<any>;
    category_performance: Array<any>;
  };
}

interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  roles: Array<{ name: string }>;
  orders_count: number;
  reviews_count: number;
  created_at: string;
}

interface Restaurant {
  id: number;
  name: string;
  user: User;
  meals_count: number;
  orders_count: number;
  orders_sum_total_amount: number;
  created_at: string;
}

interface Order {
  id: number;
  user: User;
  total_amount: number;
  status: string;
  created_at: string;
  order_items: Array<any>;
}

interface Meal {
  id: number;
  title: string;
  restaurant: Restaurant;
  category: { name: string };
  current_price: number;
  quantity: number;
  created_at: string;
}

const AdminDashboardPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<
    'overview' | 'users' | 'restaurants' | 'orders' | 'meals' | 'analytics'
  >('overview');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Dashboard data
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(
    null
  );

  // Management data
  const [users, setUsers] = useState<User[]>([]);
  const [restaurants, setRestaurants] = useState<Restaurant[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [meals, setMeals] = useState<Meal[]>([]);

  // Pagination
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Search and filters
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [roleFilter, setRoleFilter] = useState('');

  const fetchDashboardData = async () => {
    try {
      const token = auth.getToken();

      const response = await axios.get('/api/admin/dashboard', {
        headers: { Authorization: `Bearer ${token}` },
      });

      setDashboardData(response.data);
    } catch (err: any) {
      console.error('Error fetching dashboard data:', err);
      setError('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  const fetchUsers = async (page = 1, search = '', role = '') => {
    try {
      const token = auth.getToken();
      const params = new URLSearchParams({
        page: page.toString(),
        ...(search && { search }),
        ...(role && { role }),
      });

      const response = await axios.get(`/api/admin/users?${params}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setUsers(response.data.data);
      setCurrentPage(response.data.current_page);
      setTotalPages(response.data.last_page);
    } catch (err: any) {
      console.error('Error fetching users:', err);
      setError('Failed to load users');
    }
  };

  const fetchRestaurants = async (page = 1, search = '') => {
    try {
      const token = auth.getToken();
      const params = new URLSearchParams({
        page: page.toString(),
        ...(search && { search }),
      });

      const response = await axios.get(`/api/admin/restaurants?${params}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setRestaurants(response.data.data);
      setCurrentPage(response.data.current_page);
      setTotalPages(response.data.last_page);
    } catch (err: any) {
      console.error('Error fetching restaurants:', err);
      setError('Failed to load restaurants');
    }
  };

  const fetchOrders = async (page = 1, status = '') => {
    try {
      const token = auth.getToken();
      const params = new URLSearchParams({
        page: page.toString(),
        ...(status && { status }),
      });

      const response = await axios.get(`/api/admin/orders?${params}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setOrders(response.data.data);
      setCurrentPage(response.data.current_page);
      setTotalPages(response.data.last_page);
    } catch (err: any) {
      console.error('Error fetching orders:', err);
      setError('Failed to load orders');
    }
  };

  const fetchMeals = async (page = 1, search = '') => {
    try {
      const token = auth.getToken();
      const params = new URLSearchParams({
        page: page.toString(),
        ...(search && { search }),
      });

      const response = await axios.get(`/api/admin/meals?${params}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setMeals(response.data.data);
      setCurrentPage(response.data.current_page);
      setTotalPages(response.data.last_page);
    } catch (err: any) {
      console.error('Error fetching meals:', err);
      setError('Failed to load meals');
    }
  };

  const updateUserRole = async (userId: number, role: string) => {
    try {
      const token = auth.getToken();
      await axios.put(
        `/api/admin/users/${userId}/role`,
        { role },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      fetchUsers(currentPage, searchTerm, roleFilter);
    } catch (err: any) {
      console.error('Error updating user role:', err);
      setError('Failed to update user role');
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  useEffect(() => {
    if (activeTab === 'users') {
      fetchUsers(1, searchTerm, roleFilter);
    } else if (activeTab === 'restaurants') {
      fetchRestaurants(1, searchTerm);
    } else if (activeTab === 'orders') {
      fetchOrders(1, statusFilter);
    } else if (activeTab === 'meals') {
      fetchMeals(1, searchTerm);
    }
  }, [activeTab]);

  const handleSearch = () => {
    if (activeTab === 'users') {
      fetchUsers(1, searchTerm, roleFilter);
    } else if (activeTab === 'restaurants') {
      fetchRestaurants(1, searchTerm);
    } else if (activeTab === 'meals') {
      fetchMeals(1, searchTerm);
    }
  };

  const handleStatusFilter = () => {
    if (activeTab === 'orders') {
      fetchOrders(1, statusFilter);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center mt-5">
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Loading...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="alert alert-danger" role="alert">
        {error}
      </div>
    );
  }

  const renderOverview = () => {
    if (!dashboardData) {
      return <div>Loading dashboard data...</div>;
    }

    const { overview, recent_activity } = dashboardData;

    return (
      <div>
        {/* Key Metrics */}
        <div className="row mb-4">
          <div className="col-md-3 mb-3">
            <div className="card bg-primary text-white">
              <div className="card-body">
                <div className="d-flex justify-content-between">
                  <div>
                    <h4 className="card-title">
                      {overview?.users?.total ?? 0}
                    </h4>
                    <p className="card-text">Total Users</p>
                  </div>
                  <div className="align-self-center">
                    <i className="fas fa-users fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card bg-success text-white">
              <div className="card-body">
                <div className="d-flex justify-content-between">
                  <div>
                    <h4 className="card-title">€{overview.revenue.total}</h4>
                    <p className="card-text">Total Revenue</p>
                  </div>
                  <div className="align-self-center">
                    <i className="fas fa-euro-sign fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card bg-info text-white">
              <div className="card-body">
                <div className="d-flex justify-content-between">
                  <div>
                    <h4 className="card-title">{overview.orders.total}</h4>
                    <p className="card-text">Total Orders</p>
                  </div>
                  <div className="align-self-center">
                    <i className="fas fa-shopping-cart fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card bg-warning text-white">
              <div className="card-body">
                <div className="d-flex justify-content-between">
                  <div>
                    <h4 className="card-title">{overview.restaurants.total}</h4>
                    <p className="card-text">Restaurants</p>
                  </div>
                  <div className="align-self-center">
                    <i className="fas fa-store fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Detailed Stats */}
        <div className="row mb-4">
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">
                <h5 className="mb-0">User Statistics</h5>
              </div>
              <div className="card-body">
                <div className="row">
                  <div className="col-6">
                    <p>
                      <strong>New This Month:</strong>{' '}
                      {overview.users.new_this_month}
                    </p>
                    <p>
                      <strong>Active Users:</strong> {overview.users.active}
                    </p>
                  </div>
                  <div className="col-6">
                    <p>
                      <strong>Total Meals:</strong> {overview.meals.total}
                    </p>
                    <p>
                      <strong>Active Meals:</strong> {overview.meals.active}
                    </p>
                  </div>
                </div>
                <div className="mt-3">
                  <h6>Role Distribution:</h6>
                  {overview.users.role_distribution.map((role, index) => (
                    <div key={index} className="d-flex justify-content-between">
                      <span className="text-capitalize">{role.name}:</span>
                      <span className="badge bg-primary">{role.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">
                <h5 className="mb-0">Order Statistics</h5>
              </div>
              <div className="card-body">
                <div className="row">
                  <div className="col-6">
                    <p>
                      <strong>Orders This Month:</strong>{' '}
                      {overview.orders.this_month}
                    </p>
                    <p>
                      <strong>Revenue This Month:</strong> €
                      {overview.revenue.this_month}
                    </p>
                  </div>
                  <div className="col-6">
                    <p>
                      <strong>Active Restaurants:</strong>{' '}
                      {overview.restaurants.active}
                    </p>
                    <p>
                      <strong>Average Rating:</strong>{' '}
                      {overview.reviews.average_rating}/5
                    </p>
                  </div>
                </div>
                <div className="mt-3">
                  <h6>Order Status:</h6>
                  {overview.orders.status_distribution.map((status, index) => (
                    <div key={index} className="d-flex justify-content-between">
                      <span className="text-capitalize">{status.status}:</span>
                      <span className="badge bg-secondary">{status.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Recent Activity */}
        <div className="row">
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">
                <h5 className="mb-0">Recent Orders</h5>
              </div>
              <div className="card-body">
                {recent_activity.orders.map(order => (
                  <div
                    key={order.id}
                    className="d-flex justify-content-between align-items-center mb-2"
                  >
                    <div>
                      <strong>Order #{order.id}</strong>
                      <br />
                      <small className="text-muted">
                        {order.user?.first_name} {order.user?.last_name} - €
                        {order.total_amount}
                      </small>
                    </div>
                    <span
                      className={`badge bg-${order.status === 'completed' ? 'success' : order.status === 'pending' ? 'warning' : 'danger'}`}
                    >
                      {order.status}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">
                <h5 className="mb-0">Recent Users</h5>
              </div>
              <div className="card-body">
                {recent_activity.users.map(user => (
                  <div
                    key={user.id}
                    className="d-flex justify-content-between align-items-center mb-2"
                  >
                    <div>
                      <strong>
                        {user.first_name} {user.last_name}
                      </strong>
                      <br />
                      <small className="text-muted">{user.email}</small>
                    </div>
                    <span className="badge bg-info">
                      {user.roles?.[0]?.name || 'No Role'}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderUsers = () => (
    <div>
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">User Management</h5>
        </div>
        <div className="card-body">
          <div className="row mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search users..."
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
              />
            </div>
            <div className="col-md-3">
              <select
                className="form-select"
                value={roleFilter}
                onChange={e => setRoleFilter(e.target.value)}
              >
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="provider">Provider</option>
                <option value="consumer">Consumer</option>
              </select>
            </div>
            <div className="col-md-2">
              <button className="btn btn-primary" onClick={handleSearch}>
                <i className="fas fa-search"></i> Search
              </button>
            </div>
          </div>

          <div className="table-responsive">
            <table className="table table-striped">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Orders</th>
                  <th>Reviews</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map(user => (
                  <tr key={user.id}>
                    <td>
                      {user.first_name} {user.last_name}
                    </td>
                    <td>{user.email}</td>
                    <td>
                      <select
                        className="form-select form-select-sm"
                        value={user.roles?.[0]?.name || ''}
                        onChange={e => updateUserRole(user.id, e.target.value)}
                      >
                        <option value="consumer">Consumer</option>
                        <option value="provider">Provider</option>
                        <option value="admin">Admin</option>
                      </select>
                    </td>
                    <td>{user.orders_count}</td>
                    <td>{user.reviews_count}</td>
                    <td>{new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                      <button className="btn btn-sm btn-outline-primary">
                        <i className="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );

  const renderRestaurants = () => (
    <div>
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">Restaurant Management</h5>
        </div>
        <div className="card-body">
          <div className="row mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search restaurants..."
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
              />
            </div>
            <div className="col-md-2">
              <button className="btn btn-primary" onClick={handleSearch}>
                <i className="fas fa-search"></i> Search
              </button>
            </div>
          </div>

          <div className="table-responsive">
            <table className="table table-striped">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Owner</th>
                  <th>Meals</th>
                  <th>Orders</th>
                  <th>Revenue</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {restaurants.map(restaurant => (
                  <tr key={restaurant.id}>
                    <td>{restaurant.name}</td>
                    <td>
                      {restaurant.user?.first_name} {restaurant.user?.last_name}
                    </td>
                    <td>{restaurant.meals_count}</td>
                    <td>{restaurant.orders_count}</td>
                    <td>€{restaurant.orders_sum_total_amount || 0}</td>
                    <td>
                      {new Date(restaurant.created_at).toLocaleDateString()}
                    </td>
                    <td>
                      <button className="btn btn-sm btn-outline-primary">
                        <i className="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );

  const renderOrders = () => (
    <div>
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">Order Management</h5>
        </div>
        <div className="card-body">
          <div className="row mb-3">
            <div className="col-md-3">
              <select
                className="form-select"
                value={statusFilter}
                onChange={e => setStatusFilter(e.target.value)}
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div className="col-md-2">
              <button className="btn btn-primary" onClick={handleStatusFilter}>
                <i className="fas fa-filter"></i> Filter
              </button>
            </div>
          </div>

          <div className="table-responsive">
            <table className="table table-striped">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {orders.map(order => (
                  <tr key={order.id}>
                    <td>#{order.id}</td>
                    <td>
                      {order.user?.first_name} {order.user?.last_name}
                    </td>
                    <td>€{order.total_amount}</td>
                    <td>
                      <span
                        className={`badge bg-${order.status === 'completed' ? 'success' : order.status === 'pending' ? 'warning' : 'danger'}`}
                      >
                        {order.status}
                      </span>
                    </td>
                    <td>{new Date(order.created_at).toLocaleDateString()}</td>
                    <td>
                      <button className="btn btn-sm btn-outline-primary">
                        <i className="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );

  const renderMeals = () => (
    <div>
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">Meal Management</h5>
        </div>
        <div className="card-body">
          <div className="row mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search meals..."
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
              />
            </div>
            <div className="col-md-2">
              <button className="btn btn-primary" onClick={handleSearch}>
                <i className="fas fa-search"></i> Search
              </button>
            </div>
          </div>

          <div className="table-responsive">
            <table className="table table-striped">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Restaurant</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {meals.map(meal => (
                  <tr key={meal.id}>
                    <td>{meal.title}</td>
                    <td>{meal.restaurant?.name}</td>
                    <td>{meal.category?.name}</td>
                    <td>€{meal.current_price}</td>
                    <td>
                      <span
                        className={`badge bg-${meal.quantity > 0 ? 'success' : 'danger'}`}
                      >
                        {meal.quantity}
                      </span>
                    </td>
                    <td>{new Date(meal.created_at).toLocaleDateString()}</td>
                    <td>
                      <button className="btn btn-sm btn-outline-primary">
                        <i className="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );

  const renderAnalytics = () => (
    <div>
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">System Analytics</h5>
        </div>
        <div className="card-body">
          <p className="text-muted">
            Advanced analytics and reporting features will be implemented here.
          </p>
          <div className="row">
            <div className="col-md-6">
              <div className="card">
                <div className="card-body">
                  <h6>Revenue Trends</h6>
                  <p className="text-muted">Chart showing revenue over time</p>
                </div>
              </div>
            </div>
            <div className="col-md-6">
              <div className="card">
                <div className="card-body">
                  <h6>User Growth</h6>
                  <p className="text-muted">
                    Chart showing user registration trends
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h1>Admin Dashboard</h1>
        <div className="text-muted">
          <i className="fas fa-shield-alt me-2"></i>
          Administrator Panel
        </div>
      </div>

      {/* Navigation Tabs */}
      <ul className="nav nav-tabs mb-4">
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'overview' ? 'active' : ''}`}
            onClick={() => setActiveTab('overview')}
          >
            <i className="fas fa-chart-pie me-2"></i>
            Overview
          </button>
        </li>
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'users' ? 'active' : ''}`}
            onClick={() => setActiveTab('users')}
          >
            <i className="fas fa-users me-2"></i>
            Users
          </button>
        </li>
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'restaurants' ? 'active' : ''}`}
            onClick={() => setActiveTab('restaurants')}
          >
            <i className="fas fa-store me-2"></i>
            Restaurants
          </button>
        </li>
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'orders' ? 'active' : ''}`}
            onClick={() => setActiveTab('orders')}
          >
            <i className="fas fa-shopping-cart me-2"></i>
            Orders
          </button>
        </li>
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'meals' ? 'active' : ''}`}
            onClick={() => setActiveTab('meals')}
          >
            <i className="fas fa-utensils me-2"></i>
            Meals
          </button>
        </li>
        <li className="nav-item">
          <button
            className={`nav-link ${activeTab === 'analytics' ? 'active' : ''}`}
            onClick={() => setActiveTab('analytics')}
          >
            <i className="fas fa-chart-line me-2"></i>
            Analytics
          </button>
        </li>
      </ul>

      {/* Content */}
      {activeTab === 'overview' && renderOverview()}
      {activeTab === 'users' && renderUsers()}
      {activeTab === 'restaurants' && renderRestaurants()}
      {activeTab === 'orders' && renderOrders()}
      {activeTab === 'meals' && renderMeals()}
      {activeTab === 'analytics' && renderAnalytics()}

      {/* Pagination */}
      {totalPages > 1 && (
        <nav className="mt-4">
          <ul className="pagination justify-content-center">
            <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
              <button
                className="page-link"
                onClick={() => {
                  const newPage = currentPage - 1;
                  if (newPage >= 1) {
                    setCurrentPage(newPage);
                    if (activeTab === 'users')
                      fetchUsers(newPage, searchTerm, roleFilter);
                    else if (activeTab === 'restaurants')
                      fetchRestaurants(newPage, searchTerm);
                    else if (activeTab === 'orders')
                      fetchOrders(newPage, statusFilter);
                    else if (activeTab === 'meals')
                      fetchMeals(newPage, searchTerm);
                  }
                }}
              >
                Previous
              </button>
            </li>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
              <li
                key={page}
                className={`page-item ${currentPage === page ? 'active' : ''}`}
              >
                <button
                  className="page-link"
                  onClick={() => {
                    setCurrentPage(page);
                    if (activeTab === 'users')
                      fetchUsers(page, searchTerm, roleFilter);
                    else if (activeTab === 'restaurants')
                      fetchRestaurants(page, searchTerm);
                    else if (activeTab === 'orders')
                      fetchOrders(page, statusFilter);
                    else if (activeTab === 'meals')
                      fetchMeals(page, searchTerm);
                  }}
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
                onClick={() => {
                  const newPage = currentPage + 1;
                  if (newPage <= totalPages) {
                    setCurrentPage(newPage);
                    if (activeTab === 'users')
                      fetchUsers(newPage, searchTerm, roleFilter);
                    else if (activeTab === 'restaurants')
                      fetchRestaurants(newPage, searchTerm);
                    else if (activeTab === 'orders')
                      fetchOrders(newPage, statusFilter);
                    else if (activeTab === 'meals')
                      fetchMeals(newPage, searchTerm);
                  }
                }}
              >
                Next
              </button>
            </li>
          </ul>
        </nav>
      )}
    </div>
  );
};

export default AdminDashboardPage;
