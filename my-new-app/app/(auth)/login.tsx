import { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  Alert,
  BackHandler,
  Text,
  Image,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
  Dimensions,
  StatusBar,
} from "react-native";
import { useRouter, useLocalSearchParams } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { authService } from "@/services/authService";
import { useAuth } from "@/contexts/AuthContext";
import {
  SPACING,
  BORDER_RADIUS,
  SHADOWS,
  UI_STYLES,
  EXTENDED_COLORS,
} from "@/constants/styles";
import Animated, {
  FadeInDown,
  FadeInUp,
  useSharedValue,
  useAnimatedStyle,
  withTiming,
  SlideInUp,
} from "react-native-reanimated";

const { width } = Dimensions.get("window");

export default function LoginScreen() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const { login: authContextLogin, user } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [emailError, setEmailError] = useState("");
  const [passwordError, setPasswordError] = useState("");

  // Animation values
  const buttonScale = useSharedValue(1);
  const buttonAnimatedStyle = useAnimatedStyle(() => {
    return {
      transform: [{ scale: buttonScale.value }],
    };
  });

  useEffect(() => {
    const checkAuthStatus = async () => {
      try {
        const isAuthenticated = await authService.isAuthenticated(); // Check if the user is logged in
        if (isAuthenticated) {
          // If authenticated, check user role and redirect accordingly
          const userData = await authService.getUserData();
          if (userData && userData.role_id === 2) {
            // If user is a farmer, redirect to farmer dashboard
            console.log(
              "[Login] Authenticated farmer detected, redirecting to farmer dashboard"
            );
            router.replace("/farmer/dashboard");
          } else if (userData && userData.role_id !== 2) {
            // For regular users/consumers
            console.log(
              "[Login] Authenticated consumer detected, redirecting to main"
            );
            router.replace("/(tabs)/main");
          } else {
            console.warn("[Login] User role_id is undefined or invalid");
          }
        }
      } catch (error) {
        console.error("[Login] Error checking authentication status:", error);
      }
    };

    checkAuthStatus();
  }, []);

  // Handle back button press to prevent going back to intro screens when logged in
  useEffect(() => {
    const backHandler = BackHandler.addEventListener(
      "hardwareBackPress",
      () => {
        if (user) {
          // Handle redirects without async
          // Start the redirection process but don't await it
          authService
            .getUserData()
            .then((userData) => {
              if (userData && userData.role_id === 2) {
                // If user is a farmer, redirect to farmer dashboard
                router.replace("/(tabs)/farmer-dashboard");
              } else {
                // For regular users/consumers
                router.replace("/(tabs)/main");
              }
            })
            .catch((error) => {
              // Fallback to main screen if there's an error
              console.error("[Login] Error checking user role:", error);
              router.replace("/(tabs)/main");
            });
          return true; // Prevents default back behavior
        }
        return false; // Allow default back behavior for guests
      }
    );

    return () => backHandler.remove();
  }, [user, router]);

  // Validate email format
  const validateEmail = (email: string) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
      setEmailError("Email is required");
      return false;
    } else if (!emailRegex.test(email)) {
      setEmailError("Please enter a valid email address");
      return false;
    }
    setEmailError("");
    return true;
  };

  // Validate password
  const validatePassword = (password: string) => {
    if (!password) {
      setPasswordError("Password is required");
      return false;
    } else if (password.length < 6) {
      setPasswordError("Password should be at least 6 characters");
      return false;
    }
    setPasswordError("");
    return true;
  };

  const handleLogin = async () => {
    // Validate input fields
    const isEmailValid = validateEmail(email);
    const isPasswordValid = validatePassword(password);

    if (!isEmailValid || !isPasswordValid) {
      return;
    }

    try {
      setLoading(true);
      console.log("[Login] Starting login process...");

      // Login with credentials - role will be determined by the server based on user_id
      await authContextLogin({
        email,
        password,
      });
      console.log("[Login] Login successful");

      // Check if the user is a farmer and redirect accordingly
      const userData = await authService.getUserData();

      if (userData && userData.role_id === 2) {
        // If user has farmer role_id, redirect to farmer dashboard
        console.log("[Login] Farmer detected, redirecting to farmer dashboard");
        // Replace simple router.replace with more explicit navigation including refresh
        router.replace({
          pathname: "/farmer/dashboard",
          params: { refresh: "true" },
        });
      } else {
        // For regular users/consumers, redirect to consumer homepage
        console.log(
          "[Login] Consumer detected, redirecting to consumer dashboard"
        );
        router.replace({
          pathname: "/consumer/dashboard",
          params: { refresh: "true" },
        });
      }
    } catch (error: any) {
      console.error("[Login] Error:", error);

      // Show appropriate error message based on error details
      if (error.message?.toLowerCase().includes("password")) {
        // Specific error for password issues
        Alert.alert(
          "Invalid Password",
          "The password you entered is incorrect. Please try again."
        );
      } else if (
        error.message?.toLowerCase().includes("user") ||
        error.message?.toLowerCase().includes("email")
      ) {
        // Error for email not found
        Alert.alert(
          "Account Not Found",
          "No account exists with this email. Please check your email or register."
        );
      } else if (error.message?.includes("credentials")) {
        Alert.alert(
          "Login Failed",
          "Invalid email or password. Please try again."
        );
      } else if (
        error.message?.includes("network") ||
        error.message?.includes("connect")
      ) {
        Alert.alert(
          "Connection Error",
          "Unable to connect to the server. Please check your internet connection."
        );
      } else {
        Alert.alert(
          "Login Error",
          "An error occurred during login. Please try again later."
        );
      }
    } finally {
      setLoading(false);
    }
  };

  const handlePressIn = () => {
    buttonScale.value = withTiming(0.96, { duration: 100 });
  };

  const handlePressOut = () => {
    buttonScale.value = withTiming(1, { duration: 100 });
  };

  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === "ios" ? "padding" : undefined}
    >
      <StatusBar
        barStyle="light-content"
        translucent
        backgroundColor="transparent"
      />
      <LinearGradient colors={[...COLORS.gradient]} style={styles.container}>
        <Animated.View
          entering={FadeInDown.delay(200).duration(800)}
          style={styles.headerContainer}
        >
          <ThemedText style={styles.appName}>FarmersMarket</ThemedText>
        </Animated.View>

        <Animated.View
          entering={SlideInUp.springify().damping(15).delay(300)}
          style={styles.formContainer}
        >
          <ThemedText style={styles.title}>Welcome Back</ThemedText>
          <ThemedText style={styles.subtitle}>Sign in to continue</ThemedText>

          <Animated.View entering={FadeInUp.delay(600).duration(500)}>
            <View style={styles.inputContainer}>
              <View style={styles.iconContainer}>
                <Ionicons name="mail-outline" size={20} color={COLORS.light} />
              </View>
              <TextInput
                style={styles.input}
                placeholder="Email"
                placeholderTextColor={COLORS.muted}
                value={email}
                onChangeText={(text) => {
                  setEmail(text);
                  if (emailError) validateEmail(text);
                }}
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>
            {emailError ? (
              <Text style={styles.errorText}>{emailError}</Text>
            ) : null}
          </Animated.View>

          <Animated.View entering={FadeInUp.delay(700).duration(500)}>
            <View style={styles.inputContainer}>
              <View style={styles.iconContainer}>
                <Ionicons
                  name="lock-closed-outline"
                  size={20}
                  color={COLORS.light}
                />
              </View>
              <TextInput
                style={styles.input}
                placeholder="Password"
                placeholderTextColor={COLORS.muted}
                value={password}
                onChangeText={(text) => {
                  setPassword(text);
                  if (passwordError) validatePassword(text);
                }}
                secureTextEntry={!showPassword}
              />
              <TouchableOpacity
                onPress={() => setShowPassword(!showPassword)}
                style={styles.eyeIconContainer}
              >
                <Ionicons
                  name={showPassword ? "eye-off-outline" : "eye-outline"}
                  size={20}
                  color={COLORS.light}
                />
              </TouchableOpacity>
            </View>
            {passwordError ? (
              <Text style={styles.errorText}>{passwordError}</Text>
            ) : null}
          </Animated.View>

          <Animated.View
            entering={FadeInUp.delay(800).duration(500)}
            style={styles.forgotPassword}
          >
            <TouchableOpacity>
              <ThemedText style={styles.forgotPasswordText}>
                Forgot Password?
              </ThemedText>
            </TouchableOpacity>
          </Animated.View>

          <Animated.View entering={FadeInUp.delay(900).duration(500)}>
            {/* Wrap the button animation in its own container to avoid layout animation conflicts */}
            <Animated.View style={buttonAnimatedStyle}>
              <TouchableOpacity
                style={[styles.loginButton, loading && styles.disabledButton]}
                onPress={handleLogin}
                onPressIn={handlePressIn}
                onPressOut={handlePressOut}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator size="small" color={COLORS.light} />
                ) : (
                  <View style={styles.buttonContent}>
                    <ThemedText style={styles.buttonText}>Login</ThemedText>
                    <Ionicons
                      name="arrow-forward-outline"
                      size={20}
                      color={COLORS.light}
                    />
                  </View>
                )}
              </TouchableOpacity>
            </Animated.View>
          </Animated.View>

          {/* New Register Button */}
          <Animated.View
            entering={FadeInUp.delay(950).duration(500)}
            style={styles.registerButtonContainer}
          >
            <TouchableOpacity
              style={styles.registerButton}
              onPress={() => router.push("/(auth)/register")}
            >
              <View style={styles.buttonContent}>
                <Ionicons
                  name="person-add-outline"
                  size={18}
                  color={COLORS.light}
                />
                <ThemedText style={styles.registerButtonText}>
                  Create New Account
                </ThemedText>
              </View>
            </TouchableOpacity>
          </Animated.View>

          <View style={styles.footerContainer}>
            <TouchableOpacity onPress={() => router.push("/(auth)/register")}>
              <ThemedText style={styles.signupText}>
                Don't have an account?{" "}
                <Text style={styles.signupTextBold}>Sign up</Text>
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              onPress={() => router.push("/(tabs)")}
              style={styles.backButton}
            >
              <Ionicons
                name="arrow-back-outline"
                size={18}
                color={COLORS.light}
              />
              <ThemedText style={styles.backText}>Back to Intro</ThemedText>
            </TouchableOpacity>
          </View>
        </Animated.View>
      </LinearGradient>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  headerContainer: {
    alignItems: "center",
    justifyContent: "center",
    paddingTop: Platform.OS === "ios" ? 70 : 60,
    paddingBottom: SPACING.lg,
    width: "100%", // Ensure the container takes full width
    paddingHorizontal: 20, // Add horizontal padding
  },
  appName: {
    fontSize: 25, // Slightly reduce font size to prevent truncation
    fontWeight: "800",
    color: COLORS.light,
    textShadowColor: "rgba(0, 0, 0, 0.6)",
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 4, // Reduce shadow radius
    includeFontPadding: false, // Prevent Android text cutoff
    textAlign: "center", // Ensure text is centered
  },
  formContainer: {
    flex: 1,
    justifyContent: "flex-start",
    paddingHorizontal: SPACING.horizontalPadding,
    paddingTop: SPACING.lg,
    borderTopLeftRadius: BORDER_RADIUS.xl,
    borderTopRightRadius: BORDER_RADIUS.xl,
    backgroundColor: "rgba(255, 255, 255, 0.07)",
    ...SHADOWS.medium,
  },
  title: {
    fontSize: 20, // Reduced from 32 to ensure no truncation
    fontWeight: "800",
    color: COLORS.light,
    marginBottom: SPACING.sm,
    textAlign: "left",
    textShadowColor: "rgba(0, 0, 0, 0.5)",
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
    includeFontPadding: false, // Prevent Android text cutoff
  },
  subtitle: {
    fontSize: 16, // Reduced from 18 to ensure visibility
    color: COLORS.light,
    marginBottom: SPACING.xl,
    textAlign: "left",
    opacity: 0.9,
    includeFontPadding: false, // Prevent Android text cutoff
  },
  inputContainer: {
    ...UI_STYLES.inputContainer,
    marginBottom: SPACING.xs,
    backgroundColor: "rgba(255,255,255,0.08)",
    borderRadius: BORDER_RADIUS.md,
    height: 56,
  },
  iconContainer: {
    paddingHorizontal: SPACING.md,
    justifyContent: "center",
    alignItems: "center",
  },
  input: {
    flex: 1,
    color: COLORS.light,
    fontSize: 16,
    paddingVertical: SPACING.md,
  },
  eyeIconContainer: {
    paddingHorizontal: SPACING.md,
    height: "100%",
    justifyContent: "center",
  },
  errorText: {
    color: EXTENDED_COLORS.error,
    fontSize: 12,
    marginLeft: SPACING.md,
    marginBottom: SPACING.md,
  },
  forgotPassword: {
    alignSelf: "flex-end",
    marginVertical: SPACING.md,
  },
  forgotPasswordText: {
    color: COLORS.accent,
    fontSize: 14,
  },
  loginButton: {
    ...UI_STYLES.button,
    backgroundColor: COLORS.accent,
    height: 56,
    borderRadius: BORDER_RADIUS.md,
    ...SHADOWS.medium,
    shadowColor: COLORS.accent,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 8,
  },
  buttonContent: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
  },
  buttonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 16,
    fontWeight: "600",
    marginRight: SPACING.sm,
  },
  disabledButton: {
    opacity: 0.7,
  },
  registerButtonContainer: {
    marginTop: SPACING.md,
  },
  registerButton: {
    ...UI_STYLES.button,
    backgroundColor: COLORS.primary,
    height: 56,
    borderRadius: BORDER_RADIUS.md,
    ...SHADOWS.medium,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 8,
  },
  registerButtonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 16,
    fontWeight: "600",
    marginLeft: SPACING.sm,
  },
  footerContainer: {
    marginTop: "auto",
    paddingVertical: SPACING.xl,
  },
  signupText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 15,
  },
  signupTextBold: {
    fontWeight: "700",
    color: COLORS.accent,
  },
  backButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    marginTop: SPACING.lg,
  },
  backText: {
    color: COLORS.light,
    marginLeft: SPACING.xs,
    fontSize: 14,
  },
});
