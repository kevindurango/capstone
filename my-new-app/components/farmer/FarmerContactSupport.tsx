import React from "react";
import { StyleSheet, View } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";

// Define color scheme
const COLORS = {
  primary: "#1B5E20",
  text: "#263238",
  cardBg: "#F9FBF7",
  shadow: "#000000",
};

export function FarmerContactSupport() {
  return (
    <View style={styles.contactContainer}>
      <ThemedText style={styles.contactTitle}>Agricultural Support</ThemedText>
      <View style={styles.divider} />
      <View style={styles.contactInfo}>
        <View style={styles.contactItem}>
          <Ionicons name="location" size={24} color={COLORS.primary} />
          <ThemedText style={styles.contactText}>
            Municipal Agriculture Office, Palinpinon
          </ThemedText>
        </View>
        <View style={styles.contactItem}>
          <Ionicons name="call" size={24} color={COLORS.primary} />
          <ThemedText style={styles.contactText}>(035) 225-0000</ThemedText>
        </View>
        <View style={styles.contactItem}>
          <Ionicons name="mail" size={24} color={COLORS.primary} />
          <ThemedText style={styles.contactText}>
            agriculture@negor.gov.ph
          </ThemedText>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  contactContainer: {
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
    borderWidth: 1,
    borderColor: "#E0E0E0",
  },
  divider: {
    height: 1,
    backgroundColor: "#E0E0E0",
    marginVertical: 8,
  },
  contactTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  contactInfo: {
    marginTop: 8,
  },
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  contactText: {
    fontSize: 14,
    color: COLORS.text,
    marginLeft: 8,
  },
});
