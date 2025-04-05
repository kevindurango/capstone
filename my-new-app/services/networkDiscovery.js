import AsyncStorage from "@react-native-async-storage/async-storage";
import { getApiBaseUrlSync, setApiBaseUrl } from "./apiConfig";
import { Platform } from "react-native";

const RECENT_IPS_KEY = "@farmers_market_recent_ips";
const MAX_RECENT_IPS = 5;

/**
 * Get a list of recently used server IPs
 * @returns {Promise<Array<string>>} List of recent IPs
 */
export const getRecentIps = async () => {
  try {
    const storedIps = await AsyncStorage.getItem(RECENT_IPS_KEY);
    return storedIps ? JSON.parse(storedIps) : [];
  } catch (error) {
    console.error("[Network Discovery] Error retrieving recent IPs:", error);
    return [];
  }
};

/**
 * Save an IP to the recent IPs list
 * @param {string} ip - IP address to save
 */
export const saveRecentIp = async (ip) => {
  try {
    const recentIps = await getRecentIps();

    // Remove the IP if it already exists
    const filteredIps = recentIps.filter((recentIp) => recentIp !== ip);

    // Add the new IP at the beginning
    const updatedIps = [ip, ...filteredIps].slice(0, MAX_RECENT_IPS);

    await AsyncStorage.setItem(RECENT_IPS_KEY, JSON.stringify(updatedIps));
  } catch (error) {
    console.error("[Network Discovery] Error saving recent IP:", error);
  }
};

/**
 * Get likely IP addresses based on common development environments
 * @returns {Array<string>} List of likely server addresses
 */
export const getLikelyIpAddresses = () => {
  // Common IP addresses for development environments
  const commonIps = [
    "localhost",
    "127.0.0.1",
    "10.0.2.2", // Android emulator host
    "192.168.55.123", // Default from original code
  ];

  // Add common network patterns
  for (let i = 1; i <= 10; i++) {
    commonIps.push(`192.168.1.${i}`);
    commonIps.push(`192.168.0.${i}`);
    commonIps.push(`192.168.${i}.1`);
  }

  // Check if Platform is defined before accessing its properties
  if (Platform && Platform.OS) {
    if (Platform.OS === "android") {
      return ["10.0.2.2", ...commonIps]; // Android emulator host first
    } else if (Platform.OS === "ios") {
      return ["localhost", ...commonIps]; // localhost first for iOS
    }
  }

  // If Platform is undefined or it's not ios/android, return default order
  return ["localhost", "10.0.2.2", ...commonIps];
};

/**
 * Test connection to a server IP
 * @param {string} ip - IP address to test
 * @returns {Promise<boolean>} Whether connection was successful
 */
export const testServerConnection = async (ip) => {
  try {
    const url = `http://${ip}/capstone/my-new-app/api/ping.php`;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000); // 2-second timeout

    const response = await fetch(url, {
      method: "GET",
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    const data = await response.json();
    return data && data.status === "success";
  } catch (error) {
    console.log(
      `[Network Discovery] Connection test failed for ${ip}:`,
      error.message
    );
    return false;
  }
};

/**
 * Auto-discover the server on the local network
 * @returns {Promise<string|null>} Discovered server IP or null
 */
export const discoverServer = async () => {
  // First try recent IPs
  const recentIps = await getRecentIps();
  for (const ip of recentIps) {
    console.log(`[Network Discovery] Testing recent IP: ${ip}`);
    if (await testServerConnection(ip)) {
      console.log(`[Network Discovery] Found server at recent IP: ${ip}`);
      return ip;
    }
  }

  // Try common IP addresses
  const likelyIps = getLikelyIpAddresses();
  for (const ip of likelyIps) {
    console.log(`[Network Discovery] Testing likely IP: ${ip}`);
    if (await testServerConnection(ip)) {
      console.log(`[Network Discovery] Found server at: ${ip}`);
      await saveRecentIp(ip);
      return ip;
    }
  }

  console.log("[Network Discovery] Server not found on common IPs");
  return null;
};

/**
 * Update API URL with discovered server or specified IP
 * @param {string} [manualIp] - Optional manual IP to use
 * @returns {Promise<boolean>} Whether update was successful
 */
export const updateServerUrl = async (manualIp = null) => {
  try {
    let serverIp = manualIp;

    if (!serverIp) {
      serverIp = await discoverServer();
    }

    if (!serverIp) {
      console.log(
        "[Network Discovery] Could not discover server automatically"
      );
      return false;
    }

    const newBaseUrl = `http://${serverIp}/capstone/my-new-app/api`;
    await setApiBaseUrl(newBaseUrl);
    await saveRecentIp(serverIp);
    console.log(`[Network Discovery] Server URL updated to: ${newBaseUrl}`);
    return true;
  } catch (error) {
    console.error("[Network Discovery] Error updating server URL:", error);
    return false;
  }
};

export default {
  getRecentIps,
  saveRecentIp,
  getLikelyIpAddresses,
  testServerConnection,
  discoverServer,
  updateServerUrl,
};
