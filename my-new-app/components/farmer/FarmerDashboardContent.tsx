import React from "react";
import {
  StyleSheet,
  View,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { FarmerStats } from "./FarmerStats";
import { FarmerQuickActions } from "./FarmerQuickActions";
import FarmerRecentProducts from "./FarmerRecentProducts";
import { FarmerNotifications } from "./FarmerNotifications";
import { FarmerMarketInsights } from "./FarmerMarketInsights";
import { FarmerTips } from "./FarmerTips";
import { FarmerContactSupport } from "./FarmerContactSupport";
import { COLORS } from "@/constants/Colors";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withSpring,
} from "react-native-reanimated";

// Types
interface UserData {
  first_name: string;
  last_name: string;
  email: string;
  user_id?: number;
  role_id?: number;
  [key: string]: any;
}

interface FarmDetails {
  detail_id: number;
  farm_name: string;
  farm_type: string;
  farm_location: string;
  farm_size: number;
  certifications: string;
  crop_varieties: string;
}

interface Product {
  product_id: number;
  name: string;
  description: string;
  price: number;
  status: "pending" | "approved" | "rejected";
  image: string | null;
  stock: number;
  unit_type: string;
  created_at: string;
  updated_at: string;
}

interface Notification {
  notification_id: number;
  message: string;
  is_read: boolean;
  created_at: string;
  type: string;
  reference_id: number | null;
}

interface Stats {
  totalProducts: number;
  pendingProducts: number;
  approvedProducts: number;
  rejectedProducts: number;
}

interface Props {
  userData: UserData | null;
  farmDetails: FarmDetails | null;
  products: Product[];
  recentProducts: Product[];
  notifications: Notification[];
  stats: Stats;
  refreshing: boolean;
  loading?: boolean;
  error?: string | null;
  onRefresh: () => void;
  navigateToAddProduct: () => void;
  navigateToManageProducts: () => void;
  navigateToFarmProfile: (tab?: string) => void;
  navigateToProductDetails: (productId: number) => void;
  navigateToNotifications: () => void;
  navigateToOrders: () => void;
  handleLogout: () => Promise<any>;
}

export function FarmerDashboardContent({
  userData,
  farmDetails,
  products,
  recentProducts,
  notifications,
  stats,
  refreshing,
  loading = false,
  error = null,
  onRefresh,
  navigateToAddProduct,
  navigateToManageProducts,
  navigateToFarmProfile,
  navigateToProductDetails,
  navigateToNotifications,
  navigateToOrders,
  handleLogout,
}: Props) {
  // Button animation values
  const profileScale = useSharedValue(1);
  const farmScale = useSharedValue(1);
  const fieldsScale = useSharedValue(1);

  const profileAnimStyle = useAnimatedStyle(() => ({
    transform: [{ scale: profileScale.value }],
  }));

  const farmAnimStyle = useAnimatedStyle(() => ({
    transform: [{ scale: farmScale.value }],
  }));

  const fieldsAnimStyle = useAnimatedStyle(() => ({
    transform: [{ scale: fieldsScale.value }],
  }));

  const handlePressIn = (button: "profile" | "farm" | "fields") => {
    const scaleValue =
      button === "profile"
        ? profileScale
        : button === "farm"
          ? farmScale
          : fieldsScale;
    scaleValue.value = withSpring(0.95);
  };

  const handlePressOut = (button: "profile" | "farm" | "fields") => {
    const scaleValue =
      button === "profile"
        ? profileScale
        : button === "farm"
          ? farmScale
          : fieldsScale;
    scaleValue.value = withSpring(1);
  };

  // Show loading state
  if (loading && !refreshing) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <ThemedText style={styles.loadingText}>Loading dashboard...</ThemedText>
      </View>
    );
  }

  // Show error state
  if (error) {
    return (
      <View style={styles.errorContainer}>
        <ThemedText style={styles.errorText}>{error}</ThemedText>
        <View style={styles.retryButton}>
          <ThemedText style={styles.retryButtonText} onPress={onRefresh}>
            Retry
          </ThemedText>
        </View>
      </View>
    );
  }

  // Create data array for FlatList sections
  const sections = [
    { id: "stats", component: <FarmerStats stats={stats} /> },
    {
      id: "quickActions",
      component: (
        <FarmerQuickActions
          navigateToAddProduct={navigateToAddProduct}
          navigateToManageProducts={navigateToManageProducts}
          navigateToFarmProfile={navigateToFarmProfile}
          navigateToOrders={navigateToOrders}
        />
      ),
    },
    {
      id: "recentProducts",
      component: (
        <FarmerRecentProducts
          recentProducts={recentProducts}
          navigateToProductDetails={navigateToProductDetails}
          navigateToAddProduct={navigateToAddProduct}
          navigateToManageProducts={navigateToManageProducts}
        />
      ),
    },
    {
      id: "marketInsights",
      component: <FarmerMarketInsights />,
    },
    {
      id: "notifications",
      component: (
        <FarmerNotifications
          notifications={notifications}
          navigateToNotifications={navigateToNotifications}
        />
      ),
    },
    { id: "tips", component: <FarmerTips /> },
    {
      id: "profile",
      component: (
        <View style={styles.profileButtonContainer}>
          <ThemedText style={styles.profileButtonTitle}>
            Farmer Profile & Farm Information
          </ThemedText>
          <ThemedText style={styles.profileButtonSubtitle}>
            View and manage your profile, farm details, and field information
          </ThemedText>
          <View style={styles.profileButtonsRow}>
            <TouchableOpacity
              style={styles.profileButtonMultiple}
              onPress={() => navigateToFarmProfile("profile")}
              onPressIn={() => handlePressIn("profile")}
              onPressOut={() => handlePressOut("profile")}
              activeOpacity={0.9}
            >
              <Animated.View
                style={[{ width: "100%", height: "100%" }, profileAnimStyle]}
              >
                <LinearGradient
                  colors={["#2E7D32", "#1B5E20"]}
                  style={styles.buttonGradient}
                >
                  <Ionicons
                    name="person"
                    size={22}
                    color="#fff"
                    style={styles.buttonIcon}
                  />
                  <ThemedText style={styles.profileButtonText}>
                    Profile
                  </ThemedText>
                </LinearGradient>
              </Animated.View>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.profileButtonMultiple}
              onPress={() => navigateToFarmProfile("farm-details")}
              onPressIn={() => handlePressIn("farm")}
              onPressOut={() => handlePressOut("farm")}
              activeOpacity={0.9}
            >
              <Animated.View
                style={[{ width: "100%", height: "100%" }, farmAnimStyle]}
              >
                <LinearGradient
                  colors={["#2E7D32", "#1B5E20"]}
                  style={styles.buttonGradient}
                >
                  <Ionicons
                    name="home"
                    size={22}
                    color="#fff"
                    style={styles.buttonIcon}
                  />
                  <ThemedText style={styles.profileButtonText}>Farm</ThemedText>
                </LinearGradient>
              </Animated.View>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.profileButtonMultiple}
              onPress={() => navigateToFarmProfile("fields")}
              onPressIn={() => handlePressIn("fields")}
              onPressOut={() => handlePressOut("fields")}
              activeOpacity={0.9}
            >
              <Animated.View
                style={[{ width: "100%", height: "100%" }, fieldsAnimStyle]}
              >
                <LinearGradient
                  colors={["#2E7D32", "#1B5E20"]}
                  style={styles.buttonGradient}
                >
                  <Ionicons
                    name="leaf"
                    size={22}
                    color="#fff"
                    style={styles.buttonIcon}
                  />
                  <ThemedText style={styles.profileButtonText}>
                    Fields
                  </ThemedText>
                </LinearGradient>
              </Animated.View>
            </TouchableOpacity>
          </View>
        </View>
      ),
    },
    {
      id: "support",
      component: (
        <View style={{ marginTop: 24 }}>
          <View
            style={{
              marginBottom: 12,
              paddingHorizontal: 16,
            }}
          >
            <ThemedText
              style={{
                fontSize: 18,
                fontWeight: "bold",
                color: COLORS.text,
              }}
            >
              Agricultural Support Resources
            </ThemedText>
          </View>
          <FarmerContactSupport />
        </View>
      ),
    },
  ];

  return (
    <FlatList
      data={sections}
      keyExtractor={(item) => item.id}
      renderItem={({ item }) => item.component}
      style={styles.scrollView}
      contentContainerStyle={styles.contentContainer}
      showsVerticalScrollIndicator={false}
      accessibilityLabel="Farmer Dashboard Content"
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          colors={[COLORS.primary]}
          tintColor={COLORS.primary}
        />
      }
    />
  );
}

const styles = StyleSheet.create({
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  loadingText: {
    marginTop: 10,
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
    fontSize: 16,
    color: "red", // Using a standard color instead of COLORS.danger
    textAlign: "center",
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 5,
  },
  retryButtonText: {
    color: COLORS.light,
    fontWeight: "600",
  },
  profileButtonContainer: {
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
  profileButtonTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
    marginBottom: 8,
  },
  profileButtonSubtitle: {
    fontSize: 14,
    color: COLORS.muted,
    marginBottom: 16,
  },
  profileButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
  },
  profileButtonText: {
    color: "#fff",
    fontWeight: "600",
    fontSize: 13,
    marginTop: 4,
    textAlign: "center",
  },
  profileButtonsRow: {
    flexDirection: "row",
    justifyContent: "center",
    marginTop: 16,
    paddingHorizontal: 5,
    gap: 12,
  },
  profileButtonMultiple: {
    width: "28%",
    aspectRatio: 1,
    borderRadius: 12,
    overflow: "hidden",
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 4,
  },
  buttonGradient: {
    paddingHorizontal: 8,
    paddingVertical: 16,
    alignItems: "center",
    justifyContent: "center",
    width: "100%",
    height: "100%",
  },
  buttonIcon: {
    marginBottom: 4,
  },
});
