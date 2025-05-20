import React from "react";
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
} from "react-native";
import { useNavigation } from "@react-navigation/native";
import ProductImage from "./ProductImage";
import { processImagePath } from "../utils/config";

const FarmerRecentProducts = ({ products }) => {
  const navigation = useNavigation();

  console.log(
    "[FarmerRecentProducts] Number of products:",
    products?.length || 0
  );

  if (products && products.length > 0) {
    console.log(
      "[FarmerRecentProducts] Sample product image path:",
      products[0].image
    );

    // Test image path processing
    const processedUrl = processImagePath(products[0].image);
    console.log("[FarmerRecentProducts] Processed URL:", processedUrl);

    // Log all possible image paths for debugging
    const allPaths = [
      processImagePath(products[0].image),
      processImagePath(products[0].image.replace("uploads/products/", "")),
      products[0].image.replace("uploads/", ""),
      products[0].image.startsWith("/")
        ? products[0].image.substring(1)
        : products[0].image,
      products[0].image.replace("uploads/", "public/uploads/"),
      products[0].image.split("/").pop(),
    ];
    console.log(
      "[FarmerRecentProducts] All possible image paths for debugging:",
      allPaths
    );

    // Validate image path
    setTimeout(() => {
      console.log(
        "[FarmerRecentProducts] Image validation result for sample product:",
        products[0].image ? "Valid" : "Invalid"
      );
    }, 100);
  }

  const renderItem = ({ item }) => {
    return (
      <TouchableOpacity
        style={styles.productCard}
        onPress={() =>
          navigation.navigate("ProductDetails", { productId: item.id })
        }
      >
        <View style={styles.imageContainer}>
          <ProductImage
            source={item.image}
            style={styles.productImage}
            productId={item.id}
          />
        </View>
        <View style={styles.productInfo}>
          <Text style={styles.productName}>{item.name}</Text>
          <Text style={styles.productPrice}>
            ₱{parseFloat(item.price).toFixed(2)}
          </Text>
        </View>
      </TouchableOpacity>
    );
  };

  if (!products || products.length === 0) {
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyText}>No recent products found</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.sectionTitle}>Recent Products</Text>
      <FlatList
        data={products}
        renderItem={renderItem}
        keyExtractor={(item) => item.id.toString()}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.listContainer}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginVertical: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 10,
    paddingHorizontal: 15,
  },
  listContainer: {
    paddingHorizontal: 10,
  },
  productCard: {
    width: 150,
    marginHorizontal: 5,
    borderRadius: 8,
    backgroundColor: "#fff",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
    overflow: "hidden",
  },
  imageContainer: {
    height: 120,
    width: "100%",
  },
  productImage: {
    width: "100%",
    height: "100%",
  },
  productInfo: {
    padding: 10,
  },
  productName: {
    fontSize: 14,
    fontWeight: "500",
    marginBottom: 5,
  },
  productPrice: {
    fontSize: 14,
    fontWeight: "bold",
    color: "#4CAF50",
  },
  emptyContainer: {
    padding: 20,
    alignItems: "center",
  },
  emptyText: {
    fontSize: 16,
    color: "#666",
  },
});

export default FarmerRecentProducts;
