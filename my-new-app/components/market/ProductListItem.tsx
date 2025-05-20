import React from "react";
import {
  View,
  Text,
  StyleSheet,
  Pressable,
  TouchableOpacity,
} from "react-native";
import { Product } from "../../types/Product";
import OptimizedImage from "../ui/OptimizedImage";
import { formatPrice } from "../../utils/formatters";
import { Ionicons } from "@expo/vector-icons";

interface ProductListItemProps {
  product: Product;
  onPress: (product: Product) => void;
  onAddToCart?: (product: Product) => void;
  compact?: boolean;
}

/**
 * ProductListItem component that uses the OptimizedImage component
 * for more efficient image loading and caching
 */
const ProductListItem: React.FC<ProductListItemProps> = ({
  product,
  onPress,
  onAddToCart,
  compact = false,
}) => {
  const handleAddToCart = (e: any) => {
    e.stopPropagation();
    if (onAddToCart && product.stock > 0) {
      onAddToCart(product);
    }
  };

  return (
    <Pressable
      style={[styles.container, compact && styles.compactContainer]}
      onPress={() => onPress(product)}
      android_ripple={{ color: "rgba(0,0,0,0.1)" }}
    >
      <View
        style={[styles.imageContainer, compact && styles.compactImageContainer]}
      >
        <OptimizedImage
          source={product.image_url}
          cachePolicy="memory-disk"
          transition={200}
        />

        {product.discount > 0 && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountText}>-{product.discount}%</Text>
          </View>
        )}
      </View>

      <View
        style={[styles.infoContainer, compact && styles.compactInfoContainer]}
      >
        <Text style={styles.name} numberOfLines={compact ? 1 : 2}>
          {product.name}
        </Text>

        <View style={styles.priceRow}>
          <Text style={styles.price}>₱{formatPrice(product.price)}</Text>

          {product.original_price > product.price && (
            <Text style={styles.originalPrice}>
              ₱{formatPrice(product.original_price)}
            </Text>
          )}
        </View>

        <View style={styles.bottomRow}>
          <Text style={[styles.stock, product.stock <= 5 && styles.lowStock]}>
            {product.stock > 0
              ? `${product.stock} ${product.unit || "pcs"} available`
              : "Out of stock"}
          </Text>

          {onAddToCart && product.stock > 0 && (
            <TouchableOpacity
              style={styles.addButton}
              onPress={handleAddToCart}
              activeOpacity={0.8}
            >
              <Ionicons
                name="add-circle"
                size={compact ? 24 : 28}
                color="#4CAF50"
              />
            </TouchableOpacity>
          )}
        </View>
      </View>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  container: {
    flexDirection: "row",
    backgroundColor: "white",
    borderRadius: 8,
    overflow: "hidden",
    marginBottom: 12,
    elevation: 2,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  compactContainer: {
    marginBottom: 8,
  },
  imageContainer: {
    width: 100,
    height: 100,
  },
  compactImageContainer: {
    width: 80,
    height: 80,
  },
  infoContainer: {
    flex: 1,
    padding: 12,
    justifyContent: "space-between",
  },
  compactInfoContainer: {
    padding: 8,
  },
  name: {
    fontSize: 16,
    fontWeight: "500",
    marginBottom: 6,
  },
  priceRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
  },
  price: {
    fontSize: 16,
    fontWeight: "700",
    color: "#4CAF50",
  },
  originalPrice: {
    fontSize: 13,
    color: "#999",
    textDecorationLine: "line-through",
    marginLeft: 6,
  },
  bottomRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  stock: {
    fontSize: 12,
    color: "#666",
  },
  lowStock: {
    color: "#f57c00",
  },
  addButton: {
    padding: 4,
  },
  discountBadge: {
    position: "absolute",
    top: 0,
    right: 0,
    backgroundColor: "#F44336",
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
  discountText: {
    color: "white",
    fontSize: 10,
    fontWeight: "600",
  },
});

export default ProductListItem;
