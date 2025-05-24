import React, { useState, useEffect } from "react";
import {
  SafeAreaView,
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  TextInput,
  ScrollView,
  Image,
  Alert,
  ActivityIndicator,
  Modal,
  FlatList,
} from "react-native";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { Picker } from "@react-native-picker/picker";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { Redirect, useRouter } from "expo-router";
import IPConfig from "@/constants/IPConfig";
import { productService, ProductData } from "@/services/ProductService";

// Interface for category
interface Category {
  category_id: number;
  category_name: string;
}

// Add interfaces for barangay and field
interface Barangay {
  barangay_id: number;
  barangay_name: string;
}

interface Field {
  field_id: number;
  field_name: string;
  barangay_id: number;
  farmer_id: number;
}

// Interface for season
interface Season {
  season_id: number;
  season_name: string;
  start_month: number;
  end_month: number;
  description: string;
}

export default function AddProduct() {
  const { isAuthenticated, isFarmer, user } = useAuth();
  const router = useRouter();

  // Form state - add barangay_id and field_id
  const [form, setForm] = useState({
    name: "",
    description: "",
    price: "",
    stock: "",
    unit_type: "kilogram",
    selectedCategories: [] as number[],
    image: null as any,
    barangay_id: null as number | null,
    field_id: null as number | null,
    // Add new fields for geographical and production data
    estimated_production: "",
    production_unit: "kilogram",
    planted_area: "",
    area_unit: "hectare",
    season_id: null as number | null,
  });

  // States
  const [isLoading, setIsLoading] = useState(false);
  const [categories, setCategories] = useState<Category[]>([]);
  const [barangays, setBarangays] = useState<Barangay[]>([]);
  const [fields, setFields] = useState<Field[]>([]);
  const [seasons, setSeasons] = useState<Season[]>([]);
  const [isCategoryModalVisible, setIsCategoryModalVisible] = useState(false);
  const [isFieldModalVisible, setIsFieldModalVisible] = useState(false);
  const [selectedImageUri, setSelectedImageUri] = useState<string | null>(null);

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

  // Fetch categories on component mount
  useEffect(() => {
    fetchCategories();
    fetchBarangays();
    fetchFields();
    fetchSeasons();
  }, []);

  // Function to fetch categories
  const fetchCategories = async () => {
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
      Alert.alert(
        "Error",
        "Failed to load product categories. Please try again."
      );
    }
  };

  // Function to fetch barangays
  const fetchBarangays = async () => {
    try {
      const response = await fetch(`${IPConfig.API_BASE_URL}/barangays.php`);
      const data = await response.json();

      if (data.success) {
        setBarangays(data.barangays || []);
      } else {
        console.error("Error fetching barangays:", data.message);
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

  // Function to fetch fields
  const fetchFields = async () => {
    if (!user?.user_id) {
      console.error("No user ID available");
      return;
    }

    try {
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_fields.php?farmer_id=${user.user_id}`
      );
      const data = await response.json();

      if (data.success) {
        setFields(data.fields || []);
      } else {
        console.error("Error fetching fields:", data.message);
        Alert.alert("Error", "Failed to fetch fields. Please try again.");
      }
    } catch (error) {
      console.error("Error fetching fields:", error);
      Alert.alert(
        "Error",
        "Failed to fetch fields. Please check your connection."
      );
    }
  };

  // Function to fetch seasons
  const fetchSeasons = async () => {
    try {
      const response = await fetch(`${IPConfig.API_BASE_URL}/farmer/crop_seasons.php`);
      const data = await response.json();

      if (data.success) {
        setSeasons(data.seasons || []);
      } else {
        console.error("Error fetching seasons:", data.message);
      }
    } catch (error) {
      console.error("Error fetching seasons:", error);
      // Fallback season data
      setSeasons([
        { season_id: 1, season_name: "Dry Season", start_month: 11, end_month: 4, description: "November to April" },
        { season_id: 2, season_name: "Wet Season", start_month: 5, end_month: 10, description: "May to October" },
        { season_id: 3, season_name: "First Cropping", start_month: 11, end_month: 2, description: "November to February" },
        { season_id: 4, season_name: "Second Cropping", start_month: 3, end_month: 6, description: "March to June" },
        { season_id: 5, season_name: "Third Cropping", start_month: 7, end_month: 10, description: "July to October" },
      ]);
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

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });

    if (!result.canceled && result.assets && result.assets.length > 0) {
      const asset = result.assets[0];
      setForm({ ...form, image: asset });
      setSelectedImageUri(asset.uri); // Store the URI separately for the ProductService
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

  // Handle field selection
  const selectField = (fieldId: number | null) => {
    if (fieldId === null) {
      setForm({
        ...form,
        field_id: null,
        barangay_id: null,
      });
      return;
    }

    // Find the field to get its barangay_id
    const selectedField = fields.find((field) => field.field_id === fieldId);

    if (selectedField) {
      setForm({
        ...form,
        field_id: fieldId,
        barangay_id: selectedField.barangay_id,
      });
    }
  };

  // Submit product form - update to use FormData directly instead of ProductService
  const handleSubmit = async () => {
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

      // Create FormData object directly instead of using ProductService
      const formData = new FormData();
      formData.append("user_id", user?.user_id?.toString() || "");
      formData.append("name", form.name);
      formData.append("description", form.description || "");
      formData.append("price", form.price);
      formData.append("stock", form.stock);
      formData.append("unit_type", form.unit_type);

      // Append categories as array
      form.selectedCategories.forEach((categoryId) => {
        formData.append("categories[]", categoryId.toString());
      });

      // Append field_id and barangay_id if they exist
      if (form.field_id) {
        formData.append("field_id", form.field_id.toString());
      }
      if (form.barangay_id) {
        formData.append("barangay_id", form.barangay_id.toString());
      }

      // Handle image upload if present
      if (selectedImageUri) {
        const imageUri = selectedImageUri;
        const imageName = imageUri.split("/").pop() || "image.jpg";

        // Determine image type based on extension
        const fileExtension = imageName.split(".").pop()?.toLowerCase() || "";
        let imageType = "image/jpeg"; // Default

        if (fileExtension === "png") {
          imageType = "image/png";
        } else if (fileExtension === "gif") {
          imageType = "image/gif";
        }

        formData.append("image", {
          uri: imageUri,
          name: imageName,
          type: imageType,
        } as any);
      }

      // Log FormData for debugging
      console.log(
        "[ADD_PRODUCT] Sending form data:",
        "Form data prepared for submission"
      );

      // For debugging purposes only
      // We can log individual form values instead of accessing internal FormData properties
      console.log({
        name: form.name,
        description: form.description,
        price: form.price,
        stock: form.stock,
        categories: form.selectedCategories,
        field_id: form.field_id,
        barangay_id: form.barangay_id,
      });

      // Make direct API call to add_product.php
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/add_product.php`,
        {
          method: "POST",
          body: formData,
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      const result = await response.json();

      if (result.success) {
        Alert.alert("Success", "Product added successfully", [
          {
            text: "OK",
            onPress: () => {
              // Reset form
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
              setSelectedImageUri(null);

              // Navigate to products page
              router.replace("/farmer/products");
            },
          },
        ]);
      } else {
        Alert.alert("Error", result.message || "Failed to add product");
      }
    } catch (error) {
      console.error("Error submitting product:", error);
      Alert.alert("Error", "Failed to add product. Please try again.");
    } finally {
      setIsLoading(false);
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
        <Text style={styles.headerTitle}>Add New Product</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Product Image */}
        <TouchableOpacity style={styles.imagePicker} onPress={handlePickImage}>
          {form.image ? (
            <Image
              source={{ uri: form.image.uri }}
              style={styles.formImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.imagePickerPlaceholder}>
              <Ionicons name="camera" size={40} color="#aaa" />
              <Text style={styles.imagePickerText}>Tap to add image</Text>
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
          onChangeText={(text) => setForm({ ...form, description: text })}
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
              onChangeText={(text) => setForm({ ...form, price: text })}
              placeholder="0.00"
              keyboardType="numeric"
            />
          </View>
          <View style={styles.formColumn}>
            <Text style={styles.formLabel}>Stock *</Text>
            <TextInput
              style={styles.formInput}
              value={form.stock}
              onChangeText={(text) => setForm({ ...form, stock: text })}
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
            onValueChange={(value) => setForm({ ...form, unit_type: value })}
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

        {/* Field Selection (new section) */}
        <Text style={styles.formSectionHeader}>Product Location</Text>
        <Text style={styles.locationHelpText}>
          Assigning your product to a location helps buyers find local produce
          from specific areas and helps agricultural officers track production.
        </Text>

        <Text style={styles.formLabel}>Field (Optional)</Text>
        <View style={styles.pickerContainer}>
          <Picker
            selectedValue={form.field_id}
            style={styles.picker}
            onValueChange={(value) => selectField(value)}
          >
            <Picker.Item label="Select a field" value={null} />
            {fields.map((field) => {
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

        {/* Show auto-selected barangay info if field is selected */}
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

        {/* Categories */}
        <Text style={styles.formLabel}>Categories *</Text>
        <TouchableOpacity
          style={styles.categoryPickerButton}
          onPress={() => setIsCategoryModalVisible(true)}
        >
          <Text style={styles.categoryPickerButtonText}>
            {form.selectedCategories.length > 0
              ? `${form.selectedCategories.length} category(s) selected`
              : "Select categories"}
          </Text>
        </TouchableOpacity>

        <Modal
          visible={isCategoryModalVisible}
          animationType="slide"
          transparent={true}
        >
          <View style={styles.modalContainer}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Select Categories</Text>
                <TouchableOpacity
                  onPress={() => setIsCategoryModalVisible(false)}
                  style={styles.modalCloseIcon}
                >
                  <Ionicons name="close" size={24} color={COLORS.primary} />
                </TouchableOpacity>
              </View>

              <Text style={styles.modalSubtitle}>
                Choose one or more categories for your product
              </Text>

              <View style={styles.searchContainer}>
                <Ionicons
                  name="search"
                  size={20}
                  color="#666"
                  style={styles.searchIcon}
                />
                <TextInput
                  style={styles.searchInput}
                  placeholder="Search categories..."
                  placeholderTextColor="#999"
                  onChangeText={(text) => {
                    // Filter categories would go here in a real implementation
                  }}
                />
              </View>

              <ScrollView style={styles.modalScrollView}>
                <View style={styles.categoryGrid}>
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
                        numberOfLines={2}
                      >
                        {category.category_name}
                      </Text>
                      {form.selectedCategories.includes(
                        category.category_id
                      ) && (
                        <View style={styles.checkmarkContainer}>
                          <Ionicons
                            name="checkmark-circle"
                            size={18}
                            color="#fff"
                          />
                        </View>
                      )}
                    </TouchableOpacity>
                  ))}
                </View>
              </ScrollView>

              <View style={styles.modalFooter}>
                <Text style={styles.selectedCount}>
                  {form.selectedCategories.length} categories selected
                </Text>
                <TouchableOpacity
                  style={styles.doneButton}
                  onPress={() => setIsCategoryModalVisible(false)}
                >
                  <Text style={styles.doneButtonText}>Done</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* Submit Button */}
        <TouchableOpacity
          style={styles.submitButton}
          onPress={handleSubmit}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Text style={styles.submitButtonText}>Add Product</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
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
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  imagePicker: {
    alignItems: "center",
    marginBottom: 20,
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
    backgroundColor: "#ffffff",
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
    backgroundColor: "#ffffff",
    overflow: "hidden",
  },
  picker: {
    height: 50,
    width: "100%",
  },
  categoryPickerButton: {
    backgroundColor: "#ffffff",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 16,
    alignItems: "center",
  },
  categoryPickerButtonText: {
    fontSize: 16,
    color: "#333",
  },
  modalContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "rgba(0, 0, 0, 0.5)",
  },
  modalContent: {
    width: "80%",
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 16,
    alignItems: "center",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    width: "100%",
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "bold",
  },
  modalCloseIcon: {
    padding: 8,
  },
  modalSubtitle: {
    fontSize: 14,
    color: "#666",
    marginBottom: 16,
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f0f0f0",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginBottom: 16,
    width: "100%",
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    color: "#333",
  },
  modalScrollView: {
    width: "100%",
  },
  categoryGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
  },
  categoryOption: {
    backgroundColor: "#f0f0f0",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    marginBottom: 8,
    alignItems: "center",
    width: "48%",
    position: "relative",
  },
  selectedCategoryOption: {
    backgroundColor: COLORS.primary,
  },
  categoryOptionText: {
    color: "#666",
    textAlign: "center",
  },
  selectedCategoryText: {
    color: "#fff",
    fontWeight: "500",
  },
  checkmarkContainer: {
    position: "absolute",
    top: 8,
    right: 8,
  },
  modalFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    width: "100%",
    marginTop: 16,
  },
  selectedCount: {
    fontSize: 14,
    color: "#666",
  },
  doneButton: {
    backgroundColor: COLORS.primary,
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 8,
  },
  doneButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
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
  loadingText: {
    color: "#666",
    fontSize: 14,
  },
  // New styles for location section
  formSectionHeader: {
    fontSize: 18,
    fontWeight: "600",
    color: "#333",
    marginTop: 24,
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
});
