import React, { useState, useCallback } from "react";
import { View, Text, StyleSheet, FlatList, RefreshControl } from "react-native";
import FarmerRecentProducts from "./FarmerRecentProducts";

const FarmerDashboardContent = ({
  userData,
  products,
  stats,
  onRefresh,
  refreshing,
}) => {
  const renderItem = ({ item }) => {
    switch (item.type) {
      case "header":
        return (
          <View style={styles.headerSection}>
            <Text style={styles.welcomeText}>
              Welcome, {userData?.first_name || "Farmer"}!
            </Text>
            <Text style={styles.subText}>{new Date().toDateString()}</Text>
          </View>
        );
      case "stats":
        return (
          <View style={styles.statsContainer}>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats?.productCount || 0}</Text>
              <Text style={styles.statLabel}>Products</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats?.orderCount || 0}</Text>
              <Text style={styles.statLabel}>Orders</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>
                ₱{stats?.totalSales?.toFixed(2) || "0.00"}
              </Text>
              <Text style={styles.statLabel}>Sales</Text>
            </View>
          </View>
        );
      case "recentProducts":
        return <FarmerRecentProducts products={products} />;
      default:
        return <Text>Unknown section type</Text>;
    }
  };

  const sections = [
    { id: "1", type: "header" },
    { id: "2", type: "stats" },
    { id: "3", type: "recentProducts" },
  ];

  return (
    <FlatList
      data={sections}
      renderItem={renderItem}
      keyExtractor={(item) => item.id}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
      }
    />
  );
};

const styles = StyleSheet.create({
  headerSection: {
    padding: 20,
    backgroundColor: "#f8f9fa",
  },
  welcomeText: {
    fontSize: 24,
    fontWeight: "bold",
    marginBottom: 5,
  },
  subText: {
    fontSize: 14,
    color: "#6c757d",
  },
  statsContainer: {
    flexDirection: "row",
    justifyContent: "space-around",
    padding: 15,
  },
  statCard: {
    backgroundColor: "#ffffff",
    borderRadius: 10,
    padding: 15,
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.22,
    shadowRadius: 2.22,
    elevation: 3,
    width: "30%",
  },
  statValue: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 5,
  },
  statLabel: {
    fontSize: 14,
    color: "#6c757d",
  },
});

export default FarmerDashboardContent;
