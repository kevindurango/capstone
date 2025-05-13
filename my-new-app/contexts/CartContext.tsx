import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  ReactNode,
} from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { Text } from "react-native";
import { COLORS } from "@/constants/Colors";

// Define cart item interface
export interface CartItem {
  id: number;
  product_id: number;
  name: string;
  price: number;
  quantity: number;
  image?: string;
  unit_type?: string;
  seller_id?: number;
  seller_name?: string;
}

// Define cart context interface
interface CartContextType {
  cart: CartItem[];
  addToCart: (item: CartItem) => void;
  removeFromCart: (productId: number) => void;
  updateQuantity: (productId: number, quantity: number) => void;
  clearCart: () => void;
  totalItems: number;
  totalPrice: number;
  getItemQuantity: (productId: number) => number;
}

// Storage key for cart
const CART_STORAGE_KEY = "@farmers_market_cart";

// Create the context with default values
export const CartContext = createContext<CartContextType>({
  cart: [],
  addToCart: () => {},
  removeFromCart: () => {},
  updateQuantity: () => {},
  clearCart: () => {},
  totalItems: 0,
  totalPrice: 0,
  getItemQuantity: () => 0,
});

// Create a hook for easy access to the cart context
export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error("useCart must be used within a CartProvider");
  }
  return context;
};

// Cart Provider component
export const CartProvider: React.FC<{ children: ReactNode }> = ({
  children,
}) => {
  // State for cart items
  const [cart, setCart] = useState<CartItem[]>([]);
  const [totalItems, setTotalItems] = useState(0);
  const [totalPrice, setTotalPrice] = useState(0);
  const [isLoading, setIsLoading] = useState(true);

  // Load cart from storage on initial render
  useEffect(() => {
    const loadCart = async () => {
      try {
        console.log("[CartContext] Loading cart from storage");
        const storedCart = await AsyncStorage.getItem(CART_STORAGE_KEY);

        if (storedCart) {
          const parsedCart = JSON.parse(storedCart);
          setCart(parsedCart);
          console.log(
            "[CartContext] Cart loaded from storage:",
            parsedCart.length,
            "items"
          );
        } else {
          console.log("[CartContext] No saved cart found");
        }
      } catch (error) {
        console.error("[CartContext] Failed to load cart:", error);
      } finally {
        setIsLoading(false);
      }
    };

    loadCart();
  }, []);

  // Save cart to storage whenever it changes
  useEffect(() => {
    const saveCart = async () => {
      try {
        await AsyncStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
      } catch (error) {
        console.error("[CartContext] Failed to save cart:", error);
      }
    };

    // Don't save if we're still loading the initial state
    if (!isLoading) {
      saveCart();
    }
  }, [cart, isLoading]);

  // Calculate totals whenever the cart changes
  useEffect(() => {
    const items = cart.reduce((sum, item) => sum + item.quantity, 0);
    const price = cart.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );

    setTotalItems(items);
    setTotalPrice(price);
    console.log(
      "[CartContext] Totals calculated - Items:",
      items,
      "Price:",
      price
    );
  }, [cart]);

  // Add an item to the cart
  const addToCart = useCallback((newItem: CartItem) => {
    setCart((currentCart) => {
      // Check if product already exists in cart
      const existingItemIndex = currentCart.findIndex(
        (item) => item.product_id === newItem.product_id
      );

      if (existingItemIndex > -1) {
        // Update quantity of existing item
        const updatedCart = [...currentCart];
        updatedCart[existingItemIndex].quantity += newItem.quantity;
        console.log("[CartContext] Updated quantity for item:", newItem.name);
        return updatedCart;
      } else {
        // Add new item
        console.log("[CartContext] Added new item to cart:", newItem.name);
        return [...currentCart, newItem];
      }
    });
  }, []);

  // Remove an item from the cart
  const removeFromCart = useCallback((productId: number) => {
    setCart((currentCart) => {
      const updatedCart = currentCart.filter(
        (item) => item.product_id !== productId
      );
      console.log(
        "[CartContext] Removed item from cart, product ID:",
        productId
      );
      return updatedCart;
    });
  }, []);

  // Update quantity of an item
  const updateQuantity = useCallback((productId: number, quantity: number) => {
    setCart((currentCart) => {
      // If quantity is 0 or less, remove the item
      if (quantity <= 0) {
        return currentCart.filter((item) => item.product_id !== productId);
      }

      // Otherwise update the quantity
      const updatedCart = currentCart.map((item) =>
        item.product_id === productId ? { ...item, quantity } : item
      );
      console.log(
        "[CartContext] Updated quantity for product ID:",
        productId,
        "to",
        quantity
      );
      return updatedCart;
    });
  }, []);

  // Clear the entire cart
  const clearCart = useCallback(() => {
    console.log("[CartContext] Clearing cart");
    setCart([]);
  }, []);

  // Get quantity of a specific item
  const getItemQuantity = useCallback(
    (productId: number) => {
      const item = cart.find((item) => item.product_id === productId);
      return item ? item.quantity : 0;
    },
    [cart]
  );

  // Context value
  const contextValue: CartContextType = {
    cart,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    totalItems,
    totalPrice,
    getItemQuantity,
  };

  // If still loading, show nothing or a placeholder
  if (isLoading) {
    return <Text style={{ display: "none" }}>Loading cart...</Text>;
  }

  return (
    <CartContext.Provider value={contextValue}>{children}</CartContext.Provider>
  );
};
