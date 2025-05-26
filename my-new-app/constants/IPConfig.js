/**
 * Central IP address configuration
 * Only change the IP address here when it changes
 */
import { Platform } from "react-native";

// Define both development and production URLs
const DEV_IP_ADDRESS = "192.168.1.12";
const PROD_IP_ADDRESS = "192.168.1.12"; // Using same IP for now, change this to your production server IP when deploying

// Use development IP in dev mode, production IP in release mode
export const LOCAL_IP_ADDRESS = __DEV__ ? DEV_IP_ADDRESS : PROD_IP_ADDRESS;

// Platform-specific base URL
const getPlatformSpecificUrl = () => {
  if (Platform.OS === "web") {
    // Web can use localhost in dev, but needs production URL in release
    return __DEV__
      ? "http://localhost/capstone/my-new-app/api"
      : `http://${PROD_IP_ADDRESS}/capstone/my-new-app/api`;
  } else {
    // Mobile devices use IP address based on environment
    return `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/api`;
  }
};

// Full API base URL constructed from the IP
export const API_BASE_URL = getPlatformSpecificUrl();

// Export default for legacy imports
export default {
  LOCAL_IP_ADDRESS,
  API_BASE_URL,
};
