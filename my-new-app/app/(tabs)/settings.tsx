import React, { useState, useEffect } from "react";
import {
  View,
  TextInput,
  StyleSheet,
  TouchableOpacity,
  Alert,
  Text,
  ScrollView,
  ActivityIndicator,
  Platform,
  NativeModules,
  AlertButton,
  Modal,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import {
  resetApiUrl,
  getApiBaseUrl,
  setApiBaseUrl,
} from "@/services/apiConfig";
import { LOCAL_IP_ADDRESS } from "@/constants/IPConfig";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { Ionicons } from "@expo/vector-icons";
import ApiConnectionTester from "@/components/diagnostics/ApiConnectionTester";

// Storage key for the local IP address
const LOCAL_IP_STORAGE_KEY = "@FarmersMarket:localIp";

// Function to get the local IP address from storage
const getLocalIpAddress = async () => {
  try {
    const storedIp = await AsyncStorage.getItem(LOCAL_IP_STORAGE_KEY);
    return storedIp || LOCAL_IP_ADDRESS;
  } catch (error) {
    console.error("Error getting local IP:", error);
    return LOCAL_IP_ADDRESS;
  }
};

// Function to set the local IP address in storage
const setLocalIpAddress = async (ip: string) => {
  try {
    await AsyncStorage.setItem(LOCAL_IP_STORAGE_KEY, ip);

    // Construct API URL with consistent path formatting
    const apiUrl = `http://${ip}/capstone/my-new-app/api/`;

    // Update the API base URL
    await setApiBaseUrl(apiUrl);

    // Important: This ensures we're using the new IP address for all future API calls
    console.log(`[Settings] Updated local IP address to: ${ip}`);
    console.log(`[Settings] Updated API base URL to: ${apiUrl}`);

    return true;
  } catch (error) {
    console.error("Error setting local IP:", error);
    return false;
  }
};

export default function SettingsScreen() {
  const [localIp, setLocalIpState] = useState("");
  const [currentIp, setCurrentIp] = useState("Detecting...");
  const [isSaving, setIsSaving] = useState(false);
  const [isDetecting, setIsDetecting] = useState(false);
  const [recentIps, setRecentIps] = useState<string[]>([]);
  const [showDiagnosticsTool, setShowDiagnosticsTool] = useState(false);

  useEffect(() => {
    // Load saved settings
    const loadSettings = async () => {
      const savedIp = await getLocalIpAddress();

      // Load recently used IPs
      const savedIps = await AsyncStorage.getItem("@FarmersMarket:recentIps");
      const parsedIps = savedIps ? JSON.parse(savedIps) : [];
      setRecentIps(parsedIps);

      if (savedIp) {
        setLocalIpState(savedIp);
        global.localIpAddress = savedIp;
      }

      // Try to get the device's IP address
      fetchCurrentIpAddress();
    };

    loadSettings();
  }, []);

  // Save IP to recent IPs list
  const saveToRecentIps = async (ip: string) => {
    if (!ip || ip.trim() === "") return;

    try {
      let updatedIps = [...recentIps];

      // Remove the IP if it already exists (to avoid duplicates)
      updatedIps = updatedIps.filter((savedIp) => savedIp !== ip);

      // Add the new IP to the beginning of the array
      updatedIps.unshift(ip);

      // Keep only the 5 most recent IPs
      if (updatedIps.length > 5) {
        updatedIps = updatedIps.slice(0, 5);
      }

      // Save to state and AsyncStorage
      setRecentIps(updatedIps);
      await AsyncStorage.setItem(
        "@FarmersMarket:recentIps",
        JSON.stringify(updatedIps)
      );
    } catch (error) {
      console.error("Error saving recent IP:", error);
    }
  };

  // Function to get the device's current IP address
  const fetchCurrentIpAddress = async () => {
    try {
      // Use a public API to get IP address
      // This will get the external IP address, not local network IP
      setCurrentIp("Detecting...");

      // Try multiple IP detection services in case one fails
      try {
        const response = await fetch("https://api.ipify.org?format=json");
        const data = await response.json();
        if (data.ip) {
          setCurrentIp(data.ip);
          return;
        }
      } catch (err) {
        console.log("First IP service failed, trying alternative");
      }

      try {
        const response = await fetch("https://ifconfig.me/ip");
        const ip = await response.text();
        if (ip) {
          setCurrentIp(ip.trim());
          return;
        }
      } catch (err) {
        console.log("Second IP service failed");
      }

      // If all services fail
      setCurrentIp("IP detection failed");
    } catch (error) {
      console.error("Error getting IP:", error);
      setCurrentIp("Could not detect");
    }
  };

  // Function to detect IP automatically with better local IP guessing
  const detectLocalIp = async () => {
    setIsDetecting(true);
    try {
      // Try to get external IP first
      await fetchCurrentIpAddress();

      // Generate common local network patterns
      const possibleIPs: string[] = [];

      // Common local IP patterns
      const commonIpPrefixes = [
        "192.168.1.",
        "192.168.0.",
        "10.0.0.",
        "172.16.0.",
      ];
      const commonLastOctets = [1, 100, 1, 254];

      // Generate a list of possibilities
      commonIpPrefixes.forEach((prefix, index) => {
        possibleIPs.push(`${prefix}${commonLastOctets[index]}`);
      });

      // Get the unique possibilities
      const uniqueIPs = [...new Set(possibleIPs)];

      // Wait a moment for UI feedback
      await new Promise((resolve) => setTimeout(resolve, 1000));

      // Create options for user to select
      const ipOptions: AlertButton[] = [
        ...uniqueIPs.map((ip) => ({
          text: `Try ${ip}`,
          onPress: () => {
            setLocalIpState(ip);
            handleSaveLocalIp(ip);
          },
        })),
        {
          text: "Cancel",
          style: "cancel",
        },
      ];

      // Show options to user
      Alert.alert(
        "Suggested Server IPs",
        "Select a possible IP address for your XAMPP server:",
        ipOptions
      );
    } catch (error) {
      console.error("Error detecting IP:", error);
      Alert.alert(
        "Detection Failed",
        "Could not suggest IP addresses. Please enter manually."
      );
    } finally {
      setIsDetecting(false);
    }
  };

  const resetApiConnection = async () => {
    try {
      setIsSaving(true);
      const result = await resetApiUrl();
      if (result) {
        Alert.alert(
          "Connection Reset",
          "API connection has been reset successfully. The app will now use your local IP address for API connections."
        );

        // Refresh the displayed values
        const savedIp = await getLocalIpAddress();
        setLocalIpState(savedIp || "");
      } else {
        Alert.alert("Reset Failed", "Failed to reset API connection.");
      }
    } catch (error) {
      console.error("Error resetting API connection:", error);
      Alert.alert(
        "Error",
        "An unexpected error occurred while resetting the connection."
      );
    } finally {
      setIsSaving(false);
    }
  };

  const handleSaveLocalIp = async (ipOverride?: string) => {
    const ipToSave = ipOverride || localIp;

    // Validate IP format (basic validation)
    if (
      ipToSave &&
      !ipToSave.match(/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/)
    ) {
      Alert.alert(
        "Invalid IP Address",
        "Please enter a valid IP address (e.g., 192.168.1.100)"
      );
      return;
    }

    setIsSaving(true);
    try {
      const success = await setLocalIpAddress(ipToSave);
      if (success) {
        global.localIpAddress = ipToSave;
        await saveToRecentIps(ipToSave);
        Alert.alert(
          "Success",
          "Local IP address saved successfully! The app will now use this IP for API connections."
        );
        setLocalIpState(ipToSave);
      } else {
        Alert.alert("Error", "Failed to save local IP address.");
      }
    } catch (error) {
      console.error("Error saving local IP:", error);
      Alert.alert("Error", "An unexpected error occurred.");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <ThemedText style={styles.headerTitle}>
          API Connection Settings
        </ThemedText>
      </View>

      <ScrollView style={styles.content}>
        {/* API Diagnostics Button - Add this at the top */}
        <TouchableOpacity
          style={styles.diagnosticsButton}
          onPress={() => setShowDiagnosticsTool(true)}
        >
          <Ionicons name="analytics-outline" size={20} color="#fff" />
          <Text style={styles.diagnosticsButtonText}>
            Network Diagnostics Tool
          </Text>
        </TouchableOpacity>

        <View style={styles.infoCard}>
          <ThemedText style={styles.infoTitle}>Current Network</ThemedText>
          <View style={styles.infoRow}>
            <Ionicons name="wifi" size={20} color={COLORS.primary} />
            <ThemedText style={styles.infoValue}>{currentIp}</ThemedText>
          </View>
          <TouchableOpacity
            style={styles.refreshButton}
            onPress={fetchCurrentIpAddress}
          >
            <Ionicons name="refresh" size={16} color={COLORS.primary} />
            <Text style={styles.refreshText}>Refresh IP</Text>
          </TouchableOpacity>
        </View>

        {/* Local IP Configuration */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionSubtitle}>
            Server Connection Settings
          </ThemedText>
          <Text style={styles.helpText}>
            Enter the IP address of the computer running your XAMPP server
          </Text>

          <View style={styles.inputContainer}>
            <ThemedText style={styles.label}>
              Local Server IP Address:
            </ThemedText>
            <TextInput
              style={styles.input}
              placeholder="192.168.1.100"
              placeholderTextColor="#999"
              value={localIp}
              onChangeText={setLocalIpState}
              keyboardType="numeric"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>

          <TouchableOpacity
            style={styles.primaryButton}
            onPress={() => handleSaveLocalIp()}
            disabled={isSaving}
          >
            <Text style={styles.buttonText}>
              {isSaving ? "Saving..." : "Save IP Address"}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.secondaryButton, { marginTop: 10 }]}
            onPress={async () => {
              if (!localIp) {
                Alert.alert("Error", "Please enter and save a local IP first");
                return;
              }

              try {
                const testUrl = `http://${localIp}/capstone/my-new-app/api/ping.php`;
                Alert.alert(
                  "Testing...",
                  `Attempting to connect to ${testUrl}`
                );

                const response = await fetch(testUrl);
                const data = await response.json();

                if (data.status === "success") {
                  Alert.alert(
                    "Connection Successful",
                    `Server is reachable.\nTimestamp: ${new Date(
                      data.timestamp * 1000
                    ).toLocaleTimeString()}`
                  );
                } else {
                  Alert.alert(
                    "Connection Test Failed",
                    "Unexpected response from server."
                  );
                }
              } catch (error) {
                console.error("Connection test failed:", error);
                Alert.alert(
                  "Connection Failed",
                  "Could not reach the server. Check your local IP and make sure your XAMPP server is running."
                );
              }
            }}
          >
            <Text style={styles.secondaryButtonText}>Test Connection</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.secondaryButton, { marginTop: 10 }]}
            onPress={detectLocalIp}
            disabled={isDetecting}
          >
            {isDetecting ? (
              <ActivityIndicator size="small" color={COLORS.primary} />
            ) : (
              <View style={styles.buttonInner}>
                <Ionicons name="search" size={18} color={COLORS.primary} />
                <Text style={styles.secondaryButtonText}>
                  Auto-detect IP Address
                </Text>
              </View>
            )}
          </TouchableOpacity>

          {recentIps.length > 0 && (
            <View style={styles.recentIpsContainer}>
              <ThemedText style={styles.recentIpsTitle}>
                Recently Used IPs:
              </ThemedText>
              {recentIps.map((ip, index) => (
                <TouchableOpacity
                  key={index}
                  style={styles.recentIpButton}
                  onPress={() => setLocalIpState(ip)}
                >
                  <Ionicons name="time-outline" size={16} color={COLORS.dark} />
                  <Text style={styles.recentIpText}>{ip}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>

        <View style={styles.infoContainer}>
          <ThemedText style={styles.infoTitle}>
            How to find your IP Address:
          </ThemedText>
          <Text style={styles.infoText}>
            1. On the computer running XAMPP, open Command Prompt{"\n"}
            2. Type "ipconfig" and press Enter{"\n"}
            3. Look for "IPv4 Address" under your active network adapter{"\n"}
            4. Enter that IP address above{"\n"}
          </Text>
        </View>

        <TouchableOpacity
          style={[styles.resetButton, { marginTop: 20 }]}
          onPress={resetApiConnection}
          disabled={isSaving}
        >
          <Text style={styles.resetButtonText}>
            {isSaving ? "Resetting..." : "Reset API Connection"}
          </Text>
        </TouchableOpacity>

        {/* Add an extra message explaining the diagnostics tool */}
        <View style={[styles.infoContainer, { marginTop: 20 }]}>
          <ThemedText style={styles.infoTitle}>
            Having Network Issues?
          </ThemedText>
          <Text style={styles.infoText}>
            If you're experiencing connection problems, try using the Network
            Diagnostics Tool to test your API connectivity and troubleshoot
            issues.
          </Text>
        </View>
      </ScrollView>

      {/* API Diagnostics Modal */}
      <Modal
        visible={showDiagnosticsTool}
        animationType="slide"
        onRequestClose={() => setShowDiagnosticsTool(false)}
      >
        <SafeAreaView style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Network Diagnostics</Text>
            <TouchableOpacity
              style={styles.closeButton}
              onPress={() => setShowDiagnosticsTool(false)}
            >
              <Ionicons name="close" size={24} color="#000" />
            </TouchableOpacity>
          </View>

          <ApiConnectionTester />
        </SafeAreaView>
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
    paddingHorizontal: 20,
    paddingVertical: 15,
    backgroundColor: COLORS.primary,
    borderBottomWidth: 1,
    borderBottomColor: "rgba(0,0,0,0.05)",
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: "bold",
    color: COLORS.light,
    letterSpacing: 0.5,
  },
  content: {
    flex: 1,
    padding: 20,
  },
  infoCard: {
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 15,
    marginBottom: 20,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  infoTitle: {
    fontSize: 16,
    fontWeight: "bold",
    marginBottom: 10,
    color: COLORS.dark,
  },
  infoRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 5,
  },
  infoValue: {
    fontSize: 16,
    color: COLORS.dark,
    marginLeft: 10,
    fontWeight: "500",
  },
  refreshButton: {
    flexDirection: "row",
    alignItems: "center",
    alignSelf: "flex-end",
    paddingVertical: 5,
  },
  refreshText: {
    color: COLORS.primary,
    fontSize: 14,
    marginLeft: 5,
  },
  section: {
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 15,
    marginBottom: 20,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  sectionSubtitle: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 5,
    color: COLORS.primary,
  },
  inputContainer: {
    marginBottom: 15,
    marginTop: 10,
  },
  label: {
    fontSize: 16,
    marginBottom: 8,
    color: COLORS.dark,
  },
  input: {
    backgroundColor: "#f8f8f8",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: COLORS.dark,
  },
  helpText: {
    fontSize: 14,
    color: "#777",
    marginTop: 5,
  },
  primaryButton: {
    backgroundColor: COLORS.primary,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
  },
  buttonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  secondaryButton: {
    backgroundColor: "#f8f8f8",
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
    borderWidth: 1,
    borderColor: COLORS.primary,
  },
  buttonInner: {
    flexDirection: "row",
    alignItems: "center",
  },
  secondaryButtonText: {
    color: COLORS.primary,
    fontSize: 16,
    fontWeight: "bold",
    marginLeft: 5,
  },
  infoContainer: {
    backgroundColor: "#e8f4f8",
    padding: 15,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: COLORS.primary,
    marginBottom: 20,
  },
  infoText: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.dark,
  },
  recentIpsContainer: {
    marginTop: 15,
  },
  recentIpsTitle: {
    fontSize: 14,
    fontWeight: "bold",
    color: COLORS.dark,
    marginBottom: 5,
  },
  recentIpButton: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 8,
    paddingHorizontal: 12,
    backgroundColor: "#f0f0f0",
    borderRadius: 8,
    marginBottom: 5,
  },
  recentIpText: {
    fontSize: 14,
    color: COLORS.dark,
    marginLeft: 8,
  },
  resetButton: {
    backgroundColor: "#e74c3c",
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
  },
  resetButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  diagnosticsButton: {
    backgroundColor: "#0066cc",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderRadius: 8,
    marginBottom: 15,
  },
  diagnosticsButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
    marginLeft: 8,
  },
  modalContainer: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 15,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e1e1e1",
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "bold",
  },
  closeButton: {
    padding: 5,
  },
});
