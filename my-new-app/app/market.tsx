import React, { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  ScrollView,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  TextInput,
  RefreshControl,
  Platform,
  Alert,
} from "react-native";
import { useRouter } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { getApiBaseUrlSync } from "@/services/apiConfig";

// Define color scheme with TypeScript interface
interface ColorScheme {
  primary: string;
  secondary: string;
  accent: string;
  light: string;
  dark: string;
  text: string;
  muted: string;
  cardBg: string;
  shadow: string;
}

// Keep color scheme consistent with other screens
const COLORS: ColorScheme = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  cardBg: "#F9FBF7",
  shadow: "#000000",
};

// Define product interface to match database structure
interface Product {
  product_id: number;
  name: string;
  description: string | null;
  price: number;
  farmer_id: number | null;
  status: "pending" | "approved" | "rejected";
  created_at: string;
  updated_at: string;
  image: string | null;
  stock: number;
  farmer_name?: string; // Optional field joined from users table
}

// Define category interface
interface Category {
  category_id: number;
  category_name: string;
}

type ApiOptions = {
  headers?: Record<string, string>;
  method?: string;
  body?: string;
  [key: string]: any;
};

const createApiService = () => ({
  fetch: async (endpoint: string, options: ApiOptions = {}) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      const url = `${baseUrl}${endpoint}`;

      console.log("[Market API] Making request to:", url);

      // Add timeout to requests
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 seconds timeout

      const response = await fetch(url, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          ...(options.headers || {}),
        },
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      // First, check if response exists
      if (!response) {
        throw new Error("No response received from server");
      }

      // For non-JSON responses, handle appropriately
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        const text = await response.text();
        console.log("[Market API] Non-JSON response:", text);
        throw new Error("Server returned non-JSON response");
      }

      const responseData = await response.json();
      console.log("[Market API] Response data:", responseData);

      if (!response.ok) {
        console.error("[Market API] Response error:", responseData);
        throw {
          status: response.status,
          message: responseData.message || "An error occurred",
          data: responseData,
        };
      }

      return responseData;
    } catch (error) {
      console.error("[Market API] Fetch error:", error);

      // More specific error handling
      if (error.name === "AbortError") {
        throw new Error("Request timed out. Please try again.");
      } else if (
        error instanceof TypeError &&
        error.message.includes("Network request failed")
      ) {
        throw new Error(
          "Cannot connect to the server. Please check your network connection."
        );
      }

      throw error;
    }
  },
});

const apiService = createApiService();

export default function MarketScreen() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [refreshing, setRefreshing] = useState(false);

  // Fetch products from the API
  useEffect(() => {
    fetchProducts();
    fetchCategories();
  }, []);

  // Filter products when search query or category changes
  useEffect(() => {
    filterProducts();
  }, [searchQuery, selectedCategory, products]);

  const fetchProducts = async () => {
    setLoading(true);
    setError(null);

    try {
      console.log("[Market] Fetching products...");

      // Add retry mechanism
      let attempts = 0;
      const maxAttempts = 2;
      let lastError;

      while (attempts < maxAttempts) {
        try {
          attempts++;
          console.log(`[Market] Attempt ${attempts} of ${maxAttempts}`);

          const response = await apiService.fetch("/products.php");

          if (response.status === "success" && response.data) {
            setProducts(response.data);
            setFilteredProducts(response.data);
            return; // Success, exit function
          } else {
            console.error("[Market] Unexpected data format:", response);
            throw new Error("Received invalid data format from server");
          }
        } catch (err) {
          console.error(`[Market] Attempt ${attempts} failed:`, err);
          lastError = err;

          if (attempts < maxAttempts) {
            // Wait before retrying (exponential backoff)
            const delay = Math.pow(2, attempts) * 1000;
            console.log(`[Market] Retrying in ${delay}ms...`);
            await new Promise((resolve) => setTimeout(resolve, delay));
          }
        }
      }

      // If we get here, all attempts failed
      throw (
        lastError ||
        new Error("Failed to load products after multiple attempts")
      );
    } catch (err) {
      console.error("[Market] All attempts failed:", err);
      setError(
        `Failed to load products. ${
          err instanceof Error
            ? err.message
            : "Please check your connection and try again."
        }`
      );
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const fetchCategories = async () => {
    try {
      console.log("[Market] Fetching categories...");

      // Similar retry mechanism for categories
      let attempts = 0;
      const maxAttempts = 2;

      while (attempts < maxAttempts) {
        try {
          attempts++;
          const response = await apiService.fetch("/categories.php");

          if (response.status === "success" && response.data) {
            setCategories(response.data);
            return; // Success, exit function
          } else {
            console.error("[Market] Unexpected categories format:", response);
            throw new Error("Invalid categories data");
          }
        } catch (err) {
          console.error(`[Market] Categories attempt ${attempts} failed:`, err);

          if (attempts < maxAttempts) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
          } else {
            throw err;
          }
        }
      }
    } catch (err) {
      console.error("[Market] Error fetching categories:", err);
      // Don't set error state for categories as it's not critical
    }
  };

  const filterProducts = () => {
    let filtered = [...products];

    // Filter by search query
    if (searchQuery.trim()) {
      filtered = filtered.filter(
        (product) =>
          product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          (product.description &&
            product.description
              .toLowerCase()
              .includes(searchQuery.toLowerCase()))
      );
    }

    // Filter by category
    if (selectedCategory !== null) {
      // This would need proper category mapping in the database
      // Here we're just simulating filtering by category
      // In a real app, you would filter based on the productcategorymapping table
      filtered = filtered.filter(
        (product) =>
          // This is a placeholder logic - need to be replaced with actual category mapping
          product.product_id % (selectedCategory + 1) === 0
      );
    }

    // Only show approved products
    filtered = filtered.filter((product) => product.status === "approved");

    setFilteredProducts(filtered);
  };

  const onRefresh = () => {
    setRefreshing(true);
    fetchProducts();
  };

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
          accessibilityLabel="Go back"
          accessibilityRole="button"
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Farmers Market</ThemedText>
        <View style={{ width: 24 }} />
      </View>

      {/* Search and Filter Section */}
      <View style={styles.searchContainer}>
        <View style={styles.searchInputContainer}>
          <Ionicons name="search" size={20} color={COLORS.muted} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search products..."
            placeholderTextColor={COLORS.muted}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery ? (
            <TouchableOpacity onPress={() => setSearchQuery("")}>
              <Ionicons name="close-circle" size={20} color={COLORS.muted} />
            </TouchableOpacity>
          ) : null}
        </View>
      </View>

      {/* Categories Horizontal Scroll */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.categoriesContainer}
      >
        <TouchableOpacity
          style={[
            styles.categoryChip,
            selectedCategory === null && styles.selectedCategoryChip,
          ]}
          onPress={() => setSelectedCategory(null)}
        >
          <ThemedText
            style={[
              styles.categoryChipText,
              selectedCategory === null && styles.selectedCategoryChipText,
            ]}
          >
            All
          </ThemedText>
        </TouchableOpacity>

        {categories.map((category) => (
          <TouchableOpacity
            key={category.category_id}
            style={[
              styles.categoryChip,
              selectedCategory === category.category_id &&
                styles.selectedCategoryChip,
            ]}
            onPress={() => setSelectedCategory(category.category_id)}
          >
            <ThemedText
              style={[
                styles.categoryChipText,
                selectedCategory === category.category_id &&
                  styles.selectedCategoryChipText,
              ]}
            >
              {category.category_name}
            </ThemedText>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* Main Content */}
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[COLORS.primary]}
          />
        }
      >
        {/* Loading State */}
        {loading && !refreshing && (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color={COLORS.primary} />
            <ThemedText style={styles.loadingText}>
              Loading products...
            </ThemedText>
          </View>
        )}

        {/* Error State */}
        {error && !loading && (
          <View style={styles.errorContainer}>
            <Ionicons name="warning" size={64} color={COLORS.accent} />
            <ThemedText style={styles.errorText}>{error}</ThemedText>
            <TouchableOpacity
              style={styles.retryButton}
              onPress={fetchProducts}
              accessibilityLabel="Retry loading products"
              accessibilityRole="button"
            >
              <ThemedText style={styles.retryButtonText}>Retry</ThemedText>
            </TouchableOpacity>
          </View>
        )}

        {/* Empty State */}
        {!loading && !error && filteredProducts.length === 0 && (
          <View style={styles.emptyContainer}>
            <Ionicons name="basket" size={64} color={COLORS.muted} />
            <ThemedText style={styles.emptyText}>No products found</ThemedText>
            <ThemedText style={styles.emptySubtext}>
              Try adjusting your search or check back later for new products
            </ThemedText>
          </View>
        )}

        {/* Products Grid */}
        <View style={styles.productsGrid}>
          {!loading &&
            !error &&
            filteredProducts.map((product) => (
              <ProductCard key={product.product_id} product={product} />
            ))}
        </View>
      </ScrollView>
    </View>
  );
}

// Product Card Component
const ProductCard = ({ product }: { product: Product }) => {
  const defaultImage = "https://via.placeholder.com/150?text=No+Image";

  const handleAddToCart = () => {
    Alert.alert("Add to Cart", `${product.name} has been added to your cart.`, [
      { text: "OK" },
    ]);
  };

  return (
    <View style={styles.productCard}>
      <Image
        source={{ uri: product.image || defaultImage }}
        style={styles.productImage}
        resizeMode="cover"
      />
      <View style={styles.productInfo}>
        <ThemedText style={styles.productName}>{product.name}</ThemedText>
        <ThemedText style={styles.productPrice}>
          ₱{product.price.toFixed(2)}
        </ThemedText>
        {product.stock > 0 ? (
          <ThemedText style={styles.stockInfo}>
            In Stock: {product.stock}
          </ThemedText>
        ) : (
          <ThemedText style={styles.outOfStockText}>Out of Stock</ThemedText>
        )}
      </View>
      <TouchableOpacity
        style={[
          styles.addToCartButton,
          product.stock <= 0 && styles.disabledButton,
        ]}
        onPress={handleAddToCart}
        disabled={product.stock <= 0}
      >
        <Ionicons name="cart" size={18} color={COLORS.light} />
        <ThemedText style={styles.addToCartText}>Add to Cart</ThemedText>
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    paddingTop: Platform.OS === "ios" ? 50 : 30,
    backgroundColor: COLORS.primary,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.2,
        shadowRadius: 4,
      },
      android: {
        elevation: 4,
      },
    }),
  },
  headerTitle: {
    fontSize: 20,
    color: COLORS.light,
    fontWeight: "700",
  },
  backButton: {
    padding: 8,
  },
  searchContainer: {
    padding: 16,
    backgroundColor: COLORS.light,
    borderBottomWidth: 1,
    borderBottomColor: "rgba(0,0,0,0.05)",
  },
  searchInputContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(0,0,0,0.05)",
    borderRadius: 8,
    paddingHorizontal: 12,
    height: 40,
  },
  searchInput: {
    flex: 1,
    height: 40,
    paddingHorizontal: 8,
    color: COLORS.text,
  },
  categoriesContainer: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: COLORS.light,
    flexDirection: "row",
    marginBottom: 4,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: "rgba(0,0,0,0.05)",
    borderRadius: 20,
    marginRight: 8,
  },
  selectedCategoryChip: {
    backgroundColor: COLORS.primary,
  },
  categoryChipText: {
    color: COLORS.text,
  },
  selectedCategoryChipText: {
    color: COLORS.light,
    fontWeight: "500",
  },
  scrollView: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingBottom: 40,
  },
  productsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
    paddingTop: 16,
  },
  productCard: {
    width: "48%",
    backgroundColor: COLORS.cardBg,
    borderRadius: 10,
    marginBottom: 16,
    overflow: "hidden",
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  productImage: {
    width: "100%",
    height: 150,
    backgroundColor: "#f0f0f0",
  },
  productInfo: {
    padding: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: "600",
    color: COLORS.text,
    marginBottom: 4,
  },
  productPrice: {
    fontSize: 16,
    color: COLORS.primary,
    fontWeight: "bold",
    marginBottom: 8,
  },
  stockInfo: {
    fontSize: 13,
    color: COLORS.muted,
  },
  outOfStockText: {
    fontSize: 13,
    color: COLORS.accent,
  },
  addToCartButton: {
    flexDirection: "row",
    backgroundColor: COLORS.primary,
    padding: 10,
    alignItems: "center",
    justifyContent: "center",
  },
  disabledButton: {
    backgroundColor: COLORS.muted,
  },
  addToCartText: {
    color: COLORS.light,
    marginLeft: 4,
    fontSize: 14,
    fontWeight: "600",
  },
  loadingContainer: {
    padding: 50,
    alignItems: "center",
  },
  loadingText: {
    marginTop: 10,
    color: COLORS.primary,
    fontSize: 16,
  },
  errorContainer: {
    padding: 30,
    alignItems: "center",
  },
  errorText: {
    marginTop: 10,
    color: COLORS.text,
    textAlign: "center",
    fontSize: 16,
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 6,
  },
  retryButtonText: {
    color: COLORS.light,
    fontSize: 16,
    fontWeight: "600",
  },
  emptyContainer: {
    padding: 50,
    alignItems: "center",
  },
  emptyText: {
    marginTop: 10,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: "bold",
  },
  emptySubtext: {
    marginTop: 5,
    color: COLORS.muted,
    textAlign: "center",
    fontSize: 14,
  },
});
