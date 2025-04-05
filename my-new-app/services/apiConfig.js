import AsyncStorage from "@react-native-async-storage/async-storage";

// Default API base URL - will be updated dynamically
let API_BASE_URL = "http://localhost/capstone/my-new-app/api";

// Storage key for API base URL
const API_URL_STORAGE_KEY = "@farmers_market_api_url";

/**
 * Get the API base URL synchronously from the global variable
 * @returns {string} Current API base URL
 */
export const getApiBaseUrlSync = () => {
  return API_BASE_URL;
};

/**
 * Get the API base URL asynchronously from storage
 * @returns {Promise<string>} API base URL from storage or default
 */
export const getApiBaseUrl = async () => {
  try {
    const storedUrl = await AsyncStorage.getItem(API_URL_STORAGE_KEY);
    if (storedUrl) {
      API_BASE_URL = storedUrl;
    }
    return API_BASE_URL;
  } catch (error) {
    console.error("[API Config] Error retrieving API URL from storage:", error);
    return API_BASE_URL;
  }
};

/**
 * Set the API base URL and store it in AsyncStorage
 * @param {string} url - New API base URL to set
 * @returns {Promise<void>}
 */
export const setApiBaseUrl = async (url) => {
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
 * Create an API service with error handling and automatic retries
 */
export const createApiService = () => ({
  fetch: async (endpoint, options = {}) => {
    const maxRetries = options.maxRetries || 1;
    let retries = 0;
    let lastError = null;

    while (retries <= maxRetries) {
      try {
        // Make sure we're using the latest API base URL
        await getApiBaseUrl();
        const baseUrl = API_BASE_URL;
        const url = `${baseUrl}${endpoint}`;

        console.log(`[API] Making request to: ${url}`, options.method || "GET");

        const response = await fetch(url, {
          ...options,
          headers: {
            "Content-Type": "application/json",
            ...(options.headers || {}),
          },
        });

        // Try to parse the response as JSON
        const responseText = await response.text();
        let responseData;

        try {
          responseData = JSON.parse(responseText);
        } catch (parseError) {
          console.error("[API] JSON Parse error:", parseError);
          console.error("[API] Response text:", responseText);
          throw new Error(
            `Invalid JSON response: ${responseText.substring(0, 100)}...`
          );
        }

        if (!response.ok) {
          throw {
            status: response.status,
            message: responseData?.message || "An error occurred",
            data: responseData,
          };
        }

        return responseData;
      } catch (error) {
        console.error(
          `[API] Request error (attempt ${retries + 1}/${maxRetries + 1}):`,
          error
        );
        lastError = error;

        // If this wasn't our last retry, wait before retrying
        if (retries < maxRetries) {
          await new Promise((resolve) =>
            setTimeout(resolve, 1000 * (retries + 1))
          );
        }

        retries++;
      }
    }

    // If we get here, all retries failed
    throw lastError;
  },
});

export default {
  getApiBaseUrl,
  setApiBaseUrl,
  getApiBaseUrlSync,
  createApiService,
};
