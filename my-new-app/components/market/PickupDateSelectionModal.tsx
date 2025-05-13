import React, { useState } from "react";
import {
  View,
  Text,
  Modal,
  TouchableOpacity,
  StyleSheet,
  Platform,
  Alert,
  ActivityIndicator,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import {
  DateTimePickerAndroid,
  DateTimePickerEvent,
} from "@react-native-community/datetimepicker";
import DateTimePicker from "@react-native-community/datetimepicker";

export const COLORS = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  gradient: ["#1B5E20", "#388E3C", "#4CAF50"],
  shadow: "#000000",
  success: "#4CAF50",
};

import { API_URLS } from "@/constants/Config";

interface PickupDateSelectionModalProps {
  visible: boolean;
  onClose: () => void;
  onComplete: (pickupData: any) => void;
  orderId: number | null;
  paymentData?: any;
}

const PickupDateSelectionModal: React.FC<PickupDateSelectionModalProps> = ({
  visible,
  onClose,
  onComplete,
  orderId,
  paymentData,
}) => {
  const [selectedDate, setSelectedDate] = useState<Date>(getTomorrowDate());
  const [isProcessing, setIsProcessing] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState(false);

  // Function to get tomorrow's date as the default selection
  function getTomorrowDate(): Date {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow;
  }

  // Calculate minimum date (tomorrow) and maximum date (2 weeks from today)
  const minDate = getTomorrowDate();
  const maxDate = new Date();
  maxDate.setDate(maxDate.getDate() + 14); // 2 weeks from today

  const handleDateChange = (event: DateTimePickerEvent, date?: Date) => {
    setShowDatePicker(Platform.OS === "ios");
    if (date) {
      setSelectedDate(date);
    }
  };

  const showDatepicker = () => {
    if (Platform.OS === "android") {
      DateTimePickerAndroid.open({
        value: selectedDate,
        onChange: handleDateChange,
        mode: "date",
        minimumDate: minDate,
        maximumDate: maxDate,
      });
    } else {
      setShowDatePicker(true);
    }
  };

  const handleConfirm = async () => {
    if (isProcessing) return;
    setIsProcessing(true);

    try {
      // Format date for API
      const formattedDate = selectedDate.toISOString().split("T")[0];

      // Handle the pickup scheduling process
      console.log("[PickupDateSelection] Order ID:", orderId);
      console.log("[PickupDateSelection] Selected date:", formattedDate);

      const pickupData = {
        action: "schedule_pickup",
        order_id: orderId,
        payment_id: paymentData?.payment_id,
        pickup_date: formattedDate,
        pickup_notes: "",
      };

      // Safely call onComplete first with data
      const result = await fetch(`${API_URLS.ORDER}/pickup`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(pickupData),
      });

      const responseData = await result.json();

      // Process complete - call onComplete first before closing modal
      setTimeout(() => {
        onComplete({
          pickup_date: formattedDate,
          pickup_id: responseData?.data?.pickup_id || null,
        });
      }, 100);
    } catch (error) {
      console.error("[PickupDateSelection] Error scheduling pickup:", error);
      Alert.alert(
        "Error",
        "Failed to schedule pickup. Please try again or contact support."
      );
      setIsProcessing(false);
    }
  };

  const formatDate = (date: Date): string => {
    const days = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ];
    const months = [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ];

    return `${days[date.getDay()]}, ${
      months[date.getMonth()]
    } ${date.getDate()}, ${date.getFullYear()}`;
  };

  return (
    <Modal
      animationType="slide"
      transparent={false}
      visible={visible}
      onRequestClose={onClose}
    >
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.title}>Schedule Pickup</Text>
          <TouchableOpacity style={styles.closeButton} onPress={onClose}>
            <Ionicons name="close" size={24} color={COLORS.dark} />
          </TouchableOpacity>
        </View>

        <View style={styles.content}>
          <View style={styles.successCard}>
            <View style={styles.iconContainer}>
              <Ionicons
                name="checkmark-circle"
                size={60}
                color={COLORS.success}
              />
            </View>
            <Text style={styles.successText}>Payment Successful!</Text>
            <Text style={styles.successSubText}>
              Your order #{orderId} has been confirmed.
            </Text>
          </View>

          <View style={styles.pickupSection}>
            <Text style={styles.sectionTitle}>Select Pickup Date</Text>
            <Text style={styles.instructions}>
              Please select your preferred date to pick up your order from our
              facility. (Available for next 2 weeks)
            </Text>

            <TouchableOpacity
              style={styles.dateSelector}
              onPress={showDatepicker}
            >
              <Ionicons
                name="calendar-outline"
                size={24}
                color={COLORS.primary}
              />
              <Text style={styles.dateText}>{formatDate(selectedDate)}</Text>
              <Ionicons name="chevron-down" size={20} color={COLORS.muted} />
            </TouchableOpacity>

            {Platform.OS === "ios" && showDatePicker && (
              <View style={styles.datePickerContainer}>
                <DateTimePicker
                  value={selectedDate}
                  mode="date"
                  display="spinner"
                  minimumDate={minDate}
                  maximumDate={maxDate}
                  onChange={handleDateChange}
                />
                <TouchableOpacity
                  style={styles.datePickerDoneButton}
                  onPress={() => setShowDatePicker(false)}
                >
                  <Text style={styles.datePickerDoneText}>Done</Text>
                </TouchableOpacity>
              </View>
            )}

            <View style={styles.pickupInfo}>
              <View style={styles.pickupInfoItem}>
                <Ionicons name="location" size={20} color={COLORS.primary} />
                <Text style={styles.pickupInfoText}>
                  Pickup Location: Municipal Agriculture Office
                </Text>
              </View>
              <View style={styles.pickupInfoItem}>
                <Ionicons name="time" size={20} color={COLORS.primary} />
                <Text style={styles.pickupInfoText}>
                  Business Hours: 8:00 AM - 5:00 PM
                </Text>
              </View>
              <View style={styles.pickupInfoItem}>
                <Ionicons
                  name="information-circle"
                  size={20}
                  color={COLORS.primary}
                />
                <Text style={styles.pickupInfoText}>
                  Bring your order ID for verification
                </Text>
              </View>
            </View>
          </View>
        </View>

        <View style={styles.actions}>
          <TouchableOpacity
            style={[
              styles.confirmButton,
              isProcessing && styles.disabledButton,
            ]}
            onPress={handleConfirm}
            disabled={isProcessing}
          >
            {isProcessing ? (
              <ActivityIndicator size="small" color={COLORS.light} />
            ) : (
              <>
                <Text style={styles.confirmButtonText}>
                  Confirm Pickup Date
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
      </SafeAreaView>
    </Modal>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.muted,
  },
  title: {
    fontSize: 20,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  closeButton: {
    padding: 8,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  successCard: {
    backgroundColor: COLORS.light,
    padding: 20,
    borderRadius: 10,
    alignItems: "center",
    marginBottom: 24,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 3,
      },
    }),
  },
  iconContainer: {
    marginBottom: 10,
  },
  successText: {
    fontSize: 22,
    fontWeight: "bold",
    color: COLORS.success,
    marginBottom: 5,
  },
  successSubText: {
    fontSize: 16,
    color: COLORS.text,
    textAlign: "center",
  },
  pickupSection: {
    backgroundColor: COLORS.light,
    padding: 20,
    borderRadius: 10,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 3,
      },
    }),
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.dark,
    marginBottom: 10,
  },
  instructions: {
    fontSize: 14,
    color: COLORS.text,
    marginBottom: 20,
  },
  dateSelector: {
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: COLORS.muted,
    borderRadius: 8,
    padding: 12,
    marginBottom: 20,
  },
  dateText: {
    flex: 1,
    marginLeft: 10,
    fontSize: 16,
    color: COLORS.dark,
  },
  datePickerContainer: {
    backgroundColor: COLORS.light,
    borderRadius: 8,
    marginBottom: 20,
    overflow: "hidden",
  },
  datePickerDoneButton: {
    alignSelf: "flex-end",
    padding: 10,
    marginRight: 10,
  },
  datePickerDoneText: {
    color: COLORS.primary,
    fontWeight: "600",
    fontSize: 16,
  },
  pickupInfo: {
    marginTop: 10,
  },
  pickupInfoItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
  },
  pickupInfoText: {
    marginLeft: 10,
    fontSize: 14,
    color: COLORS.text,
  },
  actions: {
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: COLORS.muted,
  },
  confirmButton: {
    backgroundColor: COLORS.primary,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    padding: 16,
    borderRadius: 8,
  },
  disabledButton: {
    opacity: 0.7,
  },
  confirmButtonText: {
    color: COLORS.light,
    fontWeight: "bold",
    fontSize: 16,
    marginRight: 8,
  },
});

export default PickupDateSelectionModal;
