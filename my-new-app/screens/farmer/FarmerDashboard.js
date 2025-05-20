import React, { useEffect } from "react";
import { View, Text, StyleSheet } from "react-native";

const FarmerDashboard = ({ navigation }) => {
  // Set navigation options to remove back button
  useEffect(() => {
    navigation.setOptions({
      headerLeft: () => null,
    });
  }, [navigation]);

  return (
    <View style={styles.container}>
      <Text>Farmer Dashboard</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
});

export default FarmerDashboard;
