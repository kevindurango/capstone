import { Platform } from "react-native";
import { API_BASE_URL, LOCAL_IP_ADDRESS } from "@/constants/IPConfig";

export function logApiConfig() {
  console.log("API Configuration:");
  console.log(`Development Mode: ${__DEV__ ? "Yes" : "No"}`);
  console.log(`Platform: ${Platform.OS}`);
  console.log(`API Base URL: ${API_BASE_URL}`);
  console.log(`Local IP Address: ${LOCAL_IP_ADDRESS}`);
}
