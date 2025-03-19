import { useRouter } from "expo-router";
import {
  StyleSheet,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  ScrollView,
} from "react-native";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useState, useRef, useEffect } from "react";
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withTiming,
  interpolate,
} from "react-native-reanimated";

const { width, height } = Dimensions.get("window");

const COLORS = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  gradient: ["#2E7D32", "#1B5E20", "#0D3010"] as const,
  shadow: "#000000",
};

// Card data for the benefits section
const benefitCards = [
  {
    icon: "leaf-outline" as const,
    title: "Quality Products",
    description: "Direct from local farmers, ensuring freshness and quality",
  },
  {
    icon: "people-outline" as const,
    title: "Support Farmers",
    description:
      "Your purchase directly supports local agricultural communities",
  },
  {
    icon: "globe-outline" as const,
    title: "Market Access",
    description: "Connecting farmers to wider markets and opportunities",
  },
];

export default function IntroScreen() {
  const router = useRouter();
  const opacity = useSharedValue(0);
  const translateY = useSharedValue(50);

  useEffect(() => {
    opacity.value = withTiming(1, { duration: 1000 });
    translateY.value = withTiming(0, { duration: 800 });
  }, []);

  const animatedStyle = useAnimatedStyle(() => {
    return {
      opacity: opacity.value,
      transform: [{ translateY: translateY.value }],
    };
  });

  const handleNext = () => {
    router.push("/(tabs)/auth");
  };

  return (
    <LinearGradient
      colors={COLORS.gradient}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.container}
    >
      {/* Decorative elements */}
      <View style={styles.decorationCircle1} />
      <View style={styles.decorationCircle2} />

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <Animated.View style={[styles.headerContainer, animatedStyle]}>
          <Ionicons name="basket" size={60} color={COLORS.secondary} />
          <ThemedText style={styles.title}>Farm to Table Platform</ThemedText>
          <View style={styles.divider} />
          <ThemedText style={styles.subtitle}>
            Connecting local farmers directly to communities
          </ThemedText>
        </Animated.View>

        <Animated.View style={[styles.imageContainer, animatedStyle]}>
          <Image
            source={{
              uri: "https://images.unsplash.com/photo-1500651230702-0e2d8a49d4ad?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80",
            }}
            style={styles.heroImage}
            resizeMode="cover"
          />
          <View style={styles.imageOverlay} />
        </Animated.View>

        <Animated.View style={[styles.infoContainer, animatedStyle]}>
          <ThemedText style={styles.sectionTitle}>
            Why Choose Our Platform?
          </ThemedText>

          <View style={styles.cardsContainer}>
            {benefitCards.map((card, index) => (
              <View key={index} style={styles.card}>
                <View style={styles.cardIconContainer}>
                  <Ionicons name={card.icon} size={28} color={COLORS.primary} />
                </View>
                <ThemedText style={styles.cardTitle}>{card.title}</ThemedText>
                <ThemedText style={styles.cardDescription}>
                  {card.description}
                </ThemedText>
              </View>
            ))}
          </View>

          <ThemedText style={styles.description}>
            The Municipal Agriculture Office provides this innovative platform
            connecting farmers directly to consumers, expanding market reach
            while ensuring fair prices for quality local produce.
          </ThemedText>
        </Animated.View>

        <TouchableOpacity
          style={styles.nextButton}
          onPress={handleNext}
          activeOpacity={0.8}
        >
          <LinearGradient
            colors={[COLORS.accent, "#FF7D26"]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.buttonGradient}
          >
            <ThemedText style={styles.buttonText}>Continue</ThemedText>
            <Ionicons name="arrow-forward" size={20} color={COLORS.light} />
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    position: "relative",
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 40,
  },
  decorationCircle1: {
    position: "absolute",
    width: width * 0.6,
    height: width * 0.6,
    borderRadius: width * 0.3,
    backgroundColor: "rgba(255,255,255,0.05)",
    top: -width * 0.2,
    right: -width * 0.2,
  },
  decorationCircle2: {
    position: "absolute",
    width: width * 0.4,
    height: width * 0.4,
    borderRadius: width * 0.2,
    backgroundColor: "rgba(255,255,255,0.05)",
    bottom: -width * 0.1,
    left: -width * 0.1,
  },
  headerContainer: {
    alignItems: "center",
    marginBottom: 30,
  },
  title: {
    fontSize: 28,
    fontWeight: "bold",
    color: COLORS.light,
    marginTop: 15,
    textAlign: "center",
  },
  divider: {
    width: 80,
    height: 3,
    backgroundColor: COLORS.secondary,
    marginVertical: 15,
    borderRadius: 10,
  },
  subtitle: {
    fontSize: 16,
    color: "rgba(255,255,255,0.8)",
    textAlign: "center",
  },
  imageContainer: {
    width: "100%",
    height: 200,
    borderRadius: 15,
    overflow: "hidden",
    marginBottom: 30,
    elevation: 5,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
  },
  heroImage: {
    width: "100%",
    height: "100%",
  },
  imageOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(27, 94, 32, 0.2)",
  },
  infoContainer: {
    marginBottom: 30,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: "700",
    color: COLORS.secondary,
    marginBottom: 20,
    textAlign: "center",
  },
  cardsContainer: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
    marginBottom: 20,
  },
  card: {
    width: "48%",
    backgroundColor: "rgba(255,255,255,0.9)",
    borderRadius: 12,
    padding: 15,
    marginBottom: 15,
    elevation: 3,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 3,
  },
  cardIconContainer: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: "rgba(255, 193, 7, 0.2)",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 10,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: "700",
    color: COLORS.dark,
    marginBottom: 5,
  },
  cardDescription: {
    fontSize: 12,
    color: COLORS.text,
    lineHeight: 18,
  },
  description: {
    fontSize: 16,
    color: COLORS.light,
    textAlign: "center",
    lineHeight: 24,
  },
  nextButton: {
    width: "100%",
    marginTop: 10,
    borderRadius: 30,
    overflow: "hidden",
    elevation: 5,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
  },
  buttonGradient: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 15,
    paddingHorizontal: 20,
  },
  buttonText: {
    color: COLORS.light,
    fontSize: 18,
    fontWeight: "600",
    marginRight: 10,
  },
});
