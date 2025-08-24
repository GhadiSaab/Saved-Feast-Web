import React, { useState, useEffect, useRef, ChangeEvent } from 'react';
import axios from 'axios';
import auth from '../auth';
import { Bar, Pie } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

// Define the structure of the provider profile data expected from the API
interface ProviderProfileData {
  id: number;
  user_id: number;
  name: string;
  description: string | null;
  address: string;
  phone: string | null;
  email: string;
  website: string | null;
  image: string | null; // General restaurant image
  profile_picture_path: string | null;
  profile_picture_url: string | null; // URL for the profile picture
  created_at: string;
  updated_at: string;
  stats: {
    meals_sold: number;
    food_saved_quantity: number;
    total_revenue: string; // Comes as formatted string
  };
}

const ProviderProfile: React.FC = () => {
  const [profileData, setProfileData] = useState<ProviderProfileData | null>(
    null
  );
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [uploading, setUploading] = useState<boolean>(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const fetchProviderProfile = async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await axios.get<ProviderProfileData>(
          '/api/provider/profile',
          {
            headers: { Authorization: `Bearer ${auth.getToken()}` },
          }
        );
        setProfileData(response.data);
      } catch (err: any) {
        setError('Failed to fetch provider profile data. Please try again.');
        console.error('Error fetching provider profile:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchProviderProfile();
  }, []);

  const handleFileChange = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setUploading(true);
    setUploadError(null);
    const formData = new FormData();
    formData.append('profile_picture', file);

    try {
      const response = await axios.post<{
        message: string;
        profile_picture_url: string;
      }>('/api/provider/profile/picture', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${auth.getToken()}`,
        },
      });

      // Update profile data with the new picture URL
      if (profileData) {
        setProfileData({
          ...profileData,
          profile_picture_url: response.data.profile_picture_url,
        });
      }
      // Optionally clear the file input
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (err: any) {
      if (err.response && err.response.data && err.response.data.errors) {
        // Handle validation errors (e.g., file too large, wrong type)
        const messages = Object.values(err.response.data.errors)
          .flat()
          .join(' ');
        setUploadError(`Upload failed: ${messages}`);
      } else {
        setUploadError('Failed to upload profile picture. Please try again.');
      }
      console.error('Error uploading profile picture:', err);
    } finally {
      setUploading(false);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center mt-5">
        <div className="spinner-border" role="status">
          <span className="visually-hidden">Loading...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return <div className="alert alert-danger">{error}</div>;
  }

  if (!profileData) {
    return (
      <div className="alert alert-warning">
        Could not load provider profile data.
      </div>
    );
  }

  // Chart Data Preparation
  const stats = profileData.stats;
  const revenue = parseFloat(stats.total_revenue); // Convert string back to number for chart

  const barChartData = {
    labels: ['Meals Sold', 'Food Items Saved'],
    datasets: [
      {
        label: 'Quantity',
        data: [stats.meals_sold, stats.food_saved_quantity],
        backgroundColor: [
          'rgba(54, 162, 235, 0.6)', // Blue
          'rgba(75, 192, 192, 0.6)', // Green
        ],
        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(75, 192, 192, 1)'],
        borderWidth: 1,
      },
    ],
  };

  const pieChartData = {
    labels: ['Total Revenue (€)'], // Assuming Euro
    datasets: [
      {
        label: 'Revenue',
        data: [revenue > 0 ? revenue : 0.01], // Ensure pie chart renders even with 0 revenue
        backgroundColor: ['rgba(255, 159, 64, 0.6)'], // Orange
        borderColor: ['rgba(255, 159, 64, 1)'],
        borderWidth: 1,
      },
    ],
  };

  const chartOptions = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top' as const,
      },
      title: {
        display: true,
        text: 'Provider Statistics',
      },
    },
    scales: {
      // Needed for Bar chart, optional for Pie
      y: {
        beginAtZero: true,
      },
    },
  };

  return (
    <div className="container mt-4">
      <h1>{profileData.name} - Provider Profile</h1>
      <div className="row">
        {/* Profile Info Column */}
        <div className="col-md-4">
          <div className="card mb-3">
            <img
              src={profileData.profile_picture_url || '/placeholder-image.png'} // Use placeholder if no image
              className="card-img-top"
              alt={`${profileData.name} Profile`}
              style={{ maxHeight: '300px', objectFit: 'cover' }}
            />
            <div className="card-body text-center">
              <input
                type="file"
                accept="image/*"
                onChange={handleFileChange}
                style={{ display: 'none' }} // Hide default input
                ref={fileInputRef}
                disabled={uploading}
              />
              <button
                className="btn btn-secondary btn-sm"
                onClick={() => fileInputRef.current?.click()}
                disabled={uploading}
              >
                {uploading ? 'Uploading...' : 'Change Profile Picture'}
              </button>
              {uploadError && (
                <p className="text-danger mt-2 small">{uploadError}</p>
              )}
            </div>
          </div>
          <div className="card">
            <div className="card-body">
              <h5 className="card-title">Restaurant Details</h5>
              <p>
                <strong>Email:</strong> {profileData.email}
              </p>
              <p>
                <strong>Phone:</strong> {profileData.phone || 'N/A'}
              </p>
              <p>
                <strong>Address:</strong> {profileData.address}
              </p>
              <p>
                <strong>Website:</strong>{' '}
                {profileData.website ? (
                  <a
                    href={profileData.website}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {profileData.website}
                  </a>
                ) : (
                  'N/A'
                )}
              </p>
              <p>
                <strong>Description:</strong>{' '}
                {profileData.description || 'No description provided.'}
              </p>
            </div>
          </div>
        </div>

        {/* Statistics and Charts Column */}
        <div className="col-md-8">
          <div className="card mb-3">
            <div className="card-header">Statistics Overview</div>
            <div className="card-body">
              <div className="row text-center">
                <div className="col-4">
                  <h5>{stats.meals_sold}</h5>
                  <p className="text-muted">Meals Sold</p>
                </div>
                <div className="col-4">
                  <h5>{stats.food_saved_quantity}</h5>
                  <p className="text-muted">Food Items Saved</p>
                </div>
                <div className="col-4">
                  <h5>€{stats.total_revenue}</h5>
                  <p className="text-muted">Total Revenue</p>
                </div>
              </div>
            </div>
          </div>

          <div className="card">
            <div className="card-header">Visualizations</div>
            <div className="card-body">
              <div className="row">
                <div className="col-md-8">
                  <h6>Sales & Savings</h6>
                  <Bar options={chartOptions} data={barChartData} />
                </div>
                <div className="col-md-4 d-flex flex-column align-items-center">
                  <h6>Revenue</h6>
                  {/* Adjust size for Pie chart */}
                  <div style={{ maxWidth: '200px', maxHeight: '200px' }}>
                    <Pie
                      options={{
                        ...chartOptions,
                        plugins: {
                          ...chartOptions.plugins,
                          title: { display: false },
                        },
                      }}
                      data={pieChartData}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProviderProfile;
