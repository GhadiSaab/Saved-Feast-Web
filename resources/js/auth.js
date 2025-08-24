import axios from 'axios';

/**
 * Authentication helper functions
 */
export const auth = {
    /**
     * Get the authentication token from localStorage
     */
    getToken() {
        return localStorage.getItem('auth_token');
    },

    /**
     * Check if the user is authenticated
     */
    isAuthenticated() {
        return !!this.getToken();
    },

    /**
     * Save the authentication token
     */
    setToken(token) {
        localStorage.setItem('auth_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    },

    /**
     * Remove the authentication token
     */
    removeToken() {
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
    },

    /**
     * Get the authenticated user
     */
    async getUser() {
        if (!this.isAuthenticated()) {
            return null;
        }

        try {
            const response = await axios.get('/api/user');
            return response.data;
        } catch (error) {
            this.removeToken();
            return null;
        }
    },

    /**
     * Login the user
     */
    async login(credentials) {
        const response = await axios.post('/api/login', credentials);
        this.setToken(response.data.access_token); // Corrected key
        window.dispatchEvent(new Event('authChange')); // Dispatch event
        return response.data;
    },

    /**
     * Register a new user
     */
    async register(userData) {
        // Use relative path and correct token key
        const response = await axios.post('/api/register', userData); 
        this.setToken(response.data.access_token); // Corrected key
        window.dispatchEvent(new Event('authChange')); // Dispatch event
        return response.data;
    },

    /**
     * Logout the user
     */
    async logout() {
        try {
            await axios.post('/api/logout');
        } catch (error) {
            // Even if the server request fails, we should still clear local state
            console.error('Logout error:', error);
        } finally {
            this.removeToken();
            window.dispatchEvent(new Event('authChange')); // Dispatch event to update UI
        }
    }
};

// Initialize auth header if token exists
const token = auth.getToken();
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

export default auth;
