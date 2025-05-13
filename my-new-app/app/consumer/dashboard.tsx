import {
  StyleSheet,
  View,
  TouchableOpacity,
  ScrollView,
  ActivityIndicator,
  Alert,
} from "react-native";
import { useRouter } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useState, useEffect } from "react";
import Sidebar from "@/components/Sidebar";
import { authService } from "@/services/authService";
import React from "react";

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
}

// Keep color scheme consistent with welcome screen
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

// Simple authentication check within the component itself
export default function MainScreen() {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState("home");
  const [isSidebarVisible, setIsSidebarVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [userData, setUserData] = useState<UserData | null>(null);
  const [authState, setAuthState] = useState<AuthState>({
    isChecked: false,
    isAuthenticated: false,
  });

  // Improved authentication check with better error handling
  useEffect(() => {
    let isMounted = true;

    const checkAuth = async () => {
      setIsLoading(true);
      console.log("[Main] Starting authentication check");

      try {
        const isAuth = await authService.isAuthenticated();
        console.log("[Main] Auth check result:", isAuth);

        if (!isMounted) return;

        if (isAuth) {
          setAuthState({ isChecked: true, isAuthenticated: true });

          try {
            const userDataResult = await authService.getUserData();
            console.log("[Main] User data loaded:", userDataResult);

            if (isMounted) {
              setUserData({
                first_name: userDataResult?.first_name || "User",
                last_name: userDataResult?.last_name || "",
                email: userDataResult?.email || "",
                ...userDataResult,
              });
            }
          } catch (userError) {
            console.error("[Main] User data error:", userError);
            if (isMounted) {
              setUserData({ first_name: "User", last_name: "", email: "" });
            }
          }
        } else {
          console.log("[Main] Not authenticated, redirecting to login");
          setAuthState({ isChecked: true, isAuthenticated: false });

          // Use a timeout to prevent navigation race conditions
          setTimeout(() => {
            if (isMounted) router.replace("/(auth)/login");
          }, 300); // Increased timeout for better reliability
        }
      } catch (error) {
        console.error("[Main] Auth check error:", error);

        if (isMounted) {
          setAuthState({ isChecked: true, isAuthenticated: false });

          // Show error alert before redirecting
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
    console.log("[Main] Logout initiated");
    try {
      const success = await authService.logout();
      if (success) {
        console.log("[Main] Logout successful");
        // Redirect to login screen instead of intro screen
        router.replace("/(auth)/login");
        return true;
      } else {
        Alert.alert("Error", "Failed to logout. Please try again.");
        return false;
      }
    } catch (error) {
      console.error("[Main] Logout error:", error);
      Alert.alert(
        "Error",
        "An unexpected error occurred during logout. Please try again later."
      );
      return false;
    }
  };

  // Define valid section types for navigation
  type NavigationSection =
    | "profile"
    | "about"
    | "services"
    | "market"
    | "orders"; // Updated to match available routes

  // Memoize navigation functions to prevent re-renders
  const navigateToSection = React.useCallback(
    (section: NavigationSection) => {
      switch (section) {
        case "about":
          router.push("/consumer/about");
          break;
        case "profile":
          router.push("/consumer/profile");
          break;
        case "services":
          router.push("/consumer/services");
          break;
        case "market":
          router.push("/consumer/market");
          break;
        case "orders":
          router.push("/consumer/orders");
          break;
      }
    },
    [router]
  );

  const handleTabNavigation = React.useCallback(
    (tab: string) => {
      setActiveTab(tab);

      // Navigate to corresponding screens based on tab
      switch (tab) {
        case "services":
          router.push("/consumer/services");
          break;
        case "market":
          router.push("/consumer/market");
          break;
        case "home":
        default:
          // Stay on the current dashboard
          router.push("/consumer/dashboard");
          break;
      }
    },
    [router]
  );

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
          Please log in to access this page.
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

  // Enhance component accessibility
  return (
    <View style={styles.container} accessibilityLabel="Main screen">
      {/* Sidebar with logout option */}
      <Sidebar
        isVisible={isSidebarVisible}
        onClose={() => setIsSidebarVisible(false)}
        router={router}
        colors={COLORS}
        onLogout={handleLogout}
      />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.menuButton}
          onPress={() => setIsSidebarVisible(true)}
          accessibilityLabel="Open sidebar menu"
          accessibilityRole="button"
        >
          <Ionicons name="menu" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle} accessibilityRole="header">
          Municipal Agriculture Office
        </ThemedText>
        <TouchableOpacity
          style={styles.headerProfileButton}
          onPress={() => navigateToSection("profile")}
          accessibilityLabel="View profile"
          accessibilityRole="button"
        >
          <Ionicons name="person-circle" size={32} color={COLORS.light} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
        showsVerticalScrollIndicator={false}
        accessibilityLabel="Main content"
      >
        {/* Welcome Banner with User Info */}
        <View style={styles.welcomeBanner}>
          <View style={styles.welcomeText}>
            <ThemedText style={styles.welcomeTitle}>
              Welcome, {userData?.first_name || "User"}
            </ThemedText>
            <ThemedText style={styles.welcomeSubtitle}>
              Supporting our farmers and communities
            </ThemedText>
          </View>
          <View style={styles.welcomeImageContainer}>
            <View style={styles.welcomeImagePlaceholder}>
              <Ionicons name="leaf" size={36} color={COLORS.primary} />
            </View>
          </View>
        </View>

        {/* Quick Actions */}
        <View style={styles.quickActionsContainer}>
          <ThemedText style={styles.sectionTitle}>
            Services & Resources
          </ThemedText>

          <View style={styles.quickActions}>
            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("about")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons
                  name="information-circle"
                  size={32}
                  color={COLORS.primary}
                />
              </View>
              <ThemedText style={styles.actionTitle}>
                About Our Office
              </ThemedText>
              <ThemedText style={styles.actionDescription}>
                Learn about our mission and team
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("services")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons name="list" size={32} color={COLORS.primary} />
              </View>
              <ThemedText style={styles.actionTitle}>Services</ThemedText>
              <ThemedText style={styles.actionDescription}>
                View available services for farmers
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("market")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons name="basket" size={32} color={COLORS.primary} />
              </View>
              <ThemedText style={styles.actionTitle}>Farmers Market</ThemedText>
              <ThemedText style={styles.actionDescription}>
                Connect with local markets
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("orders")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons
                  name="document-text"
                  size={32}
                  color={COLORS.primary}
                />
              </View>
              <ThemedText style={styles.actionTitle}>Your Orders</ThemedText>
              <ThemedText style={styles.actionDescription}>
                Track and manage your orders
              </ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* News and Updates */}
        <View style={styles.newsContainer}>
          <View style={styles.sectionTitleContainer}>
            <ThemedText style={styles.sectionTitle}>Latest Updates</ThemedText>
            {/* Removed View All link that was pointing to non-existent news route */}
          </View>

          <View style={styles.newsCard}>
            <View style={styles.newsImagePlaceholder}>
              <Ionicons name="calendar" size={32} color={COLORS.primary} />
            </View>
            <View style={styles.newsContent}>
              <ThemedText style={styles.newsTitle}>
                Farmer Training Program
              </ThemedText>
              <ThemedText style={styles.newsDate}>June 15, 2025</ThemedText>
              <ThemedText style={styles.newsDescription}>
                Join our upcoming training on sustainable farming techniques.
              </ThemedText>
            </View>
          </View>

          <View style={styles.newsCard}>
            <View style={styles.newsImagePlaceholder}>
              <Ionicons name="water" size={32} color={COLORS.primary} />
            </View>
            <View style={styles.newsContent}>
              <ThemedText style={styles.newsTitle}>
                Irrigation System Updates
              </ThemedText>
              <ThemedText style={styles.newsDate}>June 10, 2025</ThemedText>
              <ThemedText style={styles.newsDescription}>
                New irrigation systems being installed in eastern districts.
              </ThemedText>
            </View>
          </View>
        </View>

        {/* User Profile Section */}
        <View style={styles.profileContainer}>
          <ThemedText style={styles.profileTitle}>Your Account</ThemedText>
          <ThemedText style={styles.profileDescription}>
            Manage your profile, view your orders, and access agricultural
            resources
          </ThemedText>
          <View style={styles.profileActions}>
            <TouchableOpacity
              style={styles.profileButton}
              onPress={() => navigateToSection("profile")}
            >
              <ThemedText style={styles.profileButtonText}>
                View Profile
              </ThemedText>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.profileButton, styles.logoutButton]}
              onPress={handleLogout}
            >
              <ThemedText style={styles.profileButtonText}>Logout</ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* Contact Section */}
        <View style={styles.contactContainer}>
          <ThemedText style={styles.contactTitle}>Contact Us</ThemedText>
          <View style={styles.contactInfo}>
            <View style={styles.contactItem}>
              <Ionicons name="location" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                Palinpinon, Negros Oriental
              </ThemedText>
            </View>
            <View style={styles.contactItem}>
              <Ionicons name="call" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>(035) 225-0000</ThemedText>
            </View>
            <View style={styles.contactItem}>
              <Ionicons name="mail" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                agriculture@negor.gov.ph
              </ThemedText>
            </View>
          </View>
        </View>
      </ScrollView>

      {/* Bottom Navigation - enhanced accessibility */}
      <View style={styles.bottomNav} accessibilityRole="tablist">
        <TouchableOpacity
          style={styles.navItem}
          onPress={() => handleTabNavigation("home")}
          accessibilityLabel="Home tab"
          accessibilityRole="tab"
          accessibilityState={{ selected: activeTab === "home" }}
        >
          <Ionicons
            name={activeTab === "home" ? "home" : "home-outline"}
            size={26}
            color={activeTab === "home" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              { color: activeTab === "home" ? COLORS.primary : COLORS.muted },
            ]}
          >
            Home
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.navItem}
          onPress={() => handleTabNavigation("services")}
          accessibilityLabel="Services tab"
          accessibilityRole="tab"
          accessibilityState={{ selected: activeTab === "services" }}
        >
          <Ionicons
            name={activeTab === "services" ? "list" : "list-outline"}
            size={26}
            color={activeTab === "services" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              {
                color: activeTab === "services" ? COLORS.primary : COLORS.muted,
              },
            ]}
          >
            Services
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.navItem}
          onPress={() => handleTabNavigation("market")}
          accessibilityLabel="Market tab"
          accessibilityRole="tab"
          accessibilityState={{ selected: activeTab === "market" }}
        >
          <Ionicons
            name={activeTab === "market" ? "basket" : "basket-outline"}
            size={26}
            color={activeTab === "market" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              { color: activeTab === "market" ? COLORS.primary : COLORS.muted },
            ]}
          >
            Market
          </ThemedText>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 20,
    paddingTop: 60,
    backgroundColor: COLORS.primary,
    elevation: 4,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
  },
  headerTitle: {
    fontSize: 20,
    color: COLORS.light,
    fontWeight: "700",
  },
  headerProfileButton: {
    padding: 8,
  },
  menuButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
    paddingTop: 0,
    paddingBottom: 40,
  },
  welcomeBanner: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  welcomeText: {
    flex: 1,
  },
  welcomeTitle: {
    fontSize: 24,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "700",
  },
  welcomeSubtitle: {
    fontSize: 16,
    color: COLORS.text,
  },
  welcomeImageContainer: {
    justifyContent: "center",
    alignItems: "center",
  },
  welcomeImagePlaceholder: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: COLORS.light,
    justifyContent: "center",
    alignItems: "center",
  },
  quickActionsContainer: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  quickActions: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
  },
  actionCard: {
    width: "48%",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    alignItems: "center",
  },
  actionIconContainer: {
    marginBottom: 8,
  },
  actionTitle: {
    fontSize: 16,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "600",
  },
  actionDescription: {
    fontSize: 14,
    color: COLORS.text,
    textAlign: "center",
  },
  newsContainer: {
    marginBottom: 16,
  },
  sectionTitleContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  viewAllLink: {
    fontSize: 14,
    color: COLORS.accent,
    fontWeight: "600",
  },
  newsCard: {
    flexDirection: "row",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  newsImagePlaceholder: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: COLORS.light,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 16,
  },
  newsContent: {
    flex: 1,
  },
  newsTitle: {
    fontSize: 16,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "600",
  },
  newsDate: {
    fontSize: 14,
    color: COLORS.muted,
    marginBottom: 4,
  },
  newsDescription: {
    fontSize: 14,
    color: COLORS.text,
  },
  profileContainer: {
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    alignItems: "center",
  },
  profileTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  profileDescription: {
    fontSize: 14,
    color: COLORS.text,
    textAlign: "center",
    marginBottom: 16,
  },
  profileActions: {
    flexDirection: "row",
    justifyContent: "space-between",
    width: "100%",
  },
  profileButton: {
    flex: 1,
    padding: 12,
    borderRadius: 8,
    alignItems: "center",
    backgroundColor: COLORS.primary,
    marginHorizontal: 4,
  },
  logoutButton: {
    backgroundColor: COLORS.accent,
  },
  profileButtonText: {
    fontSize: 16,
    color: COLORS.light,
    fontWeight: "600",
  },
  contactContainer: {
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  contactTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  contactInfo: {
    marginTop: 8,
  },
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  contactText: {
    fontSize: 14,
    color: COLORS.text,
    marginLeft: 8,
  },
  bottomNav: {
    flexDirection: "row",
    justifyContent: "space-around",
    alignItems: "center",
    padding: 16,
    backgroundColor: COLORS.light,
    borderTopWidth: 1,
    borderTopColor: COLORS.muted,
  },
  navItem: {
    alignItems: "center",
    justifyContent: "center",
  },
  navText: {
    fontSize: 12,
    marginTop: 4,
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
});
