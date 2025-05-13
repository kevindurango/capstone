import React from "react";
import { StyleSheet, View } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { router } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";

export default function ProfileScreen() {
  const { isAuthenticated, isFarmer, isConsumer } = useAuth();

  // Redirect to appropriate profile based on user role
  React.useEffect(() => {
    if (isAuthenticated) {
      if (isFarmer) {
        router.replace("/farmer/profile");
      } else if (isConsumer) {
        router.replace("/consumer/profile");
      }
    }
  }, [isAuthenticated, isFarmer, isConsumer]);

  return (
    <View style={styles.container}>
      <ThemedText style={styles.title}>Profile</ThemedText>
      <ThemedText style={styles.description}>
        Your profile information will appear here
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
