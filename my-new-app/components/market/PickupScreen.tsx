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
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { pickupService } from "./PickupService";
import { pickupStyles } from "./styles";
import { Calendar } from "react-native-calendars";
import { TimePickerModal } from "react-native-paper-dates";

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

// Rest of the component remains the same
interface PickupScreenProps {
  visible: boolean;
  onClose: () => void;
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
}

const PickupScreen: React.FC<PickupScreenProps> = ({ visible, onClose }) => {
  const { user } = useAuth();
  const [pickups, setPickups] = useState<Pickup[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedPickup, setSelectedPickup] = useState<Pickup | null>(null);
  const [isRescheduling, setIsRescheduling] = useState(false);
  const [newPickupDate, setNewPickupDate] = useState(new Date());
  const [newPickupNotes, setNewPickupNotes] = useState("");
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [expandedPickups, setExpandedPickups] = useState<number[]>([]);

  // Fetch pickups when modal becomes visible
  useEffect(() => {
    let isMounted = true;

    const loadPickups = async () => {
      if (visible && user) {
        try {
          if (!user || !user.user_id) {
            console.log(
              "[PickupScreen] No user ID available, cannot fetch pickups"
            );
            if (isMounted) {
              Alert.alert(
                "Error",
                "User information not available. Please log in again."
              );
            }
            return;
          }

          if (isMounted) setLoading(true);

          console.log(
            `[PickupScreen] Fetching pickups for user ID: ${user.user_id}`
          );
          const response = await pickupService.getUserPickups(user.user_id);
          console.log(`[PickupScreen] Response status: ${response.status}`);

          if (!isMounted) return;

          if (response && response.status === "success") {
            setPickups(response.data || []);
          } else if (
            response &&
            response.status === "error" &&
            response.message &&
            response.message.includes("No pickups found")
          ) {
            console.log(
              `[PickupScreen] No pickups found for user ID: ${user.user_id}`
            );
            setPickups([]);
          } else {
            console.error(
              `[PickupScreen] Error response: ${JSON.stringify(response)}`
            );
            Alert.alert("Error", response?.message || "Failed to load pickups");
          }
        } catch (error: any) {
          if (!isMounted) return;

          console.error(
            `[PickupScreen] Error fetching pickups: ${error.message || error}`
          );

          // Don't show error alert for empty pickups
          if (error.message && error.message.includes("No pickups found")) {
            console.log("[PickupScreen] No pickups found, setting empty array");
            setPickups([]);
          } else {
            Alert.alert(
              "Network Issue",
              "Unable to fetch pickups. Please check your connection and try again."
            );
          }
        } finally {
          if (isMounted) setLoading(false);
        }
      }
    };

    loadPickups();

    return () => {
      isMounted = false;
    };
  }, [visible, user]);

  const fetchPickups = async () => {
    if (!user || !user.user_id) {
      console.log("[PickupScreen] No user ID available, cannot fetch pickups");
      Alert.alert(
        "Error",
        "User information not available. Please log in again."
      );
      return;
    }

    setLoading(true);
    try {
      console.log(
        `[PickupScreen] Fetching pickups for user ID: ${user.user_id}`
      );
      const response = await pickupService.getUserPickups(user.user_id);
      console.log(`[PickupScreen] Response status: ${response.status}`);

      if (response && response.status === "success") {
        setPickups(response.data || []);
      } else if (
        response &&
        response.status === "error" &&
        response.message &&
        response.message.includes("No pickups found")
      ) {
        console.log(
          `[PickupScreen] No pickups found for user ID: ${user.user_id}`
        );
        setPickups([]);
      } else {
        console.error(
          `[PickupScreen] Error response: ${JSON.stringify(response)}`
        );
        Alert.alert("Error", response?.message || "Failed to load pickups");
      }
    } catch (error: any) {
      console.error(
        `[PickupScreen] Error fetching pickups: ${error.message || error}`
      );

      // Don't show error alert for empty pickups
      if (error.message && error.message.includes("No pickups found")) {
        console.log("[PickupScreen] No pickups found, setting empty array");
        setPickups([]);
      } else {
        Alert.alert(
          "Network Issue",
          "Unable to fetch pickups. Please check your connection and try again."
        );
      }
    } finally {
      setLoading(false);
    }
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

    // Extract order items to avoid nesting FlatList
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
            {/* Replace FlatList with map to avoid nesting virtualized lists */}
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

  const handleDateChange = (selectedDate: Date) => {
    setNewPickupDate(selectedDate);
  };

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={false}
      onRequestClose={onClose}
    >
      <View style={pickupStyles.container}>
        {/* Header */}
        <View style={pickupStyles.header}>
          <View style={pickupStyles.headerTitleContainer}>
            <Ionicons
              name="bag-check-outline"
              size={24}
              color={COLORS.light}
              style={pickupStyles.headerIcon}
            />
            <ThemedText style={pickupStyles.headerTitle}>My Pickups</ThemedText>
          </View>
          <View style={pickupStyles.headerButtons}>
            <TouchableOpacity
              onPress={() =>
                Alert.alert(
                  "Pickup Information",
                  "Your orders will be available for pickup at the Municipal Agriculture Office. Present your order number when collecting your items.",
                  [{ text: "Got it!" }]
                )
              }
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

        {/* Content */}
        {loading ? (
          <View style={pickupStyles.loadingContainer}>
            <ActivityIndicator size="large" color={COLORS.primary} />
            <ThemedText style={pickupStyles.loadingText}>
              Loading pickups...
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
              No Pickups Found
            </ThemedText>
            <ThemedText style={pickupStyles.emptyText}>
              You don't have any pickups scheduled yet. Once you place an order,
              your pickups will appear here.
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

export default PickupScreen;
