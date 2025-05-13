import { Stack } from "expo-router";
import { COLORS } from "@/constants/Colors";
import React from "react";
import { useAuth } from "@/contexts/AuthContext";

/**
 * Layout for consumer-specific screens
 * Handles navigation structure for all consumer features
 */
export default function ConsumerLayout() {
  const { isConsumer } = useAuth();

  return (
    <Stack
      screenOptions={{
        headerStyle: {
          backgroundColor: COLORS.primary,
        },
        headerTintColor: COLORS.light,
        headerTitleStyle: {
          fontWeight: "bold",
        },
      }}
    >
      <Stack.Screen
        name="dashboard"
        options={{
          title: "Market",
          headerShown: false,
        }}
      />
      <Stack.Screen
        name="market"
        options={{
          title: "Market",
          headerShown: false, // Hide the header for market screen to prevent duplication
        }}
      />
      <Stack.Screen
        name="orders"
        options={{
          title: "My Orders",
        }}
      />
      <Stack.Screen
        name="profile"
        options={{
          title: "My Profile",
        }}
      />
      <Stack.Screen
        name="about"
        options={{
          title: "About",
        }}
      />
    </Stack>
  );
}
