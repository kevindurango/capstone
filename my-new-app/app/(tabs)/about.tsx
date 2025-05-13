import React from "react";
import { StyleSheet, View } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { router } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";

export default function AboutScreen() {
  const { isAuthenticated, isFarmer, isConsumer } = useAuth();

  // Redirect if authenticated
  React.useEffect(() => {
    if (isAuthenticated) {
      if (isFarmer) {
        router.replace("/farmer/dashboard");
      } else if (isConsumer) {
        router.replace("/consumer/about");
      }
    }
  }, [isAuthenticated, isFarmer, isConsumer]);

  return (
    <View style={styles.container}>
      <ThemedText style={styles.title}>About</ThemedText>
      <ThemedText style={styles.description}>
        Learn about the Farmer's Market application and its mission to connect
        local farmers with consumers.
      </ThemedText>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: "bold",
    marginBottom: 20,
  },
  description: {
    fontSize: 16,
    textAlign: "center",
  },
});
