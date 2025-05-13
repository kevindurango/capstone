import React, { useState } from "react";
import {
  View,
  Modal,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  StyleSheet,
  TextInput,
  Text,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { pickupService } from "./PickupService";
import { marketService } from "./MarketService";
import { useAuth } from "@/contexts/AuthContext";
import NetInfo from "@react-native-community/netinfo";

// Custom date picker component (reused from PickupScreen)
interface CustomDatePickerProps {
  value: Date;
  onChange: (date: Date) => void;
}

const CustomDatePicker: React.FC<CustomDatePickerProps> = ({
  value,
  onChange,
}) => {
  const [date, setDate] = useState(value);

  const incrementDate = () => {
    const newDate = new Date(date);
    newDate.setDate(newDate.getDate() + 1);
    setDate(newDate);
    onChange(newDate);
  };

  const decrementDate = () => {
    const newDate = new Date(date);
    newDate.setDate(newDate.getDate() - 1);
    if (newDate >= new Date()) {
      setDate(newDate);
      onChange(newDate);
    }
  };

  const incrementHour = () => {
    const newDate = new Date(date);
    newDate.setHours(newDate.getHours() + 1);
    setDate(newDate);
    onChange(newDate);
  };

  const decrementHour = () => {
    const newDate = new Date(date);
    newDate.setHours(newDate.getHours() - 1);
    setDate(newDate);
    onChange(newDate);
  };

  return (
    <View style={styles.customDatePicker}>
      <View style={styles.datePickerSection}>
        <Text style={styles.datePickerLabel}>Date</Text>
        <View style={styles.datePickerControls}>
          <TouchableOpacity
            onPress={decrementDate}
            style={styles.datePickerButton}
          >
            <Text style={styles.datePickerButtonText}>-</Text>
          </TouchableOpacity>
          <Text style={styles.datePickerValue}>
            {date.toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
            })}
          </Text>
          <TouchableOpacity
            onPress={incrementDate}
            style={styles.datePickerButton}
          >
            <Text style={styles.datePickerButtonText}>+</Text>
          </TouchableOpacity>
        </View>
      </View>

      <View style={styles.datePickerSection}>
        <Text style={styles.datePickerLabel}>Time</Text>
        <View style={styles.datePickerControls}>
          <TouchableOpacity
            onPress={decrementHour}
            style={styles.datePickerButton}
          >
            <Text style={styles.datePickerButtonText}>-</Text>
          </TouchableOpacity>
          <Text style={styles.datePickerValue}>
            {date.toLocaleTimeString("en-US", {
              hour: "2-digit",
              minute: "2-digit",
            })}
          </Text>
          <TouchableOpacity
            onPress={incrementHour}
            style={styles.datePickerButton}
          >
            <Text style={styles.datePickerButtonText}>+</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
};

interface SchedulePickupScreenProps {
  visible: boolean;
  onClose: () => void;
  orderId: number | null;
}

const SchedulePickupScreen: React.FC<SchedulePickupScreenProps> = ({
  visible,
  onClose,
  orderId,
}) => {
  // Set initial pickup date to tomorrow at 10:00 AM
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  tomorrow.setHours(10, 0, 0, 0);

  const { user } = useAuth();
  const [pickupDate, setPickupDate] = useState(tomorrow);
  const [pickupNotes, setPickupNotes] = useState("");
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [networkStatus, setNetworkStatus] = useState({
    isConnected: true,
    isInternetReachable: true,
  });

  // Check network status when component mounts or becomes visible
  React.useEffect(() => {
    if (visible) {
      checkNetworkStatus();
    }
  }, [visible]);

  const checkNetworkStatus = async () => {
    try {
      const netInfo = await NetInfo.fetch();
      setNetworkStatus({
        isConnected: Boolean(netInfo.isConnected),
        isInternetReachable: Boolean(netInfo.isInternetReachable),
      });
      console.log(
        `[SchedulePickup] Network status - connected: ${netInfo.isConnected}, reachable: ${netInfo.isInternetReachable}`
      );
    } catch (error) {
      console.error("[SchedulePickup] Error checking network status:", error);
    }
  };

  const handleDateChange = (selectedDate: Date) => {
    setPickupDate(selectedDate);
  };

  const formatDate = (date: Date) => {
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

  const handleSchedulePickup = async () => {
    if (!orderId) {
      Alert.alert("Error", "Order ID is required to schedule pickup");
      return;
    }

    // Check network status before proceeding
    await checkNetworkStatus();

    setIsProcessing(true);

    try {
      const formattedDate = formatDateForAPI(pickupDate);

      // Show offline indicator if device is not connected
      if (!networkStatus.isConnected) {
        console.log(
          "[SchedulePickup] Device appears to be offline, proceeding with offline mode"
        );
      }

      // First, create a payment record for this order
      // This is the fix to ensure payments are properly inserted in the database
      const paymentData = {
        order_id: orderId,
        payment_method: "cash_on_pickup", // Default to cash on pickup
        user_id: user?.user_id || null,
        amount: 0, // Set a default amount - this should be updated with actual order total
      };

      console.log("[SchedulePickup] Creating payment record:", paymentData);

      const paymentResponse = await marketService.processPayment(paymentData);

      if (paymentResponse && paymentResponse.status === "success") {
        console.log(
          "[SchedulePickup] Payment record created:",
          paymentResponse.data
        );

        // Now schedule the pickup with the new payment_id
        const pickupData = {
          action: "schedule_pickup",
          order_id: orderId,
          payment_id: paymentResponse.data.payment_id,
          pickup_date: formattedDate,
          pickup_notes: pickupNotes.trim() || undefined,
        };

        console.log("[SchedulePickup] Sending pickup request:", pickupData);

        const response = await pickupService.schedulePickup(pickupData);

        if (response.status === "success") {
          // Check if this was handled in offline mode
          const offlineMode =
            response.message && response.message.includes("offline");

          Alert.alert(
            offlineMode ? "Pickup Scheduled (Offline)" : "Pickup Scheduled",
            offlineMode
              ? `Your pickup has been scheduled for ${formatDate(
                  pickupDate
                )}. It will sync when connection is restored.`
              : `Your pickup has been scheduled for ${formatDate(pickupDate)}`,
            [{ text: "OK", onPress: onClose }]
          );
        } else {
          Alert.alert("Error", response.message || "Failed to schedule pickup");
        }
      } else {
        throw new Error("Failed to create payment record for your order");
      }
    } catch (error: any) {
      console.error("[SchedulePickup] Error scheduling pickup:", error);
      Alert.alert("Error", "Failed to schedule pickup. Please try again.");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={onClose}
    >
      <View style={styles.container}>
        <View style={styles.modal}>
          <View style={styles.header}>
            <ThemedText style={styles.title}>Schedule Your Pickup</ThemedText>
            <TouchableOpacity onPress={onClose} style={styles.closeButton}>
              <Ionicons name="close" size={24} color={COLORS.dark} />
            </TouchableOpacity>
          </View>

          <View style={styles.content}>
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Order #{orderId}</Text>
              <Text style={styles.sectionDescription}>
                Please select when you would like to pick up your order from the
                Municipal Agriculture Office.
              </Text>
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Pickup Date & Time</Text>

              <TouchableOpacity
                style={styles.dateSelector}
                onPress={() => setShowDatePicker(!showDatePicker)}
              >
                <Ionicons
                  name="calendar-outline"
                  size={20}
                  color={COLORS.primary}
                />
                <Text style={styles.dateButtonText}>
                  {formatDate(pickupDate)}
                </Text>
              </TouchableOpacity>

              {showDatePicker && (
                <CustomDatePicker
                  value={pickupDate}
                  onChange={handleDateChange}
                />
              )}
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Additional Notes</Text>
              <TextInput
                style={styles.notesInput}
                placeholder="Any special instructions for pickup? (optional)"
                value={pickupNotes}
                onChangeText={setPickupNotes}
                multiline
                numberOfLines={3}
                placeholderTextColor={COLORS.muted}
              />
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Payment Method</Text>
              <Text style={styles.paymentMethod}>
                <Ionicons
                  name="cash-outline"
                  size={18}
                  color={COLORS.primary}
                />{" "}
                Cash on Pickup
              </Text>
            </View>
          </View>

          <TouchableOpacity
            style={[styles.button, isProcessing && styles.buttonDisabled]}
            onPress={handleSchedulePickup}
            disabled={isProcessing}
          >
            {isProcessing ? (
              <ActivityIndicator size="small" color={COLORS.light} />
            ) : (
              <>
                <Text style={styles.buttonText}>Schedule Pickup</Text>
                <Ionicons
                  name="checkmark-circle"
                  size={20}
                  color={COLORS.light}
                />
              </>
            )}
          </TouchableOpacity>

          <TouchableOpacity style={styles.skipButton} onPress={onClose}>
            <Text style={styles.skipButtonText}>Skip for now</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "rgba(0,0,0,0.5)",
  },
  modal: {
    width: "90%",
    backgroundColor: "#fff",
    borderRadius: 15,
    padding: 20,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 3.84,
    elevation: 5,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 20,
  },
  title: {
    fontSize: 20,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  closeButton: {
    padding: 5,
  },
  content: {
    marginBottom: 20,
  },
  section: {
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: "bold",
    color: COLORS.dark,
    marginBottom: 8,
  },
  sectionDescription: {
    fontSize: 14,
    color: COLORS.muted,
    marginBottom: 10,
  },
  dateSelector: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f0f0f0",
    padding: 15,
    borderRadius: 10,
  },
  dateButtonText: {
    marginLeft: 10,
    fontSize: 16,
    color: COLORS.dark,
  },
  notesInput: {
    backgroundColor: "#f0f0f0",
    padding: 15,
    borderRadius: 10,
    minHeight: 100,
    textAlignVertical: "top",
    color: COLORS.dark,
  },
  button: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: COLORS.primary,
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
  },
  buttonDisabled: {
    backgroundColor: COLORS.muted,
  },
  buttonText: {
    color: COLORS.light,
    fontWeight: "bold",
    fontSize: 16,
    marginRight: 10,
  },
  skipButton: {
    alignItems: "center",
    padding: 10,
  },
  skipButtonText: {
    color: COLORS.muted,
    fontSize: 14,
  },
  customDatePicker: {
    backgroundColor: "#f5f5f5",
    borderRadius: 8,
    padding: 12,
    marginTop: 10,
    marginBottom: 15,
  },
  datePickerSection: {
    marginBottom: 10,
  },
  datePickerLabel: {
    fontSize: 14,
    fontWeight: "bold",
    color: COLORS.dark,
    marginBottom: 5,
  },
  datePickerControls: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  datePickerButton: {
    backgroundColor: COLORS.primary,
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  datePickerButtonText: {
    color: COLORS.light,
    fontSize: 18,
    fontWeight: "bold",
  },
  datePickerValue: {
    fontSize: 16,
    color: COLORS.dark,
    fontWeight: "600",
    paddingHorizontal: 10,
  },
  paymentMethod: {
    fontSize: 16,
    color: COLORS.dark,
    fontWeight: "600",
    paddingHorizontal: 10,
  },
});

export default SchedulePickupScreen;
