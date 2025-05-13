import AsyncStorage from "@react-native-async-storage/async-storage";
import { api } from "./api";

// Key for storing auth data
const AUTH_TOKEN_KEY = "auth_token";
const USER_DATA_KEY = "user_data";
const LEGACY_USER_KEY = "user_data_legacy";

// Add in-memory cache to reduce excessive AsyncStorage calls
let tokenCache = null;
let tokenLastChecked = 0;
const TOKEN_CACHE_DURATION = 5000; // Cache token for 5 seconds

export const authService = {
  // Improved authentication checking with caching for better performance
  isAuthenticated: async () => {
    try {
      const now = Date.now();

      // Use cached token if available and recently checked
      if (
        tokenCache !== null &&
        now - tokenLastChecked < TOKEN_CACHE_DURATION
      ) {
        return tokenCache;
      }

      const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
      // Only log token checks on initial load or when debugging
      if (!tokenLastChecked) {
        console.log(
          "[Auth] Token check result:",
          token ? "found" : "not found"
        );
      }

      // Update cache
      tokenCache = token ? true : false;
      tokenLastChecked = now;

      return tokenCache;
    } catch (error) {
      console.error("[Auth] Authentication check error:", error);
      return false;
    }
  },

  // Login with improved error handling and validation
  login: async (token, userData) => {
    try {
      if (!token) {
        console.error("[Auth] Token is required for login");
        return false;
      }

      // Store token first
      await AsyncStorage.setItem(AUTH_TOKEN_KEY, token);
      console.log("[Auth] Token stored successfully");

      // Update cache
      tokenCache = true;
      tokenLastChecked = Date.now();

      // Store user data
      if (userData) {
        const userDataString =
          typeof userData === "string" ? userData : JSON.stringify(userData);

        await AsyncStorage.setItem(USER_DATA_KEY, userDataString);
        console.log("[Auth] User data stored successfully");
      }

      // Verify data was stored correctly
      const verifyToken = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
      if (!verifyToken) {
        console.error("[Auth] Token storage verification failed");
        return false;
      }

      return true;
    } catch (error) {
      console.error("[Auth] Login error:", error);
      return false;
    }
  },

  // Logout and clear auth data
  logout: async () => {
    try {
      console.log("[Auth] Starting logout process");

      // Get user data for logging purposes
      const userData = await AsyncStorage.getItem(USER_DATA_KEY);
      let userId = null;
      if (userData) {
        try {
          const parsedData = JSON.parse(userData);
          userId = parsedData.user_id;
        } catch (e) {
          console.error("[Auth] Error parsing user data during logout:", e);
        }
      }

      // Call the logout API endpoint
      try {
        const payload = userId ? { user_id: userId } : {};
        await api.fetch("/logout.php", {
          method: "POST",
          body: JSON.stringify(payload),
        });
        console.log("[Auth] Logout API call successful");
      } catch (apiError) {
        // Continue with local logout even if API call fails
        console.error(
          "[Auth] Logout API call failed, continuing with local logout:",
          apiError
        );
      }

      // Clear local storage regardless of API response
      console.log("[Auth] Clearing auth data from local storage");
      await AsyncStorage.removeItem(AUTH_TOKEN_KEY);
      await AsyncStorage.removeItem(USER_DATA_KEY);
      await AsyncStorage.removeItem(LEGACY_USER_KEY);

      // Clear cache
      tokenCache = null;
      tokenLastChecked = 0;

      return true;
    } catch (error) {
      console.error("[Auth] Error during logout:", error);
      return false;
    }
  },

  // Get user data
  getUserData: async () => {
    try {
      const userDataString = await AsyncStorage.getItem(USER_DATA_KEY);
      console.log("[Auth] Retrieved user data string:", userDataString);

      if (!userDataString) {
        console.warn("[Auth] No user data found in storage");
        return { first_name: "Guest", last_name: "" }; // Return default data
      }

      try {
        const parsedData = JSON.parse(userDataString);
        // Explicitly log the role_id to help with debugging the farmer redirection issue
        console.log("[Auth] User role_id:", parsedData.role_id);
        return parsedData;
      } catch (parseError) {
        console.error("[Auth] Error parsing user data:", parseError);
        return { first_name: "Guest", last_name: "" }; // Return default on parse error
      }
    } catch (error) {
      console.error("[Auth] Error getting user data:", error);
      return { first_name: "Guest", last_name: "" };
    }
  },

  // Update user profile
  updateProfile: async (profileData) => {
    try {
      console.log("[Auth] Updating user profile:", profileData);

      const response = await api.fetch("/update_profile.php", {
        method: "POST",
        body: JSON.stringify(profileData),
      });

      if (response && response.status === "success") {
        // Update local storage with new user data
        await AsyncStorage.setItem(
          USER_DATA_KEY,
          JSON.stringify(response.user)
        );
        console.log("[Auth] Profile updated and stored successfully");

        // If email was updated, update in legacy storage too for compatibility
        await AsyncStorage.setItem(
          LEGACY_USER_KEY,
          JSON.stringify(response.user)
        );

        // If password was changed, log this event
        if (response.password_updated) {
          console.log("[Auth] User password was updated successfully");
        }

        return response;
      } else {
        throw new Error(response?.message || "Unknown error updating profile");
      }
    } catch (error) {
      console.error("[Auth] Profile update error:", error);
      throw error;
    }
  },
};
