import { Platform } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";

// Storage keys
const NGROK_URL_STORAGE_KEY = "@FarmersMarket:ngrokUrl";
const LOCAL_IP_STORAGE_KEY = "@FarmersMarket:localIpAddress";

// Default values
const DEFAULT_LOCAL_IP = "192.168.1.11"; // Default local IP address

// Function to set the ngrok URL
export const setNgrokUrl = async (url: string) => {
  if (!url.trim()) return false;
  try {
    await AsyncStorage.setItem(NGROK_URL_STORAGE_KEY, url);
    console.log(`Ngrok URL set to: ${url}`);
    return true;
  } catch (error) {
    console.error("Failed to save ngrok URL:", error);
    return false;
  }
};

// Function to get the stored ngrok URL
export const getNgrokUrl = async (): Promise<string | null> => {
  try {
    return await AsyncStorage.getItem(NGROK_URL_STORAGE_KEY);
  } catch (error) {
    console.error("Failed to get ngrok URL:", error);
    return null;
  }
};

// New function to set local IP address
export const setLocalIpAddress = async (ipAddress: string) => {
  if (!ipAddress.trim()) return false;
  try {
    await AsyncStorage.setItem(LOCAL_IP_STORAGE_KEY, ipAddress);
    console.log(`Local IP address set to: ${ipAddress}`);
    return true;
  } catch (error) {
    console.error("Failed to save local IP address:", error);
    return false;
  }
};

// New function to get the stored local IP address
export const getLocalIpAddress = async (): Promise<string> => {
  try {
    const storedIp = await AsyncStorage.getItem(LOCAL_IP_STORAGE_KEY);
    return storedIp || DEFAULT_LOCAL_IP;
  } catch (error) {
    console.error("Failed to get local IP address:", error);
    return DEFAULT_LOCAL_IP;
  }
};

// Check if a URL is accessible (for ngrok health check)
export const isUrlAccessible = async (
  url: string,
  timeout = 3000
): Promise<boolean> => {
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const response = await fetch(url, {
      method: "HEAD",
      signal: controller.signal,
    });

    clearTimeout(timeoutId);
    return response.ok;
  } catch (error) {
    console.log(`URL accessibility check failed for ${url}:`, error);
    return false;
  }
};

// Modified API base URL function to prioritize local IP by default
export const getApiBaseUrlSync = () => {
  if (Platform.OS === "web") {
    return "http://localhost/capstone/my-new-app/api";
  } else {
    // For mobile devices using Expo
    const localIp = global.localIpAddress || DEFAULT_LOCAL_IP;

    // Path configurations
    const localPath = "/capstone/my-new-app/api"; // Path for local IP
    const ngrokPath = "/capstone/my-new-app/api"; // Full path for ngrok

    // CHANGED: Prioritize local IP by default
    // Only use ngrok if explicitly requested by setting useNgrok to true
    const useNgrok = global.useNgrok === true && global.ngrokUrl;

    if (useNgrok) {
      console.log(`Using ngrok URL: ${global.ngrokUrl}${ngrokPath}`);
      return `${global.ngrokUrl}${ngrokPath}`;
    }

    console.log(`Using local IP: http://${localIp}${localPath}`);
    return `http://${localIp}${localPath}`;
  }
};

// Async version with modified priorities
export const getApiBaseUrl = async () => {
  if (Platform.OS === "web") {
    return "http://localhost/capstone/my-new-app/api";
  } else {
    const apiPath = "/capstone/my-new-app/api";

    // Get stored values
    const ngrokUrl = await getNgrokUrl();
    const localIp = await getLocalIpAddress();
    const useNgrok =
      (await AsyncStorage.getItem("@FarmersMarket:useNgrok")) === "true";

    // Set global values for sync access
    global.localIpAddress = localIp;
    global.ngrokUrl = ngrokUrl;
    global.useNgrok = useNgrok;

    // Use ngrok only if explicitly enabled and available
    if (useNgrok && ngrokUrl) {
      // Test if ngrok is accessible
      const testUrl = `${ngrokUrl}/ping.php`;
      try {
        const isNgrokAccessible = await isUrlAccessible(testUrl);
        if (isNgrokAccessible) {
          console.log(`Ngrok URL is accessible: ${ngrokUrl}`);
          return `${ngrokUrl}${apiPath}`;
        } else {
          console.log(
            `Ngrok URL is not accessible, falling back to local IP: ${localIp}`
          );
        }
      } catch (error) {
        console.log("Error checking ngrok accessibility:", error);
      }
    }

    // Default to local IP
    console.log(`Using local IP: http://${localIp}${apiPath}`);
    return `http://${localIp}${apiPath}`;
  }
};

// New function to set whether to use ngrok
export const setUseNgrok = async (use: boolean) => {
  try {
    global.useNgrok = use;
    await AsyncStorage.setItem(
      "@FarmersMarket:useNgrok",
      use ? "true" : "false"
    );
    console.log(`Use ngrok set to: ${use}`);
    return true;
  } catch (error) {
    console.error("Failed to save ngrok preference:", error);
    return false;
  }
};
