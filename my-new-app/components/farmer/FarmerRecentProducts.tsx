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
import { Ionicons, MaterialIcons } from "@expo/vector-icons";

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

export function FarmerRecentProducts({
  recentProducts,
  navigateToProductDetails,
  navigateToAddProduct,
  navigateToManageProducts,
}: RecentProductsProps) {
  const windowWidth = Dimensions.get("window").width;
  const cardWidth = windowWidth * 0.65;
  // Add state to track image loading errors
  const [imageErrors, setImageErrors] = useState<Record<number, boolean>>({});

  // Debug log for image paths - moved outside of render
  useEffect(() => {
    if (recentProducts && recentProducts.length > 0) {
      recentProducts.forEach((product) => {
        if (product.image) {
          console.log(
            `[FarmerDebug] Product ${product.product_id} image path: ${product.image}`
          );
        }
      });
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

    return (
      <TouchableOpacity
        style={[styles.productCard, { width: cardWidth }]}
        onPress={() => navigateToProductDetails(item.product_id)}
        activeOpacity={0.9}
      >
        <View style={styles.productImageContainer}>
          {item.image && !imageErrors[item.product_id] ? (
            <Image
              source={{ uri: getImageUrl(item.image) }}
              style={styles.productImage}
              resizeMode="cover"
              onError={() => {
                console.error(
                  `[Farmer] Failed to load image: ${getImageUrl(item.image)}`
                );
                setImageErrors((prev) => ({
                  ...prev,
                  [item.product_id]: true,
                }));
              }}
            />
          ) : (
            <View style={styles.placeholderImage}>
              <MaterialIcons name="image" size={36} color="#ccc" />
              <ThemedText style={styles.placeholderText}>No Image</ThemedText>
            </View>
          )}
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
  placeholderImage: {
    width: "100%",
    height: "100%",
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f8f8f8",
  },
  placeholderText: {
    color: "#ccc",
    marginTop: 8,
    fontSize: 12,
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
