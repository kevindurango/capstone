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
import { ThemedText } from "./ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useRouter } from "expo-router";
import { authService } from "@/services/authService";

const { width } = Dimensions.get("window");

// Define types for menu items
interface MenuItem {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  route: string;
}

// Define types for component props
interface SidebarProps {
  isVisible: boolean;
  onClose: () => void;
  router?: ReturnType<typeof useRouter>;
  colors: {
    primary: string;
    light: string;
    text: string;
    cardBg?: string;
  };
  onLogout?: () => Promise<boolean>;
}

export default function Sidebar({
  isVisible,
  onClose,
  router,
  colors,
  onLogout,
}: SidebarProps) {
  const translateX = React.useRef(new Animated.Value(-width)).current;
  const defaultRouter = useRouter();
  const navigationRouter = router || defaultRouter;

  // Menu items with updated routes matching the app structure
  const menuItems: MenuItem[] = [
    { icon: "home", label: "Home", route: "/(tabs)/main" },
    { icon: "information-circle", label: "About", route: "/(tabs)/about" },
    { icon: "list", label: "Services", route: "/(tabs)/services" },
    { icon: "star", label: "Programs", route: "/(tabs)/programs" },
    { icon: "basket", label: "Market", route: "/(tabs)/market" },
    { icon: "newspaper", label: "News", route: "/(tabs)/news" },
    { icon: "person", label: "Profile", route: "/(tabs)/profile" },
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
      // Use push for navigation
      try {
        if (navigationRouter) {
          navigationRouter.push(route as any);
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
            console.log("[Sidebar] User confirmed logout");
            onClose(); // Close sidebar first

            // Try to use the provided onLogout first
            if (typeof onLogout === "function") {
              await onLogout();
            } else {
              // Fallback to direct authService logout
              await authService.logout();
            }

            console.log("[SSidebar] Logout completed successfully");

            // Force immediate navigation to login
            console.log("[Sidebar] Navigating to login screen");
            setTimeout(() => {
              navigationRouter.replace("/(tabs)/login");
            }, 100);
          } catch (error) {
            console.error("[Sidebar] Logout error:", error);
            // If logout fails, still try to navigate to login
            navigationRouter.replace("/(tabs)/login");
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
              backgroundColor: colors.light,
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
              <Ionicons name="close" size={24} color={colors.primary} />
            </TouchableOpacity>
            <ThemedText style={[styles.title, { color: colors.primary }]}>
              Menu
            </ThemedText>
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
                  size={24}
                  color={colors.primary}
                  style={styles.menuIcon}
                />
                <ThemedText style={[styles.menuText, { color: colors.text }]}>
                  {item.label}
                </ThemedText>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.footer}>
            <TouchableOpacity
              style={styles.logoutButton}
              onPress={handleLogout}
              activeOpacity={0.7}
            >
              <Ionicons name="log-out" size={24} color={colors.primary} />
              <ThemedText style={[styles.logoutText, { color: colors.text }]}>
                Logout
              </ThemedText>
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
    width: width * 0.75,
    paddingTop: Platform.OS === "ios" ? 50 : 30,
    paddingHorizontal: 20,
    zIndex: 101,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 30,
    paddingVertical: 10,
  },
  closeButton: {
    padding: 8,
    borderRadius: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: "700",
    marginLeft: 20,
  },
  menuItems: {
    flex: 1,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 15,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "rgba(0, 0, 0, 0.1)",
  },
  lastMenuItem: {
    borderBottomWidth: 0,
  },
  menuIcon: {
    marginRight: 15,
    width: 24,
    textAlign: "center",
  },
  menuText: {
    fontSize: 16,
    fontWeight: "500",
  },
  footer: {
    paddingVertical: 20,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: "rgba(0, 0, 0, 0.1)",
  },
  logoutButton: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 15,
  },
  logoutText: {
    fontSize: 16,
    fontWeight: "500",
    marginLeft: 15,
  },
});
