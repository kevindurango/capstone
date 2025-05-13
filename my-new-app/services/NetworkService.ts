/**
 * NetworkService.ts
 * Utility service for handling network connectivity checks and diagnostics
 */

import NetInfo from "@react-native-community/netinfo";
import { getApiBaseUrlSync } from "./apiConfig";
import { Alert, Platform } from "react-native";

interface NetworkStatus {
  isConnected: boolean;
  isInternetReachable: boolean | null;
  type: string;
  details: Record<string, any> | null;
  timestamp: number;
}

interface ApiTestResult {
  endpoint: string;
  status: number;
  success: boolean;
  responseTime: number;
  error?: string;
}

/**
 * Get current network status
 * @returns Promise with NetworkStatus object
 */
export const getNetworkStatus = async (): Promise<NetworkStatus> => {
  try {
    const netInfo = await NetInfo.fetch();
    return {
      isConnected: netInfo.isConnected ?? false,
      isInternetReachable: netInfo.isInternetReachable,
      type: netInfo.type,
      details: netInfo.details,
      timestamp: Date.now(),
    };
  } catch (error) {
    console.error("[NetworkService] Error fetching network status:", error);
    return {
      isConnected: false,
      isInternetReachable: false,
      type: "unknown",
      details: null,
      timestamp: Date.now(),
    };
  }
};

/**
 * Check if the device has network connectivity
 * @returns Promise<boolean> - true if connected
 */
export const hasNetworkConnection = async (): Promise<boolean> => {
  try {
    const netInfo = await NetInfo.fetch();
    return netInfo.isConnected === true && netInfo.isInternetReachable === true;
  } catch (error) {
    console.error("[NetworkService] Error checking network connection:", error);
    return false;
  }
};

/**
 * Test API connectivity to a specific endpoint
 * @param endpoint - API endpoint to test (defaults to connectivity-test.php)
 * @returns Promise with test result
 */
export const testApiConnectivity = async (
  endpoint: string = "connectivity-test.php"
): Promise<ApiTestResult> => {
  const startTime = Date.now();
  const apiUrl = `${getApiBaseUrlSync()}/${endpoint}`;

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);

    const response = await fetch(apiUrl, {
      method: "GET",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Client-Platform": Platform.OS,
      },
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    const responseTime = Date.now() - startTime;

    return {
      endpoint: apiUrl,
      status: response.status,
      success: response.status >= 200 && response.status < 300,
      responseTime,
    };
  } catch (error: any) {
    const responseTime = Date.now() - startTime;
    console.error(`[NetworkService] API test failed for ${apiUrl}:`, error);

    return {
      endpoint: apiUrl,
      status: 0,
      success: false,
      responseTime,
      error: error.message || "Unknown error",
    };
  }
};

/**
 * Run a comprehensive diagnostic test of API endpoints
 * @returns Promise with test results for all critical endpoints
 */
export const runNetworkDiagnostics = async (): Promise<{
  networkStatus: NetworkStatus;
  apiTests: ApiTestResult[];
}> => {
  const networkStatus = await getNetworkStatus();

  if (!networkStatus.isConnected) {
    return {
      networkStatus,
      apiTests: [],
    };
  }

  // Test critical API endpoints
  const endpoints = [
    "connectivity-test.php",
    "api-test.php",
    "login.php",
    "register.php",
    "market.php",
    "barangays.php",
  ];

  const apiTests = await Promise.all(
    endpoints.map((endpoint) => testApiConnectivity(endpoint))
  );

  return {
    networkStatus,
    apiTests,
  };
};

/**
 * Show network diagnostic information to the user
 * Useful for troubleshooting connectivity issues
 */
export const showNetworkDiagnosticInfo = async (): Promise<void> => {
  const diagnostics = await runNetworkDiagnostics();

  let message = `Network: ${
    diagnostics.networkStatus.isConnected ? "Connected" : "Disconnected"
  }\n`;
  message += `Type: ${diagnostics.networkStatus.type}\n\n`;

  if (diagnostics.apiTests.length > 0) {
    message += "API Tests:\n";
    diagnostics.apiTests.forEach((test) => {
      message += `- ${test.endpoint.split("/").pop()}: ${
        test.success ? "✅" : "❌"
      } ${test.status} (${test.responseTime}ms)\n`;
      if (test.error) {
        message += `  Error: ${test.error}\n`;
      }
    });
  } else {
    message += "Could not run API tests - no network connection.";
  }

  Alert.alert("Network Diagnostics", message, [{ text: "OK" }]);
};

/**
 * Handle network errors with consistent error messages and retry options
 * @param error - The caught error
 * @param retryCallback - Function to call when retry is selected
 * @param context - Context string for logging
 */
export const handleNetworkError = (
  error: any,
  retryCallback?: () => void,
  context: string = "API"
): void => {
  console.error(`[${context}] Network error:`, error);

  let title = "Network Error";
  let message =
    "Unable to connect to the server. Please check your internet connection and try again.";

  if (error.name === "AbortError" || error.message?.includes("timeout")) {
    title = "Connection Timeout";
    message =
      "The request took too long to complete. This could be due to slow internet or server issues.";
  } else if (error.message?.includes("Network request failed")) {
    message =
      "Could not connect to the server. Please make sure you have an internet connection and the server is running.";
  }

  const buttons = [{ text: "Cancel", style: "cancel" as const }];

  if (retryCallback) {
    buttons.push({
      text: "Retry",
      onPress: retryCallback,
    });
  }

  Alert.alert(title, message, buttons);
};
