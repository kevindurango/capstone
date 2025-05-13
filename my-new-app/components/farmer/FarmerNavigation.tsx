import React from "react";
import { View, TouchableOpacity, StyleSheet, Text } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useRouter, usePathname } from "expo-router";
import { COLORS } from "@/constants/Colors";

type IconName = React.ComponentProps<typeof Ionicons>["name"];
type Route =
  | "/farmer/dashboard"
  | "/farmer/products"
  | "/farmer/orders"
  | "/farmer/profile";

interface NavItem {
  label: string;
  icon: IconName;
  route: Route;
  active: boolean;
}

/**
 * Farmer-specific bottom navigation component
 * Provides navigation between farmer screens
 */
export default function FarmerNavigation() {
  const router = useRouter();
  const pathname = usePathname();

  const navItems: NavItem[] = [
    {
      label: "Dashboard",
      icon: "grid-outline",
      route: "/farmer/dashboard",
      active: pathname === "/farmer/dashboard",
    },
    {
      label: "Products",
      icon: "leaf-outline",
      route: "/farmer/products",
      active: pathname === "/farmer/products",
    },
    {
      label: "Orders",
      icon: "receipt-outline",
      route: "/farmer/orders",
      active: pathname === "/farmer/orders",
    },
    {
      label: "Profile",
      icon: "person-outline",
      route: "/farmer/profile",
      active: pathname === "/farmer/profile",
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
