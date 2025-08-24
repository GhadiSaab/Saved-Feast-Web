import React, { createContext, useState, useContext, ReactNode } from 'react';

// Define the structure of a single item in the cart
interface CartItem {
    id: number; // Meal ID
    name: string;
    price: number;
    quantity: number;
}

// Define the shape of the context data
interface CartContextType {
    cartItems: CartItem[];
    addToCart: (meal: { id: number; name: string; price: number }) => void;
    removeFromCart: (mealId: number) => void;
    updateQuantity: (mealId: number, quantity: number) => void;
    clearCart: () => void;
    getCartTotal: () => number;
    getItemCount: () => number;
}

// Create the context with a default value (can be null or a default object)
// Using 'null' forces us to check if the context is available before using it
const CartContext = createContext<CartContextType | null>(null);

// Create a custom hook for easy access to the context
export const useCart = () => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCart must be used within a CartProvider');
    }
    return context;
};

// Define the props for the provider component
interface CartProviderProps {
    children: ReactNode; // To wrap other components
}

// Create the provider component
export const CartProvider: React.FC<CartProviderProps> = ({ children }) => {
    // State to hold the cart items
    // We can potentially load this from localStorage later for persistence
    const [cartItems, setCartItems] = useState<CartItem[]>([]);

    // Function to add a meal to the cart or increment its quantity
    const addToCart = (meal: { id: number; name: string; price: number }) => {
        setCartItems(prevItems => {
            const existingItem = prevItems.find(item => item.id === meal.id);
            if (existingItem) {
                // Increment quantity if item already exists
                return prevItems.map(item =>
                    item.id === meal.id ? { ...item, quantity: item.quantity + 1 } : item
                );
            } else {
                // Add new item with quantity 1
                return [...prevItems, { ...meal, quantity: 1 }];
            }
        });
        console.log("Cart after add:", cartItems); // For debugging
    };

    // Function to remove an item completely from the cart
    const removeFromCart = (mealId: number) => {
        setCartItems(prevItems => prevItems.filter(item => item.id !== mealId));
    };

    // Function to update the quantity of a specific item
    const updateQuantity = (mealId: number, quantity: number) => {
        setCartItems(prevItems => {
            if (quantity <= 0) {
                // Remove item if quantity is 0 or less
                return prevItems.filter(item => item.id !== mealId);
            } else {
                // Update quantity for the specific item
                return prevItems.map(item =>
                    item.id === mealId ? { ...item, quantity: quantity } : item
                );
            }
        });
    };

    // Function to clear the entire cart
    const clearCart = () => {
        setCartItems([]);
    };

    // Function to calculate the total price of items in the cart
    const getCartTotal = (): number => {
        return cartItems.reduce((total, item) => total + item.price * item.quantity, 0);
    };

    // Function to get the total number of individual items in the cart
    const getItemCount = (): number => {
        return cartItems.reduce((count, item) => count + item.quantity, 0);
    };

    // Value object provided by the context
    const contextValue: CartContextType = {
        cartItems,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getCartTotal,
        getItemCount,
    };

    // Provide the context value to children components
    return <CartContext.Provider value={contextValue}>{children}</CartContext.Provider>;
};

// Optional: Add useEffect for localStorage persistence (Example)
/*
useEffect(() => {
    const storedCart = localStorage.getItem('shoppingCart');
    if (storedCart) {
        setCartItems(JSON.parse(storedCart));
    }
}, []); // Load cart from localStorage on initial mount

useEffect(() => {
    if (cartItems.length > 0) { // Only save if cart is not empty (or based on your logic)
      localStorage.setItem('shoppingCart', JSON.stringify(cartItems));
    } else {
      localStorage.removeItem('shoppingCart'); // Clear storage if cart is empty
    }
}, [cartItems]); // Save cart to localStorage whenever it changes
*/
