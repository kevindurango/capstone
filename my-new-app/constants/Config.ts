import { Platform } from "react-native";
import { LOCAL_IP_ADDRESS } from "./IPConfig";

// Development flag and network timeout
const DEV_MODE = true;
export const NETWORK_TIMEOUT = 15000; // 15 seconds to allow more processing time

// Base configuration
const API_CONFIG = {
  BASE_PATH: "/capstone/my-new-app/api",
  URLS: {
    ANDROID: LOCAL_IP_ADDRESS, // Use the centralized IP address for Android too
    IOS: LOCAL_IP_ADDRESS, // Use the centralized IP address for iOS
    LOCAL: LOCAL_IP_ADDRESS, // Use the centralized IP address
  },
};

// Get base URL with better error handling and logging
const getBaseUrl = (): string => {
  const platform = Platform.OS;
  console.log(`[Config] Platform detected: ${platform}`);

  try {
    // Always use the same LOCAL_IP_ADDRESS for all platforms
    // This ensures consistent behavior across all devices
    let url = `http://${LOCAL_IP_ADDRESS}${API_CONFIG.BASE_PATH}`;

    // Ensure the URL doesn't end with a slash, so we can add it consistently
    if (url.endsWith("/")) {
      url = url.slice(0, -1);
    }

    console.log(`[Config] Using URL: ${url}`);
    return url;
  } catch (error) {
    console.error("[Config] Error getting base URL:", error);
    return `http://${API_CONFIG.URLS.LOCAL}${API_CONFIG.BASE_PATH}`;
  }
};

const baseUrl = getBaseUrl();
// Get the root URL (without the /api part)
const rootUrl = baseUrl.replace(API_CONFIG.BASE_PATH, "");

// Export API endpoints with consistent URL construction
export const API_URLS = {
  BASE: baseUrl,
  REGISTER: `${baseUrl}/register.php`,
  LOGIN: `${baseUrl}/login.php`,
  PAYMENT: `${baseUrl}/payment.php`,
  ORDER: `${baseUrl}/order.php`,
  PAYMENT_METHODS: `${baseUrl}/payment.php?action=methods`,
  PAYMENT_STATUS: `${baseUrl}/payment.php?action=status`,
  CONNECTIVITY_TEST: `${baseUrl}/connectivity-test.php`,
};

// Helper function for endpoints with error handling
export const getApiUrl = (endpoint: string): string => {
  try {
    if (endpoint.startsWith("/")) {
      return `${baseUrl}${endpoint}`;
    }
    return `${baseUrl}/${endpoint}`;
  } catch {
    return `http://${API_CONFIG.URLS.LOCAL}${API_CONFIG.BASE_PATH}/${endpoint}`;
  }
};

// Helper function to format image URLs correctly
export const getImageUrl = (imagePath: string | null | undefined): string => {
  if (!imagePath) return "";

  // If the image path already contains a URL, return it as is
  if (imagePath.startsWith("http://") || imagePath.startsWith("https://")) {
    return imagePath;
  }

  // Remove any leading slashes for consistency
  const cleanPath = imagePath.startsWith("/") ? imagePath.substring(1) : imagePath;

  // For paths like 'uploads/products/...' - direct path from xampp root
  if (cleanPath.includes("uploads/products/")) {
    return `${rootUrl}/${cleanPath}`;
  }

  // If it's just a filename without path, assume it's in uploads/products
  if (!cleanPath.includes("/")) {
    return `${rootUrl}/uploads/products/${cleanPath}`;
  }

  // If it has 'public/' prefix, strip that out as it's likely not in URL path
  if (cleanPath.startsWith("public/")) {
    return `${rootUrl}/${cleanPath.substring(7)}`;
  }

  // As a fallback, return the cleaned path with root URL
  return `${rootUrl}/${cleanPath}`;
};

export const Config = {
  API_URL: baseUrl,
  ROOT_URL: rootUrl,
  IS_ANDROID: Platform.OS === "android",
  IS_IOS: Platform.OS === "ios",
};
