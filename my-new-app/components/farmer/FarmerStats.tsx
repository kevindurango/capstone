import React from "react";
import {
  StyleSheet,
  View,
  Dimensions,
  Animated,
  TouchableOpacity,
} from "react-native";
import { ThemedText } from "@/components/ThemedText";
import { COLORS } from "@/constants/Colors";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";

interface StatsProps {
  stats: {
    totalProducts: number;
    pendingProducts: number;
    approvedProducts: number;
    rejectedProducts: number;
  };
}

export function FarmerStats({ stats }: StatsProps) {
  // Calculate approval percentage
  const approvalPercentage = stats.totalProducts
    ? Math.round((stats.approvedProducts / stats.totalProducts) * 100)
    : 0;

  // Animation value for progress bar
  const progressAnim = React.useRef(new Animated.Value(0)).current;

  React.useEffect(() => {
    // Animate the progress bar on component mount
    Animated.timing(progressAnim, {
      toValue: 1,
      duration: 800,
      useNativeDriver: false,
    }).start();
  }, []);

  // Get suitable color based on approval rate
  const getApprovalColor = () => {
    if (approvalPercentage >= 70) return "#4CAF50"; // Green instead of COLORS.success
    if (approvalPercentage >= 40) return "#FFC107"; // Yellow instead of COLORS.warning
    return approvalPercentage > 0 ? "#F44336" : COLORS.muted; // Red instead of COLORS.danger
  };

  // Calculate donut chart segments
  const calculateSegment = (value: number) => {
    if (stats.totalProducts === 0) return 0;
    return (value / stats.totalProducts) * 100;
  };

  const approvedSegment = calculateSegment(stats.approvedProducts);
  const pendingSegment = calculateSegment(stats.pendingProducts);
  const rejectedSegment = calculateSegment(stats.rejectedProducts);

  return (
    <View style={styles.container}>
      <View style={styles.headerRow}>
        <ThemedText style={styles.title}>Product Statistics</ThemedText>
        <View
          style={[
            styles.badgeContainer,
            { backgroundColor: getApprovalColor() },
          ]}
        >
          <ThemedText style={styles.badgeText}>
            {approvalPercentage}% Approval Rate
          </ThemedText>
        </View>
      </View>

      <View style={styles.overviewContainer}>
        <View style={styles.donutChartContainer}>
          <View style={styles.donutChart}>
            <View style={styles.donutBackground}>
              {stats.totalProducts > 0 ? (
                <>
                  {/* SVG-like donut chart segments created with View */}
                  <View
                    style={[
                      styles.donutSegment,
                      {
                        backgroundColor: COLORS.success,
                        transform: [{ rotate: "0deg" }],
                        zIndex: 3,
                      },
                      approvedSegment >= 50 && {
                        width: "100%",
                        height: "100%",
                        borderRadius: 40,
                      },
                    ]}
                  />

                  <View
                    style={[
                      styles.donutSegment,
                      {
                        backgroundColor: COLORS.warning,
                        transform: [{ rotate: `${approvedSegment * 3.6}deg` }],
                        zIndex: 2,
                      },
                      pendingSegment >= 50 && {
                        width: "100%",
                        height: "100%",
                        borderRadius: 40,
                      },
                    ]}
                  />

                  <View
                    style={[
                      styles.donutSegment,
                      {
                        backgroundColor: COLORS.danger,
                        transform: [
                          {
                            rotate: `${(approvedSegment + pendingSegment) * 3.6}deg`,
                          },
                        ],
                        zIndex: 1,
                      },
                    ]}
                  />
                </>
              ) : (
                <View
                  style={[
                    styles.donutSegment,
                    { backgroundColor: COLORS.muted },
                  ]}
                />
              )}
              {/* Inner white circle for donut effect */}
              <View style={styles.donutHole}>
                <ThemedText style={styles.donutCenterText}>
                  {stats.totalProducts}
                </ThemedText>
                <ThemedText style={styles.donutCenterLabel}>
                  Products
                </ThemedText>
              </View>
            </View>
          </View>

          <View style={styles.legendContainerVertical}>
            <View style={styles.legendItem}>
              <View
                style={[styles.legendDot, { backgroundColor: COLORS.success }]}
              />
              <ThemedText style={styles.legendText}>
                Approved ({stats.approvedProducts})
              </ThemedText>
            </View>
            <View style={styles.legendItem}>
              <View
                style={[styles.legendDot, { backgroundColor: COLORS.warning }]}
              />
              <ThemedText style={styles.legendText}>
                Pending ({stats.pendingProducts})
              </ThemedText>
            </View>
            <View style={styles.legendItem}>
              <View
                style={[styles.legendDot, { backgroundColor: COLORS.danger }]}
              />
              <ThemedText style={styles.legendText}>
                Rejected ({stats.rejectedProducts})
              </ThemedText>
            </View>
          </View>
        </View>
      </View>

      <View style={styles.statsGrid}>
        <TouchableOpacity style={[styles.statCard, styles.totalCard]}>
          <View
            style={[
              styles.statIconContainer,
              { backgroundColor: "rgba(27, 94, 32, 0.1)" },
            ]}
          >
            <Ionicons name="layers" size={20} color={COLORS.primary} />
          </View>
          <View style={styles.statTextContainer}>
            <ThemedText style={styles.statValue}>
              {stats.totalProducts}
            </ThemedText>
            <ThemedText style={styles.statLabel}>Total Products</ThemedText>
          </View>
          <MaterialIcons name="chevron-right" size={20} color="#ccc" />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.statCard, styles.approvedCard]}>
          <View
            style={[
              styles.statIconContainer,
              { backgroundColor: "rgba(76, 175, 80, 0.1)" },
            ]}
          >
            <Ionicons
              name="checkmark-circle"
              size={20}
              color={COLORS.success}
            />
          </View>
          <View style={styles.statTextContainer}>
            <ThemedText style={[styles.statValue, { color: COLORS.success }]}>
              {stats.approvedProducts}
            </ThemedText>
            <ThemedText style={styles.statLabel}>Active Products</ThemedText>
          </View>
          <MaterialIcons name="chevron-right" size={20} color="#ccc" />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.statCard, styles.pendingCard]}>
          <View
            style={[
              styles.statIconContainer,
              { backgroundColor: "rgba(255, 152, 0, 0.1)" },
            ]}
          >
            <Ionicons name="time" size={20} color={COLORS.warning} />
          </View>
          <View style={styles.statTextContainer}>
            <ThemedText style={[styles.statValue, { color: COLORS.warning }]}>
              {stats.pendingProducts}
            </ThemedText>
            <ThemedText style={styles.statLabel}>Pending Review</ThemedText>
          </View>
          <MaterialIcons name="chevron-right" size={20} color="#ccc" />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.statCard, styles.rejectedCard]}>
          <View
            style={[
              styles.statIconContainer,
              { backgroundColor: "rgba(244, 67, 54, 0.1)" },
            ]}
          >
            <Ionicons name="alert-circle" size={20} color={COLORS.danger} />
          </View>
          <View style={styles.statTextContainer}>
            <ThemedText style={[styles.statValue, { color: COLORS.danger }]}>
              {stats.rejectedProducts}
            </ThemedText>
            <ThemedText style={styles.statLabel}>Needs Updates</ThemedText>
          </View>
          <MaterialIcons name="chevron-right" size={20} color="#ccc" />
        </TouchableOpacity>
      </View>

      {/* Progress bar showing approval rate visually */}
      <View style={styles.progressContainer}>
        <View style={styles.progressLabelContainer}>
          <ThemedText style={styles.progressLabel}>
            Product Status Distribution
          </ThemedText>
          <ThemedText style={styles.progressPercentage}>
            {approvalPercentage}% Approved
          </ThemedText>
        </View>
        <View style={styles.progressBarContainer}>
          <View style={styles.progressBar}>
            {stats.totalProducts > 0 && (
              <>
                <Animated.View
                  style={[
                    styles.progressSegment,
                    styles.approvedSegment,
                    {
                      flex: stats.approvedProducts / stats.totalProducts,
                      width: progressAnim.interpolate({
                        inputRange: [0, 1],
                        outputRange: ["0%", `${approvedSegment}%`],
                      }),
                    },
                  ]}
                />
                <Animated.View
                  style={[
                    styles.progressSegment,
                    styles.pendingSegment,
                    {
                      flex: stats.pendingProducts / stats.totalProducts,
                      width: progressAnim.interpolate({
                        inputRange: [0, 1],
                        outputRange: ["0%", `${pendingSegment}%`],
                      }),
                    },
                  ]}
                />
                <Animated.View
                  style={[
                    styles.progressSegment,
                    styles.rejectedSegment,
                    {
                      flex: stats.rejectedProducts / stats.totalProducts,
                      width: progressAnim.interpolate({
                        inputRange: [0, 1],
                        outputRange: ["0%", `${rejectedSegment}%`],
                      }),
                    },
                  ]}
                />
              </>
            )}
          </View>
        </View>
      </View>
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
  badgeContainer: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    color: "#fff",
    fontWeight: "bold",
    fontSize: 12,
  },
  overviewContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 24,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
  },
  donutChartContainer: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    width: "100%",
  },
  donutChart: {
    width: 100,
    height: 100,
    justifyContent: "center",
    alignItems: "center",
  },
  donutBackground: {
    width: 100,
    height: 100,
    borderRadius: 50,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f0f0f0",
    position: "relative",
    overflow: "hidden",
  },
  donutSegment: {
    position: "absolute",
    width: "100%",
    height: "100%",
    borderRadius: 0,
    backgroundColor: COLORS.primary,
  },
  donutHole: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
    zIndex: 10,
  },
  donutCenterText: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  donutCenterLabel: {
    fontSize: 10,
    color: COLORS.muted,
  },
  statsGrid: {
    marginBottom: 16,
  },
  statCard: {
    backgroundColor: "#fff",
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#f0f0f0",
  },
  statIconContainer: {
    width: 36,
    height: 36,
    borderRadius: 18,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  totalCard: {
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
  },
  approvedCard: {
    borderLeftWidth: 3,
    borderLeftColor: COLORS.success,
  },
  pendingCard: {
    borderLeftWidth: 3,
    borderLeftColor: COLORS.warning,
  },
  rejectedCard: {
    borderLeftWidth: 3,
    borderLeftColor: COLORS.danger,
  },
  statTextContainer: {
    flex: 1,
  },
  statValue: {
    fontSize: 18,
    fontWeight: "bold",
    color: COLORS.primary,
  },
  statLabel: {
    fontSize: 12,
    color: COLORS.muted,
  },
  progressContainer: {
    marginTop: 8,
  },
  progressLabelContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  progressLabel: {
    fontSize: 14,
    color: COLORS.text,
  },
  progressPercentage: {
    fontSize: 12,
    fontWeight: "600",
    color: COLORS.primary,
  },
  progressBarContainer: {
    backgroundColor: "#f0f0f0",
    height: 8,
    borderRadius: 4,
    overflow: "hidden",
  },
  progressBar: {
    height: "100%",
    flexDirection: "row",
    borderRadius: 4,
    position: "relative",
  },
  progressSegment: {
    height: "100%",
    position: "absolute",
  },
  approvedSegment: {
    backgroundColor: COLORS.success,
    left: 0,
  },
  pendingSegment: {
    backgroundColor: COLORS.warning,
    left: 0,
  },
  rejectedSegment: {
    backgroundColor: COLORS.danger,
    left: 0,
  },
  legendContainerVertical: {
    justifyContent: "space-between",
    paddingLeft: 16,
  },
  legendItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
  },
  legendDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 6,
  },
  legendText: {
    fontSize: 12,
    color: COLORS.text,
  },
});
