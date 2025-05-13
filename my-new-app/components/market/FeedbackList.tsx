import React, { useState, useEffect } from "react";
import {
  View,
  StyleSheet,
  FlatList,
  TextInput,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "@/contexts/AuthContext";
import FeedbackService from "@/services/FeedbackService";

interface FeedbackResponse {
  feedback_id: number;
  response_text: string;
  responded_by: number | undefined;
}

interface Feedback {
  feedback_id: number;
  user_id: number;
  product_id: number | null;
  order_id?: number | null;
  feedback_text: string;
  rating: number;
  created_at: string;
  status: string;
  user_name?: string;
  product_name?: string;
  order_reference?: string;
  response?: {
    response_id: number;
    response_text: string;
    responded_by: number;
    responder_name: string;
    response_date: string;
  };
}

interface FeedbackListProps {
  farmerId?: number; // For filtering feedback by farmer
  productId?: number; // For filtering feedback by product
  orderId?: number; // For filtering feedback by order
  showResponseForm?: boolean; // Whether to show response form for farmers
  refreshTrigger?: number; // To trigger refresh of feedback list
}

const FeedbackList: React.FC<FeedbackListProps> = ({
  farmerId,
  productId,
  orderId,
  showResponseForm = false,
  refreshTrigger = 0,
}) => {
  const [feedbackList, setFeedbackList] = useState<Feedback[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [responseText, setResponseText] = useState<{ [key: number]: string }>(
    {}
  );
  const [isSubmitting, setIsSubmitting] = useState<{ [key: number]: boolean }>(
    {}
  );
  const [error, setError] = useState<string | null>(null);
  const { user } = useAuth();

  const fetchFeedback = async () => {
    setIsLoading(true);
    setError(null);

    try {
      let response;
      if (farmerId) {
        response = await FeedbackService.getFeedbackByFarmer(farmerId);
      } else if (productId) {
        response = await FeedbackService.getFeedbackByProduct(productId);
      } else if (orderId) {
        response = await FeedbackService.getFeedbackByOrder(orderId);
      } else {
        response = await FeedbackService.getAllFeedback();
      }

      if (response.success) {
        setFeedbackList(response.feedback || []);
      } else {
        console.warn("Failed to fetch feedback:", response.message);
        setError(response.message || "Failed to load reviews");
        setFeedbackList([]);
      }
    } catch (error) {
      console.error("Error fetching feedback:", error);
      setError("Network error while loading reviews");
      setFeedbackList([]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRetry = () => {
    fetchFeedback();
  };

  useEffect(() => {
    fetchFeedback();
  }, [farmerId, productId, orderId, refreshTrigger]);

  const handleSubmitResponse = async (feedbackId: number) => {
    if (!responseText[feedbackId]?.trim()) {
      Alert.alert("Error", "Please enter your response");
      return;
    }

    setIsSubmitting({ ...isSubmitting, [feedbackId]: true });

    try {
      const response = await FeedbackService.submitFeedbackResponse({
        feedback_id: feedbackId,
        response_text: responseText[feedbackId],
        responded_by: user?.user_id,
      });

      if (response.success) {
        setResponseText({ ...responseText, [feedbackId]: "" });
        Alert.alert("Success", "Your response has been submitted successfully");
        fetchFeedback(); // Refresh the list to show the new response
      } else {
        Alert.alert("Error", response.message || "Failed to submit response");
      }
    } catch (error) {
      console.error("Error submitting response:", error);
      Alert.alert("Error", "An unexpected error occurred");
    } finally {
      setIsSubmitting({ ...isSubmitting, [feedbackId]: false });
    }
  };

  const renderStars = (rating: number) => {
    return (
      <View style={styles.starsContainer}>
        {[1, 2, 3, 4, 5].map((star) => (
          <Ionicons
            key={star}
            name={rating >= star ? "star" : "star-outline"}
            size={18}
            color={rating >= star ? COLORS.secondary : COLORS.muted}
            style={styles.starIcon}
          />
        ))}
      </View>
    );
  };

  const renderFeedbackItem = ({ item }: { item: Feedback }) => (
    <View style={styles.feedbackItem}>
      <View style={styles.feedbackHeader}>
        <View style={styles.userInfo}>
          <Ionicons
            name="person-circle-outline"
            size={24}
            color={COLORS.muted}
          />
          <ThemedText style={styles.userName}>
            {item.user_name || "Anonymous"}
          </ThemedText>
        </View>
        {renderStars(item.rating)}
      </View>

      <ThemedText style={styles.feedbackDate}>
        {new Date(item.created_at).toLocaleDateString()}
      </ThemedText>

      {item.product_name && (
        <View style={styles.productTag}>
          <Ionicons name="leaf-outline" size={14} color={COLORS.primary} />
          <ThemedText style={styles.productName}>
            {item.product_name}
          </ThemedText>
        </View>
      )}

      {item.order_reference && (
        <View style={styles.orderTag}>
          <Ionicons name="receipt-outline" size={14} color={COLORS.primary} />
          <ThemedText style={styles.orderReference}>
            Order: {item.order_reference}
          </ThemedText>
        </View>
      )}

      <ThemedText style={styles.feedbackText}>{item.feedback_text}</ThemedText>

      {item.response && (
        <View style={styles.responseContainer}>
          <View style={styles.responseHeader}>
            <Ionicons
              name="chatbubble-outline"
              size={16}
              color={COLORS.primary}
            />
            <ThemedText style={styles.responseHeaderText}>
              Farmer Response
            </ThemedText>
          </View>
          <ThemedText style={styles.responseText}>
            {item.response.response_text}
          </ThemedText>
          <ThemedText style={styles.responseDate}>
            - {item.response.responder_name},{" "}
            {new Date(item.response.response_date).toLocaleDateString()}
          </ThemedText>
        </View>
      )}

      {showResponseForm && !item.response && user?.role_id === 2 && (
        <View style={styles.responseFormContainer}>
          <TextInput
            style={styles.responseInput}
            placeholder="Write your response..."
            placeholderTextColor={COLORS.muted}
            multiline
            value={responseText[item.feedback_id] || ""}
            onChangeText={(text) =>
              setResponseText({ ...responseText, [item.feedback_id]: text })
            }
            numberOfLines={3}
          />

          <TouchableOpacity
            style={[
              styles.responseButton,
              !responseText[item.feedback_id]?.trim() && styles.disabledButton,
            ]}
            onPress={() => handleSubmitResponse(item.feedback_id)}
            disabled={
              isSubmitting[item.feedback_id] ||
              !responseText[item.feedback_id]?.trim()
            }
          >
            {isSubmitting[item.feedback_id] ? (
              <ActivityIndicator size="small" color={COLORS.light} />
            ) : (
              <ThemedText style={styles.responseButtonText}>
                Submit Response
              </ThemedText>
            )}
          </TouchableOpacity>
        </View>
      )}
    </View>
  );

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <ThemedText style={styles.loadingText}>Loading reviews...</ThemedText>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.errorContainer}>
        <Ionicons name="alert-circle-outline" size={48} color={COLORS.accent} />
        <ThemedText style={styles.errorText}>{error}</ThemedText>
        <TouchableOpacity style={styles.retryButton} onPress={handleRetry}>
          <Ionicons name="refresh" size={18} color={COLORS.light} />
          <ThemedText style={styles.retryButtonText}>Retry</ThemedText>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ThemedText style={styles.title}>
        {feedbackList.length > 0 ? "Customer Reviews" : ""}
      </ThemedText>

      {feedbackList.length > 0 ? (
        <FlatList
          data={feedbackList}
          renderItem={renderFeedbackItem}
          keyExtractor={(item) => item.feedback_id.toString()}
          contentContainerStyle={styles.listContent}
        />
      ) : (
        <View style={styles.emptyContainer}>
          <Ionicons
            name="chatbubble-ellipses-outline"
            size={48}
            color={COLORS.muted}
          />
          <ThemedText style={styles.emptyText}>
            No reviews yet for this product
          </ThemedText>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  title: {
    fontSize: 20,
    fontWeight: "bold",
    marginBottom: 16,
    color: COLORS.primary,
    paddingHorizontal: 16,
  },
  listContent: {
    padding: 16,
  },
  feedbackItem: {
    backgroundColor: COLORS.light,
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#eeeeee",
  },
  feedbackHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  userInfo: {
    flexDirection: "row",
    alignItems: "center",
  },
  userName: {
    fontSize: 16,
    fontWeight: "bold",
    marginLeft: 8,
    color: COLORS.dark,
  },
  starsContainer: {
    flexDirection: "row",
  },
  starIcon: {
    marginLeft: 2,
  },
  feedbackDate: {
    fontSize: 12,
    color: COLORS.muted,
    marginBottom: 8,
  },
  productTag: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: COLORS.primary + "15", // 15% opacity
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
    alignSelf: "flex-start",
    marginBottom: 8,
  },
  productName: {
    fontSize: 12,
    color: COLORS.primary,
    marginLeft: 4,
  },
  orderTag: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: COLORS.primary + "15", // 15% opacity
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
    alignSelf: "flex-start",
    marginBottom: 8,
  },
  orderReference: {
    fontSize: 12,
    color: COLORS.primary,
    marginLeft: 4,
  },
  feedbackText: {
    fontSize: 16,
    lineHeight: 22,
    color: COLORS.text,
  },
  responseContainer: {
    marginTop: 12,
    backgroundColor: COLORS.primary + "10", // 10% opacity
    padding: 12,
    borderRadius: 8,
  },
  responseHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  responseHeaderText: {
    fontSize: 14,
    fontWeight: "bold",
    color: COLORS.primary,
    marginLeft: 6,
  },
  responseText: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.text,
  },
  responseDate: {
    fontSize: 12,
    color: COLORS.muted,
    marginTop: 4,
    fontStyle: "italic",
    textAlign: "right",
  },
  responseFormContainer: {
    marginTop: 16,
  },
  responseInput: {
    borderWidth: 1,
    borderColor: "#e0e0e0",
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    minHeight: 80,
    textAlignVertical: "top",
    color: COLORS.dark,
    backgroundColor: "#f9f9f9",
  },
  responseButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 8,
    padding: 12,
    alignItems: "center",
    justifyContent: "center",
  },
  disabledButton: {
    backgroundColor: COLORS.muted,
  },
  responseButtonText: {
    color: COLORS.light,
    fontWeight: "bold",
    fontSize: 14,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  loadingText: {
    marginTop: 10,
    color: COLORS.muted,
  },
  emptyContainer: {
    alignItems: "center",
    justifyContent: "center",
    padding: 40,
  },
  emptyText: {
    marginTop: 12,
    fontSize: 16,
    color: COLORS.muted,
  },
  errorContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  errorText: {
    marginTop: 12,
    marginBottom: 16,
    fontSize: 16,
    color: COLORS.accent,
    textAlign: "center",
  },
  retryButton: {
    backgroundColor: COLORS.primary,
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
  },
  retryButtonText: {
    marginLeft: 8,
    color: COLORS.light,
    fontWeight: "bold",
  },
});

export default FeedbackList;
