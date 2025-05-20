import React, { useState, useEffect } from "react";
import {
  View,
  Modal,
  TouchableOpacity,
  FlatList,
  ActivityIndicator,
  Alert,
  TextInput,
  Platform,
  Text,
  ScrollView,
  RefreshControl,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { pickupService } from "./PickupService";
import { pickupStyles } from "./styles";
import { Calendar } from "react-native-calendars";
import { TimePickerModal } from "react-native-paper-dates";
import FeedbackService from "@/services/FeedbackService";
import FeedbackForm from "./FeedbackForm";

// Calendar-based date picker component
interface CalendarDatePickerProps {
  value: Date;
  onChange: (date: Date) => void;
  onClose?: () => void;
}

const CalendarDatePicker = ({
  value,
  onChange,
  onClose,
}: CalendarDatePickerProps) => {
  const [selectedDate, setSelectedDate] = useState(value);
  const [showTimePicker, setShowTimePicker] = useState(false);

  // Format current date for marking in calendar
  const formattedDate = value.toISOString().split("T")[0];

  // Get current date for min date
  const today = new Date();
  const minDate = today.toISOString().split("T")[0];

  // Create marked dates object for the calendar
  const markedDates = {
    [formattedDate]: {
      selected: true,
      selectedColor: COLORS.primary,
    },
  };

  const handleDayPress = (day: any) => {
    const newDate = new Date(day.dateString);
    // Preserve the current time
    newDate.setHours(selectedDate.getHours());
    newDate.setMinutes(selectedDate.getMinutes());

    setSelectedDate(newDate);
    onChange(newDate);

    // Show time picker after selecting a date
    setShowTimePicker(true);
  };

  const onTimeConfirm = ({
    hours,
    minutes,
  }: {
    hours: number;
    minutes: number;
  }) => {
    const newDate = new Date(selectedDate);
    newDate.setHours(hours);
    newDate.setMinutes(minutes);

    setSelectedDate(newDate);
    onChange(newDate);
    setShowTimePicker(false);
  };

  return (
    <View style={pickupStyles.calendarPickerContainer}>
      <Calendar
        current={formattedDate}
        minDate={minDate}
        onDayPress={handleDayPress}
        markedDates={markedDates}
        theme={{
          backgroundColor: "#ffffff",
          calendarBackground: "#ffffff",
          textSectionTitleColor: COLORS.primary,
          selectedDayBackgroundColor: COLORS.primary,
          selectedDayTextColor: COLORS.light,
          todayTextColor: COLORS.accent,
          dayTextColor: COLORS.dark,
          textDisabledColor: "#d9e1e8",
          dotColor: COLORS.primary,
          selectedDotColor: "#ffffff",
          arrowColor: COLORS.primary,
          monthTextColor: COLORS.primary,
          indicatorColor: COLORS.primary,
          textDayFontWeight: "300",
          textMonthFontWeight: "bold",
          textDayHeaderFontWeight: "bold",
          textDayFontSize: 16,
          textMonthFontSize: 16,
          textDayHeaderFontSize: 14,
        }}
      />

      <View style={pickupStyles.timeSelectionContainer}>
        <Text style={pickupStyles.timeLabel}>Selected Time:</Text>
        <TouchableOpacity
          style={pickupStyles.timeButton}
          onPress={() => setShowTimePicker(true)}
        >
          <Ionicons name="time-outline" size={20} color={COLORS.primary} />
          <Text style={pickupStyles.timeButtonText}>
            {selectedDate.toLocaleTimeString([], {
              hour: "2-digit",
              minute: "2-digit",
            })}
          </Text>
        </TouchableOpacity>
      </View>

      <TimePickerModal
        visible={showTimePicker}
        onDismiss={() => setShowTimePicker(false)}
        onConfirm={onTimeConfirm}
        hours={selectedDate.getHours()}
        minutes={selectedDate.getMinutes()}
        use24HourClock={false}
      />
    </View>
  );
};

// Pickup screen component
interface OrdersScreenProps {
  visible: boolean;
  onClose: () => void;
  standalone?: boolean;
}

interface OrderItem {
  order_item_id: number;
  product_id?: number;
  product_name: string;
  quantity: number;
  price: number;
  unit_type?: string;
  subtotal: number;
}

interface Pickup {
  pickup_id: number;
  order_id: number;
  pickup_status: string;
  pickup_date: string;
  pickup_location: string;
  pickup_notes?: string;
  contact_person?: string;
  order_items?: OrderItem[];
  order_total?: number;
  item_count?: number;
  payment_method?: string; // Add payment method property
}

// Interface for eligible orders for feedback
interface EligibleOrder {
  order_id: number;
  order_date: string;
  order_reference: string;
  product_id: number;
  product_name: string;
}

const OrdersScreen: React.FC<OrdersScreenProps> = ({ visible, onClose }) => {
  const { user } = useAuth();
  const [pickups, setPickups] = useState<Pickup[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedPickup, setSelectedPickup] = useState<Pickup | null>(null);
  const [isRescheduling, setIsRescheduling] = useState(false);
  const [newPickupDate, setNewPickupDate] = useState(new Date());
  const [newPickupNotes, setNewPickupNotes] = useState("");
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [expandedPickups, setExpandedPickups] = useState<number[]>([]);

  // Feedback section states
  const [activeTab, setActiveTab] = useState<"pickups" | "feedback">("pickups");
  const [eligibleOrders, setEligibleOrders] = useState<EligibleOrder[]>([]);
  const [feedbackLoading, setFeedbackLoading] = useState(false);
  const [feedbackError, setFeedbackError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<number | null>(null);

  // Fetch pickups when modal becomes visible
  useEffect(() => {
    let isMounted = true;

    const loadData = async () => {
      if (visible && user) {
        try {
          await fetchPickups();
          if (activeTab === "feedback" && isMounted) {
            await fetchEligibleOrders();
          }
        } catch (error) {
          console.error("[Orders] Error loading data:", error);
        }
      }
    };

    loadData();

    return () => {
      // Mark component as unmounted to prevent state updates
      isMounted = false;
    };
  }, [visible, user, activeTab]);

  const fetchPickups = async () => {
    if (!user || !user.user_id) {
      console.log("[Orders] No user ID available, cannot fetch pickups");
      Alert.alert(
        "Error",
        "User information not available. Please log in again."
      );
      return;
    }

    setLoading(true);
    try {
      console.log(`[Orders] Fetching pickups for user ID: ${user.user_id}`);
      const response = await pickupService.getUserPickups(user.user_id);
      console.log(`[Orders] Response status: ${response.status}`);

      if (response.status === "success" && response.data) {
        console.log(
          `[Orders] Successfully loaded ${response.data.length} pickups`
        );

        // Debug the structure of the first pickup if available
        if (response.data.length > 0) {
          console.log(
            `[Orders] First pickup details: ${JSON.stringify(response.data[0])}`
          );
          console.log(
            `[Orders] Order items available: ${
              response.data[0].order_items
                ? response.data[0].order_items.length
                : "none"
            }`
          );
        }

        // Process the data to ensure order_items is available for each pickup
        const processedPickups = response.data.map((pickup: Pickup) => {
          // Make sure order_items is at least an empty array if not provided
          if (!pickup.order_items) {
            pickup.order_items = [];
            console.log(
              `[Orders] Created empty order_items array for pickup #${pickup.pickup_id}`
            );
          } else {
            console.log(
              `[Orders] Pickup #${pickup.pickup_id} has ${pickup.order_items.length} items`
            );
          }
          return pickup;
        });

        // Set all pickups as expanded by default so order items are visible
        const allPickupIds = processedPickups.map(
          (pickup: Pickup) => pickup.pickup_id
        );
        setExpandedPickups(allPickupIds);

        setPickups(processedPickups);
      } else if (
        response.status === "success" &&
        (!response.data || response.data.length === 0)
      ) {
        console.log(`[Orders] No pickups found for user ID: ${user.user_id}`);
        setPickups([]);
      } else {
        console.error(`[Orders] Error response: ${JSON.stringify(response)}`);
        Alert.alert("Error", response.message || "Failed to load pickups");
      }
    } catch (error: any) {
      console.error(
        `[Orders] Error fetching pickups: ${error.message || error}`
      );

      // Don't show error alert for empty pickups
      if (error.message && error.message.includes("No pickups found")) {
        console.log("[Orders] No pickups found, setting empty array");
        setPickups([]);
      } else {
        Alert.alert(
          "Network Issue",
          "Unable to fetch pickups. Please check your connection and try again."
        );
      }
    } finally {
      setLoading(false);
      setRefreshing(false); // Reset refreshing state when done
    }
  };

  // Feedback functionality
  const fetchEligibleOrders = async () => {
    if (!user?.user_id) {
      setFeedbackError(
        "You must be logged in to view eligible orders for feedback"
      );
      setFeedbackLoading(false);
      return;
    }

    setFeedbackLoading(true);
    setFeedbackError(null);

    try {
      const response = await FeedbackService.getOrdersEligibleForFeedback(
        user.user_id
      );

      if (response.success) {
        setEligibleOrders(response.orders || []);
      } else {
        console.warn("Failed to fetch eligible orders:", response.message);
        setFeedbackError(response.message || "Failed to load eligible orders");
        setEligibleOrders([]);
      }
    } catch (error) {
      console.error("Error fetching eligible orders:", error);
      setFeedbackError("Network error while loading eligible orders");
      setEligibleOrders([]);
    } finally {
      setFeedbackLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    if (activeTab === "pickups") {
      fetchPickups();
    } else {
      fetchEligibleOrders();
    }
  };

  const handleFeedbackSubmitted = (orderId: number, productId: number) => {
    // Filter out the product from the eligible orders
    setEligibleOrders((prevOrders) =>
      prevOrders.filter(
        (order) =>
          !(order.order_id === orderId && order.product_id === productId)
      )
    );

    // If there are no more products from this order, collapse it
    const remainingOrderProducts = eligibleOrders.filter(
      (order) => order.order_id === orderId
    );
    if (remainingOrderProducts.length <= 1) {
      setSelectedOrder(null);
    }
  };

  const groupOrdersByOrderId = () => {
    const ordersMap = new Map();

    eligibleOrders.forEach((order) => {
      if (!ordersMap.has(order.order_id)) {
        // Create a new entry for this order_id with its first product
        ordersMap.set(order.order_id, {
          order_id: order.order_id,
          order_date: order.order_date,
          order_reference: order.order_reference,
          products: [
            {
              product_id: order.product_id,
              product_name: order.product_name,
            },
          ],
        });
      } else {
        // Add this product to the existing order
        ordersMap.get(order.order_id).products.push({
          product_id: order.product_id,
          product_name: order.product_name,
        });
      }
    });

    // Convert map to array for FlatList
    return Array.from(ordersMap.values());
  };

  const handleReschedulePickup = (pickup: Pickup) => {
    setSelectedPickup(pickup);
    setNewPickupDate(new Date(pickup.pickup_date));
    setNewPickupNotes(pickup.pickup_notes || "");
    setIsRescheduling(true);
  };

  const handleSaveReschedule = async () => {
    if (!selectedPickup) return;

    try {
      const formattedDate = formatDateForAPI(newPickupDate);
      const response = await pickupService.schedulePickup({
        action: "schedule_pickup",
        order_id: selectedPickup.order_id,
        pickup_date: formattedDate,
        pickup_notes: newPickupNotes,
      });

      if (response.status === "success") {
        Alert.alert("Success", "Pickup rescheduled successfully");
        setIsRescheduling(false);
        fetchPickups(); // Refresh pickups list
      } else {
        Alert.alert("Error", response.message || "Failed to reschedule pickup");
      }
    } catch (error) {
      console.error("Error rescheduling pickup:", error);
      Alert.alert("Error", "Failed to reschedule pickup");
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const formatDateForAPI = (date: Date) => {
    return date.toISOString().slice(0, 19).replace("T", " ");
  };

  // Format payment method for display
  const formatPaymentMethodName = (method?: string): string => {
    if (!method) return "Not specified";

    switch (method) {
      case "credit_card":
        return "Credit/Debit Card";
      case "bank_transfer":
        return "Bank Transfer";
      case "cash_on_pickup":
        return "Cash on Pickup";
      case "gcash":
        return "GCash";
      case "paypal":
        return "PayPal";
      default:
        return method
          .replace(/_/g, " ")
          .replace(/\b\w/g, (l) => l.toUpperCase());
    }
  };

  // Get color for different status values
  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case "pending":
        return "#f57c00"; // Orange
      case "assigned":
        return "#1976d2"; // Blue
      case "ready":
        return "#4caf50"; // Green
      case "in_transit":
        return "#0288d1"; // Light blue
      case "completed":
        return "#388e3c"; // Dark green
      case "canceled":
        return "#d32f2f"; // Red
      default:
        return "#757575"; // Grey
    }
  };

  // Get user-friendly status text
  const getStatusText = (status: string) => {
    switch (status.toLowerCase()) {
      case "pending":
        return "Pending";
      case "assigned":
        return "Processing";
      case "ready":
        return "Ready for Pickup";
      case "in_transit":
        return "In Transit";
      case "completed":
        return "Completed";
      case "canceled":
        return "Canceled";
      default:
        return status;
    }
  };

  // Get icon for different status values
  const getStatusIcon = (status: string) => {
    switch (status.toLowerCase()) {
      case "pending":
        return "time-outline"; // Clock icon
      case "assigned":
        return "people-outline"; // People icon
      case "ready":
        return "checkmark-circle-outline"; // Checkmark icon
      case "in_transit":
        return "car-outline"; // Car icon
      case "completed":
        return "bag-check-outline"; // Bag with checkmark
      case "canceled":
        return "close-circle-outline"; // X icon
      default:
        return "help-circle-outline"; // Question mark icon
    }
  };

  const getStatusProgress = (status: string) => {
    switch (status.toLowerCase()) {
      case "pending":
        return 1;
      case "assigned":
        return 2;
      case "ready":
        return 3;
      case "in_transit":
        return 3;
      case "completed":
        return 4;
      default:
        return 0;
    }
  };

  const togglePickupDetails = (pickupId: number) => {
    setExpandedPickups((prev) => {
      if (prev.includes(pickupId)) {
        return prev.filter((id) => id !== pickupId);
      } else {
        return [...prev, pickupId];
      }
    });
  };

  const renderOrderItem = ({ item }: { item: OrderItem }) => (
    <View style={pickupStyles.orderItemContainer}>
      <View style={pickupStyles.orderItemInfo}>
        <ThemedText style={pickupStyles.orderItemName}>
          {item.product_name || "Unknown Product"}
        </ThemedText>
        <View style={pickupStyles.orderItemDetails}>
          <ThemedText style={pickupStyles.orderItemQuantity}>
            {item.quantity} {item.unit_type || "pc"}
          </ThemedText>
          <ThemedText style={pickupStyles.orderItemPrice}>
            ₱{item.price !== undefined ? Number(item.price).toFixed(2) : "0.00"}{" "}
            each
          </ThemedText>
        </View>
      </View>
      <ThemedText style={pickupStyles.orderItemSubtotal}>
        ₱
        {item.subtotal !== undefined
          ? Number(item.subtotal).toFixed(2)
          : "0.00"}
      </ThemedText>
    </View>
  );

  const renderPickupItem = ({ item }: { item: Pickup }) => {
    const isExpanded = expandedPickups.includes(item.pickup_id);
    const statusProgress = getStatusProgress(item.pickup_status);

    // Extract the order items from the Pickup item to avoid nesting FlatLists
    const orderItems = item.order_items || [];

    return (
      <View style={pickupStyles.pickupItem}>
        {/* Status indicator strip */}
        <View
          style={[
            pickupStyles.statusStrip,
            { backgroundColor: getStatusColor(item.pickup_status) },
          ]}
        />

        <TouchableOpacity
          style={pickupStyles.pickupHeader}
          onPress={() => togglePickupDetails(item.pickup_id)}
          activeOpacity={0.7}
        >
          <View style={pickupStyles.pickupInfo}>
            <ThemedText style={pickupStyles.pickupId}>
              Pickup #{item.pickup_id}
            </ThemedText>
            <View
              style={[
                pickupStyles.statusBadge,
                { backgroundColor: getStatusColor(item.pickup_status) },
              ]}
            >
              <Ionicons
                name={getStatusIcon(item.pickup_status)}
                size={16}
                color={COLORS.light}
                style={{ marginRight: 5 }}
              />
              <Text style={pickupStyles.statusRowText}>
                {getStatusText(item.pickup_status)}
              </Text>
            </View>
          </View>
          {item.pickup_status.toLowerCase() === "pending" && (
            <TouchableOpacity
              style={pickupStyles.actionButton}
              onPress={(e) => {
                e.stopPropagation();
                handleReschedulePickup(item);
              }}
            >
              <Ionicons
                name="calendar-outline"
                size={18}
                color={COLORS.light}
              />
              <Text style={pickupStyles.actionButtonText}>Reschedule</Text>
            </TouchableOpacity>
          )}
        </TouchableOpacity>

        {/* Status progress indicator */}
        <View style={pickupStyles.statusProgressContainer}>
          <View
            style={[
              pickupStyles.statusProgressStep,
              statusProgress >= 1 && pickupStyles.statusProgressStepActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressLine,
              statusProgress >= 2 && pickupStyles.statusProgressLineActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressStep,
              statusProgress >= 2 && pickupStyles.statusProgressStepActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressLine,
              statusProgress >= 3 && pickupStyles.statusProgressLineActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressStep,
              statusProgress >= 3 && pickupStyles.statusProgressStepActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressLine,
              statusProgress >= 4 && pickupStyles.statusProgressLineActive,
            ]}
          />
          <View
            style={[
              pickupStyles.statusProgressStep,
              statusProgress >= 4 && pickupStyles.statusProgressStepActive,
            ]}
          />
        </View>

        {/* Status labels */}
        <View style={pickupStyles.statusLabelsContainer}>
          <Text
            style={[
              pickupStyles.statusLabel,
              statusProgress >= 1 && pickupStyles.statusLabelActive,
            ]}
          >
            Pending
          </Text>
          <Text
            style={[
              pickupStyles.statusLabel,
              statusProgress >= 2 && pickupStyles.statusLabelActive,
            ]}
          >
            Processing
          </Text>
          <Text
            style={[
              pickupStyles.statusLabel,
              statusProgress >= 3 && pickupStyles.statusLabelActive,
            ]}
          >
            Ready
          </Text>
          <Text
            style={[
              pickupStyles.statusLabel,
              statusProgress >= 4 && pickupStyles.statusLabelActive,
            ]}
          >
            Completed
          </Text>
        </View>

        <View style={pickupStyles.pickupDetails}>
          <View style={pickupStyles.detailRow}>
            <Ionicons name="calendar" size={16} color={COLORS.primary} />
            <ThemedText style={pickupStyles.detailText}>
              {formatDate(item.pickup_date)}
            </ThemedText>
          </View>

          <View style={pickupStyles.detailRow}>
            <Ionicons name="location" size={16} color={COLORS.primary} />
            <ThemedText style={pickupStyles.detailText}>
              {item.pickup_location}
            </ThemedText>
          </View>

          {/* Payment method */}
          <View style={pickupStyles.detailRow}>
            <Ionicons name="wallet-outline" size={16} color={COLORS.primary} />
            <ThemedText style={pickupStyles.detailText}>
              Payment: {formatPaymentMethodName(item.payment_method)}
            </ThemedText>
          </View>

          {item.contact_person && (
            <View style={pickupStyles.detailRow}>
              <Ionicons name="person" size={16} color={COLORS.primary} />
              <ThemedText style={pickupStyles.detailText}>
                Contact: {item.contact_person}
              </ThemedText>
            </View>
          )}

          {item.pickup_notes && (
            <View style={pickupStyles.detailRow}>
              <Ionicons
                name="information-circle"
                size={16}
                color={COLORS.primary}
              />
              <ThemedText style={pickupStyles.detailText}>
                Notes: {item.pickup_notes}
              </ThemedText>
            </View>
          )}
        </View>

        <View style={pickupStyles.orderSummary}>
          <View style={pickupStyles.orderHeader}>
            <View style={pickupStyles.orderTitleContainer}>
              <Ionicons
                name="cart-outline"
                size={18}
                color={COLORS.primary}
                style={{ marginRight: 5 }}
              />
              <ThemedText style={pickupStyles.orderTitle}>
                Order #{item.order_id}
              </ThemedText>
            </View>
            <TouchableOpacity
              onPress={() => togglePickupDetails(item.pickup_id)}
              hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            >
              <Ionicons
                name={isExpanded ? "chevron-up" : "chevron-down"}
                size={22}
                color={COLORS.primary}
              />
            </TouchableOpacity>
          </View>

          {/* Order summary line */}
          <View style={pickupStyles.orderSummaryLine}>
            <ThemedText style={pickupStyles.orderSummaryText}>
              <Text style={pickupStyles.itemCount}>{item.item_count || 0}</Text>{" "}
              items
            </ThemedText>
            <ThemedText style={pickupStyles.orderTotal}>
              Total:{" "}
              <Text style={pickupStyles.totalAmount}>
                ₱{(item.order_total || 0).toFixed(2)}
              </Text>
            </ThemedText>
          </View>
        </View>

        {/* Expandable order details section */}
        {isExpanded && orderItems.length > 0 && (
          <View style={pickupStyles.orderItemsList}>
            <ThemedText style={pickupStyles.orderItemsHeader}>
              Order Items:
            </ThemedText>
            {/* Replace FlatList with a map to render items directly to avoid nesting */}
            {orderItems.map((orderItem) => (
              <View
                key={
                  orderItem.order_item_id?.toString() ||
                  Math.random().toString()
                }
              >
                {renderOrderItem({ item: orderItem })}
              </View>
            ))}
            <View style={pickupStyles.orderTotalRow}>
              <ThemedText style={pickupStyles.orderTotalLabel}>
                Total:
              </ThemedText>
              <ThemedText style={pickupStyles.orderGrandTotal}>
                ₱{(item.order_total || 0).toFixed(2)}
              </ThemedText>
            </View>
          </View>
        )}

        {/* Tap to view details hint */}
        <TouchableOpacity
          style={pickupStyles.expandHint}
          onPress={() => togglePickupDetails(item.pickup_id)}
        >
          <ThemedText style={pickupStyles.expandHintText}>
            {isExpanded ? "Hide details" : "Show order details"}
          </ThemedText>
          <Ionicons
            name={
              isExpanded
                ? "chevron-up-circle-outline"
                : "chevron-down-circle-outline"
            }
            size={16}
            color={COLORS.muted}
          />
        </TouchableOpacity>
      </View>
    );
  };

  // Render feedback order item
  const renderFeedbackOrderItem = ({ item }: { item: any }) => (
    <View style={pickupStyles.pickupItem}>
      <TouchableOpacity
        style={pickupStyles.pickupHeader}
        onPress={() =>
          setSelectedOrder(
            selectedOrder === item.order_id ? null : item.order_id
          )
        }
      >
        <View style={pickupStyles.orderInfo}>
          <Ionicons name="receipt-outline" size={20} color={COLORS.primary} />
          <ThemedText style={pickupStyles.orderReference}>
            {item.order_reference}
          </ThemedText>
          <ThemedText style={pickupStyles.orderDate}>
            {new Date(item.order_date).toLocaleDateString()}
          </ThemedText>
        </View>
        <Ionicons
          name={selectedOrder === item.order_id ? "chevron-up" : "chevron-down"}
          size={20}
          color={COLORS.muted}
        />
      </TouchableOpacity>

      {selectedOrder === item.order_id && (
        <View style={pickupStyles.productList}>
          <ThemedText style={pickupStyles.productsTitle}>
            Products Available for Feedback
          </ThemedText>
          {item.products.map((product: any) => (
            <View key={product.product_id} style={pickupStyles.productItem}>
              <FeedbackForm
                productId={product.product_id}
                productName={product.product_name}
                orderId={item.order_id}
                orderReference={item.order_reference}
                onFeedbackSubmitted={() =>
                  handleFeedbackSubmitted(item.order_id, product.product_id)
                }
              />
            </View>
          ))}
        </View>
      )}
    </View>
  );

  const handleDateChange = (selectedDate: Date) => {
    setNewPickupDate(selectedDate);
  };

  // Render the feedback section
  const renderFeedbackSection = () => {
    if (feedbackLoading) {
      return (
        <View style={pickupStyles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <ThemedText style={pickupStyles.loadingText}>
            Loading eligible orders...
          </ThemedText>
        </View>
      );
    }

    if (feedbackError) {
      return (
        <View style={pickupStyles.errorContainer}>
          <Ionicons
            name="alert-circle-outline"
            size={48}
            color={COLORS.accent}
          />
          <ThemedText style={pickupStyles.errorText}>
            {feedbackError}
          </ThemedText>
          <TouchableOpacity
            style={pickupStyles.retryButton}
            onPress={fetchEligibleOrders}
          >
            <Ionicons name="refresh" size={18} color={COLORS.light} />
            <ThemedText style={pickupStyles.retryButtonText}>Retry</ThemedText>
          </TouchableOpacity>
        </View>
      );
    }

    const groupedOrders = groupOrdersByOrderId();

    if (groupedOrders.length === 0) {
      return (
        <View style={pickupStyles.emptyContainer}>
          <Ionicons name="star-outline" size={48} color={COLORS.muted} />
          <ThemedText style={pickupStyles.emptyText}>
            You don't have any completed orders that need feedback
          </ThemedText>
          <TouchableOpacity
            style={pickupStyles.refreshButton}
            onPress={fetchEligibleOrders}
          >
            <Ionicons name="refresh" size={18} color={COLORS.light} />
            <ThemedText style={pickupStyles.refreshButtonText}>
              Refresh
            </ThemedText>
          </TouchableOpacity>
        </View>
      );
    }

    return (
      <View style={pickupStyles.container}>
        <ThemedText style={pickupStyles.title}>
          Orders Available for Feedback
        </ThemedText>
        <ThemedText style={pickupStyles.subtitle}>
          Share your thoughts on products from your completed orders
        </ThemedText>

        <FlatList
          data={groupedOrders}
          renderItem={renderFeedbackOrderItem}
          keyExtractor={(item) => item.order_id.toString()}
          contentContainerStyle={pickupStyles.listContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        />
      </View>
    );
  };

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={false}
      onRequestClose={onClose}
    >
      <View style={pickupStyles.container}>
        {/* Header with tabs */}
        <View style={pickupStyles.header}>
          <View style={pickupStyles.headerTitleContainer}>
            <Ionicons
              name={
                activeTab === "pickups" ? "bag-check-outline" : "star-outline"
              }
              size={24}
              color={COLORS.light}
              style={pickupStyles.headerIcon}
            />
            <ThemedText style={pickupStyles.headerTitle}>
              {activeTab === "pickups" ? "My Orders" : "Leave Feedback"}
            </ThemedText>
          </View>

          <View style={pickupStyles.headerButtons}>
            <TouchableOpacity
              onPress={() => {
                if (activeTab === "pickups") {
                  Alert.alert(
                    "Order Information",
                    "Your orders will be available for pickup at the Municipal Agriculture Office. Present your order number when collecting your items.",
                    [{ text: "Got it!" }]
                  );
                } else {
                  Alert.alert(
                    "Feedback Information",
                    "Share your experience with products you've purchased. Your feedback helps farmers improve their products.",
                    [{ text: "Got it!" }]
                  );
                }
              }}
              style={pickupStyles.helpButton}
            >
              <Ionicons
                name="help-circle-outline"
                size={24}
                color={COLORS.light}
              />
            </TouchableOpacity>
            <TouchableOpacity
              onPress={onClose}
              style={pickupStyles.closeButton}
            >
              <Ionicons name="close" size={24} color={COLORS.light} />
            </TouchableOpacity>
          </View>
        </View>

        {/* Tab selector */}
        <View style={pickupStyles.tabContainer}>
          <TouchableOpacity
            style={[
              pickupStyles.tab,
              activeTab === "pickups" && pickupStyles.activeTab,
            ]}
            onPress={() => setActiveTab("pickups")}
          >
            <Ionicons
              name="bag-check-outline"
              size={20}
              color={activeTab === "pickups" ? COLORS.primary : COLORS.muted}
            />
            <Text
              style={[
                pickupStyles.tabText,
                activeTab === "pickups" && pickupStyles.activeTabText,
              ]}
            >
              Orders
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[
              pickupStyles.tab,
              activeTab === "feedback" && pickupStyles.activeTab,
            ]}
            onPress={() => setActiveTab("feedback")}
          >
            <Ionicons
              name="star-outline"
              size={20}
              color={activeTab === "feedback" ? COLORS.primary : COLORS.muted}
            />
            <Text
              style={[
                pickupStyles.tabText,
                activeTab === "feedback" && pickupStyles.activeTabText,
              ]}
            >
              Feedback
            </Text>
          </TouchableOpacity>
        </View>

        {/* Content */}
        {activeTab === "pickups" ? (
          loading ? (
            <View style={pickupStyles.loadingContainer}>
              <ActivityIndicator size="large" color={COLORS.primary} />
              <ThemedText style={pickupStyles.loadingText}>
                Loading orders...
              </ThemedText>
            </View>
          ) : pickups.length === 0 ? (
            <View style={pickupStyles.emptyContainer}>
              <Ionicons
                name="bag-handle-outline"
                size={80}
                color={COLORS.primary}
              />
              <ThemedText style={pickupStyles.emptyTitle}>
                No Orders Found
              </ThemedText>
              <ThemedText style={pickupStyles.emptyText}>
                You don't have any orders yet. Place an order to get started.
              </ThemedText>
              <View style={pickupStyles.emptyActionButtons}>
                <TouchableOpacity
                  style={pickupStyles.refreshButton}
                  onPress={fetchPickups}
                >
                  <Ionicons
                    name="refresh"
                    size={18}
                    color={COLORS.light}
                    style={{ marginRight: 5 }}
                  />
                  <Text style={pickupStyles.refreshButtonText}>Refresh</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={pickupStyles.shopButton}
                  onPress={onClose}
                >
                  <Ionicons
                    name="cart"
                    size={18}
                    color={COLORS.light}
                    style={{ marginRight: 5 }}
                  />
                  <Text style={pickupStyles.shopButtonText}>Shop Now</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <FlatList
              data={pickups}
              renderItem={renderPickupItem}
              keyExtractor={(item) => item.pickup_id.toString()}
              contentContainerStyle={pickupStyles.listContainer}
              showsVerticalScrollIndicator={false}
            />
          )
        ) : (
          renderFeedbackSection()
        )}
      </View>

      {/* Reschedule Modal */}
      <Modal
        visible={isRescheduling}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setIsRescheduling(false)}
      >
        <View style={pickupStyles.rescheduleModalContainer}>
          <View style={pickupStyles.rescheduleModal}>
            <View style={pickupStyles.modalHeader}>
              <Ionicons
                name="calendar-outline"
                size={28}
                color={COLORS.primary}
              />
              <ThemedText style={pickupStyles.rescheduleTitle}>
                Reschedule Pickup
              </ThemedText>
            </View>

            <View style={pickupStyles.modalDivider} />

            <Text style={pickupStyles.modalLabel}>
              Select New Date and Time:
            </Text>
            <TouchableOpacity
              style={pickupStyles.datePickerButton}
              onPress={() => setShowDatePicker(!showDatePicker)}
            >
              <Ionicons name="time-outline" size={20} color={COLORS.primary} />
              <ThemedText style={pickupStyles.dateButtonText}>
                {newPickupDate.toLocaleDateString("en-US", {
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </ThemedText>
              <Ionicons
                name={
                  showDatePicker ? "chevron-up-outline" : "chevron-down-outline"
                }
                size={20}
                color={COLORS.primary}
                style={{ marginLeft: "auto" }}
              />
            </TouchableOpacity>

            {showDatePicker && (
              <CalendarDatePicker
                value={newPickupDate}
                onChange={handleDateChange}
              />
            )}

            <Text style={pickupStyles.modalLabel}>Additional Notes:</Text>
            <TextInput
              style={pickupStyles.notesInput}
              placeholder="Special instructions for pickup..."
              value={newPickupNotes}
              onChangeText={setNewPickupNotes}
              multiline
              numberOfLines={4}
              placeholderTextColor={COLORS.muted}
            />

            <View style={pickupStyles.modalActions}>
              <TouchableOpacity
                style={[pickupStyles.modalButton, pickupStyles.cancelButton]}
                onPress={() => setIsRescheduling(false)}
              >
                <Ionicons
                  name="close-outline"
                  size={18}
                  color={COLORS.dark}
                  style={{ marginRight: 5 }}
                />
                <Text style={pickupStyles.cancelButtonText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[pickupStyles.modalButton, pickupStyles.saveButton]}
                onPress={handleSaveReschedule}
              >
                <Ionicons
                  name="save-outline"
                  size={18}
                  color={COLORS.light}
                  style={{ marginRight: 5 }}
                />
                <Text style={pickupStyles.saveButtonText}>Save Changes</Text>
              </TouchableOpacity>
            </View>

            <View style={pickupStyles.modalFooter}>
              <Text style={pickupStyles.modalFooterText}>
                Note: The Municipal Agriculture Office may contact you to
                confirm this change.
              </Text>
            </View>
          </View>
        </View>
      </Modal>
    </Modal>
  );
};

export default OrdersScreen;
