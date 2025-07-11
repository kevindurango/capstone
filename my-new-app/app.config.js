// This file extends app.json configuration with dynamic variables
import { ExpoConfig, getConfig } from "expo/config";

// Import the static configuration - use getConfig() to properly read app.json
const baseConfig = getConfig(__dirname);

const isProduction = process.env.APP_ENV === "production";

// You should set these as environment variables when building
const KEYSTORE_PASSWORD = process.env.KEYSTORE_PASSWORD || "kevin4567";
const KEY_PASSWORD = process.env.KEY_PASSWORD || "kevin4567";

// Get the base configuration from app.json
const config = baseConfig.expo;

// Create a new configuration object for dynamic properties
const expoConfig = {
  ...config,
};

// Add the keystore configuration for release/production builds
if (isProduction && expoConfig.android) {
  expoConfig.android = {
    ...expoConfig.android,
    buildProperties: {
      keystore: "./android/app/release.keystore",
      keystorePassword: KEYSTORE_PASSWORD,
      keyAlias: "my-key-alias",
      keyPassword: KEY_PASSWORD,
    },
  };
}

module.exports = {
  expo: expoConfig,
  name: "Farmers App",
  slug: "my-new-app",
  version: "1.0.0",
  orientation: "portrait",
  icon: "./assets/images/icon.png",
  scheme: "farmersmarket",
  userInterfaceStyle: "automatic",
  splash: {
    image: "./assets/images/splash-icon.png",
    resizeMode: "contain",
    backgroundColor: "#ffffff",
  },
  assetBundlePatterns: ["**/*"],
  ios: {
    supportsTablet: true,
  },
  android: {
    adaptiveIcon: {
      foregroundImage: "./assets/images/adaptive-icon.png",
      backgroundColor: "#ffffff",
    },
    package: "com.municipalagriculturesoffice.farmersapp",
    permissions: ["INTERNET", "ACCESS_NETWORK_STATE"],
  },
  plugins: ["expo-router"],
  experiments: {
    typedRoutes: true,
  },
  extra: {
    router: {
      origin: false,
    },
  },
};
