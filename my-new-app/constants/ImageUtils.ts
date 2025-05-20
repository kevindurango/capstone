import { LOCAL_IP_ADDRESS } from "./IPConfig";

/**
 * Utility function to get multiple possible image URLs for a product image
 * This helps with image loading fallbacks when the primary URL fails
 *
 * @param imagePath Original image path from database
 * @returns Array of possible image URLs to try
 */
export const getImagePaths = (imagePath: string | null): string[] => {
  if (!imagePath) return [];

  const timestamp = `?t=${Date.now()}`;
  const paths: string[] = [];

  try {
    // Decode URL-encoded parameters before processing
    let cleanedPath = imagePath;

    // Remove any encoded query parameters (common issue with product 75)
    if (cleanedPath.includes("%3f") || cleanedPath.includes("%3F")) {
      // Extract just the base filename without the encoded parameters
      cleanedPath = cleanedPath.split("%3f")[0].split("%3F")[0];
      console.log(`[ImageUtils] Removed encoded query params: ${cleanedPath}`);
    }

    // Special handling for product 75 which has consistent loading issues
    if (cleanedPath.includes("product_6829ff66d0bac.jpeg")) {
      console.log(`[ImageUtils] Special handling for product 75 image`);
      // Try direct PHP access pattern first (most reliable for problematic images)
      paths.push(
        `http://${LOCAL_IP_ADDRESS}/capstone/api/image.php?path=product_6829ff66d0bac.jpeg${timestamp}`
      );

      // Try some additional fallback paths specifically for this image
      paths.push(
        `http://${LOCAL_IP_ADDRESS}/capstone/public/uploads/products/product_6829ff66d0bac.jpeg${timestamp}`,
        `http://${LOCAL_IP_ADDRESS}/uploads/product_6829ff66d0bac.jpeg${timestamp}`,
        `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/public/uploads/product_6829ff66d0bac.jpeg${timestamp}`
      );
    }

    // If it's already a complete URL, use it directly but ensure proper timestamp
    if (cleanedPath.startsWith("http")) {
      // Check common URL patterns from the logs
      const url = `${cleanedPath}${cleanedPath.includes("?") ? "&" : "?"}t=${Date.now()}`;
      paths.push(url);

      // If this is a known pattern, return immediately
      // This prevents unnecessary fallback attempts for valid URLs
      if (cleanedPath.includes("capstone/public/uploads")) {
        return paths;
      }

      // For URLs that don't match the common pattern, continue to generate fallbacks
    }

    // Extract just the filename
    const filename = cleanedPath.split("/").pop();
    if (!filename) return [];

    // Clean the path from any potential issues
    let cleanPath = cleanedPath;
    if (cleanPath.startsWith("/")) {
      cleanPath = cleanPath.substring(1);
    }
    if (cleanPath.startsWith("public/")) {
      cleanPath = cleanPath.substring(7);
    }

    // Analyze the path to determine the best patterns to try
    const isProductImage =
      cleanPath.includes("product_") ||
      cleanPath.includes("/products/") ||
      filename.includes("_") ||
      cleanPath.includes("uploads/products");

    // Using observed patterns from logs in priority order

    // Pattern 1: Most common in logs - Full path with public folder
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/capstone/public/uploads/products/${filename}${timestamp}`
    );

    // For files with product_ prefix, try a special pattern matching product 69/73 format
    if (filename.startsWith("product_")) {
      // Try the root capstone uploads folder format (this works for products 69 and 73)
      paths.push(
        `http://${LOCAL_IP_ADDRESS}/capstone/uploads/products/${filename}${timestamp}`
      );
      // Try direct root access
      paths.push(
        `http://${LOCAL_IP_ADDRESS}/uploads/products/${filename}${timestamp}`
      );
    }

    // Pattern 2: Direct from capstone root - without public folder
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/capstone/uploads/products/${filename}${timestamp}`
    );

    // Pattern 3: Also appears in logs - just the filename in uploads
    if (filename.includes("_")) {
      // Handle product_123.jpg format
      paths.push(
        `http://${LOCAL_IP_ADDRESS}/capstone/public/uploads/${filename}${timestamp}`
      );
    }

    // Pattern 4: Direct database format
    paths.push(`http://${LOCAL_IP_ADDRESS}/${cleanPath}${timestamp}`);

    // Pattern 5: Standard uploads/products path
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/uploads/products/${filename}${timestamp}`
    );

    // Pattern 6: With public prefix
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/public/uploads/products/${filename}${timestamp}`
    );

    // Pattern 7: Direct access through my-new-app folder path
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/capstone/my-new-app/public/uploads/products/${filename}${timestamp}`
    );

    // Pattern 8: Absolute path from web root
    paths.push(`http://${LOCAL_IP_ADDRESS}/${filename}${timestamp}`);

    // Pattern 9: API image endpoint fallback
    paths.push(
      `http://${LOCAL_IP_ADDRESS}/capstone/api/image.php?path=${filename}${timestamp}`
    );

    return paths;
  } catch (error) {
    console.error("[ImageUtils] Error processing image path:", error);
    return [
      `http://${LOCAL_IP_ADDRESS}/uploads/placeholder.png?t=${Date.now()}`,
    ];
  }
};

/**
 * Pre-validate an image URL by making a HEAD request
 * Useful to check if an image exists before trying to load it
 *
 * @param url Image URL to validate
 * @returns Promise resolving to a boolean indicating if the image exists
 */
export const validateImageUrl = async (url: string): Promise<boolean> => {
  try {
    const response = await fetch(url, {
      method: "HEAD",
      headers: {
        "Cache-Control": "no-cache",
        Pragma: "no-cache",
      },
    });

    return (
      response.ok &&
      response.status >= 200 &&
      response.status < 300 &&
      (response.headers.get("content-type") || "").startsWith("image/")
    );
  } catch (e) {
    console.error(`[ImageUtils] Error validating image URL: ${url}`, e);
    return false;
  }
};
