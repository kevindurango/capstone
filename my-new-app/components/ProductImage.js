import React, { useState, useEffect } from "react";
import { View, Image, Text, StyleSheet } from "react-native";
import { processImagePath } from "../utils/config";

const ProductImage = ({ source, style, resizeMode = "cover", productId }) => {
  const [imageUrl, setImageUrl] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (source) {
      const imagePath = typeof source === "string" ? source : source.uri;

      if (productId) {
        console.log(
          `[ProductImage] Product ${productId} original path:`,
          imagePath
        );
      }

      // Process the image path to get a full URL
      const fullUrl = processImagePath(imagePath);

      if (productId) {
        console.log(`[ProductImage] Product ${productId} trying URL:`, fullUrl);
      }

      setImageUrl(fullUrl);
      setLoading(false);
    }
  }, [source, productId]);

  if (loading) {
    return (
      <View style={[styles.container, style]}>
        <Text>Loading...</Text>
      </View>
    );
  }

  if (error || !imageUrl) {
    return (
      <View style={[styles.container, style]}>
        <Text>Image not available</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Image
        source={{ uri: imageUrl }}
        style={[styles.image, style]}
        resizeMode={resizeMode}
        onError={() => setError(true)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    overflow: "hidden",
  },
  image: {
    width: "100%",
    height: "100%",
  },
});

export default ProductImage;
