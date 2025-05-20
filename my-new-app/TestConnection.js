import { API_BASE_URL } from "./constants/IPConfig";

/**
 * Utility function to test API connectivity
 * Call this function from your app to diagnose connection issues
 */
export const testApiConnection = async () => {
  console.log("Testing API connection to:", API_BASE_URL);

  try {
    // First test the general connection test endpoint
    const connectionResponse = await fetch(
      `${API_BASE_URL}/connection_test.php`,
      {
        method: "GET",
      }
    );

    const connectionData = await connectionResponse.json();
    console.log("Connection test response:", connectionData);

    // Then test specifically the update_product endpoint
    const productTestResponse = await fetch(
      `${API_BASE_URL}/farmer/update_product.php?test=1`,
      {
        method: "GET",
      }
    );

    const productTestData = await productTestResponse.json();
    console.log("Product API test response:", productTestData);

    return {
      success: true,
      connectionTest: connectionData,
      productApiTest: productTestData,
    };
  } catch (error) {
    console.error("API connection test failed:", error);
    return {
      success: false,
      error: error.message,
      stack: error.stack,
    };
  }
};

/**
 * Function to test a POST request to the update_product.php endpoint
 */
export const testProductUpdate = async (productId, userId) => {
  const testData = new FormData();
  testData.append("product_id", productId || "75");
  testData.append("user_id", userId || "1");
  testData.append("name", "Test Product");
  testData.append("price", "100");
  testData.append("stock", "10");
  testData.append("unit_type", "kilogram");
  testData.append("category_id", "1");

  console.log("Testing product update with data:", {
    product_id: productId || "75",
    user_id: userId || "1",
    endpoint: `${API_BASE_URL}/farmer/update_product.php`,
  });

  try {
    const response = await fetch(`${API_BASE_URL}/farmer/update_product.php`, {
      method: "POST",
      body: testData,
      headers: {
        Accept: "application/json",
      },
    });

    // Get raw response text first for debugging
    const responseText = await response.text();
    console.log("Raw response:", responseText);

    // Try to parse JSON
    let jsonData;
    try {
      jsonData = JSON.parse(responseText);
    } catch (err) {
      return {
        success: false,
        error: "Invalid JSON response",
        rawResponse: responseText,
      };
    }

    return {
      success: true,
      data: jsonData,
    };
  } catch (error) {
    console.error("Product update test failed:", error);
    return {
      success: false,
      error: error.message,
      stack: error.stack,
    };
  }
};
