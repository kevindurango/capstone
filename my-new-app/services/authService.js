import AsyncStorage from "@react-native-async-storage/async-storage";

// Key for storing auth data
const AUTH_TOKEN_KEY = "auth_token";
const USER_DATA_KEY = "user_data";

export const authService = {
  // Improved authentication checking with caching for better performance
  isAuthenticated: async () => {
    try {
      const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
      console.log("[Auth] Token check result:", token ? "found" : "not found");

      // Explicitly return a boolean based on token existence
      return token ? true : false;
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
      console.log("[Auth] Clearing auth data");
      await AsyncStorage.removeItem(AUTH_TOKEN_KEY);
      await AsyncStorage.removeItem(USER_DATA_KEY);
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
};
