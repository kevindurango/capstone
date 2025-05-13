import React from "react";
import { useFonts } from "expo-font";
import { Stack, SplashScreen, Slot } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { useEffect } from "react";
import { View, Text } from "react-native";
import { enableScreens } from "react-native-screens";
import "react-native-reanimated";

import { useColorScheme } from "@/hooks/useColorScheme";
import { AuthProvider } from "@/contexts/AuthContext";
import { CartProvider } from "@/components/market/CartContext";
import { resetApiUrl } from "@/services/apiConfig";

// Enable screens for better navigation performance
enableScreens();

// Prevent the splash screen from auto-hiding before asset loading is complete.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const colorScheme = useColorScheme();
  const [loaded] = useFonts({
    SpaceMono: require("../assets/fonts/SpaceMono-Regular.ttf"),
  });

  useEffect(() => {
    if (loaded) {
      SplashScreen.hideAsync();
    }
  }, [loaded]);

  // Reset API URL when app starts to ensure we use the latest IP address
  useEffect(() => {
    const updateApiConfig = async () => {
      try {
        // Reset to use the current value from IPConfig.js
        const newUrl = await resetApiUrl();
        console.log("[App] Reset API URL to:", newUrl);
      } catch (error) {
        console.error("[App] Error resetting API URL:", error);
      }
    };

    updateApiConfig();
  }, []);

  // Add debug logging
  console.log("[RootLayout] Font loaded:", loaded);

  if (!loaded) {
    return (
      <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
        <Text>Loading fonts...</Text>
      </View>
    );
  }

  // Using Slot with no additional navigator components to avoid navigation conflicts
  return (
    <AuthProvider>
      <CartProvider>
        <Slot />
        <StatusBar style={colorScheme === "dark" ? "light" : "dark"} />
      </CartProvider>
    </AuthProvider>
  );
}
