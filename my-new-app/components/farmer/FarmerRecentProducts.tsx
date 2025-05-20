import React, { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  FlatList,
  Image,
  Dimensions,
  Animated,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { getImageUrl } from "@/constants/Config";
import { getImagePaths, validateImageUrl } from "@/constants/ImageUtils";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import ProductImage from "@/components/ui/ProductImage";

interface Product {
  product_id: number;
  name: string;
  description: string;
  price: number;
  status: "pending" | "approved" | "rejected";
  image: string | null;
  stock: number;
  unit_type: string;
  created_at: string;
  updated_at: string;
}

interface RecentProductsProps {
  recentProducts: Product[];
  navigateToProductDetails: (productId: number) => void;
  navigateToAddProduct: () => void;
  navigateToManageProducts: () => void;
}

export default function FarmerRecentProducts({
  recentProducts,
  navigateToProductDetails,
  navigateToAddProduct,
  navigateToManageProducts,
}: RecentProductsProps) {
  const windowWidth = Dimensions.get("window").width;
  const cardWidth = windowWidth * 0.65;
  const [validatedProducts, setValidatedProducts] = useState<
    Record<number, boolean>
  >({});
  const [validUrls, setValidUrls] = useState<Record<number, string>>({});

  // Pre-validate image URLs to improve loading experience
  const prevalidateImages = async (products: Product[]) => {
    const validationResults: Record<number, boolean> = {};
    const bestUrls: Record<number, string> = {};

    // Only validate a few images to avoid too many network requests
    const productsToValidate = products.slice(0, 3);

    for (const product of productsToValidate) {
      if (product.image) {
        try {
          const url = getImageUrl(product.image);
          const isValid = await validateImageUrl(url);
          validationResults[product.product_id] = isValid;

          if (isValid) {
            bestUrls[product.product_id] = url;
          } else {
            // If the primary URL is invalid, try all paths
            const allPaths = getImagePaths(product.image);
            for (let i = 0; i < allPaths.length; i++) {
              const pathValid = await validateImageUrl(allPaths[i]);
              if (pathValid) {
                validationResults[product.product_id] = true;
                bestUrls[product.product_id] = allPaths[i];
                console.log(
                  `[FarmerRecentProducts] Found valid alternate URL for product ${product.product_id}: ${allPaths[i]}`
                );
                break;
              }
            }
          }
        } catch (e) {
          console.error(
            `[FarmerRecentProducts] Error pre-validating image for product ${product.product_id}:`,
            e
          );
        }
      }
    }

    setValidatedProducts(validationResults);
    setValidUrls(bestUrls);
    return { validationResults, bestUrls };
  };

  // Enhanced debug logging for image paths
  useEffect(() => {
    if (recentProducts && recentProducts.length > 0) {
      console.log(
        "[FarmerRecentProducts] Number of products:",
        recentProducts.length
      );

      // Log a sample product for debugging
      const sampleProduct = recentProducts[0];
      if (sampleProduct && sampleProduct.image) {
        console.log(
          `[FarmerRecentProducts] Sample product image path: ${sampleProduct.image}`
        );

        // Get processed URL and log it
        const processedUrl = getImageUrl(sampleProduct.image);
        console.log(`[FarmerRecentProducts] Processed URL: ${processedUrl}`);

        // Pre-validate the image URL
        validateImageUrl(processedUrl)
          .then((isValid: boolean) => {
            console.log(
              `[FarmerRecentProducts] Image validation result for sample product: ${isValid ? "Valid" : "Invalid"}`
            );
          })
          .catch((error: Error) => {
            console.error(
              `[FarmerRecentProducts] Error validating image: ${error}`
            );
          });

        // Also get all possible paths for reference
        const allPaths = getImagePaths(sampleProduct.image);
        console.log(
          `[FarmerRecentProducts] All possible image paths for debugging:`,
          allPaths
        );

        // Start prevalidation process
        prevalidateImages(recentProducts);
      }
    }
  }, [recentProducts]);

  // Format the date from ISO string to readable format
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  // Get status details for better visual representation
  const getStatusDetails = (status: string) => {
    switch (status) {
      case "approved":
        return {
          color: COLORS.success,
          icon: "checkmark-circle" as const,
          label: "Approved",
        };
      case "pending":
        return {
          color: COLORS.warning,
          icon: "time" as const,
          label: "Pending",
        };
      case "rejected":
        return {
          color: COLORS.danger,
          icon: "close-circle" as const,
          label: "Rejected",
        };
      default:
        return {
          color: COLORS.muted,
          icon: "help-circle" as const,
          label: "Unknown",
        };
    }
  };

  const renderProductItem = ({
    item,
    index,
  }: {
    item: Product;
    index: number;
  }) => {
    const statusDetails = getStatusDetails(item.status);
    // Check if we have a prevalidated URL for this product
    const hasValidatedUrl = validUrls[item.product_id] !== undefined;

    return (
      <TouchableOpacity
        style={[styles.productCard, { width: cardWidth }]}
        onPress={() => navigateToProductDetails(item.product_id)}
        activeOpacity={0.9}
      >
        <View style={styles.productImageContainer}>
          <ProductImage
            imagePath={
              hasValidatedUrl ? validUrls[item.product_id] : item.image
            }
            productId={item.product_id}
            style={styles.productImage}
            fallbackIcon="image-outline"
            fallbackIconSize={36}
            fallbackIconColor="#ccc"
            placeholderColor="#f8f8f8"
            maxRetries={3}
            directUrl={hasValidatedUrl} // Use direct URL if we've validated it
            showDebugInfo={true} // Enable debug info to help diagnose issues
          />
          <View
            style={[
              styles.statusBadge,
              { backgroundColor: statusDetails.color },
            ]}
          >
            <Ionicons name={statusDetails.icon} size={12} color="#fff" />
            <ThemedText style={styles.statusText}>
              {statusDetails.label}
            </ThemedText>
          </View>
        </View>
        <View style={styles.productInfo}>
          <View style={styles.productNameRow}>
            <ThemedText style={styles.productName} numberOfLines={1}>
              {item.name}
            </ThemedText>
          </View>
          <View style={styles.productDetailsRow}>
            <View style={styles.priceContainer}>
              <ThemedText style={styles.productPrice}>
                ₱{item.price.toFixed(2)}
              </ThemedText>
              <ThemedText style={styles.unitType}>
                per {item.unit_type}
              </ThemedText>
            </View>

            <View style={styles.stockContainer}>
              <Ionicons
                name={item.stock > 10 ? "analytics" : "alert-circle"}
                size={16}
                color={item.stock > 10 ? "#4CAF50" : "#FFC107"}
              />
              <ThemedText
                style={[
                  styles.stockText,
                  { color: item.stock > 10 ? "#4CAF50" : "#FFC107" },
                ]}
              >
                {item.stock} in stock
              </ThemedText>
            </View>
          </View>
          <View style={styles.dateContainer}>
            <Ionicons name="calendar-outline" size={12} color={COLORS.muted} />
            <ThemedText style={styles.dateText}>
              Added {formatDate(item.created_at)}
            </ThemedText>
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.headerRow}>
        <ThemedText style={styles.title}>Recent Products</ThemedText>
        <TouchableOpacity
          style={styles.viewAllButton}
          onPress={navigateToManageProducts}
        >
          <ThemedText style={styles.viewAllText}>Manage All</ThemedText>
          <Ionicons name="chevron-forward" size={16} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      {recentProducts.length > 0 ? (
        <FlatList
          data={recentProducts}
          renderItem={renderProductItem}
          keyExtractor={(item) => item.product_id.toString()}
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.productsList}
          nestedScrollEnabled={true}
          snapToInterval={cardWidth + 16} // Snap to card width + margin
          decelerationRate="fast"
          snapToAlignment="start"
        />
      ) : (
        <View style={styles.emptyState}>
          <View style={styles.emptyStateIconContainer}>
            <Ionicons name="basket-outline" size={48} color="#e0e0e0" />
          </View>
          <ThemedText style={styles.emptyStateTitle}>
            No Products Yet
          </ThemedText>
          <ThemedText style={styles.emptyStateText}>
            Your recently added products will appear here
          </ThemedText>
          <TouchableOpacity
            style={styles.addProductButton}
            onPress={navigateToAddProduct}
          >
            <Ionicons name="add-circle" size={18} color="#fff" />
            <ThemedText style={styles.addProductButtonText}>
              Add Your First Product
            </ThemedText>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  headerRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  viewAllButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(27, 94, 32, 0.08)",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
  },
  viewAllText: {
    fontSize: 14,
    color: COLORS.primary,
    marginRight: 2,
    fontWeight: "600",
  },
  productsList: {
    paddingRight: 16,
    paddingBottom: 8,
  },
  productCard: {
    backgroundColor: "#fff",
    borderRadius: 12,
    marginRight: 16,
    overflow: "hidden",
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 3,
    borderWidth: 1,
    borderColor: "#f0f0f0",
  },
  productImageContainer: {
    position: "relative",
    height: 160,
  },
  productImage: {
    width: "100%",
    height: "100%",
  },
  statusBadge: {
    position: "absolute",
    top: 12,
    right: 12,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    flexDirection: "row",
    alignItems: "center",
    zIndex: 10,
  },
  statusText: {
    color: "#fff",
    fontSize: 11,
    fontWeight: "bold",
    marginLeft: 4,
  },
  productInfo: {
    padding: 16,
  },
  productNameRow: {
    marginBottom: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: "700",
    color: COLORS.text,
  },
  productDetailsRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  priceContainer: {
    flexDirection: "row",
    alignItems: "baseline",
  },
  productPrice: {
    fontSize: 16,
    color: COLORS.primary,
    fontWeight: "700",
  },
  unitType: {
    fontSize: 12,
    color: COLORS.muted,
    marginLeft: 4,
  },
  stockContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  stockText: {
    fontSize: 12,
    marginLeft: 4,
    fontWeight: "500",
  },
  dateContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  dateText: {
    fontSize: 11,
    color: COLORS.muted,
    marginLeft: 4,
  },
  emptyState: {
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 32,
    paddingHorizontal: 16,
  },
  emptyStateIconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "#f8f8f8",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 16,
  },
  emptyStateTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.text,
    marginBottom: 8,
  },
  emptyStateText: {
    fontSize: 14,
    color: COLORS.muted,
    textAlign: "center",
    marginBottom: 24,
  },
  addProductButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: COLORS.primary,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 24,
  },
  addProductButtonText: {
    color: "#fff",
    fontWeight: "600",
    marginLeft: 8,
  },
});
