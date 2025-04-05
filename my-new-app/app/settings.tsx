import { View, StyleSheet } from "react-native";
import { LinearGradient } from "expo-linear-gradient";
import { COLORS } from "@/constants/Colors";
import { ThemedText } from "@/components/ThemedText";
import ApiConnectionTester from "@/components/ApiConnectionTester";

export default function SettingsScreen() {
  return (
    <LinearGradient colors={COLORS.gradient} style={styles.container}>
      <View style={styles.header}>
        <ThemedText style={styles.headerText}>Settings</ThemedText>
      </View>
      <View style={styles.content}>
        <ThemedText style={styles.sectionTitle}>API Configuration</ThemedText>
        <ApiConnectionTester colors={COLORS} />
      </View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    paddingTop: 60,
    paddingBottom: 20,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: "rgba(255,255,255,0.1)",
  },
  headerText: {
    fontSize: 32,
    fontWeight: "bold",
    color: COLORS.light,
  },
  content: {
    padding: 20,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: "600",
    color: COLORS.light,
    marginBottom: 20,
  },
});
