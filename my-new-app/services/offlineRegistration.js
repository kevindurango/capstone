import AsyncStorage from "@react-native-async-storage/async-storage";

const OFFLINE_REGISTRATIONS_KEY = "@farmers_market_offline_registrations";

/**
 * Store registration data for later submission
 * @param {Object} userData - User registration data
 * @returns {Promise<void>}
 */
export const storeRegistration = async (userData) => {
  try {
    // Get existing offline registrations
    const existingData = await AsyncStorage.getItem(OFFLINE_REGISTRATIONS_KEY);
    const offlineRegistrations = existingData ? JSON.parse(existingData) : [];

    // Add timestamp to registration data
    const registrationWithTimestamp = {
      ...userData,
      timestamp: new Date().toISOString(),
      status: "pending",
    };

    // Add to list and save
    offlineRegistrations.push(registrationWithTimestamp);
    await AsyncStorage.setItem(
      OFFLINE_REGISTRATIONS_KEY,
      JSON.stringify(offlineRegistrations)
    );

    console.log(
      "[Offline Registration] Stored registration for later submission"
    );
  } catch (error) {
    console.error("[Offline Registration] Error storing registration:", error);
    throw error;
  }
};

/**
 * Get all pending offline registrations
 * @returns {Promise<Array>} List of pending registrations
 */
export const getPendingRegistrations = async () => {
  try {
    const existingData = await AsyncStorage.getItem(OFFLINE_REGISTRATIONS_KEY);
    return existingData ? JSON.parse(existingData) : [];
  } catch (error) {
    console.error(
      "[Offline Registration] Error retrieving pending registrations:",
      error
    );
    return [];
  }
};

/**
 * Mark a registration as submitted
 * @param {number} index - Index of the registration in the stored array
 * @returns {Promise<void>}
 */
export const markRegistrationSubmitted = async (index) => {
  try {
    const existingData = await AsyncStorage.getItem(OFFLINE_REGISTRATIONS_KEY);
    if (!existingData) {
      return;
    }

    const offlineRegistrations = JSON.parse(existingData);
    if (index >= 0 && index < offlineRegistrations.length) {
      offlineRegistrations[index].status = "submitted";
      offlineRegistrations[index].submittedAt = new Date().toISOString();

      await AsyncStorage.setItem(
        OFFLINE_REGISTRATIONS_KEY,
        JSON.stringify(offlineRegistrations)
      );
      console.log("[Offline Registration] Marked registration as submitted");
    }
  } catch (error) {
    console.error(
      "[Offline Registration] Error updating registration status:",
      error
    );
  }
};

/**
 * Clear all offline registrations
 * @returns {Promise<void>}
 */
export const clearOfflineRegistrations = async () => {
  try {
    await AsyncStorage.removeItem(OFFLINE_REGISTRATIONS_KEY);
    console.log("[Offline Registration] Cleared all offline registrations");
  } catch (error) {
    console.error(
      "[Offline Registration] Error clearing registrations:",
      error
    );
  }
};

export default {
  storeRegistration,
  getPendingRegistrations,
  markRegistrationSubmitted,
  clearOfflineRegistrations,
};
