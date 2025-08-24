import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import MealCard from '../../components/MealCard';

// Mock the context and auth
vi.mock('../../context/CartContext', () => ({
  useCart: () => ({
    addToCart: vi.fn()
  })
}));

vi.mock('../../auth', () => ({
  default: {
    isAuthenticated: () => true
  }
}));

// Mock meal data matching the actual interface
const mockMeal = {
  id: 1,
  title: 'Margherita Pizza',
  description: 'Classic margherita pizza with tomato and mozzarella',
  current_price: 15.99,
  original_price: 20.00,
  image: 'https://example.com/pizza.jpg',
  available_from: '2024-01-01T18:00:00Z',
  available_until: '2024-01-01T20:00:00Z',
  restaurant: {
    name: 'Pizza Palace'
  }
};

describe('MealCard Component', () => {
  it('renders meal information correctly', () => {
    render(
      <BrowserRouter>
        <MealCard meal={mockMeal} />
      </BrowserRouter>
    );
    
    expect(screen.getByText('Margherita Pizza')).toBeInTheDocument();
    expect(screen.getByText('Classic margherita pizza with tomato and mozzarella')).toBeInTheDocument();
    expect(screen.getByText('€15.99')).toBeInTheDocument();
  });

  it('displays restaurant name', () => {
    render(
      <BrowserRouter>
        <MealCard meal={mockMeal} />
      </BrowserRouter>
    );
    
    expect(screen.getByText('Pizza Palace')).toBeInTheDocument();
  });

  it('shows savings badge when there is a discount', () => {
    render(
      <BrowserRouter>
        <MealCard meal={mockMeal} />
      </BrowserRouter>
    );
    
    expect(screen.getByText('20% OFF')).toBeInTheDocument();
  });

  it('shows original price when discounted', () => {
    render(
      <BrowserRouter>
        <MealCard meal={mockMeal} />
      </BrowserRouter>
    );
    
    expect(screen.getByText('€20.00')).toBeInTheDocument();
  });

  it('displays add to cart button', () => {
    render(
      <BrowserRouter>
        <MealCard meal={mockMeal} />
      </BrowserRouter>
    );
    
    expect(screen.getByText('Add to Cart')).toBeInTheDocument();
  });
});
