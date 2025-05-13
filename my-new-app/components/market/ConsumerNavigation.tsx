import React from "react";
import { View, TouchableOpacity, StyleSheet, Text } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useRouter, usePathname } from "expo-router";
import { COLORS } from "@/constants/Colors";

type IconName = React.ComponentProps<typeof Ionicons>["name"];
type Route =
  | "/consumer/market"
  | "/consumer/dashboard"
  | "/consumer/orders"
  | "/consumer/profile"
  | "/(tabs)/main";

interface NavItem {
  label: string;
  icon: IconName;
  route: Route;
  active: boolean;
}

/**
 * Consumer-specific bottom navigation component
 * Provides navigation between consumer screens
 */
export default function ConsumerNavigation() {
  const router = useRouter();
  const pathname = usePathname();

  const navItems: NavItem[] = [
    {
      label: "Market",
      icon: "basket-outline",
      route: "/consumer/market",
      active: pathname === "/consumer/market",
    },
    {
      label: "Orders",
      icon: "receipt-outline",
      route: "/consumer/orders",
      active: pathname === "/consumer/orders",
    },
    {
      label: "Profile",
      icon: "person-outline",
      route: "/consumer/profile",
      active: pathname === "/consumer/profile",
    },
    {
      label: "Home",
      icon: "home-outline",
      route: "/(tabs)/main",
      active: pathname === "/(tabs)/main",
    },
  ];

  return (
    <View style={styles.container}>
      {navItems.map((item, index) => (
        <TouchableOpacity
          key={index}
          style={styles.tabItem}
          onPress={() => router.push(item.route as any)}
          accessibilityLabel={item.label}
        >
          <Ionicons
            name={
              item.active
                ? (item.icon.replace("-outline", "") as IconName)
                : item.icon
            }
            size={24}
            color={item.active ? COLORS.primary : COLORS.muted}
          />
          <Text style={[styles.tabLabel, item.active && styles.activeTabLabel]}>
            {item.label}
          </Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderTopWidth: 1,
    borderTopColor: "#e0e0e0",
    paddingBottom: 4,
    paddingTop: 8,
    elevation: 8,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  tabItem: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 8,
  },
  tabLabel: {
    fontSize: 12,
    marginTop: 4,
    color: COLORS.muted,
  },
  activeTabLabel: {
    color: COLORS.primary,
    fontWeight: "600",
  },
});
