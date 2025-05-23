import { useState } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  Alert,
  Text,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
  StatusBar,
} from "react-native";
import { useRouter, useLocalSearchParams } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
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
import { API_URL, API_URLS } from "@/constants/Config";

export default function ResetPasswordScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const token = params.token as string;

  const [loading, setLoading] = useState(false);
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [passwordError, setPasswordError] = useState("");
  const [confirmPasswordError, setConfirmPasswordError] = useState("");

  // Animation values
  const buttonScale = useSharedValue(1);
  const buttonAnimatedStyle = useAnimatedStyle(() => {
    return {
      transform: [{ scale: buttonScale.value }],
    };
  });

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

  // Validate confirm password
  const validateConfirmPassword = (confirmPassword: string) => {
    if (!confirmPassword) {
      setConfirmPasswordError("Please confirm your password");
      return false;
    } else if (confirmPassword !== password) {
      setConfirmPasswordError("Passwords do not match");
      return false;
    }
    setConfirmPasswordError("");
    return true;
  };

  const handleResetPassword = async () => {
    // Validate input fields
    const isPasswordValid = validatePassword(password);
    const isConfirmPasswordValid = validateConfirmPassword(confirmPassword);

    if (!isPasswordValid || !isConfirmPasswordValid) {
      return;
    }

    if (!token) {
      Alert.alert("Error", "Invalid password reset token");
      return;
    }

    try {
      setLoading(true);
      console.log("[ResetPassword] Starting password reset with token:", token);
      console.log("[ResetPassword] Using URL:", API_URLS.RESET_PASSWORD);

      // Call reset password API endpoint with the correct URL
      const response = await fetch(API_URLS.RESET_PASSWORD, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ token, password }),
      }); // First check if response is actually received
      if (!response) {
        throw new Error("No response received from server");
      }

      // Log response information for debugging
      console.log("[ResetPassword] Response status:", response.status);
      console.log(
        "[ResetPassword] Response headers:",
        JSON.stringify([...response.headers.entries()])
      );

      // Check if response has content
      const responseText = await response.text();
      console.log("[ResetPassword] Raw response:", responseText);

      let data;
      try {
        // Parse the response if it's not empty
        data = responseText ? JSON.parse(responseText) : null;
      } catch (jsonError) {
        console.error("[ResetPassword] JSON Parse Error:", jsonError);
        throw new Error(
          "Invalid response format from server. Please try again later."
        );
      }

      if (!response.ok) {
        console.error("[ResetPassword] API Error:", data);
        throw new Error(data?.message || "Password reset failed");
      }

      console.log("[ResetPassword] Password reset successful");

      // Show success message and redirect to login
      Alert.alert(
        "Success",
        "Your password has been reset successfully!",
        [
          {
            text: "Login Now",
            onPress: () => {
              // Force immediate navigation to login screen
              router.replace("/(auth)/login");
            },
          },
        ],
        { cancelable: false } // Prevent dismissing the alert by tapping outside
      );

      // Also set a timeout to navigate automatically if user doesn't tap the button
      setTimeout(() => {
        router.replace("/(auth)/login");
      }, 3000); // 3 second timeout as fallback
    } catch (error: any) {
      console.error("[ResetPassword] Error:", error);

      // Check if it's a network/connection error
      if (
        error.message?.includes("network") ||
        error.message?.includes("connect")
      ) {
        Alert.alert(
          "Connection Error",
          "Unable to connect to the server. Please check your internet connection."
        );
      } else {
        Alert.alert(
          "Error",
          error.message || "An error occurred while resetting your password."
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
          <ThemedText style={styles.title}>Reset Password</ThemedText>
          <ThemedText style={styles.subtitle}>
            Enter your new password below
          </ThemedText>

          <Animated.View entering={FadeInUp.delay(600).duration(500)}>
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
                placeholder="New Password"
                placeholderTextColor={COLORS.muted}
                value={password}
                onChangeText={(text) => {
                  setPassword(text);
                  if (passwordError) validatePassword(text);
                  if (confirmPasswordError && confirmPassword)
                    validateConfirmPassword(confirmPassword);
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

          <Animated.View entering={FadeInUp.delay(700).duration(500)}>
            <View style={styles.inputContainer}>
              <View style={styles.iconContainer}>
                <Ionicons
                  name="checkmark-circle-outline"
                  size={20}
                  color={COLORS.light}
                />
              </View>
              <TextInput
                style={styles.input}
                placeholder="Confirm New Password"
                placeholderTextColor={COLORS.muted}
                value={confirmPassword}
                onChangeText={(text) => {
                  setConfirmPassword(text);
                  if (confirmPasswordError) validateConfirmPassword(text);
                }}
                secureTextEntry={!showPassword}
              />
            </View>
            {confirmPasswordError ? (
              <Text style={styles.errorText}>{confirmPasswordError}</Text>
            ) : null}
          </Animated.View>

          <Animated.View
            entering={FadeInUp.delay(800).duration(500)}
            style={{ marginTop: 20 }}
          >
            <Animated.View style={buttonAnimatedStyle}>
              <TouchableOpacity
                style={[styles.resetButton, loading && styles.disabledButton]}
                onPress={handleResetPassword}
                onPressIn={handlePressIn}
                onPressOut={handlePressOut}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator size="small" color={COLORS.light} />
                ) : (
                  <View style={styles.buttonContent}>
                    <ThemedText style={styles.buttonText}>
                      Reset Password
                    </ThemedText>
                    <Ionicons
                      name="checkmark-outline"
                      size={20}
                      color={COLORS.light}
                    />
                  </View>
                )}
              </TouchableOpacity>
            </Animated.View>
          </Animated.View>

          <View style={styles.footerContainer}>
            <TouchableOpacity onPress={() => router.replace("/(auth)/login")}>
              <View style={styles.backButton}>
                <Ionicons
                  name="arrow-back-outline"
                  size={18}
                  color={COLORS.light}
                />
                <ThemedText style={styles.backText}>Back to Login</ThemedText>
              </View>
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
    width: "100%",
    paddingHorizontal: 20,
  },
  appName: {
    fontSize: 25,
    fontWeight: "800",
    color: COLORS.light,
    textShadowColor: "rgba(0, 0, 0, 0.6)",
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 4,
    includeFontPadding: false,
    textAlign: "center",
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
    fontSize: 20,
    fontWeight: "800",
    color: COLORS.light,
    marginBottom: SPACING.sm,
    textAlign: "left",
    textShadowColor: "rgba(0, 0, 0, 0.5)",
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
    includeFontPadding: false,
  },
  subtitle: {
    fontSize: 16,
    color: COLORS.light,
    marginBottom: SPACING.xl,
    textAlign: "left",
    opacity: 0.9,
    includeFontPadding: false,
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
  resetButton: {
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
  footerContainer: {
    marginTop: "auto",
    paddingVertical: SPACING.xl,
    alignItems: "center",
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
