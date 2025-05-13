import React, { useState, useEffect, useContext } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Modal,
  TextInput,
  Alert,
  ActivityIndicator,
  ScrollView,
} from "react-native";
import { Picker } from "@react-native-picker/picker";
import { FontAwesome } from "@expo/vector-icons";
import { AuthContext } from "../../contexts/AuthContext";
import { ThemedText } from "../ThemedText";
import { getApiBaseUrlSync } from "@/services/apiConfig";
// Import NetInfo for connectivity checks
import NetInfo from "@react-native-community/netinfo";

// Use the standardized API URL from apiConfig
const API_URL = getApiBaseUrlSync();

// Check network connectivity
const checkNetworkConnectivity = async (): Promise<boolean> => {
  try {
    const networkState = await NetInfo.fetch();
    return (
      networkState.isConnected === true &&
      networkState.isInternetReachable === true
    );
  } catch (error) {
    console.error("[FarmerFields] Error checking network connectivity:", error);
    return false;
  }
};

// Utility function to build consistent API URLs
const buildApiUrl = (endpoint: string) => {
  // If the base URL ends with a slash and the endpoint starts with a slash
  // remove one of them to avoid double slashes
  if (API_URL.endsWith("/") && endpoint.startsWith("/")) {
    return `${API_URL}${endpoint.substring(1)}`;
  }
  // If neither has a slash, add one
  if (!API_URL.endsWith("/") && !endpoint.startsWith("/")) {
    return `${API_URL}/${endpoint}`;
  }
  // Otherwise just concatenate them
  return `${API_URL}${endpoint}`;
};

interface FarmerFieldsProps {
  refreshTrigger?: number;
  onFieldUpdate?: () => void;
  farmerId?: number; // Add explicit farmerId prop
}

interface BarangayType {
  barangay_id: number;
  barangay_name: string;
}

interface FieldType {
  field_id: number;
  farmer_id: number;
  barangay_id: number;
  field_name: string;
  field_size: number;
  field_type: string;
  notes: string;
  coordinates: string;
  created_at: string;
  barangay_name?: string; // For display purposes
}

const defaultField: Omit<FieldType, "field_id" | "created_at"> = {
  farmer_id: 0,
  barangay_id: 0,
  field_name: "",
  field_size: 0,
  field_type: "",
  notes: "",
  coordinates: "",
};

const FIELD_TYPES = [
  "Vegetable Farm",
  "Rice Field",
  "Fruit Orchard",
  "Mixed Crop",
  "Herb Garden",
  "Root Crop Farm",
  "Highland Farm",
  "Lowland Farm",
  "Aquaculture",
  "Livestock Area",
  "Other",
];

const FarmerFields: React.FC<FarmerFieldsProps> = ({
  refreshTrigger = 0,
  onFieldUpdate,
  farmerId, // Accept farmerId prop
}) => {
  const { user } = useContext(AuthContext);
  const [fields, setFields] = useState<FieldType[]>([]);
  const [barangays, setBarangays] = useState<BarangayType[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [modalVisible, setModalVisible] = useState<boolean>(false);
  const [currentField, setCurrentField] =
    useState<Omit<FieldType, "field_id" | "created_at">>(defaultField);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [editingFieldId, setEditingFieldId] = useState<number | null>(null);
  const [saving, setSaving] = useState<boolean>(false);
  
  // Determine which farmer ID to use - prefer prop over context
  const effectiveFarmerId = farmerId || user?.user_id;

  useEffect(() => {
    // Log which farmer ID we're using for debugging
    console.log("[FarmerFields] Using farmer ID:", effectiveFarmerId);
    
    // First validate that we have an actual farmer ID before doing anything
    if (effectiveFarmerId) {
      // Validate the user is a farmer before proceeding
      validateFarmerId(effectiveFarmerId).then(isValidFarmer => {
        if (isValidFarmer) {
          fetchFields();
          fetchBarangays();
        } else {
          console.error("[FarmerFields] Invalid farmer ID or user is not a farmer");
          setLoading(false);
        }
      });
    } else {
      console.warn("[FarmerFields] No farmer ID available, cannot fetch fields");
      setLoading(false);
    }
  }, [effectiveFarmerId, refreshTrigger]);

  // Helper function to validate farmer ID
  const validateFarmerId = async (id: number): Promise<boolean> => {
    try {
      const response = await fetch(buildApiUrl(`farmer/validate_farmer.php?user_id=${id}`));
      const data = await response.json();
      return data.success && data.is_farmer === true;
    } catch (error) {
      console.error("[FarmerFields] Error validating farmer ID:", error);
      return false;
    }
  };

  const fetchFields = async () => {
    if (!effectiveFarmerId) {
      console.error("[FarmerFields] Cannot fetch fields: No farmer ID available");
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      // Check network connectivity first
      const isConnected = await checkNetworkConnectivity();
      if (!isConnected) {
        console.warn("[FarmerFields] No network connectivity detected");
        Alert.alert(
          "Network Error",
          "No internet connection detected. Please check your connection and try again.",
          [
            { text: "Cancel", style: "cancel" },
            {
              text: "Retry",
              onPress: () => fetchFields(),
            },
          ]
        );
        setLoading(false);
        return;
      }

      const apiUrl = buildApiUrl(
        `farmer/farmer_fields.php?farmer_id=${effectiveFarmerId}`
      );
      console.log("[FarmerFields] Fetching fields from:", apiUrl);

      // Set timeout for the fetch request
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

      const response = await fetch(apiUrl, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        signal: controller.signal,
      });

      // Clear the timeout
      clearTimeout(timeoutId);

      // Debug response
      console.log("[FarmerFields] Response status:", response.status);

      // Get the response text first for debugging
      const responseText = await response.text();
      console.log(
        "[FarmerFields] Raw response:",
        responseText.length > 300
          ? responseText.substring(0, 300) + "..."
          : responseText
      );

      // Parse the JSON (after logging the text)
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (error) {
        const parseError = error as Error;
        console.error("[FarmerFields] JSON parse error:", parseError);
        throw new Error(`Failed to parse response: ${parseError.message}`);
      }

      if (data.success) {
        setFields(data.fields || []);
      } else {
        console.error("[FarmerFields] API error:", data.message);
        Alert.alert("Error", data.message || "Failed to fetch fields");
      }
    } catch (error) {
      console.error("[FarmerFields] Error fetching fields:", error);

      // Get more details about the error
      const err = error as Error;
      if (err.name === "AbortError") {
        console.error("[FarmerFields] Request timed out");
        Alert.alert(
          "Connection Timeout",
          "Request took too long to complete. Please check your connection and try again.",
          [
            { text: "Cancel", style: "cancel" },
            {
              text: "Retry",
              onPress: () => fetchFields(),
            },
          ]
        );
      } else {
        console.error("[FarmerFields] Network error details:", err.message);
        Alert.alert(
          "Network Error",
          "Failed to load farm fields. Please check your connection and make sure the server is running.",
          [
            { text: "Cancel", style: "cancel" },
            {
              text: "Retry",
              onPress: () => fetchFields(),
            },
          ]
        );
      }
    } finally {
      setLoading(false);
    }
  };

  const fetchBarangays = async () => {
    try {
      // Check network connectivity first
      const isConnected = await checkNetworkConnectivity();
      if (!isConnected) {
        console.warn(
          "[FarmerFields] No network connectivity for barangay fetch"
        );
        // Use fallback data when offline
        setBarangays([
          { barangay_id: 1, barangay_name: "Balayagmanok" },
          { barangay_id: 2, barangay_name: "Balili" },
          { barangay_id: 3, barangay_name: "Bongbong Central" },
          // These are fallbacks in case network is unavailable
        ]);
        return;
      }

      // Set timeout for the fetch request
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

      const response = await fetch(buildApiUrl("barangays.php"), {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        signal: controller.signal,
      });

      // Clear the timeout
      clearTimeout(timeoutId);

      const responseText = await response.text();
      console.log(
        "[FarmerFields] Barangays raw response:",
        responseText.length > 300
          ? responseText.substring(0, 300) + "..."
          : responseText
      );

      // Parse the JSON (after logging the text)
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (error) {
        console.error("[FarmerFields] Barangays JSON parse error:", error);
        throw new Error(`Failed to parse barangays response`);
      }

      if (data.success) {
        setBarangays(data.barangays || []);
      } else {
        console.error("[FarmerFields] Barangays API error:", data.message);
        // Use fallback data
        useFallbackBarangays();
      }
    } catch (error) {
      console.error("Error fetching barangays:", error);
      // Use fallback data if API fails
      useFallbackBarangays();
    }
  };

  // Helper function for fallback barangay data
  const useFallbackBarangays = () => {
    setBarangays([
      { barangay_id: 1, barangay_name: "Balayagmanok" },
      { barangay_id: 2, barangay_name: "Balili" },
      { barangay_id: 3, barangay_name: "Bongbong Central" },
      { barangay_id: 4, barangay_name: "Bulwang" },
      { barangay_id: 5, barangay_name: "Canaway" },
      // More local barangays
    ]);
  };

  const handleChange = (field: string, value: any) => {
    setCurrentField((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const resetForm = () => {
    setCurrentField({
      ...defaultField,
      farmer_id: effectiveFarmerId || 0,
    });
    setIsEditing(false);
    setEditingFieldId(null);
  };

  const openAddModal = () => {
    resetForm();
    setModalVisible(true);
  };

  const openEditModal = (field: FieldType) => {
    setCurrentField({
      farmer_id: field.farmer_id,
      barangay_id: field.barangay_id,
      field_name: field.field_name,
      field_size: field.field_size,
      field_type: field.field_type,
      notes: field.notes || "",
      coordinates: field.coordinates || "",
    });
    setIsEditing(true);
    setEditingFieldId(field.field_id);
    setModalVisible(true);
  };

  // Update the handleSubmit function to ensure user_id is always included
  const handleSubmit = async () => {
    if (!effectiveFarmerId) {
      Alert.alert("Error", "Farmer ID is not available. Please log in again or refresh the page.");
      return;
    }

    // Validate inputs
    if (!currentField.field_name.trim()) {
      Alert.alert("Error", "Field name is required");
      return;
    }

    if (!currentField.barangay_id) {
      Alert.alert("Error", "Please select a barangay");
      return;
    }

    if (!currentField.field_size || currentField.field_size <= 0) {
      Alert.alert("Error", "Field size must be greater than 0");
      return;
    }

    // Always ensure farmer_id is set to the effective farmer ID
    setCurrentField(prev => ({
      ...prev,
      farmer_id: effectiveFarmerId
    }));

    setSaving(true);
    try {
      const method = isEditing ? "PUT" : "POST";
      const endpoint = isEditing
        ? buildApiUrl(`farmer/farmer_fields.php?field_id=${editingFieldId}`)
        : buildApiUrl(`farmer/farmer_fields.php`);

      // Explicitly include farmer_id in the request body
      const fieldData = {
        ...currentField,
        farmer_id: effectiveFarmerId, // Ensure farmer_id is always included
      };

      console.log("[FarmerFields] Submitting field with farmer_id:", effectiveFarmerId);

      const response = await fetch(endpoint, {
        method,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(fieldData),
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert(
          "Success",
          isEditing
            ? "Field updated successfully"
            : "New field added successfully"
        );
        setModalVisible(false);
        fetchFields();

        if (onFieldUpdate) {
          onFieldUpdate();
        }
      } else {
        Alert.alert("Error", data.message || "Failed to save field");
      }
    } catch (error) {
      console.error("Error saving field:", error);
      Alert.alert("Error", "Failed to save field. Please try again later.");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = (fieldId: number) => {
    Alert.alert(
      "Confirm Delete",
      "Are you sure you want to delete this field? This action cannot be undone.",
      [
        {
          text: "Cancel",
          style: "cancel",
        },
        {
          text: "Delete",
          style: "destructive",
          onPress: () => deleteField(fieldId),
        },
      ]
    );
  };

  const deleteField = async (fieldId: number) => {
    if (!effectiveFarmerId) {
      Alert.alert("Error", "Farmer ID is not available. Cannot delete field.");
      return;
    }

    try {
      // Explicitly construct the URL with both required parameters
      const url = `${API_URL}/farmer/farmer_fields.php?field_id=${fieldId}&farmer_id=${effectiveFarmerId}`;
      
      console.log("[FarmerFields] Deleting field with URL:", url);
      
      const response = await fetch(url, {
        method: "DELETE",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
        }
      });

      // Debug the response
      const responseText = await response.text();
      console.log("[FarmerFields] Delete response:", responseText);
      
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (error) {
        console.error("[FarmerFields] Error parsing delete response:", error);
        throw new Error("Failed to parse server response");
      }

      if (data.success) {
        Alert.alert("Success", "Field deleted successfully");
        setFields(fields.filter((field) => field.field_id !== fieldId));

        if (onFieldUpdate) {
          onFieldUpdate();
        }
      } else {
        Alert.alert("Error", data.message || "Failed to delete field");
      }
    } catch (error) {
      console.error("[FarmerFields] Error deleting field:", error);
      Alert.alert("Error", "Failed to delete field. Please try again later.");
    }
  };

  const renderFieldItem = ({ item }: { item: FieldType }) => {
    const barangayName = barangays.find(
      (b) => b.barangay_id === item.barangay_id
    )?.barangay_name;

    return (
      <View style={styles.fieldCard}>
        <View style={styles.fieldHeader}>
          <ThemedText style={styles.fieldName}>{item.field_name}</ThemedText>
          <View style={styles.fieldActions}>
            <TouchableOpacity
              style={[styles.actionButton, styles.editButton]}
              onPress={() => openEditModal(item)}
            >
              <FontAwesome name="edit" size={16} color="#fff" />
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.actionButton, styles.deleteButton]}
              onPress={() => handleDelete(item.field_id)}
            >
              <FontAwesome name="trash" size={16} color="#fff" />
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.fieldInfoRow}>
          <FontAwesome
            name="map-marker"
            size={16}
            color="#555"
            style={styles.infoIcon}
          />
          <ThemedText style={styles.fieldInfoText}>
            {barangayName || "Unknown location"}
          </ThemedText>
        </View>

        <View style={styles.fieldInfoRow}>
          <FontAwesome
            name="leaf"
            size={16}
            color="#555"
            style={styles.infoIcon}
          />
          <ThemedText style={styles.fieldInfoText}>
            {item.field_type || "Not specified"}
          </ThemedText>
        </View>

        <View style={styles.fieldInfoRow}>
          <FontAwesome
            name="expand"
            size={16}
            color="#555"
            style={styles.infoIcon}
          />
          <ThemedText style={styles.fieldInfoText}>
            {item.field_size} hectares
          </ThemedText>
        </View>

        {item.notes && (
          <View style={styles.notesContainer}>
            <ThemedText style={styles.notesLabel}>Notes:</ThemedText>
            <ThemedText style={styles.notesText}>{item.notes}</ThemedText>
          </View>
        )}
      </View>
    );
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0066cc" />
        <Text style={styles.loadingText}>Loading fields...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <ThemedText style={styles.title}>My Fields</ThemedText>
        <TouchableOpacity style={styles.addButton} onPress={openAddModal}>
          <FontAwesome name="plus" size={16} color="#fff" />
          <Text style={styles.addButtonText}>Add Field</Text>
        </TouchableOpacity>
      </View>

      {fields.length === 0 ? (
        <View style={styles.emptyContainer}>
          <FontAwesome name="map-o" size={50} color="#aaa" />
          <ThemedText style={styles.emptyText}>
            You haven't added any fields yet.
          </ThemedText>
          <TouchableOpacity
            style={styles.emptyAddButton}
            onPress={openAddModal}
          >
            <Text style={styles.emptyAddButtonText}>Add Your First Field</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <View style={{ flex: 1 }}>
          <FlatList
            data={fields}
            renderItem={renderFieldItem}
            keyExtractor={(item) => item.field_id.toString()}
            contentContainerStyle={styles.listContainer}
            nestedScrollEnabled={true}
          />
        </View>
      )}

      <Modal
        visible={modalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <ThemedText style={styles.modalTitle}>
                {isEditing ? "Edit Field" : "Add New Field"}
              </ThemedText>
              <TouchableOpacity
                style={styles.closeButton}
                onPress={() => setModalVisible(false)}
              >
                <FontAwesome name="times" size={20} color="#555" />
              </TouchableOpacity>
            </View>

            {/* Use FlatList with static data instead of ScrollView to avoid nesting issues */}
            <FlatList
              data={[1]} // Just need one item to render the form
              keyExtractor={() => "form-key"}
              renderItem={() => (
                <View style={styles.formContainer}>
                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>Field Name *</ThemedText>
                    <TextInput
                      style={styles.input}
                      value={currentField.field_name}
                      onChangeText={(value) =>
                        handleChange("field_name", value)
                      }
                      placeholder="Enter field name"
                    />
                  </View>

                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>Barangay *</ThemedText>
                    <View style={styles.pickerContainer}>
                      <Picker
                        selectedValue={currentField.barangay_id}
                        style={styles.picker}
                        onValueChange={(value) =>
                          handleChange("barangay_id", value)
                        }
                      >
                        <Picker.Item label="Select barangay" value={0} />
                        {barangays.map((barangay) => (
                          <Picker.Item
                            key={barangay.barangay_id}
                            label={barangay.barangay_name}
                            value={barangay.barangay_id}
                          />
                        ))}
                      </Picker>
                    </View>
                  </View>

                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>Field Type</ThemedText>
                    <View style={styles.pickerContainer}>
                      <Picker
                        selectedValue={currentField.field_type}
                        style={styles.picker}
                        onValueChange={(value) =>
                          handleChange("field_type", value)
                        }
                      >
                        <Picker.Item label="Select field type" value="" />
                        {FIELD_TYPES.map((type, index) => (
                          <Picker.Item key={index} label={type} value={type} />
                        ))}
                      </Picker>
                    </View>
                  </View>

                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>
                      Field Size (hectares) *
                    </ThemedText>
                    <TextInput
                      style={styles.input}
                      value={currentField.field_size.toString()}
                      onChangeText={(value) =>
                        handleChange("field_size", parseFloat(value) || 0)
                      }
                      keyboardType="decimal-pad"
                      placeholder="Enter field size"
                    />
                  </View>

                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>
                      Coordinates (optional)
                    </ThemedText>
                    <TextInput
                      style={styles.input}
                      value={currentField.coordinates}
                      onChangeText={(value) =>
                        handleChange("coordinates", value)
                      }
                      placeholder="e.g., 9.2715,123.2345"
                      keyboardType="default"
                    />
                  </View>

                  <View style={styles.formGroup}>
                    <ThemedText style={styles.label}>
                      Notes (optional)
                    </ThemedText>
                    <TextInput
                      style={styles.textArea}
                      value={currentField.notes}
                      onChangeText={(value) => handleChange("notes", value)}
                      placeholder="Enter additional notes"
                      multiline
                      textAlignVertical="top"
                      numberOfLines={4}
                    />
                  </View>
                </View>
              )}
              contentContainerStyle={styles.formFlatListContainer}
            />

            <View style={styles.modalButtons}>
              <TouchableOpacity
                style={[styles.modalButton, styles.cancelModalButton]}
                onPress={() => setModalVisible(false)}
                disabled={saving}
              >
                <Text style={styles.modalButtonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.saveModalButton]}
                onPress={handleSubmit}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.modalButtonText}>
                    {isEditing ? "Update" : "Save"}
                  </Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f9f9f9",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingVertical: 15,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e1e1e1",
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
  },
  addButton: {
    flexDirection: "row",
    backgroundColor: "#4caf50",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    alignItems: "center",
  },
  addButtonText: {
    color: "#fff",
    fontWeight: "600",
    marginLeft: 5,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 20,
  },
  emptyText: {
    fontSize: 16,
    color: "#777",
    textAlign: "center",
    marginTop: 20,
    marginBottom: 30,
  },
  emptyAddButton: {
    backgroundColor: "#4caf50",
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 25,
  },
  emptyAddButtonText: {
    color: "#fff",
    fontWeight: "bold",
    fontSize: 16,
  },
  listContainer: {
    padding: 15,
    paddingBottom: 50,
  },
  fieldCard: {
    backgroundColor: "#fff",
    borderRadius: 10,
    padding: 15,
    marginBottom: 15,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  fieldHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
    paddingBottom: 10,
  },
  fieldName: {
    fontSize: 18,
    fontWeight: "bold",
  },
  fieldActions: {
    flexDirection: "row",
  },
  actionButton: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: "center",
    alignItems: "center",
    marginLeft: 8,
  },
  editButton: {
    backgroundColor: "#0066cc",
  },
  deleteButton: {
    backgroundColor: "#f44336",
  },
  fieldInfoRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  infoIcon: {
    marginRight: 10,
  },
  fieldInfoText: {
    fontSize: 15,
    flex: 1,
  },
  notesContainer: {
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: "#f0f0f0",
  },
  notesLabel: {
    fontSize: 14,
    color: "#777",
    marginBottom: 4,
  },
  notesText: {
    fontSize: 15,
    color: "#444",
  },
  modalContainer: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.5)",
    justifyContent: "center",
    alignItems: "center",
  },
  modalContent: {
    backgroundColor: "#fff",
    borderRadius: 10,
    width: "90%",
    maxHeight: "80%",
    padding: 20,
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: "bold",
  },
  closeButton: {
    padding: 5,
  },
  formContainer: {
    maxHeight: 400,
  },
  formGroup: {
    marginBottom: 15,
  },
  label: {
    fontSize: 14,
    marginBottom: 5,
    color: "#555",
  },
  input: {
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 5,
    paddingHorizontal: 10,
    paddingVertical: 8,
    fontSize: 16,
  },
  textArea: {
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 5,
    paddingHorizontal: 10,
    paddingVertical: 8,
    fontSize: 16,
    minHeight: 100,
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 5,
    overflow: "hidden",
  },
  picker: {
    height: 50,
  },
  modalButtons: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginTop: 20,
  },
  modalButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 5,
    justifyContent: "center",
    alignItems: "center",
  },
  saveModalButton: {
    backgroundColor: "#4caf50",
    marginLeft: 10,
  },
  cancelModalButton: {
    backgroundColor: "#f44336",
    marginRight: 10,
  },
  modalButtonText: {
    color: "#fff",
    fontWeight: "bold",
    fontSize: 16,
  },
  formFlatListContainer: {
    paddingBottom: 20, // Add padding at the bottom to ensure all content is visible
  },
});

export default FarmerFields;
