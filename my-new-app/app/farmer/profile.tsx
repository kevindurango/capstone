import React, { useState, useEffect } from "react";
import {
  SafeAreaView,
  StyleSheet,
  View,
  ActivityIndicator,
} from "react-native";
import { useAuth } from "@/contexts/AuthContext";
import FarmerProfile from "@/components/farmer/FarmerProfile";
import { Redirect, useRouter } from "expo-router";
import { authService } from "@/services/authService";
import { COLORS } from "@/constants/Colors";

/**
 * Farmer Profile Screen
 * Allows farmers to view and edit their profile information
 */
export default function FarmerProfileScreen() {
  const { isAuthenticated, isFarmer, logout } = useAuth();
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);

  // Check authentication
  if (isAuthenticated === undefined) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  // Redirect unauthenticated users to login
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect consumers to consumer dashboard
  if (!isFarmer) {
    return <Redirect href="/consumer/dashboard" />;
  }

  const handleNavigateToFarmProfile = () => {
    // This function can be used for additional navigation needs
    // Since we're already on the profile page, it doesn't need to do anything
    // It's kept for compatibility with the FarmerProfile component
  };

  const handleLogout = async (): Promise<boolean> => {
    try {
      setIsLoading(true);
      // Use the logout function from AuthContext instead of direct service call
      await logout();
      router.replace("/(auth)/login");
      return true;
    } catch (error) {
      console.error("Error logging out:", error);
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <FarmerProfile
        navigateToFarmProfile={handleNavigateToFarmProfile}
        handleLogout={handleLogout}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
    padding: 16,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f8f8f8",
  },
});
