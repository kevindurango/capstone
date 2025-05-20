import React, { useState, useEffect, useCallback } from "react";
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
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { Redirect, useRouter } from "expo-router";
import IPConfig from "@/constants/IPConfig";
import { Picker } from "@react-native-picker/picker";
import * as ImagePicker from "expo-image-picker";
import { getImageUrl } from "@/constants/Config";

// Farm details type
interface FarmDetails {
  detail_id: number;
  user_id: number;
  farm_name: string;
  farm_type: string;
  certifications: string;
  crop_varieties: string;
  machinery_used: string;
  farm_size: number;
  income: number | null;
  farm_location: string;
  barangay_id: number | null;
  farm_image: string | null;
}

// Farm profile form
interface FarmForm {
  farm_name: string;
  farm_type: string;
  certifications: string;
  crop_varieties: string;
  machinery_used: string;
  farm_size: string;
  income: string;
  farm_location: string;
  barangay_id: string;
  farm_image: any;
}

// Barangay type
interface Barangay {
  barangay_id: number;
  name: string;
}

// Farm types options
const farmTypes = [
  "Rice Farm",
  "Vegetable Farm",
  "Fruit Orchard",
  "Mixed Crop Farm",
  "Livestock Farm",
  "Poultry Farm",
  "Aquaculture Farm",
  "Organic Farm",
  "Other",
];

export default function FarmProfile() {
  const { isAuthenticated, isFarmer, user } = useAuth();
  const router = useRouter();
  // State variables
  const [farmDetails, setFarmDetails] = useState<FarmDetails | null>(null);
  const [barangays, setBarangays] = useState<Barangay[]>([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [activeSection, setActiveSection] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false); // Add state for refresh operation
  const [editableSection, setEditableSection] = useState<string | null>(null); // New state for section-specific editing

  // Form state
  const [form, setForm] = useState<FarmForm>({
    farm_name: "",
    farm_type: farmTypes[0],
    certifications: "",
    crop_varieties: "",
    machinery_used: "",
    farm_size: "0",
    income: "0",
    farm_location: "",
    barangay_id: "",
    farm_image: null,
  });

  // Fetch farm details
  const fetchFarmDetails = useCallback(async () => {
    if (!user?.user_id) return;

    try {
      setLoading(true);
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_details.php?user_id=${user.user_id}`
      );
      const data = await response.json();

      if (data.success && data.farm_details) {
        setFarmDetails(data.farm_details);

        // Initialize form with fetched data
        setForm({
          farm_name: data.farm_details.farm_name || "",
          farm_type: data.farm_details.farm_type || farmTypes[0],
          certifications: data.farm_details.certifications || "",
          crop_varieties: data.farm_details.crop_varieties || "",
          machinery_used: data.farm_details.machinery_used || "",
          farm_size: data.farm_details.farm_size?.toString() || "0",
          income: data.farm_details.income?.toString() || "0",
          farm_location: data.farm_details.farm_location || "",
          barangay_id: data.farm_details.barangay_id?.toString() || "",
          farm_image: data.farm_details.farm_image || null,
        });
      } else {
        console.log("No farm details found, creating new profile");
      }
    } catch (error) {
      console.error("Error fetching farm details:", error);
      Alert.alert("Error", "Could not load farm details. Please try again.");
    } finally {
      setLoading(false);
    }
  }, [user?.user_id]);

  // Add refresh function for farm details
  const refreshFarmDetails = useCallback(async () => {
    if (!user?.user_id) return;

    try {
      setRefreshing(true);
      const response = await fetch(
        `${IPConfig.API_BASE_URL}/farmer/farmer_details.php?user_id=${user.user_id}`
      );
      const data = await response.json();

      if (data.success && data.farm_details) {
        setFarmDetails(data.farm_details);

        // Update form with refreshed data
        setForm({
          farm_name: data.farm_details.farm_name || "",
          farm_type: data.farm_details.farm_type || farmTypes[0],
          certifications: data.farm_details.certifications || "",
          crop_varieties: data.farm_details.crop_varieties || "",
          machinery_used: data.farm_details.machinery_used || "",
          farm_size: data.farm_details.farm_size?.toString() || "0",
          income: data.farm_details.income?.toString() || "0",
          farm_location: data.farm_details.farm_location || "",
          barangay_id: data.farm_details.barangay_id?.toString() || "",
          farm_image: data.farm_details.farm_image || null,
        });
        Alert.alert("Success", "Farm details refreshed successfully");
      } else {
        console.log("No farm details found or error refreshing");
        Alert.alert("Info", "No updates found or could not refresh details");
      }
    } catch (error) {
      console.error("Error refreshing farm details:", error);
      Alert.alert("Error", "Failed to refresh farm details. Please try again.");
    } finally {
      setRefreshing(false);
    }
  }, [user?.user_id]);

  // Fetch barangays
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
    }
  };

  // Load data on component mount
  useEffect(() => {
    if (user?.user_id) {
      fetchFarmDetails();
      fetchBarangays();
    }
  }, [fetchFarmDetails, user]);
  // Track form changes and edit state  // Handle scrolling to the correct section when editing is activated
  useEffect(() => {
    if (isEditing && activeSection) {
      console.log(`Focusing on section: ${activeSection}`);
      // Reset active section when exiting edit mode
      if (!isEditing) {
        setActiveSection(null);
      }
    }
  }, [isEditing, activeSection]);

  useEffect(() => {
    console.log("Current edit state:", isEditing);
    if (!farmDetails) return;

    const formChanged =
      form.farm_name !== (farmDetails.farm_name || "") ||
      form.farm_type !== (farmDetails.farm_type || farmTypes[0]) ||
      form.certifications !== (farmDetails.certifications || "") ||
      form.crop_varieties !== (farmDetails.crop_varieties || "") ||
      form.machinery_used !== (farmDetails.machinery_used || "") ||
      form.farm_size !== (farmDetails.farm_size?.toString() || "0") ||
      form.income !== (farmDetails.income?.toString() || "0") ||
      form.farm_location !== (farmDetails.farm_location || "") ||
      form.barangay_id !== (farmDetails.barangay_id?.toString() || "") ||
      (typeof form.farm_image === "object" && form.farm_image !== null);

    setHasUnsavedChanges(formChanged);
  }, [form, farmDetails]);

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

    if (!result.canceled) {
      setForm({ ...form, farm_image: result.assets[0] });
    }
  };
  // Handle form update
  const handleUpdateFarmDetails = async () => {
    console.log("Updating farm details...");
    // Verify user is logged in with a valid ID
    if (!user || !user.user_id) {
      Alert.alert(
        "Error",
        "You must be logged in to update your farm profile. Please log in again."
      );
      return;
    }

    // Basic validation
    if (!form.farm_name.trim()) {
      Alert.alert("Error", "Please enter your farm name");
      return;
    }

    if (!form.farm_location.trim()) {
      Alert.alert("Error", "Please enter your farm location");
      return;
    }

    try {
      setUpdating(true);

      // Create form data for file upload
      const formData = new FormData();

      // Explicitly convert user_id to string and verify it exists
      const userId = user.user_id.toString();
      console.log("Submitting farm details with user_id:", userId);

      formData.append("user_id", userId);
      formData.append("farm_name", form.farm_name);
      formData.append("farm_type", form.farm_type);
      formData.append("certifications", form.certifications);
      formData.append("crop_varieties", form.crop_varieties);
      formData.append("machinery_used", form.machinery_used);
      formData.append("farm_size", form.farm_size);
      formData.append("income", form.income);
      formData.append("farm_location", form.farm_location);

      if (form.barangay_id) {
        formData.append("barangay_id", form.barangay_id);
      }

      if (farmDetails?.detail_id) {
        formData.append("detail_id", farmDetails.detail_id.toString());
      }

      // Add image if selected and it's a new file (not a string URL)
      if (form.farm_image && typeof form.farm_image !== "string") {
        const filenameParts = form.farm_image.uri.split("/");
        const filename = filenameParts[filenameParts.length - 1];

        // Determine file type
        const match = /\.(\w+)$/.exec(filename);
        const fileType = match ? `image/${match[1]}` : "image";

        formData.append("farm_image", {
          uri: form.farm_image.uri,
          name: filename,
          type: fileType,
        } as any);
      }

      const endpoint = farmDetails?.detail_id
        ? `${IPConfig.API_BASE_URL}/farmer/farmer_details.php?detail_id=${farmDetails.detail_id}`
        : `${IPConfig.API_BASE_URL}/farmer/farmer_details.php`;

      const response = await fetch(endpoint, {
        method: "POST",
        body: formData,
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      const result = await response.json();

      if (result.success) {
        Alert.alert("Success", "Farm profile updated successfully");
        fetchFarmDetails();
        setIsEditing(false);
        setEditableSection(null);
      } else {
        Alert.alert("Error", result.message || "Failed to update farm profile");
      }
    } catch (error) {
      console.error("Error updating farm profile:", error);
      Alert.alert("Error", "Failed to update farm profile. Please try again.");
    } finally {
      setUpdating(false);
    }
  };
  // Function to enter edit mode with optional section focus
  const enterEditMode = (section?: string) => {
    console.log("Entering edit mode, focusing on section:", section || "none");
    setIsEditing(true);
    if (section) {
      setActiveSection(section);
    }
  };

  // New function to toggle section-specific editing
  const toggleSectionEdit = (section: string) => {
    if (editableSection === section) {
      // If already editing this section, save changes
      handleUpdateFarmDetails();
      setEditableSection(null);
    } else {
      // Start editing this section
      setEditableSection(section);
    }
  };

  // Handle cancel edit
  const handleCancelEdit = () => {
    if (hasUnsavedChanges) {
      Alert.alert(
        "Discard Changes",
        "Are you sure you want to discard your changes?",
        [
          { text: "Cancel", style: "cancel" },
          {
            text: "Discard",
            onPress: () => {
              // Reset form to original values
              if (farmDetails) {
                setForm({
                  farm_name: farmDetails.farm_name || "",
                  farm_type: farmDetails.farm_type || farmTypes[0],
                  certifications: farmDetails.certifications || "",
                  crop_varieties: farmDetails.crop_varieties || "",
                  machinery_used: farmDetails.machinery_used || "",
                  farm_size: farmDetails.farm_size?.toString() || "0",
                  income: farmDetails.income?.toString() || "0",
                  farm_location: farmDetails.farm_location || "",
                  barangay_id: farmDetails.barangay_id?.toString() || "",
                  farm_image: farmDetails.farm_image || null,
                });
              }
              setIsEditing(false);
            },
          },
        ]
      );
    } else {
      setIsEditing(false);
    }
  };
  // Navigate back to farmer dashboard
  const navigateBackToDashboard = () => {
    console.log("Navigating back to farmer dashboard");
    router.replace("/farmer/dashboard" as any);
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
          onPress={navigateBackToDashboard}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Farm Profile</Text>
        <View style={styles.headerButtons}>
          {!isEditing && (
            <>
              <TouchableOpacity
                style={styles.refreshButton}
                onPress={refreshFarmDetails}
                disabled={refreshing}
              >
                <Ionicons
                  name={refreshing ? "sync" : "refresh-outline"}
                  size={24}
                  color={COLORS.light}
                  style={refreshing ? styles.spinningIcon : {}}
                />
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.editButton}
                onPress={() => enterEditMode()}
              >
                <Ionicons
                  name="create-outline"
                  size={24}
                  color={COLORS.light}
                />
              </TouchableOpacity>
            </>
          )}
        </View>
      </View>
      {loading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading farm profile...</Text>
        </View>
      ) : refreshing ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Refreshing farm details...</Text>
        </View>
      ) : isEditing ? (
        <KeyboardAvoidingView
          behavior={Platform.OS === "ios" ? "padding" : "height"}
          style={{ flex: 1 }}
        >
          <ScrollView
            style={styles.scrollView}
            contentContainerStyle={styles.contentContainer}
            ref={(ref) => {
              // Scroll to the appropriate section if activeSection is set
              if (ref && activeSection) {
                // This is a basic implementation - in a real app, you'd use layout measurements
                // or a more sophisticated approach to scroll to the exact position
                setTimeout(() => {
                  const yOffset =
                    activeSection === "farmInfo"
                      ? 250
                      : activeSection === "certifications"
                        ? 600
                        : activeSection === "cropVarieties"
                          ? 750
                          : activeSection === "machineryUsed"
                            ? 900
                            : 0;

                  ref.scrollTo({ y: yOffset, animated: true });
                }, 300);
              }
            }}
          >
            {/* Farm Image */}
            <TouchableOpacity
              style={styles.imagePicker}
              onPress={handlePickImage}
            >
              {form.farm_image ? (
                <Image
                  source={{
                    uri:
                      typeof form.farm_image === "string"
                        ? getImageUrl(form.farm_image)
                        : form.farm_image.uri,
                  }}
                  style={styles.farmImage}
                  resizeMode="cover"
                />
              ) : (
                <View style={styles.placeholderImage}>
                  <Ionicons name="image-outline" size={40} color="#aaa" />
                  <Text style={styles.placeholderText}>
                    Tap to add farm image
                  </Text>
                </View>
              )}
            </TouchableOpacity>
            {/* Farm Information Section */}
            <View
              style={[
                styles.formSection,
                activeSection === "farmInfo" ? styles.activeFormSection : {},
              ]}
            >
              {/* Farm Name */}
              <Text style={styles.formLabel}>Farm Name *</Text>
              <TextInput
                style={styles.formInput}
                value={form.farm_name}
                onChangeText={(text) => setForm({ ...form, farm_name: text })}
                placeholder="Enter farm name"
              />
              {/* Farm Type */}
              <Text style={styles.formLabel}>Farm Type *</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={form.farm_type}
                  onValueChange={(value) =>
                    setForm({ ...form, farm_type: value })
                  }
                  style={styles.picker}
                >
                  {farmTypes.map((type) => (
                    <Picker.Item key={type} label={type} value={type} />
                  ))}
                </Picker>
              </View>
              {/* Farm Size */}
              <Text style={styles.formLabel}>Farm Size (hectares)</Text>
              <TextInput
                style={styles.formInput}
                value={form.farm_size}
                onChangeText={(text) => setForm({ ...form, farm_size: text })}
                placeholder="0"
                keyboardType="numeric"
              />
              {/* Farm Location */}
              <Text style={styles.formLabel}>Farm Location *</Text>
              <TextInput
                style={styles.formInput}
                value={form.farm_location}
                onChangeText={(text) =>
                  setForm({ ...form, farm_location: text })
                }
                placeholder="Enter farm address"
              />
              {/* Barangay */}
              <Text style={styles.formLabel}>Barangay</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={form.barangay_id}
                  onValueChange={(value) =>
                    setForm({ ...form, barangay_id: value })
                  }
                  style={styles.picker}
                >
                  <Picker.Item label="Select Barangay" value="" />
                  {barangays.map((barangay) => (
                    <Picker.Item
                      key={barangay.barangay_id}
                      label={barangay.name}
                      value={barangay.barangay_id.toString()}
                    />
                  ))}
                </Picker>
              </View>
              {/* Certifications */}
              <Text style={styles.formLabel}>Certifications</Text>
              <TextInput
                style={[styles.formInput, styles.textArea]}
                value={form.certifications}
                onChangeText={(text) =>
                  setForm({ ...form, certifications: text })
                }
                placeholder="Enter certifications (e.g., Organic, Good Agricultural Practices)"
                multiline
                numberOfLines={3}
              />
              {/* Crop Varieties */}
              <Text style={styles.formLabel}>Crop Varieties</Text>
              <TextInput
                style={[styles.formInput, styles.textArea]}
                value={form.crop_varieties}
                onChangeText={(text) =>
                  setForm({ ...form, crop_varieties: text })
                }
                placeholder="Enter crop varieties grown"
                multiline
                numberOfLines={3}
              />
              {/* Machinery Used */}
              <Text style={styles.formLabel}>Machinery Used</Text>
              <TextInput
                style={[styles.formInput, styles.textArea]}
                value={form.machinery_used}
                onChangeText={(text) =>
                  setForm({ ...form, machinery_used: text })
                }
                placeholder="Enter farming equipment and machinery used"
                multiline
                numberOfLines={3}
              />
              {/* Annual Income (₱) */}
              <Text style={styles.formLabel}>Annual Income (₱)</Text>
              <TextInput
                style={styles.formInput}
                value={form.income}
                onChangeText={(text) => setForm({ ...form, income: text })}
                placeholder="0"
                keyboardType="numeric"
              />

              {/* Action Buttons */}
              <View style={styles.buttonContainer}>
                <TouchableOpacity
                  style={[styles.button, styles.cancelButton]}
                  onPress={handleCancelEdit}
                >
                  <Text style={styles.buttonText}>Cancel</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[
                    styles.button,
                    styles.saveButton,
                    updating && styles.disabledButton,
                  ]}
                  onPress={handleUpdateFarmDetails}
                  disabled={updating}
                >
                  {updating ? (
                    <ActivityIndicator size="small" color="#fff" />
                  ) : (
                    <Text style={styles.buttonText}>Save</Text>
                  )}
                </TouchableOpacity>
              </View>
            </View>
            {/* Close Farm Information Section */}
          </ScrollView>
        </KeyboardAvoidingView>
      ) : (
        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={styles.contentContainer}
        >
          {/* Farm Image */}
          {form.farm_image ? (
            <Image
              source={{
                uri:
                  typeof form.farm_image === "string"
                    ? getImageUrl(form.farm_image)
                    : form.farm_image.uri,
              }}
              style={styles.farmImageLarge}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.placeholderImageLarge}>
              <Ionicons name="image-outline" size={60} color="#aaa" />
              <Text style={styles.placeholderText}>No farm image</Text>
            </View>
          )}
          <View style={styles.profileCard}>
            {/* Farm Name - Now inline editable */}
            {editableSection === "farmName" ? (
              <View style={styles.inlineEditContainer}>
                <TextInput
                  style={styles.inlineEditInput}
                  value={form.farm_name}
                  onChangeText={(text) => setForm({ ...form, farm_name: text })}
                  placeholder="Enter farm name"
                  autoFocus
                />
                <View style={styles.inlineEditButtons}>
                  <TouchableOpacity
                    style={styles.inlineEditButton}
                    onPress={() => toggleSectionEdit("farmName")}
                  >
                    <Ionicons
                      name="checkmark-circle"
                      size={24}
                      color={COLORS.primary}
                    />
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.inlineEditButton}
                    onPress={() => {
                      if (farmDetails) {
                        setForm({
                          ...form,
                          farm_name: farmDetails.farm_name || "",
                        });
                      }
                      setEditableSection(null);
                    }}
                  >
                    <Ionicons name="close-circle" size={24} color="#F44336" />
                  </TouchableOpacity>
                </View>
              </View>
            ) : (
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.farmName}>
                  {form.farm_name || "Unnamed Farm"}
                </Text>
                <TouchableOpacity
                  onPress={() => toggleSectionEdit("farmName")}
                  style={styles.sectionEditButton}
                >
                  <Ionicons
                    name="create-outline"
                    size={16}
                    color={COLORS.primary}
                  />
                  <Text style={styles.sectionEditButtonText}>Edit</Text>
                </TouchableOpacity>
              </View>
            )}

            {/* Farm Type */}
            <View style={styles.farmTypeBadge}>
              <Text style={styles.farmTypeText}>{form.farm_type}</Text>
            </View>

            <View style={styles.detailsSection}>
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.sectionTitle}>Farm Information</Text>
                {editableSection === "farmInfo" ? (
                  <TouchableOpacity
                    onPress={() => toggleSectionEdit("farmInfo")}
                    style={[
                      styles.sectionEditButton,
                      { backgroundColor: COLORS.primary },
                    ]}
                  >
                    <Ionicons name="save-outline" size={16} color="#fff" />
                    <Text
                      style={[styles.sectionEditButtonText, { color: "#fff" }]}
                    >
                      Save
                    </Text>
                  </TouchableOpacity>
                ) : (
                  <TouchableOpacity
                    onPress={() => toggleSectionEdit("farmInfo")}
                    style={styles.sectionEditButton}
                  >
                    <Ionicons
                      name="create-outline"
                      size={16}
                      color={COLORS.primary}
                    />
                    <Text style={styles.sectionEditButtonText}>Edit</Text>
                  </TouchableOpacity>
                )}
              </View>

              {editableSection === "farmInfo" ? (
                <View style={styles.inlineSectionEdit}>
                  <Text style={styles.formLabel}>Farm Type *</Text>
                  <View style={styles.pickerContainer}>
                    <Picker
                      selectedValue={form.farm_type}
                      onValueChange={(value) =>
                        setForm({ ...form, farm_type: value })
                      }
                      style={styles.picker}
                    >
                      {farmTypes.map((type) => (
                        <Picker.Item key={type} label={type} value={type} />
                      ))}
                    </Picker>
                  </View>

                  <Text style={styles.formLabel}>Farm Size (hectares)</Text>
                  <TextInput
                    style={styles.formInput}
                    value={form.farm_size}
                    onChangeText={(text) =>
                      setForm({ ...form, farm_size: text })
                    }
                    placeholder="0"
                    keyboardType="numeric"
                  />

                  <Text style={styles.formLabel}>Farm Location *</Text>
                  <TextInput
                    style={styles.formInput}
                    value={form.farm_location}
                    onChangeText={(text) =>
                      setForm({ ...form, farm_location: text })
                    }
                    placeholder="Enter farm address"
                  />

                  <Text style={styles.formLabel}>Barangay</Text>
                  <View style={styles.pickerContainer}>
                    <Picker
                      selectedValue={form.barangay_id}
                      onValueChange={(value) =>
                        setForm({ ...form, barangay_id: value })
                      }
                      style={styles.picker}
                    >
                      <Picker.Item label="Select Barangay" value="" />
                      {barangays.map((barangay) => (
                        <Picker.Item
                          key={barangay.barangay_id}
                          label={barangay.name}
                          value={barangay.barangay_id.toString()}
                        />
                      ))}
                    </Picker>
                  </View>
                </View>
              ) : (
                <>
                  <View style={styles.detailRow}>
                    <View style={styles.detailIconContainer}>
                      <Ionicons
                        name="location"
                        size={20}
                        color={COLORS.primary}
                      />
                    </View>
                    <View style={styles.detailContent}>
                      <Text style={styles.detailLabel}>Location</Text>
                      <Text style={styles.detailValue}>
                        {form.farm_location || "Not specified"}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.detailRow}>
                    <View style={styles.detailIconContainer}>
                      <Ionicons name="map" size={20} color={COLORS.primary} />
                    </View>
                    <View style={styles.detailContent}>
                      <Text style={styles.detailLabel}>Barangay</Text>
                      <Text style={styles.detailValue}>
                        {barangays.find(
                          (b) => b.barangay_id.toString() === form.barangay_id
                        )?.name || "Not specified"}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.detailRow}>
                    <View style={styles.detailIconContainer}>
                      <Ionicons
                        name="resize"
                        size={20}
                        color={COLORS.primary}
                      />
                    </View>
                    <View style={styles.detailContent}>
                      <Text style={styles.detailLabel}>Farm Size</Text>
                      <Text style={styles.detailValue}>
                        {form.farm_size
                          ? `${form.farm_size} hectares`
                          : "Not specified"}
                      </Text>
                    </View>
                  </View>
                  {form.income && parseInt(form.income) > 0 && (
                    <View style={styles.detailRow}>
                      <View style={styles.detailIconContainer}>
                        <Ionicons
                          name="cash-outline"
                          size={20}
                          color={COLORS.primary}
                        />
                      </View>
                      <View style={styles.detailContent}>
                        <Text style={styles.detailLabel}>Annual Income</Text>
                        <Text style={styles.detailValue}>
                          ₱{parseInt(form.income).toLocaleString()}
                        </Text>
                      </View>
                    </View>
                  )}
                </>
              )}
            </View>

            {/* Remaining sections - show inline editing when selected */}
            {form.certifications && (
              <View style={styles.detailsSection}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Certifications</Text>
                  {editableSection === "certifications" ? (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("certifications")}
                      style={[
                        styles.sectionEditButton,
                        { backgroundColor: COLORS.primary },
                      ]}
                    >
                      <Ionicons name="save-outline" size={16} color="#fff" />
                      <Text
                        style={[
                          styles.sectionEditButtonText,
                          { color: "#fff" },
                        ]}
                      >
                        Save
                      </Text>
                    </TouchableOpacity>
                  ) : (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("certifications")}
                      style={styles.sectionEditButton}
                    >
                      <Ionicons
                        name="create-outline"
                        size={16}
                        color={COLORS.primary}
                      />
                      <Text style={styles.sectionEditButtonText}>Edit</Text>
                    </TouchableOpacity>
                  )}
                </View>
                {editableSection === "certifications" ? (
                  <TextInput
                    style={[styles.formInput, styles.textArea]}
                    value={form.certifications}
                    onChangeText={(text) =>
                      setForm({ ...form, certifications: text })
                    }
                    placeholder="Enter certifications"
                    multiline
                    numberOfLines={3}
                  />
                ) : (
                  <Text style={styles.detailText}>{form.certifications}</Text>
                )}
              </View>
            )}

            {/* Remaining sections follow the same pattern */}
            {form.crop_varieties && (
              <View style={styles.detailsSection}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Crop Varieties</Text>
                  {editableSection === "cropVarieties" ? (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("cropVarieties")}
                      style={[
                        styles.sectionEditButton,
                        { backgroundColor: COLORS.primary },
                      ]}
                    >
                      <Ionicons name="save-outline" size={16} color="#fff" />
                      <Text
                        style={[
                          styles.sectionEditButtonText,
                          { color: "#fff" },
                        ]}
                      >
                        Save
                      </Text>
                    </TouchableOpacity>
                  ) : (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("cropVarieties")}
                      style={styles.sectionEditButton}
                    >
                      <Ionicons
                        name="create-outline"
                        size={16}
                        color={COLORS.primary}
                      />
                      <Text style={styles.sectionEditButtonText}>Edit</Text>
                    </TouchableOpacity>
                  )}
                </View>
                {editableSection === "cropVarieties" ? (
                  <TextInput
                    style={[styles.formInput, styles.textArea]}
                    value={form.crop_varieties}
                    onChangeText={(text) =>
                      setForm({ ...form, crop_varieties: text })
                    }
                    placeholder="Enter crop varieties"
                    multiline
                    numberOfLines={3}
                  />
                ) : (
                  <Text style={styles.detailText}>{form.crop_varieties}</Text>
                )}
              </View>
            )}
            {form.machinery_used && (
              <View style={styles.detailsSection}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Machinery Used</Text>
                  {editableSection === "machineryUsed" ? (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("machineryUsed")}
                      style={[
                        styles.sectionEditButton,
                        { backgroundColor: COLORS.primary },
                      ]}
                    >
                      <Ionicons name="save-outline" size={16} color="#fff" />
                      <Text
                        style={[
                          styles.sectionEditButtonText,
                          { color: "#fff" },
                        ]}
                      >
                        Save
                      </Text>
                    </TouchableOpacity>
                  ) : (
                    <TouchableOpacity
                      onPress={() => toggleSectionEdit("machineryUsed")}
                      style={styles.sectionEditButton}
                    >
                      <Ionicons
                        name="create-outline"
                        size={16}
                        color={COLORS.primary}
                      />
                      <Text style={styles.sectionEditButtonText}>Edit</Text>
                    </TouchableOpacity>
                  )}
                </View>
                {editableSection === "machineryUsed" ? (
                  <TextInput
                    style={[styles.formInput, styles.textArea]}
                    value={form.machinery_used}
                    onChangeText={(text) =>
                      setForm({ ...form, machinery_used: text })
                    }
                    placeholder="Enter machinery used"
                    multiline
                    numberOfLines={3}
                  />
                ) : (
                  <Text style={styles.detailText}>{form.machinery_used}</Text>
                )}
              </View>
            )}
          </View>
          <TouchableOpacity
            style={styles.editProfileButton}
            onPress={() => enterEditMode()}
          >
            <Ionicons name="create-outline" size={20} color="#fff" />
            <Text style={styles.editProfileButtonText}>
              Edit Full Farm Profile
            </Text>
          </TouchableOpacity>
        </ScrollView>
      )}
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
  headerButtons: {
    flexDirection: "row",
    alignItems: "center",
  },
  backButton: {
    padding: 8,
  },
  editButton: {
    padding: 8,
  },
  refreshButton: {
    padding: 8,
    marginRight: 8,
  },
  spinningIcon: {
    opacity: 0.7,
    transform: [{ rotate: "45deg" }],
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  loadingText: {
    marginTop: 10,
    color: "#666",
    fontSize: 16,
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  imagePicker: {
    alignItems: "center",
    marginBottom: 20,
  },
  farmImage: {
    width: "100%",
    height: 200,
    borderRadius: 8,
  },
  farmImageLarge: {
    width: "100%",
    height: 200,
    borderRadius: 8,
    marginBottom: 16,
  },
  placeholderImage: {
    width: "100%",
    height: 150,
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#ddd",
    borderStyle: "dashed",
  },
  placeholderImageLarge: {
    width: "100%",
    height: 200,
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
    borderRadius: 8,
    marginBottom: 16,
  },
  placeholderText: {
    color: "#999",
    marginTop: 8,
  },
  formLabel: {
    fontSize: 16,
    color: "#333",
    marginBottom: 6,
    fontWeight: "500",
  },
  formInput: {
    backgroundColor: "#fff",
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
  pickerContainer: {
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    marginBottom: 16,
    backgroundColor: "#fff",
    overflow: "hidden",
  },
  picker: {
    height: 50,
    width: "100%",
    color: "#333", // Explicitly set text color for the picker
  },
  buttonContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginTop: 16,
  },
  button: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    justifyContent: "center",
    alignItems: "center",
  },
  cancelButton: {
    backgroundColor: "#ccc",
    marginRight: 8,
  },
  saveButton: {
    backgroundColor: COLORS.primary,
    marginLeft: 8,
  },
  disabledButton: {
    opacity: 0.5,
  },
  buttonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  profileCard: {
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  farmName: {
    fontSize: 22,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 8,
  },
  farmTypeBadge: {
    backgroundColor: COLORS.primary + "20",
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 16,
    alignSelf: "flex-start",
    marginBottom: 16,
  },
  farmTypeText: {
    color: COLORS.primary,
    fontWeight: "500",
  },
  detailsSection: {
    marginVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#eee",
    paddingTop: 12,
  },
  sectionHeaderRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#333",
  },
  sectionEditButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: COLORS.primary + "20",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 16,
    elevation: 1,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 1,
  },
  sectionEditButtonText: {
    fontSize: 12,
    color: COLORS.primary,
    fontWeight: "600",
    marginLeft: 4,
  },
  detailRow: {
    flexDirection: "row",
    marginBottom: 12,
  },
  detailIconContainer: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: COLORS.primary + "10",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  detailContent: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 14,
    color: "#666",
    marginBottom: 2,
  },
  detailValue: {
    fontSize: 16,
    color: "#333",
  },
  detailText: {
    fontSize: 16,
    color: "#333",
    lineHeight: 22,
  },
  editProfileButton: {
    backgroundColor: COLORS.primary,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 14,
    borderRadius: 8,
    marginBottom: 30,
  },
  editProfileButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
    marginLeft: 8,
  },
  formSection: {
    marginBottom: 15,
    paddingTop: 10,
  },
  activeFormSection: {
    backgroundColor: COLORS.primary + "10",
    padding: 15,
    borderRadius: 8,
    marginLeft: -10,
    marginRight: -10,
  },
  inlineEditContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  inlineEditInput: {
    flex: 1,
    backgroundColor: "#f5f5f5",
    borderWidth: 1,
    borderColor: COLORS.primary,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 18,
    fontWeight: "500",
  },
  inlineEditButtons: {
    flexDirection: "row",
    marginLeft: 8,
  },
  inlineEditButton: {
    padding: 4,
    marginLeft: 4,
  },
  inlineSectionEdit: {
    backgroundColor: "#f9f9f9",
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
  },
});
