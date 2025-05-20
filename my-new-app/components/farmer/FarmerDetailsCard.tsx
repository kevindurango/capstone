import React from "react";
import { View, Text, StyleSheet, TouchableOpacity, Image } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";
import { getImageUrl } from "@/constants/Config";

interface FarmerDetailCardProps {
  farmDetails: any;
  onEdit?: () => void;
}

const FarmerDetailCard: React.FC<FarmerDetailCardProps> = ({
  farmDetails,
  onEdit,
}) => {
  if (!farmDetails) {
    return (
      <View style={styles.container}>
        <Text style={styles.noDataText}>No farm details available</Text>
        {onEdit && (
          <TouchableOpacity style={styles.editButton} onPress={onEdit}>
            <Ionicons name="add-circle-outline" size={20} color="#fff" />
            <Text style={styles.editButtonText}>Add Farm Details</Text>
          </TouchableOpacity>
        )}
      </View>
    );
  }

  const hasFarmName =
    farmDetails.farm_name && farmDetails.farm_name.trim() !== "";

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View>
          <Text style={styles.farmName}>
            {hasFarmName ? farmDetails.farm_name : "Unnamed Farm"}
          </Text>
          {farmDetails.farm_type && (
            <View style={styles.farmTypeBadge}>
              <Text style={styles.farmTypeText}>{farmDetails.farm_type}</Text>
            </View>
          )}
        </View>
        {onEdit && (
          <TouchableOpacity style={styles.editButtonSmall} onPress={onEdit}>
            <Ionicons name="create-outline" size={18} color={COLORS.primary} />
          </TouchableOpacity>
        )}
      </View>

      <View style={styles.divider} />

      <View style={styles.detailsContainer}>
        <View style={styles.detailRow}>
          <View style={styles.detailIconContainer}>
            <Ionicons name="person" size={18} color={COLORS.primary} />
          </View>
          <View style={styles.detailContent}>
            <Text style={styles.detailLabel}>Farmer</Text>
            <Text style={styles.detailValue}>
              {farmDetails.farmer?.full_name || "Not specified"}
            </Text>
          </View>
        </View>

        <View style={styles.detailRow}>
          <View style={styles.detailIconContainer}>
            <Ionicons name="location" size={18} color={COLORS.primary} />
          </View>
          <View style={styles.detailContent}>
            <Text style={styles.detailLabel}>Location</Text>
            <Text style={styles.detailValue}>
              {farmDetails.farm_location || "Not specified"}
              {farmDetails.barangay_name && `, ${farmDetails.barangay_name}`}
            </Text>
          </View>
        </View>

        <View style={styles.detailRow}>
          <View style={styles.detailIconContainer}>
            <Ionicons name="resize" size={18} color={COLORS.primary} />
          </View>
          <View style={styles.detailContent}>
            <Text style={styles.detailLabel}>Farm Size</Text>
            <Text style={styles.detailValue}>
              {farmDetails.farm_size
                ? `${farmDetails.farm_size} hectares`
                : "Not specified"}
            </Text>
          </View>
        </View>

        {farmDetails.summary && (
          <View style={styles.statContainer}>
            <View style={styles.statItem}>
              <Text style={styles.statValue}>
                {farmDetails.summary.field_count || 0}
              </Text>
              <Text style={styles.statLabel}>Fields</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={styles.statValue}>
                {farmDetails.summary.product_count || 0}
              </Text>
              <Text style={styles.statLabel}>Products</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={styles.statValue}>
                {farmDetails.summary.total_field_size || 0}
              </Text>
              <Text style={styles.statLabel}>Total Area (ha)</Text>
            </View>
          </View>
        )}
      </View>

      {farmDetails.certifications && (
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Certifications</Text>
          <Text style={styles.sectionText}>{farmDetails.certifications}</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    elevation: 2,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    marginBottom: 12,
  },
  farmName: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 4,
  },
  farmTypeBadge: {
    backgroundColor: COLORS.primary + "20",
    paddingHorizontal: 10,
    paddingVertical: 2,
    borderRadius: 12,
    alignSelf: "flex-start",
  },
  farmTypeText: {
    fontSize: 12,
    color: COLORS.primary,
    fontWeight: "500",
  },
  divider: {
    height: 1,
    backgroundColor: "#eee",
    marginVertical: 12,
  },
  detailsContainer: {
    marginBottom: 16,
  },
  detailRow: {
    flexDirection: "row",
    marginBottom: 10,
  },
  detailIconContainer: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: COLORS.primary + "15",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 10,
  },
  detailContent: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 12,
    color: "#888",
    marginBottom: 2,
  },
  detailValue: {
    fontSize: 14,
    color: "#333",
  },
  statContainer: {
    flexDirection: "row",
    justifyContent: "space-around",
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: "#eee",
  },
  statItem: {
    alignItems: "center",
  },
  statValue: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  statLabel: {
    fontSize: 12,
    color: "#888",
  },
  editButton: {
    backgroundColor: COLORS.primary,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    marginTop: 10,
  },
  editButtonText: {
    color: "#fff",
    fontWeight: "bold",
    marginLeft: 8,
  },
  editButtonSmall: {
    padding: 8,
    borderRadius: 20,
    backgroundColor: "#f5f5f5",
  },
  noDataText: {
    fontSize: 16,
    color: "#888",
    textAlign: "center",
    marginBottom: 16,
  },
  sectionContainer: {
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: "#eee",
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: "600",
    color: "#333",
    marginBottom: 6,
  },
  sectionText: {
    fontSize: 14,
    color: "#555",
    lineHeight: 20,
  },
});

export default FarmerDetailCard;
