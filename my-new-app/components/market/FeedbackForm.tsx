import React, { useState } from "react";
import {
  View,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "@/contexts/AuthContext";
import FeedbackService from "@/services/FeedbackService";

interface FeedbackFormProps {
  productId: number;
  productName: string;
  orderId?: number; // Added orderId as an optional prop
  orderReference?: string; // Added to display order reference
  onFeedbackSubmitted?: () => void;
}

const FeedbackForm: React.FC<FeedbackFormProps> = ({
  productId,
  productName,
  orderId,
  orderReference,
  onFeedbackSubmitted,
}) => {
  const [feedbackText, setFeedbackText] = useState("");
  const [rating, setRating] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { user } = useAuth();

  const handleSubmit = async () => {
    if (!feedbackText.trim()) {
      Alert.alert("Error", "Please enter your feedback");
      return;
    }

    if (rating === 0) {
      Alert.alert("Error", "Please select a rating");
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await FeedbackService.submitFeedback({
        user_id: user?.user_id,
        product_id: productId,
        order_id: orderId, // Include orderId in the submission if available
        feedback_text: feedbackText,
        rating: rating,
      });

      if (response.success) {
        setFeedbackText("");
        setRating(0);
        Alert.alert("Success", "Your feedback has been submitted successfully");
        if (onFeedbackSubmitted) {
          onFeedbackSubmitted();
        }
      } else {
        Alert.alert("Error", response.message || "Failed to submit feedback");
      }
    } catch (error) {
      console.error("Error submitting feedback:", error);
      Alert.alert("Error", "An unexpected error occurred");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <ThemedText style={styles.title}>
        Share Your Feedback for {productName}
      </ThemedText>

      {orderReference && (
        <View style={styles.orderInfoContainer}>
          <Ionicons name="receipt-outline" size={16} color={COLORS.primary} />
          <ThemedText style={styles.orderReference}>
            Order: {orderReference}
          </ThemedText>
        </View>
      )}

      <View style={styles.ratingContainer}>
        <ThemedText style={styles.ratingLabel}>Rating:</ThemedText>
        <View style={styles.starsContainer}>
          {[1, 2, 3, 4, 5].map((star) => (
            <TouchableOpacity
              key={star}
              onPress={() => setRating(star)}
              style={styles.starButton}
            >
              <Ionicons
                name={rating >= star ? "star" : "star-outline"}
                size={24}
                color={rating >= star ? COLORS.secondary : COLORS.muted}
              />
            </TouchableOpacity>
          ))}
        </View>
      </View>

      <TextInput
        style={styles.input}
        placeholder="Write your feedback here..."
        placeholderTextColor={COLORS.muted}
        multiline
        value={feedbackText}
        onChangeText={setFeedbackText}
        numberOfLines={4}
      />

      <TouchableOpacity
        style={[
          styles.submitButton,
          feedbackText.trim() === "" && styles.disabledButton,
        ]}
        onPress={handleSubmit}
        disabled={isSubmitting || feedbackText.trim() === ""}
      >
        {isSubmitting ? (
          <ActivityIndicator size="small" color={COLORS.light} />
        ) : (
          <ThemedText style={styles.submitButtonText}>
            Submit Feedback
          </ThemedText>
        )}
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: COLORS.light,
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#eeeeee",
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 12,
    color: COLORS.dark,
  },
  orderInfoContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 16,
    backgroundColor: COLORS.primary + "15", // 15% opacity
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 6,
  },
  orderReference: {
    fontSize: 14,
    color: COLORS.primary,
    marginLeft: 8,
  },
  ratingContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 16,
  },
  ratingLabel: {
    fontSize: 16,
    marginRight: 10,
    color: COLORS.dark,
  },
  starsContainer: {
    flexDirection: "row",
  },
  starButton: {
    marginRight: 5,
  },
  input: {
    borderWidth: 1,
    borderColor: "#e0e0e0",
    borderRadius: 8,
    padding: 12,
    marginBottom: 16,
    minHeight: 100,
    textAlignVertical: "top",
    color: COLORS.dark,
    backgroundColor: "#f9f9f9",
  },
  submitButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 8,
    padding: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  disabledButton: {
    backgroundColor: COLORS.muted,
  },
  submitButtonText: {
    color: COLORS.light,
    fontWeight: "bold",
    fontSize: 16,
  },
});

export default FeedbackForm;
