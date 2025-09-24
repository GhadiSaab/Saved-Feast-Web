import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
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

const normalizeCurrency = (value: string) => value.replace(/[\s\u00A0\u202F]/g, '');
const NAIRA_CODEPOINT = 8358;

const mockMeal = {
  id: 1,
  title: 'Test Meal',
  description: 'A delicious test meal',
  current_price: 9.99,
  original_price: 15.99,
  image: '/storage/meals/test-image.jpg',
  image_url: '/storage/meals/test-image.jpg',
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
  image_url: null,
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
    expect(screen.getAllByText('Test Restaurant')).toHaveLength(2);

    const card = screen.getByText('Test Meal').closest('.card') as HTMLElement;
    const currentPrice = card.querySelector('.current-price');
    expect(currentPrice).not.toBeNull();
    const normalized = normalizeCurrency(currentPrice?.textContent ?? '');
    expect(normalized.endsWith('9.99')).toBe(true);
    expect(normalized.codePointAt(0)).toBe(NAIRA_CODEPOINT);
  });

  it('displays discounted price correctly without overlap', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const card = screen.getByText('Test Meal').closest('.card') as HTMLElement;
    const currentPrice = card.querySelector('.current-price');
    const originalPrice = card.querySelector('.original-price');
    const savings = card.querySelector('.price-display small.text-success.fw-bold');

    expect(currentPrice).not.toBeNull();
    expect(originalPrice).not.toBeNull();
    expect(savings).not.toBeNull();

    const normalizedCurrent = normalizeCurrency(currentPrice?.textContent ?? '');
    const normalizedOriginal = normalizeCurrency(originalPrice?.textContent ?? '');

    expect(normalizedCurrent.endsWith('9.99')).toBe(true);
    expect(normalizedOriginal.endsWith('15.99')).toBe(true);
    expect(normalizedCurrent.codePointAt(0)).toBe(NAIRA_CODEPOINT);
    expect(normalizedOriginal.codePointAt(0)).toBe(NAIRA_CODEPOINT);
    expect(savings?.textContent || '').toContain('6.00');

    const priceContainer = currentPrice?.closest('.d-flex.flex-column');
    expect(priceContainer).toBeInTheDocument();
  });

  it('displays regular price when no discount', () => {
    renderWithCartProvider(<MealCard meal={mockMealWithoutDiscount} />);

    const card = screen.getByText('Test Meal').closest('.card') as HTMLElement;
    const currentPrice = card.querySelector('.current-price');
    const originalPrice = card.querySelector('.original-price');

    expect(currentPrice).not.toBeNull();
    expect(originalPrice).toBeNull();

    const normalizedCurrent = normalizeCurrency(currentPrice?.textContent ?? '');
    expect(normalizedCurrent.endsWith('9.99')).toBe(true);
    expect(normalizedCurrent.codePointAt(0)).toBe(NAIRA_CODEPOINT);
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

    expect(screen.getByLabelText('Close')).toBeInTheDocument();
    expect(screen.getByText('Price')).toBeInTheDocument();

    const modalContent = screen.getByLabelText('Close').closest('.meal-detail-modal-content');
    const modalQueries = within(modalContent as HTMLElement);

    const modalCurrentPrice = modalQueries.getByText((text, element) => {
      const normalized = normalizeCurrency(element?.textContent ?? '');
      return normalized.endsWith('9.99') && normalized.codePointAt(0) === NAIRA_CODEPOINT;
    });
    const modalOriginalPrice = modalQueries.getByText((text, element) => {
      const normalized = normalizeCurrency(element?.textContent ?? '');
      return normalized.endsWith('15.99') && normalized.codePointAt(0) === NAIRA_CODEPOINT;
    });
    const modalSavings = modalQueries.getByText(/Save/);

    expect(modalCurrentPrice).toBeInTheDocument();
    expect(modalOriginalPrice).toBeInTheDocument();
    expect(modalSavings.textContent || '').toContain('6.00');
    expect(modalSavings.textContent).toContain('38% OFF');
    expect(modalQueries.getByText('Test Category')).toBeInTheDocument();

    const modalOverlay = document.querySelector('.meal-detail-modal-overlay');
    expect(modalOverlay).toBeInTheDocument();
  });

  it('shows add to cart animation when button is clicked', async () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const addButton = screen.getByText('Add to Cart');
    fireEvent.click(addButton);

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

    expect(screen.getByText('38% OFF')).toBeInTheDocument();
  });

  it('formats pickup time correctly', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    expect(screen.getByText(/Pickup:/)).toBeInTheDocument();
  });

  it('handles image loading error gracefully', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const image = screen.getByAltText('Test Meal');
    fireEvent.error(image);

    expect(screen.getByText('Click to view')).toBeInTheDocument();
  });

  it('prevents event propagation on add to cart button click', () => {
    renderWithCartProvider(<MealCard meal={mockMeal} />);

    const addButton = screen.getByText('Add to Cart');
    const stopPropagation = vi.fn();
    fireEvent.click(addButton, { stopPropagation });

    expect(screen.queryByText('Price')).not.toBeInTheDocument();
  });
});



