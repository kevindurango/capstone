import React, { useState, useEffect } from "react";
import {
  View,
  TextInput,
  StyleSheet,
  TouchableOpacity,
  Alert,
  Text,
  ScrollView,
  Switch,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import {
  setNgrokUrl,
  getNgrokUrl,
  setLocalIpAddress,
  getLocalIpAddress,
  setUseNgrok,
} from "@/services/apiConfig";
import AsyncStorage from "@react-native-async-storage/async-storage";

export default function SettingsScreen() {
  const [ngrokUrl, setNgrokUrlState] = useState("");
  const [localIp, setLocalIpState] = useState("");
  const [useNgrok, setUseNgrokState] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    // Load saved settings
    const loadSettings = async () => {
      const savedUrl = await getNgrokUrl();
      const savedIp = await getLocalIpAddress();
      const useNgrokPref =
        (await AsyncStorage.getItem("@FarmersMarket:useNgrok")) === "true";

      if (savedUrl) {
        setNgrokUrlState(savedUrl);
        global.ngrokUrl = savedUrl;
      }

      if (savedIp) {
        setLocalIpState(savedIp);
        global.localIpAddress = savedIp;
      }

      setUseNgrokState(useNgrokPref);
      global.useNgrok = useNgrokPref;
    };

    loadSettings();
  }, []);

  const handleSaveNgrokUrl = async () => {
    // Validate URL format
    if (ngrokUrl && !ngrokUrl.match(/^https?:\/\/.+/)) {
      Alert.alert(
        "Invalid URL",
        "Please enter a valid URL including the protocol (http:// or https://)"
      );
      return;
    }

    setIsSaving(true);
    try {
      const success = await setNgrokUrl(ngrokUrl);
      if (success) {
        global.ngrokUrl = ngrokUrl;
        Alert.alert("Success", "Ngrok URL saved successfully!");
      } else {
        Alert.alert("Error", "Failed to save ngrok URL.");
      }
    } catch (error) {
      console.error("Error saving ngrok URL:", error);
      Alert.alert("Error", "An unexpected error occurred.");
    } finally {
      setIsSaving(false);
    }
  };

  const handleSaveLocalIp = async () => {
    // Validate IP format (basic validation)
    if (
      localIp &&
      !localIp.match(/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/)
    ) {
      Alert.alert(
        "Invalid IP Address",
        "Please enter a valid IP address (e.g., 192.168.1.100)"
      );
      return;
    }

    setIsSaving(true);
    try {
      const success = await setLocalIpAddress(localIp);
      if (success) {
        global.localIpAddress = localIp;
        Alert.alert("Success", "Local IP address saved successfully!");
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

  // Handler for toggling between ngrok and local IP
  const handleUseNgrokToggle = async (value: boolean) => {
    setUseNgrokState(value);
    await setUseNgrok(value);

    Alert.alert(
      "Connection Mode Updated",
      value
        ? "The app will now use Ngrok for API connections when available"
        : "The app will now use Local IP for API connections",
      [{ text: "OK" }]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <ThemedText style={styles.headerTitle}>App Settings</ThemedText>
      </View>

      <ScrollView style={styles.content}>
        <ThemedText style={styles.sectionTitle}>API Configuration</ThemedText>

        {/* Connection Mode Selector */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionSubtitle}>
            Connection Mode
          </ThemedText>
          <View style={styles.connectionModeContainer}>
            <ThemedText>Use Local IP Address (Recommended)</ThemedText>
            <Switch
              trackColor={{ false: "#767577", true: COLORS.accent }}
              thumbColor={useNgrok ? "#ffffff" : "#ffffff"}
              ios_backgroundColor="#3e3e3e"
              onValueChange={handleUseNgrokToggle}
              value={useNgrok}
            />
            <ThemedText>Use Ngrok Tunnel</ThemedText>
          </View>
          <Text style={styles.helpText}>
            {useNgrok
              ? "Using Ngrok tunnel for remote access (slower but works from anywhere)"
              : "Using direct local IP connection (faster but only works on same network)"}
          </Text>
        </View>

        {/* Local IP Configuration - Moved to the top for emphasis */}
        <View style={[styles.section, !useNgrok && styles.activeSection]}>
          <ThemedText style={styles.sectionSubtitle}>
            Local Network Connection
          </ThemedText>
          <View style={styles.inputContainer}>
            <ThemedText style={styles.label}>Local IP Address:</ThemedText>
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
            <Text style={styles.helpText}>
              Enter your local machine's IP address where XAMPP is running
            </Text>
          </View>

          <TouchableOpacity
            style={styles.saveButton}
            onPress={handleSaveLocalIp}
            disabled={isSaving}
          >
            <Text style={styles.saveButtonText}>
              {isSaving ? "Saving..." : "Save Local IP"}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.testButton, { marginTop: 10 }]}
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
            <Text style={[styles.testButtonText]}>Test Local Connection</Text>
          </TouchableOpacity>
        </View>

        {/* Ngrok Configuration */}
        <View
          style={[
            styles.section,
            styles.ngrokSection,
            useNgrok && styles.activeSection,
          ]}
        >
          <ThemedText style={styles.sectionSubtitle}>Ngrok Tunnel</ThemedText>
          <View style={styles.inputContainer}>
            <ThemedText style={styles.label}>Ngrok URL:</ThemedText>
            <TextInput
              style={styles.input}
              placeholder="https://your-ngrok-domain.ngrok.io"
              placeholderTextColor="#999"
              value={ngrokUrl}
              onChangeText={setNgrokUrlState}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <Text style={styles.helpText}>
              Enter the ngrok URL including http:// or https://
            </Text>
          </View>

          <TouchableOpacity
            style={styles.saveButton}
            onPress={handleSaveNgrokUrl}
            disabled={isSaving}
          >
            <Text style={styles.saveButtonText}>
              {isSaving ? "Saving..." : "Save Ngrok URL"}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.testButton, { marginTop: 10 }]}
            onPress={async () => {
              if (!ngrokUrl) {
                Alert.alert("Error", "Please enter and save a ngrok URL first");
                return;
              }

              try {
                const testUrl = `${ngrokUrl}/ping.php`;
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
                  "Could not reach the server. Check your ngrok URL and make sure ngrok is running."
                );
              }
            }}
          >
            <Text style={[styles.testButtonText]}>Test Connection</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.infoContainer}>
          <ThemedText style={styles.infoTitle}>Connection Priority:</ThemedText>
          <Text style={styles.infoText}>
            Based on your current settings, the app will use:
            {useNgrok ? (
              <>
                {"\n\n"}📡{" "}
                <Text style={{ fontWeight: "bold" }}>Ngrok Tunnel</Text> (if
                available)
                {"\n"}📱 Local IP as fallback
              </>
            ) : (
              <>
                {"\n\n"}📱 <Text style={{ fontWeight: "bold" }}>Local IP</Text>{" "}
                only
                {"\n"}📡 Ngrok is disabled
              </>
            )}
          </Text>
        </View>

        <View style={styles.infoContainer}>
          <ThemedText style={styles.infoTitle}>How to use ngrok:</ThemedText>
          <Text style={styles.infoText}>
            1. Run ngrok in your command prompt: ngrok http 80{"\n"}
            2. Copy the https URL provided by ngrok{"\n"}
            3. Paste it above and save{"\n"}
            4. Restart the app for changes to take effect
          </Text>
        </View>
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
  section: {
    marginBottom: 25,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: "bold",
    marginBottom: 20,
    color: COLORS.dark,
  },
  sectionSubtitle: {
    fontSize: 16,
    fontWeight: "bold",
    marginBottom: 10,
    color: COLORS.accent,
  },
  inputContainer: {
    marginBottom: 15,
  },
  label: {
    fontSize: 16,
    marginBottom: 8,
    color: COLORS.dark,
  },
  input: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: COLORS.dark,
  },
  helpText: {
    fontSize: 12,
    color: "#777",
    marginTop: 5,
  },
  saveButton: {
    backgroundColor: COLORS.accent,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
  },
  saveButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  testButton: {
    backgroundColor: "#4a90e2",
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: "center",
  },
  testButtonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
  infoContainer: {
    backgroundColor: "#e8f4f8",
    padding: 15,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: COLORS.primary,
    marginBottom: 20,
  },
  infoTitle: {
    fontSize: 16,
    fontWeight: "bold",
    marginBottom: 10,
    color: COLORS.dark,
  },
  infoText: {
    fontSize: 14,
    lineHeight: 20,
    color: COLORS.dark,
  },
  connectionModeContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
    paddingHorizontal: 10,
    paddingVertical: 15,
    backgroundColor: "#f0f0f0",
    borderRadius: 8,
  },
  activeSection: {
    borderWidth: 1,
    borderColor: COLORS.accent,
    borderRadius: 8,
    padding: 15,
    backgroundColor: "rgba(243, 244, 246, 0.7)",
  },
  ngrokSection: {
    opacity: 0.7, // Dim the ngrok section when not in use
  },
});
