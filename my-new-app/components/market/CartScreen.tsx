import React, { useContext } from "react";
import {
  View,
  Text,
  TouchableOpacity,
  Modal,
  ScrollView,
  Alert,
  ActivityIndicator,
  Image,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import { COLORS } from "@/constants/Colors";
import { CartContext } from "./CartContext";
import { CartItem } from "./types";
import { cartStyles } from "./styles";

interface CartScreenProps {
  visible: boolean;
  onClose: () => void;
  onCheckout: () => void;
  isProcessing?: boolean;
}

const CartScreen: React.FC<CartScreenProps> = ({
  visible,
  onClose,
  onCheckout,
  isProcessing = false,
}) => {
  const { cart, updateQuantity, removeFromCart, totalPrice } =
    useContext(CartContext);

  // Handle checkout button press
  const handleCheckoutPress = () => {
    if (cart.length === 0) {
      Alert.alert(
        "Empty Cart",
        "Add some products to your cart before checking out."
      );
      return;
    }

    // Disable close button and call onCheckout
    console.log("[CartScreen] Proceeding with checkout");
    onCheckout();
  };

  const renderCartItem = (item: CartItem) => (
    <View style={cartStyles.cartItem} key={item.product.id}>
      {/* Add product image */}
      <View style={cartStyles.cartItemImageContainer}>
        {item.product.image_url ? (
          <Image
            source={{ uri: item.product.image_url }}
            style={cartStyles.cartItemImage}
            resizeMode="cover"
            onError={() => {
              console.log(
                `[Cart] Failed to load image: ${item.product.image_url}`
              );
            }}
          />
        ) : (
          <View style={cartStyles.cartItemPlaceholder}>
            <Ionicons name="leaf-outline" size={24} color={COLORS.primary} />
          </View>
        )}
      </View>

      <View style={cartStyles.cartItemInfo}>
        <Text style={cartStyles.cartItemName}>{item.product.name}</Text>
        <Text style={cartStyles.cartItemPrice}>
          ₱{item.product.price.toFixed(2)} / {item.product.unit}
        </Text>
        <Text style={cartStyles.cartItemFarm}>
          {item.product.farm_name || "Unknown Farm"}
        </Text>
      </View>

      <View style={cartStyles.cartItemActions}>
        <View style={cartStyles.quantityContainer}>
          <TouchableOpacity
            style={cartStyles.quantityButton}
            onPress={() => updateQuantity(item.product.id, item.quantity - 1)}
            disabled={isProcessing}
          >
            <Ionicons name="remove" size={16} color={COLORS.dark} />
          </TouchableOpacity>

          <Text style={cartStyles.quantityText}>{item.quantity}</Text>

          <TouchableOpacity
            style={cartStyles.quantityButton}
            onPress={() => {
              // Ensure we don't exceed available stock
              if (item.quantity < item.product.quantity_available) {
                updateQuantity(item.product.id, item.quantity + 1);
              } else {
                Alert.alert(
                  "Maximum Stock Reached",
                  `Sorry, only ${item.product.quantity_available} ${item.product.unit}(s) available.`
                );
              }
            }}
            disabled={isProcessing}
          >
            <Ionicons name="add" size={16} color={COLORS.dark} />
          </TouchableOpacity>
        </View>

        <Text style={cartStyles.cartItemTotal}>
          ₱{(item.product.price * item.quantity).toFixed(2)}
        </Text>

        <TouchableOpacity
          style={cartStyles.removeButton}
          onPress={() => removeFromCart(item.product.id)}
          disabled={isProcessing}
        >
          <Ionicons name="trash-outline" size={20} color="#e74c3c" />
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <Modal
      animationType="slide"
      transparent={false}
      visible={visible}
      onRequestClose={isProcessing ? () => {} : onClose}
    >
      <SafeAreaView style={cartStyles.cartContainer}>
        <View style={cartStyles.cartHeader}>
          <Text style={cartStyles.cartTitle}>Your Cart</Text>
          <TouchableOpacity
            style={cartStyles.cartCloseButton}
            onPress={onClose}
            disabled={isProcessing}
          >
            <Ionicons
              name="close"
              size={24}
              color={isProcessing ? COLORS.muted : COLORS.dark}
            />
          </TouchableOpacity>
        </View>

        {cart.length === 0 ? (
          <View style={cartStyles.emptyCartContainer}>
            <Ionicons name="cart-outline" size={80} color={COLORS.muted} />
            <Text style={cartStyles.emptyCartText}>Your cart is empty</Text>
            <TouchableOpacity
              style={cartStyles.continueShoppingButton}
              onPress={onClose}
              disabled={isProcessing}
            >
              <Text style={cartStyles.continueShoppingText}>
                Continue Shopping
              </Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            <ScrollView
              style={cartStyles.cartList}
              scrollEnabled={!isProcessing}
            >
              {cart.map(renderCartItem)}
            </ScrollView>

            <View style={cartStyles.cartSummary}>
              <View style={cartStyles.cartTotalRow}>
                <Text style={cartStyles.cartTotalLabel}>Total:</Text>
                <Text style={cartStyles.cartTotalAmount}>
                  ₱{totalPrice.toFixed(2)}
                </Text>
              </View>

              <TouchableOpacity
                style={[
                  cartStyles.checkoutButton,
                  isProcessing && { opacity: 0.7 },
                ]}
                onPress={handleCheckoutPress}
                disabled={isProcessing}
              >
                {isProcessing ? (
                  <>
                    <ActivityIndicator size="small" color={COLORS.light} />
                    <Text
                      style={[
                        cartStyles.checkoutButtonText,
                        { marginLeft: 10 },
                      ]}
                    >
                      Processing...
                    </Text>
                  </>
                ) : (
                  <>
                    <Text style={cartStyles.checkoutButtonText}>
                      Proceed to Checkout
                    </Text>
                    <Ionicons
                      name="arrow-forward"
                      size={20}
                      color={COLORS.light}
                    />
                  </>
                )}
              </TouchableOpacity>
            </View>
          </>
        )}
      </SafeAreaView>
    </Modal>
  );
};

export default CartScreen;
