import { getApiUrl } from "@/constants/Config";
import IPConfig from "@/constants/IPConfig";
import { ApiError } from "./api";
import { Platform } from "react-native";

/**
 * Interface for Product data
 */
export interface ProductData {
  product_id?: number;
  name: string;
  description: string;
  price: number;
  stock: number;
  farmer_id: number;
  category_id?: number;
  unit_type?: string;
  image?: string | null;
  status?: string;
  current_image?: string;
  barangay_id?: number | null;
  field_id?: number | null;
  categories?: number[];
}

/**
 * Service for handling product-related API operations
 */
export class ProductService {
  private apiBaseUrl: string;
  // Update the image upload directory to match the specified path
  private imageDirectory: string = "public/uploads/products/";

  constructor() {
    this.apiBaseUrl = IPConfig.API_BASE_URL;
  }

  /**
   * Add a new product with image upload support
   *
   * @param productData Product data
   * @param imageUri Local image URI (if available)
   * @returns Promise with the API response
   */
  async addProduct(productData: ProductData, imageUri?: string): Promise<any> {
    try {
      console.log("[ProductService] Adding product:", productData);

      const formData = this.createProductFormData(productData, imageUri);

      const response = await fetch(
        `${this.apiBaseUrl}/farmer/add_product.php`,
        {
          method: "POST",
          body: formData,
          headers: {
            Accept: "application/json",
            // Note: Don't set Content-Type for multipart/form-data
          },
        }
      );

      const responseText = await response.text();
      console.log("[ProductService] Add product response:", responseText);

      try {
        return JSON.parse(responseText);
      } catch (error) {
        console.error("[ProductService] Error parsing response:", error);
        throw new ApiError(500, "Invalid server response");
      }
    } catch (error) {
      console.error("[ProductService] Add product error:", error);
      throw error;
    }
  }

  /**
   * Update an existing product
   *
   * @param productId Product ID
   * @param productData Updated product data
   * @param imageUri New image URI (if available)
   * @returns Promise with the API response
   */
  async updateProduct(
    productId: number,
    productData: ProductData,
    imageUri?: string
  ): Promise<any> {
    try {
      console.log("[ProductService] Updating product:", productId, productData);
      if (imageUri) {
        console.log("[ProductService] Adding image to form data:", {
          name: imageUri.split("/").pop(),
          type:
            imageUri.endsWith(".jpeg") || imageUri.endsWith(".jpg")
              ? "image/jpeg"
              : imageUri.endsWith(".png")
                ? "image/png"
                : "image/jpeg",
          uri: imageUri,
        });
      }

      const formData = this.createProductFormData(
        { ...productData, product_id: productId },
        imageUri
      );

      // Added timeout and more robust error handling
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

      try {
        const response = await fetch(
          `${this.apiBaseUrl}/farmer/update_product.php`,
          {
            method: "POST",
            body: formData,
            headers: {
              Accept: "application/json",
              // Note: Don't set Content-Type for multipart/form-data
            },
            signal: controller.signal,
          }
        );

        clearTimeout(timeoutId);

        const responseText = await response.text();
        console.log("[ProductService] Update product response:", responseText);

        try {
          return JSON.parse(responseText);
        } catch (error) {
          console.error("[ProductService] Error parsing response:", error);
          throw new ApiError(
            500,
            "Invalid server response: " + responseText.substring(0, 100)
          );
        }
      } catch (fetchError: unknown) {
        clearTimeout(timeoutId);
        console.error("[ProductService] Fetch error:", fetchError);

        // Check for specific network errors
        if (
          fetchError instanceof Error &&
          fetchError.message === "Network request failed"
        ) {
          console.error(
            "[ProductService] Network request failed. Possible causes:"
          );
          console.error("1. Server is unreachable");
          console.error("2. File size may be too large");
          console.error("3. Server timeout");
          console.error("4. CORS policy issue");

          throw new ApiError(
            503,
            "Network connection error. Please check your internet connection and try again."
          );
        }

        throw fetchError;
      }
    } catch (error) {
      console.error("[ProductService] Update product error:", error);
      throw error;
    }
  }

  /**
   * Delete a product
   *
   * @param productId Product ID
   * @returns Promise with the API response
   */
  async deleteProduct(productId: number): Promise<any> {
    try {
      console.log("[ProductService] Deleting product:", productId);

      const response = await fetch(
        `${this.apiBaseUrl}/farmer/delete_product.php?id=${productId}`,
        {
          method: "DELETE",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
        }
      );

      const responseText = await response.text();
      console.log("[ProductService] Delete product response:", responseText);

      try {
        return JSON.parse(responseText);
      } catch (error) {
        console.error("[ProductService] Error parsing response:", error);
        throw new ApiError(500, "Invalid server response");
      }
    } catch (error) {
      console.error("[ProductService] Delete product error:", error);
      throw error;
    }
  }

  /**
   * Get all products for a farmer
   *
   * @param farmerId Farmer ID
   * @returns Promise with the products
   */
  async getFarmerProducts(farmerId: number): Promise<any[]> {
    try {
      console.log("[ProductService] Getting products for farmer:", farmerId);

      const response = await fetch(
        `${this.apiBaseUrl}/farmer/farmer_products.php?farmer_id=${farmerId}`,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        }
      );

      const responseText = await response.text();
      console.log(
        "[ProductService] Get farmer products response:",
        responseText
      );

      try {
        const data = JSON.parse(responseText);
        return data.success ? data.products || [] : [];
      } catch (error) {
        console.error("[ProductService] Error parsing response:", error);
        return [];
      }
    } catch (error) {
      console.error("[ProductService] Get farmer products error:", error);
      return [];
    }
  }

  /**
   * Create FormData for product submission
   *
   * @param productData Product data
   * @param imageUri Local image URI (optional)
   * @returns FormData object
   */
  private createProductFormData(
    productData: ProductData,
    imageUri?: string
  ): FormData {
    const formData = new FormData();

    // Validate unit_type to prevent sending "0" as a value
    const validUnitTypes = [
      "kilogram",
      "gram",
      "piece",
      "bunch",
      "bundle",
      "liter",
      "milliliter",
      "bag",
      "sack",
      "box",
      "can",
      "bottle",
      "dozen",
      "container",
    ];

    const unitType =
      productData.unit_type && validUnitTypes.includes(productData.unit_type)
        ? productData.unit_type
        : "kilogram"; // Default to kilogram if invalid

    // Add all regular fields
    if (productData.product_id)
      formData.append("product_id", productData.product_id.toString());
    formData.append("name", productData.name);
    formData.append("description", productData.description || "");
    formData.append("price", productData.price.toString());
    formData.append("stock", productData.stock.toString());
    formData.append("farmer_id", productData.farmer_id.toString());

    // Also add user_id as the same value as farmer_id for the backend
    formData.append("user_id", productData.farmer_id.toString());

    if (productData.category_id) {
      formData.append("category_id", productData.category_id.toString());

      // Also add as part of categories array for the backend
      formData.append("categories[]", productData.category_id.toString());
    }

    // If there are additional categories in the product form, add them too
    if (productData.categories && Array.isArray(productData.categories)) {
      // Skip the first one if it matches category_id (already added)
      const additionalCategories = productData.category_id
        ? productData.categories.filter((c) => c !== productData.category_id)
        : productData.categories;

      // Add all additional categories
      additionalCategories.forEach((categoryId) => {
        formData.append("categories[]", categoryId.toString());
      });
    }

    // Add barangay and field IDs if provided
    if (productData.barangay_id) {
      formData.append("barangay_id", productData.barangay_id.toString());
    }

    if (productData.field_id) {
      formData.append("field_id", productData.field_id.toString());
    }

    // Add the validated unit_type
    formData.append("unit_type", unitType);

    // Add the target upload directory for image storage
    formData.append("upload_directory", this.imageDirectory);

    // Handle image path for existing images
    if (
      productData.image &&
      typeof productData.image === "string" &&
      !imageUri
    ) {
      formData.append("current_image", productData.image);
    }

    // Handle new image upload if URI is provided
    if (imageUri) {
      // Get the filename from the URI
      const uriParts = imageUri.split("/");
      const filename = uriParts[uriParts.length - 1];

      // Determine file type
      const match = /\.(\w+)$/.exec(filename);
      let fileType = match ? `image/${match[1]}` : "image/jpeg";

      // Default to jpeg if we can't determine the type or for common cases
      if (!match || fileType === "image/jpg") {
        fileType = "image/jpeg";
      }

      // Log the image data for debugging
      console.log("[ProductService] Processing image:", {
        uri: imageUri,
        name: filename,
        type: fileType,
      });

      try {
        // Add the image to the form data with proper format for React Native
        formData.append("image", {
          uri:
            Platform.OS === "android"
              ? imageUri
              : imageUri.replace("file://", ""),
          name: filename,
          type: fileType,
        } as any);

        console.log("[ProductService] Image added to form data");
      } catch (error) {
        console.error(
          "[ProductService] Error adding image to form data:",
          error
        );
      }
    }

    // Log the entire form data for debugging
    if (__DEV__) {
      try {
        console.log(
          "[ProductService] Form data keys:",
          Object.keys(formData).map(
            (key) => `${key}: ${(formData as any)[key]}`
          )
        );
      } catch (e) {
        console.log("[ProductService] Cannot stringify form data");
      }
    }

    return formData;
  }

  /**
   * Get a single product by ID
   *
   * @param productId Product ID
   * @returns Promise with the product details
   */
  async getProductById(productId: number): Promise<any> {
    try {
      console.log("[ProductService] Getting product:", productId);

      const response = await fetch(
        `${this.apiBaseUrl}/farmer/farmer_products.php?product_id=${productId}`,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        }
      );

      const responseText = await response.text();
      console.log("[ProductService] Get product response:", responseText);

      try {
        const data = JSON.parse(responseText);
        return data.success ? data.product : null;
      } catch (error) {
        console.error("[ProductService] Error parsing response:", error);
        return null;
      }
    } catch (error) {
      console.error("[ProductService] Get product error:", error);
      return null;
    }
  }
}

// Create and export a singleton instance
export const productService = new ProductService();
