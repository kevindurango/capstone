import React, { useEffect, useState, useCallback } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Image,
  Modal,
  TextInput,
  Alert,
  ActivityIndicator,
  RefreshControl,
  Platform,
  ScrollView,
  SafeAreaView,
} from "react-native";
import { useRouter, Redirect } from "expo-router";
import * as ImagePicker from "expo-image-picker";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "@/contexts/AuthContext";
import { productService, ProductData } from "@/services/ProductService";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import IPConfig from "@/constants/IPConfig";
import { getImageUrl } from "@/constants/Config";
import ProductImage from "@/components/ui/ProductImage";
import { Picker } from "@react-native-picker/picker";

// Product type definition based on database schema
interface Product {
  product_id: number;
  name: string;
  description: string;
  price: number;
  status: "pending" | "approved" | "rejected";
  created_at: string;
  updated_at: string;
  image: string | null;
  stock: number;
  unit_type: string;
  categories: string; // String representation of categories for display
  category_ids?: string; // Comma-separated IDs from API
  category_objects?: Category[]; // Properly typed category objects
  barangay_id?: number;
  field_id?: number;
  // Additional fields for location tracking
  barangay_name?: string;
  field_name?: string;
  // Production metrics
  estimated_production?: number;
  production_unit?: string;
}

// Product statistics type
interface ProductStats {
  total: number;
  pending: number;
  approved: number;
  rejected: number;
}

// Category type
interface Category {
  category_id: number;
  category_name: string;
}

// Barangay type
interface Barangay {
  barangay_id: number;
  barangay_name: string;
}

// Field type
interface Field {
  field_id: number;
  field_name: string;
  barangay_id: number;
  farmer_id: number;
}

// Product form for add/edit operations
interface ProductForm {
  name: string;
  description: string;
  price: string;
  stock: string;
  unit_type: string;
  selectedCategories: number[];
  image: any;
  barangay_id: number | null;
  field_id: number | null;
  // Form validation errors
  errors?: {
    name?: string;
    price?: string;
    stock?: string;
    categories?: string;
    barangay?: string;
    field?: string;
    image?: string;
  };
}

// Validate product form fields and return errors
const validateProductForm = (
  form: ProductForm
): { isValid: boolean; errors: ProductForm["errors"] } => {
  const errors: ProductForm["errors"] = {};
  let isValid = true;

  // Validate name
  if (!form.name.trim()) {
    errors.name = "Product name is required";
    isValid = false;
  }

  // Validate price
  if (
    !form.price ||
    isNaN(parseFloat(form.price)) ||
    parseFloat(form.price) <= 0
  ) {
    errors.price = "Valid price greater than zero is required";
    isValid = false;
  }

  // Validate stock
  if (!form.stock || isNaN(parseInt(form.stock)) || parseInt(form.stock) < 0) {
    errors.stock = "Valid stock quantity is required";
    isValid = false;
  }

  // Validate categories
  if (form.selectedCategories.length === 0) {
    errors.categories = "At least one category must be selected";
    isValid = false;
  }

  return { isValid, errors };
};

/**
 * Product list item component
 */
const ProductListItem = ({
  item,
  onEdit,
  onDelete,
}: {
  item: Product;
  onEdit: (product: Product) => void;
  onDelete: (productId: number) => void;
}) => {
  // Format status for display with appropriate color
  const getStatusColor = (status: string) => {
    switch (status) {
      case "approved":
        return "#4CAF50";
      case "rejected":
        return "#F44336";
      default:
        return "#FFC107";
    }
  };

  // Format price with proper currency symbol
  const formatPrice = (price: number) => {
    return `₱${price.toFixed(2)}`;
  };

  // Check if this product has location data (formatted as part of ProductListItem component)
  const hasLocation = item.barangay_id || item.field_id;

  return (
    <View style={styles.productItemContainer}>
      <View style={styles.productImageContainer}>
        <ProductImage
          imagePath={item.image}
          style={styles.productImage}
          productId={item.product_id}
        />
      </View>

      <View style={styles.productDetails}>
        <Text style={styles.productName}>{item.name}</Text>
        <Text style={styles.productDescription} numberOfLines={2}>
          {item.description || "No description"}
        </Text>

        <View style={styles.productMetaRow}>
          <Text style={styles.productPrice}>{formatPrice(item.price)}</Text>
          <Text style={styles.productStock}>Stock: {item.stock}</Text>
        </View>

        <View style={styles.productCategoryRow}>
          {item.categories &&
            item.categories.split(",").map((category, index) => (
              <View key={index} style={styles.categoryTag}>
                <Text style={styles.categoryText}>{category}</Text>
              </View>
            ))}
          {hasLocation && (
            <View style={[styles.categoryTag, styles.locationTag]}>
              <Ionicons name="location" size={12} color={COLORS.primary} />
              <Text style={styles.locationText}>Location assigned</Text>
            </View>
          )}
        </View>

        <View style={styles.productStatusRow}>
          <View
            style={[
              styles.statusBadge,
              { backgroundColor: getStatusColor(item.status) },
            ]}
          >
            <Text style={styles.statusText}>
              {item.status.charAt(0).toUpperCase() + item.status.slice(1)}
            </Text>
          </View>
        </View>
      </View>

      <View style={styles.productActions}>
        <TouchableOpacity
          style={styles.editButton}
          onPress={() => onEdit(item)}
        >
          <Ionicons name="create-outline" size={20} color="#FFF" />
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.deleteButton}
          onPress={() => onDelete(item.product_id)}
        >
          <Ionicons name="trash-outline" size={20} color="#FFF" />
        </TouchableOpacity>
      </View>
    </View>
  );
};

// Add this new component for displaying current location information
const LocationDisplay = ({
  barangay_id,
  field_id,
  barangays,
  fields,
}: {
  barangay_id: number | null;
  field_id: number | null;
  barangays: Barangay[];
  fields: Field[];
}) => {
  const barangayName =
    barangays.find((b: Barangay) => b.barangay_id === barangay_id)
      ?.barangay_name || "Not assigned";
  const fieldName =
    fields.find((f: Field) => f.field_id === field_id)?.field_name ||
    "Not assigned";

  return (
    <View style={styles.locationDisplay}>
      <Text style={styles.locationTitle}>Current Location:</Text>
      <View style={styles.locationItemInDisplay}>
        <Text style={styles.locationLabel}>Barangay:</Text>
        <Text style={styles.locationValue}>{barangayName}</Text>
      </View>
      {field_id && (
        <View style={styles.locationItemInDisplay}>
          <Text style={styles.locationLabel}>Field:</Text>
          <Text style={styles.locationValue}>{fieldName}</Text>
        </View>
      )}
    </View>
  );
};

/**
 * Farmer Products Screen
 * Allows farmers to manage their product listings
 */
export default function FarmerProducts() {
  const { isAuthenticated, isFarmer, user } = useAuth();
  const router = useRouter();

  // State variables
  const [products, setProducts] = useState<Product[]>([]);
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [stats, setStats] = useState<ProductStats>({
    total: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
  });
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [currentProductId, setCurrentProductId] = useState<number | null>(null);
  const [selectedImageUri, setSelectedImageUri] = useState<string | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [unitTypes, setUnitTypes] = useState([
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
  ]);
  const [form, setForm] = useState<ProductForm>({
    name: "",
    description: "",
    price: "",
    stock: "",
    unit_type: "kilogram",
    selectedCategories: [],
    image: null,
    barangay_id: null,
    field_id: null,
  });
  const [barangays, setBarangays] = useState<Barangay[]>([]);
  const [fields, setFields] = useState<Field[]>([]);
  const [filteredFields, setFilteredFields] = useState<Field[]>([]);

  // Format status for display with appropriate color
  const getStatusColor = (status: string) => {
    switch (status) {
      case "approved":
        return "#4CAF50";
      case "rejected":
        return "#F44336";
      default:
        return "#FFC107";
    }
  };

  // Function to fetch products
  const fetchProducts = useCallback(async () => {
    if (!user?.user_id) return;

    try {
      setIsLoading(true);

      // Add a parameter to include location data in the response
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${user.user_id}&include_location=true`
      );
      const statsResponse = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${user.user_id}&stats=true`
      );

      const data = await response.json();
      const statsData = await statsResponse.json();

      if (data.success) {
        const products = data.products || [];

        // Pre-validate image URLs (run in parallel)
        if (products.length > 0) {
          console.log(
            `[FarmerProducts] Pre-validating images for ${products.length} products`
          );

          // Check each product's image URL
          const imageChecks = await Promise.all(
            products
              .filter((p: Product) => p.image) // Only check products with images
              .map(async (product: Product) => {
                const imageUrl = getImageUrl(product.image!);
                const result = await checkImageUrl(
                  imageUrl,
                  product.product_id
                );
                return {
                  productId: product.product_id,
                  url: imageUrl,
                  ...result,
                };
              })
          );

          console.log(
            "[FarmerProducts] Image validation results:",
            imageChecks.map(
              (r) => `Product ${r.productId}: ${r.exists ? "OK" : "FAIL"}`
            )
          );
        }

        setProducts(products);
        setFilteredProducts(products);
        console.log(
          "[Products] Products loaded with location data:",
          products.filter((p: Product) => p.barangay_id || p.field_id).length +
            " of " +
            products.length +
            " have location data"
        );
      } else {
        console.error("Error fetching products:", data.message);
      }

      if (statsData.success) {
        setStats(statsData.stats);
      }
    } catch (error) {
      console.error("Error fetching products:", error);
      Alert.alert("Error", "Could not load products. Please try again later.");
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  }, [user?.user_id]);

  // Function to fetch categories
  const fetchCategories = useCallback(async () => {
    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/market.php?categories=true`
      );
      const data = await response.json();

      if (data.success) {
        setCategories(data.categories || []);
      } else {
        console.error("Error fetching categories:", data.message);
      }
    } catch (error) {
      console.error("Error fetching categories:", error);
    }
  }, []);

  // Fetch barangays and fields
  useEffect(() => {
    if (user?.user_id) {
      fetchBarangays();
      fetchFields();
    }
  }, [user?.user_id]); // Use all farmer's fields directly - each field already has barangay info
  useEffect(() => {
    // Set filtered fields to all fields that belong to this farmer
    // This runs when fields are loaded or when user changes
    if (fields.length > 0) {
      console.log(
        `[Products] Using all ${fields.length} farmer fields for selection`
      );
      setFilteredFields(fields);
    }
  }, [fields]);

  const fetchBarangays = async () => {
    try {
      const response = await fetch(`${IPConfig.API_BASE_URL}/barangays.php`);
      const data = await response.json();

      if (data.success) {
        setBarangays(data.barangays || []);
      } else {
        console.error("Failed to fetch barangays:", data.message);
        // Fallback barangay data
        setBarangays([
          { barangay_id: 1, barangay_name: "Balayagmanok" },
          { barangay_id: 2, barangay_name: "Balili" },
          { barangay_id: 3, barangay_name: "Bongbong Central" },
          { barangay_id: 4, barangay_name: "Cambucad" },
          { barangay_id: 5, barangay_name: "Caidiocan" },
        ]);
      }
    } catch (error) {
      console.error("Error fetching barangays:", error);
      // Fallback barangay data
      setBarangays([
        { barangay_id: 1, barangay_name: "Balayagmanok" },
        { barangay_id: 2, barangay_name: "Balili" },
        { barangay_id: 3, barangay_name: "Bongbong Central" },
        { barangay_id: 4, barangay_name: "Cambucad" },
        { barangay_id: 5, barangay_name: "Caidiocan" },
      ]);
    }
  };

  // Update the fetchFields function to explicitly filter for the logged-in farmer's fields
  const fetchFields = async () => {
    if (!user?.user_id) {
      console.error("No user ID available");
      return [];
    }

    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_fields.php?farmer_id=${user.user_id}`
      );
      const data = await response.json();

      if (data.success) {
        const fields = data.fields || [];
        setFields(fields);
        return fields;
      } else {
        console.error("Error fetching fields:", data.message);
        Alert.alert("Error", "Failed to fetch fields. Please try again.");
        return [];
      }
    } catch (error) {
      console.error("Error fetching fields:", error);
      Alert.alert(
        "Error",
        "Failed to fetch fields. Please check your connection."
      );
      return [];
    }
  };

  // Load data on component mount
  useEffect(() => {
    if (user?.user_id) {
      fetchProducts();
      fetchCategories();
    }
  }, [fetchProducts, fetchCategories, user]);

  // Filter products based on search query and status filter
  useEffect(() => {
    if (products.length === 0) {
      setFilteredProducts([]);
      return;
    }

    let filtered = [...products];

    // Apply search filter
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (product) =>
          product.name.toLowerCase().includes(query) ||
          product.description.toLowerCase().includes(query)
      );
    }

    // Apply status filter
    if (statusFilter) {
      filtered = filtered.filter((product) => product.status === statusFilter);
    }

    setFilteredProducts(filtered);
  }, [searchQuery, statusFilter, products]);

  // Handle refresh
  const onRefresh = useCallback(() => {
    setRefreshing(true);
    fetchProducts();
  }, [fetchProducts]);

  // Reset form state
  const resetForm = () => {
    setForm({
      name: "",
      description: "",
      price: "",
      stock: "",
      unit_type: "kilogram",
      selectedCategories: [],
      image: null,
      barangay_id: null,
      field_id: null,
    });
    setCurrentProductId(null);
    setSelectedImageUri(null);
  };

  // Open add product modal
  const handleAddProduct = () => {
    setIsEditMode(false);
    resetForm();
    setModalVisible(true);
  };
  // Handle edit product data
  const handleEditProduct = async (product: Product) => {
    setIsEditMode(true);
    setCurrentProductId(product.product_id);

    try {
      // Parse category IDs if they exist
      const categoryIds = product.category_ids
        ? product.category_ids.split(",").map((id) => parseInt(id))
        : [];

      // Make sure unit_type is a proper string value
      const validUnitType = unitTypes.includes(product.unit_type)
        ? product.unit_type
        : "kilogram";

      // First ensure we have the latest fields data that belong to this farmer
      const currentFields = await fetchFields();
      console.log(
        `[Products] Loaded ${currentFields.length} fields for edit from farmer ID: ${user?.user_id}`
      );

      // Then fetch product's location data
      const locationInfo = await fetchProductLocation(product.product_id);
      console.log(`[Products] Product location data:`, locationInfo);

      // Set form state with product data
      setForm({
        name: product.name,
        description: product.description || "",
        price: product.price.toString(),
        stock: product.stock.toString(),
        unit_type: validUnitType,
        selectedCategories: categoryIds,
        image: product.image,
        barangay_id: locationInfo?.barangay_id || null,
        field_id: locationInfo?.field_id || null,
      });

      // Set all farmer's fields for selection
      setFields(currentFields);

      // Set selected image URI if it exists
      if (product.image) {
        setSelectedImageUri(getImageUrl(product.image));
      } else {
        setSelectedImageUri(null);
      }

      setModalVisible(true);
    } catch (error) {
      console.error("[Products] Error setting up edit form:", error);
      Alert.alert("Error", "Failed to load product data. Please try again.");
    }
  };
  // Fetch product location info (barangay and field)
  const fetchProductLocation = async (productId: number) => {
    try {
      console.log(`[Products] Fetching location for product ID: ${productId}`);

      // Use full URL for clarity and include farmer_id to ensure we only get fields belonging to this farmer
      const apiUrl = `${IPConfig.API_BASE_URL}/farmer/product_location.php?product_id=${productId}&farmer_id=${user?.user_id}`;
      console.log(`[Products] Location API URL: ${apiUrl}`);

      const response = await fetch(apiUrl);
      const responseText = await response.text();
      console.log(`[Products] Raw location response: ${responseText}`);

      try {
        const data = JSON.parse(responseText);

        if (data.success) {
          console.log(
            `[Products] Location data: barangay_id=${data.barangay_id}, field_id=${data.field_id}`
          );
          return {
            barangay_id: data.barangay_id ? parseInt(data.barangay_id) : null,
            field_id: data.field_id ? parseInt(data.field_id) : null,
          };
        } else {
          console.error(`[Products] Failed to fetch location: ${data.message}`);
        }
      } catch (parseError) {
        console.error(
          "[Products] Error parsing location response:",
          parseError
        );
      }

      return null;
    } catch (error) {
      console.error("[Products] Error fetching product location:", error);
      return null;
    }
  };

  // Handle image picking
  const handlePickImage = async () => {
    const permissionResult =
      await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (permissionResult.granted === false) {
      Alert.alert(
        "Permission Required",
        "You need to grant permission to access your photos."
      );
      return;
    }

    // Use the non-deprecated API - using string values for mediaTypes
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });

    if (!result.canceled && result.assets && result.assets.length > 0) {
      const asset = result.assets[0];
      setForm({ ...form, image: asset });
      setSelectedImageUri(asset.uri);
    }
  };

  // Toggle category selection
  const toggleCategory = (categoryId: number) => {
    const { selectedCategories } = form;

    // If already selected, remove it
    if (selectedCategories.includes(categoryId)) {
      // Update the selected categories, removing the one that was clicked
      const updatedCategories = selectedCategories.filter(
        (id) => id !== categoryId
      );

      setForm({
        ...form,
        selectedCategories: updatedCategories,
      });
    } else {
      // If this is the first category being selected, it becomes the primary
      // Otherwise, add it to the end of the list
      setForm({
        ...form,
        selectedCategories: [...selectedCategories, categoryId],
      });
    }
  };
  // Submit product form
  const handleSubmitProduct = async () => {
    try {
      // Validate form using our utility function
      const { isValid, errors } = validateProductForm(form);

      if (!isValid) {
        // Update form with errors
        setForm({ ...form, errors });

        // Show the first error in an alert
        const firstError = errors
          ? Object.values(errors).find((error) => error)
          : undefined;
        Alert.alert(
          "Validation Error",
          firstError || "Please check the form for errors"
        );
        return;
      }

      // Clear any previous errors
      setForm({ ...form, errors: {} });

      // Create FormData object
      const formData = new FormData();
      formData.append("user_id", user.user_id.toString());
      formData.append("name", form.name);
      formData.append("description", form.description || "");
      formData.append("price", form.price);
      formData.append("stock", form.stock);
      formData.append("unit_type", form.unit_type);

      // Add product_id to form data if in edit mode
      if (isEditMode && currentProductId) {
        formData.append("product_id", currentProductId.toString());
      }

      // Append categories as array
      form.selectedCategories.forEach((categoryId) => {
        formData.append("categories[]", categoryId.toString());
      });

      // Append field and barangay if selected
      if (form.field_id) {
        formData.append("field_id", form.field_id.toString());
      }
      if (form.barangay_id) {
        formData.append("barangay_id", form.barangay_id.toString());
      }

      // Handle image upload - this needs special attention to align with API
      if (form.image && typeof form.image !== "string") {
        // Only upload new image if it's not a string URL (meaning it's a new file)
        const imageUri = form.image.uri;
        const imageName = imageUri.split("/").pop() || "image.jpg";

        // Create proper mimetype based on file extension
        const fileExtension = imageName.split(".").pop()?.toLowerCase() || "";
        let imageType = "image/jpeg"; // Default

        if (fileExtension === "png") {
          imageType = "image/png";
        } else if (fileExtension === "gif") {
          imageType = "image/gif";
        }

        // For native, properly format the file object for FormData
        formData.append("image", {
          uri: imageUri,
          name: imageName,
          type: imageType,
        } as any);
      } else if (selectedImageUri && !form.image) {
        // Handle case where image is selected but not in form
        const imageUri = selectedImageUri;
        const imageName = imageUri.split("/").pop() || "image.jpg";
        const imageType = "image/jpeg"; // Default to JPEG

        formData.append("image", {
          uri: imageUri,
          name: imageName,
          type: imageType,
        } as any);
      }

      // Determine endpoint
      const endpoint =
        isEditMode && currentProductId
          ? `${IPConfig.API_BASE_URL}/farmer/update_product.php`
          : `${IPConfig.API_BASE_URL}/farmer/add_product.php`;

      console.log(`[Products] Submitting to endpoint: ${endpoint}`);
      // Log FormData contents in a type-safe way
      const formDataEntries: Record<string, any> = {};
      formData.forEach((value, key) => {
        formDataEntries[key] = value;
      });
      console.log(`[Products] Form data keys:`, formDataEntries);

      // For better debugging - add more logging
      try {
        const response = await fetch(endpoint, {
          method: "POST",
          body: formData,
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        // Add clearer response parsing
        const text = await response.text();
        console.log(
          `[Products] Raw API response: ${text.substring(0, 200)}...`
        );

        try {
          const data = JSON.parse(text);
          if (data.success) {
            Alert.alert(
              "Success",
              isEditMode
                ? "Product updated successfully"
                : "Product added successfully"
            );
            setModalVisible(false);
            fetchProducts();
          } else {
            console.error(`[Products] API returned error:`, data);
            throw new Error(data.message || "Server returned an error");
          }
        } catch (parseError) {
          console.error("[Products] Failed to parse API response:", parseError);
          throw new Error("Invalid server response");
        }
      } catch (err) {
        console.error("[Products] Error saving product:", err);

        // Show different messages based on error type
        let errorMessage = "Failed to save product";
        const error = err as Error;

        if (error.name === "AbortError") {
          errorMessage =
            "Request timed out. The server took too long to respond. Your internet connection may be slow or unstable.";
        } else if (
          error instanceof TypeError &&
          error.message === "Network request failed"
        ) {
          // Specific handling for network errors
          errorMessage =
            "Network connection failed. Please check your internet connection and try again later.";
        } else if (error instanceof Error) {
          errorMessage = error.message;
        }

        Alert.alert("Error", errorMessage, [
          { text: "OK" },
          {
            text: "Try Again",
            onPress: handleSubmitProduct,
          },
        ]);
      } finally {
        setIsLoading(false);
      }
    } catch (outerError) {
      console.error("[Products] Fatal error in form submission:", outerError);
      Alert.alert(
        "Error",
        "An unexpected error occurred. Please try again later."
      );
      setIsLoading(false);
    }
  };

  // Delete a product
  const handleDeleteProduct = (productId: number) => {
    Alert.alert(
      "Delete Product",
      "Are you sure you want to delete this product? This action cannot be undone.",
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Delete",
          style: "destructive",
          onPress: async () => {
            try {
              setIsLoading(true);

              // Use ProductService to delete product
              const result = await productService.deleteProduct(productId);

              if (result.success) {
                Alert.alert("Success", "Product deleted successfully");
                fetchProducts();
              } else {
                Alert.alert(
                  "Error",
                  result.message || "Failed to delete product"
                );
              }
            } catch (error) {
              console.error("Error deleting product:", error);
              Alert.alert(
                "Error",
                "Failed to delete product. Please try again."
              );
            } finally {
              setIsLoading(false);
            }
          },
        },
      ]
    );
  };

  // Helper function to check if image URL is accessible via HTTP request
  const checkImageUrl = async (url: string, productId: number) => {
    try {
      console.log(
        `[FarmerProducts] Checking image URL for product ${productId}: ${url}`
      );
      const response = await fetch(url, {
        method: "HEAD",
        headers: {
          "Cache-Control": "no-store",
          Pragma: "no-cache",
        },
      });

      const status = response.status;
      const contentType = response.headers.get("content-type") || "";

      console.log(
        `[FarmerProducts] Image check result for product ${productId}:`,
        {
          status,
          contentType,
          isImage: contentType.startsWith("image/"),
        }
      );

      return {
        exists: status >= 200 && status < 300,
        isImage: contentType.startsWith("image/"),
      };
    } catch (error) {
      console.error(
        `[FarmerProducts] Error checking image URL for product ${productId}:`,
        error
      );
      return { exists: false, isImage: false };
    }
  };

  // Check authentication
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect consumers to consumer dashboard
  if (!isFarmer) {
    return <Redirect href="/consumer/dashboard" />;
  }

  // Render product item
  const renderProductItem = ({ item }: { item: Product }) => (
    <ProductListItem
      item={item}
      onEdit={handleEditProduct}
      onDelete={handleDeleteProduct}
    />
  );

  const handleChange = (field: keyof ProductForm, value: any) => {
    setForm((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.replace("/farmer/dashboard")}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>My Products</Text>
        <TouchableOpacity style={styles.addButton} onPress={handleAddProduct}>
          <Ionicons name="add" size={24} color={COLORS.light} />
        </TouchableOpacity>
      </View>
      {/* Stats Section */}
      <View style={styles.statsContainer}>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.total}</Text>
          <Text style={styles.statLabel}>Total</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.approved}</Text>
          <Text style={styles.statLabel}>Approved</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.pending}</Text>
          <Text style={styles.statLabel}>Pending</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statNumber}>{stats.rejected}</Text>
          <Text style={styles.statLabel}>Rejected</Text>
        </View>
      </View>
      {/* Product List with Search and Filter as Header Component */}
      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading products...</Text>
        </View>
      ) : filteredProducts.length > 0 ? (
        <FlatList
          data={filteredProducts}
          renderItem={renderProductItem}
          keyExtractor={(item) => item.product_id.toString()}
          contentContainerStyle={styles.productList}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
          ListHeaderComponent={
            <View style={styles.searchFilterContainer}>
              <View style={styles.searchContainer}>
                <Ionicons
                  name="search"
                  size={18}
                  color="#666"
                  style={styles.searchIcon}
                />
                <TextInput
                  style={styles.searchInput}
                  placeholder="Search products..."
                  value={searchQuery}
                  onChangeText={setSearchQuery}
                />
                {searchQuery ? (
                  <TouchableOpacity onPress={() => setSearchQuery("")}>
                    <Ionicons name="close-circle" size={18} color="#666" />
                  </TouchableOpacity>
                ) : null}
              </View>

              <View style={styles.filterContainer}>
                <Text style={styles.filterLabel}>Filter: </Text>
                <View style={styles.filterButtons}>
                  <TouchableOpacity
                    style={[
                      styles.filterButton,
                      statusFilter === null && styles.activeFilterButton,
                    ]}
                    onPress={() => setStatusFilter(null)}
                  >
                    <Text
                      style={[
                        styles.filterButtonText,
                        statusFilter === null && styles.activeFilterText,
                      ]}
                    >
                      All
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[
                      styles.filterButton,
                      statusFilter === "approved" && styles.activeFilterButton,
                    ]}
                    onPress={() => setStatusFilter("approved")}
                  >
                    <Text
                      style={[
                        styles.filterButtonText,
                        statusFilter === "approved" && styles.activeFilterText,
                      ]}
                    >
                      Approved
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[
                      styles.filterButton,
                      statusFilter === "pending" && styles.activeFilterButton,
                    ]}
                    onPress={() => setStatusFilter("pending")}
                  >
                    <Text
                      style={[
                        styles.filterButtonText,
                        statusFilter === "pending" && styles.activeFilterText,
                      ]}
                    >
                      Pending
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[
                      styles.filterButton,
                      statusFilter === "rejected" && styles.activeFilterButton,
                    ]}
                    onPress={() => setStatusFilter("rejected")}
                  >
                    <Text
                      style={[
                        styles.filterButtonText,
                        statusFilter === "rejected" && styles.activeFilterText,
                      ]}
                    >
                      Rejected
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          }
        />
      ) : (
        <View style={styles.emptyContainer}>
          <Ionicons name="basket-outline" size={64} color={COLORS.muted} />
          <Text style={styles.emptyText}>No products found</Text>
          <Text style={styles.emptySubText}>
            {searchQuery || statusFilter
              ? "Try adjusting your search or filters"
              : "Tap the + button to add your first product"}
          </Text>
        </View>
      )}
      {/* Add/Edit Product Modal */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={modalVisible}
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {isEditMode ? "Edit Product" : "Add New Product"}
              </Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={24} color="#333" />
              </TouchableOpacity>
            </View>

            <FlatList
              data={[1]} // Just need one item for rendering all content
              renderItem={() => (
                <View style={styles.modalFormContent}>
                  {/* Product Image */}
                  <TouchableOpacity
                    style={styles.imagePicker}
                    onPress={handlePickImage}
                  >
                    {form.image ? (
                      <View style={styles.imageContainer}>
                        {typeof form.image === "string" ? (
                          <ProductImage
                            imagePath={form.image}
                            style={styles.formImage}
                            resizeMode="cover"
                            productId={currentProductId || undefined}
                          />
                        ) : (
                          <Image
                            source={{ uri: form.image.uri }}
                            style={styles.formImage}
                            resizeMode="cover"
                          />
                        )}
                        {/* Image edit overlay */}
                        <View style={styles.imageOverlay}>
                          <Ionicons name="camera" size={24} color="#fff" />
                          <Text style={styles.imageOverlayText}>Change</Text>
                        </View>
                      </View>
                    ) : (
                      <View style={styles.imagePickerPlaceholder}>
                        <Ionicons name="camera" size={40} color="#aaa" />
                        <Text style={styles.imagePickerText}>
                          Tap to add image
                        </Text>
                      </View>
                    )}
                  </TouchableOpacity>
                  {isEditMode && currentProductId && (
                    <View style={styles.productStatusInfo}>
                      <Text style={styles.productIdText}>
                        Product ID: {currentProductId}
                      </Text>
                      {products.find((p) => p.product_id === currentProductId)
                        ?.status && (
                        <View
                          style={[
                            styles.statusBadgeSmall,
                            {
                              backgroundColor: getStatusColor(
                                products.find(
                                  (p) => p.product_id === currentProductId
                                )?.status || "pending"
                              ),
                            },
                          ]}
                        >
                          <Text style={styles.statusTextSmall}>
                            {(
                              products.find(
                                (p) => p.product_id === currentProductId
                              )?.status || ""
                            )
                              .charAt(0)
                              .toUpperCase() +
                              (
                                products.find(
                                  (p) => p.product_id === currentProductId
                                )?.status || ""
                              ).slice(1)}
                          </Text>
                        </View>
                      )}
                    </View>
                  )}
                  {/* Product Name */}
                  <Text style={styles.formLabel}>Product Name *</Text>
                  <TextInput
                    style={styles.formInput}
                    value={form.name}
                    onChangeText={(text) => setForm({ ...form, name: text })}
                    placeholder="Enter product name"
                  />
                  {/* Product Description */}
                  <Text style={styles.formLabel}>Description</Text>
                  <TextInput
                    style={[styles.formInput, styles.textArea]}
                    value={form.description}
                    onChangeText={(text) =>
                      setForm({ ...form, description: text })
                    }
                    placeholder="Enter product description"
                    multiline
                    numberOfLines={4}
                  />
                  {/* Price and Stock */}
                  <View style={styles.formRow}>
                    <View style={styles.formColumn}>
                      <Text style={styles.formLabel}>Price (₱) *</Text>
                      <TextInput
                        style={styles.formInput}
                        value={form.price}
                        onChangeText={(text) =>
                          setForm({ ...form, price: text })
                        }
                        placeholder="0.00"
                        keyboardType="numeric"
                      />
                    </View>
                    <View style={styles.formColumn}>
                      <Text style={styles.formLabel}>Stock *</Text>
                      <TextInput
                        style={styles.formInput}
                        value={form.stock}
                        onChangeText={(text) =>
                          setForm({ ...form, stock: text })
                        }
                        placeholder="0"
                        keyboardType="numeric"
                      />
                    </View>
                  </View>
                  {/* Unit Type */}
                  <Text style={styles.formLabel}>Unit Type *</Text>
                  <View style={styles.pickerContainer}>
                    <Picker
                      selectedValue={form.unit_type}
                      onValueChange={(value) =>
                        setForm({ ...form, unit_type: value })
                      }
                      style={styles.picker}
                    >
                      {unitTypes.map((unit) => (
                        <Picker.Item
                          key={unit}
                          label={unit.charAt(0).toUpperCase() + unit.slice(1)}
                          value={unit}
                        />
                      ))}
                    </Picker>
                  </View>
                  {/* Categories */}
                  <Text style={styles.formLabel}>Categories *</Text>
                  <View style={styles.categoriesContainer}>
                    {categories.map((category) => (
                      <TouchableOpacity
                        key={category.category_id}
                        style={[
                          styles.categoryOption,
                          form.selectedCategories.includes(
                            category.category_id
                          ) && styles.selectedCategoryOption,
                        ]}
                        onPress={() => toggleCategory(category.category_id)}
                      >
                        <Text
                          style={[
                            styles.categoryOptionText,
                            form.selectedCategories.includes(
                              category.category_id
                            ) && styles.selectedCategoryText,
                          ]}
                        >
                          {category.category_name}{" "}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                  {/* Product Location Section */}
                  <Text style={styles.formSectionHeader}>Product Location</Text>
                  <Text style={styles.locationHelpText}>
                    Assigning your product to a location helps buyers find local
                    produce from specific areas and helps agricultural officers
                    track production.
                  </Text>
                  {isEditMode && (
                    <LocationDisplay
                      barangay_id={form.barangay_id}
                      field_id={form.field_id}
                      barangays={barangays}
                      fields={fields}
                    />
                  )}
                  {/* Field Selection with enhanced UX */}
                  <Text style={styles.formLabel}>Field</Text>
                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>
                      Field (Optional)
                    </ThemedText>
                    <View style={styles.pickerContainer}>
                      <Picker
                        selectedValue={form.field_id}
                        style={styles.picker}
                        onValueChange={(value) => {
                          // Find the selected field to get its barangay_id
                          const selectedField = fields.find(
                            (field) => field.field_id === value
                          );

                          // Set the form with updated field and barangay
                          setForm({
                            ...form,
                            field_id: value,
                            // Set the barangay_id automatically based on the selected field
                            barangay_id: selectedField
                              ? selectedField.barangay_id
                              : null,
                          });

                          // Show feedback to the user about the barangay auto-selection
                          if (selectedField) {
                            const barangay = barangays.find(
                              (b) => b.barangay_id === selectedField.barangay_id
                            );
                            if (barangay) {
                              // Toast notification could be added here in the future
                              console.log(
                                `Automatically selected Barangay: ${barangay.barangay_name}`
                              );
                            }
                          }
                        }}
                      >
                        <Picker.Item label="Select a field" value={null} />
                        {fields
                          .filter((field) => field.farmer_id === user?.user_id)
                          .map((field) => {
                            // Find the barangay name for this field to display in the picker
                            const fieldBarangay = barangays.find(
                              (b) => b.barangay_id === field.barangay_id
                            );

                            // Create a more informative label with both field name and barangay
                            const label = fieldBarangay
                              ? `${field.field_name} (${fieldBarangay.barangay_name})`
                              : field.field_name;

                            return (
                              <Picker.Item
                                key={field.field_id}
                                label={label}
                                value={field.field_id}
                              />
                            );
                          })}
                      </Picker>
                    </View>
                    {form.errors?.field && (
                      <Text style={styles.errorText}>{form.errors.field}</Text>
                    )}
                  </View>
                  {/* Display automatically selected barangay if field is chosen */}
                  {form.field_id && form.barangay_id && (
                    <View style={styles.autoSelectedBarangay}>
                      <Ionicons
                        name="information-circle"
                        size={16}
                        color={COLORS.primary}
                      />
                      <Text style={styles.autoSelectedText}>
                        Barangay automatically set based on field selection
                      </Text>
                    </View>
                  )}
                  {/* Submit Button */}
                  <TouchableOpacity
                    style={styles.submitButton}
                    onPress={handleSubmitProduct}
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <ActivityIndicator size="small" color="#fff" />
                    ) : (
                      <Text style={styles.submitButtonText}>
                        {isEditMode ? "Update Product" : "Add Product"}
                      </Text>
                    )}
                  </TouchableOpacity>
                </View>
              )}
              keyExtractor={() => "form"}
              contentContainerStyle={styles.modalScroll}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: COLORS.primary,
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: "bold",
    color: COLORS.light,
  },
  backButton: {
    padding: 8,
  },
  addButton: {
    padding: 8,
  },
  statsContainer: {
    flexDirection: "row",
    justifyContent: "space-around",
    paddingVertical: 16,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  statCard: {
    alignItems: "center",
    paddingHorizontal: 12,
  },
  statNumber: {
    fontSize: 22,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  statLabel: {
    fontSize: 12,
    color: "#666",
    marginTop: 4,
  },
  searchFilterContainer: {
    padding: 16,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f0f0f0",
    borderRadius: 8,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    height: 40,
    fontSize: 16,
  },
  filterContainer: {
    flexDirection: "row",
    alignItems: "center",
  },
  filterLabel: {
    fontSize: 14,
    color: "#666",
    marginRight: 8,
  },
  filterButton: {
    paddingHorizontal: 16,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: "#f0f0f0",
    marginRight: 8,
  },
  activeFilterButton: {
    backgroundColor: COLORS.primary,
  },
  filterButtonText: {
    color: "#666",
  },
  activeFilterText: {
    color: "#fff",
    fontWeight: "bold",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  loadingText: {
    marginTop: 10,
    color: "#666",
  },
  productList: {
    padding: 16,
  },
  productItemContainer: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderRadius: 8,
    marginBottom: 16,
    overflow: "hidden",
    elevation: 2,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  productImageContainer: {
    width: 100,
    height: 100,
  },
  productImage: {
    width: "100%",
    height: "100%",
  },
  placeholderImage: {
    width: "100%",
    height: "100%",
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
  },
  productDetails: {
    flex: 1,
    padding: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 4,
  },
  productDescription: {
    fontSize: 14,
    color: "#666",
    marginBottom: 8,
  },
  productMetaRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  productPrice: {
    fontSize: 14,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  productStock: {
    fontSize: 14,
    color: "#666",
  },
  productCategoryRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginBottom: 8,
  },
  categoryTag: {
    backgroundColor: "#e8f5e9",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    marginRight: 6,
    marginBottom: 6,
  },
  categoryText: {
    fontSize: 12,
    color: COLORS.primary,
  },
  productStatusRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  statusText: {
    fontSize: 12,
    color: "#fff",
    fontWeight: "bold",
  },
  productActions: {
    flexDirection: "row",
    padding: 8,
    justifyContent: "flex-end",
    alignItems: "center",
  },
  editButton: {
    padding: 8,
    backgroundColor: "#2196F3", // Blue background color
    borderRadius: 4,
    marginRight: 6,
    ...Platform.select({
      android: { elevation: 2 },
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.2,
        shadowRadius: 1,
      },
    }),
  },
  deleteButton: {
    padding: 8,
    backgroundColor: "#F44336", // Red background color
    borderRadius: 4,
    ...Platform.select({
      android: { elevation: 2 },
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.2,
        shadowRadius: 1,
      },
    }),
  },
  emptyContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#666",
    marginTop: 16,
  },
  emptySubText: {
    fontSize: 14,
    color: "#999",
    textAlign: "center",
    marginTop: 8,
  },
  modalOverlay: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "rgba(0, 0, 0, 0.5)",
  },
  modalContent: {
    width: "90%",
    maxHeight: "80%",
    backgroundColor: "#fff",
    borderRadius: 8,
    overflow: "hidden",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#333",
  },
  modalScroll: {
    padding: 16,
  },
  imagePicker: {
    alignItems: "center",
    marginBottom: 16,
  },
  formImage: {
    width: 200,
    height: 200,
    borderRadius: 8,
    backgroundColor: "#f0f0f0",
  },
  imagePickerPlaceholder: {
    width: 200,
    height: 200,
    borderRadius: 8,
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#ddd",
    borderStyle: "dashed",
  },
  imagePickerText: {
    marginTop: 8,
    color: "#999",
  },
  infoText: {
    color: "#666",
    fontStyle: "italic",
    marginTop: 4,
    marginBottom: 16,
  },
  formLabel: {
    fontSize: 16,
    color: "#333",
    marginBottom: 6,
    fontWeight: "500",
  },
  formInput: {
    backgroundColor: "#f8f8f8",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 16,
    fontSize: 16,
  },
  textArea: {
    height: 100,
    textAlignVertical: "top",
  },
  formRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  formColumn: {
    width: "48%",
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    marginBottom: 16,
    overflow: "hidden",
  },
  picker: {
    height: 50,
    width: "100%",
  },
  categoriesContainer: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginBottom: 16,
  },
  categoryOption: {
    backgroundColor: "#f0f0f0",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    marginRight: 8,
    marginBottom: 8,
  },
  selectedCategoryOption: {
    backgroundColor: COLORS.primary,
  },
  categoryOptionText: {
    color: "#666",
  },
  selectedCategoryText: {
    color: "#fff",
    fontWeight: "500",
  },
  helperText: {
    fontSize: 12,
    color: "#666",
    fontStyle: "italic",
    marginTop: 4,
    marginBottom: 16,
  },
  submitButton: {
    backgroundColor: COLORS.primary,
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: "center",
    marginTop: 16,
    marginBottom: 32,
  },
  submitButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  filterButtons: {
    flexDirection: "row",
    flexWrap: "wrap",
  },
  errorText: {
    color: "#f44336",
    fontSize: 12,
    marginTop: -12,
    marginBottom: 8,
  },
  inputError: {
    borderColor: "#f44336",
  },
  autoSelectedBarangay: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#e1f5fe",
    padding: 8,
    borderRadius: 6,
    marginBottom: 16,
  },
  autoSelectedText: {
    color: COLORS.primary,
    fontSize: 12,
    marginLeft: 4,
  },
  locationDisplayContainer: {
    backgroundColor: "#f2f9ff",
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  locationDisplayText: {
    color: "#666",
    fontStyle: "italic",
  },
  locationItem: {
    flexDirection: "row",
    alignItems: "center",
    marginVertical: 4,
  },
  locationName: {
    marginLeft: 6,
    fontSize: 14,
  },
  modalFormContent: {
    padding: 16,
    paddingBottom: 32,
  },
  imageContainer: {
    position: "relative",
  },
  imageOverlay: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: "rgba(0, 0, 0, 0.5)",
    justifyContent: "center",
    alignItems: "center",
    borderRadius: 8,
  },
  imageOverlayText: {
    color: "#fff",
    fontSize: 16,
    marginTop: 4,
  },
  productStatusInfo: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#eaeaea",
  },
  productIdText: {
    fontSize: 14,
    color: "#666",
    fontStyle: "italic",
  },
  statusBadgeSmall: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 4,
  },
  statusTextSmall: {
    fontSize: 12,
    color: "#fff",
    fontWeight: "bold",
  },
  dropdownContainer: {
    backgroundColor: "#f0f0f0",
    borderRadius: 5,
    marginBottom: 15,
    borderWidth: 1,
    borderColor: "#e0e0e0",
  },
  formSectionHeader: {
    fontSize: 18,
    fontWeight: "600",
    color: "#333",
    marginTop: 16,
    marginBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#e0e0e0",
    paddingBottom: 8,
  },
  locationHelpText: {
    fontSize: 14,
    color: "#666",
    marginBottom: 12,
    lineHeight: 20,
  },
  locationDisplay: {
    backgroundColor: "#f2f9ff",
    padding: 12,
    borderRadius: 8,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    marginBottom: 16,
  },
  locationTitle: {
    fontWeight: "bold",
    marginBottom: 4,
    color: "#333",
  },
  locationItemInDisplay: {
    flexDirection: "row",
    marginVertical: 2,
  },
  locationLabel: {
    color: "#555",
    width: 70,
  },
  locationValue: {
    fontWeight: "500",
    color: "#333",
    flex: 1,
  },
  addFieldButton: {
    paddingVertical: 8,
    alignItems: "center",
    marginBottom: 16,
  },
  addFieldButtonText: {
    color: COLORS.primary,
    fontWeight: "500",
  },
  locationTag: {
    backgroundColor: "#e1f5fe", // Light blue background for location tag
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    marginRight: 6,
    marginBottom: 6,
  },
  locationText: {
    fontSize: 12,
    color: COLORS.primary,
    marginLeft: 2,
  },
  formGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 6,
  },
  formGroupContent: {
    flexDirection: "row",
    alignItems: "center",
  },
  formGroupInput: {
    flex: 1,
    backgroundColor: "#f8f8f8",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    padding: 12,
  },
  formGroupPicker: {
    flexDirection: "row",
    alignItems: "center",
    marginLeft: 12,
  },
  formGroupPickerItem: {
    padding: 8,
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
  },
  formGroupPickerItemSelected: {
    backgroundColor: COLORS.primary,
  },
  formGroupPickerItemText: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
  },
});
