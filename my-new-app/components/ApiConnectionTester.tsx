import React, { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  TouchableOpacity,
  TextInput,
  Alert,
  Modal,
  Platform,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";
import { getApiBaseUrlSync, setApiBaseUrl } from "@/services/apiConfig";
import { updateServerUrl } from "@/services/networkDiscovery";

interface ApiConnectionTesterProps {
  colors: {
    light: string;
    dark: string;
    muted: string;
    accent: string;
  };
}

interface StyleColors {
  light: string;
  dark: string;
  muted: string;
  accent: string;
}

export default function ApiConnectionTester({
  colors,
}: ApiConnectionTesterProps) {
  const [isTestingConnection, setIsTestingConnection] = useState(false);
  const [showIpModal, setShowIpModal] = useState(false);
  const [serverIp, setServerIp] = useState(
    Platform.OS === "android" ? "10.0.2.2" : "localhost"
  );

  useEffect(() => {
    const initializeConnection = async () => {
      const baseUrl = getApiBaseUrlSync();

      if (baseUrl) {
        try {
          const urlObj = new URL(baseUrl);
          setServerIp(urlObj.host);
        } catch (e) {
          console.error("[API Tester] Error parsing baseUrl:", e);
        }
      } else {
        const discovered = await updateServerUrl();

        if (!discovered) {
          const defaultUrl = `http://localhost/capstone/my-new-app/api`;
          await setApiBaseUrl(defaultUrl);
          setServerIp("localhost");
        } else {
          const currentUrl = getApiBaseUrlSync();
          try {
            const urlObj = new URL(currentUrl || "");
            setServerIp(urlObj.host);
          } catch (e) {
            console.error("[API Tester] Error updating serverIp:", e);
          }
        }
      }
    };

    initializeConnection();
  }, []);

  const testApiConnection = async () => {
    setIsTestingConnection(true);
    try {
      const discovered = await updateServerUrl();

      if (discovered) {
        const currentUrl = getApiBaseUrlSync();
        try {
          const urlObj = new URL(currentUrl || "");
          setServerIp(urlObj.host);
        } catch (e) {
          console.error("[Test Connection] Error updating serverIp:", e);
        }

        Alert.alert(
          "Connection Successful",
          `API server was automatically discovered and connection is working!`
        );
        setShowIpModal(false);
        return;
      }

      const testUrl = `http://${serverIp}/capstone/my-new-app/api/ping.php`;
      console.log("[Test Connection] Testing connection to:", testUrl);

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000);

      const response = await fetch(testUrl, {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      const responseData = await response.json();
      console.log("[Test Connection] Response:", responseData);

      if (response.ok && responseData.status === "success") {
        Alert.alert(
          "Connection Successful",
          `API server is running!\n\nServer: ${
            responseData.server
          }\nTimestamp: ${new Date(
            responseData.timestamp * 1000
          ).toLocaleString()}`
        );
      } else {
        Alert.alert(
          "Connection Issue",
          `Unexpected response from server.\nStatus: ${
            response.status
          }\nResponse: ${JSON.stringify(responseData)}`
        );
      }
    } catch (error: any) {
      console.error("[Test Connection] Error details:", error);

      let errorMessage = "Could not connect to the API server.";
      if (error.name === "AbortError") {
        errorMessage = "Connection timed out. Ensure the server is running.";
      } else if (
        error instanceof TypeError &&
        error.message.includes("Network request failed")
      ) {
        errorMessage =
          "Network request failed. Ensure your device and server are on the same network.";
      }

      Alert.alert("Connection Failed", errorMessage);
    } finally {
      setIsTestingConnection(false);
    }
  };

  const saveServerIp = async () => {
    if (!serverIp) {
      Alert.alert("Error", "Please enter a valid server address");
      return;
    }

    const newBaseUrl = `http://${serverIp}/capstone/my-new-app/api`;
    try {
      await setApiBaseUrl(newBaseUrl);
      Alert.alert(
        "Success",
        `Server address updated to ${serverIp}\nAPI URL: ${newBaseUrl}`,
        [
          { text: "Test Connection", onPress: testApiConnection },
          { text: "Close", onPress: () => setShowIpModal(false) },
        ]
      );
    } catch (error) {
      console.error("[API Config] Error setting API URL:", error);
      Alert.alert(
        "Error",
        "Failed to update server address. Please try again."
      );
    }
  };

  const addConnectionTroubleshooting = () => {
    const isAndroid = Platform && Platform.OS === "android";

    return (
      <View style={styles(colors).troubleshootingContainer}>
        <ThemedText style={styles(colors).troubleshootingTitle}>
          Having connection issues?
        </ThemedText>

        <TouchableOpacity
          style={styles(colors).autoDiscoverButton}
          onPress={async () => {
            try {
              setIsTestingConnection(true);
              const success = await updateServerUrl();
              if (success) {
                const currentUrl = getApiBaseUrlSync();
                try {
                  const urlObj = new URL(currentUrl || "");
                  setServerIp(urlObj.host);
                  Alert.alert("Success", "Server discovered automatically!");
                  setShowIpModal(false);
                } catch (e) {
                  console.error("[Auto-Discovery] Error updating serverIp:", e);
                }
              } else {
                Alert.alert(
                  "Auto-Discovery Failed",
                  "Could not automatically discover the server. Please enter the address manually."
                );
              }
            } catch (error) {
              console.error("[Auto-Discovery] Error:", error);
              Alert.alert("Error", "Auto-discovery failed. Please try again.");
            } finally {
              setIsTestingConnection(false);
            }
          }}
        >
          <ThemedText style={styles(colors).autoDiscoverButtonText}>
            {isTestingConnection ? "Discovering..." : "Auto-Discover Server"}
          </ThemedText>
        </TouchableOpacity>

        <View style={styles(colors).ipButtonsContainer}>
          <TouchableOpacity
            style={styles(colors).ipButton}
            onPress={() => {
              setServerIp("localhost");
              saveServerIp();
            }}
          >
            <ThemedText style={styles(colors).ipButtonText}>
              Use localhost
            </ThemedText>
          </TouchableOpacity>

          {isAndroid && (
            <TouchableOpacity
              style={styles(colors).ipButton}
              onPress={() => {
                setServerIp("10.0.2.2");
                saveServerIp();
              }}
            >
              <ThemedText style={styles(colors).ipButtonText}>
                Use 10.0.2.2 (Android Emulator)
              </ThemedText>
            </TouchableOpacity>
          )}
        </View>
      </View>
    );
  };

  return (
    <>
      <TouchableOpacity
        style={[
          styles(colors).testConnectionButton,
          isTestingConnection && styles(colors).disabledButton,
        ]}
        onPress={testApiConnection}
        disabled={isTestingConnection}
      >
        <ThemedText style={styles(colors).testButtonText}>
          {isTestingConnection
            ? "Testing Connection..."
            : "Test API Connection"}
        </ThemedText>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles(colors).configureIpButton}
        onPress={() => setShowIpModal(true)}
      >
        <Ionicons name="settings-outline" size={16} color={colors.light} />
        <ThemedText style={styles(colors).configureIpText}>
          Configure Server IP
        </ThemedText>
      </TouchableOpacity>

      <Modal
        visible={showIpModal}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setShowIpModal(false)}
      >
        <View style={styles(colors).modalOverlay}>
          <View style={styles(colors).modalContent}>
            <ThemedText style={styles(colors).modalTitle}>
              Configure Server IP
            </ThemedText>

            <ThemedText style={styles(colors).modalDescription}>
              Enter your development server's IP address (without http:// or
              path)
            </ThemedText>

            <View style={styles(colors).ipInputContainer}>
              <TextInput
                style={styles(colors).ipInput}
                placeholder="192.168.1.100"
                value={serverIp}
                onChangeText={setServerIp}
                keyboardType="numbers-and-punctuation"
                autoCapitalize="none"
              />
            </View>

            {addConnectionTroubleshooting()}

            <View style={styles(colors).modalButtons}>
              <TouchableOpacity
                style={[
                  styles(colors).modalButton,
                  styles(colors).cancelButton,
                ]}
                onPress={() => setShowIpModal(false)}
              >
                <ThemedText style={styles(colors).modalButtonText}>
                  Cancel
                </ThemedText>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles(colors).modalButton, styles(colors).saveButton]}
                onPress={saveServerIp}
              >
                <ThemedText style={styles(colors).modalButtonText}>
                  Save
                </ThemedText>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
}

const styles = (colors: StyleColors) =>
  StyleSheet.create({
    testConnectionButton: {
      backgroundColor: "rgba(255,255,255,0.2)",
      padding: 12,
      borderRadius: 10,
      marginTop: 20,
      borderWidth: 1,
      borderColor: colors.light,
    },
    testButtonText: {
      color: colors.light,
      textAlign: "center",
      fontSize: 14,
    },
    configureIpButton: {
      flexDirection: "row",
      alignItems: "center",
      justifyContent: "center",
      marginTop: 15,
      padding: 8,
    },
    configureIpText: {
      color: colors.light,
      fontSize: 14,
      marginLeft: 5,
      textDecorationLine: "underline",
    },
    modalOverlay: {
      flex: 1,
      backgroundColor: "rgba(0,0,0,0.5)",
      justifyContent: "center",
      alignItems: "center",
    },
    modalContent: {
      width: "80%",
      backgroundColor: colors.dark,
      borderRadius: 15,
      padding: 20,
      borderWidth: 1,
      borderColor: colors.light,
    },
    modalTitle: {
      fontSize: 20,
      fontWeight: "bold",
      color: colors.light,
      marginBottom: 15,
      textAlign: "center",
    },
    modalDescription: {
      fontSize: 14,
      color: colors.muted,
      marginBottom: 15,
      textAlign: "center",
    },
    ipInputContainer: {
      backgroundColor: "rgba(255,255,255,0.1)",
      borderRadius: 10,
      padding: 5,
      marginBottom: 20,
    },
    ipInput: {
      color: colors.light,
      fontSize: 16,
      padding: 10,
    },
    modalButtons: {
      flexDirection: "row",
      justifyContent: "space-between",
    },
    modalButton: {
      flex: 1,
      padding: 12,
      borderRadius: 8,
      margin: 5,
      alignItems: "center",
    },
    cancelButton: {
      backgroundColor: "rgba(255,255,255,0.2)",
    },
    saveButton: {
      backgroundColor: colors.accent,
    },
    modalButtonText: {
      color: colors.light,
      fontWeight: "bold",
    },
    troubleshootingContainer: {
      marginTop: 10,
      marginBottom: 15,
      padding: 10,
      backgroundColor: "rgba(0,0,0,0.2)",
      borderRadius: 8,
    },
    troubleshootingTitle: {
      color: colors.light,
      fontSize: 14,
      fontWeight: "bold",
      textAlign: "center",
      marginBottom: 8,
    },
    ipButtonsContainer: {
      flexDirection: "column",
      gap: 8,
    },
    ipButton: {
      backgroundColor: "rgba(255,255,255,0.15)",
      padding: 8,
      borderRadius: 6,
      alignItems: "center",
    },
    ipButtonText: {
      color: colors.light,
      fontSize: 13,
    },
    autoDiscoverButton: {
      backgroundColor: "rgba(0,150,136,0.7)",
      padding: 10,
      borderRadius: 6,
      marginBottom: 12,
      alignItems: "center",
    },
    autoDiscoverButtonText: {
      color: colors.light,
      fontWeight: "bold",
      fontSize: 14,
    },
    disabledButton: {
      opacity: 0.7,
    },
  });
