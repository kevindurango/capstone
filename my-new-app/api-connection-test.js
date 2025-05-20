// api-connection-test.js

// Configuration
const API_BASE_URL = "http://192.168.1.12/capstone/my-new-app/api";
const TEST_ENDPOINTS = [
  "/request_diagnostic.php",
  "/farmer/update_product.php",
  "/farmer/add_product.php",
  "/barangays.php",
];

// Helper function to test API connectivity
const testConnection = async (endpoint) => {
  console.log(`Testing connectivity to: ${API_BASE_URL}${endpoint}`);

  try {
    const startTime = Date.now();
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method:
        endpoint.includes("update_product") || endpoint.includes("add_product")
          ? "OPTIONS"
          : "GET",
      headers: {
        "Cache-Control": "no-cache",
      },
    });

    const elapsedTime = Date.now() - startTime;
    console.log(`✅ Response status: ${response.status} (${elapsedTime}ms)`);

    // For successful responses, try to parse the JSON
    if (response.status >= 200 && response.status < 300) {
      try {
        if (
          endpoint.includes("update_product") ||
          endpoint.includes("add_product")
        ) {
          // For OPTIONS request we don't expect a body
          console.log(
            `   Headers: ${JSON.stringify([...response.headers.entries()])}`
          );
          return true;
        }

        const data = await response.json();
        console.log(
          `   Response: ${JSON.stringify(data).substring(0, 100)}...`
        );
        return true;
      } catch (parseError) {
        console.error(`❌ Error parsing response: ${parseError.message}`);
        return false;
      }
    } else {
      console.warn(`⚠️ Non-successful status code: ${response.status}`);
      return false;
    }
  } catch (error) {
    console.error(`❌ Connection error: ${error.message}`);
    return false;
  }
};

// Run tests for all endpoints
const runAllTests = async () => {
  console.log("🔍 Starting API connectivity tests...");
  console.log("===============================");

  let successes = 0;
  let failures = 0;

  for (const endpoint of TEST_ENDPOINTS) {
    const success = await testConnection(endpoint);
    if (success) {
      successes++;
    } else {
      failures++;
    }
    console.log("-------------------------------");
  }

  console.log("===============================");
  console.log(`✅ ${successes} endpoints accessible`);
  console.log(`❌ ${failures} endpoints failed`);
  console.log("===============================");

  if (failures > 0) {
    console.log("\nTroubleshooting tips:");
    console.log("1. Check that your PHP server is running");
    console.log("2. Verify the IP address is correct in IPConfig.js");
    console.log("3. Ensure CORS headers are properly set in PHP files");
    console.log("4. Check PHP error logs for server-side issues");
  }
};

// Execute all tests
runAllTests();
