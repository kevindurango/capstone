import React from "react";
import { View, StyleSheet, TouchableOpacity, Modal, Alert } from "react-native";
import { ThemedText } from "./ThemedText";
import { Ionicons } from "@expo/vector-icons";

const Sidebar = ({ isVisible, onClose, router, colors, onLogout }) => {
  // Handle logout with confirmation and proper error handling
  const handleLogout = () => {
    if (typeof onLogout !== "function") {
      console.error("[Sidebar] Logout function not provided");
      Alert.alert("Error", "Cannot logout at this time");
      return;
    }

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

            // Handle the async logout properly
            await onLogout();
            console.log("[Sidebar] Logout completed successfully");

            // Force immediate navigation to login
            console.log("[Sidebar] Navigating to login screen now");
            router.replace("/(tabs)/login");
          } catch (error) {
            console.error("[Sidebar] Error during logout:", error);

            // If logout fails, still try to navigate to login
            console.log("[Sidebar] Attempting navigation despite error");
            router.replace("/(tabs)/login");
          }
        },
      },
    ]);
  };

  const menuItems = [
    {
      icon: "home-outline",
      label: "Home",
      onPress: () => router.push("/(tabs)/home"),
    },
    {
      icon: "information-circle-outline",
      label: "About",
      onPress: () => router.push("/(tabs)/about"),
    },
    {
      icon: "list-outline",
      label: "Services",
      onPress: () => router.push("/(tabs)/services"),
    },
    {
      icon: "star-outline",
      label: "Programs",
      onPress: () => router.push("/(tabs)/programs"),
    },
    {
      icon: "basket-outline",
      label: "Market",
      onPress: () => router.push("/(tabs)/market"),
    },
    {
      icon: "log-out-outline",
      label: "Logout",
      onPress: handleLogout,
    },
  ];

  return (
    <Modal visible={isVisible} animationType="slide" transparent>
      <View style={styles.overlay}>
        <View style={[styles.sidebar, { backgroundColor: colors.cardBg }]}>
          <TouchableOpacity style={styles.closeButton} onPress={onClose}>
            <Ionicons name="close" size={24} color={colors.primary} />
          </TouchableOpacity>
          {menuItems.map((item, index) => (
            <TouchableOpacity
              key={index}
              style={styles.menuItem}
              onPress={item.onPress}
            >
              <Ionicons
                name={item.icon}
                size={24}
                color={colors.primary}
                style={styles.menuIcon}
              />
              <ThemedText style={styles.menuLabel}>{item.label}</ThemedText>
            </TouchableOpacity>
          ))}
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.5)",
    justifyContent: "flex-end",
  },
  sidebar: {
    width: "80%",
    height: "100%",
    padding: 16,
    elevation: 4,
  },
  closeButton: {
    alignSelf: "flex-end",
    marginBottom: 16,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 12,
  },
  menuIcon: {
    marginRight: 12,
  },
  menuLabel: {
    fontSize: 16,
    fontWeight: "600",
  },
});

export default Sidebar;
