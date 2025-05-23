import React, { useState, useEffect } from "react";
import {
  View,
  Image,
  TouchableOpacity,
  Text,
  Alert,
  Modal,
  Animated,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { Product } from "./types";
import { productStyles } from "./styles";
import FeedbackList from "./FeedbackList";
import { useAuth } from "@/contexts/AuthContext";
import { getImageUrl } from "@/constants/Config";
import ProductImage from "@/components/ui/ProductImage";

interface ProductItemProps {
  item: Product;
  onAddToCart: (product: Product) => void;
}

const ProductItem = React.memo(({ item, onAddToCart }: ProductItemProps) => {
  const [imageError, setImageError] = useState(false);
  const [showProductDetail, setShowProductDetail] = useState(false);
  const [activeTab, setActiveTab] = useState<"details" | "feedback">("details");
  const [refreshFeedback, setRefreshFeedback] = useState(0);
  const { user } = useAuth();

  // Add state for button animation and feedback
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [addedToCart, setAddedToCart] = useState(false);
  const buttonScale = useState(new Animated.Value(1))[0];
  // Enhanced image path debugging when the component mounts
  useEffect(() => {
    // Check if item has image_url or image property
    const imagePath = item.image_url || item.image;

    if (imagePath) {
      console.log(
        `[Market] Product ${item.id} original image path: ${imagePath}`
      );
      try {
        // Add additional check to ensure path is properly formatted
        // If path doesn't start with http:// or https:// and doesn't start with uploads/
        // we'll try to fix it by adding uploads/products/ prefix
        let pathToProcess = imagePath;
        if (
          !pathToProcess.startsWith("http") &&
          !pathToProcess.startsWith("uploads/")
        ) {
          console.log(
            `[Market] Path doesn't have proper prefix, adding uploads/products/ prefix`
          );
          pathToProcess = `uploads/products/${pathToProcess}`;
        }

        const fullUrl = getImageUrl(pathToProcess);
        console.log(
          `[Market] Product ${item.id} transformed image URL: ${fullUrl}`
        );
      } catch (error) {
        console.error(
          `[Market] Error getting image URL for product ${item.id}:`,
          error
        );
        setImageError(true);
      }
    } else {
      console.log(`[Market] No image available for product ${item.id}`);
      setImageError(true);
    }
  }, [item]);

  // Function to handle adding to cart with visual feedback
  const handleAddToCart = (e: any) => {
    e.stopPropagation(); // Prevent the card touch event from triggering

    // Don't do anything if already processing
    if (isAddingToCart) return;

    if (item.quantity_available > 0) {
      setIsAddingToCart(true);

      // Animate button press
      Animated.sequence([
        Animated.timing(buttonScale, {
          toValue: 0.8,
          duration: 100,
          useNativeDriver: true,
        }),
        Animated.timing(buttonScale, {
          toValue: 1,
          duration: 100,
          useNativeDriver: true,
        }),
      ]).start();

      // Show temporary visual feedback
      setAddedToCart(true);

      // Call the onAddToCart prop function to actually add the item
      onAddToCart(item);

      // Reset states after a delay
      setTimeout(() => {
        setIsAddingToCart(false);
        setAddedToCart(false);
      }, 1000);
    } else {
      Alert.alert(
        "Out of Stock",
        "Sorry, this item is currently out of stock."
      );
    }
  };
  // Get the correct image path from either image_url or image property
  // with enhanced path normalization
  const getImagePath = () => {
    const imagePath = item.image_url || item.image || null;

    // If no image path at all, return default image
    if (!imagePath || imagePath === "http://192.168.1.12/capstone/public/") {
      return "uploads/products/product-default.png";
    }

    let normalizedPath = imagePath;

    // Remove server URL prefix if it exists (handle multiple possible prefixes)
    const serverPrefixes = [
      "http://192.168.1.12/capstone/public/",
      "http://192.168.1.12/capstone/",
      "http://192.168.1.12/",
    ];
    for (const prefix of serverPrefixes) {
      if (normalizedPath.startsWith(prefix)) {
        normalizedPath = normalizedPath.replace(prefix, "");
        break;
      }
    }

    // Remove any 'public/' prefix if present
    if (normalizedPath.startsWith("public/")) {
      normalizedPath = normalizedPath.replace("public/", "");
    }

    // If path doesn't include uploads/products/, extract filename and prepend the correct path
    if (!normalizedPath.includes("uploads/products/")) {
      const filename = normalizedPath.split("/").pop(); // Get the filename
      if (filename) {
        normalizedPath = `uploads/products/${filename}`;
      } else {
        return "uploads/products/product-default.png";
      }
    } else {
      // Extract everything after uploads/products/ to ensure consistent path
      const parts = normalizedPath.split("uploads/products/");
      normalizedPath = `uploads/products/${parts[parts.length - 1]}`;
    }

    // Clean up any double slashes and trim
    normalizedPath = normalizedPath.replace(/\/+/g, "/").trim();

    return normalizedPath;
  };

  return (
    <>
      <TouchableOpacity
        style={productStyles.productCard}
        onPress={() => setShowProductDetail(true)}
      >
        <View style={productStyles.productImageContainer}>
          {!imageError ? (
            <ProductImage
              imagePath={getImagePath()}
              style={productStyles.productImage}
              productId={item.id}
            />
          ) : (
            <View style={productStyles.placeholderImage}>
              <Ionicons name="leaf-outline" size={40} color={COLORS.light} />
            </View>
          )}
          {/* Category badge positioned on top of image */}
          <View style={productStyles.categoryBadge}>
            <Text style={productStyles.categoryBadgeText}>
              {item.category || "Uncategorized"}
            </Text>
          </View>
        </View>

        <View style={productStyles.productDetails}>
          <ThemedText
            style={productStyles.productName}
            numberOfLines={1}
            ellipsizeMode="tail"
          >
            {item.name}
          </ThemedText>
          <ThemedText
            style={productStyles.productFarm}
            numberOfLines={1}
            ellipsizeMode="tail"
          >
            {item.farm_name || "Unknown Farm"}
          </ThemedText>
          <View style={productStyles.productPriceRow}>
            <ThemedText style={productStyles.productPrice}>
              ₱{item.price.toFixed(2)}/{item.unit}
            </ThemedText>
            {item.quantity_available > 0 ? (
              <Animated.View style={{ transform: [{ scale: buttonScale }] }}>
                <TouchableOpacity
                  style={[
                    productStyles.addButton,
                    addedToCart && { backgroundColor: "#4CAF50" }, // Green feedback when added
                  ]}
                  onPress={handleAddToCart}
                  disabled={isAddingToCart}
                >
                  <Ionicons
                    name={addedToCart ? "checkmark" : "cart"}
                    size={14}
                    color={COLORS.light}
                  />
                  <Text style={productStyles.addButtonText}>
                    {addedToCart ? "Added" : "Add"}
                  </Text>
                </TouchableOpacity>
              </Animated.View>
            ) : (
              <View
                style={[
                  productStyles.stockBadge,
                  productStyles.outOfStockBadge,
                ]}
              >
                <Text
                  style={[
                    productStyles.stockBadgeText,
                    productStyles.outOfStockText,
                  ]}
                >
                  Out of Stock
                </Text>
              </View>
            )}
          </View>
        </View>
      </TouchableOpacity>

      {/* Product Detail Modal with Feedback Feature */}
      <Modal
        visible={showProductDetail}
        animationType="slide"
        transparent={false}
        onRequestClose={() => setShowProductDetail(false)}
      >
        <View style={productStyles.modalContainer}>
          <View style={productStyles.modalHeader}>
            <TouchableOpacity
              onPress={() => setShowProductDetail(false)}
              style={productStyles.closeButton}
            >
              <Ionicons name="close" size={24} color={COLORS.dark} />
            </TouchableOpacity>
            <Text style={productStyles.modalTitle}>{item.name}</Text>
          </View>

          <View style={productStyles.modalTabs}>
            <TouchableOpacity
              style={[
                productStyles.modalTab,
                activeTab === "details" && productStyles.activeTab,
              ]}
              onPress={() => setActiveTab("details")}
            >
              <Ionicons
                name="information-circle-outline"
                size={18}
                color={activeTab === "details" ? COLORS.accent : COLORS.dark}
                style={productStyles.tabIcon}
              />
              <Text
                style={[
                  productStyles.tabText,
                  activeTab === "details" && productStyles.activeTabText,
                ]}
              >
                Details
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                productStyles.modalTab,
                activeTab === "feedback" && productStyles.activeTab,
              ]}
              onPress={() => setActiveTab("feedback")}
            >
              <Ionicons
                name="star-outline"
                size={18}
                color={activeTab === "feedback" ? COLORS.accent : COLORS.dark}
                style={productStyles.tabIcon}
              />
              <Text
                style={[
                  productStyles.tabText,
                  activeTab === "feedback" && productStyles.activeTabText,
                ]}
              >
                Customer Reviews
              </Text>
            </TouchableOpacity>
          </View>

          <View style={productStyles.modalContent}>
            {activeTab === "details" ? (
              <View>
                <View style={productStyles.productImageContainerLarge}>
                  {!imageError ? (
                    <ProductImage
                      imagePath={getImagePath()}
                      style={productStyles.productImageLarge}
                      productId={item.id}
                    />
                  ) : (
                    <View style={productStyles.placeholderImageLarge}>
                      <Ionicons
                        name="leaf-outline"
                        size={60}
                        color={COLORS.light}
                      />
                    </View>
                  )}
                </View>

                <View style={productStyles.detailRow}>
                  <Text style={productStyles.detailLabel}>Price:</Text>
                  <Text style={productStyles.detailValue}>
                    ₱{item.price.toFixed(2)} per {item.unit}
                  </Text>
                </View>

                <View style={productStyles.detailRow}>
                  <Text style={productStyles.detailLabel}>Available:</Text>
                  <Text style={productStyles.detailValue}>
                    {item.quantity_available} {item.unit}(s)
                  </Text>
                </View>

                <View style={productStyles.detailRow}>
                  <Text style={productStyles.detailLabel}>Farm:</Text>
                  <Text style={productStyles.detailValue}>
                    {item.farm_name || "Unknown"}
                  </Text>
                </View>

                <View style={productStyles.detailRow}>
                  <Text style={productStyles.detailLabel}>Farmer:</Text>
                  <Text style={productStyles.detailValue}>
                    {item.farmer || "Unknown"}
                  </Text>
                </View>

                <View style={productStyles.detailRow}>
                  <Text style={productStyles.detailLabel}>Contact:</Text>
                  <Text style={productStyles.detailValue}>
                    {item.contact || "No contact provided"}
                  </Text>
                </View>

                <View style={productStyles.descriptionContainer}>
                  <Text style={productStyles.descriptionLabel}>
                    Description:
                  </Text>
                  <Text style={productStyles.descriptionText}>
                    {item.description || "No description available"}
                  </Text>
                </View>

                {item.quantity_available > 0 && (
                  <Animated.View
                    style={{ transform: [{ scale: buttonScale }] }}
                  >
                    <TouchableOpacity
                      style={productStyles.addToCartButton}
                      onPress={() => {
                        setIsAddingToCart(true);

                        // Animate button press
                        Animated.sequence([
                          Animated.timing(buttonScale, {
                            toValue: 0.95,
                            duration: 100,
                            useNativeDriver: true,
                          }),
                          Animated.timing(buttonScale, {
                            toValue: 1,
                            duration: 100,
                            useNativeDriver: true,
                          }),
                        ]).start();

                        // Add to cart with a brief delay for animation
                        setTimeout(() => {
                          onAddToCart(item);
                          Alert.alert(
                            "Added to Cart",
                            `${item.name} has been added to your cart.`
                          );
                          setIsAddingToCart(false);
                        }, 150);
                      }}
                      disabled={isAddingToCart}
                    >
                      <Ionicons
                        name="cart"
                        size={20}
                        color={COLORS.light}
                        style={{ marginRight: 10 }}
                      />
                      <Text style={productStyles.addToCartButtonText}>
                        {isAddingToCart ? "Adding..." : "Add to Cart"}
                      </Text>
                    </TouchableOpacity>
                  </Animated.View>
                )}
              </View>
            ) : (
              <View style={productStyles.feedbackContainer}>
                <View style={productStyles.feedbackHeader}>
                  <Text style={productStyles.feedbackHeaderTitle}>
                    Customer Reviews
                  </Text>
                  <Text style={productStyles.feedbackHeaderSubtitle}>
                    Reviews from customers who purchased this product
                  </Text>
                </View>
                <View style={productStyles.feedbackListContainer}>
                  <FeedbackList
                    productId={item.id}
                    refreshTrigger={refreshFeedback}
                    showResponseForm={user?.role_id === 2}
                  />
                </View>
                <View style={productStyles.feedbackFooterNote}>
                  <Ionicons
                    name="information-circle-outline"
                    size={16}
                    color={COLORS.muted}
                  />
                  <Text style={productStyles.feedbackFooterText}>
                    Reviews are available from customers with completed orders
                  </Text>
                </View>
              </View>
            )}
          </View>
        </View>
      </Modal>
    </>
  );
});

export default ProductItem;
