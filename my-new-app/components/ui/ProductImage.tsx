import React, { useState, useEffect } from "react";
import {
  Image,
  View,
  StyleSheet,
  ActivityIndicator,
  ImageStyle,
  ViewStyle,
  ImageResizeMode,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { getImageUrl } from "@/constants/Config";
import { COLORS } from "@/constants/Colors";

interface ProductImageProps {
  imagePath: string | null;
  style?: ViewStyle;
  resizeMode?: ImageResizeMode;
  productId?: number;
}

const ProductImage: React.FC<ProductImageProps> = ({
  imagePath,
  style,
  resizeMode = "cover",
  productId,
}) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [retryCount, setRetryCount] = useState(0);
  const maxRetries = 2;

  useEffect(() => {
    if (!imagePath) {
      setError(true);
      setLoading(false);
      return;
    }

    const loadImage = async () => {
      try {
        setLoading(true);
        setError(false);

        // Get the processed URL
        const timestamp = Date.now();
        let url = getImageUrl(imagePath);

        if (!url) {
          throw new Error("Invalid image URL");
        }

        // Clean the URL and add cache busting
        url = url.replace(/\/+/g, "/").replace(":/", "://").trim();
        url = `${url}${url.includes("?") ? "&" : "?"}t=${timestamp}`;

        // Pre-validate the image URL with a HEAD request
        const response = await fetch(url, {
          method: "HEAD",
          headers: {
            "Cache-Control": "no-cache, no-store, must-revalidate",
            Pragma: "no-cache",
            Expires: "0",
          },
        });

        if (
          !response.ok ||
          !response.headers.get("content-type")?.startsWith("image/")
        ) {
          throw new Error("Invalid image response");
        }

        setImageUrl(url);
        console.log(
          `[ProductImage] Image URL validated for product ${productId || "unknown"}: ${url}`
        );
      } catch (err) {
        console.error(
          `[ProductImage] Error loading image for product ${productId || "unknown"}:`,
          err
        );

        if (retryCount < maxRetries) {
          console.log(
            `[ProductImage] Retrying (${retryCount + 1}/${maxRetries})`
          );
          setRetryCount((prev) => prev + 1);
          return;
        }

        setError(true);
        setLoading(false);
      }
    };

    loadImage();
  }, [imagePath, productId, retryCount]);

  const handleLoad = () => {
    console.log(
      `[ProductImage] Image loaded successfully for product ${productId || "unknown"}`
    );
    setLoading(false);
  };

  const handleError = () => {
    console.log(
      `[ProductImage] Image failed to load for product ${productId || "unknown"}`
    );

    if (retryCount < maxRetries) {
      setRetryCount((prev) => prev + 1);
      return;
    }

    setError(true);
    setLoading(false);
  };

  if (error || !imagePath) {
    return (
      <View style={[styles.placeholderContainer, style]}>
        <Ionicons name="image-outline" size={24} color="#ccc" />
      </View>
    );
  }

  return (
    <View style={[styles.container, style]}>
      {loading && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="small" color={COLORS.primary} />
        </View>
      )}
      {imageUrl && (
        <Image
          source={{ uri: imageUrl }}
          style={[styles.image, { opacity: loading ? 0 : 1 }]}
          resizeMode={resizeMode}
          onLoad={handleLoad}
          onError={handleError}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    overflow: "hidden",
    backgroundColor: "#f0f0f0",
    position: "relative",
  },
  image: {
    width: "100%",
    height: "100%",
  },
  placeholderContainer: {
    backgroundColor: "#f0f0f0",
    justifyContent: "center",
    alignItems: "center",
  },
  loadingContainer: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: "center",
    alignItems: "center",
  },
});

export default ProductImage;
