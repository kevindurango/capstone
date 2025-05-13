import React, { useEffect } from "react";
import { StyleSheet, View, Text } from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";

/**
 * Main screen that routes users to the appropriate dashboard based on their role
 */
export default function MainScreen() {
  const { isAuthenticated, isFarmer, isConsumer } = useAuth();
  const router = useRouter();

  useEffect(() => {
    // If the user is authenticated, redirect them to their appropriate dashboard
    if (isAuthenticated) {
      if (isFarmer) {
        router.replace("/farmer/dashboard");
      } else if (isConsumer) {
        // Updated to be consistent with login flow - redirects consumer to their dashboard
        router.replace("/consumer/dashboard");
      }
    }
  }, [isAuthenticated, isFarmer, isConsumer, router]);

  return (
    <View style={styles.container}>
      <Text style={styles.text}>Redirecting to your dashboard...</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f5fcff",
  },
  text: {
    fontSize: 16,
    color: "#333",
  },
});
