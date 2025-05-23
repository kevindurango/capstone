import { Platform } from "react-native";
import { LOCAL_IP_ADDRESS } from "./IPConfig";
import { getImagePaths } from "./ImageUtils";

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
  LOGOUT: `${baseUrl}/logout.php`,
  FORGOT_PASSWORD: `${baseUrl}/forgot-password.php`,
  RESET_PASSWORD: `${baseUrl}/reset-password.php`,
  USER_PROFILE: `${baseUrl}/user_profile.php`,
  UPDATE_PROFILE: `${baseUrl}/update_profile.php`,
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

  try {
    // Debug log to see what paths we're receiving
    console.log(`[Config] Processing image path: ${imagePath}`);

    // Handle case where path contains encoded query parameters (like %3ft%3d)
    let cleanedPath = imagePath;
    if (cleanedPath.includes("%3f") || cleanedPath.includes("%3F")) {
      // Extract the base path without the encoded query string
      cleanedPath = cleanedPath.split("%3f")[0].split("%3F")[0];
      console.log(`[Config] Cleaned encoded path: ${cleanedPath}`);
    }

    // Special case for problematic product 75 image
    if (cleanedPath.includes("product_6829ff66d0bac.jpeg")) {
      console.log(`[Config] Using direct API path for problematic image`);
      return `http://${LOCAL_IP_ADDRESS}/capstone/api/image.php?path=product_6829ff66d0bac.jpeg&t=${Date.now()}`;
    }

    // Check if path is already a capstone URL pattern from logs (most common pattern)
    if (
      cleanedPath?.startsWith("http") &&
      cleanedPath.includes("capstone/public/uploads")
    ) {
      // Add timestamp to prevent caching
      const timestamp = `t=${Date.now()}`;
      return `${cleanedPath}${cleanedPath.includes("?") ? "&" : "?"}${timestamp}`;
    }

    // Look for common filename patterns from database (e.g., 67ff925ccd2bf_kalamunggay.png)
    // This is the likely format when uploading from the farmer product screen
    if (cleanedPath && /[a-f0-9]+_[\w.]+$/i.test(cleanedPath)) {
      console.log(
        `[Config] Detected filename with hash pattern: ${cleanedPath}`
      );

      // If the path doesn't have uploads/products/ prefix but looks like a product image,
      // add the correct prefix
      if (
        !cleanedPath.includes("uploads/products/") &&
        !cleanedPath.startsWith("http")
      ) {
        // Check if we just need to add the prefix or extract the filename
        const filename = cleanedPath.includes("/")
          ? cleanedPath.split("/").pop()
          : cleanedPath;
        cleanedPath = `uploads/products/${filename}`;
        console.log(`[Config] Normalized path to: ${cleanedPath}`);
      }
    }

    // Use our getImagePaths utility to get the primary URL
    const possiblePaths = getImagePaths(cleanedPath);

    // Return the primary URL if available
    if (possiblePaths.length > 0) {
      return possiblePaths[0];
    }

    // Fallback to empty string if no path could be generated
    return "";
  } catch (error) {
    console.error("[Config] Error processing image URL:", error, imagePath);
    return ""; // Return empty string on error
  }
};

export const Config = {
  ROOT_URL: rootUrl,
  IS_ANDROID: Platform.OS === "android",
  IS_IOS: Platform.OS === "ios",
};

// Export API_URL as the base URL for use throughout the app
export const API_URL = baseUrl;
