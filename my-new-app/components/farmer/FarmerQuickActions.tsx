import React, { useRef, useEffect } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  Platform,
  Animated,
  Easing,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";

// Define valid icon names explicitly for type safety
type IoniconName = keyof typeof Ionicons.glyphMap;

interface ActionButtonProps {
  icon: IoniconName;
  color: string;
  label: string;
  backgroundColor: string;
  onPress: () => void;
  animationDelay: number;
}

// Reusable action button component for better structure
const ActionButton = ({
  icon,
  color,
  label,
  backgroundColor,
  onPress,
  animationDelay,
}: ActionButtonProps) => {
  const scale = useRef(new Animated.Value(0.8)).current;

  useEffect(() => {
    Animated.timing(scale, {
      toValue: 1,
      duration: 300,
      delay: animationDelay,
      useNativeDriver: true,
      easing: Easing.out(Easing.back(1.5)),
    }).start();
  }, []);

  return (
    <Animated.View style={{ transform: [{ scale }] }}>
      <TouchableOpacity
        style={styles.actionButton}
        onPress={onPress}
        activeOpacity={0.7}
        accessibilityLabel={label}
      >
        <View style={[styles.iconCircle, { backgroundColor }]}>
          <Ionicons name={icon} size={22} color={color} />
        </View>
        <ThemedText style={styles.actionLabel}>{label}</ThemedText>
      </TouchableOpacity>
    </Animated.View>
  );
};

interface QuickActionsProps {
  navigateToAddProduct: () => void;
  navigateToManageProducts: () => void;
  navigateToFarmProfile: () => void;
  navigateToOrders: () => void;
}

export function FarmerQuickActions({
  navigateToAddProduct,
  navigateToManageProducts,
  navigateToFarmProfile,
  navigateToOrders,
}: QuickActionsProps) {
  // Define action buttons with explicit typings
  const actionButtons: {
    icon: IoniconName;
    color: string;
    label: string;
    backgroundColor: string;
    onPress: () => void;
    animationDelay: number;
  }[] = [
    {
      icon: "add-circle" as IoniconName,
      color: COLORS.primary,
      label: "Add",
      backgroundColor: "rgba(27, 94, 32, 0.1)",
      onPress: navigateToAddProduct,
      animationDelay: 100,
    },
    {
      icon: "list" as IoniconName,
      color: COLORS.secondary,
      label: "Products",
      backgroundColor: "rgba(255, 193, 7, 0.1)",
      onPress: navigateToManageProducts,
      animationDelay: 150,
    },
    {
      icon: "cart" as IoniconName,
      color: COLORS.accent,
      label: "Orders",
      backgroundColor: "rgba(230, 81, 0, 0.1)",
      onPress: navigateToOrders,
      animationDelay: 200,
    },
    {
      icon: "leaf" as IoniconName,
      color: COLORS.success,
      label: "Farm",
      backgroundColor: "rgba(76, 175, 80, 0.1)",
      onPress: navigateToFarmProfile,
      animationDelay: 250,
    },
  ];

  return (
    <View style={styles.container}>
      <View style={styles.headerRow}>
        <ThemedText style={styles.title}>Quick Actions</ThemedText>
      </View>

      <View style={styles.actionsGrid}>
        {actionButtons.map((button, index) => (
          <View key={`action-${index}`} style={styles.actionButtonWrapper}>
            <ActionButton
              icon={button.icon}
              color={button.color}
              label={button.label}
              backgroundColor={button.backgroundColor}
              onPress={button.onPress}
              animationDelay={button.animationDelay}
            />
          </View>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
    borderRadius: 10,
    padding: 12,
    marginBottom: 10,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.05,
        shadowRadius: 2,
      },
      android: {
        elevation: 1,
      },
    }),
  },
  headerRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },
  title: {
    fontSize: 16,
    fontWeight: "bold",
    color: COLORS.text,
  },
  actionsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginHorizontal: -4, // Compensate for the padding in actionButtonWrapper
  },
  actionButtonWrapper: {
    width: "25%", // Exactly 4 buttons per row
    paddingHorizontal: 4,
  },
  actionButton: {
    alignItems: "center",
    justifyContent: "center",
    width: "100%",
  },
  iconCircle: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 4,
  },
  actionLabel: {
    fontSize: 10,
    textAlign: "center",
    color: COLORS.text,
    fontWeight: "500",
  },
});
