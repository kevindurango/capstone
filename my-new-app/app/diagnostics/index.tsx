import React from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
} from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

export default function DiagnosticsScreen() {
  return (
    <View style={styles.container}>
      <Stack.Screen
        options={{
          title: "Diagnostics Tools",
          headerStyle: {
            backgroundColor: "#2196F3",
          },
          headerTintColor: "#fff",
        }}
      />

      <ScrollView style={styles.content}>
        <Text style={styles.title}>Developer Tools</Text>
        <Text style={styles.description}>
          Use these diagnostic tools to troubleshoot common issues with the app.
        </Text>

        <View style={styles.emptyState}>
          <Ionicons name="build-outline" size={64} color="#ccc" />
          <Text style={styles.emptyText}>No diagnostic tools available</Text>
          <Text style={styles.emptySubtext}>
            Additional diagnostic tools will appear here when needed
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f9fa",
  },
  content: {
    flex: 1,
    padding: 16,
  },
  title: {
    fontSize: 22,
    fontWeight: "bold",
    marginBottom: 8,
  },
  description: {
    fontSize: 14,
    color: "#666",
    marginBottom: 24,
  },
  toolCard: {
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    flexDirection: "row",
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  toolIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#e3f2fd",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 16,
  },
  toolContent: {
    flex: 1,
  },
  toolTitle: {
    fontSize: 16,
    fontWeight: "600",
    marginBottom: 4,
  },
  toolDescription: {
    fontSize: 12,
    color: "#666",
  },
  emptyState: {
    alignItems: "center",
    justifyContent: "center",
    padding: 40,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: "500",
    color: "#666",
    marginTop: 16,
  },
  emptySubtext: {
    fontSize: 14,
    color: "#999",
    textAlign: "center",
    marginTop: 8,
  },
});
