import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  TouchableOpacity,
  Modal,
  ScrollView,
  TextInput,
  ActivityIndicator,
  Alert,
  Platform,
  ToastAndroid,
} from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import { COLORS } from "@/constants/Colors";
import { paymentStyles } from "./styles";
import { useAuth } from "@/contexts/AuthContext";
import { API_URLS } from "@/constants/Config";
import NetInfo from "@react-native-community/netinfo";
import PickupDateSelectionModal from "./PickupDateSelectionModal";
import { marketService } from "./MarketService";

interface PaymentScreenProps {
  visible: boolean;
  onClose: () => void;
  onComplete: (paymentMethod: string, paymentData?: any) => void;
  orderTotal: number;
  orderId: number | null;
}

const PaymentScreen: React.FC<PaymentScreenProps> = ({
  visible,
  onClose,
  onComplete,
  orderTotal,
  orderId,
}) => {
  const { user } = useAuth();
  const [selectedPaymentMethod, setSelectedPaymentMethod] =
    useState<string>("credit_card");
  const [isProcessing, setIsProcessing] = useState(false);
  const [cardNumber, setCardNumber] = useState("");
  const [cardName, setCardName] = useState("");
  const [expiryDate, setExpiryDate] = useState("");
  const [cvv, setCvv] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isConnected, setIsConnected] = useState(true);

  // State for showing pickup date selection modal
  const [showPickupModal, setShowPickupModal] = useState(false);
  const [paymentResult, setPaymentResult] = useState<any>(null);
  const [showSuccessScreen, setShowSuccessScreen] = useState(false);

  // Check network connectivity on component mount
  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state) => {
      setIsConnected(state.isConnected ?? false);
    });

    return () => {
      unsubscribe();
    };
  }, []);

  useEffect(() => {
    // Test connectivity to the API server when component mounts
    const testApiConnectivity = async () => {
      try {
        console.log(
          `[Payment] Testing connectivity to: ${API_URLS.CONNECTIVITY_TEST}`
        );

        // Create a more reliable abort controller with a longer timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
          console.log(
            "[Payment] API connectivity test timed out after 15 seconds"
          );
          controller.abort();
        }, 15000); // Extended to 15 seconds

        try {
          const response = await fetch(API_URLS.CONNECTIVITY_TEST, {
            method: "GET",
            headers: { "Content-Type": "application/json" },
            signal: controller.signal,
            cache: "no-store", // Prevent caching issues
          });

          clearTimeout(timeoutId);

          if (response.ok) {
            const data = await response.json();
            console.log(
              "[Payment] API connectivity test succeeded:",
              JSON.stringify(data)
            );
            setIsConnected(true);
          }
        } catch (fetchError) {
          clearTimeout(timeoutId);
          console.error(`[Payment] API connectivity fetch error:`, fetchError);
        }
      } catch (error) {
        console.error(`[Payment] API connectivity overall error:`, error);
      }
    };

    // Network connectivity monitoring
    const netInfoUnsubscribe = NetInfo.addEventListener((state) => {
      const isConnected = state.isConnected ?? false;
      console.log(
        `[Payment] Network connection changed - Connected: ${isConnected}`
      );
      setIsConnected(isConnected);

      if (isConnected) {
        setTimeout(testApiConnectivity, 1000);
      }
    });

    return () => {
      netInfoUnsubscribe();
    };
  }, []);

  // Add useEffect to log visibility changes and cleanup
  React.useEffect(() => {
    let isMounted = true;

    if (visible) {
      console.log("[PaymentScreen] Payment screen is now visible");
      console.log("[PaymentScreen] Order ID:", orderId);
      console.log("[PaymentScreen] Order total:", orderTotal);

      // Pre-select cash on pickup as the default payment method for better user experience
      setSelectedPaymentMethod("cash_on_pickup");

      // Reset state when component becomes visible
      if (isMounted) {
        setShowSuccessScreen(false);
        setError(null);
        setIsProcessing(false);
        setPaymentResult(null);
      }
    }

    return () => {
      // Mark component as unmounted to prevent state updates after unmounting
      isMounted = false;
    };
  }, [visible, orderId, orderTotal]);

  // Add useEffect to log user details when component mounts
  React.useEffect(() => {
    // Log user information to help diagnose user ID issues
    console.log(
      "[PaymentScreen] Current user context:",
      JSON.stringify(user, null, 2)
    );
  }, [user]);

  // Define interface for payment payload
  interface PaymentPayload {
    order_id: number;
    payment_method: string;
    user_id: any;
    amount: number;
    card_details?: {
      card_number: string;
      card_name: string;
      expiry_date: string;
      expiry_month: number;
      expiry_year: number;
      cvv: string;
    };
  }

  // Function to process payment
  const processPayment = async () => {
    try {
      // Ensure orderId is a number before proceeding
      if (!orderId) {
        throw new Error("Order ID is required for payment processing");
      }

      // Check for valid user ID with enhanced debugging
      if (!user) {
        console.warn(
          "[PaymentScreen] No user found in context. Processing as guest."
        );
      } else {
        console.log(
          "[PaymentScreen] User object structure:",
          Object.keys(user).join(", ")
        );
      }

      // Enhanced user ID extraction with better fallbacks and type conversion
      let userId = null;
      if (user) {
        // Try different possible ways the user_id might be stored
        if (user.user_id !== undefined) {
          userId = parseInt(user.user_id, 10) || user.user_id;
          console.log("[PaymentScreen] Using user.user_id:", userId);
        } else if (user.id !== undefined) {
          userId = parseInt(user.id, 10) || user.id;
          console.log("[PaymentScreen] Using user.id:", userId);
        } else {
          // Look for any key that might contain the user ID
          for (const key of Object.keys(user)) {
            if (key.toLowerCase().includes("id") && user[key]) {
              userId = parseInt(user[key], 10) || user[key];
              console.log(
                `[PaymentScreen] Found alternative ID in user.${key}:`,
                userId
              );
              break;
            }
          }
        }
      }

      console.log("[PaymentScreen] Final userId to be used:", userId);

      // Create payment payload
      const paymentPayload: PaymentPayload = {
        order_id: orderId,
        payment_method: selectedPaymentMethod,
        user_id: userId, // Use the extracted user ID
        amount: orderTotal,
      };

      // Add card details for credit card payments
      if (selectedPaymentMethod === "credit_card") {
        const [month, year] = expiryDate.split("/");
        paymentPayload.card_details = {
          card_number: cardNumber.replace(/\s/g, ""),
          card_name: cardName,
          expiry_date: expiryDate,
          expiry_month: parseInt(month || "0"),
          expiry_year: parseInt(year || "0"),
          cvv: cvv,
        };
      }

      console.log(
        `[PaymentScreen] Processing payment for order #${orderId}`,
        JSON.stringify({
          method: selectedPaymentMethod,
          amount: orderTotal,
          user_id: userId, // Log the user ID being sent
        })
      );

      // Check network connectivity first
      if (!isConnected) {
        throw new Error(
          "Internet connection required for payment processing. Please check your connection and try again."
        );
      }

      console.log(
        "[PaymentScreen] Calling marketService.processPayment with payload:",
        JSON.stringify({
          ...paymentPayload,
          user_id: userId, // Log the user ID
          card_details: paymentPayload.card_details ? "(hidden)" : undefined,
        })
      );

      const result = await marketService.processPayment(paymentPayload);
      console.log(
        "[PaymentScreen] Payment processing result:",
        JSON.stringify(result)
      );

      if (result && result.status === "success") {
        // Save the successful payment ID to track order payment status
        await AsyncStorage.setItem(
          `order_payment_${orderId}`,
          JSON.stringify({
            paymentId: result.data.payment_id,
            status: result.data.payment_status,
            timestamp: Date.now(),
            user_id: userId, // Store the user ID with the payment record
          })
        );

        return { success: true, data: result.data };
      } else {
        throw new Error(result?.message || "Payment failed");
      }
    } catch (err: any) {
      console.error("[PaymentScreen] Processing error:", err);
      return {
        success: false,
        error: err,
        errorMessage:
          err?.message || "An error occurred while processing your payment.",
      };
    }
  };

  // Show toast message (Android) or console log (iOS)
  const showMessage = (message: string) => {
    if (Platform.OS === "android") {
      ToastAndroid.show(message, ToastAndroid.SHORT);
    } else {
      console.log(message);
    }
  };

  // Add confirmation view for payment success
  const renderPaymentConfirmation = () => {
    if (!paymentResult) return null;

    // Format date for display
    const formattedDate = new Date().toLocaleDateString(undefined, {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });

    return (
      <View style={{ padding: 20, alignItems: "center" }}>
        <Ionicons
          name="checkmark-circle"
          size={60}
          color={COLORS.primary}
          style={{ marginBottom: 15 }}
        />
        <Text
          style={{
            fontSize: 22,
            fontWeight: "bold",
            color: COLORS.primary,
            marginBottom: 10,
          }}
        >
          Payment Successful!
        </Text>
        <Text
          style={{
            fontSize: 16,
            color: COLORS.dark,
            marginBottom: 20,
            textAlign: "center",
          }}
        >
          Your order has been confirmed and is being processed.
        </Text>

        <View
          style={{
            backgroundColor: "#f9f9f9",
            padding: 15,
            borderRadius: 10,
            width: "100%",
            marginBottom: 20,
          }}
        >
          <View
            style={{
              flexDirection: "row",
              justifyContent: "space-between",
              marginBottom: 10,
            }}
          >
            <Text style={{ color: COLORS.muted }}>Transaction ID:</Text>
            <Text style={{ fontWeight: "500" }}>
              {paymentResult.transaction_reference || "N/A"}
            </Text>
          </View>
          <View
            style={{
              flexDirection: "row",
              justifyContent: "space-between",
              marginBottom: 10,
            }}
          >
            <Text style={{ color: COLORS.muted }}>Order ID:</Text>
            <Text style={{ fontWeight: "500" }}>#{orderId}</Text>
          </View>
          <View
            style={{
              flexDirection: "row",
              justifyContent: "space-between",
              marginBottom: 10,
            }}
          >
            <Text style={{ color: COLORS.muted }}>Amount:</Text>
            <Text style={{ fontWeight: "500" }}>₱{orderTotal.toFixed(2)}</Text>
          </View>
          <View
            style={{
              flexDirection: "row",
              justifyContent: "space-between",
              marginBottom: 10,
            }}
          >
            <Text style={{ color: COLORS.muted }}>Payment Method:</Text>
            <Text style={{ fontWeight: "500" }}>
              {formatPaymentMethodName(selectedPaymentMethod)}
            </Text>
          </View>
          <View
            style={{ flexDirection: "row", justifyContent: "space-between" }}
          >
            <Text style={{ color: COLORS.muted }}>Date:</Text>
            <Text style={{ fontWeight: "500" }}>{formattedDate}</Text>
          </View>
        </View>

        <Text
          style={{
            fontSize: 14,
            color: COLORS.muted,
            textAlign: "center",
            marginBottom: 20,
          }}
        >
          Please select your pickup date on the next screen.
        </Text>
      </View>
    );
  };

  // Format payment method for display
  const formatPaymentMethodName = (method: string): string => {
    switch (method) {
      case "credit_card":
        return "Credit/Debit Card";
      case "bank_transfer":
        return "Bank Transfer";
      case "cash_on_pickup":
        return "Cash on Pickup";
      default:
        return method
          .replace(/_/g, " ")
          .replace(/\b\w/g, (l) => l.toUpperCase());
    }
  };

  const handlePayment = async () => {
    // Basic validation
    if (selectedPaymentMethod === "credit_card") {
      if (!cardNumber || !cardName || !expiryDate || !cvv) {
        Alert.alert("Missing Information", "Please fill in all card details.");
        return;
      }

      if (cardNumber.length < 16) {
        Alert.alert("Invalid Card", "Please enter a valid card number.");
        return;
      }

      // Validate expiry date format (MM/YY)
      if (!/^\d{1,2}\/\d{2}$/.test(expiryDate)) {
        Alert.alert("Invalid Format", "Expiry date should be in MM/YY format.");
        return;
      }

      // For credit card payments, check connectivity first
      if (!isConnected) {
        Alert.alert(
          "Cannot Process Credit Card",
          "Credit card payments require an internet connection. Please try another payment method or check your connection.",
          [{ text: "OK" }]
        );
        return;
      }
    }

    setIsProcessing(true);
    setError(null);

    try {
      // Process payment
      const result = await processPayment();

      if (result && result.success) {
        // Payment successful
        setIsProcessing(false);

        // Store payment result for pickup modal
        setPaymentResult(result.data);

        // Show success screen instead of immediately showing pickup modal
        setShowSuccessScreen(true);
      } else {
        setIsProcessing(false);

        // Show a more user-friendly error message
        Alert.alert(
          "Payment Failed",
          result?.errorMessage ||
            "There was an error processing your payment. Please try again or select a different payment method."
        );
      }
    } catch (err: any) {
      setIsProcessing(false);
      console.error("[Payment] Error in handlePayment:", err);

      Alert.alert(
        "Payment Error",
        err?.message || "There was an unexpected error processing your payment."
      );
    }
  };

  // Handle pickup date selection completion
  const handlePickupComplete = (pickupData: any) => {
    // Set data first before UI changes
    const finalData = {
      ...paymentResult,
      pickup_date: pickupData.pickup_date,
      pickup_id: pickupData.pickup_id,
      pickup_scheduled: true,
    };

    // Close modal with a slight delay to allow animations to complete
    setTimeout(() => {
      setShowPickupModal(false);

      // Show success message and prompt for feedback
      setTimeout(() => {
        showMessage("Pickup scheduled successfully");

        // Prompt user to leave feedback once order is ready
        Alert.alert(
          "Your Order is Confirmed!",
          "Would you like to be reminded to leave feedback after receiving your order?",
          [
            {
              text: "Yes, Remind Me",
              onPress: () => {
                // Set a flag in AsyncStorage to remind user about feedback
                AsyncStorage.setItem(`feedback_reminder_${orderId}`, "true");
                onComplete(selectedPaymentMethod, finalData);
              },
            },
            {
              text: "No Thanks",
              onPress: () => onComplete(selectedPaymentMethod, finalData),
              style: "cancel",
            },
          ]
        );
      }, 100);
    }, 100);
  };

  // Handle close - with safeguard to prevent unmounted component updates
  const handleClose = () => {
    if (!visible) return;
    onClose();
  };

  const renderPaymentMethodOption = (
    method: string,
    title: string,
    icon: any
  ) => (
    <TouchableOpacity
      style={[
        paymentStyles.paymentMethodOption,
        selectedPaymentMethod === method && paymentStyles.paymentMethodSelected,
      ]}
      onPress={() => setSelectedPaymentMethod(method)}
    >
      <Ionicons
        name={icon}
        size={24}
        color={selectedPaymentMethod === method ? COLORS.accent : COLORS.muted}
      />
      <Text
        style={[
          paymentStyles.paymentMethodText,
          selectedPaymentMethod === method &&
            paymentStyles.paymentMethodTextSelected,
        ]}
      >
        {title}
      </Text>
      {selectedPaymentMethod === method && (
        <Ionicons
          name="checkmark-circle"
          size={20}
          color={COLORS.accent}
          style={paymentStyles.checkIcon}
        />
      )}
    </TouchableOpacity>
  );

  return (
    <>
      <Modal
        animationType="slide"
        transparent={false}
        visible={visible}
        onRequestClose={onClose}
      >
        <SafeAreaView style={paymentStyles.paymentContainer}>
          {showSuccessScreen && paymentResult ? (
            // Show payment confirmation screen
            <>
              <View style={paymentStyles.paymentHeader}>
                <Text style={paymentStyles.paymentTitle}>Payment Complete</Text>
                <TouchableOpacity
                  style={paymentStyles.paymentCloseButton}
                  onPress={() =>
                    onComplete(selectedPaymentMethod, paymentResult)
                  }
                >
                  <Ionicons name="close" size={24} color={COLORS.dark} />
                </TouchableOpacity>
              </View>

              <ScrollView style={paymentStyles.paymentContent}>
                {renderPaymentConfirmation()}
              </ScrollView>

              <View style={paymentStyles.paymentActions}>
                <TouchableOpacity
                  style={paymentStyles.paymentButton}
                  onPress={() => setShowPickupModal(true)}
                >
                  <Text style={paymentStyles.paymentButtonText}>
                    Schedule Pickup
                  </Text>
                  <Ionicons
                    name="arrow-forward"
                    size={20}
                    color={COLORS.light}
                  />
                </TouchableOpacity>
              </View>
            </>
          ) : (
            // Show standard payment form
            <>
              <View style={paymentStyles.paymentHeader}>
                <Text style={paymentStyles.paymentTitle}>Payment</Text>
                <TouchableOpacity
                  style={paymentStyles.paymentCloseButton}
                  onPress={onClose}
                >
                  <Ionicons name="close" size={24} color={COLORS.dark} />
                </TouchableOpacity>
              </View>

              <ScrollView style={paymentStyles.paymentContent}>
                {/* Order Summary Section */}
                <View style={paymentStyles.orderSummarySection}>
                  <Text style={paymentStyles.sectionTitle}>Order Summary</Text>
                  <View style={paymentStyles.orderInfoRow}>
                    <Text style={paymentStyles.orderInfoLabel}>Order ID:</Text>
                    <Text style={paymentStyles.orderInfoValue}>
                      {orderId || "Pending"}
                    </Text>
                  </View>
                  <View style={paymentStyles.orderInfoRow}>
                    <Text style={paymentStyles.orderInfoLabel}>
                      Total Amount:
                    </Text>
                    <Text style={paymentStyles.orderInfoValue}>
                      ₱{orderTotal.toFixed(2)}
                    </Text>
                  </View>
                </View>

                <View style={paymentStyles.paymentMethodSection}>
                  <Text style={paymentStyles.sectionTitle}>Payment Method</Text>
                  <View style={paymentStyles.paymentMethodOptions}>
                    {renderPaymentMethodOption(
                      "credit_card",
                      "Credit/Debit Card",
                      "card-outline"
                    )}
                    {renderPaymentMethodOption(
                      "bank_transfer",
                      "Bank Transfer",
                      "business-outline"
                    )}
                    {renderPaymentMethodOption(
                      "cash_on_pickup",
                      "Cash on Pickup",
                      "cash-outline"
                    )}
                  </View>
                </View>

                {selectedPaymentMethod === "credit_card" && (
                  <View style={paymentStyles.cardDetailsSection}>
                    <Text style={paymentStyles.sectionTitle}>Card Details</Text>
                    <View style={paymentStyles.inputContainer}>
                      <Text style={paymentStyles.inputLabel}>Card Number</Text>
                      <TextInput
                        style={paymentStyles.input}
                        placeholder="1234 5678 9012 3456"
                        keyboardType="numeric"
                        maxLength={16}
                        value={cardNumber}
                        onChangeText={setCardNumber}
                      />
                    </View>

                    <View style={paymentStyles.inputContainer}>
                      <Text style={paymentStyles.inputLabel}>
                        Cardholder Name
                      </Text>
                      <TextInput
                        style={paymentStyles.input}
                        placeholder="John Doe"
                        value={cardName}
                        onChangeText={setCardName}
                      />
                    </View>

                    <View style={paymentStyles.inputRow}>
                      <View
                        style={[
                          paymentStyles.inputContainer,
                          { flex: 1, marginRight: 10 },
                        ]}
                      >
                        <Text style={paymentStyles.inputLabel}>
                          Expiry Date
                        </Text>
                        <TextInput
                          style={paymentStyles.input}
                          placeholder="MM/YY"
                          maxLength={5}
                          value={expiryDate}
                          onChangeText={setExpiryDate}
                        />
                      </View>
                      <View style={[paymentStyles.inputContainer, { flex: 1 }]}>
                        <Text style={paymentStyles.inputLabel}>CVV</Text>
                        <TextInput
                          style={paymentStyles.input}
                          placeholder="123"
                          keyboardType="numeric"
                          maxLength={3}
                          secureTextEntry
                          value={cvv}
                          onChangeText={setCvv}
                        />
                      </View>
                    </View>
                  </View>
                )}

                {selectedPaymentMethod === "bank_transfer" && (
                  <View style={paymentStyles.bankDetailsSection}>
                    <Text style={paymentStyles.sectionTitle}>
                      Bank Transfer Details
                    </Text>
                    <Text style={paymentStyles.bankInstructions}>
                      Please transfer the exact amount to the following account:
                    </Text>
                    <View style={paymentStyles.bankInfo}>
                      <Text style={paymentStyles.bankInfoItem}>
                        Bank: Agricultural Bank of the Philippines
                      </Text>
                      <Text style={paymentStyles.bankInfoItem}>
                        Account Name: Municipal Agriculture Office
                      </Text>
                      <Text style={paymentStyles.bankInfoItem}>
                        Account Number: 1234-5678-9012
                      </Text>
                      <Text style={paymentStyles.bankInfoItem}>
                        Reference: Order #{orderId || "Pending"}
                      </Text>
                    </View>
                    <Text style={paymentStyles.bankNote}>
                      Note: Please take a screenshot of this information. Your
                      order will be processed once payment is confirmed.
                    </Text>
                  </View>
                )}

                {selectedPaymentMethod === "cash_on_pickup" && (
                  <View style={paymentStyles.cashPickupSection}>
                    <Text style={paymentStyles.sectionTitle}>
                      Cash on Pickup
                    </Text>
                    <View style={paymentStyles.cashPickupInfo}>
                      <Ionicons
                        name="information-circle-outline"
                        size={24}
                        color={COLORS.accent}
                      />
                      <Text style={paymentStyles.cashPickupText}>
                        You will pay ₱{orderTotal.toFixed(2)} when you pick up
                        your order at the Municipal Agriculture Office.
                      </Text>
                    </View>
                    <Text style={paymentStyles.cashPickupNote}>
                      Please bring the exact amount as change may not always be
                      available.
                    </Text>
                  </View>
                )}

                {error && (
                  <View style={paymentStyles.errorContainer}>
                    <Text style={paymentStyles.errorText}>{error}</Text>
                  </View>
                )}
              </ScrollView>

              <View style={paymentStyles.paymentActions}>
                <TouchableOpacity
                  style={[
                    paymentStyles.paymentButton,
                    isProcessing && paymentStyles.paymentButtonDisabled,
                  ]}
                  disabled={isProcessing}
                  onPress={handlePayment}
                >
                  {isProcessing ? (
                    <ActivityIndicator size="small" color={COLORS.light} />
                  ) : (
                    <>
                      <Text style={paymentStyles.paymentButtonText}>
                        {selectedPaymentMethod === "cash_on_pickup"
                          ? "Confirm Order"
                          : "Complete Payment"}
                      </Text>
                      <Ionicons
                        name="checkmark-circle"
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

      {/* Pickup Date Selection Modal */}
      <PickupDateSelectionModal
        visible={showPickupModal}
        onClose={() => {
          setShowPickupModal(false);
          // Even if they close the pickup modal, we still want to complete the payment flow
          onComplete(selectedPaymentMethod, paymentResult);
        }}
        onComplete={handlePickupComplete}
        orderId={orderId}
        paymentData={paymentResult}
      />
    </>
  );
};

export default PaymentScreen;
