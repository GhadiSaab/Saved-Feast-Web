// React import not needed in modern JSX with vite plugin-react
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { vi } from 'vitest';
import axios from 'axios';
import RestaurantApplicationPage from '../../routes/RestaurantApplicationPage';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

describe('RestaurantApplicationPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders application header with correct styling', () => {
    render(<RestaurantApplicationPage />);

    expect(screen.getByText('Partner with SavedFeast')).toBeInTheDocument();
    expect(
      screen.getByText(/Join our mission to reduce food waste/)
    ).toBeInTheDocument();
    expect(screen.getByText('Back to Home')).toBeInTheDocument();
  });

  it('displays application form', () => {
    render(<RestaurantApplicationPage />);

    expect(screen.getByText('Restaurant Application')).toBeInTheDocument();
    expect(screen.getByLabelText('Restaurant Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Full Address')).toBeInTheDocument();
    expect(screen.getByLabelText('Contact Person Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Contact Email')).toBeInTheDocument();
    expect(screen.getByLabelText('Contact Phone')).toBeInTheDocument();
    expect(
      screen.getByLabelText('Cuisine Type (e.g., Italian, Lebanese, Cafe)')
    ).toBeInTheDocument();
    expect(
      screen.getByLabelText('Brief Description / Why you want to join')
    ).toBeInTheDocument();
  });

  it('allows form input', () => {
    render(<RestaurantApplicationPage />);

    const restaurantNameInput = screen.getByLabelText('Restaurant Name');
    const addressInput = screen.getByLabelText('Full Address');
    const contactNameInput = screen.getByLabelText('Contact Person Name');
    const contactEmailInput = screen.getByLabelText('Contact Email');
    const contactPhoneInput = screen.getByLabelText('Contact Phone');
    const cuisineTypeInput = screen.getByLabelText(
      'Cuisine Type (e.g., Italian, Lebanese, Cafe)'
    );
    const descriptionInput = screen.getByLabelText(
      'Brief Description / Why you want to join'
    );

    fireEvent.change(restaurantNameInput, {
      target: { value: 'Test Restaurant' },
    });
    fireEvent.change(addressInput, { target: { value: '123 Test St' } });
    fireEvent.change(contactNameInput, { target: { value: 'John Doe' } });
    fireEvent.change(contactEmailInput, { target: { value: 'john@test.com' } });
    fireEvent.change(contactPhoneInput, { target: { value: '123-456-7890' } });
    fireEvent.change(cuisineTypeInput, { target: { value: 'Italian' } });
    fireEvent.change(descriptionInput, {
      target: { value: 'A great restaurant' },
    });

    expect(restaurantNameInput).toHaveValue('Test Restaurant');
    expect(addressInput).toHaveValue('123 Test St');
    expect(contactNameInput).toHaveValue('John Doe');
    expect(contactEmailInput).toHaveValue('john@test.com');
    expect(contactPhoneInput).toHaveValue('123-456-7890');
    expect(cuisineTypeInput).toHaveValue('Italian');
    expect(descriptionInput).toHaveValue('A great restaurant');
  });

  it('submits form successfully', async () => {
    mockedAxios.post.mockResolvedValueOnce({
      data: {
        success: true,
        message: 'Application submitted successfully!',
      },
    });

    render(<RestaurantApplicationPage />);

    // Fill out the form
    fireEvent.change(screen.getByLabelText('Restaurant Name'), {
      target: { value: 'Test Restaurant' },
    });
    fireEvent.change(screen.getByLabelText('Full Address'), {
      target: { value: '123 Test St' },
    });
    fireEvent.change(screen.getByLabelText('Contact Person Name'), {
      target: { value: 'John Doe' },
    });
    fireEvent.change(screen.getByLabelText('Contact Email'), {
      target: { value: 'john@test.com' },
    });
    fireEvent.change(screen.getByLabelText('Contact Phone'), {
      target: { value: '123-456-7890' },
    });
    fireEvent.change(
      screen.getByLabelText('Cuisine Type (e.g., Italian, Lebanese, Cafe)'),
      {
        target: { value: 'Italian' },
      }
    );
    fireEvent.change(
      screen.getByLabelText('Brief Description / Why you want to join'),
      {
        target: { value: 'A great restaurant' },
      }
    );

    // Submit the form
    const submitButton = screen.getByText('Submit Application');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(mockedAxios.post).toHaveBeenCalledWith(
        '/api/restaurant-applications',
        {
          restaurant_name: 'Test Restaurant',
          address: '123 Test St',
          contact_name: 'John Doe',
          contact_email: 'john@test.com',
          contact_phone: '123-456-7890',
          cuisine_type: 'Italian',
          description: 'A great restaurant',
        }
      );
    });

    await waitFor(() => {
      expect(screen.getByText('Application Submitted!')).toBeInTheDocument();
      expect(
        screen.getByText('Application submitted successfully!')
      ).toBeInTheDocument();
    });
  });

  it('handles form submission errors', async () => {
    mockedAxios.post.mockRejectedValueOnce({
      response: {
        data: {
          message: 'Validation failed',
        },
      },
    });

    render(<RestaurantApplicationPage />);

    // Fill out the form
    fireEvent.change(screen.getByLabelText('Restaurant Name'), {
      target: { value: 'Test Restaurant' },
    });
    fireEvent.change(screen.getByLabelText('Full Address'), {
      target: { value: '123 Test St' },
    });
    fireEvent.change(screen.getByLabelText('Contact Person Name'), {
      target: { value: 'John Doe' },
    });
    fireEvent.change(screen.getByLabelText('Contact Email'), {
      target: { value: 'john@test.com' },
    });
    fireEvent.change(screen.getByLabelText('Contact Phone'), {
      target: { value: '123-456-7890' },
    });
    fireEvent.change(
      screen.getByLabelText('Cuisine Type (e.g., Italian, Lebanese, Cafe)'),
      {
        target: { value: 'Italian' },
      }
    );
    fireEvent.change(
      screen.getByLabelText('Brief Description / Why you want to join'),
      {
        target: { value: 'A great restaurant' },
      }
    );

    // Submit the form
    const submitButton = screen.getByText('Submit Application');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Error')).toBeInTheDocument();
      expect(screen.getByText('Validation failed')).toBeInTheDocument();
    });
  });

  it('shows loading state during submission', async () => {
    mockedAxios.post.mockImplementation(() => new Promise(() => {})); // Never resolves

    render(<RestaurantApplicationPage />);

    // Fill out the form
    fireEvent.change(screen.getByLabelText('Restaurant Name'), {
      target: { value: 'Test Restaurant' },
    });
    fireEvent.change(screen.getByLabelText('Full Address'), {
      target: { value: '123 Test St' },
    });
    fireEvent.change(screen.getByLabelText('Contact Person Name'), {
      target: { value: 'John Doe' },
    });
    fireEvent.change(screen.getByLabelText('Contact Email'), {
      target: { value: 'john@test.com' },
    });
    fireEvent.change(screen.getByLabelText('Contact Phone'), {
      target: { value: '123-456-7890' },
    });
    fireEvent.change(
      screen.getByLabelText('Cuisine Type (e.g., Italian, Lebanese, Cafe)'),
      {
        target: { value: 'Italian' },
      }
    );
    fireEvent.change(
      screen.getByLabelText('Brief Description / Why you want to join'),
      {
        target: { value: 'A great restaurant' },
      }
    );

    // Submit the form
    const submitButton = screen.getByText('Submit Application');
    fireEvent.click(submitButton);

    expect(screen.getByText('Submitting Application...')).toBeInTheDocument();
    expect(submitButton).toBeDisabled();
  });

  it('hides form after successful submission', async () => {
    mockedAxios.post.mockResolvedValueOnce({
      data: {
        success: true,
        message: 'Application submitted successfully!',
      },
    });

    render(<RestaurantApplicationPage />);

    // Fill out and submit the form
    fireEvent.change(screen.getByLabelText('Restaurant Name'), {
      target: { value: 'Test Restaurant' },
    });
    fireEvent.change(screen.getByLabelText('Full Address'), {
      target: { value: '123 Test St' },
    });
    fireEvent.change(screen.getByLabelText('Contact Person Name'), {
      target: { value: 'John Doe' },
    });
    fireEvent.change(screen.getByLabelText('Contact Email'), {
      target: { value: 'john@test.com' },
    });
    fireEvent.change(screen.getByLabelText('Contact Phone'), {
      target: { value: '123-456-7890' },
    });
    fireEvent.change(
      screen.getByLabelText('Cuisine Type (e.g., Italian, Lebanese, Cafe)'),
      {
        target: { value: 'Italian' },
      }
    );
    fireEvent.change(
      screen.getByLabelText('Brief Description / Why you want to join'),
      {
        target: { value: 'A great restaurant' },
      }
    );

    const submitButton = screen.getByText('Submit Application');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Application Submitted!')).toBeInTheDocument();
      expect(
        screen.queryByLabelText('Restaurant Name')
      ).not.toBeInTheDocument();
    });
  });

  it('renders with proper card styling', () => {
    render(<RestaurantApplicationPage />);

    const cards = screen
      .getAllByRole('generic')
      .filter(el => el.className.includes('card'));
    expect(cards.length).toBeGreaterThan(0);
  });
});
