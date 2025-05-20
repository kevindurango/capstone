import { useState } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  ScrollView,
  Alert,
  Image,
  Platform,
  ActivityIndicator,
  KeyboardAvoidingView,
  StatusBar,
  Text,
} from "react-native";
import { useRouter } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { getApiBaseUrlSync } from "@/services/apiConfig";
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

type ApiOptions = {
  headers?: Record<string, string>;
  method?: string;
  body?: string;
  [key: string]: any;
};

// API service
const createApiService = () => ({
  fetch: async (endpoint: string, options: ApiOptions = {}) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      const url = `${baseUrl}${endpoint}`;

      console.log("[API] Making request to:", url);
      console.log("[API] Request payload:", options.body);

      const response = await fetch(url, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          ...(options.headers || {}),
        },
      });

      const textResponse = await response.text();
      console.log("[API] Raw response:", textResponse);

      // If response is empty, throw error
      if (!textResponse) {
        throw new Error("Empty response from server");
      }

      let jsonResponse;
      try {
        jsonResponse = JSON.parse(textResponse);
      } catch (parseError) {
        console.error("[API] JSON parse error:", parseError);
        throw new Error(`Server returned invalid JSON: ${textResponse}`);
      }

      if (!response.ok) {
        throw {
          status: response.status,
          message: jsonResponse?.message || "An error occurred",
          data: jsonResponse,
        };
      }

      return jsonResponse;
    } catch (error) {
      console.error("[API] Fetch error:", error);
      throw error;
    }
  },
});

const apiService = createApiService();

export default function RegisterScreen() {
  const router = useRouter();
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [contactNumber, setContactNumber] = useState("");
  const [address, setAddress] = useState("");
  const [userType, setUserType] = useState("consumer");
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  // Form validation states
  const [fullNameError, setFullNameError] = useState("");
  const [emailError, setEmailError] = useState("");
  const [passwordError, setPasswordError] = useState("");
  const [confirmPasswordError, setConfirmPasswordError] = useState("");
  const [contactNumberError, setContactNumberError] = useState("");
  const [addressError, setAddressError] = useState("");

  // Animation for button
  const buttonScale = useSharedValue(1);
  const buttonAnimatedStyle = useAnimatedStyle(() => {
    return {
      transform: [{ scale: buttonScale.value }],
    };
  });

  const handlePressIn = () => {
    buttonScale.value = withTiming(0.96, { duration: 100 });
  };

  const handlePressOut = () => {
    buttonScale.value = withTiming(1, { duration: 100 });
  };

  // Form validation functions
  const validateFullName = (name: string) => {
    if (!name.trim()) {
      setFullNameError("Full name is required");
      return false;
    }
    setFullNameError("");
    return true;
  };

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

  const validateConfirmPassword = (confirmPwd: string) => {
    if (!confirmPwd) {
      setConfirmPasswordError("Please confirm your password");
      return false;
    } else if (confirmPwd !== password) {
      setConfirmPasswordError("Passwords don't match");
      return false;
    }
    setConfirmPasswordError("");
    return true;
  };

  const validateContactNumber = (number: string) => {
    if (!number.trim()) {
      setContactNumberError("Contact number is required");
      return false;
    }
    setContactNumberError("");
    return true;
  };

  const validateAddress = (address: string) => {
    if (!address.trim()) {
      setAddressError("Address is required");
      return false;
    }
    setAddressError("");
    return true;
  };

  const handleRegister = async () => {
    // Validate all fields
    const isNameValid = validateFullName(fullName);
    const isEmailValid = validateEmail(email);
    const isPasswordValid = validatePassword(password);
    const isConfirmPasswordValid = validateConfirmPassword(confirmPassword);
    const isContactValid = validateContactNumber(contactNumber);
    const isAddressValid = validateAddress(address);

    if (
      !isNameValid ||
      !isEmailValid ||
      !isPasswordValid ||
      !isConfirmPasswordValid ||
      !isContactValid ||
      !isAddressValid
    ) {
      return;
    }

    try {
      setIsLoading(true);
      console.log("[Register] Starting registration...");

      const payload = {
        fullName,
        email,
        password,
        userType,
        contact_number: contactNumber,
        address: address,
      };

      console.log("[Register] Payload:", payload);

      const response = await apiService.fetch("/register.php", {
        method: "POST",
        body: JSON.stringify(payload),
      });

      console.log("[Register] Server response:", response);

      if (response?.status === "success") {
        Alert.alert("Success", "Registration successful!", [
          {
            text: "OK",
            onPress: () => router.replace("/(auth)/login"),
          },
        ]);
      } else {
        throw new Error(response?.message || "Registration failed");
      }
    } catch (error: any) {
      console.error("[Register] Error:", error);
      Alert.alert(
        "Registration Failed",
        error.message || "Unable to complete registration. Please try again."
      );
    } finally {
      setIsLoading(false);
    }
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
      <LinearGradient
        colors={Array.from(COLORS.gradient)}
        style={styles.container}
      >
        <Animated.View
          entering={FadeInDown.delay(200).duration(800)}
          style={styles.logoContainer}
        >
          <ThemedText style={styles.appName}>FarmersMarket</ThemedText>
        </Animated.View>

        <Animated.View
          entering={SlideInUp.springify().damping(15).delay(300)}
          style={styles.formContainer}
        >
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            showsVerticalScrollIndicator={false}
          >
            <ThemedText style={styles.title}>Create Account</ThemedText>
            <ThemedText style={styles.subtitle}>
              Fill in your details to get started
            </ThemedText>

            <View style={styles.userTypeContainer}>
              <TouchableOpacity
                style={[
                  styles.userTypeButton,
                  userType === "consumer" && styles.userTypeButtonActive,
                ]}
                onPress={() => setUserType("consumer")}
              >
                <Ionicons
                  name="cart-outline"
                  size={22}
                  color={userType === "consumer" ? COLORS.dark : COLORS.light}
                />
                <Text
                  style={[
                    styles.userTypeText,
                    userType === "consumer" && styles.userTypeTextActive,
                  ]}
                >
                  Consumer
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.userTypeButton,
                  userType === "farmer" && styles.userTypeButtonActive,
                ]}
                onPress={() => setUserType("farmer")}
              >
                <Ionicons
                  name="leaf-outline"
                  size={22}
                  color={userType === "farmer" ? COLORS.dark : COLORS.light}
                />
                <Text
                  style={[
                    styles.userTypeText,
                    userType === "farmer" && styles.userTypeTextActive,
                  ]}
                >
                  Farmer
                </Text>
              </TouchableOpacity>
            </View>

            {/* Full Name */}
            <Animated.View entering={FadeInUp.delay(400).duration(400)}>
              <View style={styles.inputContainer}>
                <View style={styles.iconContainer}>
                  <Ionicons
                    name="person-outline"
                    size={20}
                    color={COLORS.light}
                  />
                </View>
                <TextInput
                  style={styles.input}
                  placeholder="Full Name"
                  placeholderTextColor={COLORS.muted}
                  value={fullName}
                  onChangeText={(text) => {
                    setFullName(text);
                    if (fullNameError) validateFullName(text);
                  }}
                  autoCapitalize="words"
                />
              </View>
              {fullNameError ? (
                <Text style={styles.errorText}>{fullNameError}</Text>
              ) : null}
            </Animated.View>

            {/* Email */}
            <Animated.View entering={FadeInUp.delay(450).duration(400)}>
              <View style={styles.inputContainer}>
                <View style={styles.iconContainer}>
                  <Ionicons
                    name="mail-outline"
                    size={20}
                    color={COLORS.light}
                  />
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

            {/* Contact Number */}
            <Animated.View entering={FadeInUp.delay(500).duration(400)}>
              <View style={styles.inputContainer}>
                <View style={styles.iconContainer}>
                  <Ionicons
                    name="call-outline"
                    size={20}
                    color={COLORS.light}
                  />
                </View>
                <TextInput
                  style={styles.input}
                  placeholder="Contact Number"
                  placeholderTextColor={COLORS.muted}
                  value={contactNumber}
                  onChangeText={(text) => {
                    setContactNumber(text);
                    if (contactNumberError) validateContactNumber(text);
                  }}
                  keyboardType="phone-pad"
                />
              </View>
              {contactNumberError ? (
                <Text style={styles.errorText}>{contactNumberError}</Text>
              ) : null}
            </Animated.View>

            {/* Address */}
            <Animated.View entering={FadeInUp.delay(550).duration(400)}>
              <View style={styles.inputContainer}>
                <View style={styles.iconContainer}>
                  <Ionicons
                    name="location-outline"
                    size={20}
                    color={COLORS.light}
                  />
                </View>
                <TextInput
                  style={styles.input}
                  placeholder="Address"
                  placeholderTextColor={COLORS.muted}
                  value={address}
                  onChangeText={(text) => {
                    setAddress(text);
                    if (addressError) validateAddress(text);
                  }}
                />
              </View>
              {addressError ? (
                <Text style={styles.errorText}>{addressError}</Text>
              ) : null}
            </Animated.View>

            {/* Password */}
            <Animated.View entering={FadeInUp.delay(600).duration(400)}>
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
                    if (confirmPassword && confirmPasswordError) {
                      validateConfirmPassword(confirmPassword);
                    }
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

            {/* Confirm Password */}
            <Animated.View entering={FadeInUp.delay(650).duration(400)}>
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
                  placeholder="Confirm Password"
                  placeholderTextColor={COLORS.muted}
                  value={confirmPassword}
                  onChangeText={(text) => {
                    setConfirmPassword(text);
                    if (confirmPasswordError) validateConfirmPassword(text);
                  }}
                  secureTextEntry={!showConfirmPassword}
                />
                <TouchableOpacity
                  onPress={() => setShowConfirmPassword(!showConfirmPassword)}
                  style={styles.eyeIconContainer}
                >
                  <Ionicons
                    name={
                      showConfirmPassword ? "eye-off-outline" : "eye-outline"
                    }
                    size={20}
                    color={COLORS.light}
                  />
                </TouchableOpacity>
              </View>
              {confirmPasswordError ? (
                <Text style={styles.errorText}>{confirmPasswordError}</Text>
              ) : null}
            </Animated.View>

            <Animated.View
              entering={FadeInUp.delay(700).duration(400)}
              style={[buttonAnimatedStyle, { marginTop: SPACING.xl }]}
            >
              <TouchableOpacity
                style={[
                  styles.registerButton,
                  isLoading && styles.disabledButton,
                  userType === "farmer" && styles.farmerButton,
                ]}
                onPress={handleRegister}
                onPressIn={handlePressIn}
                onPressOut={handlePressOut}
                disabled={isLoading}
              >
                {isLoading ? (
                  <ActivityIndicator size="small" color={COLORS.light} />
                ) : (
                  <View style={styles.buttonContent}>
                    <ThemedText style={styles.buttonText}>
                      Create Account
                    </ThemedText>
                    <Ionicons
                      name="arrow-forward-outline"
                      size={20}
                      color={COLORS.light}
                    />
                  </View>
                )}
              </TouchableOpacity>
            </Animated.View>

            <Animated.View
              entering={FadeInUp.delay(800).duration(400)}
              style={styles.bottomContainer}
            >
              <TouchableOpacity onPress={() => router.push("/(auth)/login")}>
                <ThemedText style={styles.loginText}>
                  Already have an account?{" "}
                  <Text style={styles.loginTextBold}>Login</Text>
                </ThemedText>
              </TouchableOpacity>

              <TouchableOpacity
                onPress={() => router.back()}
                style={styles.backButton}
              >
                <Ionicons
                  name="arrow-back-outline"
                  size={18}
                  color={COLORS.light}
                />
                <ThemedText style={styles.backText}>Back</ThemedText>
              </TouchableOpacity>
            </Animated.View>
          </ScrollView>
        </Animated.View>
      </LinearGradient>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  logoContainer: {
    alignItems: "center",
    justifyContent: "center",
    paddingTop: Platform.OS === "ios" ? 60 : 40,
    paddingBottom: SPACING.sm,
  },
  logo: {
    width: 70,
    height: 70,
  },
  appName: {
    marginTop: SPACING.xs,
    fontSize: 20,
    fontWeight: "600",
    color: COLORS.light,
  },
  formContainer: {
    flex: 1,
    borderTopLeftRadius: BORDER_RADIUS.xl,
    borderTopRightRadius: BORDER_RADIUS.xl,
    backgroundColor: "rgba(255, 255, 255, 0.07)",
    ...SHADOWS.medium,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: SPACING.horizontalPadding,
    paddingTop: SPACING.lg,
    paddingBottom: SPACING.xxl,
  },
  title: {
    fontSize: 20,
    fontWeight: "bold",
    color: COLORS.light,
    marginBottom: SPACING.xs,
    textAlign: "left",
  },
  subtitle: {
    fontSize: 16,
    color: COLORS.light + EXTENDED_COLORS.alpha[60],
    marginBottom: SPACING.xl,
    textAlign: "left",
  },
  userTypeContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: SPACING.lg,
    borderRadius: BORDER_RADIUS.md,
    overflow: "hidden",
    backgroundColor: "rgba(255,255,255,0.07)",
    padding: SPACING.xs,
  },
  userTypeButton: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: SPACING.md - 4,
    borderRadius: BORDER_RADIUS.sm,
    gap: SPACING.sm,
  },
  userTypeButtonActive: {
    backgroundColor: COLORS.secondary,
  },
  userTypeText: {
    color: COLORS.light,
    fontWeight: "600",
    fontSize: 14,
  },
  userTypeTextActive: {
    color: COLORS.dark,
    fontWeight: "700",
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
  registerButton: {
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
  farmerButton: {
    backgroundColor: COLORS.secondary,
    shadowColor: COLORS.secondary,
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
  bottomContainer: {
    marginTop: SPACING.xl,
    alignItems: "center",
  },
  loginText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 15,
  },
  loginTextBold: {
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
