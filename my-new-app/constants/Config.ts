import { Platform } from "react-native";

// Development flag and network timeout
const DEV_MODE = true;
const NETWORK_TIMEOUT = 5000;

// Base configuration
const API_CONFIG = {
  BASE_PATH: "/capstone/my-new-app/api",
  URLS: {
    ANDROID: "10.0.2.2", // Remove port number
    IOS: "localhost",
    LOCAL: "192.168.1.100",
  },
};

// Get base URL with error handling
const getBaseUrl = (): string => {
  const platform = Platform.OS;
  console.log(`[Config] Platform: ${platform}`);

  try {
    if (platform === "ios") {
      const url = `http://${API_CONFIG.URLS.IOS}${API_CONFIG.BASE_PATH}`;
      console.log(`[Config] Using iOS URL: ${url}`);
      return url;
    }
    if (platform === "android") {
      const url = `http://${API_CONFIG.URLS.ANDROID}${API_CONFIG.BASE_PATH}`;
      console.log(`[Config] Using Android URL: ${url}`);
      return url;
    }
    const url = `http://${API_CONFIG.URLS.LOCAL}${API_CONFIG.BASE_PATH}`;
    console.log(`[Config] Using Local URL: ${url}`);
    return url;
  } catch (error) {
    console.error("[Config] Error getting base URL:", error);
    return `http://${API_CONFIG.URLS.LOCAL}${API_CONFIG.BASE_PATH}`;
  }
};

// Export API endpoints
export const API_URLS = {
  BASE: getBaseUrl(),
  REGISTER: `${getBaseUrl()}/register.php`,
  LOGIN: `${getBaseUrl()}/login.php`,
};

// Helper function for endpoints with error handling
export const getApiUrl = (endpoint: string): string => {
  try {
    return `${API_URLS.BASE}${endpoint}`;
  } catch {
    return `http://${API_CONFIG.URLS.LOCAL}${API_CONFIG.BASE_PATH}${endpoint}`;
  }
};
