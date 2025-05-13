import React from "react";
import { useAuth } from "@/contexts/AuthContext";
import { View } from "react-native";
import MarketContent from "@/components/market/MarketContent";
import { Redirect } from "expo-router";

/**
 * Consumer Market page showing product listings and allowing consumers to shop
 */
export default function ConsumerMarket() {
  const { isAuthenticated, isConsumer } = useAuth();

  // If authentication is still being checked, show a loading state
  if (isAuthenticated === undefined) {
    return <View style={{ flex: 1 }} />;
  }

  // Redirect unauthenticated users to login
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect farmers to farmer dashboard
  if (!isConsumer) {
    return <Redirect href="/farmer/dashboard" />;
  }

  // Display the MarketContent component for authenticated consumers
  return <MarketContent />;
}
