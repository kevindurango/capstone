/**
 * Central IP address configuration
 * Only change the IP address here when it changes
 */
import { Platform } from "react-native";

export const LOCAL_IP_ADDRESS = "192.168.1.12"; // This is the working IP address

// Platform-specific base URL
const getPlatformSpecificUrl = () => {
  if (Platform.OS === "web") {
    // Web can use localhost
    return "http://localhost/capstone/my-new-app/api";
  } else {
    // Mobile devices need to use the actual IP address
    // Removed trailing slash to prevent double-slash in URLs
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
