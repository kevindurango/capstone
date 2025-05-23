import { useRouter } from "expo-router";
import {
  StyleSheet,
  View,
  Dimensions,
  Animated,
  BackHandler,
  Platform,
  Text,
} from "react-native";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useState, useEffect, useRef } from "react";
import GetStartedButton from "@/components/GetStartedButton";

// Updated color scheme inspired by agricultural themes and Filipino colors
const COLORS = {
  primary: "#1B5E20", // Deep forest green
  secondary: "#FFC107", // Golden yellow (represents rice/crops)
  accent: "#E65100", // Terracotta (represents soil)
  light: "#FFFFFF", // Clean white
  dark: "#1B5E20", // Deep green
  text: "#263238", // Dark blue-grey
  muted: "#78909C", // Muted blue-grey
  gradient: ["#2E7D32", "#1B5E20", "#0D3010"], // Multi-tone green gradient
  shadow: "#000000",
};

export default function WelcomeScreen() {
  const router = useRouter();
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(50)).current;
  const logoScale = useRef(new Animated.Value(0.3)).current;
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Pulse animation for the logo
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {
          toValue: 1.1,
          duration: 1000,
          useNativeDriver: true,
        }),
        Animated.timing(pulseAnim, {
          toValue: 1,
          duration: 1000,
          useNativeDriver: true,
        }),
      ])
    ).start();

    // Subtle rotation animation for the logo background
    Animated.loop(
      Animated.sequence([
        Animated.timing(rotateAnim, {
          toValue: 1,
          duration: 10000,
          useNativeDriver: true,
        }),
        Animated.timing(rotateAnim, {
          toValue: 0,
          duration: 10000,
          useNativeDriver: true,
        }),
      ])
    ).start();

    // Main entrance animations
    Animated.sequence([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 1000,
        useNativeDriver: true,
      }),
      Animated.parallel([
        Animated.spring(slideAnim, {
          toValue: 0,
          tension: 20,
          useNativeDriver: true,
        }),
        Animated.spring(logoScale, {
          toValue: 1,
          tension: 20,
          useNativeDriver: true,
        }),
      ]),
    ]).start();
  }, []);

  const handleGetStarted = () => {
    // Simply navigate to the intro screen without checking authentication
    router.push("/(tabs)/intro");
  };

  // Create interpolated rotation value
  const spin = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ["0deg", "360deg"],
  });

  // Render the welcome screen
  return (
    <LinearGradient
      colors={COLORS.gradient}
      start={[0, 0]}
      end={[1, 1]}
      style={styles.container}
    >
      {/* Decorative background elements */}
      <View style={styles.backgroundDecoration1} />
      <View style={styles.backgroundDecoration2} />

      <Animated.View
        style={[
          styles.logoContainer,
          {
            opacity: fadeAnim,
            transform: [{ scale: logoScale }, { scale: pulseAnim }],
          },
        ]}
      >
        <View style={styles.logoInner}>
          <Animated.View
            style={[styles.logoBackground, { transform: [{ rotate: spin }] }]}
          />
          <View style={styles.logoIconContainer}>
            <Ionicons name="leaf" size={80} color={COLORS.light} />
          </View>
        </View>
      </Animated.View>

      <Animated.View
        style={[
          styles.textContainer,
          {
            opacity: fadeAnim,
            transform: [{ translateY: slideAnim }],
          },
        ]}
      >
        <ThemedText style={styles.welcomeText}>Welcome to</ThemedText>
        <ThemedText style={styles.title}>NEGROS ORIENTAL</ThemedText>
        <ThemedText style={styles.subtitle}>
          MUNICIPAL AGRICULTURE OFFICE
        </ThemedText>
        <View style={styles.separatorContainer}>
          <View style={styles.separator} />
          <View style={styles.separatorDot} />
          <View style={styles.separator} />
        </View>
        <ThemedText style={styles.description}>
          Connecting Farmers to Communities
        </ThemedText>
      </Animated.View>

      <Animated.View
        style={[
          styles.buttonContainer,
          {
            opacity: fadeAnim,
            transform: [{ translateY: slideAnim }],
          },
        ]}
      >
        <GetStartedButton
          title="Explore Services"
          onPress={handleGetStarted}
          buttonColor={COLORS.accent}
        />
      </Animated.View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingTop: 60,
    paddingBottom: 30,
    position: "relative", // For absolute positioned decoration elements
    overflow: "hidden",
  },
  // Decorative background elements
  backgroundDecoration1: {
    position: "absolute",
    width: 300,
    height: 300,
    borderRadius: 150,
    backgroundColor: "rgba(255,255,255,0.05)",
    top: -100,
    right: -100,
  },
  backgroundDecoration2: {
    position: "absolute",
    width: 200,
    height: 200,
    borderRadius: 100,
    backgroundColor: "rgba(255,255,255,0.05)",
    bottom: -50,
    left: -50,
  },
  logoContainer: {
    height: "35%",
    justifyContent: "center",
    alignItems: "center",
  },
  logoInner: {
    width: 160,
    height: 160,
    justifyContent: "center",
    alignItems: "center",
    position: "relative",
  },
  logoBackground: {
    position: "absolute",
    width: "100%",
    height: "100%",
    borderRadius: 80,
    backgroundColor: "rgba(255, 255, 255, 0.15)",
    transform: [{ rotate: "45deg" }],
  },
  logoIconContainer: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: "rgba(255, 255, 255, 0.1)",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 2,
    borderColor: "rgba(255, 255, 255, 0.3)",
  },
  textContainer: {
    height: "40%", // Reduced from 45% to give more space below
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 30,
  },
  welcomeText: {
    fontSize: 24,
    color: "rgba(255, 255, 255, 0.8)",
    marginBottom: 10,
    fontWeight: "500",
  },
  title: {
    fontSize: 32,
    fontWeight: "900",
    color: COLORS.light,
    letterSpacing: 2,
    textAlign: "center",
    marginBottom: 8,
    includeFontPadding: false,
    lineHeight: 40,
    textShadowColor: "rgba(0, 0, 0, 0.3)",
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 3,
  },
  subtitle: {
    fontSize: 18,
    color: COLORS.secondary,
    letterSpacing: 1.2,
    textAlign: "center",
    marginBottom: 20,
    fontWeight: "600",
    includeFontPadding: false,
    lineHeight: 24,
  },
  separatorContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 20,
    width: "80%",
    justifyContent: "center",
  },
  separator: {
    width: "40%",
    height: 2,
    backgroundColor: COLORS.accent,
    borderRadius: 2,
  },
  separatorDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: COLORS.accent,
    marginHorizontal: 8,
  },
  description: {
    fontSize: 18,
    color: COLORS.light,
    textAlign: "center",
    fontWeight: "500",
    letterSpacing: 0.5,
    opacity: 0.9,
  },
  buttonContainer: {
    width: "100%",
    paddingHorizontal: 30,
    height: "25%", // Increased from 20% to allow button to be positioned higher
    justifyContent: "flex-start", // Changed from center to flex-start
    paddingTop: 20, // Add padding top to position button higher
  },
});
