import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import auth from '../auth';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    address?: string;
    roles?: Array<{
        id: number;
        name: string;
        description: string;
    }>;
}

interface AuthContextType {
    user: User | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (credentials: { email: string; password: string }) => Promise<void>;
    register: (userData: any) => Promise<void>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    const isAuthenticated = !!user;

    const refreshUser = async () => {
        try {
            const userData = await auth.getUser();
            setUser(userData);
        } catch (error) {
            setUser(null);
        }
    };

    const login = async (credentials: { email: string; password: string }) => {
        setIsLoading(true);
        try {
            const response = await auth.login(credentials);
            setUser(response.user);
        } finally {
            setIsLoading(false);
        }
    };

    const register = async (userData: any) => {
        setIsLoading(true);
        try {
            const response = await auth.register(userData);
            setUser(response.user);
        } finally {
            setIsLoading(false);
        }
    };

    const logout = async () => {
        setIsLoading(true);
        try {
            await auth.logout();
            setUser(null);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        const initializeAuth = async () => {
            if (auth.isAuthenticated()) {
                await refreshUser();
            }
            setIsLoading(false);
        };

        initializeAuth();

        // Listen for auth changes
        const handleAuthChange = () => {
            if (auth.isAuthenticated()) {
                refreshUser();
            } else {
                setUser(null);
            }
        };

        window.addEventListener('authChange', handleAuthChange);
        window.addEventListener('storage', handleAuthChange);

        return () => {
            window.removeEventListener('authChange', handleAuthChange);
            window.removeEventListener('storage', handleAuthChange);
        };
    }, []);

    const value: AuthContextType = {
        user,
        isAuthenticated,
        isLoading,
        login,
        register,
        logout,
        refreshUser,
    };

    return (
        <AuthContext.Provider value={value}>
            {children}
        </AuthContext.Provider>
    );
}; 