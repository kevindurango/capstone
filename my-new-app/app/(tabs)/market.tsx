import React from "react";
import { useAuth } from "@/contexts/AuthContext";
import { View } from "react-native";
import MarketContent from "@/components/market/MarketContent";

/**
 * Market page showing product listings and allowing users to shop
 */
export default function Market() {
  const { isAuthenticated } = useAuth();

  // If authentication is still being checked, show a loading state
  if (isAuthenticated === undefined) {
    return <View style={{ flex: 1 }} />;
  }

  // Display the MarketContent component regardless of authentication status
  // The MarketContent component has its own authentication handling
  return <MarketContent />;
}
