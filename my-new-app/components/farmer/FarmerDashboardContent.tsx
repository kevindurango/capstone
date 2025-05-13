import React from "react";
import {
  StyleSheet,
  View,
  FlatList,
  RefreshControl,
  ActivityIndicator,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { FarmerStats } from "./FarmerStats";
import { FarmerQuickActions } from "./FarmerQuickActions";
import { FarmerRecentProducts } from "./FarmerRecentProducts";
import { FarmerNotifications } from "./FarmerNotifications";
import { FarmerMarketInsights } from "./FarmerMarketInsights";
import { FarmerTips } from "./FarmerTips";
import FarmerProfile from "./FarmerProfile";
import { FarmerContactSupport } from "./FarmerContactSupport";
import { COLORS } from "@/constants/Colors";

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
  navigateToFarmProfile: () => void;
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
        <FarmerProfile
          navigation={{
            navigate: navigateToFarmProfile,
          }}
        />
      ),
    },
    { id: "support", component: <FarmerContactSupport /> },
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
});
