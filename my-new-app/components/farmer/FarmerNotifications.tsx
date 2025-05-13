import React from "react";
import { StyleSheet, View, TouchableOpacity, FlatList } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";

interface Notification {
  notification_id: number;
  message: string;
  is_read: boolean;
  created_at: string;
  type: string;
  reference_id: number | null;
}

interface NotificationsProps {
  notifications: Notification[];
  navigateToNotifications: () => void;
}

export function FarmerNotifications({
  notifications,
  navigateToNotifications,
}: NotificationsProps) {
  // Function to format the time elapsed
  const getTimeElapsed = (dateString: string) => {
    const now = new Date();
    const createdAt = new Date(dateString);
    const diffMs = now.getTime() - createdAt.getTime();

    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) {
      return `${diffMins} min${diffMins !== 1 ? "s" : ""} ago`;
    } else if (diffHours < 24) {
      return `${diffHours} hour${diffHours !== 1 ? "s" : ""} ago`;
    } else if (diffDays < 7) {
      return `${diffDays} day${diffDays !== 1 ? "s" : ""} ago`;
    } else {
      return createdAt.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
      });
    }
  };

  // Function to get icon based on notification type
  const getNotificationIcon = (type: string) => {
    switch (type) {
      case "product_approved":
        return {
          name: "checkmark-circle",
          color: "#4CAF50", // Green color instead of COLORS.success
          bg: "rgba(76, 175, 80, 0.1)",
        };
      case "product_rejected":
        return {
          name: "close-circle",
          color: "#F44336", // Red color instead of COLORS.danger
          bg: "rgba(244, 67, 54, 0.1)",
        };
      case "order_received":
        return {
          name: "cart",
          color: COLORS.accent,
          bg: "rgba(230, 81, 0, 0.1)",
        };
      case "payment_received":
        return { name: "cash", color: "#6200EA", bg: "rgba(98, 0, 234, 0.1)" };
      case "message_received":
        return {
          name: "chatbubble",
          color: "#0288D1",
          bg: "rgba(2, 136, 209, 0.1)",
        };
      default:
        return {
          name: "notifications",
          color: COLORS.primary,
          bg: "rgba(27, 94, 32, 0.1)",
        };
    }
  };

  // Count unread notifications
  const unreadCount = notifications.filter((n) => !n.is_read).length;

  const renderNotificationItem = ({ item }: { item: Notification }) => {
    const icon = getNotificationIcon(item.type);

    return (
      <TouchableOpacity
        style={[styles.notificationItem, !item.is_read && styles.unreadItem]}
      >
        <View
          style={[
            styles.notificationIconContainer,
            { backgroundColor: icon.bg },
          ]}
        >
          <Ionicons name={icon.name as any} size={20} color={icon.color} />
        </View>

        <View style={styles.notificationContent}>
          <ThemedText style={styles.notificationMessage}>
            {item.message}
          </ThemedText>
          <ThemedText style={styles.notificationDate}>
            {getTimeElapsed(item.created_at)}
          </ThemedText>
        </View>

        {!item.is_read && <View style={styles.unreadIndicator} />}
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.headerRow}>
        <View style={styles.titleContainer}>
          <ThemedText style={styles.title}>Notifications</ThemedText>
          {unreadCount > 0 && (
            <View style={styles.badgeContainer}>
              <ThemedText style={styles.badgeText}>{unreadCount}</ThemedText>
            </View>
          )}
        </View>

        <TouchableOpacity
          style={styles.viewAllButton}
          onPress={navigateToNotifications}
          accessibilityLabel="View all notifications"
        >
          <ThemedText style={styles.viewAllText}>View All</ThemedText>
          <Ionicons name="chevron-forward" size={16} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      {notifications.length > 0 ? (
        <View style={styles.notificationsListContainer}>
          <FlatList
            data={notifications.slice(0, 3)} // Show only up to 3 notifications
            renderItem={renderNotificationItem}
            keyExtractor={(item) => item.notification_id.toString()}
            scrollEnabled={false}
            contentContainerStyle={styles.notificationsList}
          />

          {notifications.length > 3 && (
            <TouchableOpacity
              style={styles.moreNotificationsButton}
              onPress={navigateToNotifications}
            >
              <ThemedText style={styles.moreNotificationsText}>
                {notifications.length - 3} more notification
                {notifications.length - 3 !== 1 ? "s" : ""}
              </ThemedText>
            </TouchableOpacity>
          )}
        </View>
      ) : (
        <View style={styles.emptyNotificationsContainer}>
          <View style={styles.emptyIconContainer}>
            <Ionicons name="notifications-off" size={32} color="#ccc" />
          </View>
          <ThemedText style={styles.emptyTitle}>All Caught Up!</ThemedText>
          <ThemedText style={styles.emptyText}>
            You don't have any notifications at this moment
          </ThemedText>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  headerRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  titleContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  badgeContainer: {
    backgroundColor: COLORS.accent,
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 3,
    marginLeft: 8,
  },
  badgeText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "bold",
  },
  viewAllButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(27, 94, 32, 0.08)",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
  },
  viewAllText: {
    fontSize: 14,
    color: COLORS.primary,
    marginRight: 2,
    fontWeight: "600",
  },
  notificationsListContainer: {
    marginTop: 4,
  },
  notificationsList: {
    paddingTop: 4,
  },
  notificationItem: {
    flexDirection: "row",
    padding: 12,
    borderRadius: 12,
    marginBottom: 12,
    alignItems: "center",
    backgroundColor: "#ffffff",
    borderLeftWidth: 0,
    borderWidth: 1,
    borderColor: "#f0f0f0",
  },
  unreadItem: {
    backgroundColor: "rgba(27, 94, 32, 0.04)",
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
  },
  notificationIconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  notificationContent: {
    flex: 1,
  },
  notificationMessage: {
    fontSize: 14,
    color: COLORS.text,
    fontWeight: "500",
    marginBottom: 4,
  },
  notificationDate: {
    fontSize: 12,
    color: COLORS.muted,
  },
  unreadIndicator: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: COLORS.primary,
    marginLeft: 8,
  },
  moreNotificationsButton: {
    alignItems: "center",
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#f0f0f0",
    marginTop: 4,
  },
  moreNotificationsText: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: "600",
  },
  emptyNotificationsContainer: {
    padding: 24,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#f9f9f9",
    borderRadius: 12,
  },
  emptyIconContainer: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: "bold",
    color: COLORS.text,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 14,
    color: COLORS.muted,
    textAlign: "center",
  },
});
