import React, { createContext, useState, useEffect } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { CartItem, Product } from "./types";

// Cart Context type definition
interface CartContextType {
  cart: CartItem[];
  addToCart: (product: Product, quantity: number) => void;
  updateQuantity: (productId: number, quantity: number) => void;
  removeFromCart: (productId: number) => void;
  clearCart: () => void;
  totalItems: number;
  totalPrice: number;
}

// Default context value
const defaultContextValue: CartContextType = {
  cart: [],
  addToCart: () => {},
  updateQuantity: () => {},
  removeFromCart: () => {},
  clearCart: () => {},
  totalItems: 0,
  totalPrice: 0,
};

// Create the context
export const CartContext = createContext<CartContextType>(defaultContextValue);

// Storage key for the cart
const CART_STORAGE_KEY = "@farmers_market_cart";

// CartProvider component to manage cart state
export const CartProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [totalItems, setTotalItems] = useState(0);
  const [totalPrice, setTotalPrice] = useState(0);

  // Load cart from AsyncStorage on component mount
  useEffect(() => {
    const loadCart = async () => {
      try {
        console.log("[CartContext] Loading cart from storage");
        const savedCart = await AsyncStorage.getItem(CART_STORAGE_KEY);

        if (savedCart) {
          console.log(
            "[CartContext] Found saved cart:",
            savedCart.substring(0, 50) + "..."
          );
          const parsedCart = JSON.parse(savedCart);
          setCart(parsedCart);
          calculateTotals(parsedCart);
          console.log(
            "[CartContext] Cart loaded with",
            parsedCart.length,
            "items"
          );
        } else {
          console.log("[CartContext] No saved cart found");
        }
      } catch (error) {
        console.error("[CartContext] Error loading cart from storage:", error);
      }
    };

    loadCart();
  }, []);

  // Calculate cart totals
  const calculateTotals = (cartItems: CartItem[]) => {
    const items = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const price = cartItems.reduce(
      (sum, item) => sum + item.product.price * item.quantity,
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
  };

  // Save cart to AsyncStorage whenever it changes
  useEffect(() => {
    const saveCart = async () => {
      try {
        console.log("[CartContext] Saving cart with", cart.length, "items");
        await AsyncStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
        console.log("[CartContext] Cart saved successfully");
      } catch (error) {
        console.error("[CartContext] Error saving cart to storage:", error);
      }
    };

    // Only save if cart has changed
    if (cart.length > 0 || totalItems > 0) {
      saveCart();
    }
    calculateTotals(cart);
  }, [cart]);

  // Add a product to the cart
  const addToCart = (product: Product, quantity: number) => {
    console.log(
      "[CartContext] Adding to cart:",
      product.name,
      "Quantity:",
      quantity
    );

    setCart((prevCart) => {
      // Check if the product already exists in the cart
      const existingItem = prevCart.find(
        (item) => item.product.id === product.id
      );

      let newCart;
      if (existingItem) {
        // Update quantity if product already exists
        console.log("[CartContext] Product already in cart, updating quantity");
        newCart = prevCart.map((item) =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item
        );
      } else {
        // Add new product to cart
        console.log("[CartContext] Adding new product to cart");
        newCart = [...prevCart, { product, quantity }];
      }

      return newCart;
    });
  };

  // Update quantity of a product in the cart
  const updateQuantity = (productId: number, quantity: number) => {
    console.log(
      "[CartContext] Updating quantity for product ID:",
      productId,
      "New quantity:",
      quantity
    );

    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }

    setCart((prevCart) =>
      prevCart.map((item) =>
        item.product.id === productId ? { ...item, quantity } : item
      )
    );
  };

  // Remove a product from the cart
  const removeFromCart = (productId: number) => {
    console.log("[CartContext] Removing product ID from cart:", productId);

    setCart((prevCart) =>
      prevCart.filter((item) => item.product.id !== productId)
    );
  };

  // Clear the entire cart
  const clearCart = () => {
    console.log("[CartContext] Clearing cart");

    setCart([]);
    // Ensure we also clear AsyncStorage
    AsyncStorage.removeItem(CART_STORAGE_KEY)
      .then(() => {
        console.log("[CartContext] Cart storage cleared");
      })
      .catch((error) => {
        console.error("[CartContext] Error clearing cart storage:", error);
      });
  };

  return (
    <CartContext.Provider
      value={{
        cart,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        totalItems,
        totalPrice,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};
