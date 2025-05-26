const { getDefaultConfig } = require("expo/metro-config");

const config = getDefaultConfig(__dirname);

// Fix for Expo SDK 52 virtual modules issue
config.resolver.platforms = ["ios", "android", "native", "web"];

// Add resolver for virtual modules
config.resolver.alias = {
  // Provide fallbacks for virtual modules that might not exist
  "expo/virtual/streams.js": require.resolve("stream"),
};

// Add node modules for better compatibility
config.resolver.alias = {
  ...config.resolver.alias,
  stream: require.resolve("stream-browserify"),
  crypto: require.resolve("crypto-browserify"),
  util: require.resolve("util"),
};

module.exports = config;
