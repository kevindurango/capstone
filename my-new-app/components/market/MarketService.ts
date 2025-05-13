import { getApiBaseUrlSync, resetApiUrl } from "@/services/apiConfig";
import { Product } from "./types";
import NetInfo from "@react-native-community/netinfo";

// Define payment related interfaces
interface Payment {
  payment_id: number;
  order_id: number;
  status: string;
  amount: number;
  payment_method: string;
  created_at: string;
}

interface PaymentMethod {
  method_id: number;
  method_name: string;
  is_active: boolean;
}

interface PaymentStatus {
  status_id: number;
  payment_id: number;
  status: string;
  timestamp: string;
}

// API URLs for different endpoints - make dynamic with function to ensure latest URL
const getAPIUrls = () => ({
  PAYMENT: `${getApiBaseUrlSync()}/payment.php`,
  ORDER: `${getApiBaseUrlSync()}/order.php`,
  MARKET: `${getApiBaseUrlSync()}/market.php`,
});

// Utility function to check network connectivity
const checkNetworkConnectivity = async (): Promise<boolean> => {
  try {
    const networkState = await NetInfo.fetch();
    return (
      networkState.isConnected === true &&
      networkState.isInternetReachable === true
    );
  } catch (error) {
    console.error(
      "[MarketService] Error checking network connectivity:",
      error
    );
    return false;
  }
};

// API service for market functionality
const createMarketService = () => ({
  getProducts: async (
    params: {
      category?: string;
      search?: string;
      limit?: number;
      offset?: number;
    } = {}
  ) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      let url = `${baseUrl}/market.php`;

      // Build query string from params
      const queryParams = Object.entries(params)
        .filter(
          ([_, value]) => value !== undefined && value !== null && value !== ""
        )
        .map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`)
        .join("&");

      if (queryParams) {
        url += `?${queryParams}`;
      }

      console.log("[Market] Fetching products from:", url);

      const response = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      const textResponse = await response.text();
      console.log(
        "[Market] Raw response:",
        textResponse.substring(0, 200) +
          (textResponse.length > 200 ? "..." : "")
      );

      // Check if response starts with HTML - common server error
      if (
        textResponse.trim().startsWith("<!DOCTYPE") ||
        textResponse.trim().startsWith("<html")
      ) {
        console.error("[Market] Received HTML instead of JSON");
        throw new Error("Server error occurred. Please check server logs.");
      }

      let data;
      try {
        data = JSON.parse(textResponse);
      } catch (error) {
        console.error("[Market] JSON parse error:", error);
        throw new Error(
          "Invalid response from server. Please check server configuration."
        );
      }

      if (response.status >= 400 || data.status === "error") {
        throw new Error(data?.message || "Failed to fetch products");
      }

      return data;
    } catch (error: any) {
      console.error("[Market] Error fetching products:", error.message);
      throw error;
    }
  },

  // Add a reset API URL function
  resetApiConfiguration: async () => {
    try {
      const newUrl = await resetApiUrl();
      console.log("[Market] Reset API URL to:", newUrl);
      return { success: true, newUrl };
    } catch (error: any) {
      console.error("[Market] Failed to reset API URL:", error.message);
      return { success: false, error: error.message };
    }
  },

  // Create a new order
  createOrder: async (orderData: {
    items: Array<{ product_id: number; quantity: number }>;
    pickup_details?: string;
    user_id?: string | number;
  }) => {
    try {
      const baseUrl = getApiBaseUrlSync();
      const url = `${baseUrl}/order.php`;

      console.log("[Market] Creating order:", orderData);

      // Check network connectivity before making the request
      const isConnected = await checkNetworkConnectivity();
      if (!isConnected) {
        throw new Error("No network connection available");
      }

      const response = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(orderData),
      });

      const responseData = await response.json();

      if (!response.ok || responseData.status === "error") {
        throw new Error(responseData.message || "Failed to create order");
      }

      return responseData;
    } catch (error: any) {
      console.error("[Market] Order creation error:", error);
      throw error;
    }
  },
});

export const updateOrderStatus = async (orderId: number, status: string) => {
  const MAX_RETRIES = 3;
  const RETRY_DELAY = 1000; // 1 second
  const API_URLS = getAPIUrls(); // Get fresh API URLs

  const delay = (ms: number) =>
    new Promise((resolve) => setTimeout(resolve, ms));

  let lastError = null;

  for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
    try {
      console.log(
        `[MarketService] Updating order #${orderId} status to ${status} (attempt ${
          attempt + 1
        }/${MAX_RETRIES})`
      );

      // Check network connectivity before making the request
      const isConnected = await checkNetworkConnectivity();
      if (!isConnected) {
        throw new Error("No network connection available");
      }

      // Use a timeout for the fetch request
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // Extended to 15 seconds

      const response = await fetch(`${API_URLS.ORDER}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update_status", // Explicitly include action in the body
          order_id: orderId,
          status: status,
          // Removed the empty items array that was causing the error
        }),
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      // Handle server errors
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Server returned ${response.status}: ${errorText}`);
      }

      const data = await response.json();
      console.log(
        `[MarketService] Order #${orderId} status updated successfully`
      );
      return data;
    } catch (error: any) {
      lastError = error;
      console.warn(
        `[MarketService] Update order status attempt ${attempt + 1} failed:`,
        error
      );

      // If we're using up our last retry, don't delay
      if (attempt < MAX_RETRIES - 1) {
        console.log(`[MarketService] Retrying in ${RETRY_DELAY}ms...`);
        await delay(RETRY_DELAY);
      }
    }
  }

  // All retries failed - throw the error
  console.error(
    "[MarketService] All attempts to update order status failed:",
    lastError
  );
  throw (
    lastError ||
    new Error(
      "Failed to update order status after multiple attempts. Please check your connection and try again."
    )
  );
};

export const processPayment = async (paymentData: {
  order_id: number;
  payment_method: string;
  user_id?: number | null;
  amount: number;
  payment_notes?: string;
  card_details?: {
    card_number: string;
    card_name: string;
    expiry_date: string;
    expiry_month: number;
    expiry_year: number;
    cvv: string;
  };
}) => {
  const MAX_RETRIES = 2;
  const RETRY_DELAY = 2000; // 2 seconds
  const API_URLS = getAPIUrls(); // Get fresh API URLs

  const delay = (ms: number) =>
    new Promise((resolve) => setTimeout(resolve, ms));
  let lastError = null;

  for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
    try {
      console.log(
        `[MarketService] Processing payment (attempt ${
          attempt + 1
        }/${MAX_RETRIES}):`,
        JSON.stringify({
          amount: paymentData.amount,
          order_id: paymentData.order_id,
          payment_method: paymentData.payment_method,
          card_details: paymentData.card_details ? undefined : undefined,
        })
      );

      // Check network connectivity first
      const isConnected = await checkNetworkConnectivity();
      if (!isConnected) {
        throw new Error(
          "No network connection available. Please check your internet connection and try again."
        );
      }

      // Use a timeout for the fetch request
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout

      const response = await fetch(API_URLS.PAYMENT, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(paymentData),
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const errorText = await response.text();
        console.error(
          `[MarketService] Payment API error (${response.status}):`,
          errorText
        );
        throw new Error(`Payment failed: Server error ${response.status}`);
      }

      const result = await response.json();
      console.log("[MarketService] Payment API response:", result);

      if (result.status !== "success") {
        throw new Error(result.message || "Payment processing failed");
      }

      return result;
    } catch (error: any) {
      lastError = error;
      console.warn(
        `[MarketService] Payment processing attempt ${attempt + 1} failed:`,
        error
      );

      // If this isn't our last attempt, wait and retry
      if (attempt < MAX_RETRIES - 1) {
        console.log(`[MarketService] Retrying payment in ${RETRY_DELAY}ms...`);
        await delay(RETRY_DELAY);
      }
    }
  }

  // All retries failed - throw the error
  console.error(
    "[MarketService] All payment processing attempts failed:",
    lastError
  );
  throw (
    lastError ||
    new Error(
      "Payment processing failed after multiple attempts. Please try again."
    )
  );
};

export const getPaymentMethods = async (): Promise<PaymentMethod[]> => {
  const API_URLS = getAPIUrls(); // Get fresh API URLs
  try {
    const response = await fetch(`${API_URLS.PAYMENT}?action=methods`, {
      method: "GET",
    });

    if (!response.ok) {
      throw new Error("Failed to fetch payment methods");
    }

    const result = await response.json();
    return result.data;
  } catch (error) {
    console.error("[MarketService] Error fetching payment methods:", error);
    return [
      { method_id: 1, method_name: "credit_card", is_active: true },
      { method_id: 2, method_name: "bank_transfer", is_active: true },
      { method_id: 3, method_name: "cash_on_pickup", is_active: true },
    ];
  }
};

export const getPaymentStatus = async (
  paymentId: number
): Promise<PaymentStatus[]> => {
  const API_URLS = getAPIUrls(); // Get fresh API URLs
  try {
    const response = await fetch(
      `${API_URLS.PAYMENT}?action=status&payment_id=${paymentId}`,
      {
        method: "GET",
      }
    );

    if (!response.ok) {
      throw new Error("Failed to fetch payment status");
    }

    const result = await response.json();
    return result.data;
  } catch (error) {
    console.error("[MarketService] Error fetching payment status:", error);
    throw error;
  }
};

const marketService = createMarketService();

// Add updateOrderStatus to the marketService object
const extendedMarketService = {
  ...marketService,
  updateOrderStatus,
  processPayment,
  getPaymentMethods,
  getPaymentStatus,
};

export { extendedMarketService as marketService };
