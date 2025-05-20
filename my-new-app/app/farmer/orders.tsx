import React, { useState, useEffect, useCallback } from "react";
import {
  SafeAreaView,
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  ScrollView,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TextInput,
  Alert,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { Redirect, useRouter } from "expo-router";
import IPConfig from "@/constants/IPConfig";

// Order type definition
interface Order {
  order_id: number;
  customer_name: string;
  order_date: string;
  total_amount: number;
  status: string;
  payment_status: string;
  items_count: number;
  pickup_date?: string;
  pickup_time?: string;
  // Optional fields that might be present in detailed responses
  consumer_id?: number;
  consumer_contact?: string;
  pickup_details?: string;
  items?: OrderItem[];
  payment?: PaymentInfo;
  pickup?: PickupInfo;
}

// Add these interfaces to match the API response structure
interface OrderItem {
  order_item_id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  price: number;
  total: number;
  unit_type: string;
  image?: string;
}

interface PaymentInfo {
  payment_id: number;
  method: string;
  status: string;
  date: string;
  amount: number;
  reference: string;
}

interface PickupInfo {
  pickup_id: number;
  status: string;
  date: string;
  location: string;
  notes?: string;
  office_location?: string;
  contact_person?: string;
}

// Order details type
interface OrderDetail {
  order_detail_id: number;
  product_name: string;
  quantity: number;
  unit_price: number;
  subtotal: number;
  unit_type: string;
}

// Order statistics
interface OrderStats {
  total: number;
  pending: number;
  confirmed: number;
  completed: number;
  canceled: number;
}

export default function FarmerOrders() {
  const { isAuthenticated, isFarmer, user } = useAuth();
  const router = useRouter();

  // State variables
  const [orders, setOrders] = useState<Order[]>([]);
  const [filteredOrders, setFilteredOrders] = useState<Order[]>([]);
  const [stats, setStats] = useState<OrderStats>({
    total: 0,
    pending: 0,
    confirmed: 0,
    completed: 0,
    canceled: 0,
  });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null);
  const [selectedOrderDetails, setSelectedOrderDetails] = useState<
    OrderDetail[]
  >([]);
  const [showOrderDetails, setShowOrderDetails] = useState(false);

  // Function to fetch orders
  const fetchOrders = useCallback(async () => {
    if (!user?.user_id) return;

    try {
      setLoading(true);
      // Use the farmer_orders.php endpoint which now properly handles both GET and POST
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_orders.php?farmer_id=${user.user_id}`
      );

      // Update to fetch stats from the farmer_orders.php endpoint
      // This will ensure we get accurate stats specific to this farmer
      const statsResponse = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_orders.php?farmer_id=${user.user_id}&stats=true`
      );

      const data = await response.json();
      const statsData = await statsResponse.json();

      if (data.success) {
        // Ensure we handle empty orders array properly
        setOrders(data.orders || []);
        setFilteredOrders(data.orders || []);

        // Calculate statistics directly from orders data if stats endpoint fails
        if (!statsData.success) {
          const orderStats = calculateOrderStats(data.orders || []);
          setStats(orderStats);
        }
      } else {
        console.error("Error fetching orders:", data.message);
        Alert.alert(
          "Error",
          data.message || "Could not load orders. Please try again later."
        );
      }

      if (statsData.success) {
        setStats(statsData.stats);
      } else {
        console.error("Error fetching order statistics:", statsData?.message);
      }
    } catch (error) {
      console.error("Error fetching orders:", error);
      Alert.alert("Error", "Could not load orders. Please try again later.");
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [user?.user_id]);

  // Helper function to calculate order statistics from orders array
  const calculateOrderStats = (orders: Order[]): OrderStats => {
    const stats: OrderStats = {
      total: orders.length,
      pending: 0,
      confirmed: 0,
      completed: 0,
      canceled: 0,
    };

    orders.forEach((order) => {
      switch (order.status?.toLowerCase()) {
        case "pending":
          stats.pending++;
          break;
        case "confirmed":
          stats.confirmed++;
          break;
        case "completed":
          stats.completed++;
          break;
        case "canceled":
          stats.canceled++;
          break;
      }
    });

    return stats;
  };

  // Function to fetch order details
  const fetchOrderDetails = async (orderId: number) => {
    try {
      setLoading(true);
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_orders.php?order_id=${orderId}&details=true`
      );
      const data = await response.json();

      if (data.success) {
        setSelectedOrderDetails(data.order_details || []);
      } else {
        console.error("Error fetching order details:", data.message);
        Alert.alert("Error", "Could not load order details. Please try again.");
      }
    } catch (error) {
      console.error("Error fetching order details:", error);
      Alert.alert("Error", "Could not load order details. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Function to update order status
  const updateOrderStatus = async (orderId: number, status: string) => {
    try {
      setLoading(true);
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_orders.php`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            order_id: orderId,
            status: status,
            farmer_id: user?.user_id,
            action: "update_status",
          }),
        }
      );

      const data = await response.json();

      if (data.success) {
        Alert.alert(
          "Success",
          `Order #${orderId} status updated to ${status}.`
        );
        fetchOrders();
        if (selectedOrderId === orderId) {
          setShowOrderDetails(false);
          setSelectedOrderId(null);
        }
      } else {
        Alert.alert("Error", data.message || "Failed to update order status.");
      }
    } catch (error) {
      console.error("Error updating order status:", error);
      Alert.alert("Error", "Failed to update order status. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Filter orders based on search query and status filter
  useEffect(() => {
    if (orders.length === 0) {
      setFilteredOrders([]);
      return;
    }

    let filtered = [...orders];

    // Apply search filter with more robust null checks and improved search
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter((order) => {
        // Check order ID
        if (order.order_id && order.order_id.toString().includes(query)) {
          return true;
        }

        // Check customer name
        if (
          order.customer_name &&
          order.customer_name.toLowerCase().includes(query)
        ) {
          return true;
        }

        // Check order date
        if (order.order_date) {
          const formattedDate = formatDate(order.order_date).toLowerCase();
          if (formattedDate.includes(query)) {
            return true;
          }
        }

        return false;
      });
    }

    // Apply status filter with null check
    if (statusFilter) {
      filtered = filtered.filter(
        (order) =>
          order.status &&
          order.status.toLowerCase() === statusFilter.toLowerCase()
      );
    }

    setFilteredOrders(filtered);
  }, [searchQuery, statusFilter, orders]);

  // Load data on component mount
  useEffect(() => {
    if (user?.user_id) {
      fetchOrders();
    }
  }, [fetchOrders, user]);

  // Handle refresh
  const onRefresh = useCallback(() => {
    setRefreshing(true);
    fetchOrders();
  }, [fetchOrders]);

  // View order details
  const viewOrderDetails = (orderId: number) => {
    setSelectedOrderId(orderId);
    fetchOrderDetails(orderId);
    setShowOrderDetails(true);
  };

  // Format date for display
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(date);
  };

  // Format price for display
  const formatPrice = (price: number) => {
    return `₱${price.toFixed(2)}`;
  };

  // Get status color for display
  const getStatusColor = (status: string) => {
    if (!status) return "#9E9E9E"; // Default grey for undefined status

    switch (status.toLowerCase()) {
      case "pending":
        return "#FFC107"; // Yellow
      case "processing":
        return "#FF9800"; // Orange
      case "ready":
        return "#2196F3"; // Blue
      case "completed":
        return "#4CAF50"; // Green
      case "canceled":
        return "#F44336"; // Red
      case "paid":
        return "#4CAF50"; // Green
      default:
        return "#9E9E9E"; // Grey
    }
  };

  // Check authentication
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect consumers to consumer dashboard
  if (!isFarmer) {
    return <Redirect href="/consumer/dashboard" />;
  }

  // Order details modal
  const renderOrderDetailsModal = () => {
    if (!showOrderDetails || !selectedOrderId) return null;

    const selectedOrder = orders.find(
      (order) => order.order_id === selectedOrderId
    );
    if (!selectedOrder) return null;

    return (
      <View style={styles.modalOverlay}>
        <View style={styles.modalContent}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>
              Order #{selectedOrder.order_id}
            </Text>
            <TouchableOpacity onPress={() => setShowOrderDetails(false)}>
              <Ionicons name="close" size={24} color="#333" />
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.modalBody}>
            <View style={styles.orderInfo}>
              <Text style={styles.sectionTitle}>Order Information</Text>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Customer:</Text>
                <Text style={styles.infoValue}>
                  {selectedOrder.customer_name}
                </Text>
              </View>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Order Date:</Text>
                <Text style={styles.infoValue}>
                  {formatDate(selectedOrder.order_date)}
                </Text>
              </View>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Status:</Text>
                <View
                  style={[
                    styles.statusBadge,
                    { backgroundColor: getStatusColor(selectedOrder.status) },
                  ]}
                >
                  <Text style={styles.statusText}>{selectedOrder.status}</Text>
                </View>
              </View>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Payment:</Text>
                <View
                  style={[
                    styles.statusBadge,
                    {
                      backgroundColor: getStatusColor(
                        selectedOrder.payment_status
                      ),
                    },
                  ]}
                >
                  <Text style={styles.statusText}>
                    {selectedOrder.payment_status}
                  </Text>
                </View>
              </View>
              {selectedOrder.pickup_date && (
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Pickup:</Text>
                  <Text style={styles.infoValue}>
                    {selectedOrder.pickup_date} {selectedOrder.pickup_time}
                  </Text>
                </View>
              )}
            </View>

            <Text style={styles.sectionTitle}>Order Items</Text>
            {loading ? (
              <ActivityIndicator
                size="small"
                color={COLORS.primary}
                style={styles.loadingIndicator}
              />
            ) : (
              <>
                {selectedOrderDetails.map((item) => (
                  <View key={item.order_detail_id} style={styles.orderItem}>
                    <View style={styles.orderItemHeader}>
                      <Text style={styles.orderItemName}>
                        {item.product_name}
                      </Text>
                      <Text style={styles.orderItemPrice}>
                        {formatPrice(item.subtotal)}
                      </Text>
                    </View>
                    <View style={styles.orderItemDetails}>
                      <Text style={styles.orderItemQuantity}>
                        {item.quantity} {item.unit_type} x{" "}
                        {formatPrice(item.unit_price)}
                      </Text>
                    </View>
                  </View>
                ))}

                <View style={styles.totalContainer}>
                  <Text style={styles.totalLabel}>Total Amount:</Text>
                  <Text style={styles.totalValue}>
                    {formatPrice(selectedOrder.total_amount)}
                  </Text>
                </View>
              </>
            )}

            {/* Order Actions */}
            {selectedOrder.status === "pending" && (
              <View style={styles.actionButtons}>
                <TouchableOpacity
                  style={[styles.actionButton, styles.confirmButton]}
                  onPress={() =>
                    updateOrderStatus(selectedOrderId, "processing")
                  }
                >
                  <Ionicons name="checkmark-circle" size={18} color="#fff" />
                  <Text style={styles.actionButtonText}>Process Order</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.actionButton, styles.cancelButton]}
                  onPress={() => updateOrderStatus(selectedOrderId, "canceled")}
                >
                  <Ionicons name="close-circle" size={18} color="#fff" />
                  <Text style={styles.actionButtonText}>Cancel Order</Text>
                </TouchableOpacity>
              </View>
            )}

            {selectedOrder.status === "processing" && (
              <TouchableOpacity
                style={[styles.actionButton, styles.confirmButton]}
                onPress={() => updateOrderStatus(selectedOrderId, "ready")}
              >
                <Ionicons name="checkmark-done-circle" size={18} color="#fff" />
                <Text style={styles.actionButtonText}>Mark as Ready</Text>
              </TouchableOpacity>
            )}

            {selectedOrder.status === "ready" && (
              <TouchableOpacity
                style={[styles.actionButton, styles.completeButton]}
                onPress={() => updateOrderStatus(selectedOrderId, "completed")}
              >
                <Ionicons name="checkmark-done-circle" size={18} color="#fff" />
                <Text style={styles.actionButtonText}>Mark as Completed</Text>
              </TouchableOpacity>
            )}
          </ScrollView>
        </View>
      </View>
    );
  };

  // Render order item
  const renderOrderItem = ({ item }: { item: Order }) => (
    <TouchableOpacity
      style={styles.orderCard}
      activeOpacity={0.7}
      onPress={() => viewOrderDetails(item.order_id)}
    >
      <View style={styles.orderHeader}>
        <View style={styles.orderIdContainer}>
          <Text style={styles.orderId}>Order #{item.order_id}</Text>
        </View>
        <View
          style={[
            styles.statusBadge,
            { backgroundColor: getStatusColor(item.status) },
          ]}
        >
          <Text style={styles.statusText}>{item.status || "Unknown"}</Text>
        </View>
      </View>

      <View style={styles.orderContent}>
        <View style={styles.orderRow}>
          <Ionicons name="person-outline" size={16} color="#666" />
          <Text style={styles.orderText}>
            {item.customer_name || "Unknown Customer"}
          </Text>
        </View>
        <View style={styles.orderRow}>
          <Ionicons name="calendar-outline" size={16} color="#666" />
          <Text style={styles.orderText}>
            {item.order_date ? formatDate(item.order_date) : "No date"}
          </Text>
        </View>
        <View style={styles.orderRow}>
          <Ionicons name="cart-outline" size={16} color="#666" />
          <Text style={styles.orderText}>{item.items_count || 0} item(s)</Text>
        </View>
      </View>

      <View style={styles.orderFooter}>
        <View style={styles.paymentStatus}>
          <Text style={styles.paymentLabel}>Payment: </Text>
          <View
            style={[
              styles.paymentBadge,
              { backgroundColor: getStatusColor(item.payment_status) },
            ]}
          >
            <Text style={styles.paymentText}>
              {item.payment_status || "Unknown"}
            </Text>
          </View>
        </View>
        <Text style={styles.orderAmount}>
          {formatPrice(item.total_amount || 0)}
        </Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.replace("/farmer/dashboard")}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>My Orders</Text>
        <View style={{ width: 24 }} />
      </View>

      {/* Stats Section */}
      <View style={styles.statsContainer}>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.total || 0}</Text>
          <Text style={styles.statLabel}>Total</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.pending || 0}</Text>
          <Text style={styles.statLabel}>Pending</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.confirmed || 0}</Text>
          <Text style={styles.statLabel}>Confirmed</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.completed || 0}</Text>
          <Text style={styles.statLabel}>Completed</Text>
        </View>
      </View>

      {/* Search and Filter Section */}
      <View style={styles.searchFilterContainer}>
        <View style={styles.searchContainer}>
          <Ionicons
            name="search"
            size={18}
            color="#666"
            style={styles.searchIcon}
          />
          <TextInput
            style={styles.searchInput}
            placeholder="Search orders..."
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery ? (
            <TouchableOpacity onPress={() => setSearchQuery("")}>
              <Ionicons name="close-circle" size={18} color="#666" />
            </TouchableOpacity>
          ) : null}
        </View>

        <View style={styles.filterContainer}>
          <Text style={styles.filterLabel}>Filter: </Text>
          {/* Replace ScrollView with View to avoid nesting virtualized lists */}
          <View style={styles.filterButtonsContainer}>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === null && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter(null)}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === null && styles.activeFilterText,
                ]}
              >
                All
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === "pending" && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter("pending")}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === "pending" && styles.activeFilterText,
                ]}
              >
                Pending
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === "processing" && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter("processing")}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === "processing" && styles.activeFilterText,
                ]}
              >
                Processing
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === "ready" && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter("ready")}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === "ready" && styles.activeFilterText,
                ]}
              >
                Ready
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === "completed" && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter("completed")}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === "completed" && styles.activeFilterText,
                ]}
              >
                Completed
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.filterButton,
                statusFilter === "canceled" && styles.activeFilterButton,
              ]}
              onPress={() => setStatusFilter("canceled")}
            >
              <Text
                style={[
                  styles.filterButtonText,
                  statusFilter === "canceled" && styles.activeFilterText,
                ]}
              >
                Canceled
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      {/* Orders List */}
      {loading && !refreshing ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading orders...</Text>
        </View>
      ) : filteredOrders.length > 0 ? (
        <FlatList
          data={filteredOrders}
          renderItem={renderOrderItem}
          keyExtractor={(item) => item.order_id.toString()}
          contentContainerStyle={styles.ordersList}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        />
      ) : (
        <View style={styles.emptyContainer}>
          <Ionicons name="cart-outline" size={64} color={COLORS.muted} />
          <Text style={styles.emptyText}>No orders found</Text>
          <Text style={styles.emptySubText}>
            {searchQuery || statusFilter
              ? "Try adjusting your search or filters"
              : "You have not received any orders yet"}
          </Text>
        </View>
      )}

      {/* Order Details Modal */}
      {renderOrderDetailsModal()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: COLORS.primary,
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: "bold",
    color: COLORS.light,
  },
  backButton: {
    padding: 8,
  },
  statsContainer: {
    flexDirection: "row",
    justifyContent: "space-around",
    paddingVertical: 16,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  statCard: {
    alignItems: "center",
    paddingHorizontal: 8,
  },
  statNumber: {
    fontSize: 22,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  statLabel: {
    fontSize: 12,
    color: "#666",
    marginTop: 4,
  },
  searchFilterContainer: {
    padding: 16,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f0f0f0",
    borderRadius: 8,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    height: 40,
    fontSize: 16,
  },
  filterContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  filterLabel: {
    fontSize: 14,
    color: "#666",
    marginRight: 8,
  },
  filterButton: {
    paddingHorizontal: 16,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: "#f0f0f0",
    marginRight: 8,
  },
  activeFilterButton: {
    backgroundColor: COLORS.primary,
  },
  filterButtonText: {
    color: "#666",
  },
  activeFilterText: {
    color: "#fff",
    fontWeight: "bold",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  loadingText: {
    marginTop: 10,
    color: "#666",
  },
  ordersList: {
    padding: 16,
    paddingBottom: 40,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#666",
    marginTop: 16,
  },
  emptySubText: {
    fontSize: 14,
    color: "#999",
    textAlign: "center",
    marginTop: 8,
  },
  orderCard: {
    backgroundColor: "#fff",
    borderRadius: 8,
    marginBottom: 16,
    padding: 16,
    elevation: 2,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  orderHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  orderIdContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  orderId: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
  },
  orderContent: {
    marginBottom: 12,
  },
  orderRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
  },
  orderText: {
    marginLeft: 8,
    fontSize: 14,
    color: "#666",
  },
  orderFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    borderTopWidth: 1,
    borderTopColor: "#eee",
    paddingTop: 12,
  },
  paymentStatus: {
    flexDirection: "row",
    alignItems: "center",
  },
  paymentLabel: {
    fontSize: 14,
    color: "#666",
  },
  paymentBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
  },
  paymentText: {
    fontSize: 12,
    color: "#fff",
    fontWeight: "500",
  },
  orderAmount: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  statusText: {
    fontSize: 12,
    color: "#fff",
    fontWeight: "bold",
  },
  modalOverlay: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "center",
    alignItems: "center",
  },
  modalContent: {
    width: "90%",
    maxHeight: "80%",
    backgroundColor: "#fff",
    borderRadius: 8,
    overflow: "hidden",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#333",
  },
  modalBody: {
    padding: 16,
    maxHeight: 500,
  },
  orderInfo: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  infoLabel: {
    width: 80,
    fontSize: 14,
    color: "#666",
  },
  infoValue: {
    fontSize: 14,
    color: "#333",
    flex: 1,
  },
  orderItem: {
    backgroundColor: "#f9f9f9",
    padding: 12,
    borderRadius: 8,
    marginBottom: 8,
  },
  orderItemHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 4,
  },
  orderItemName: {
    fontSize: 14,
    fontWeight: "bold",
    color: "#333",
    flex: 1,
  },
  orderItemPrice: {
    fontSize: 14,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  orderItemDetails: {
    flexDirection: "row",
    justifyContent: "space-between",
  },
  orderItemQuantity: {
    fontSize: 14,
    color: "#666",
  },
  totalContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: "#e0e0e0",
  },
  totalLabel: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
  },
  totalValue: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  loadingIndicator: {
    marginVertical: 20,
  },
  actionButtons: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginTop: 20,
  },
  actionButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    marginTop: 16,
    marginBottom: 8,
  },
  confirmButton: {
    backgroundColor: "#2196F3",
    flex: 1,
    marginRight: 8,
  },
  cancelButton: {
    backgroundColor: "#F44336",
    flex: 1,
    marginLeft: 8,
  },
  completeButton: {
    backgroundColor: "#4CAF50",
    flex: 1,
  },
  actionButtonText: {
    color: "#fff",
    fontWeight: "bold",
    marginLeft: 8,
  },
  filterButtonsContainer: {
    flexDirection: "row",
    flexWrap: "wrap",
  },
});
