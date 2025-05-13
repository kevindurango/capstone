import apiConfig from "./apiConfig";

export interface Feedback {
  feedback_id: number;
  user_id: number;
  product_id?: number;
  farmer_id?: number;
  order_id?: number;
  feedback_text: string;
  rating: number;
  created_at: string;
  status: "pending" | "responded";
  username?: string;
  first_name?: string;
  last_name?: string;
  product_name?: string;
  product_image?: string;
  responses?: FeedbackResponse[];
}

export interface FeedbackResponse {
  response_id: number;
  feedback_id: number;
  response_text: string;
  responded_by: number;
  response_date: string;
  username?: string;
  first_name?: string;
  last_name?: string;
}

export interface FeedbackSubmission {
  user_id: number | undefined;
  product_id: number;
  order_id?: number;
  feedback_text: string;
  rating: number;
}

export interface FeedbackResponseSubmission {
  feedback_id: number;
  response_text: string;
  responded_by: number;
}

class FeedbackService {
  // Helper method to safely parse JSON responses and handle HTML errors
  static async safelyParseResponse(response: Response, context: string) {
    try {
      // First check if response is ok
      if (!response.ok) {
        console.warn(
          `[Feedback] HTTP error ${response.status} from ${context}`
        );
        return {
          success: false,
          message: `Server returned ${response.status} error`,
          feedback: [],
        };
      }

      // Get text content first
      const text = await response.text();

      // Check if it starts with HTML indicators (common PHP error outputs)
      if (
        text.trim().startsWith("<!DOCTYPE") ||
        text.trim().startsWith("<html")
      ) {
        console.error(
          `[Feedback] Received HTML instead of JSON from ${context}`
        );
        return {
          success: false,
          message: "Server returned HTML instead of JSON data",
          feedback: [],
        };
      }

      // If not HTML, try to parse as JSON
      try {
        return JSON.parse(text);
      } catch (parseError) {
        console.error(
          `[Feedback] JSON parse error from ${context}:`,
          parseError
        );
        // Log the first part of the response for debugging
        console.error(
          `[Feedback] Response content (first 100 chars): ${text.substring(
            0,
            100
          )}`
        );
        return {
          success: false,
          message: "Invalid JSON response from server",
          feedback: [],
        };
      }
    } catch (error) {
      console.error(
        `[Feedback] Error processing response from ${context}:`,
        error
      );
      return {
        success: false,
        message: "Failed to process server response",
        feedback: [],
      };
    }
  }

  // Get all feedback
  static async getAllFeedback() {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php`;
      console.log(`[Feedback] Fetching all feedback from: ${apiUrl}`);

      const response = await fetch(apiUrl);
      return await this.safelyParseResponse(response, "getAllFeedback");
    } catch (error) {
      console.error("[Feedback] Error fetching all feedback:", error);
      return {
        success: false,
        message: "Failed to fetch feedback",
        feedback: [],
      };
    }
  }

  // Get feedback for a specific product
  static async getFeedbackByProduct(productId: number) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php?product_id=${productId}`;
      console.log(
        `[Feedback] Fetching feedback for product ${productId} from: ${apiUrl}`
      );

      const response = await fetch(apiUrl);
      return await this.safelyParseResponse(
        response,
        `getFeedbackByProduct(${productId})`
      );
    } catch (error) {
      console.error(
        `[Feedback] Error fetching feedback for product ${productId}:`,
        error
      );
      return {
        success: false,
        message: "Failed to fetch product feedback",
        feedback: [],
      };
    }
  }

  // Get feedback for a specific farmer
  static async getFeedbackByFarmer(farmerId: number) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php?farmer_id=${farmerId}`;
      console.log(
        `[Feedback] Fetching feedback for farmer ${farmerId} from: ${apiUrl}`
      );

      const response = await fetch(apiUrl);
      return await this.safelyParseResponse(
        response,
        `getFeedbackByFarmer(${farmerId})`
      );
    } catch (error) {
      console.error(
        `[Feedback] Error fetching feedback for farmer ${farmerId}:`,
        error
      );
      return {
        success: false,
        message: "Failed to fetch farmer feedback",
        feedback: [],
      };
    }
  }

  // Get feedback for a specific order
  static async getFeedbackByOrder(orderId: number) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php?order_id=${orderId}`;
      console.log(
        `[Feedback] Fetching feedback for order ${orderId} from: ${apiUrl}`
      );

      const response = await fetch(apiUrl);
      return await this.safelyParseResponse(
        response,
        `getFeedbackByOrder(${orderId})`
      );
    } catch (error) {
      console.error(
        `[Feedback] Error fetching feedback for order ${orderId}:`,
        error
      );
      return {
        success: false,
        message: "Failed to fetch order feedback",
        feedback: [],
      };
    }
  }

  // Get orders eligible for feedback (completed orders without feedback)
  static async getOrdersEligibleForFeedback(userId: number) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php?eligible_orders=${userId}`;
      console.log(`[Feedback] Fetching eligible orders for user ${userId}`);

      const response = await fetch(apiUrl);
      return await this.safelyParseResponse(
        response,
        `getOrdersEligibleForFeedback(${userId})`
      );
    } catch (error) {
      console.error(
        `[Feedback] Error fetching eligible orders for user ${userId}:`,
        error
      );
      return {
        success: false,
        message: "Failed to fetch eligible orders",
        orders: [],
      };
    }
  }

  // Submit new feedback
  static async submitFeedback(feedback: FeedbackSubmission) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback.php`;
      console.log(`[Feedback] Submitting feedback to: ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(feedback),
      });

      return await this.safelyParseResponse(response, "submitFeedback");
    } catch (error) {
      console.error("[Feedback] Error submitting feedback:", error);
      return {
        success: false,
        message: "Failed to submit feedback",
      };
    }
  }

  // Submit response to feedback
  static async submitFeedbackResponse(response: FeedbackResponseSubmission) {
    try {
      const apiUrl = `${apiConfig.getApiBaseUrlSync()}/feedback_response.php`;
      console.log(`[Feedback] Submitting feedback response to: ${apiUrl}`);

      const apiResponse = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(response),
      });

      return await this.safelyParseResponse(
        apiResponse,
        "submitFeedbackResponse"
      );
    } catch (error) {
      console.error("[Feedback] Error submitting feedback response:", error);
      return {
        success: false,
        message: "Failed to submit response",
      };
    }
  }
}

export default FeedbackService;
