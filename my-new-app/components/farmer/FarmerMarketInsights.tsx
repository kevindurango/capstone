// filepath: c:\xampp\htdocs\capstone\my-new-app\components\farmer\FarmerMarketInsights.tsx
import React from "react";
import { StyleSheet, View, TouchableOpacity, ScrollView } from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { Ionicons, FontAwesome5 } from "@expo/vector-icons";

interface MarketInsightsProps {
  // Add any props if needed in the future
}

export function FarmerMarketInsights({}: MarketInsightsProps) {
  // Get current month for seasonal recommendations
  const currentMonth = new Date().getMonth() + 1; // 1-12

  // Determine current season based on month
  const getCurrentSeason = () => {
    if (currentMonth >= 11 || currentMonth <= 4) return "Dry Season";
    return "Wet Season";
  };

  // Get crops recommended for the current season
  const getRecommendedCrops = () => {
    const season = getCurrentSeason();
    if (season === "Dry Season") {
      return [
        { name: "Upland Rice", icon: "seedling" },
        { name: "Sweet Potato", icon: "carrot" },
        { name: "Cassava", icon: "seedling" },
        { name: "Mung Beans", icon: "seedling" },
      ];
    } else {
      return [
        { name: "Lowland Rice", icon: "seedling" },
        { name: "Leafy Vegetables", icon: "leaf" },
        { name: "Root Crops", icon: "carrot" },
        { name: "Tropical Fruits", icon: "apple-alt" },
      ];
    }
  };

  const season = getCurrentSeason();
  const recommendedCrops = getRecommendedCrops();

  return (
    <View style={styles.container}>
      <View style={styles.headerRow}>
        <ThemedText style={styles.title}>Market Insights</ThemedText>
        <TouchableOpacity style={styles.refreshButton}>
          <Ionicons name="refresh" size={18} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      <View style={styles.seasonCard}>
        <View style={styles.seasonHeader}>
          <View style={styles.seasonIconContainer}>
            <Ionicons
              name={season === "Dry Season" ? "sunny" : "rainy"}
              size={24}
              color="#fff"
            />
          </View>
          <View style={styles.seasonTextContainer}>
            <ThemedText style={styles.currentSeasonLabel}>
              Current Season
            </ThemedText>
            <ThemedText style={styles.currentSeasonText}>{season}</ThemedText>
          </View>
        </View>

        <View style={styles.seasonInfoContainer}>
          <ThemedText style={styles.seasonDescription}>
            {season === "Dry Season"
              ? "Hot and dry period ideal for drought-resistant crops. Average temperature ranges from 26-33°C with minimal rainfall."
              : "Rainy period with high humidity, suitable for moisture-loving crops. Average rainfall of 200-400mm per month."}
          </ThemedText>
        </View>
      </View>

      <View style={styles.recommendationsContainer}>
        <ThemedText style={styles.recommendationsTitle}>
          Recommended Crops for {season}
        </ThemedText>

        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.cropsScrollContent}
        >
          {recommendedCrops.map((crop, index) => (
            <View key={index} style={styles.cropItem}>
              <View style={styles.cropIconContainer}>
                <FontAwesome5
                  name={crop.icon}
                  size={16}
                  color={COLORS.primary}
                />
              </View>
              <ThemedText style={styles.cropName}>{crop.name}</ThemedText>
            </View>
          ))}
        </ScrollView>
      </View>

      <View style={styles.marketTrendsContainer}>
        <ThemedText style={styles.marketTrendsTitle}>Market Trends</ThemedText>

        <View style={styles.trendItem}>
          <View
            style={[
              styles.trendIconContainer,
              { backgroundColor: "rgba(76, 175, 80, 0.1)" },
            ]}
          >
            <Ionicons name="trending-up" size={20} color={COLORS.primary} />
          </View>
          <View style={styles.trendTextContainer}>
            <ThemedText style={styles.trendCrop}>Valencia Red Rice</ThemedText>
            <ThemedText style={styles.trendDescription}>
              High demand this month with 25% increase in orders
            </ThemedText>
          </View>
        </View>

        <View style={styles.trendItem}>
          <View
            style={[
              styles.trendIconContainer,
              { backgroundColor: "rgba(255, 152, 0, 0.1)" },
            ]}
          >
            <Ionicons name="star" size={20} color="#FF9800" />
          </View>
          <View style={styles.trendTextContainer}>
            <ThemedText style={styles.trendCrop}>Organic Vegetables</ThemedText>
            <ThemedText style={styles.trendDescription}>
              Featured products this season - consider adding to your inventory
            </ThemedText>
          </View>
        </View>
      </View>

      <TouchableOpacity style={styles.viewMoreButton}>
        <ThemedText style={styles.viewMoreText}>
          View Detailed Market Report
        </ThemedText>
        <Ionicons name="arrow-forward" size={16} color={COLORS.primary} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  headerRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  refreshButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "rgba(27, 94, 32, 0.08)",
    justifyContent: "center",
    alignItems: "center",
  },
  seasonCard: {
    backgroundColor: "#f9f9f9",
    borderRadius: 12,
    overflow: "hidden",
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#f0f0f0",
  },
  seasonHeader: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 12,
    paddingHorizontal: 16,
    backgroundColor: COLORS.primary,
  },
  seasonIconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "rgba(255, 255, 255, 0.2)",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  seasonTextContainer: {
    flex: 1,
  },
  currentSeasonLabel: {
    color: "rgba(255, 255, 255, 0.8)",
    fontSize: 12,
    marginBottom: 2,
  },
  currentSeasonText: {
    color: "#ffffff",
    fontSize: 18,
    fontWeight: "bold",
  },
  seasonInfoContainer: {
    padding: 16,
  },
  seasonDescription: {
    fontSize: 14,
    color: COLORS.text,
    lineHeight: 20,
  },
  recommendationsContainer: {
    marginBottom: 16,
  },
  recommendationsTitle: {
    fontSize: 16,
    fontWeight: "600",
    color: COLORS.text,
    marginBottom: 12,
  },
  cropsScrollContent: {
    paddingVertical: 8,
    paddingRight: 8,
  },
  cropItem: {
    alignItems: "center",
    marginRight: 16,
    width: 80,
  },
  cropIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: "rgba(27, 94, 32, 0.08)",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 8,
  },
  cropName: {
    fontSize: 12,
    color: COLORS.text,
    textAlign: "center",
  },
  marketTrendsContainer: {
    marginBottom: 16,
  },
  marketTrendsTitle: {
    fontSize: 16,
    fontWeight: "600",
    color: COLORS.text,
    marginBottom: 12,
  },
  trendItem: {
    flexDirection: "row",
    alignItems: "center",
    padding: 12,
    backgroundColor: "#f9faf7",
    borderRadius: 12,
    marginBottom: 8,
  },
  trendIconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  trendTextContainer: {
    flex: 1,
  },
  trendCrop: {
    fontSize: 14,
    fontWeight: "600",
    color: COLORS.text,
    marginBottom: 4,
  },
  trendDescription: {
    fontSize: 12,
    color: COLORS.muted,
  },
  viewMoreButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#f0f0f0",
  },
  viewMoreText: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: "600",
    marginRight: 8,
  },
});
