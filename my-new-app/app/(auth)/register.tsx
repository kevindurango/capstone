import { useState } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  ScrollView,
  Alert,
} from "react-native";
import { useRouter } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { getApiBaseUrlSync } from "@/services/apiConfig";

type ApiOptions = {
  headers?: Record<string, string>;
  method?: string;
  body?: string;
  [key: string]: any;
};

const createApiService = () => ({
  fetch: async (endpoint: string, options: ApiOptions = {}) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      const url = `${baseUrl}${endpoint}`;

      console.log("[Register API] Making request to:", url);

      const response = await fetch(url, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          ...(options.headers || {}),
        },
      });

      const responseData = await response.json();

      if (!response.ok) {
        console.error("[Register API] Response error:", responseData);
        throw {
          status: response.status,
          message: responseData.message || "An error occurred",
          data: responseData,
        };
      }

      return responseData;
    } catch (error) {
      console.error("[Register API] Fetch error:", error);
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

  const handleRegister = async () => {
    try {
      if (
        !fullName ||
        !email ||
        !password ||
        !confirmPassword ||
        !contactNumber ||
        !address
      ) {
        alert("All fields are required");
        return;
      }

      if (password !== confirmPassword) {
        alert("Passwords don't match!");
        return;
      }

      setIsLoading(true);

      const payload = {
        fullName,
        email,
        password,
        userType,
        contact_number: contactNumber,
        address: address,
      };

      console.log("[Register] Sending registration request...");

      const response = await apiService.fetch("/register.php", {
        method: "POST",
        body: JSON.stringify(payload),
      });

      console.log("[Register] Response:", response);

      if (response && response.status === "success") {
        Alert.alert("Success", "Registration successful!", [
          {
            text: "OK",
            onPress: () => router.replace("/(auth)/login"),
          },
        ]);
      } else {
        const errorMessage =
          response?.message || "Registration failed. Please try again.";
        console.error("[Register] Error:", errorMessage);
        Alert.alert("Registration Failed", errorMessage);
      }
    } catch (error) {
      console.error("[Register] Error:", error);
      Alert.alert(
        "Error",
        "Connection error. Please check your internet connection and try again."
      );
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <LinearGradient colors={COLORS.gradient} style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.formContainer}>
          <ThemedText style={styles.title}>Create Account</ThemedText>

          <View style={styles.inputContainer}>
            <Ionicons name="person-outline" size={24} color={COLORS.light} />
            <TextInput
              style={styles.input}
              placeholder="Full Name"
              placeholderTextColor={COLORS.muted}
              value={fullName}
              onChangeText={setFullName}
              autoCapitalize="words"
            />
          </View>

          <View style={styles.inputContainer}>
            <Ionicons name="mail-outline" size={24} color={COLORS.light} />
            <TextInput
              style={styles.input}
              placeholder="Email"
              placeholderTextColor={COLORS.muted}
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
            />
          </View>

          <View style={styles.inputContainer}>
            <Ionicons name="call-outline" size={24} color={COLORS.light} />
            <TextInput
              style={styles.input}
              placeholder="Contact Number"
              placeholderTextColor={COLORS.muted}
              value={contactNumber}
              onChangeText={setContactNumber}
              keyboardType="phone-pad"
            />
          </View>

          <View style={styles.inputContainer}>
            <Ionicons name="location-outline" size={24} color={COLORS.light} />
            <TextInput
              style={styles.input}
              placeholder="Address"
              placeholderTextColor={COLORS.muted}
              value={address}
              onChangeText={setAddress}
            />
          </View>

          <View style={styles.inputContainer}>
            <Ionicons
              name="lock-closed-outline"
              size={24}
              color={COLORS.light}
            />
            <TextInput
              style={styles.input}
              placeholder="Password"
              placeholderTextColor={COLORS.muted}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
            />
          </View>

          <View style={styles.inputContainer}>
            <Ionicons
              name="lock-closed-outline"
              size={24}
              color={COLORS.light}
            />
            <TextInput
              style={styles.input}
              placeholder="Confirm Password"
              placeholderTextColor={COLORS.muted}
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              secureTextEntry
            />
          </View>

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
                size={24}
                color={userType === "consumer" ? COLORS.dark : COLORS.light}
              />
              <ThemedText
                style={[
                  styles.userTypeText,
                  userType === "consumer" && styles.userTypeTextActive,
                ]}
              >
                Consumer
              </ThemedText>
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
                size={24}
                color={userType === "farmer" ? COLORS.dark : COLORS.light}
              />
              <ThemedText
                style={[
                  styles.userTypeText,
                  userType === "farmer" && styles.userTypeTextActive,
                ]}
              >
                Farmer
              </ThemedText>
            </TouchableOpacity>
          </View>

          <TouchableOpacity
            style={[styles.registerButton, isLoading && styles.disabledButton]}
            onPress={handleRegister}
            disabled={isLoading}
          >
            <ThemedText style={styles.buttonText}>
              {isLoading ? "Creating Account..." : "Create Account"}
            </ThemedText>
          </TouchableOpacity>

          <View style={styles.bottomContainer}>
            <TouchableOpacity onPress={() => router.back()}>
              <ThemedText style={styles.backText}>Go Back</ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.settingsButton}
              onPress={() => router.push("/settings")}
            >
              <Ionicons
                name="settings-outline"
                size={20}
                color={COLORS.light}
              />
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingTop: 20, // Reduced from 40
    paddingBottom: 40,
    paddingHorizontal: 20,
  },
  formContainer: {
    flex: 1,
    justifyContent: "center",
  },
  title: {
    fontSize: 25, // Modify this to adjust the header text size
    fontWeight: "bold",
    color: COLORS.light,
    textAlign: "center",
    marginBottom: 40,
    marginTop: 20,
  },
  inputContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(255,255,255,0.1)",
    borderRadius: 10,
    marginBottom: 15,
    paddingHorizontal: 15,
  },
  input: {
    flex: 1,
    paddingVertical: 15,
    paddingHorizontal: 10,
    color: COLORS.light,
    fontSize: 12, // Change this to adjust input text size
  },
  userTypeContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginVertical: 20,
    gap: 10,
  },
  userTypeButton: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    padding: 15,
    backgroundColor: "rgba(255,255,255,0.1)",
    borderRadius: 10,
    gap: 10,
  },
  userTypeButtonActive: {
    backgroundColor: COLORS.secondary,
  },
  userTypeText: {
    color: COLORS.light,
    fontSize: 14, // Modify this to adjust the user type button text size
    fontWeight: "600",
  },
  userTypeTextActive: {
    color: COLORS.dark,
  },
  registerButton: {
    backgroundColor: COLORS.accent,
    padding: 15,
    borderRadius: 10,
    marginTop: 20,
  },
  buttonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 13, // Adjust the button text size here
    fontWeight: "bold",
  },
  backText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 16, // Add this line to adjust the "Go Back" text size
  },
  bottomContainer: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    marginTop: 20,
    position: "relative",
    width: "100%",
  },
  settingsButton: {
    position: "absolute",
    right: 0,
    padding: 10,
    backgroundColor: "rgba(255,255,255,0.1)",
    borderRadius: 20,
  },
  disabledButton: {
    opacity: 0.7,
  },
});
