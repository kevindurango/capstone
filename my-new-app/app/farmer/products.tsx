import React, { useEffect, useState, useCallback } from "react";
import {
  SafeAreaView,
  StyleSheet,
  Text,
  View,
  FlatList,
  TouchableOpacity,
  Image,
  TextInput,
  ActivityIndicator,
  Modal,
  ScrollView,
  Alert,
  RefreshControl,
} from "react-native";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { Redirect, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import IPConfig from "@/constants/IPConfig";
import { ThemedText } from "@/components/ThemedText";
import { Picker } from "@react-native-picker/picker";
import { productService, ProductData } from "@/services/ProductService";
import { getImageUrl } from "@/constants/Config";

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
  categories: string;
  category_ids?: string;
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

// Product form for add/edit operations
interface ProductForm {
  name: string;
  description: string;
  price: string;
  stock: string;
  unit_type: string;
  selectedCategories: number[];
  image: any;
}

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

  // Modal states
  const [modalVisible, setModalVisible] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [currentProductId, setCurrentProductId] = useState<number | null>(null);
  const [selectedImageUri, setSelectedImageUri] = useState<string | null>(null);

  // Form state
  const [form, setForm] = useState<ProductForm>({
    name: "",
    description: "",
    price: "",
    stock: "",
    unit_type: "kilogram",
    selectedCategories: [],
    image: null,
  });

  // Units available for products
  const unitTypes = [
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

  // Function to fetch products
  const fetchProducts = useCallback(async () => {
    if (!user?.user_id) return;

    try {
      setIsLoading(true);
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${user.user_id}`
      );
      const statsResponse = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_products.php?user_id=${user.user_id}&stats=true`
      );

      const data = await response.json();
      const statsData = await statsResponse.json();

      if (data.success) {
        setProducts(data.products || []);
        setFilteredProducts(data.products || []);
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
  const handleEditProduct = (product: Product) => {
    setIsEditMode(true);
    setCurrentProductId(product.product_id);

    // Parse category IDs if they exist
    const categoryIds = product.category_ids
      ? product.category_ids.split(",").map((id) => parseInt(id))
      : [];

    // Make sure unit_type is a proper string value, not just "0"
    const unit =
      product.unit_type && unitTypes.includes(product.unit_type)
        ? product.unit_type
        : "kilogram";

    setForm({
      name: product.name,
      description: product.description,
      price: product.price.toString(),
      stock: product.stock.toString(),
      unit_type: unit,
      selectedCategories: categoryIds,
      image: product.image,
    });

    setModalVisible(true);
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

    // Update to use the non-deprecated API
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
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

    if (selectedCategories.includes(categoryId)) {
      setForm({
        ...form,
        selectedCategories: selectedCategories.filter(
          (id) => id !== categoryId
        ),
      });
    } else {
      setForm({
        ...form,
        selectedCategories: [...selectedCategories, categoryId],
      });
    }
  };

  // Submit product form
  const handleSubmitProduct = async () => {
    // Basic validation
    if (!form.name.trim()) {
      Alert.alert("Error", "Please enter a product name");
      return;
    }

    if (
      !form.price.trim() ||
      isNaN(parseFloat(form.price)) ||
      parseFloat(form.price) <= 0
    ) {
      Alert.alert("Error", "Please enter a valid price");
      return;
    }

    if (
      !form.stock.trim() ||
      isNaN(parseInt(form.stock)) ||
      parseInt(form.stock) < 0
    ) {
      Alert.alert("Error", "Please enter a valid stock quantity");
      return;
    }

    if (form.selectedCategories.length === 0) {
      Alert.alert("Error", "Please select at least one category");
      return;
    }

    try {
      setIsLoading(true);

      // Create product data for ProductService
      const productData: ProductData = {
        name: form.name,
        description: form.description,
        price: parseFloat(form.price),
        stock: parseInt(form.stock),
        farmer_id: user?.user_id || 0,
        category_id: form.selectedCategories[0], // For now using first category
        unit_type: form.unit_type,
      };

      // If editing, add current image path
      if (isEditMode && typeof form.image === "string") {
        productData.image = form.image;
      }

      let result;

      // Use the ProductService to add/update the product
      if (isEditMode && currentProductId) {
        result = await productService.updateProduct(
          currentProductId,
          productData,
          selectedImageUri || undefined
        );
      } else {
        result = await productService.addProduct(
          productData,
          selectedImageUri || undefined
        );
      }

      if (result.success) {
        Alert.alert(
          "Success",
          isEditMode
            ? "Product updated successfully"
            : "Product added successfully"
        );
        setModalVisible(false);
        fetchProducts();
      } else {
        Alert.alert(
          "Error",
          result.message || "Failed to process your request"
        );
      }
    } catch (error) {
      console.error("Error submitting product:", error);
      Alert.alert("Error", "Failed to process your request. Please try again.");
    } finally {
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

  // Check authentication
  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  // Redirect consumers to consumer dashboard
  if (!isFarmer) {
    return <Redirect href="/consumer/dashboard" />;
  }

  // Render product item
  const renderProductItem = ({ item }: { item: Product }) => {
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

    return (
      <View style={styles.productCard}>
        <View style={styles.productImageContainer}>
          {item.image ? (
            <Image
              source={{
                uri: getImageUrl(item.image), // Use getImageUrl helper function
              }}
              style={styles.productImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.placeholderImage}>
              <Ionicons name="image-outline" size={40} color="#aaa" />
            </View>
          )}
        </View>

        <View style={styles.productDetails}>
          <Text style={styles.productName}>{item.name}</Text>
          <Text style={styles.productDescription} numberOfLines={2}>
            {item.description || "No description"}
          </Text>

          <View style={styles.productMetaRow}>
            <Text style={styles.productPrice}>
              ₱{item.price.toFixed(2)} / {item.unit_type}
            </Text>
            <Text style={styles.productStock}>Stock: {item.stock}</Text>
          </View>

          <View style={styles.productCategoryRow}>
            {item.categories &&
              item.categories.split(",").map((category, index) => (
                <View key={index} style={styles.categoryTag}>
                  <Text style={styles.categoryText}>{category}</Text>
                </View>
              ))}
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

            <View style={styles.productActions}>
              <TouchableOpacity
                style={[styles.actionButton, styles.editButton]}
                onPress={() => handleEditProduct(item)}
              >
                <Ionicons name="create-outline" size={18} color="#fff" />
                <Text style={styles.actionButtonText}>Edit</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.actionButton, styles.deleteButton]}
                onPress={() => handleDeleteProduct(item.product_id)}
              >
                <Ionicons name="trash-outline" size={18} color="#fff" />
                <Text style={styles.actionButtonText}>Delete</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
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
                      <Image
                        source={{
                          uri:
                            typeof form.image === "string"
                              ? getImageUrl(form.image)
                              : form.image.uri,
                        }}
                        style={styles.formImage}
                        resizeMode="cover"
                      />
                    ) : (
                      <View style={styles.imagePickerPlaceholder}>
                        <Ionicons name="camera" size={40} color="#aaa" />
                        <Text style={styles.imagePickerText}>
                          Tap to add image
                        </Text>
                      </View>
                    )}
                  </TouchableOpacity>

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
                          {category.category_name}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>

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
  productCard: {
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
  },
  actionButton: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
    marginLeft: 8,
  },
  editButton: {
    backgroundColor: "#2196f3",
  },
  deleteButton: {
    backgroundColor: "#f44336",
  },
  actionButtonText: {
    color: "#fff",
    fontSize: 12,
    marginLeft: 4,
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
  modalFormContent: {
    padding: 16,
    paddingBottom: 32,
  },
});
