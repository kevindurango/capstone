import React from "react";
import {
  View,
  StyleSheet,
  Animated,
  TouchableOpacity,
  Dimensions,
  Platform,
  Alert,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useRouter } from "expo-router";
import { authService } from "@/services/authService";
import { COLORS } from "@/constants/Colors";

const { width } = Dimensions.get("window");

// Define types for menu items
interface MenuItem {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  route: string;
}

// Define types for component props
interface FarmerSidebarProps {
  isVisible: boolean;
  onClose: () => void;
  router?: ReturnType<typeof useRouter>;
  onLogout?: () => Promise<boolean>;
}

export default function FarmerSidebar({
  isVisible,
  onClose,
  router,
  onLogout,
}: FarmerSidebarProps) {
  const translateX = React.useRef(new Animated.Value(-width)).current;
  const defaultRouter = useRouter();
  const navigationRouter = router || defaultRouter;

  // Menu items specifically for farmer functionality
  const menuItems: MenuItem[] = [
    { icon: "grid-outline", label: "Dashboard", route: "/farmer/dashboard" },
    { icon: "leaf-outline", label: "My Products", route: "/farmer/products" },
    { icon: "receipt-outline", label: "Orders", route: "/farmer/orders" },
    { icon: "person-outline", label: "Farm Profile", route: "/farmer/profile" },
    { icon: "settings-outline", label: "Settings", route: "/(tabs)/settings" },
    { icon: "help-circle-outline", label: "Help", route: "/(tabs)/about" },
  ];

  React.useEffect(() => {
    if (isVisible) {
      // Open sidebar
      Animated.spring(translateX, {
        toValue: 0,
        useNativeDriver: true,
        damping: 20,
        mass: 1,
        stiffness: 100,
      }).start();
    } else {
      // Close sidebar
      Animated.timing(translateX, {
        toValue: -width,
        duration: 250,
        useNativeDriver: true,
      }).start();
    }
  }, [isVisible, translateX]);
  const handleNavigation = (route: string) => {
    // Close sidebar first, then navigate
    Animated.timing(translateX, {
      toValue: -width,
      duration: 250,
      useNativeDriver: true,
    }).start(() => {
      onClose();
      // Use replace for navigation to prevent back button issues
      try {
        if (navigationRouter) {
          navigationRouter.replace(route as any);
        }
      } catch (error) {
        console.error("Navigation error:", error);
      }
    });
  };

  // Handle logout with proper cleanup and navigation
  const handleLogout = async () => {
    Alert.alert("Logout", "Are you sure you want to logout?", [
      {
        text: "Cancel",
        style: "cancel",
      },
      {
        text: "Logout",
        onPress: async () => {
          try {
            console.log("[FarmerSidebar] User confirmed logout");
            onClose(); // Close sidebar first

            // Try to use the provided onLogout first
            if (typeof onLogout === "function") {
              await onLogout();
            } else {
              // Fallback to direct authService logout
              await authService.logout();
            }

            console.log("[FarmerSidebar] Logout completed successfully");

            // Force immediate navigation to login
            console.log("[FarmerSidebar] Navigating to login screen");
            setTimeout(() => {
              navigationRouter.replace("/(auth)/login");
            }, 100);
          } catch (error) {
            console.error("[FarmerSidebar] Logout error:", error);
            // If logout fails, still try to navigate to login
            navigationRouter.replace("/(auth)/login");
          }
        },
      },
    ]);
  };

  if (!isVisible) return null;

  return (
    <View style={StyleSheet.absoluteFillObject}>
      <TouchableOpacity
        style={styles.overlay}
        activeOpacity={1}
        onPress={onClose}
      >
        <Animated.View
          style={[
            styles.container,
            {
              transform: [{ translateX: translateX }],
              ...Platform.select({
                ios: {
                  shadowColor: "#000",
                  shadowOffset: { width: 2, height: 0 },
                  shadowOpacity: 0.3,
                  shadowRadius: 5,
                },
                android: {
                  elevation: 5,
                },
              }),
            },
          ]}
        >
          <View style={styles.header}>
            <TouchableOpacity
              onPress={onClose}
              style={styles.closeButton}
              hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            >
              <Ionicons name="close" size={24} color={COLORS.primary} />
            </TouchableOpacity>
            <ThemedText style={styles.title}>Farmer Menu</ThemedText>
          </View>

          <View style={styles.menuItems}>
            {menuItems.map((item, index) => (
              <TouchableOpacity
                key={item.route}
                style={[
                  styles.menuItem,
                  index === menuItems.length - 1 && styles.lastMenuItem,
                ]}
                onPress={() => handleNavigation(item.route)}
                activeOpacity={0.7}
              >
                <Ionicons
                  name={item.icon}
                  size={22}
                  color={COLORS.primary}
                  style={styles.menuIcon}
                />
                <ThemedText style={styles.menuText}>{item.label}</ThemedText>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.footer}>
            <TouchableOpacity
              style={styles.logoutButton}
              onPress={handleLogout}
              activeOpacity={0.7}
            >
              <Ionicons name="log-out" size={22} color={COLORS.accent} />
              <ThemedText style={styles.logoutText}>Logout</ThemedText>
            </TouchableOpacity>
          </View>
        </Animated.View>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(0, 0, 0, 0.5)",
    zIndex: 100,
  },
  container: {
    position: "absolute",
    top: 0,
    left: 0,
    bottom: 0,
    width: width * 0.7, // Slightly narrower than consumer sidebar
    backgroundColor: "#FFFFFF",
    paddingTop: Platform.OS === "ios" ? 50 : 30,
    paddingHorizontal: 16, // Less padding for compact appearance
    zIndex: 101,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 20, // Reduced spacing
    paddingVertical: 8, // Smaller padding
  },
  closeButton: {
    padding: 6,
    borderRadius: 20,
  },
  title: {
    fontSize: 20, // Smaller font size
    fontWeight: "700",
    marginLeft: 16,
    color: COLORS.primary,
  },
  menuItems: {
    flex: 1,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 12, // Reduced padding
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "rgba(0, 0, 0, 0.1)",
  },
  lastMenuItem: {
    borderBottomWidth: 0,
  },
  menuIcon: {
    marginRight: 14,
    width: 22,
    textAlign: "center",
  },
  menuText: {
    fontSize: 15, // Smaller font size
    fontWeight: "500",
    color: COLORS.text,
  },
  footer: {
    paddingVertical: 16, // Reduced padding
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: "rgba(0, 0, 0, 0.1)",
  },
  logoutButton: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 12, // Reduced padding
  },
  logoutText: {
    fontSize: 15, // Smaller font size
    fontWeight: "500",
    marginLeft: 14,
    color: COLORS.accent, // Changed from danger to accent
  },
});
