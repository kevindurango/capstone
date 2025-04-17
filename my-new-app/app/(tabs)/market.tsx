import React, { useState, useEffect, useCallback } from "react";
import {
  StyleSheet,
  View,
  FlatList,
  Image,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Text,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { getApiBaseUrlSync } from "@/services/apiConfig";

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  unit: string;
  quantity_available: number;
  image_url: string | null;
  category: string;
  farm_name: string;
  farmer: string;
  contact: string;
}

// API service for market functionality
const createMarketService = () => ({
  getProducts: async (
    params: {
      category?: string;
      search?: string;
      limit?: number;
      offset?: number;
    } = {}
  ) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      let url = `${baseUrl}/market.php`;

      // Build query string from params
      const queryParams = Object.entries(params)
        .filter(
          ([_, value]) => value !== undefined && value !== null && value !== ""
        )
        .map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`)
        .join("&");

      if (queryParams) {
        url += `?${queryParams}`;
      }

      console.log("[Market] Fetching products from:", url);

      const response = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      const textResponse = await response.text();
      console.log(
        "[Market] Raw response:",
        textResponse.substring(0, 200) +
          (textResponse.length > 200 ? "..." : "")
      );

      // Check if response starts with HTML - common server error
      if (
        textResponse.trim().startsWith("<!DOCTYPE") ||
        textResponse.trim().startsWith("<html")
      ) {
        console.error("[Market] Received HTML instead of JSON");
        throw new Error("Server error occurred. Please check server logs.");
      }

      let data;
      try {
        data = JSON.parse(textResponse);
      } catch (error) {
        console.error("[Market] JSON parse error:", error);
        throw new Error(
          "Invalid response from server. Please check server configuration."
        );
      }

      if (response.status >= 400 || data.status === "error") {
        throw new Error(data?.message || "Failed to fetch products");
      }

      return data;
    } catch (error: any) {
      console.error("[Market] Error fetching products:", error.message);
      throw error;
    }
  },
});

const marketService = createMarketService();

// Categories for filter
const CATEGORIES = [
  { id: "", name: "All" },
  { id: "Vegetable", name: "Vegetables" },
  { id: "Fruit", name: "Fruits" },
  { id: "Grain", name: "Grains" },
  { id: "Dairy", name: "Dairy" },
  { id: "Meat", name: "Meat" },
  { id: "Others", name: "Others" },
];

// Create a separate component for product items
const ProductItem = React.memo(({ item }: { item: Product }) => {
  const [imageError, setImageError] = useState(false);

  return (
    <TouchableOpacity
      style={styles.productCard}
      onPress={() =>
        Alert.alert(
          item.name,
          `Price: ₱${item.price.toFixed(2)} per ${item.unit}\n` +
            `Available: ${item.quantity_available} ${item.unit}(s)\n` +
            `Farm: ${item.farm_name || "Unknown"}\n` +
            `Farmer: ${item.farmer || "Unknown"}\n` +
            `Contact: ${item.contact || "No contact provided"}\n\n` +
            `${item.description || "No description available"}`,
          [{ text: "Close" }]
        )
      }
    >
      <View style={styles.productImageContainer}>
        {!imageError && item.image_url ? (
          <Image
            source={{ uri: item.image_url }}
            style={styles.productImage}
            resizeMode="cover"
            onError={() => {
              console.error(`[Market] Failed to load image: ${item.image_url}`);
              setImageError(true);
            }}
          />
        ) : (
          <View style={styles.placeholderImage}>
            <Ionicons name="leaf-outline" size={40} color={COLORS.light} />
          </View>
        )}
        {/* Category badge positioned on top of image */}
        <View style={styles.categoryBadge}>
          <Text style={styles.categoryBadgeText}>
            {item.category || "Uncategorized"}
          </Text>
        </View>
      </View>

      <View style={styles.productDetails}>
        <ThemedText
          style={styles.productName}
          numberOfLines={1}
          ellipsizeMode="tail"
        >
          {item.name}
        </ThemedText>
        <ThemedText
          style={styles.productFarm}
          numberOfLines={1}
          ellipsizeMode="tail"
        >
          {item.farm_name || "Unknown Farm"}
        </ThemedText>
        <View style={styles.productPriceRow}>
          <ThemedText style={styles.productPrice}>
            ₱{item.price.toFixed(2)}/{item.unit}
          </ThemedText>
          {item.quantity_available > 0 ? (
            <View style={styles.stockBadge}>
              <Text style={styles.stockBadgeText}>In Stock</Text>
            </View>
          ) : (
            <View style={[styles.stockBadge, styles.outOfStockBadge]}>
              <Text style={[styles.stockBadgeText, styles.outOfStockText]}>
                Out of Stock
              </Text>
            </View>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );
});

export default function MarketScreen() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("");
  const [offset, setOffset] = useState(0);
  const [hasMore, setHasMore] = useState(true);
  const [totalCount, setTotalCount] = useState(0);
  const limit = 10;

  // Function to fetch products
  const fetchProducts = useCallback(
    async (reset = false) => {
      try {
        setLoading(true);
        const newOffset = reset ? 0 : offset;

        const response = await marketService.getProducts({
          category: selectedCategory,
          search: searchQuery.trim() || undefined,
          limit,
          offset: newOffset,
        });

        if (response && response.status === "success") {
          const fetchedProducts = response.data.products || [];

          console.log("[Market] Fetched products:", fetchedProducts);

          if (reset) {
            setProducts(fetchedProducts);
          } else {
            setProducts((prev) => [...prev, ...fetchedProducts]);
          }

          setTotalCount(response.data.total || 0);
          setHasMore(newOffset + limit < (response.data.total || 0));

          if (reset) {
            setOffset(limit);
          } else {
            setOffset(newOffset + limit);
          }
        } else {
          Alert.alert("Error", "Failed to load products");
        }
      } catch (error: any) {
        console.error("[Market] Fetch error:", error);
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
  useEffect(() => {
    fetchProducts(true);
  }, []);

  // When filters change
  useEffect(() => {
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
      <View style={styles.footerLoader}>
        <ActivityIndicator size="small" color={COLORS.accent} />
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <ThemedText style={styles.headerTitle}>Farmers Market</ThemedText>
      </View>

      {/* Search bar */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBar}>
          <Ionicons name="search" size={20} color={COLORS.muted} />
          <TextInput
            style={styles.searchInput}
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
      <View style={styles.categoryFilterContainer}>
        <FlatList
          horizontal
          data={CATEGORIES}
          keyExtractor={(item) => item.id}
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.categoriesContainer}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={[
                styles.categoryButton,
                selectedCategory === item.id && styles.categoryButtonActive,
              ]}
              onPress={() => {
                setSelectedCategory(item.id);
                fetchProducts(true);
              }}
            >
              <Text
                style={[
                  styles.categoryText,
                  selectedCategory === item.id && styles.categoryTextActive,
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
        <View style={styles.noProductsContainer}>
          <Ionicons name="basket-outline" size={70} color={COLORS.muted} />
          <ThemedText style={styles.noProductsText}>
            No products found in this category
          </ThemedText>
          <TouchableOpacity
            style={styles.refreshButton}
            onPress={() => {
              setSelectedCategory(""); // Reset to "All" category
              fetchProducts(true);
            }}
          >
            <Text style={styles.refreshButtonText}>Show All Products</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => item.id.toString()}
          renderItem={({ item }) => <ProductItem item={item} />}
          numColumns={2}
          contentContainerStyle={styles.listContainer}
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
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 15,
    backgroundColor: COLORS.primary,
    borderBottomWidth: 1,
    borderBottomColor: "rgba(0,0,0,0.05)",
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: "bold",
    color: COLORS.light,
    letterSpacing: 0.5,
  },
  searchContainer: {
    paddingHorizontal: 15,
    paddingVertical: 12,
    backgroundColor: COLORS.primary,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.15,
    shadowRadius: 5,
    elevation: 6,
    marginBottom: 5,
  },
  searchBar: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: COLORS.light,
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 2,
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.05)",
  },
  searchInput: {
    flex: 1,
    paddingVertical: 10,
    paddingHorizontal: 10,
    color: COLORS.dark,
    fontSize: 15,
  },
  categoryFilterContainer: {
    backgroundColor: "#f8f8f8",
    paddingVertical: 5,
    borderBottomWidth: 1,
    borderBottomColor: "rgba(0,0,0,0.05)",
  },
  categoriesContainer: {
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  categoryButton: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    marginRight: 8,
    marginVertical: 4,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 50,
    backgroundColor: "#f0f0f0",
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.05)",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  categoryButtonActive: {
    backgroundColor: COLORS.accent,
  },
  categoryText: {
    fontSize: 14,
    color: COLORS.dark,
    fontWeight: "500",
    textAlignVertical: "center",
  },
  categoryTextActive: {
    color: COLORS.light,
    fontWeight: "bold",
  },
  listContainer: {
    padding: 10,
    paddingBottom: 20,
  },
  productCard: {
    flex: 1,
    margin: 8,
    borderRadius: 16,
    backgroundColor: "white",
    overflow: "hidden",
    elevation: 4,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    maxWidth: "47%",
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.03)",
  },
  productImageContainer: {
    height: 130,
    width: "100%",
    backgroundColor: "#f0f0f0",
    position: "relative",
  },
  productImage: {
    height: "100%",
    width: "100%",
  },
  placeholderImage: {
    height: "100%",
    width: "100%",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: COLORS.primary,
  },
  categoryBadge: {
    position: "absolute",
    top: 8,
    left: 8,
    paddingHorizontal: 8,
    paddingVertical: 3,
    backgroundColor: "rgba(255,255,255,0.85)",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.05)",
  },
  categoryBadgeText: {
    fontSize: 10,
    fontWeight: "bold",
    color: COLORS.dark,
  },
  productDetails: {
    padding: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: "bold",
    marginBottom: 4,
    color: COLORS.dark,
  },
  productFarm: {
    fontSize: 13,
    color: "#777",
    marginBottom: 8,
  },
  productPriceRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginTop: 4,
  },
  productPrice: {
    fontSize: 15,
    fontWeight: "bold",
    color: COLORS.accent,
  },
  stockBadge: {
    paddingHorizontal: 6,
    paddingVertical: 3,
    backgroundColor: "#e8f5e9",
    borderRadius: 4,
    borderWidth: 1,
    borderColor: "#c8e6c9",
  },
  stockBadgeText: {
    fontSize: 10,
    fontWeight: "bold",
    color: "#2e7d32",
  },
  outOfStockBadge: {
    backgroundColor: "#ffebee",
    borderColor: "#ffcdd2",
  },
  outOfStockText: {
    color: "#c62828",
  },
  footerLoader: {
    paddingVertical: 20,
    alignItems: "center",
  },
  noProductsContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 50,
    paddingHorizontal: 20,
  },
  noProductsText: {
    fontSize: 18,
    marginTop: 16,
    marginBottom: 8,
    color: COLORS.muted,
    fontWeight: "500",
    textAlign: "center",
  },
  refreshButton: {
    marginTop: 15,
    paddingHorizontal: 20,
    paddingVertical: 12,
    backgroundColor: COLORS.accent,
    borderRadius: 12,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 3,
    elevation: 3,
  },
  refreshButtonText: {
    color: COLORS.light,
    fontWeight: "bold",
    fontSize: 16,
  },
});
