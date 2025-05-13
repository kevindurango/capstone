import AsyncStorage from "@react-native-async-storage/async-storage";
import NetInfo from "@react-native-community/netinfo";
import { Platform } from "react-native";
import {
  API_BASE_URL as DEFAULT_API_URL,
  LOCAL_IP_ADDRESS,
} from "@/constants/IPConfig";

// Debug variable to enable verbose API URL logging
const DEBUG_API_URLS = true;

// Storage key for API base URL
const API_URL_STORAGE_KEY = "@farmers_market_api_url";

// Default API base URLs
let API_BASE_URL = ""; // This will be set during initialization

/**
 * Initialize the API URL with platform-aware settings
 */
const initializeApiUrl = async () => {
  try {
    // Check if we have a stored URL first
    const storedUrl = await AsyncStorage.getItem(API_URL_STORAGE_KEY);

    if (storedUrl) {
      API_BASE_URL = storedUrl;
      logDebug("[Config] Using stored API URL:", API_BASE_URL);
      return;
    }

    // No stored URL, determine based on platform
    if (Platform.OS === "web") {
      // Web can use localhost
      API_BASE_URL = "http://localhost/capstone/my-new-app/api";
      logDebug("[Config] Web platform detected, using localhost");
    } else if (Platform.OS === "ios") {
      // iOS simulator can sometimes use localhost, but real devices need IP
      const netInfo = await NetInfo.fetch();
      // Check if running in iOS simulator
      if (
        __DEV__ &&
        Platform.OS === "ios" &&
        !Platform.isPad &&
        !Platform.isTV &&
        Platform.constants.systemName.toLowerCase().includes("simulator")
      ) {
        API_BASE_URL = "http://localhost/capstone/my-new-app/api";
        logDebug("[Config] iOS simulator detected, using localhost");
      } else {
        API_BASE_URL = `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/api`;
        logDebug(
          "[Config] iOS device detected, using IP address:",
          LOCAL_IP_ADDRESS
        );
      }
    } else {
      // Android always needs the real IP address
      API_BASE_URL = `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/api`;
      logDebug(
        "[Config] Android detected, using IP address:",
        LOCAL_IP_ADDRESS
      );
    }
  } catch (error) {
    console.error("[Config] Error initializing API URL:", error);
    // Fallback to the default from IPConfig.js
    API_BASE_URL = DEFAULT_API_URL;
    logDebug("[Config] Using fallback API URL:", API_BASE_URL);
  }

  // Double-check that API_BASE_URL actually has a value
  if (!API_BASE_URL) {
    logDebug("[Config] API_BASE_URL is empty, using default from IPConfig");
    API_BASE_URL = DEFAULT_API_URL;
  }

  // Log the final selected URL - always show this message
  console.log("[API Config] Final API URL:", API_BASE_URL);
};

// Helper function for conditional debug logging
function logDebug(message: string, ...args: any[]) {
  if (DEBUG_API_URLS) {
    console.log(message, ...args);
  }
}

// Initialize URL when this module is imported
initializeApiUrl();

/**
 * Get the API base URL synchronously
 * @returns {string} Current API base URL
 */
export const getApiBaseUrlSync = (): string => {
  // Extra safety check to prevent empty URLs
  if (!API_BASE_URL) {
    if (Platform.OS === "web") {
      return "http://localhost/capstone/my-new-app/api";
    } else {
      return `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/api`;
    }
  }
  return API_BASE_URL;
};

/**
 * Get the API base URL asynchronously from storage
 * @returns {Promise<string>} API base URL from storage or default
 */
export const getApiBaseUrl = async (): Promise<string> => {
  try {
    const storedUrl = await AsyncStorage.getItem(API_URL_STORAGE_KEY);
    if (storedUrl) {
      API_BASE_URL = storedUrl;
    }
    return API_BASE_URL;
  } catch (error) {
    console.error("[API Config] Error retrieving API URL:", error);
    return API_BASE_URL;
  }
};

/**
 * Set the API base URL and store it in AsyncStorage
 * @param {string} url - New API base URL to set
 * @returns {Promise<void>}
 */
export const setApiBaseUrl = async (url: string): Promise<void> => {
  if (!url) {
    throw new Error("API URL cannot be empty");
  }

  try {
    API_BASE_URL = url;
    await AsyncStorage.setItem(API_URL_STORAGE_KEY, url);
    console.log("[API Config] API URL set to:", url);
  } catch (error) {
    console.error("[API Config] Error storing API URL:", error);
    throw error;
  }
};

/**
 * Clear the stored API URL from AsyncStorage and reset to default
 * @returns {Promise<string>} The new default API base URL
 */
export const resetApiUrl = async (): Promise<string> => {
  try {
    console.log("[API Config] Resetting API URL to default");
    await AsyncStorage.removeItem(API_URL_STORAGE_KEY);
    // Set to the current local IP (updated in this file)
    API_BASE_URL = `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/api`;
    console.log("[API Config] API URL reset to:", API_BASE_URL);
    return API_BASE_URL;
  } catch (error) {
    console.error("[API Config] Error resetting API URL:", error);
    throw error;
  }
};

export default {
  getApiBaseUrl,
  setApiBaseUrl,
  getApiBaseUrlSync,
  resetApiUrl,
};
