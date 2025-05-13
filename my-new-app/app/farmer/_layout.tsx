import { Stack } from "expo-router";
import { COLORS } from "@/constants/Colors";
import React from "react";
import { useAuth } from "@/contexts/AuthContext";

/**
 * Layout for farmer-specific screens
 * Handles navigation structure for all farmer features
 */
export default function FarmerLayout() {
  const { isFarmer } = useAuth();

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
          title: "Farmer Dashboard",
          headerShown: false,
        }}
      />
      <Stack.Screen
        name="products"
        options={{
          title: "My Products",
          headerShown: false, // Hide default header
        }}
      />
      <Stack.Screen
        name="add-product"
        options={{
          title: "Add New Product",
          headerShown: false, // Hide default header
        }}
      />
      <Stack.Screen
        name="orders"
        options={{
          title: "Orders",
          headerShown: false, // Hide default header
        }}
      />
      <Stack.Screen
        name="profile"
        options={{
          title: "Farm Profile",
          headerShown: false, // Hide default header
        }}
      />
      <Stack.Screen
        name="farm-profile"
        options={{
          title: "Farm Profile",
          headerShown: false, // Hide the default header
        }}
      />
    </Stack>
  );
}
