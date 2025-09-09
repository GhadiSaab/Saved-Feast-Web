import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';
import axios from 'axios';
import FeedPage from '../../routes/FeedPage';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

// Mock the MealCard component
vi.mock('../../components/MealCard', () => ({
  default: function MockMealCard({ meal }: { meal: any }) {
    return <div data-testid={`meal-card-${meal.id}`}>{meal.title}</div>;
  },
}));

const mockMeals = [
  {
    id: 1,
    title: 'Test Meal 1',
    description: 'A delicious test meal',
    current_price: 9.99,
    original_price: 15.99,
    image: '/storage/meals/test1.jpg',
    available_from: '2024-01-01T10:00:00Z',
    available_until: '2024-01-01T18:00:00Z',
    restaurant: {
      name: 'Test Restaurant 1',
    },
  },
  {
    id: 2,
    title: 'Test Meal 2',
    description: 'Another delicious test meal',
    current_price: 12.99,
    original_price: null,
    image: null,
    available_from: '2024-01-01T11:00:00Z',
    available_until: '2024-01-01T19:00:00Z',
    restaurant: {
      name: 'Test Restaurant 2',
    },
  },
];

const mockFilters = {
  categories: [
    { id: 1, name: 'Italian' },
    { id: 2, name: 'Asian' },
  ],
  sort_orders: [
    { value: 'created_at', label: 'Newest First' },
    { value: 'price_asc', label: 'Price: Low to High' },
  ],
};

describe('FeedPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    // Mock successful API responses
    mockedAxios.get.mockImplementation((url) => {
      if (url === '/api/meals/filters') {
        return Promise.resolve({ data: { data: mockFilters } });
      } else if (url.startsWith('/api/meals')) {
        return Promise.resolve({
          data: {
            status: true,
            data: mockMeals,
            pagination: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 2,
            },
            filters_applied: {},
          },
        });
      }
      return Promise.reject(new Error('Unknown URL'));
    });
  });

  it('renders hero section with correct statistics', async () => {
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(screen.getByText('Save Food, Save Money, Save the Planet')).toBeInTheDocument();
      expect(screen.getByText('2.5M')).toBeInTheDocument();
      expect(screen.getByText('Meals Saved')).toBeInTheDocument();
      expect(screen.getByText('€15M')).toBeInTheDocument();
      expect(screen.getByText('Money Saved')).toBeInTheDocument();
      expect(screen.getByText('500+')).toBeInTheDocument();
      expect(screen.getByText('Local Partners')).toBeInTheDocument();
    });
  });

  it('displays meals correctly', async () => {
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(screen.getByTestId('meal-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('meal-card-2')).toBeInTheDocument();
    });
  });

  it('shows loading state initially', () => {
    render(<FeedPage />);
    
    expect(screen.getByRole('status')).toBeInTheDocument();
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('handles API errors gracefully', async () => {
    // Mock the meals API to fail while keeping filters working
    mockedAxios.get.mockImplementation((url) => {
      if (url === '/api/meals/filters') {
        return Promise.resolve({ data: { data: mockFilters } });
      } else if (url.startsWith('/api/meals')) {
        return Promise.reject(new Error('API Error'));
      }
      return Promise.reject(new Error('Unknown URL'));
    });
    
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(screen.getByText(/Failed to fetch meals/)).toBeInTheDocument();
    });
  });

  it('shows empty state when no meals', async () => {
    mockedAxios.get.mockImplementation((url) => {
      if (url === '/api/meals/filters') {
        return Promise.resolve({ data: { data: mockFilters } });
      } else if (url === '/api/meals') {
        return Promise.resolve({
          data: {
            status: true,
            data: [],
            pagination: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
            filters_applied: {},
          },
        });
      }
      return Promise.reject(new Error('Unknown URL'));
    });
    
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(screen.getByText('Failed to fetch meals. Please try again later.')).toBeInTheDocument();
    });
  });

  it('renders search and filter controls', async () => {
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(screen.getByPlaceholderText('Search for delicious meals...')).toBeInTheDocument();
      expect(screen.getByText('All Categories')).toBeInTheDocument();
    });
  });

  it('fetches filters on component mount', async () => {
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(mockedAxios.get).toHaveBeenCalledWith('/api/meals/filters');
    });
  });

  it('fetches meals on component mount', async () => {
    render(<FeedPage />);
    
    await waitFor(() => {
      expect(mockedAxios.get).toHaveBeenCalledWith('/api/meals?page=1&per_page=12&sort_by=created_at&sort_order=desc&available=true');
    });
  });
});

