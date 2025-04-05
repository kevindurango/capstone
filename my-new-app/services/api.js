import { Platform } from "react-native";
import { getApiBaseUrl, getApiBaseUrlSync } from "./apiConfig";

// Safe import of Alert
let Alert;
try {
  Alert = require("react-native").Alert;
} catch (error) {
  // Fallback Alert implementation if import fails
  Alert = {
    alert: (title, message) => {
      console.log(`[Alert Fallback] ${title}: ${message}`);
    },
  };
}

// Import NetInfo with fallback
let NetInfo;
try {
  NetInfo = require("@react-native-community/netinfo").default;
} catch (e) {
  console.log("[API] NetInfo not available, using polyfill");
  try {
    NetInfo = require("./netinfo").default;
  } catch (e2) {
    console.error("[API] NetInfo polyfill also failed:", e2);
    // Create a minimal NetInfo fallback
    NetInfo = {
      fetch: async () => ({ isConnected: true, isInternetReachable: true }),
    };
  }
}

// Legacy getBaseUrl function for backward compatibility
export function getBaseUrl() {
  // Return the sync version for backward compatibility
  return getApiBaseUrlSync();
}

// Network troubleshooting helper
const checkNetworkConnectivity = async () => {
  try {
    // Check if device has network connectivity
    const netInfoState = await NetInfo.fetch();
    console.log("[API] Network connectivity:", netInfoState);

    if (!netInfoState.isConnected) {
      console.log("[API] No network connection available");
      return { isConnected: false, error: "No network connection" };
    }

    // Try pinging the server to check if it's accessible
    const baseUrl = await getApiBaseUrl();
    console.log(`[API] Testing connectivity to ${baseUrl}`);

    // Use AbortController if available
    let controller;
    let timeoutId;

    try {
      controller = new AbortController();
      timeoutId = setTimeout(() => controller.abort(), 5000);
    } catch (e) {
      console.log("[API] AbortController not available");
    }

    try {
      const options = controller ? { signal: controller.signal } : {};
      await fetch(`${baseUrl}/ping.php`, {
        method: "GET",
        ...options,
      });

      if (timeoutId) clearTimeout(timeoutId);

      console.log("[API] Server is reachable");
      return { isConnected: true };
    } catch (error) {
      if (timeoutId) clearTimeout(timeoutId);

      console.log("[API] Server connectivity test failed:", error);
      return {
        isConnected: false,
        error:
          "Cannot reach server. Make sure XAMPP is running and Apache is started.",
      };
    }
  } catch (error) {
    console.error("[API] Network check failed:", error);
    return { isConnected: false, error: error.message };
  }
};

// Define the API service object with error handling
const apiService = {
  // Include getBaseUrl as a method in the service
  getBaseUrl: async () => await getApiBaseUrl(),

  fetch: async (endpoint, options = {}) => {
    try {
      const baseUrl = await getApiBaseUrl();
      const url = `${baseUrl}${endpoint}`;

      console.log("[API] Attempting request to:", url);

      // Check connectivity before making request
      const networkStatus = await checkNetworkConnectivity();
      if (!networkStatus.isConnected) {
        console.error("[API] Network connectivity issue:", networkStatus.error);
        throw new Error(`Network connectivity issue: ${networkStatus.error}`);
      }

      // Set default headers if not provided
      const headers = {
        "Content-Type": "application/json",
        ...(options.headers || {}),
      };

      // Prepare request options
      const requestOptions = {
        ...options,
        headers,
      };

      // Log the payload being sent
      if (options.body) {
        try {
          console.log("[API] Payload being sent:", JSON.parse(options.body));
        } catch (e) {
          console.log("[API] Payload (not JSON):", options.body);
        }
      }

      // Add timeout to prevent hanging requests
      let controller;
      let timeoutId;

      try {
        controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
      } catch (e) {
        console.log("[API] AbortController not available");
      }

      // Send request with timeout if available
      const fetchOptions = {
        ...requestOptions,
        ...(controller ? { signal: controller.signal } : {}),
      };

      const response = await fetch(url, fetchOptions);

      if (timeoutId) clearTimeout(timeoutId);

      // Check for HTTP errors
      if (!response.ok) {
        const errorText = await response.text();
        console.error(
          `[API] HTTP error! Status: ${response.status}, Response: ${errorText}`
        );
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      // Parse the JSON response
      const data = await response.json();
      return data;
    } catch (error) {
      console.error("[API] Detailed fetch error:", error);

      // Provide more detailed error information
      if (error.name === "AbortError") {
        console.log("[API] Request timed out");
        throw new Error("Request timed out. Server might be unresponsive.");
      } else if (error.message.includes("Network request failed")) {
        console.log(
          "[API] Network request failed. Check server configuration:"
        );
        console.log("- Make sure XAMPP is running");
        console.log("- Make sure Apache server is started");
        console.log("- Check if API endpoint exists at the correct path");
        console.log(
          "- For Android emulator, verify you're using 10.0.2.2 instead of localhost"
        );
      }

      throw error;
    }
  },

  testConnection: async () => {
    try {
      const status = await checkNetworkConnectivity();
      if (status.isConnected) {
        Alert.alert("Connection Test", "Successfully connected to server!");
      } else {
        Alert.alert(
          "Connection Failed",
          status.error || "Could not connect to server"
        );
      }
      return status;
    } catch (error) {
      console.error("Test connection error:", error);
      Alert.alert("Connection Test Error", error.message);
      return { isConnected: false, error: error.message };
    }
  },
};

// Export the API service as default
export default apiService;

// Also export it as a named export for backward compatibility
export const api = apiService;

// Explicitly check the module was properly exported (debugging help)
console.log("[API] Module initialization successful:", !!apiService);
