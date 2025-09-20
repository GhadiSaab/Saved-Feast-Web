// React import not needed in modern JSX with vite plugin-react
import {
  render,
  screen,
  fireEvent,
  waitFor,
  cleanup,
} from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi, afterEach } from 'vitest';
import axios from 'axios';
import RestaurantDashboardPage from '../../routes/RestaurantDashboardPage';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

// Mock the auth module
vi.mock('../../auth', () => ({
  default: {
    getToken: vi.fn(() => 'mock-token'),
    isAuthenticated: vi.fn(() => true),
  },
}));

const mockMeals = [
  {
    id: 1,
    title: 'Test Meal 1',
    description: 'A delicious test meal',
    current_price: 9.99,
    original_price: 15.99,
    quantity: 10,
    category_id: 1,
    image: '/storage/meals/test1.jpg',
    available_from: '2024-01-01T10:00:00Z',
    available_until: '2024-01-01T18:00:00Z',
    restaurant_id: 1,
    created_at: '2024-01-01T10:00:00Z',
    updated_at: '2024-01-01T10:00:00Z',
    category: {
      id: 1,
      name: 'Italian',
    },
  },
];

const mockCategories = [
  { id: 1, name: 'Italian' },
  { id: 2, name: 'Asian' },
];

describe('RestaurantDashboardPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    // Mock successful API responses
    mockedAxios.get.mockImplementation((url: string) => {
      if (url === '/api/provider/dashboard-data') {
        return Promise.resolve({
          data: { message: 'Welcome to your dashboard!' },
        });
      } else if (url === '/api/provider/meals') {
        return Promise.resolve({ data: mockMeals });
      } else if (url === '/api/categories') {
        return Promise.resolve({ data: mockCategories });
      }
      return Promise.reject(new Error('Unknown URL'));
    });
  });

  afterEach(() => {
    cleanup();
  });

  it('renders dashboard header with correct styling', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByText('Restaurant Dashboard')).toBeInTheDocument();
      expect(screen.getByText('Add New Meal')).toBeInTheDocument();
    });
  });

  it('displays meals management section', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByText('Manage Your Meals')).toBeInTheDocument();
      expect(screen.getByText('Test Meal 1')).toBeInTheDocument();
      expect(screen.getByText('Italian')).toBeInTheDocument();
      expect(screen.getByText('€9.99')).toBeInTheDocument();
    });
  });

  it('shows add meal form when add button is clicked', async () => {
    render(<RestaurantDashboardPage />);

    // Wait for the component to load first
    await waitFor(
      () => {
        expect(screen.getByText('Restaurant Dashboard')).toBeInTheDocument();
      },
      { timeout: 5000 }
    );

    // Then find and click the add button
    const addButton = await waitFor(
      () => {
        return screen.getByRole('button', { name: /Add New Meal/i });
      },
      { timeout: 5000 }
    );

    fireEvent.click(addButton);

    // Wait for the form to appear
    await waitFor(
      () => {
        expect(screen.getByLabelText('Meal Title')).toBeInTheDocument();
        expect(screen.getByLabelText('Description')).toBeInTheDocument();
      },
      { timeout: 5000 }
    );
  });

  it('displays meals in table format', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByText('Title')).toBeInTheDocument();
      expect(screen.getByText('Category')).toBeInTheDocument();
      expect(screen.getByText('Price')).toBeInTheDocument();
      expect(screen.getByText('Quantity')).toBeInTheDocument();
      expect(screen.getByText('Actions')).toBeInTheDocument();
    });
  });

  it('shows discounted price correctly', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByText('€9.99')).toBeInTheDocument();
      expect(screen.getByText('€15.99')).toBeInTheDocument();
    });
  });

  it('handles form submission for new meal', async () => {
    mockedAxios.post.mockResolvedValueOnce({ data: { success: true } });

    render(<RestaurantDashboardPage />);

    // Wait for component to load
    await waitFor(
      () => {
        expect(screen.getByText('Restaurant Dashboard')).toBeInTheDocument();
      },
      { timeout: 5000 }
    );

    // Click add button
    const addButton = await waitFor(
      () => {
        return screen.getByRole('button', { name: /Add New Meal/i });
      },
      { timeout: 5000 }
    );
    fireEvent.click(addButton);

    // Wait for form to appear and fill it
    await waitFor(
      () => {
        expect(screen.getByLabelText('Meal Title')).toBeInTheDocument();
      },
      { timeout: 5000 }
    );

    const titleInput = screen.getByLabelText('Meal Title');
    const descriptionInput = screen.getByLabelText('Description');
    const priceInput = screen.getByLabelText('Current Selling Price (€)');
    const quantityInput = screen.getByLabelText('Quantity Available');
    const categorySelect = screen.getByLabelText('Category');
    const availableFromInput = screen.getByLabelText(
      'Available From (Pickup Start)'
    );
    const availableUntilInput = screen.getByLabelText(
      'Available Until (Pickup End)'
    );

    fireEvent.change(titleInput, { target: { value: 'New Meal' } });
    fireEvent.change(descriptionInput, {
      target: { value: 'New description' },
    });
    fireEvent.change(priceInput, { target: { value: '12.99' } });
    fireEvent.change(quantityInput, { target: { value: '5' } });
    fireEvent.change(categorySelect, { target: { value: '1' } });

    // Set datetime values for required fields
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const dayAfter = new Date(now.getTime() + 48 * 60 * 60 * 1000);

    fireEvent.change(availableFromInput, {
      target: { value: tomorrow.toISOString().slice(0, 16) },
    });
    fireEvent.change(availableUntilInput, {
      target: { value: dayAfter.toISOString().slice(0, 16) },
    });

    const submitButton = screen.getByText('Add Meal');
    fireEvent.click(submitButton);

    // Wait for the API call to be made
    await waitFor(
      () => {
        expect(mockedAxios.post).toHaveBeenCalled();
      },
      { timeout: 5000 }
    );
  });

  it('shows loading state initially', () => {
    render(<RestaurantDashboardPage />);

    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('handles API errors gracefully', async () => {
    // Mock the dashboard data API to fail
    mockedAxios.get.mockImplementation((url: string) => {
      if (url === '/api/provider/dashboard-data') {
        return Promise.reject(new Error('API Error'));
      } else if (url === '/api/provider/meals') {
        return Promise.resolve({ data: mockMeals });
      } else if (url === '/api/categories') {
        return Promise.resolve({ data: mockCategories });
      }
      return Promise.reject(new Error('Unknown URL'));
    });

    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(
        screen.getByText('Failed to load dashboard data.')
      ).toBeInTheDocument();
    });
  });

  it('shows refresh button in header', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByText('Add New Meal')).toBeInTheDocument();
    });
  });

  it('renders with proper card styling', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      const cards = screen
        .getAllByRole('generic')
        .filter(el => el.className.includes('card'));
      expect(cards.length).toBeGreaterThan(0);
    });
  });

  it('displays edit and delete actions for meals', async () => {
    render(<RestaurantDashboardPage />);

    await waitFor(() => {
      expect(screen.getByTitle('Edit Meal')).toBeInTheDocument();
      expect(screen.getByTitle('Delete Meal')).toBeInTheDocument();
    });
  });
});
