import { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  Alert,
  Platform,
} from "react-native";
import { useRouter } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { authService } from "@/services/authService";
import { getApiBaseUrlSync } from "@/services/apiConfig";

// Create a simple API service that doesn't depend on external imports
interface ApiOptions extends RequestInit {
  headers?: Record<string, string>;
}

const createApiService = () => ({
  fetch: async (endpoint: string, options: ApiOptions = {}) => {
    try {
      const baseUrl = getApiBaseUrlSync(); // Ensure this always fetches the latest URL
      const url = `${baseUrl}${endpoint}`;

      console.log("[API] Making request to:", url);

      const response = await fetch(url, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          ...(options.headers || {}),
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("[API] Fetch error:", error);
      throw error;
    }
  },
});

const apiService = createApiService();

export default function LoginScreen() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  useEffect(() => {
    const checkAuthStatus = async () => {
      try {
        const isAuthenticated = await authService.isAuthenticated(); // Check if the user is logged in
        if (isAuthenticated) {
          router.replace("/(tabs)/main"); // Redirect to the main screen
        }
      } catch (error) {
        console.error("[Login] Error checking authentication status:", error);
      }
    };

    checkAuthStatus();
  }, []);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert("Error", "Please enter both email and password");
      return;
    }

    try {
      setLoading(true);
      console.log("[Login] Starting login process...");

      const response = await apiService.fetch("/login.php", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });

      console.log("[Login] Response:", response);

      if (response && response.status === "success") {
        try {
          const token = response.token || "dummy_token";
          const userData = response.user || { email };

          console.log("[Login] Storing auth data:", { token, userData });

          const success = await authService.login(token, userData);

          if (success) {
            Alert.alert("Success", "Login successful!", [
              {
                text: "OK",
                onPress: () => router.replace("/(tabs)/main"),
              },
            ]);
          } else {
            throw new Error("Failed to save authentication data");
          }
        } catch (storageError) {
          console.error("[Login] Auth storage error:", storageError);
          Alert.alert(
            "Login Error",
            "Failed to complete login process. Please try again."
          );
        }
      } else {
        Alert.alert(
          "Login Failed",
          response?.message || "Invalid credentials. Please try again."
        );
      }
    } catch (error) {
      console.error("[Login] Error:", error);
      Alert.alert("Error", "Connection error. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient colors={COLORS.gradient} style={styles.container}>
      <View style={styles.formContainer}>
        <ThemedText style={styles.title}>Login</ThemedText>

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
          <Ionicons name="lock-closed-outline" size={24} color={COLORS.light} />
          <TextInput
            style={styles.input}
            placeholder="Password"
            placeholderTextColor={COLORS.muted}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
        </View>

        <TouchableOpacity
          style={[styles.loginButton, loading && styles.disabledButton]}
          onPress={handleLogin}
          disabled={loading}
        >
          <ThemedText style={styles.buttonText}>
            {loading ? "Logging in..." : "Login"}
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity onPress={() => router.push("/(auth)/register")}>
          <ThemedText style={styles.signupText}>
            Don't have an account? Sign up
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity onPress={() => router.back()}>
          <ThemedText style={styles.backText}>Go Back</ThemedText>
        </TouchableOpacity>
      </View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
  },
  formContainer: {
    flex: 1,
    justifyContent: "center",
    padding: 20,
  },
  title: {
    fontSize: 32,
    fontWeight: "bold",
    color: COLORS.light,
    marginBottom: 40,
    textAlign: "center",
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
    fontSize: 16,
  },
  loginButton: {
    backgroundColor: COLORS.accent,
    padding: 15,
    borderRadius: 10,
    marginTop: 20,
  },
  buttonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 18,
    fontWeight: "bold",
  },
  backText: {
    color: COLORS.light,
    textAlign: "center",
    marginTop: 20,
  },
  disabledButton: {
    opacity: 0.7,
  },
  signupText: {
    color: COLORS.light,
    textAlign: "center",
    marginTop: 20,
  },
});
