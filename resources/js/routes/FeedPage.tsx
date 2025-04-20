// resources/js/routes/FeedPage.tsx
import React, { useState, useEffect } from 'react';
import axios from 'axios'; // Axios is already a dependency
import MealCard from '../components/MealCard'; // Import the actual component

// Define a type for the Meal data (adjust based on actual API response)
interface Meal {
    id: number;
    name: string;
    description: string;
    price: number;
    image_url?: string; // Reverted back to image_url
    restaurant?: { // Assuming restaurant is nested
        name: string;
    };
    // Add other relevant fields
}

// Placeholder MealCard component - removed


const FeedPage: React.FC = () => {
    const [meals, setMeals] = useState<Meal[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchMeals = async () => {
            setLoading(true);
            setError(null);
            try {
                // Fetch from the existing API endpoint
                const response = await axios.get<{ data: Meal[] }>('/api/meals');
                // Adjust based on how Laravel pagination/resource wraps the data
                setMeals(response.data.data || response.data);
            } catch (err: any) {
                setError('Failed to fetch meals. Please try again later.');
                console.error("Error fetching meals:", err);
            } finally {
                setLoading(false);
            }
        };

        fetchMeals();
    }, []); // Empty dependency array means this runs once on mount

    return (
        <div className="feed-page-container bg-white p-4 p-md-5 rounded shadow-sm"> {/* Wrapper div */}
            <h1 className="mb-5 page-title">Today's Feasts</h1> {/* Added page-title class */}
            {loading && (
                <div className="d-flex justify-content-center">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            )}
            {error && <div className="alert alert-danger">{error}</div>}
            {!loading && !error && (
                <div className="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4"> {/* Added xl breakpoint */}
                    {meals.length > 0 ? (
                        meals.map(meal => (
                            <div className="col" key={meal.id}>
                                <MealCard meal={meal} /> {/* Use actual component */}
                            </div>
                        ))
                    ) : (
                         <div className="col-12">
                            <p className="text-center text-muted">No meals available right now. Check back soon!</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default FeedPage;
