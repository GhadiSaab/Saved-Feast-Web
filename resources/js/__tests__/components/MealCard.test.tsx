import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';
import MealCard from '../../components/MealCard';
import { CartProvider } from '../../context/CartContext';
import auth from '../../auth';

// Mock the auth module
vi.mock('../../auth', () => ({
  default: {
    isAuthenticated: vi.fn(),
    getToken: vi.fn(),
  },
}));

// Mock the useNavigate hook
const mockNavigate = vi.fn();
vi.mock('react-router-dom', () => ({
  ...vi.importActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

const mockMeal = {
  id: 1,
  title: 'Test Meal',
  description: 'A delicious test meal',
  current_price: 9.99,
  original_price: 15.99,
  image: '/storage/meals/test-image.jpg',
  available_from: '2024-01-01T10:00:00Z',
  available_until: '2024-01-01T18:00:00Z',
  restaurant: {
    name: 'Test Restaurant',
  },
  category: {
    id: 1,
    name: 'Test Category',
  },
};

const mockMealWithoutDiscount = {
  ...mockMeal,
  original_price: null,
};

const mockMealWithoutImage = {
  ...mockMeal,
  image: null,
};

const renderWithCartProvider = (component: React.ReactElement) => {
  return render(<CartProvider>{component}</CartProvider>);
};

describe('MealCard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    (auth.isAuthenticated as any).mockReturnValue(true);
  });

  it('renders meal information correctly', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    expect(screen.getByText('Test Meal')).toBeInTheDocument();
    expect(screen.getByText('A delicious test meal')).toBeInTheDocument();
    expect(screen.getAllByText('Test Restaurant')).toHaveLength(2); // Badge and subtitle
    expect(screen.getByText('€9.99')).toBeInTheDocument();
  });

  it('displays discounted price correctly without overlap', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const currentPrice = screen.getByText('€9.99');
    const originalPrice = screen.getByText('€15.99');
    const savings = screen.getByText('Save €6.00');

    expect(currentPrice).toBeInTheDocument();
    expect(originalPrice).toBeInTheDocument();
    expect(savings).toBeInTheDocument();

    // Check that prices are in a flex-column container to prevent overlap
    const priceContainer = currentPrice.closest('.d-flex.flex-column');
    expect(priceContainer).toBeInTheDocument();
  });

  it('displays regular price when no discount', () => {
    renderWithCartProvider(<MealCard meal={mockMealWithoutDiscount} />);

    expect(screen.getByText('€9.99')).toBeInTheDocument();
    expect(screen.queryByText('€15.99')).not.toBeInTheDocument();
  });

  it('shows image when available', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const image = screen.getByAltText('Test Meal');
    expect(image).toBeInTheDocument();
    expect(image).toHaveAttribute(
      'src',
      expect.stringContaining('/storage/meals/test-image.jpg')
    );
  });

  it('shows placeholder when no image', () => {
    renderWithCartProvider(<MealCard meal={mockMealWithoutImage} />);

    expect(screen.queryByAltText('Test Meal')).not.toBeInTheDocument();
    expect(screen.getByText('Click to view')).toBeInTheDocument();
  });

  it('opens meal detail modal when card is clicked', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const card = screen.getByText('Test Meal').closest('.card');
    fireEvent.click(card!);

    // Check for modal-specific elements that are unique to the modal
    expect(screen.getByLabelText('Close')).toBeInTheDocument(); // Modal close button
    expect(screen.getByText('Price')).toBeInTheDocument(); // Modal price section (unique to modal)
    expect(screen.getByText('Save €6.00 (38% OFF)')).toBeInTheDocument(); // Modal savings info
    expect(screen.getByText('Test Category')).toBeInTheDocument(); // Category info only in modal

    // Verify the modal is visible
    const modalOverlay = document.querySelector('.meal-detail-modal-overlay');
    expect(modalOverlay).toBeInTheDocument();
  });

  it('shows add to cart animation when button is clicked', async () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const addButton = screen.getByText('Add to Cart');
    fireEvent.click(addButton);

    // Check for success state
    await waitFor(() => {
      expect(screen.getByText('Added!')).toBeInTheDocument();
    });
  });

  it('redirects to login when not authenticated', () => {
    (auth.isAuthenticated as any).mockReturnValue(false);

    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const addButton = screen.getByText('Add to Cart');
    fireEvent.click(addButton);

    expect(mockNavigate).toHaveBeenCalledWith('/login');
  });

  it('calculates savings percentage correctly', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    // 15.99 - 9.99 = 6.00, 6.00 / 15.99 * 100 = 37.5% ≈ 38%
    expect(screen.getByText('38% OFF')).toBeInTheDocument();
  });

  it('formats pickup time correctly', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    // Should show time range based on available_from and available_until
    expect(screen.getByText(/Pickup:/)).toBeInTheDocument();
  });

  it('handles image loading error gracefully', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const image = screen.getByAltText('Test Meal');
    fireEvent.error(image);

    // Should fallback to placeholder
    expect(screen.getByText('Click to view')).toBeInTheDocument();
  });

  it('prevents event propagation on add to cart button click', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const addButton = screen.getByText('Add to Cart');

    // Mock stopPropagation
    const stopPropagation = vi.fn();
    fireEvent.click(addButton, { stopPropagation });

    // Should not open modal when clicking add to cart button
    expect(screen.queryByText('Price')).not.toBeInTheDocument(); // Modal should not be open
  });
});
