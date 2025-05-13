import React from "react";
import { SafeAreaView, StyleSheet, View } from "react-native";
import Orders from "@/components/market/Orders";
import { useAuth } from "@/contexts/AuthContext";
import { Redirect, useRouter } from "expo-router";
import ConsumerNavigation from "@/components/market/ConsumerNavigation";

/**
 * Consumer Orders Screen
 * This screen shows all user orders and allows for feedback submission
 */
export default function ConsumerOrders() {
  const { isAuthenticated, isConsumer } = useAuth();
  const router = useRouter();

  // Check authentication
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect farmers to farmer dashboard
  if (!isConsumer) {
    return <Redirect href="/farmer/dashboard" />;
  }

  // Handler for back button navigation
  const handleClose = () => {
    router.replace("/(tabs)");
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Orders visible={true} onClose={handleClose} standalone={true} />
      </View>
      <ConsumerNavigation />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  content: {
    flex: 1,
  },
});
