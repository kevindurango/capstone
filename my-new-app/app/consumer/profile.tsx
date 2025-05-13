import React, { useEffect, useState } from "react";
import {
  StyleSheet,
  View,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
  Image,
  Switch,
} from "react-native";
import { useRouter, Redirect } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { useAuth } from "@/contexts/AuthContext";
import { authService } from "@/services/authService";

// Enhanced colors for profile page
const ProfileColors = {
  ...COLORS,
  primary: "#1B5E20",
  accent: "#E65100",
  secondary: "#FFC107",
  success: "#43A047",
  error: "#D32F2F",
  inputBg: "#F5F8F5",
  cardBg: "#FFFFFF",
  fieldBorder: "#E0E0E0",
  fieldLabel: "#616161",
  placeholder: "#9E9E9E",
};

// Define types for userData for type safety
interface UserData {
  first_name: string;
  last_name: string;
  email: string;
  contact_number?: string;
  address?: string;
  user_id?: number;
  role_id?: number;
  username?: string;
  [key: string]: any; // For other properties that might be present
}

// Interface for password change data
interface PasswordChangeData {
  current_password: string;
  new_password: string;
  confirm_password: string;
}

export default function ProfileScreen() {
  const router = useRouter();
  const { user, logout, isAuthenticated, isConsumer, isFarmer } = useAuth();
  const [userData, setUserData] = useState<UserData>({
    first_name: "",
    last_name: "",
    email: "",
  });
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [editMode, setEditMode] = useState(false);
  const [updatedUserData, setUpdatedUserData] = useState<UserData | null>(null);
  const [showPasswordSection, setShowPasswordSection] = useState(false);
  const [passwordData, setPasswordData] = useState<PasswordChangeData>({
    current_password: "",
    new_password: "",
    confirm_password: "",
  });
  const [passwordErrors, setPasswordErrors] = useState<{
    [key: string]: string;
  }>({});

  // Fetch user data when component mounts
  useEffect(() => {
    const fetchUserData = async () => {
      try {
        const isAuthenticated = await authService.isAuthenticated();
        if (!isAuthenticated) {
          router.replace("/(auth)/login");
          return;
        }

        const userDataResult = await authService.getUserData();
        if (userDataResult) {
          setUserData({
            first_name: userDataResult.first_name || "",
            last_name: userDataResult.last_name || "",
            email: userDataResult.email || "",
            contact_number: userDataResult.contact_number || "",
            address: userDataResult.address || "",
            username: userDataResult.username || "",
            user_id: userDataResult.user_id,
            role_id: userDataResult.role_id,
          });
          setUpdatedUserData(null);
        }
      } catch (error) {
        console.error("[Profile] Error fetching user data:", error);
        Alert.alert("Error", "Failed to load profile data. Please try again.");
      } finally {
        setLoading(false);
      }
    };

    fetchUserData();
  }, []);

  const handleLogout = async () => {
    try {
      await logout();
      router.replace("/(auth)/login");
    } catch (error) {
      console.error("[Profile] Logout error:", error);
      Alert.alert("Error", "An unexpected error occurred. Please try again.");
    }
  };

  const handleEditToggle = () => {
    if (editMode && updatedUserData) {
      // Show confirmation before saving changes
      Alert.alert(
        "Save Changes",
        "Do you want to save your profile changes?",
        [
          {
            text: "Discard",
            onPress: () => {
              setEditMode(false);
              setUpdatedUserData(null);
              setShowPasswordSection(false);
            },
            style: "cancel",
          },
          {
            text: "Save",
            onPress: handleSaveChanges,
          },
        ],
        { cancelable: false }
      );
    } else {
      // Enter edit mode and initialize updatedUserData with current values
      setEditMode(true);
      setUpdatedUserData({ ...userData });
    }
  };

  const handleSaveChanges = async () => {
    if (!updatedUserData || !updatedUserData.user_id) {
      Alert.alert("Error", "User ID is missing. Please reload the profile.");
      return;
    }

    // Validate email format
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(updatedUserData.email)) {
      Alert.alert("Error", "Please enter a valid email address.");
      return;
    }

    setUpdating(true);
    try {
      // Create payload for the API
      const payload = {
        user_id: updatedUserData.user_id,
        first_name: updatedUserData.first_name,
        last_name: updatedUserData.last_name,
        email: updatedUserData.email,
        username: updatedUserData.username || "",
        contact_number: updatedUserData.contact_number || "",
        address: updatedUserData.address || "",
      };

      // Add password data if password section is shown and data is provided
      if (
        showPasswordSection &&
        passwordData.current_password &&
        passwordData.new_password
      ) {
        // Validate passwords
        const passwordValidation = validatePasswords();
        if (!passwordValidation.valid) {
          setUpdating(false);
          return;
        }

        Object.assign(payload, {
          current_password: passwordData.current_password,
          new_password: passwordData.new_password,
        });
      }

      // Call the profile update API
      const response = await authService.updateProfile(payload);

      if (response && response.status === "success") {
        // Update local state with new data
        setUserData({ ...response.user });
        setEditMode(false);
        setShowPasswordSection(false);
        setPasswordData({
          current_password: "",
          new_password: "",
          confirm_password: "",
        });
        Alert.alert("Success", response.message);
      } else {
        throw new Error(response?.message || "Unknown error updating profile");
      }
    } catch (error: any) {
      console.error("[Profile] Update profile error:", error);
      Alert.alert(
        "Error",
        error.message || "Failed to update profile. Please try again."
      );
    } finally {
      setUpdating(false);
    }
  };

  const handleInputChange = (field: keyof UserData, value: string) => {
    if (updatedUserData) {
      setUpdatedUserData({
        ...updatedUserData,
        [field]: value,
      });
    }
  };

  const handlePasswordChange = (
    field: keyof PasswordChangeData,
    value: string
  ) => {
    setPasswordData({
      ...passwordData,
      [field]: value,
    });

    // Clear errors when typing
    if (passwordErrors[field]) {
      const updatedErrors = { ...passwordErrors };
      delete updatedErrors[field];
      setPasswordErrors(updatedErrors);
    }
  };

  const validatePasswords = () => {
    const errors: { [key: string]: string } = {};

    if (!passwordData.current_password) {
      errors.current_password = "Current password is required";
    }

    if (!passwordData.new_password) {
      errors.new_password = "New password is required";
    } else if (passwordData.new_password.length < 6) {
      errors.new_password = "Password must be at least 6 characters";
    }

    if (passwordData.new_password !== passwordData.confirm_password) {
      errors.confirm_password = "Passwords do not match";
    }

    setPasswordErrors(errors);
    return { valid: Object.keys(errors).length === 0 };
  };

  const getRoleName = (roleId?: number) => {
    if (!roleId) return "User";

    const roles: { [key: number]: string } = {
      1: "User",
      2: "Farmer",
      3: "Admin",
      4: "Manager",
      5: "Organization Head",
    };

    return roles[roleId] || "User";
  };

  const togglePasswordSection = () => {
    setShowPasswordSection(!showPasswordSection);
    setPasswordData({
      current_password: "",
      new_password: "",
      confirm_password: "",
    });
    setPasswordErrors({});
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={ProfileColors.accent} />
        <ThemedText style={styles.loadingText}>Loading profile...</ThemedText>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Profile Header */}
      <LinearGradient
        colors={[ProfileColors.primary, ProfileColors.accent]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <View style={styles.profileImageContainer}>
            <View style={styles.profileImageWrapper}>
              <View style={styles.profileImage}>
                <Ionicons name="person" size={40} color={ProfileColors.light} />
              </View>
            </View>
            <View style={styles.profileBadge}>
              <Ionicons
                name="shield-checkmark"
                size={18}
                color={ProfileColors.light}
              />
            </View>
          </View>

          <View style={styles.profileInfo}>
            <ThemedText style={styles.profileName}>
              {userData.first_name} {userData.last_name}
            </ThemedText>
            <View style={styles.roleTag}>
              <Ionicons
                name="people-outline"
                size={14}
                color={ProfileColors.light}
              />
              <ThemedText style={styles.profileRole}>
                {getRoleName(userData.role_id)}
              </ThemedText>
            </View>
          </View>

          <TouchableOpacity
            style={[styles.editButton, editMode && styles.saveButton]}
            onPress={handleEditToggle}
          >
            <Ionicons
              name={editMode ? "save-outline" : "create-outline"}
              size={20}
              color={ProfileColors.light}
            />
            <ThemedText style={styles.editButtonText}>
              {editMode ? "Save" : "Edit"}
            </ThemedText>
          </TouchableOpacity>
        </View>
      </LinearGradient>

      {/* Profile Content */}
      <ScrollView
        style={styles.content}
        contentContainerStyle={styles.contentContainer}
      >
        {updating && (
          <View style={styles.updatingOverlay}>
            <ActivityIndicator size="large" color={ProfileColors.accent} />
            <ThemedText style={styles.updatingText}>
              Updating profile...
            </ThemedText>
          </View>
        )}

        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>
            Personal Information
          </ThemedText>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>First Name</ThemedText>
              {editMode && (
                <ThemedText style={styles.requiredStar}>*</ThemedText>
              )}
            </View>
            {editMode ? (
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.input}
                  value={updatedUserData?.first_name}
                  onChangeText={(text) => handleInputChange("first_name", text)}
                  placeholder="Enter first name"
                  placeholderTextColor={ProfileColors.placeholder}
                />
                <Ionicons
                  name="person-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={styles.inputIcon}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="person"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.first_name}
                </ThemedText>
              </View>
            )}
          </View>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>Last Name</ThemedText>
              {editMode && (
                <ThemedText style={styles.requiredStar}>*</ThemedText>
              )}
            </View>
            {editMode ? (
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.input}
                  value={updatedUserData?.last_name}
                  onChangeText={(text) => handleInputChange("last_name", text)}
                  placeholder="Enter last name"
                  placeholderTextColor={ProfileColors.placeholder}
                />
                <Ionicons
                  name="person-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={styles.inputIcon}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="person"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.last_name}
                </ThemedText>
              </View>
            )}
          </View>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>Username</ThemedText>
            </View>
            {editMode ? (
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.input}
                  value={updatedUserData?.username}
                  onChangeText={(text) => handleInputChange("username", text)}
                  placeholder="Enter username (optional)"
                  placeholderTextColor={ProfileColors.placeholder}
                />
                <Ionicons
                  name="at-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={styles.inputIcon}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="at"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.username || "Not set"}
                </ThemedText>
              </View>
            )}
          </View>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>Email</ThemedText>
              {editMode && (
                <ThemedText style={styles.requiredStar}>*</ThemedText>
              )}
            </View>
            {editMode ? (
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.input}
                  value={updatedUserData?.email}
                  onChangeText={(text) => handleInputChange("email", text)}
                  placeholder="Enter email address"
                  placeholderTextColor={ProfileColors.placeholder}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
                <Ionicons
                  name="mail-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={styles.inputIcon}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="mail"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.email}
                </ThemedText>
              </View>
            )}
          </View>
        </View>

        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Contact Details</ThemedText>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>Phone Number</ThemedText>
            </View>
            {editMode ? (
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.input}
                  value={updatedUserData?.contact_number}
                  onChangeText={(text) =>
                    handleInputChange("contact_number", text)
                  }
                  placeholder="Enter phone number"
                  placeholderTextColor={ProfileColors.placeholder}
                  keyboardType="phone-pad"
                />
                <Ionicons
                  name="call-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={styles.inputIcon}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="call"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.contact_number || "Not set"}
                </ThemedText>
              </View>
            )}
          </View>

          <View style={styles.field}>
            <View style={styles.fieldLabelContainer}>
              <ThemedText style={styles.fieldLabel}>Address</ThemedText>
            </View>
            {editMode ? (
              <View
                style={[styles.inputContainer, styles.multilineInputContainer]}
              >
                <TextInput
                  style={[styles.input, styles.multilineInput]}
                  value={updatedUserData?.address}
                  onChangeText={(text) => handleInputChange("address", text)}
                  placeholder="Enter address"
                  placeholderTextColor={ProfileColors.placeholder}
                  multiline={true}
                  numberOfLines={3}
                />
                <Ionicons
                  name="location-outline"
                  size={20}
                  color={ProfileColors.muted}
                  style={[styles.inputIcon, styles.multilineInputIcon]}
                />
              </View>
            ) : (
              <View style={styles.valueContainer}>
                <Ionicons
                  name="location"
                  size={18}
                  color={ProfileColors.primary}
                  style={styles.valueIcon}
                />
                <ThemedText style={styles.fieldValue}>
                  {userData.address || "Not set"}
                </ThemedText>
              </View>
            )}
          </View>
        </View>

        {/* Password Change Section */}
        {editMode && (
          <View style={styles.section}>
            <View style={styles.sectionTitleRow}>
              <ThemedText style={styles.sectionTitle}>Password</ThemedText>
              <TouchableOpacity
                style={[
                  styles.toggleButton,
                  showPasswordSection && styles.toggleActiveButton,
                ]}
                onPress={togglePasswordSection}
              >
                <Ionicons
                  name={showPasswordSection ? "chevron-up" : "lock-closed"}
                  size={16}
                  color={ProfileColors.accent}
                  style={{ marginRight: 5 }}
                />
                <ThemedText style={styles.toggleButtonText}>
                  {showPasswordSection ? "Cancel" : "Change Password"}
                </ThemedText>
              </TouchableOpacity>
            </View>

            {showPasswordSection && (
              <View style={styles.passwordFields}>
                <View style={styles.field}>
                  <View style={styles.fieldLabelContainer}>
                    <ThemedText style={styles.fieldLabel}>
                      Current Password
                    </ThemedText>
                    <ThemedText style={styles.requiredStar}>*</ThemedText>
                  </View>
                  <View
                    style={[
                      styles.inputContainer,
                      passwordErrors.current_password && styles.inputError,
                    ]}
                  >
                    <TextInput
                      style={styles.input}
                      value={passwordData.current_password}
                      onChangeText={(text) =>
                        handlePasswordChange("current_password", text)
                      }
                      placeholder="Enter your current password"
                      placeholderTextColor={ProfileColors.placeholder}
                      secureTextEntry={true}
                    />
                    <Ionicons
                      name="lock-closed-outline"
                      size={20}
                      color={
                        passwordErrors.current_password
                          ? ProfileColors.error
                          : ProfileColors.muted
                      }
                      style={styles.inputIcon}
                    />
                  </View>
                  {passwordErrors.current_password && (
                    <ThemedText style={styles.errorText}>
                      {passwordErrors.current_password}
                    </ThemedText>
                  )}
                </View>

                <View style={styles.field}>
                  <View style={styles.fieldLabelContainer}>
                    <ThemedText style={styles.fieldLabel}>
                      New Password
                    </ThemedText>
                    <ThemedText style={styles.requiredStar}>*</ThemedText>
                  </View>
                  <View
                    style={[
                      styles.inputContainer,
                      passwordErrors.new_password && styles.inputError,
                    ]}
                  >
                    <TextInput
                      style={styles.input}
                      value={passwordData.new_password}
                      onChangeText={(text) =>
                        handlePasswordChange("new_password", text)
                      }
                      placeholder="At least 6 characters"
                      placeholderTextColor={ProfileColors.placeholder}
                      secureTextEntry={true}
                    />
                    <Ionicons
                      name="key-outline"
                      size={20}
                      color={
                        passwordErrors.new_password
                          ? ProfileColors.error
                          : ProfileColors.muted
                      }
                      style={styles.inputIcon}
                    />
                  </View>
                  {passwordErrors.new_password && (
                    <ThemedText style={styles.errorText}>
                      {passwordErrors.new_password}
                    </ThemedText>
                  )}
                </View>

                <View style={styles.field}>
                  <View style={styles.fieldLabelContainer}>
                    <ThemedText style={styles.fieldLabel}>
                      Confirm New Password
                    </ThemedText>
                    <ThemedText style={styles.requiredStar}>*</ThemedText>
                  </View>
                  <View
                    style={[
                      styles.inputContainer,
                      passwordErrors.confirm_password && styles.inputError,
                    ]}
                  >
                    <TextInput
                      style={styles.input}
                      value={passwordData.confirm_password}
                      onChangeText={(text) =>
                        handlePasswordChange("confirm_password", text)
                      }
                      placeholder="Re-enter new password"
                      placeholderTextColor={ProfileColors.placeholder}
                      secureTextEntry={true}
                    />
                    <Ionicons
                      name="checkmark-circle-outline"
                      size={20}
                      color={
                        passwordData.confirm_password &&
                        passwordData.new_password ===
                          passwordData.confirm_password
                          ? ProfileColors.success
                          : passwordErrors.confirm_password
                          ? ProfileColors.error
                          : ProfileColors.muted
                      }
                      style={styles.inputIcon}
                    />
                  </View>
                  {passwordErrors.confirm_password && (
                    <ThemedText style={styles.errorText}>
                      {passwordErrors.confirm_password}
                    </ThemedText>
                  )}
                </View>

                <View style={styles.passwordNote}>
                  <Ionicons
                    name="information-circle"
                    size={16}
                    color={ProfileColors.accent}
                  />
                  <ThemedText style={styles.passwordNoteText}>
                    Password must be at least 6 characters long
                  </ThemedText>
                </View>
              </View>
            )}
          </View>
        )}

        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Ionicons
            name="log-out-outline"
            size={22}
            color={ProfileColors.light}
          />
          <ThemedText style={styles.logoutButtonText}>Logout</ThemedText>
        </TouchableOpacity>

        {/* Version info at bottom */}
        <View style={styles.versionContainer}>
          <ThemedText style={styles.versionText}>
            Palinpinon Farmers Market v1.0.0
          </ThemedText>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f5f7f9",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f5f7f9",
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: ProfileColors.text,
  },
  header: {
    paddingTop: 60,
    paddingBottom: 20,
    paddingHorizontal: 20,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
  },
  headerContent: {
    flexDirection: "row",
    alignItems: "center",
  },
  profileImageContainer: {
    position: "relative",
    marginRight: 15,
  },
  profileImageWrapper: {
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: "rgba(255, 255, 255, 0.2)",
    justifyContent: "center",
    alignItems: "center",
  },
  profileImage: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "rgba(255, 255, 255, 0.3)",
    justifyContent: "center",
    alignItems: "center",
  },
  profileBadge: {
    position: "absolute",
    bottom: 0,
    right: 0,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: ProfileColors.accent,
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 2,
    borderColor: ProfileColors.primary,
  },
  profileInfo: {
    flex: 1,
  },
  profileName: {
    fontSize: 18,
    fontWeight: "700",
    color: ProfileColors.light,
    marginBottom: 2,
  },
  roleTag: {
    flexDirection: "row",
    alignItems: "center",
  },
  profileRole: {
    fontSize: 14,
    color: ProfileColors.light,
    opacity: 0.8,
    marginLeft: 4,
  },
  editButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(255, 255, 255, 0.2)",
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
  },
  saveButton: {
    backgroundColor: ProfileColors.accent,
  },
  editButtonText: {
    fontSize: 14,
    color: ProfileColors.light,
    fontWeight: "600",
    marginLeft: 5,
  },
  content: {
    flex: 1,
  },
  contentContainer: {
    padding: 20,
    paddingBottom: 40,
  },
  updatingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(255, 255, 255, 0.9)",
    justifyContent: "center",
    alignItems: "center",
    zIndex: 100,
  },
  updatingText: {
    marginTop: 10,
    fontSize: 16,
    color: ProfileColors.accent,
  },
  section: {
    backgroundColor: ProfileColors.cardBg,
    borderRadius: 10,
    padding: 16,
    marginBottom: 20,
    shadowColor: ProfileColors.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 3,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: ProfileColors.primary,
    marginBottom: 16,
  },
  sectionTitleRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  field: {
    marginBottom: 16,
  },
  fieldLabelContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
  },
  fieldLabel: {
    fontSize: 14,
    fontWeight: "600",
    color: ProfileColors.fieldLabel,
  },
  requiredStar: {
    color: ProfileColors.error,
    marginLeft: 4,
    fontWeight: "bold",
  },
  inputContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: ProfileColors.inputBg,
    borderWidth: 1,
    borderColor: ProfileColors.fieldBorder,
    borderRadius: 8,
    paddingHorizontal: 12,
    height: 48,
  },
  input: {
    flex: 1,
    height: "100%",
    fontSize: 16,
    color: ProfileColors.dark,
  },
  inputIcon: {
    marginLeft: 8,
  },
  multilineInputContainer: {
    height: "auto",
    paddingVertical: 10,
  },
  multilineInput: {
    minHeight: 80,
    textAlignVertical: "top",
  },
  multilineInputIcon: {
    alignSelf: "flex-start",
    marginTop: 4,
  },
  valueContainer: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 10,
    paddingHorizontal: 12,
    backgroundColor: ProfileColors.inputBg,
    borderRadius: 8,
    opacity: 0.9,
  },
  valueIcon: {
    marginRight: 10,
  },
  fieldValue: {
    fontSize: 16,
    color: ProfileColors.dark,
  },
  toggleButton: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 6,
    paddingHorizontal: 12,
    backgroundColor: "#f8f8f8",
    borderRadius: 18,
  },
  toggleActiveButton: {
    backgroundColor: "rgba(230, 81, 0, 0.1)",
  },
  toggleButtonText: {
    fontSize: 14,
    color: ProfileColors.accent,
    fontWeight: "600",
  },
  passwordFields: {
    marginTop: 10,
  },
  inputError: {
    borderColor: ProfileColors.error,
  },
  errorText: {
    fontSize: 12,
    color: ProfileColors.error,
    marginTop: 4,
    marginLeft: 8,
  },
  passwordNote: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(230, 81, 0, 0.05)",
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    marginTop: 10,
  },
  passwordNoteText: {
    fontSize: 13,
    color: ProfileColors.accent,
    marginLeft: 8,
  },
  logoutButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: ProfileColors.accent,
    paddingVertical: 14,
    borderRadius: 8,
    marginVertical: 20,
  },
  logoutButtonText: {
    fontSize: 16,
    fontWeight: "600",
    color: ProfileColors.light,
    marginLeft: 8,
  },
  versionContainer: {
    alignItems: "center",
    marginBottom: 20,
  },
  versionText: {
    fontSize: 12,
    color: ProfileColors.muted,
    opacity: 0.7,
  },
});
