import { useRouter } from "expo-router";
import {
  StyleSheet,
  View,
  Dimensions,
  TouchableOpacity,
  Image,
  ScrollView,
  BackHandler,
} from "react-native";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { useEffect } from "react";
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withTiming,
} from "react-native-reanimated";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";

const { width, height } = Dimensions.get("window");

export default function AuthScreen() {
  const router = useRouter();
  const { user, isLoading } = useAuth();
  const opacity = useSharedValue(0);
  const translateY = useSharedValue(50);

  // Check if user is already authenticated and skip auth screen
  useEffect(() => {
    if (!isLoading && user) {
      console.log(
        "[Navigation] User already logged in, redirecting from auth screen to main"
      );
      // Replace navigation to eliminate this screen from history
      router.replace("/(tabs)/main");
    }
  }, [user, isLoading, router]);

  // Handle back button press to prevent going back to intro screens when logged in
  useEffect(() => {
    const backHandler = BackHandler.addEventListener(
      "hardwareBackPress",
      () => {
        if (user) {
          // If user is logged in, navigate to main screen instead of going back
          router.replace("/(tabs)/main");
          return true; // Prevents default back behavior
        }
        return false; // Allow default back behavior for guests
      }
    );

    return () => backHandler.remove();
  }, [user, router]);

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

  const handleLogin = () => {
    router.push("/(auth)/login");
  };

  const handleRegister = () => {
    router.push("/(auth)/register");
  };

  // If still loading auth state, don't render anything yet
  if (isLoading) {
    return null;
  }

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
        bounces={false} // Prevent bouncing which can show white space
      >
        <Animated.View style={[styles.logoContainer, animatedStyle]}>
          <View style={styles.logoCircle}>
            <Ionicons name="person" size={70} color={COLORS.light} />
          </View>
          <ThemedText style={styles.title}>Welcome</ThemedText>
          <View style={styles.divider} />
          <ThemedText style={styles.subtitle}>
            Join our growing community of farmers and consumers
          </ThemedText>
        </Animated.View>

        <Animated.View style={[styles.imageContainer, animatedStyle]}>
          <Image
            source={{
              uri: "https://images.unsplash.com/photo-1464226184884-fa280b87c399?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80",
            }}
            style={styles.heroImage}
            resizeMode="cover"
          />
          <View style={styles.imageOverlay} />
          <View style={styles.imageTextContainer}>
            <ThemedText style={styles.imageText}>
              Connect • Trade • Grow
            </ThemedText>
          </View>
        </Animated.View>

        <Animated.View style={[styles.description, animatedStyle]}>
          <ThemedText style={styles.descriptionText}>
            Access exclusive features, connect with local farmers, and discover
            fresh produce by creating an account or signing in.
          </ThemedText>
        </Animated.View>

        <Animated.View style={[styles.buttonContainer, animatedStyle]}>
          <TouchableOpacity
            style={styles.loginButton}
            onPress={handleLogin}
            activeOpacity={0.8}
          >
            <LinearGradient
              colors={[COLORS.accent, "#FF7D26"]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.buttonGradient}
            >
              <Ionicons
                name="log-in-outline"
                size={22}
                color={COLORS.light}
                style={styles.buttonIcon}
              />
              <ThemedText style={styles.buttonText}>Login</ThemedText>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.registerButton}
            onPress={handleRegister}
            activeOpacity={0.8}
          >
            <LinearGradient
              colors={[COLORS.secondary, "#FFD54F"]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.buttonGradient}
            >
              <Ionicons
                name="person-add-outline"
                size={22}
                color={COLORS.dark}
                style={styles.buttonIcon}
              />
              <ThemedText style={[styles.buttonText, { color: COLORS.dark }]}>
                Register
              </ThemedText>
            </LinearGradient>
          </TouchableOpacity>
        </Animated.View>

        <Animated.View style={[styles.footerContainer, animatedStyle]}>
          <ThemedText style={styles.footerText}>
            By continuing, you agree to our Terms of Service and Privacy Policy
          </ThemedText>
        </Animated.View>
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
    paddingHorizontal: 24,
    paddingTop: 60,
    paddingBottom: 40,
    flexGrow: 1, // Use flexGrow instead of minHeight for better behavior
    // Remove minHeight to prevent scrollable white space
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
  logoContainer: {
    alignItems: "center",
    marginBottom: 30,
    marginTop: 10, // Added marginTop to push content down slightly
  },
  logoCircle: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: "rgba(255,255,255,0.1)",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 2,
    borderColor: "rgba(255,255,255,0.3)",
    marginBottom: 20,
  },
  title: {
    fontSize: 27, // You can increase or decrease this value
    fontWeight: "bold",
    color: COLORS.light,
    textAlign: "center",
  },
  divider: {
    width: 60,
    height: 3,
    backgroundColor: COLORS.secondary,
    marginVertical: 15,
    borderRadius: 10,
  },
  subtitle: {
    fontSize: 16, // Adjust this value to make subtitle text bigger or smaller
    color: "rgba(255,255,255,0.8)",
    textAlign: "center",
  },
  imageContainer: {
    width: "100%",
    height: 180,
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
    backgroundColor: "rgba(27, 94, 32, 0.3)",
  },
  imageTextContainer: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    padding: 15,
    backgroundColor: "rgba(0,0,0,0.3)",
  },
  imageText: {
    color: COLORS.light,
    fontSize: 18, // Modify this value to adjust image overlay text size
    fontWeight: "700",
    textAlign: "center",
  },
  description: {
    marginBottom: 30,
  },
  descriptionText: {
    fontSize: 16, // Change this to adjust description text size
    color: COLORS.light,
    textAlign: "center",
    lineHeight: 24,
  },
  buttonContainer: {
    width: "100%",
    marginBottom: 20,
  },
  loginButton: {
    width: "100%",
    marginBottom: 15,
    borderRadius: 12,
    overflow: "hidden",
    elevation: 3,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
  },
  registerButton: {
    width: "100%",
    borderRadius: 12,
    overflow: "hidden",
    elevation: 3,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
  },
  buttonGradient: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 16,
  },
  buttonIcon: {
    marginRight: 10,
  },
  buttonText: {
    fontSize: 18, // Adjust button text size here
    fontWeight: "600",
    color: COLORS.light,
  },
  footerContainer: {
    marginTop: 20,
  },
  footerText: {
    fontSize: 12, // Change this for footer text size
    color: "rgba(255,255,255,0.6)",
    textAlign: "center",
  },
});
