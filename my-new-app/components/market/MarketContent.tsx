import React, { useState, useCallback, useContext } from "react";
import {
  View,
  FlatList,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Text,
  Animated,
  StatusBar,
  Platform,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { router } from "expo-router";

import { CartContext } from "./CartContext";
import { marketService } from "./MarketService";
import ProductItem from "./ProductItem";
import CartScreen from "./CartScreen";
import PaymentScreen from "./PaymentScreen";
import Orders from "./Orders";
import SchedulePickupScreen from "./SchedulePickupScreen";
import { CATEGORIES, Product } from "./types";
import { marketStyles } from "./styles";
import { getImageUrl } from "@/constants/Config"; // Add this import

const MarketContent: React.FC = () => {
  const insets = useSafeAreaInsets();
  // State for cart modal
  const [cartVisible, setCartVisible] = useState(false);
  const [isCheckingOut, setIsCheckingOut] = useState(false);
  const [checkoutSuccessful, setCheckoutSuccessful] = useState(false);

  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("");
  const [offset, setOffset] = useState(0);
  const [hasMore, setHasMore] = useState(true);
  const [totalCount, setTotalCount] = useState(0);
  const limit = 10;

  // Get the authenticated user from AuthContext
  const { user } = useAuth();

  // Debug user authentication status
  React.useEffect(() => {
    console.log("[MarketContent] Auth user:", user);
  }, [user]);

  // Use context for cart management
  const { cart, addToCart, clearCart, totalItems, totalPrice } =
    useContext(CartContext);

  // State for payment modal
  const [paymentVisible, setPaymentVisible] = useState(false);
  const [orderId, setOrderId] = useState<number | null>(null);
  const [orderAmount, setOrderAmount] = useState<number>(0); // Store order amount separately

  // State for pickup modal
  const [pickupVisible, setPickupVisible] = useState(false);
  // New state for schedule pickup modal
  const [schedulePickupVisible, setSchedulePickupVisible] = useState(false);

  const [showOrdersModal, setShowOrdersModal] = useState(false);
  const [submitLoading, setSubmitLoading] = useState(false);
  const [currentOrderId, setCurrentOrderId] = useState<number | null>(null);
  const [currentOrderTotal, setCurrentOrderTotal] = useState(0);
  const [searchActive, setSearchActive] = useState(false);

  // Navigate to order feedback screen
  const navigateToOrderFeedback = () => {
    setShowOrdersModal(true);
  };

  // Function to handle adding products to cart
  const handleAddToCart = (product: Product) => {
    if (product.quantity_available <= 0) {
      Alert.alert(
        "Out of Stock",
        "Sorry, this item is currently out of stock."
      );
      return;
    }

    console.log(`[MarketContent] Adding ${product.name} to cart`);

    // Add the item to cart with quantity 1
    addToCart(product, 1);
  };

  // Function to handle checkout process
  const handleCheckout = async () => {
    try {
      // Check if user is logged in
      if (!user) {
        console.log("[Checkout] No user detected, login required");
        Alert.alert(
          "Login Required",
          "Please login to your account to place an order.",
          [{ text: "OK" }]
        );
        return;
      }

      console.log(
        "[Checkout] Proceeding with checkout for user:",
        user.user_id
      );
      setIsCheckingOut(true);

      // Prepare order data
      const orderItems = cart.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
      }));

      // Get pickup location from user
      const pickupLocation = "Municipal Agriculture Office";

      // Use the logged-in user's ID for the order
      const result = await marketService.createOrder({
        items: orderItems,
        pickup_details: pickupLocation,
        user_id: user.user_id, // Use the authenticated user's ID
      });

      console.log("[Checkout] Creating order for user:", user.user_id);

      if (result.status === "success") {
        setCheckoutSuccessful(true);

        // Store order ID for the payment screen
        const newOrderId = result.data?.order_id;
        setOrderId(newOrderId);

        console.log(
          "[Checkout] Order created successfully with ID:",
          newOrderId
        );

        // Store the current cart total before clearing it
        const cartTotalPrice = totalPrice;
        setOrderAmount(cartTotalPrice);

        // Clear cart but don't show the success message yet
        clearCart();

        console.log("[Checkout] Cart cleared, now closing cart modal");

        // Close cart modal first
        setCartVisible(false);

        // Show payment screen after a short delay to ensure the cart modal is closed
        setTimeout(() => {
          console.log("[Checkout] Showing payment screen");
          setPaymentVisible(true);
        }, 300);
      } else {
        throw new Error(result.message || "Failed to place order");
      }
    } catch (error: any) {
      console.error("[Checkout] Error:", error);
      Alert.alert(
        "Checkout Failed",
        error.message ||
          "Something went wrong with your order. Please try again."
      );
    } finally {
      setIsCheckingOut(false);
    }
  };

  // Function to handle payment completion
  const handlePaymentComplete = (paymentMethod: string, paymentData: any) => {
    console.log(
      "[MarketContent] Payment completed with method:",
      paymentMethod
    );
    console.log("[MarketContent] Payment data:", paymentData);

    // Clear cart first before any UI changes
    clearCart();

    // Set payment visible to false - add slight delay to allow animations to complete
    setTimeout(() => {
      setPaymentVisible(false);

      // Check if pickup was already scheduled in the PaymentScreen
      if (paymentData && !paymentData.pickup_scheduled) {
        // Show the schedule pickup modal with the payment data after a delay
        setTimeout(() => {
          setSchedulePickupVisible(true);
        }, 300);
      } else {
        // Pickup was already scheduled in PaymentScreen, just show success message
        Alert.alert(
          "Order Complete",
          "Thank you for your order! You can view your pickup details in the pickups section.",
          [
            {
              text: "View Pickups",
              onPress: () => {
                setTimeout(() => setShowOrdersModal(true), 300);
              },
            },
            {
              text: "Continue Shopping",
              style: "cancel",
            },
          ]
        );
      }
    }, 100);
  };

  // Handle payment cancellation
  const handlePaymentCancel = () => {
    console.log("[MarketContent] Payment canceled");
    setPaymentVisible(false);
    // Don't clear the cart since payment was canceled
  };

  // Function to handle closing schedule pickup modal
  const handleSchedulePickupClose = () => {
    setSchedulePickupVisible(false);

    // Show success message after closing schedule pickup
    Alert.alert(
      "Order Complete",
      "Thank you for your order! You can view your pickup details in the pickups section.",
      [
        {
          text: "View Pickups",
          onPress: () => {
            setTimeout(() => setShowOrdersModal(true), 300);
          },
        },
        {
          text: "Continue Shopping",
          style: "cancel",
        },
      ]
    );
  };
  // Add the checkImageUrl helper function - updated to match products.tsx implementation
  const checkImageUrl = async (url: string | null, productId: number) => {
    try {
      // If URL is null or empty, return early
      if (!url) {
        console.log(`[Market] No image URL for product ${productId}`);
        return { exists: false, isImage: false };
      }

      console.log(
        `[Market] Checking image URL for product ${productId}: ${url}`
      );

      // Add a strong cache busting parameter
      const cacheBuster = `${new Date().getTime()}`;
      const urlWithCacheBuster = `${url}${url.includes("?") ? "&" : "?"}t=${cacheBuster}`;

      const response = await fetch(urlWithCacheBuster, {
        method: "HEAD",
        headers: {
          // Use stronger cache control headers like in products.tsx
          "Cache-Control": "no-cache, no-store, must-revalidate",
          Pragma: "no-cache",
          Expires: "0",
        },
      });

      const status = response.status;
      const contentType = response.headers.get("content-type") || "";

      console.log(`[Market] Image check result for product ${productId}:`, {
        status,
        contentType,
        isImage: contentType.startsWith("image/"),
        url: urlWithCacheBuster,
      });

      return {
        exists: status >= 200 && status < 300,
        isImage: contentType.startsWith("image/"),
      };
    } catch (error) {
      console.error(
        `[Market] Error checking image URL for product ${productId}:`,
        error
      );
      return { exists: false, isImage: false };
    }
  };

  // Process products to ensure consistent image paths
  const processProductImages = (product: Product) => {
    if (!product.image) return product;

    try {
      // Clean the image path by removing any duplicate slashes and trim
      let cleanImagePath = product.image.replace(/\/+/g, "/").trim();

      // Remove server URL prefixes if present
      const serverPrefixes = [
        "http://192.168.1.12/capstone/public/",
        "http://192.168.1.12/capstone/",
        "http://192.168.1.12/",
      ];
      for (const prefix of serverPrefixes) {
        if (cleanImagePath.startsWith(prefix)) {
          cleanImagePath = cleanImagePath.replace(prefix, "");
          break;
        }
      }

      // Remove 'public/' prefix if present
      if (cleanImagePath.startsWith("public/")) {
        cleanImagePath = cleanImagePath.replace("public/", "");
      }

      // Ensure proper path structure
      if (!cleanImagePath.includes("uploads/products/")) {
        const filename = cleanImagePath.split("/").pop();
        if (filename) {
          cleanImagePath = `uploads/products/${filename}`;
        } else {
          cleanImagePath = "uploads/products/product-default.png";
        }
      } else {
        // Extract everything after uploads/products/ to ensure consistent path
        const parts = cleanImagePath.split("uploads/products/");
        cleanImagePath = `uploads/products/${parts[parts.length - 1]}`;
      }

      console.log(
        `[Market] Normalized image path for product ${product.id}: ${cleanImagePath}`
      );
      return { ...product, image: cleanImagePath };
    } catch (error) {
      console.error(
        `[Market] Error processing image path for product ${product.id}:`,
        error
      );
      return { ...product, image: "uploads/products/product-default.png" };
    }
  };

  // Function to fetch products
  const fetchProducts = useCallback(
    async (reset = false) => {
      try {
        setLoading(true);
        const newOffset = reset ? 0 : offset;

        // Add cache busting parameter to prevent API response caching
        const cacheBuster = `${new Date().getTime()}`;
        const searchWithCache = searchQuery.trim()
          ? `${searchQuery.trim()}_${cacheBuster}`
          : undefined;

        const response = await marketService.getProducts({
          category: selectedCategory,
          search: searchWithCache,
          limit,
          offset: newOffset,
        });

        if (response && response.status === "success") {
          let fetchedProducts = response.data.products || [];

          console.log(
            "[Market] Fetched products:",
            fetchedProducts.length,
            "Total count:",
            response.data.total
          );

          // Check if we have any products
          if (fetchedProducts.length === 0 && reset) {
            console.log("[Market] No products found");
            setProducts([]);
            setHasMore(false);
          } else {
            // Process each product's image path
            const processedProducts = fetchedProducts.map(processProductImages);

            try {
              // Check each product's image URL in parallel
              const imageChecks = await Promise.all(
                processedProducts
                  .filter((p: Product) => p.image)
                  .map(async (product: Product) => {
                    const imageUrl = getImageUrl(product.image!);
                    const result = await checkImageUrl(imageUrl, product.id);
                    return {
                      productId: product.id,
                      url: imageUrl,
                      ...result,
                    };
                  })
              );

              console.log(
                "[Market] Image validation results:",
                imageChecks.map(
                  (r) => `Product ${r.productId}: ${r.exists ? "OK" : "FAIL"}`
                )
              );
            } catch (imageError) {
              console.error("[Market] Error validating images:", imageError);
            }

            // Update products with processed ones
            if (reset) {
              setProducts(processedProducts);
            } else {
              setProducts((prev) => [...prev, ...processedProducts]);
            }

            // Update pagination
            const hasMore = fetchedProducts.length === limit;
            setHasMore(hasMore);
            if (reset) {
              setOffset(limit);
            } else {
              setOffset(newOffset + limit);
            }
          }

          // Store total count
          const total = response.data.total || fetchedProducts.length;
          setTotalCount(total);
        } else {
          console.warn(
            "[Market] API returned non-success status:",
            response?.status
          );
          Alert.alert("Error", "Failed to load products from server");
        }
      } catch (error: any) {
        console.error("[Market] Fetch error:", error);
        if (error.message === "Network request failed") {
          try {
            const result = await marketService.resetApiConfiguration();
            if (result.success) {
              Alert.alert(
                "Connection Updated",
                "The server address has been updated. Trying again...",
                [{ text: "OK", onPress: () => fetchProducts(true) }]
              );
              return;
            }
          } catch (resetError) {
            console.error("[Market] Error resetting API URL:", resetError);
          }
        }
        Alert.alert(
          "Error Loading Products",
          error.message || "Something went wrong. Please try again later."
        );
        if (reset) {
          setHasMore(false);
        }
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [searchQuery, selectedCategory, offset, limit]
  );

  // Initial load
  React.useEffect(() => {
    fetchProducts(true);
  }, []);

  // When filters change
  React.useEffect(() => {
    fetchProducts(true);
  }, [selectedCategory]);

  // Handle search
  const handleSearch = () => {
    fetchProducts(true);
  };

  // Handle refresh
  const onRefresh = useCallback(() => {
    setRefreshing(true);
    fetchProducts(true);
  }, [fetchProducts]);

  // Load more products
  const loadMoreProducts = () => {
    if (!loading && hasMore) {
      fetchProducts();
    }
  };

  // Render list footer (loading indicator)
  const renderFooter = () => {
    if (!loading) return null;
    return (
      <View style={marketStyles.footerLoader}>
        <ActivityIndicator size="small" color={COLORS.accent} />
      </View>
    );
  };

  return (
    <View
      style={[
        marketStyles.container,
        { paddingTop: 0 }, // Remove paddingTop completely to fix whitespace issue
      ]}
    >
      <StatusBar barStyle="light-content" backgroundColor={COLORS.primary} />

      {/* Header with no extra padding */}
      <View
        style={[
          marketStyles.header,
          {
            paddingTop: insets.top, // Use insets.top for proper SafeArea handling
          },
        ]}
      >
        <ThemedText style={marketStyles.headerTitle}>Farmers Market</ThemedText>
        <View style={{ flexDirection: "row", alignItems: "center" }}>
          {user && (
            <>
              <TouchableOpacity
                style={marketStyles.ordersButton}
                onPress={() => setShowOrdersModal(true)}
              >
                <Ionicons
                  name="receipt-outline"
                  size={22}
                  color={COLORS.light}
                />
                <Text style={marketStyles.ordersButtonText}>My Orders</Text>
              </TouchableOpacity>
            </>
          )}
          <TouchableOpacity
            style={marketStyles.cartButton}
            onPress={() => setCartVisible(true)}
          >
            <Ionicons name="cart-outline" size={24} color={COLORS.light} />
            {totalItems > 0 && (
              <View style={marketStyles.cartBadge}>
                <Text style={marketStyles.cartBadgeText}>
                  {totalItems > 9 ? "9+" : totalItems}
                </Text>
              </View>
            )}
          </TouchableOpacity>
        </View>
      </View>

      {/* Search bar */}
      <View style={marketStyles.searchContainer}>
        <View style={marketStyles.searchBar}>
          <Ionicons name="search" size={20} color={COLORS.muted} />
          <TextInput
            style={marketStyles.searchInput}
            placeholder="Search fresh products..."
            placeholderTextColor={COLORS.muted}
            value={searchQuery}
            onChangeText={setSearchQuery}
            returnKeyType="search"
            onSubmitEditing={handleSearch}
            clearButtonMode="while-editing"
          />
        </View>
      </View>

      {/* Category filter */}
      <View style={marketStyles.categoryFilterContainer}>
        <FlatList
          horizontal
          data={CATEGORIES}
          keyExtractor={(item) => item.id}
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={marketStyles.categoriesContainer}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={[
                marketStyles.categoryButton,
                selectedCategory === item.id &&
                  marketStyles.categoryButtonActive,
              ]}
              onPress={() => {
                setSelectedCategory(item.id);
                fetchProducts(true);
              }}
            >
              <Text
                style={[
                  marketStyles.categoryText,
                  selectedCategory === item.id &&
                    marketStyles.categoryTextActive,
                ]}
              >
                {item.name}
              </Text>
            </TouchableOpacity>
          )}
        />
      </View>

      {/* Products list */}
      {products.length === 0 && !loading ? (
        <View style={marketStyles.noProductsContainer}>
          <Ionicons name="basket-outline" size={70} color={COLORS.muted} />
          <ThemedText style={marketStyles.noProductsText}>
            No products found in this category
          </ThemedText>
          <TouchableOpacity
            style={marketStyles.refreshButton}
            onPress={() => {
              setSelectedCategory(""); // Reset to "All" category
              fetchProducts(true);
            }}
          >
            <Text style={marketStyles.refreshButtonText}>
              Show All Products
            </Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => item.id.toString()}
          renderItem={({ item }) => (
            <ProductItem item={item} onAddToCart={handleAddToCart} />
          )}
          numColumns={2}
          contentContainerStyle={[
            marketStyles.listContainer,
            { paddingBottom: 50 },
          ]}
          onEndReached={loadMoreProducts}
          onEndReachedThreshold={0.5}
          ListFooterComponent={renderFooter}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={[COLORS.primary]}
              tintColor={COLORS.primary}
            />
          }
        />
      )}

      {/* Subtotal bar at bottom of screen */}
      {totalItems > 0 && (
        <View style={styles.subtotalContainer}>
          <View style={styles.subtotalInfo}>
            <Text style={styles.subtotalText}>
              {totalItems} {totalItems === 1 ? "item" : "items"}
            </Text>
            <Text style={styles.subtotalPrice}>₱{totalPrice.toFixed(2)}</Text>
          </View>
          <TouchableOpacity
            style={styles.viewCartButton}
            onPress={() => setCartVisible(true)}
          >
            <Text style={styles.viewCartButtonText}>View Cart</Text>
            <Ionicons name="chevron-forward" size={16} color="#fff" />
          </TouchableOpacity>
        </View>
      )}

      {/* Cart Modal */}
      <CartScreen
        visible={cartVisible}
        onClose={() => setCartVisible(false)}
        onCheckout={handleCheckout}
      />

      {/* Payment Modal */}
      <PaymentScreen
        visible={paymentVisible}
        onClose={handlePaymentCancel}
        onComplete={handlePaymentComplete}
        orderTotal={orderAmount}
        orderId={orderId}
      />

      {/* Orders Modal (combined pickup and feedback) */}
      <Orders
        visible={showOrdersModal}
        onClose={() => setShowOrdersModal(false)}
      />

      {/* Schedule Pickup Modal - This is the new component */}
      <SchedulePickupScreen
        visible={schedulePickupVisible}
        onClose={handleSchedulePickupClose}
        orderId={orderId}
      />
    </View>
  );
};

// Additional styles for the subtotal bar
const styles = {
  subtotalContainer: {
    position: "absolute" as const,
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#fff",
    flexDirection: "row" as const,
    alignItems: "center" as const,
    justifyContent: "space-between" as const,
    borderTopWidth: 1,
    borderTopColor: "#eee",
    paddingHorizontal: 15,
    paddingVertical: 10,
    elevation: 5,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -3 },
    shadowOpacity: 0.1,
    shadowRadius: 5,
  },
  subtotalInfo: {
    flex: 1,
  },
  subtotalText: {
    fontSize: 14,
    color: COLORS.dark,
  },
  subtotalPrice: {
    fontSize: 18,
    fontWeight: "bold" as const,
    color: COLORS.dark,
  },
  viewCartButton: {
    backgroundColor: COLORS.primary,
    flexDirection: "row" as const,
    alignItems: "center" as const,
    paddingVertical: 8,
    paddingHorizontal: 15,
    borderRadius: 8,
  },
  viewCartButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold" as const,
    marginRight: 5,
  },
};

export default MarketContent;
