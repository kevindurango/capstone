import React from "react";
import { StyleSheet, View } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { MaterialCommunityIcons, Ionicons } from "@expo/vector-icons";

// Define color scheme
const COLORS = {
  primary: "#1B5E20",
  text: "#263238",
  cardBg: "#F9FBF7",
  shadow: "#000000",
};

export function FarmerTips() {
  return (
    <View style={styles.tipsContainer}>
      <ThemedText style={styles.sectionTitle}>Farming Tips</ThemedText>

      <View style={styles.tipCard}>
        <View style={styles.tipIconContainer}>
          <MaterialCommunityIcons
            name="seed"
            size={32}
            color={COLORS.primary}
          />
        </View>
        <View style={styles.tipContent}>
          <ThemedText style={styles.tipTitle}>Organic Pest Control</ThemedText>
          <ThemedText style={styles.tipDescription}>
            Create natural pest repellents using neem oil or garlic spray to
            protect your crops without harmful chemicals.
          </ThemedText>
        </View>
      </View>

      <View style={styles.tipCard}>
        <View style={styles.tipIconContainer}>
          <MaterialCommunityIcons
            name="water"
            size={32}
            color={COLORS.primary}
          />
        </View>
        <View style={styles.tipContent}>
          <ThemedText style={styles.tipTitle}>Water Management</ThemedText>
          <ThemedText style={styles.tipDescription}>
            Water deeply but infrequently to encourage strong root growth.
            Morning watering reduces evaporation and fungal diseases.
          </ThemedText>
        </View>
      </View>

      <View style={styles.tipCard}>
        <View style={styles.tipIconContainer}>
          <Ionicons name="analytics-outline" size={32} color={COLORS.primary} />
        </View>
        <View style={styles.tipContent}>
          <ThemedText style={styles.tipTitle}>Market Analysis</ThemedText>
          <ThemedText style={styles.tipDescription}>
            Research market trends before planting to ensure demand for your
            crops. Focus on high-value products with local demand.
          </ThemedText>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  tipsContainer: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 12,
    fontWeight: "700",
  },
  tipCard: {
    flexDirection: "row",
    backgroundColor: COLORS.cardBg,
    borderRadius: 8,
    padding: 16,
    marginBottom: 8,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  tipIconContainer: {
    backgroundColor: "#FFFFFF",
    width: 50,
    height: 50,
    borderRadius: 25,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  tipContent: {
    flex: 1,
  },
  tipTitle: {
    fontSize: 16,
    fontWeight: "600",
    color: COLORS.primary,
    marginBottom: 4,
  },
  tipDescription: {
    fontSize: 14,
    color: COLORS.text,
  },
});
