import { ExpoConfig } from "expo/config";

// Import the base config from app.json
const config = require("./app.json").expo as ExpoConfig;

// Export the configuration
export default config;
