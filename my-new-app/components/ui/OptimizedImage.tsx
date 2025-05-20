import React, { useState } from "react";
import {
  View,
  StyleSheet,
  ActivityIndicator,
  StyleProp,
  ImageStyle,
  ViewStyle,
} from "react-native";
import { Image } from "expo-image";
import { Ionicons } from "@expo/vector-icons";
import { getImageUrl } from "../../constants/Config";

interface OptimizedImageProps {
  source: string | null;
  style?: StyleProp<ImageStyle>;
  contentFit?: "cover" | "contain" | "fill" | "scale-down";
  transition?: number;
  placeholder?: string;
  cachePolicy?: "memory" | "disk" | "memory-disk" | "none";
  placeholderColor?: string;
  fallbackIcon?: keyof typeof Ionicons.glyphMap;
  fallbackIconSize?: number;
  fallbackIconColor?: string;
  showLoader?: boolean;
  contentPosition?:
    | "center"
    | "top"
    | "bottom"
    | "left"
    | "right"
    | "top right"
    | "top left"
    | "bottom right"
    | "bottom left";
  loaderSize?: "small" | "large";
  loaderColor?: string;
  containerStyle?: StyleProp<ViewStyle>;
}

/**
 * OptimizedImage component for better performance and caching
 * Uses Expo Image which has better performance and caching than react-native's Image
 */
const OptimizedImage: React.FC<OptimizedImageProps> = ({
  source,
  style,
  contentFit = "cover",
  transition = 300,
  placeholder = null,
  cachePolicy = "memory-disk",
  placeholderColor = "#f0f0f0",
  fallbackIcon = "image-outline",
  fallbackIconSize = 40,
  fallbackIconColor = "#aaa",
  showLoader = true,
  contentPosition = "center",
  loaderSize = "small",
  loaderColor = "#999",
  containerStyle,
}) => {
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);

  // Process image source to get the proper URL
  const imageUrl = source ? getImageUrl(source) : null;

  // Show error state if image can't be loaded
  if (hasError || !imageUrl) {
    return (
      <View
        style={[
          styles.container,
          { backgroundColor: placeholderColor },
          containerStyle,
        ]}
      >
        <Ionicons
          name={fallbackIcon}
          size={fallbackIconSize}
          color={fallbackIconColor}
        />
      </View>
    );
  }

  return (
    <View style={[styles.container, containerStyle]}>
      <Image
        source={{ uri: imageUrl }}
        style={[styles.image, style]}
        contentFit={contentFit}
        transition={transition}
        placeholder={placeholder}
        contentPosition={contentPosition}
        cachePolicy={cachePolicy}
        onLoadStart={() => setIsLoading(true)}
        onLoad={() => setIsLoading(false)}
        onError={() => {
          setHasError(true);
          setIsLoading(false);
        }}
      />
      {isLoading && showLoader && (
        <View style={styles.loaderContainer}>
          <ActivityIndicator size={loaderSize} color={loaderColor} />
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    position: "relative",
    justifyContent: "center",
    alignItems: "center",
    overflow: "hidden",
    width: "100%",
    height: "100%",
  },
  image: {
    width: "100%",
    height: "100%",
  },
  loaderContainer: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "rgba(0,0,0,0.1)",
  },
});

export default OptimizedImage;
