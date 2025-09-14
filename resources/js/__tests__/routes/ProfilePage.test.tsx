import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';
import axios from 'axios';
import ProfilePage from '../../routes/ProfilePage';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

// Mock Chart.js components
vi.mock('react-chartjs-2', () => ({
  Bar: () => <div data-testid="bar-chart">Bar Chart</div>,
}));

// Mock the AuthContext
vi.mock('../../context/AuthContext', () => ({
  AuthProvider: ({ children }: { children: React.ReactNode }) => children,
  useAuth: () => mockAuthContext,
}));

const mockUser = {
  id: 1,
  first_name: 'John',
  last_name: 'Doe',
  email: 'john.doe@example.com',
  roles: [{ name: 'consumer' }],
};

// Mock the AuthContext
const mockAuthContext = {
  user: mockUser,
  isAuthenticated: true,
  login: vi.fn(),
  logout: vi.fn(),
  loading: false,
};

const mockOrders = [
  {
    id: 1,
    status: 'completed',
    total_amount: 25.99,
    created_at: '2024-01-01T10:00:00Z',
    order_items: [
      {
        id: 1,
        quantity: 2,
        price: 9.99,
        original_price: 15.99,
        meal: {
          name: 'Test Meal',
        },
      },
    ],
  },
];

const renderWithAuthProvider = (component: React.ReactElement) => {
  return render(component);
};

describe('ProfilePage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    // Mock successful API responses
    mockedAxios.get.mockImplementation((url: string) => {
      if (url === '/api/orders') {
        return Promise.resolve({
          data: {
            status: true,
            data: mockOrders,
          },
        });
      } else if (url === '/api/user/profile') {
        return Promise.resolve({
          data: {
            status: true,
            data: mockUser,
          },
        });
      }
      return Promise.reject(new Error('Unknown URL'));
    });
  });

  it('renders profile header with correct styling', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText('My Profile')).toBeInTheDocument();
      expect(screen.getByText(/Manage your account settings/)).toBeInTheDocument();
    });
  });

  it('displays user statistics cards', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText('1')).toBeInTheDocument(); // Orders completed
      expect(screen.getByText('Orders Completed')).toBeInTheDocument();
      expect(screen.getByText('2')).toBeInTheDocument(); // Food items saved
      expect(screen.getByText('Food Items Saved')).toBeInTheDocument();
      expect(screen.getByText('€12.00')).toBeInTheDocument(); // Money saved
      expect(screen.getByText('Money Saved')).toBeInTheDocument();
    });
  });

  it('shows profile information section', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText('Profile Information')).toBeInTheDocument();
      expect(screen.getByText('John Doe')).toBeInTheDocument();
      expect(screen.getByText('john.doe@example.com')).toBeInTheDocument();
    });
  });

  it('shows change password section', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Change Password' })).toBeInTheDocument();
      expect(screen.getByLabelText('Current Password')).toBeInTheDocument();
      expect(screen.getByLabelText('New Password')).toBeInTheDocument();
      expect(screen.getByLabelText('Confirm New Password')).toBeInTheDocument();
    });
  });

  it('shows impact visualization section', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText('Your Impact')).toBeInTheDocument();
      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
  });

  it('allows editing profile information', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      const editButton = screen.getByText('Edit Info');
      fireEvent.click(editButton);
      
      expect(screen.getByDisplayValue('John Doe')).toBeInTheDocument();
      expect(screen.getByDisplayValue('john.doe@example.com')).toBeInTheDocument();
      expect(screen.getByText('Save Info')).toBeInTheDocument();
    });
  });

  it('shows loading state initially', () => {
    renderWithAuthProvider(<ProfilePage />);
    
    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('handles API errors gracefully', async () => {
    mockedAxios.get.mockRejectedValueOnce(new Error('API Error'));
    
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText(/An error occurred while fetching order history/)).toBeInTheDocument();
    });
  });

  it('calculates statistics correctly', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      // 2 items * (15.99 - 9.99) = 12.00 saved
      expect(screen.getByText('€12.00')).toBeInTheDocument();
      expect(screen.getByText('2')).toBeInTheDocument(); // Food items saved
      expect(screen.getByText('1')).toBeInTheDocument(); // Orders completed
    });
  });

  it('shows refresh button in header', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      expect(screen.getByText('Refresh')).toBeInTheDocument();
    });
  });

  it('renders with proper card styling', async () => {
    renderWithAuthProvider(<ProfilePage />);
    
    await waitFor(() => {
      const cards = screen.getAllByRole('generic').filter(el => 
        el.className.includes('card')
      );
      expect(cards.length).toBeGreaterThan(0);
    });
  });
});

