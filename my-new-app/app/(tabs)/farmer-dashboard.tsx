import { Redirect } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";
import { View, ActivityIndicator } from "react-native";

export default function FarmerDashboardRedirect() {
  const { isAuthenticated, isFarmer } = useAuth();

  // Show loading while checking authentication
  if (isAuthenticated === undefined) {
    return (
      <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
        <ActivityIndicator size="large" color="#1B5E20" />
      </View>
    );
  }

  // If not authenticated, redirect to login
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // If authenticated but not a farmer, redirect to main
  if (!isFarmer) {
    return <Redirect href="/(tabs)/main" />;
  }

  // If authenticated and a farmer, redirect to the new farmer dashboard
  return <Redirect href="/farmer/dashboard" />;
}
