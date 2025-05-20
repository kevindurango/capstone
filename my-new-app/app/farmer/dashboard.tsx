import React, { useState, useEffect, useCallback } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Platform,
} from "react-native";
import { useRouter, useLocalSearchParams } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import FarmerSidebar from "@/components/farmer/FarmerSidebar"; // Import the new FarmerSidebar
import { authService } from "@/services/authService";
import IPConfig from "@/constants/IPConfig";
import RoleGuard from "@/components/RoleGuard";
import { useAuth } from "@/contexts/AuthContext";
import { FarmerDashboardContent } from "@/components/farmer/FarmerDashboardContent";
import FarmerNavigation from "@/components/farmer/FarmerNavigation";

// Define color scheme with TypeScript interface
interface ColorScheme {
  primary: string;
  secondary: string;
  accent: string;
  light: string;
  dark: string;
  text: string;
  muted: string;
  cardBg: string;
  shadow: string;
  success: string;
  warning: string;
  danger: string;
}

// Keep color scheme consistent with welcome screen but add farmer-specific colors
const COLORS: ColorScheme = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  cardBg: "#F9FBF7",
  shadow: "#000000",
  success: "#4CAF50",
  warning: "#FF9800",
  danger: "#F44336",
};

// Define types for userData for type safety
interface UserData {
  first_name: string;
  last_name: string;
  email: string;
  user_id?: number;
  role_id?: number;
  [key: string]: any; // For other properties that might be present
}

// Define auth state interface
interface AuthState {
  isChecked: boolean;
  isAuthenticated: boolean;
}

// Product interface
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

// Farm Details interface
interface FarmDetails {
  detail_id: number;
  farm_name: string;
  farm_type: string;
  farm_location: string;
  farm_size: number;
  certifications: string;
  crop_varieties: string;
}

// Notification interface
interface Notification {
  notification_id: number;
  message: string;
  is_read: boolean;
  created_at: string;
  type: string;
  reference_id: number | null;
}

export default function FarmerDashboard() {
  const router = useRouter();
  const { isFarmer } = useAuth(); // Use the role-based helper from AuthContext
  const [activeTab, setActiveTab] = useState("overview");
  const [isSidebarVisible, setIsSidebarVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [userData, setUserData] = useState<UserData | null>(null);
  const [farmDetails, setFarmDetails] = useState<FarmDetails | null>(null);
  const [authState, setAuthState] = useState<AuthState>({
    isChecked: false,
    isAuthenticated: false,
  });

  // Get refresh parameter from navigation
  const params = useLocalSearchParams();

  const [products, setProducts] = useState<Product[]>([]);
  const [recentProducts, setRecentProducts] = useState<Product[]>([]);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [stats, setStats] = useState({
    totalProducts: 0,
    pendingProducts: 0,
    approvedProducts: 0,
    rejectedProducts: 0,
  });

  // Function to fetch user's product statistics
  const fetchProductStats = async (userId: number) => {
    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${userId}&stats=true`
      );
      const data = await response.json();

      if (data.success) {
        setStats({
          totalProducts: data.stats.total || 0,
          pendingProducts: data.stats.pending || 0,
          approvedProducts: data.stats.approved || 0,
          rejectedProducts: data.stats.rejected || 0,
        });
      } else {
        console.error(
          "Error fetching product statistics: API returned success=false",
          data
        );
      }
    } catch (error) {
      console.error("Error fetching product statistics:", error);
      if (error instanceof SyntaxError) {
        console.error("Response might not be JSON. Check API endpoint.");
      }
    }
  };

  // Function to fetch farmer's products
  const fetchProducts = async (userId: number) => {
    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${userId}`
      );
      const data = await response.json();

      if (data.success) {
        setProducts(data.products);
        // Get the 3 most recent products
        setRecentProducts(data.products.slice(0, 3));
      } else {
        console.error(
          "Error fetching products: API returned success=false",
          data
        );
      }
    } catch (error) {
      console.error("Error fetching products:", error);
      if (error instanceof SyntaxError) {
        console.error("Response might not be JSON. Check API endpoint.");
      }
    }
  };

  // Function to fetch farmer's notifications
  const fetchNotifications = async (userId: number) => {
    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/notifications.php?user_id=${userId}&limit=5`
      );
      const data = await response.json();

      if (data.success) {
        setNotifications(data.notifications); // Get only the 5 most recent notifications
      } else {
        console.error(
          "Error fetching notifications: API returned success=false",
          data
        );
      }
    } catch (error) {
      console.error("Error fetching notifications:", error);
      if (error instanceof SyntaxError) {
        console.error("Response might not be JSON. Check API endpoint.");
      }
    }
  };

  // Function to fetch farmer details
  const fetchFarmDetails = async (userId: number) => {
    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_details.php?user_id=${userId}`
      );
      const data = await response.json();

      if (data.success) {
        setFarmDetails(data.farm_details);
      } else {
        console.error(
          "Error fetching farm details: API returned success=false",
          data
        );
      }
    } catch (error) {
      console.error("Error fetching farm details:", error);
      if (error instanceof SyntaxError) {
        console.error("Response might not be JSON. Check API endpoint.");
      }
    }
  };

  // Refresh all data
  const onRefresh = useCallback(async () => {
    if (!userData?.user_id) return;

    setRefreshing(true);

    try {
      await Promise.all([
        fetchProducts(userData.user_id),
        fetchProductStats(userData.user_id),
        fetchNotifications(userData.user_id),
        fetchFarmDetails(userData.user_id),
      ]);
    } catch (error) {
      console.error("Error refreshing data:", error);
    } finally {
      setRefreshing(false);
    }
  }, [userData?.user_id]);

  // Improved authentication check with better error handling
  useEffect(() => {
    let isMounted = true;

    const checkAuth = async () => {
      setIsLoading(true);
      console.log("[FarmerDashboard] Starting authentication check");

      // Check if we're being redirected from main.tsx
      const redirectFrom = params.redirect;
      if (redirectFrom === "from_main") {
        console.log("[FarmerDashboard] Detected redirect from main screen");
      }

      try {
        const isAuth = await authService.isAuthenticated();
        console.log("[FarmerDashboard] Auth check result:", isAuth);

        if (!isMounted) return;

        if (isAuth) {
          setAuthState({ isChecked: true, isAuthenticated: true });

          try {
            const userDataResult = await authService.getUserData();
            console.log("[FarmerDashboard] User data loaded:", userDataResult);

            if (isMounted) {
              // Check if user is a farmer (role_id = 2)
              if (userDataResult?.role_id !== 2) {
                Alert.alert(
                  "Access Denied",
                  "This page is only accessible to farmers.",
                  [
                    {
                      text: "OK",
                      onPress: () => {
                        if (isMounted) router.replace("/(tabs)/main");
                      },
                    },
                  ]
                );
                return;
              }

              setUserData({
                first_name: userDataResult?.first_name || "Farmer",
                last_name: userDataResult?.last_name || "",
                email: userDataResult?.email || "",
                ...userDataResult,
              });

              // Fetch farmer's data
              if (userDataResult?.user_id) {
                fetchProducts(userDataResult.user_id);
                fetchProductStats(userDataResult.user_id);
                fetchNotifications(userDataResult.user_id);
                fetchFarmDetails(userDataResult.user_id);
              }
            }
          } catch (userError) {
            console.error("[FarmerDashboard] User data error:", userError);
            if (isMounted) {
              setUserData({ first_name: "Farmer", last_name: "", email: "" });
            }
          }
        } else {
          console.log(
            "[FarmerDashboard] Not authenticated, redirecting to login"
          );
          setAuthState({ isChecked: true, isAuthenticated: false });

          // Use a timeout to prevent navigation race conditions
          setTimeout(() => {
            if (isMounted) router.replace("/(auth)/login");
          }, 300);
        }
      } catch (error) {
        console.error("[FarmerDashboard] Auth check error:", error);

        if (isMounted) {
          setAuthState({ isChecked: true, isAuthenticated: false });

          Alert.alert(
            "Authentication Error",
            "There was an error checking your authentication status. Please login again.",
            [
              {
                text: "OK",
                onPress: () => {
                  if (isMounted) router.replace("/(auth)/login");
                },
              },
            ]
          );
        }
      } finally {
        if (isMounted) setIsLoading(false);
      }
    };

    checkAuth();

    return () => {
      isMounted = false;
    };
  }, [router]);

  // Handle logout with error handling
  const handleLogout = async () => {
    console.log("[FarmerDashboard] Logout initiated");
    try {
      const success = await authService.logout();
      if (success) {
        console.log("[FarmerDashboard] Logout successful");
        router.replace("/(auth)/login");
        return true;
      } else {
        Alert.alert("Error", "Failed to logout. Please try again.");
        return false;
      }
    } catch (error) {
      console.error("[FarmerDashboard] Logout error:", error);
      Alert.alert(
        "Error",
        "An unexpected error occurred during logout. Please try again later."
      );
      return false;
    }
  };
  // Navigate to add product screen - using replace to prevent back buttons
  const navigateToAddProduct = () => {
    router.replace("/farmer/add-product");
  };

  // Navigate to manage products screen - using replace to prevent back buttons
  const navigateToManageProducts = () => {
    router.replace("/farmer/products");
  };

  // Navigate to farm profile screen - using replace to prevent back buttons
  const navigateToFarmProfile = (tab?: string) => {
    if (tab) {
      router.replace(`/farmer/profile?tab=${tab}`);
    } else {
      router.replace("/farmer/profile");
    }
  };

  // Navigate to specific product details - using replace to prevent back buttons
  const navigateToProductDetails = (productId: number) => {
    router.replace(`/farmer/products?id=${productId}`);
  };

  // Navigate to all notifications - using replace to prevent back buttons
  const navigateToNotifications = () => {
    router.replace("/farmer/notifications" as any);
  };

  // Navigate to market orders - using replace to prevent back buttons
  const navigateToOrders = () => {
    router.replace("/farmer/orders");
  };

  // Show loading state while checking authentication
  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <ThemedText style={styles.loadingText}>Loading...</ThemedText>
      </View>
    );
  }

  // Show login prompt only if we've explicitly determined the user is not authenticated
  if (authState.isChecked && !authState.isAuthenticated) {
    return (
      <View style={styles.loadingContainer}>
        <ThemedText style={styles.loadingText}>
          Please log in to access the farmer dashboard.
        </ThemedText>
        <TouchableOpacity
          style={styles.loginButton}
          onPress={() => router.replace("/(auth)/login")}
        >
          <ThemedText style={styles.buttonText}>Go to Login</ThemedText>
        </TouchableOpacity>
      </View>
    );
  }

  // Farmer Dashboard
  return (
    <RoleGuard allowedRoles={[2]} fallbackPath="/(tabs)/main">
      <View style={styles.container} accessibilityLabel="Farmer Dashboard">
        {/* Use the new FarmerSidebar instead of the general Sidebar */}
        <FarmerSidebar
          isVisible={isSidebarVisible}
          onClose={() => setIsSidebarVisible(false)}
          router={router}
          onLogout={handleLogout}
        />

        {/* More compact header */}
        <View style={styles.compactHeader}>
          <TouchableOpacity
            style={styles.menuButton}
            onPress={() => setIsSidebarVisible(true)}
            accessibilityLabel="Open sidebar menu"
            accessibilityRole="button"
          >
            <Ionicons name="menu" size={24} color={COLORS.light} />
          </TouchableOpacity>
          <View style={styles.headerContent}>
            <ThemedText style={styles.headerTitle} accessibilityRole="header">
              {userData?.first_name
                ? `${userData.first_name}'s Farm`
                : "Farmer Dashboard"}
            </ThemedText>
            {farmDetails?.farm_name && (
              <ThemedText style={styles.farmName}>
                {farmDetails.farm_name}
              </ThemedText>
            )}
          </View>
          <TouchableOpacity
            style={styles.headerProfileButton}
            onPress={() => navigateToFarmProfile()}
            accessibilityLabel="View farm profile"
            accessibilityRole="button"
          >
            <Ionicons name="person-circle" size={32} color={COLORS.light} />
          </TouchableOpacity>
        </View>

        <View style={styles.contentContainer}>
          {/* Main content */}
          <FarmerDashboardContent
            userData={userData}
            farmDetails={farmDetails}
            products={products}
            recentProducts={recentProducts}
            notifications={notifications}
            stats={stats}
            refreshing={refreshing}
            onRefresh={onRefresh}
            navigateToAddProduct={navigateToAddProduct}
            navigateToManageProducts={navigateToManageProducts}
            navigateToFarmProfile={navigateToFarmProfile}
            navigateToProductDetails={navigateToProductDetails}
            navigateToNotifications={navigateToNotifications}
            navigateToOrders={navigateToOrders}
            handleLogout={handleLogout}
          />
        </View>

        {/* Bottom Navigation - Using our new component */}
        <FarmerNavigation />
      </View>
    </RoleGuard>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  contentContainer: {
    flex: 1,
  },
  // Updated header styles for a more compact design
  compactHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 12,
    paddingTop: Platform.OS === "ios" ? 50 : 30,
    backgroundColor: COLORS.primary,
    elevation: 4,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
  },
  headerContent: {
    flex: 1,
    alignItems: "center",
  },
  headerTitle: {
    fontSize: 18,
    color: COLORS.light,
    fontWeight: "700",
  },
  farmName: {
    fontSize: 14,
    color: COLORS.secondary,
    fontWeight: "500",
  },
  headerProfileButton: {
    padding: 8,
  },
  menuButton: {
    padding: 8,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: COLORS.light,
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: COLORS.primary,
  },
  loginButton: {
    backgroundColor: COLORS.accent,
    padding: 15,
    borderRadius: 10,
    marginTop: 20,
    width: 200,
    alignItems: "center",
  },
  buttonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 18,
    fontWeight: "bold",
  },
  tabsContainer: {
    flexDirection: "row",
    backgroundColor: "rgba(255,255,255,0.1)",
    borderRadius: 10,
    marginHorizontal: 20,
    padding: 5,
    marginBottom: 10,
  },
  tab: {
    flex: 1,
    alignItems: "center",
    paddingVertical: 10,
    flexDirection: "row",
    justifyContent: "center",
    borderRadius: 8,
  },
  activeTab: {
    backgroundColor: "rgba(255,255,255,0.2)",
  },
  tabText: {
    color: COLORS.light,
    marginLeft: 5,
  },
  activeTabText: {
    color: COLORS.accent,
    fontWeight: "bold",
  },
});
