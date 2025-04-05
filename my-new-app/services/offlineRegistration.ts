import AsyncStorage from "@react-native-async-storage/async-storage";

// Interface for pending registrations
interface PendingRegistration {
  fullName: string;
  email: string;
  password: string;
  userType: string;
  contact_number: string;
  address: string;
  timestamp: number;
}

// Store a registration locally when offline
export const storeRegistration = async (
  data: PendingRegistration
): Promise<void> => {
  try {
    // Get existing registrations
    const existingString = await AsyncStorage.getItem("pendingRegistrations");
    const existing: PendingRegistration[] = existingString
      ? JSON.parse(existingString)
      : [];

    // Add new registration with timestamp
    const newRegistration = {
      ...data,
      timestamp: Date.now(),
    };

    // Store updated list
    await AsyncStorage.setItem(
      "pendingRegistrations",
      JSON.stringify([...existing, newRegistration])
    );

    console.log("Registration stored locally for later sync");
    return Promise.resolve();
  } catch (error) {
    console.error("Error storing registration:", error);
    return Promise.reject(error);
  }
};

// Get all pending registrations
export const getPendingRegistrations = async (): Promise<
  PendingRegistration[]
> => {
  try {
    const data = await AsyncStorage.getItem("pendingRegistrations");
    return data ? JSON.parse(data) : [];
  } catch (error) {
    console.error("Error getting pending registrations:", error);
    return [];
  }
};

// Clear a specific registration
export const clearRegistration = async (email: string): Promise<void> => {
  try {
    const registrations = await getPendingRegistrations();
    const filtered = registrations.filter((reg) => reg.email !== email);
    await AsyncStorage.setItem(
      "pendingRegistrations",
      JSON.stringify(filtered)
    );
  } catch (error) {
    console.error("Error clearing registration:", error);
  }
};

// Sync pending registrations
export const syncPendingRegistrations = async (
  apiUrl: string
): Promise<boolean> => {
  try {
    const pending = await getPendingRegistrations();
    if (pending.length === 0) return true;

    for (const registration of pending) {
      try {
        const response = await fetch(apiUrl, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(registration),
        });

        if (response.ok) {
          await clearRegistration(registration.email);
        }
      } catch (error) {
        console.error(
          `Failed to sync registration for ${registration.email}:`,
          error
        );
        return false;
      }
    }
    return true;
  } catch (error) {
    console.error("Sync failed:", error);
    return false;
  }
};
