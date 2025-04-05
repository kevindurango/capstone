import { API_URLS, getApiUrl } from "@/constants/Config";
import { storeRegistration } from "./offlineRegistration";

const TIMEOUT = 60000; // Increase to 60 seconds

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
    this.name = "ApiError";
  }
}

const fetchWithTimeout = async (
  url: string,
  options: RequestInit = {}
): Promise<Response> => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort();
    console.log("[API] Request timed out for:", url);
  }, TIMEOUT);

  try {
    console.log("[API] Starting request with URL:", url);
    const response = await fetch(url, {
      ...options,
      signal: controller.signal,
    });

    clearTimeout(timeoutId);
    if (!response.ok) {
      throw new ApiError(response.status, await response.text());
    }
    return response;
  } catch (error: unknown) {
    clearTimeout(timeoutId);
    console.log("[API] Detailed fetch error:", error);
    if (error instanceof Error && error.name === "AbortError") {
      throw new ApiError(408, "Request timeout - server may be unreachable");
    }
    throw error;
  }
};

export const api = {
  async fetch<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    try {
      const url = getApiUrl(endpoint);
      console.log("[API] Attempting request to:", url);
      console.log("[API] Payload being sent:", options.body); // Log the payload

      const response = await fetchWithTimeout(url, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          ...options.headers,
        },
      });

      const responseText = await response.text();
      console.log("[API] Response:", responseText);

      try {
        return JSON.parse(responseText);
      } catch {
        throw new ApiError(500, "Invalid JSON response");
      }
    } catch (error) {
      console.error("[API] Request failed:", error);
      if (
        error instanceof TypeError &&
        error.message === "Network request failed"
      ) {
        if (options.method === "POST") {
          await storeRegistration(JSON.parse(options.body as string));
          console.log("[API] Registration stored for offline sync");
        }
      }
      throw error;
    }
  },
};
