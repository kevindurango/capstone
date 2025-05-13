import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  ActivityIndicator,
  Alert,
} from "react-native";
import NetInfo from "@react-native-community/netinfo";
import { ThemedText } from "../ThemedText";
import { getApiBaseUrlSync, resetApiUrl } from "@/services/apiConfig";
import { LOCAL_IP_ADDRESS } from "@/constants/IPConfig";
import { Platform } from "react-native";

// Critical endpoints to test
const ENDPOINTS = [
  "connectivity-test.php",
  "api-test.php",
  "barangays.php",
  "farmer/farmer_fields.php?farmer_id=1",
  "login.php",
  "market.php",
];

interface TestResult {
  endpoint: string;
  status: number;
  success: boolean;
  responseTime: number;
  error?: string;
  data?: any;
}

interface NetworkInfo {
  type: string;
  isConnected: boolean;
  isInternetReachable: boolean | null;
  details: any;
}

const ApiConnectionTester = () => {
  const [results, setResults] = useState<TestResult[]>([]);
  const [networkInfo, setNetworkInfo] = useState<NetworkInfo | null>(null);
  const [loading, setLoading] = useState(false);
  const [currentApiUrl, setCurrentApiUrl] = useState(getApiBaseUrlSync());

  useEffect(() => {
    // Get network info when component mounts
    checkNetworkInfo();
  }, []);

  const checkNetworkInfo = async () => {
    try {
      const info = await NetInfo.fetch();
      setNetworkInfo({
        type: info.type,
        isConnected: info.isConnected || false,
        isInternetReachable: info.isInternetReachable,
        details: info.details,
      });
    } catch (error) {
      console.error("Error checking network info:", error);
    }
  };

  const testEndpoint = async (endpoint: string): Promise<TestResult> => {
    const startTime = Date.now();
    const apiUrl = `${getApiBaseUrlSync()}/${endpoint}`;

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 8000);

      const response = await fetch(apiUrl, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Client-Platform": Platform.OS,
        },
        signal: controller.signal,
      });

      clearTimeout(timeoutId);
      const responseTime = Date.now() - startTime;

      let data = null;
      try {
        const text = await response.text();
        data = text ? JSON.parse(text) : null;
      } catch (e) {
        console.log(`Could not parse JSON for ${endpoint}:`, e);
      }

      return {
        endpoint,
        status: response.status,
        success: response.status >= 200 && response.status < 300,
        responseTime,
        data,
      };
    } catch (error: any) {
      const responseTime = Date.now() - startTime;
      console.error(`API test failed for ${apiUrl}:`, error);

      return {
        endpoint,
        status: 0,
        success: false,
        responseTime,
        error: error.message || "Unknown error",
      };
    }
  };

  const runTests = async () => {
    setLoading(true);
    setResults([]);

    try {
      // Update network info first
      await checkNetworkInfo();

      // Test each endpoint
      const testResults = await Promise.all(
        ENDPOINTS.map((endpoint) => testEndpoint(endpoint))
      );

      setResults(testResults);
    } catch (error) {
      console.error("Error running API tests:", error);
      Alert.alert("Error", "Failed to complete API tests.");
    } finally {
      setLoading(false);
    }
  };

  const resetApi = async () => {
    try {
      const newUrl = await resetApiUrl();
      setCurrentApiUrl(newUrl);
      Alert.alert("Success", `API URL reset to: ${newUrl}`);
    } catch (error) {
      console.error("Error resetting API URL:", error);
      Alert.alert("Error", "Failed to reset API URL.");
    }
  };

  const renderNetworkInfo = () => {
    if (!networkInfo) return null;

    return (
      <View style={styles.sectionCard}>
        <ThemedText style={styles.sectionTitle}>Network Information</ThemedText>
        <View style={styles.infoRow}>
          <ThemedText>Connection Type:</ThemedText>
          <ThemedText style={styles.infoValue}>{networkInfo.type}</ThemedText>
        </View>
        <View style={styles.infoRow}>
          <ThemedText>Connected:</ThemedText>
          <ThemedText
            style={[
              styles.infoValue,
              {
                color: networkInfo.isConnected ? "#4caf50" : "#f44336",
              },
            ]}
          >
            {networkInfo.isConnected ? "✓ Yes" : "✗ No"}
          </ThemedText>
        </View>
        <View style={styles.infoRow}>
          <ThemedText>Internet Reachable:</ThemedText>
          <ThemedText
            style={[
              styles.infoValue,
              {
                color: networkInfo.isInternetReachable ? "#4caf50" : "#f44336",
              },
            ]}
          >
            {networkInfo.isInternetReachable === null
              ? "Unknown"
              : networkInfo.isInternetReachable
              ? "✓ Yes"
              : "✗ No"}
          </ThemedText>
        </View>
        {Platform.OS !== "web" && (
          <View style={styles.infoRow}>
            <ThemedText>Cellular/WiFi:</ThemedText>
            <ThemedText style={styles.infoValue}>
              {networkInfo.details?.isConnectionExpensive
                ? "Cellular"
                : "WiFi/Other"}
            </ThemedText>
          </View>
        )}
      </View>
    );
  };

  const renderApiInfo = () => {
    return (
      <View style={styles.sectionCard}>
        <ThemedText style={styles.sectionTitle}>API Configuration</ThemedText>
        <View style={styles.infoRow}>
          <ThemedText>Current API URL:</ThemedText>
          <ThemedText
            style={styles.apiUrl}
            numberOfLines={1}
            ellipsizeMode="middle"
          >
            {currentApiUrl}
          </ThemedText>
        </View>
        <View style={styles.infoRow}>
          <ThemedText>Local IP Address:</ThemedText>
          <ThemedText style={styles.infoValue}>{LOCAL_IP_ADDRESS}</ThemedText>
        </View>
        <View style={styles.infoRow}>
          <ThemedText>Platform:</ThemedText>
          <ThemedText style={styles.infoValue}>{Platform.OS}</ThemedText>
        </View>
        <TouchableOpacity style={styles.resetButton} onPress={resetApi}>
          <Text style={styles.resetButtonText}>Reset API URL</Text>
        </TouchableOpacity>
      </View>
    );
  };

  const renderResults = () => {
    if (results.length === 0) {
      return null;
    }

    return (
      <View style={styles.sectionCard}>
        <ThemedText style={styles.sectionTitle}>API Test Results</ThemedText>
        {results.map((result, index) => (
          <View key={index} style={styles.resultCard}>
            <View style={styles.resultHeader}>
              <ThemedText style={styles.endpointName}>
                {result.endpoint}
              </ThemedText>
              <View
                style={[
                  styles.statusBadge,
                  {
                    backgroundColor: result.success ? "#4caf50" : "#f44336",
                  },
                ]}
              >
                <Text style={styles.statusText}>
                  {result.success ? "SUCCESS" : "FAILED"}
                </Text>
              </View>
            </View>

            <View style={styles.resultDetails}>
              <View style={styles.detailRow}>
                <ThemedText>Status:</ThemedText>
                <ThemedText style={styles.detailValue}>
                  {result.status || "N/A"}
                </ThemedText>
              </View>
              <View style={styles.detailRow}>
                <ThemedText>Time:</ThemedText>
                <ThemedText style={styles.detailValue}>
                  {result.responseTime} ms
                </ThemedText>
              </View>
              {result.error && (
                <View style={styles.detailRow}>
                  <ThemedText>Error:</ThemedText>
                  <ThemedText
                    style={[styles.detailValue, { color: "#f44336" }]}
                  >
                    {result.error}
                  </ThemedText>
                </View>
              )}
            </View>
          </View>
        ))}
      </View>
    );
  };

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <ThemedText style={styles.title}>
            API Connection Diagnostics
          </ThemedText>
          <TouchableOpacity
            style={styles.testButton}
            onPress={runTests}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <Text style={styles.testButtonText}>Run Tests</Text>
            )}
          </TouchableOpacity>
        </View>

        {renderApiInfo()}
        {renderNetworkInfo()}
        {renderResults()}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f5f5f5",
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: "bold",
  },
  testButton: {
    backgroundColor: "#0066cc",
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 4,
  },
  testButtonText: {
    color: "#fff",
    fontWeight: "bold",
  },
  resetButton: {
    backgroundColor: "#ff9800",
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 4,
    marginTop: 12,
    alignSelf: "flex-start",
  },
  resetButtonText: {
    color: "#fff",
    fontWeight: "bold",
  },
  sectionCard: {
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
  sectionTitle: {
    fontSize: 16,
    fontWeight: "bold",
    marginBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#eee",
    paddingBottom: 8,
  },
  infoRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  infoValue: {
    fontWeight: "500",
  },
  apiUrl: {
    flex: 1,
    marginLeft: 8,
    fontWeight: "500",
  },
  resultCard: {
    borderWidth: 1,
    borderColor: "#eee",
    borderRadius: 4,
    marginBottom: 12,
    overflow: "hidden",
  },
  resultHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 8,
    backgroundColor: "#f9f9f9",
    borderBottomWidth: 1,
    borderBottomColor: "#eee",
  },
  endpointName: {
    flex: 1,
    fontSize: 14,
    fontWeight: "500",
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  statusText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "bold",
  },
  resultDetails: {
    padding: 8,
  },
  detailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 4,
  },
  detailValue: {
    fontWeight: "500",
  },
});

export default ApiConnectionTester;
