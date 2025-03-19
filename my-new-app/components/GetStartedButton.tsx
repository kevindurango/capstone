import React from "react";
import { StyleSheet, TouchableOpacity, View } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";

// Color scheme inspired by agricultural themes and Filipino colors
const COLORS = {
  primary: "#1B5E20", // Deep forest green
  secondary: "#FFC107", // Golden yellow (represents rice/crops)
  accent: "#E65100", // Terracotta (represents soil)
  light: "#FFFFFF", // Clean white
  dark: "#1B5E20", // Deep green
  shadow: "#000000",
};

interface GetStartedButtonProps {
  title: string;
  onPress: () => void;
  buttonColor?: string;
  textColor?: string;
}

const GetStartedButton: React.FC<GetStartedButtonProps> = ({
  title,
  onPress,
  buttonColor = COLORS.accent,
  textColor = COLORS.light,
}) => {
  return (
    <TouchableOpacity
      style={[styles.getStartedButton, { backgroundColor: buttonColor }]}
      onPress={onPress}
    >
      <ThemedText style={[styles.buttonText, { color: textColor }]}>
        {title}
      </ThemedText>
      <View style={styles.arrowContainer}>
        <Ionicons name="arrow-forward" size={24} color={textColor} />
      </View>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  getStartedButton: {
    paddingVertical: 18,
    paddingHorizontal: 32,
    borderRadius: 16,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 12,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 8,
    borderWidth: 1,
    borderColor: "rgba(255, 255, 255, 0.2)",
  },
  arrowContainer: {
    backgroundColor: "rgba(255,255,255,0.2)",
    borderRadius: 12,
    padding: 4,
  },
  buttonText: {
    fontSize: 20,
    fontWeight: "700",
    letterSpacing: 1,
  },
});

export default GetStartedButton;
