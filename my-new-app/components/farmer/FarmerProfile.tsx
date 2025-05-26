import React, { useState, useEffect, useContext } from "react";
import {
  View,
  Text,
  StyleSheet,
  Image,
  TouchableOpacity,
  ScrollView,
  TextInput,
  Alert,
  ActivityIndicator,
  Modal,
  SafeAreaView,
} from "react-native";
import { COLORS } from "@/constants/Colors";
import { Picker } from "@react-native-picker/picker";
import { FontAwesome } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { AuthContext } from "../../contexts/AuthContext";
import { ThemedText } from "../ThemedText";
import FarmerFields from "./FarmerFields";
import { getApiBaseUrlSync } from "@/services/apiConfig";
import {
  createMaterialTopTabNavigator,
  MaterialTopTabNavigationEventMap,
} from "@react-navigation/material-top-tabs";
import {
  NavigationHelpers,
  ParamListBase,
  TabNavigationState,
} from "@react-navigation/native";
import SafeTopTabBar from "../SafeTopTabBar";
import { getImageUrl } from "@/constants/Config"; // Import getImageUrl function
import { useRouter } from "expo-router"; // Import useRouter from expo-router
import { MaterialTopTabDescriptorMap } from "@react-navigation/material-top-tabs/lib/typescript/src/types";
import { SceneRendererProps } from "react-native-tab-view";
import { JSX } from "react/jsx-runtime";

// Use the standardized API URL from apiConfig
const API_URL = getApiBaseUrlSync();

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

interface FarmerProfileProps {
  navigation?: any;
  initialTab?: string;
}

interface FarmerDetails {
  detail_id?: number;
  user_id?: number;
  farm_name: string;
  farm_type: string;
  certifications: string;
  crop_varieties: string;
  machinery_used: string;
  farm_size: number;
  income: number | null;
  farm_location: string;
  barangay_id: number | null;
}

interface UserProfile {
  user_id: number;
  username: string;
  first_name: string;
  last_name: string;
  email: string;
  contact_number: string;
  address: string;
  role_id: number;
}

interface Barangay {
  barangay_id: number;
  barangay_name: string;
  municipality?: string;
  province?: string;
}

const FARM_TYPES = [
  "Vegetable Farm",
  "Rice Farm",
  "Fruit Orchard",
  "Mixed Crops",
  "Root Crop Farm",
  "Herb Garden",
  "Highland Farm",
  "Lowland Farm",
  "Aquaculture",
  "Livestock",
];

// Create a Tab navigator for the profile sections
const Tab = createMaterialTopTabNavigator();

const FarmerProfile: React.FC<FarmerProfileProps> = ({
  navigation,
  initialTab = "Profile",
}) => {
  const router = useRouter(); // Add the router hook
  const { user, updateUserContext } = useContext(AuthContext);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editMode, setEditMode] = useState(false);
  const [profileData, setProfileData] = useState<UserProfile | null>(null);
  const [farmerDetails, setFarmerDetails] = useState<FarmerDetails>({
    farm_name: "",
    farm_type: "",
    certifications: "",
    crop_varieties: "",
    machinery_used: "",
    farm_size: 0,
    income: null,
    farm_location: "",
    barangay_id: null,
  });
  const [originalData, setOriginalData] = useState<{
    profile: UserProfile | null;
    details: FarmerDetails;
  }>({
    profile: null,
    details: {
      farm_name: "",
      farm_type: "",
      certifications: "",
      crop_varieties: "",
      machinery_used: "",
      farm_size: 0,
      income: null,
      farm_location: "",
      barangay_id: null,
    },
  });
  const [barangays, setBarangays] = useState<Barangay[]>([]);
  const [profileImage, setProfileImage] = useState<string | null>(null);
  const [refreshFieldsTrigger, setRefreshFieldsTrigger] = useState(0);
  const [imageError, setImageError] = useState(false); // Add state to track image loading errors

  useEffect(() => {
    if (user) {
      fetchProfileData();
      fetchBarangays();
    }
  }, [user]);

  const fetchProfileData = async () => {
    if (!user) return;

    setLoading(true);
    try {
      // Fetch user profile
      const profileResponse = await fetch(
        buildApiUrl(`user_profile.php?user_id=${user.user_id}`)
      );
      const profileData = await profileResponse.json();

      if (profileData.success) {
        setProfileData(profileData.user);
        setOriginalData((prev) => ({ ...prev, profile: profileData.user }));
      } else {
        console.error("Failed to fetch profile:", profileData.message);
      }

      // Fetch farmer details
      const detailsResponse = await fetch(
        buildApiUrl(`farmer/farmer_details.php?user_id=${user.user_id}`)
      );
      const detailsData = await detailsResponse.json();

      console.log("Farmer details response:", detailsData); // Debug log

      if (detailsData.success && detailsData.farm_details) {
        setFarmerDetails({
          detail_id: detailsData.farm_details.detail_id,
          user_id: detailsData.farm_details.user_id,
          farm_name: detailsData.farm_details.farm_name || "",
          farm_type: detailsData.farm_details.farm_type || "",
          certifications: detailsData.farm_details.certifications || "",
          crop_varieties: detailsData.farm_details.crop_varieties || "",
          machinery_used: detailsData.farm_details.machinery_used || "",
          farm_size: detailsData.farm_details.farm_size || 0,
          income: detailsData.farm_details.income || null,
          farm_location: detailsData.farm_details.farm_location || "",
          barangay_id: detailsData.farm_details.barangay_id || null,
        });

        setOriginalData((prev) => ({
          ...prev,
          details: {
            farm_name: detailsData.farm_details.farm_name || "",
            farm_type: detailsData.farm_details.farm_type || "",
            certifications: detailsData.farm_details.certifications || "",
            crop_varieties: detailsData.farm_details.crop_varieties || "",
            machinery_used: detailsData.farm_details.machinery_used || "",
            farm_size: detailsData.farm_details.farm_size || 0,
            income: detailsData.farm_details.income || null,
            farm_location: detailsData.farm_details.farm_location || "",
            barangay_id: detailsData.farm_details.barangay_id || null,
          },
        }));

        // If the component has fields to display, you could set them here
        // This assumes you have state to hold fields data
        if (detailsData.farm_details.fields) {
          // Store fields data in component state if needed
          console.log(
            "Fields data available:",
            detailsData.farm_details.fields.length
          );
        }

        // If the component has products to display, you could set them here
        if (detailsData.farm_details.products) {
          // Store products data in component state if needed
          console.log(
            "Products data available:",
            detailsData.farm_details.products.length
          );
        }
      } else if (detailsData.success && !detailsData.farm_details) {
        // If no existing details, keep default empty values
        console.log("No existing farmer details, using defaults");
      } else {
        console.error("Failed to fetch farmer details:", detailsData.message);
      }

      // Try to fetch profile image
      try {
        const imageResponse = await fetch(
          buildApiUrl(`user_profile_image.php?user_id=${user.user_id}`)
        );
        const imageData = await imageResponse.json();

        if (imageData.success && imageData.image_url) {
          // Store the image URL and use getImageUrl to process it
          setProfileImage(imageData.image_url);
          console.log(`[Profile] Original image path: ${imageData.image_url}`);
          console.log(
            `[Profile] Transformed URL: ${getImageUrl(imageData.image_url)}`
          );
        }
      } catch (error) {
        console.error("Error fetching profile image:", error);
        // No need to show an alert for this non-critical error
      }
    } catch (error) {
      console.error("Error fetching profile data:", error);
      Alert.alert(
        "Error",
        "Failed to load profile data. Please try again later."
      );
    } finally {
      setLoading(false);
    }
  };

  const fetchBarangays = async () => {
    try {
      // Use the buildApiUrl utility for consistent URL handling
      const response = await fetch(buildApiUrl("barangays.php"));
      const data = await response.json();

      if (data.success) {
        setBarangays(data.barangays);
      }
    } catch (error) {
      console.error("Error fetching barangays:", error);
      // Use dummy data if API fails
      setBarangays([
        { barangay_id: 1, barangay_name: "Balayagmanok" },
        { barangay_id: 2, barangay_name: "Balili" },
        { barangay_id: 3, barangay_name: "Bongbong Central" },
        // More barangays from database if needed
      ]);
    }
  };

  const handleProfileChange = (
    field: keyof UserProfile,
    value: string | number
  ) => {
    if (profileData) {
      setProfileData({
        ...profileData,
        [field]: value,
      });
    }
  };

  const handleFarmerDetailsChange = (
    field: keyof FarmerDetails,
    value: string | number | null
  ) => {
    setFarmerDetails({
      ...farmerDetails,
      [field]: value,
    });
  };

  const handleSave = async () => {
    if (!profileData || !user) return;

    // Validate inputs
    if (!profileData.first_name.trim() || !profileData.last_name.trim()) {
      Alert.alert("Error", "First name and last name are required");
      return;
    }

    if (!profileData.contact_number || profileData.contact_number.length < 10) {
      Alert.alert("Error", "Please enter a valid contact number");
      return;
    }

    setSaving(true);
    try {
      // Update user profile
      const profileResponse = await fetch(buildApiUrl(`update_profile.php`), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          user_id: user.user_id,
          first_name: profileData.first_name,
          last_name: profileData.last_name,
          contact_number: profileData.contact_number,
          address: profileData.address,
        }),
      });

      const profileResult = await profileResponse.json();

      if (!profileResult.success) {
        throw new Error(profileResult.message || "Failed to update profile");
      }

      // Update or create farmer details
      const farmerDetailsMethod = farmerDetails.detail_id ? "PUT" : "POST";
      const farmerDetailsEndpoint = farmerDetails.detail_id
        ? buildApiUrl(
            `farmer/farmer_details.php?detail_id=${farmerDetails.detail_id}`
          )
        : buildApiUrl(`farmer/farmer_details.php`);

      const farmerDetailsResponse = await fetch(farmerDetailsEndpoint, {
        method: farmerDetailsMethod,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ...farmerDetails,
          user_id: user.user_id,
        }),
      });

      const farmerDetailsResult = await farmerDetailsResponse.json();

      if (!farmerDetailsResult.success) {
        throw new Error(
          farmerDetailsResult.message || "Failed to update farmer details"
        );
      }

      // Update the local user context if necessary
      if (
        user.first_name !== profileData.first_name ||
        user.last_name !== profileData.last_name
      ) {
        if (updateUserContext) {
          updateUserContext({
            ...user,
            first_name: profileData.first_name,
            last_name: profileData.last_name,
          });
        }
      }

      // Update original data
      setOriginalData({
        profile: { ...profileData },
        details: { ...farmerDetails },
      });

      Alert.alert("Success", "Profile updated successfully");
      setEditMode(false);

      // Refresh fields list if barangay changed
      if (originalData.details.barangay_id !== farmerDetails.barangay_id) {
        setRefreshFieldsTrigger((prev) => prev + 1);
      }
    } catch (error: any) {
      console.error("Error saving profile:", error);
      Alert.alert(
        "Error",
        error.message || "Failed to update profile. Please try again later."
      );
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    // Revert to original data
    if (originalData.profile) {
      setProfileData(originalData.profile);
    }
    setFarmerDetails(originalData.details);
    setEditMode(false);
  };

  // Modify the selectProfileImage function to handle the absence of profile_image
  const selectProfileImage = async () => {
    if (!user) return;

    // Since we don't have profile image storage implemented yet,
    // let's just show an alert explaining this feature isn't available
    Alert.alert(
      "Feature Not Available",
      "Profile image upload is not currently available. This feature will be implemented in a future update.",
      [{ text: "OK", style: "default" }]
    );
  };

  // Create components for each tab
  const ProfileInfoTab = () => (
    <ScrollView style={styles.tabContainer}>
      {/* Profile Header Section */}
      <View style={styles.profileHeader}>
        <View style={styles.profileImageContainer}>
          <TouchableOpacity onPress={selectProfileImage} disabled={!editMode}>
            {profileImage && !imageError ? (
              <Image
                source={{ uri: getImageUrl(profileImage) }} // Use getImageUrl to transform the image path
                style={styles.profileImage}
                onError={() => {
                  console.error(
                    `[Profile] Failed to load image: ${getImageUrl(
                      profileImage
                    )}`
                  );
                  setImageError(true);
                }}
              />
            ) : (
              <View style={styles.profilePlaceholder}>
                <Text style={styles.profilePlaceholderText}>
                  {profileData?.first_name?.charAt(0)?.toUpperCase() || ""}
                  {profileData?.last_name?.charAt(0)?.toUpperCase() || ""}
                </Text>
              </View>
            )}
            {editMode && (
              <View style={styles.editImageOverlay}>
                <FontAwesome name="camera" size={24} color="#fff" />
              </View>
            )}
          </TouchableOpacity>
        </View>

        <View style={styles.profileInfo}>
          {!editMode ? (
            <>
              <ThemedText style={styles.profileName}>
                {profileData?.first_name} {profileData?.last_name}
              </ThemedText>
              <ThemedText style={styles.profileRole}>Farmer</ThemedText>
              <ThemedText style={styles.profileEmail}>
                {profileData?.email}
              </ThemedText>
            </>
          ) : (
            <>
              <View style={styles.editRow}>
                <TextInput
                  style={styles.editInput}
                  value={profileData?.first_name || ""}
                  onChangeText={(text) =>
                    handleProfileChange("first_name", text)
                  }
                  placeholder="First Name"
                />
                <TextInput
                  style={styles.editInput}
                  value={profileData?.last_name || ""}
                  onChangeText={(text) =>
                    handleProfileChange("last_name", text)
                  }
                  placeholder="Last Name"
                />
              </View>
              <ThemedText style={styles.profileRole}>Farmer</ThemedText>
              <ThemedText style={styles.profileEmail}>
                {profileData?.email}
              </ThemedText>
            </>
          )}
        </View>
      </View>

      {/* Edit/Save Buttons */}
      <View style={styles.actionButtonContainer}>
        {!editMode ? (
          <TouchableOpacity
            style={[styles.actionButton, styles.editButton]}
            onPress={() => setEditMode(true)}
          >
            <FontAwesome name="edit" size={16} color="#fff" />
            <Text style={styles.actionButtonText}>Edit Profile</Text>
          </TouchableOpacity>
        ) : (
          <View style={styles.editActionRow}>
            <TouchableOpacity
              style={[styles.actionButton, styles.cancelButton]}
              onPress={handleCancel}
              disabled={saving}
            >
              <FontAwesome name="times" size={16} color="#fff" />
              <Text style={styles.actionButtonText}>Cancel</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.actionButton, styles.saveButton]}
              onPress={handleSave}
              disabled={saving}
            >
              {saving ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <FontAwesome name="check" size={16} color="#fff" />
                  <Text style={styles.actionButtonText}>Save Changes</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        )}
      </View>

      {/* Contact Information Section */}
      <View style={styles.section}>
        <ThemedText style={styles.sectionTitle}>Contact Information</ThemedText>
        <View style={styles.sectionContent}>
          <View style={styles.infoRow}>
            <FontAwesome
              name="phone"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {profileData?.contact_number || "Not provided"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={profileData?.contact_number || ""}
                onChangeText={(text) =>
                  handleProfileChange("contact_number", text)
                }
                placeholder="Your contact number"
                keyboardType="phone-pad"
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="map-marker"
              size={20}
              color="#555"
              style={styles.infoIcon}
            />
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {profileData?.address || "Address not provided"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={profileData?.address || ""}
                onChangeText={(text) => handleProfileChange("address", text)}
                placeholder="Your address"
                multiline
              />
            )}
          </View>
        </View>
      </View>
    </ScrollView>
  );

  const FarmDetailsTab = () => (
    <ScrollView style={styles.tabContainer}>
      {/* Farm Details Section */}
      <View style={styles.section}>
        <ThemedText style={styles.sectionTitle}>Farm Details</ThemedText>
        <View style={styles.sectionContent}>
          <View style={styles.infoRow}>
            <FontAwesome
              name="home"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Farm Name:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.farm_name || "Not provided"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={farmerDetails.farm_name}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("farm_name", text)
                }
                placeholder="Enter farm name"
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="leaf"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Farm Type:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.farm_type || "Not specified"}
              </ThemedText>
            ) : (
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={farmerDetails.farm_type}
                  style={styles.picker}
                  onValueChange={(value) =>
                    handleFarmerDetailsChange("farm_type", value)
                  }
                >
                  <Picker.Item label="Select farm type" value="" />
                  {FARM_TYPES.map((type, index) => (
                    <Picker.Item key={index} label={type} value={type} />
                  ))}
                </Picker>
              </View>
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="map"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Barangay:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {barangays.find(
                  (b) => b.barangay_id === farmerDetails.barangay_id
                )?.barangay_name || "Not specified"}
              </ThemedText>
            ) : (
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={farmerDetails.barangay_id}
                  style={styles.picker}
                  onValueChange={(value) =>
                    handleFarmerDetailsChange("barangay_id", value)
                  }
                >
                  <Picker.Item label="Select barangay" value={null} />
                  {barangays.map((barangay) => (
                    <Picker.Item
                      key={barangay.barangay_id}
                      label={barangay.barangay_name}
                      value={barangay.barangay_id}
                    />
                  ))}
                </Picker>
              </View>
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="location-arrow"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Farm Location:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.farm_location || "Not provided"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={farmerDetails.farm_location}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("farm_location", text)
                }
                placeholder="Specific farm location"
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="expand"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>
              Farm Size (hectares):
            </ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.farm_size > 0
                  ? `${farmerDetails.farm_size} hectares`
                  : "Not provided"}
              </ThemedText>
            ) : (
              <TextInput
                style={[styles.editInfoInput, { width: 100 }]}
                value={farmerDetails.farm_size.toString()}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("farm_size", parseFloat(text) || 0)
                }
                placeholder="0.00"
                keyboardType="decimal-pad"
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="certificate"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Certifications:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.certifications || "None specified"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={farmerDetails.certifications}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("certifications", text)
                }
                placeholder="Any certifications (e.g., Organic, GAP)"
                multiline
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="pagelines"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Crop Varieties:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.crop_varieties || "None specified"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={farmerDetails.crop_varieties || ""}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("crop_varieties", text)
                }
                placeholder="Major crop varieties grown"
                multiline
              />
            )}
          </View>

          <View style={styles.infoRow}>
            <FontAwesome
              name="wrench"
              size={18}
              color="#555"
              style={styles.infoIcon}
            />
            <ThemedText style={styles.infoLabel}>Machinery Used:</ThemedText>
            {!editMode ? (
              <ThemedText style={styles.infoText}>
                {farmerDetails.machinery_used || "None specified"}
              </ThemedText>
            ) : (
              <TextInput
                style={styles.editInfoInput}
                value={farmerDetails.machinery_used || ""}
                onChangeText={(text) =>
                  handleFarmerDetailsChange("machinery_used", text)
                }
                placeholder="Farm machinery and equipment used"
                multiline
              />
            )}
          </View>
        </View>
      </View>
    </ScrollView>
  );

  const FieldsTab = () => (
    <View style={styles.tabContainer}>
      <FarmerFields
        refreshTrigger={refreshFieldsTrigger}
        farmerId={user?.user_id} // Explicitly pass the farmer ID
      />
    </View>
  );

  // Navigate back to dashboard
  const handleBackToDashboard = () => {
    console.log("Navigating back to farmer dashboard");
    router.replace("/farmer/dashboard" as any);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0066cc" />
        <Text style={styles.loadingText}>Loading profile data...</Text>
      </View>
    );
  }
  // Map initialTab parameter to screen name
  const getInitialRouteName = () => {
    switch (initialTab?.toLowerCase()) {
      case "profile":
        return "Profile";
      case "farm-details":
        return "Farm Details";
      case "fields":
        return "Fields";
      default:
        return "Profile";
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header with Back Button */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={handleBackToDashboard}
        >
          <FontAwesome name="arrow-left" size={20} color="#333" />
          <Text style={styles.backButtonText}>Back to Dashboard</Text>
        </TouchableOpacity>
      </View>

      <Tab.Navigator
        initialRouteName={getInitialRouteName()}
        tabBar={(
          props: JSX.IntrinsicAttributes &
            SceneRendererProps & {
              state: TabNavigationState<ParamListBase>;
              navigation: NavigationHelpers<
                ParamListBase,
                MaterialTopTabNavigationEventMap
              >;
              descriptors: MaterialTopTabDescriptorMap;
            }
        ) => <SafeTopTabBar {...props} />}
        screenOptions={{
          tabBarActiveTintColor: COLORS.primary,
          tabBarInactiveTintColor: "#666",
          tabBarLabelStyle: { fontSize: 14, fontWeight: "bold" },
          tabBarStyle: { backgroundColor: "#fff" },
          tabBarIndicatorStyle: { backgroundColor: COLORS.primary },
        }}
      >
        <Tab.Screen
          name="Profile"
          component={ProfileInfoTab}
          options={{
            tabBarItemStyle: { width: "auto" },
            tabBarLabelStyle: { fontSize: 14 },
          }}
        />
        <Tab.Screen
          name="Farm Details"
          component={FarmDetailsTab}
          options={{
            tabBarItemStyle: { width: "auto" },
            tabBarLabelStyle: { fontSize: 14 },
          }}
        />
        <Tab.Screen
          name="Fields"
          component={FieldsTab}
          options={{
            tabBarItemStyle: { width: "auto" },
            tabBarLabelStyle: { fontSize: 14 },
          }}
        />
      </Tab.Navigator>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    padding: 10,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e1e1e1",
  },
  backButton: {
    flexDirection: "row",
    alignItems: "center",
    padding: 5,
  },
  backButtonText: {
    marginLeft: 10,
    fontSize: 16,
    color: "#333",
    fontWeight: "500",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f8f8f8",
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: "#555",
  },
  profileHeader: {
    backgroundColor: "#fff",
    padding: 20,
    flexDirection: "row",
    alignItems: "center",
    borderBottomWidth: 1,
    borderBottomColor: "#e1e1e1",
  },
  profileImageContainer: {
    position: "relative",
  },
  profileImage: {
    width: 90,
    height: 90,
    borderRadius: 45,
    borderWidth: 2,
    borderColor: "#ddd",
  },
  profilePlaceholder: {
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: "#0066cc",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 2,
    borderColor: "#ddd",
  },
  profilePlaceholderText: {
    fontSize: 32,
    color: "#fff",
    fontWeight: "bold",
  },
  editImageOverlay: {
    position: "absolute",
    bottom: 0,
    right: 0,
    backgroundColor: "rgba(0,0,0,0.5)",
    borderRadius: 15,
    width: 30,
    height: 30,
    justifyContent: "center",
    alignItems: "center",
  },
  profileInfo: {
    marginLeft: 20,
    flex: 1,
  },
  profileName: {
    fontSize: 22,
    fontWeight: "bold",
  },
  profileRole: {
    fontSize: 16,
    color: "#666",
    marginVertical: 4,
  },
  profileEmail: {
    fontSize: 14,
    color: "#888",
  },
  actionButtonContainer: {
    padding: 15,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e1e1e1",
  },
  actionButton: {
    flexDirection: "row",
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 5,
    alignItems: "center",
    justifyContent: "center",
  },
  editActionRow: {
    flexDirection: "row",
    justifyContent: "space-between",
  },
  editButton: {
    backgroundColor: "#0066cc",
  },
  saveButton: {
    backgroundColor: "#4caf50",
    flex: 1,
    marginLeft: 10,
  },
  cancelButton: {
    backgroundColor: "#f44336",
    flex: 1,
  },
  actionButtonText: {
    color: "#fff",
    fontWeight: "bold",
    marginLeft: 8,
  },
  section: {
    backgroundColor: "#fff",
    borderRadius: 8,
    marginHorizontal: 15,
    marginVertical: 10,
    padding: 15,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  fieldsSection: {
    padding: 0,
    paddingTop: 15,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 15,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
    paddingBottom: 5,
  },
  sectionContent: {},
  infoRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 12,
    flexWrap: "wrap",
  },
  infoIcon: {
    width: 25,
    textAlign: "center",
    marginRight: 10,
  },
  infoLabel: {
    fontWeight: "bold",
    width: 120,
  },
  infoText: {
    flex: 1,
    color: "#444",
  },
  editRow: {
    flexDirection: "row",
    justifyContent: "space-between",
  },
  editInput: {
    fontSize: 18,
    fontWeight: "bold",
    borderBottomWidth: 1,
    borderBottomColor: "#ddd",
    padding: 5,
    flex: 1,
    marginRight: 5,
  },
  editInfoInput: {
    flex: 1,
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 4,
    padding: 8,
    fontSize: 14,
  },
  pickerContainer: {
    flex: 1,
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 4,
    overflow: "hidden",
  },
  picker: {
    height: 40,
    width: "100%",
  },
  tabContainer: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
});

export default FarmerProfile;
