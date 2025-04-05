import React from "react";
import {
  StyleSheet,
  View,
  ScrollView,
  TouchableOpacity,
  Image,
  Platform,
  StatusBar,
} from "react-native";
import { useRouter } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { Ionicons } from "@expo/vector-icons";

// Define color scheme with TypeScript interface
interface ColorScheme {
  primary: string;
  secondary: string;
  accent: string;
  light: string;
  dark: string;
  text: string;
  muted: string;
  cardBg: string;
  shadow: string;
  overlay: string;
}

// Enhanced color scheme with better contrast
const COLORS: ColorScheme = {
  primary: "#1B5E20", // Dark green
  secondary: "#FFC107", // Amber
  accent: "#E65100", // Deep orange
  light: "#FFFFFF", // White
  dark: "#0D2E11", // Darker green
  text: "#263238", // Dark blue-gray
  muted: "#78909C", // Blue-gray
  cardBg: "#F9FBF7", // Very light green
  shadow: "#000000", // Black
  overlay: "rgba(27, 94, 32, 0.85)", // Primary with opacity
};

// Define service interface with additional metadata
interface ServiceCategory {
  id: string;
  title: string;
  description: string;
  icon: keyof typeof Ionicons.glyphMap;
  services: string[];
  color?: string;
}

// Organized services with unique IDs
const SERVICE_CATEGORIES: ServiceCategory[] = [
  {
    id: "farmer-registration",
    title: "Farmer Registration & Profiling",
    description:
      "Register as a farmer to access government support programs and agricultural services.",
    icon: "person-add",
    services: [
      "Farmer detail registration",
      "Farm type categorization",
      "Crop variety documentation",
      "Certification management",
      "Income tracking",
    ],
  },
  {
    id: "product-management",
    title: "Agricultural Product Management",
    description:
      "Services to help farmers manage, list, and sell their agricultural products.",
    icon: "leaf",
    services: [
      "Product listing and approval",
      "Inventory management",
      "Price setting assistance",
      "Product categorization",
      "Image uploading for products",
    ],
  },
  {
    id: "market-connection",
    title: "Market Connection Services",
    description:
      "Connect farmers with consumers through our marketplace platform.",
    icon: "basket",
    services: [
      "Online marketplace access",
      "Order management",
      "Consumer connection",
      "Product visibility",
      "Market price tracking",
    ],
  },
  {
    id: "logistics",
    title: "Logistics & Delivery",
    description:
      "Transportation and delivery services for agricultural products.",
    icon: "car",
    services: [
      "Driver assignment",
      "Pickup scheduling",
      "Delivery tracking",
      "Vehicle type matching",
      "Load capacity planning",
    ],
  },
  {
    id: "payments",
    title: "Payment Processing",
    description: "Secure payment options for agricultural transactions.",
    icon: "cash",
    services: [
      "Multiple payment methods",
      "Transaction tracking",
      "Payment verification",
      "Receipt generation",
    ],
  },
  {
    id: "feedback",
    title: "Feedback & Quality Control",
    description:
      "Ensure product quality through customer feedback and product ratings.",
    icon: "star",
    services: [
      "Product rating system",
      "Customer feedback collection",
      "Farmer response management",
      "Quality improvement suggestions",
    ],
  },
  {
    id: "admin-support",
    title: "Administrative Support",
    description:
      "Support services provided by the Municipal Agriculture Office.",
    icon: "document-text",
    services: [
      "Account management",
      "Activity logging",
      "Agricultural updates",
      "Information dissemination",
    ],
  },
];

export default function ServicesScreen() {
  const router = useRouter();

  return (
    <View style={styles.outerContainer}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={COLORS.primary}
        translucent={Platform.OS === "android"}
      />

      {/* Fixed Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
          accessibilityLabel="Go back"
          accessibilityRole="button"
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>
          Municipal Agriculture Services
        </ThemedText>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
        showsVerticalScrollIndicator={false}
        bounces={false}
      >
        {/* Hero Section */}
        <View style={styles.heroContainer}>
          <Image
            source={{
              uri: "https://images.unsplash.com/photo-1539177357530-51458dfc23c0?auto=format&fit=crop&q=80&w=870",
            }}
            style={styles.heroImage}
            resizeMode="cover"
            accessibilityIgnoresInvertColors
          />
          <View style={styles.heroOverlay}>
            <ThemedText style={styles.heroTitle}>
              Supporting Negros Oriental Farmers
            </ThemedText>
            <ThemedText style={styles.heroSubtitle}>
              Our comprehensive services to help farmers succeed
            </ThemedText>
          </View>
        </View>

        {/* Main Content */}
        <View style={styles.mainContent}>
          {/* Introduction */}
          <View style={styles.introSection}>
            <ThemedText style={styles.sectionTitle}>
              Our Agricultural Services
            </ThemedText>
            <ThemedText style={styles.sectionDescription}>
              The Municipal Agriculture Office provides a range of services
              designed to support local farmers in production, marketing, and
              distribution of agricultural products.
            </ThemedText>
          </View>

          {/* Service Categories */}
          <View style={styles.servicesSection}>
            {SERVICE_CATEGORIES.map((category) => (
              <ServiceCard key={category.id} category={category} />
            ))}
          </View>

          {/* Contact Information */}
          <View style={styles.contactSection}>
            <ThemedText style={styles.sectionTitle}>
              Contact Information
            </ThemedText>

            <ContactItem icon="location" text="Palinpinon, Negros Oriental" />
            <ContactItem icon="call" text="(035) 225-0000" />
            <ContactItem icon="mail" text="agriculture@negor.gov.ph" />
            <ContactItem
              icon="time"
              text="Office Hours: Monday to Friday, 8:00 AM - 5:00 PM"
            />
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

// Reusable Service Card Component
const ServiceCard = ({ category }: { category: ServiceCategory }) => (
  <View style={styles.serviceCard}>
    <View style={styles.cardHeader}>
      <View style={styles.cardIcon}>
        <Ionicons
          name={category.icon}
          size={28}
          color={COLORS.primary}
          accessibilityIgnoresInvertColors
        />
      </View>
      <ThemedText style={styles.cardTitle}>{category.title}</ThemedText>
    </View>

    <ThemedText style={styles.cardDescription}>
      {category.description}
    </ThemedText>

    <View style={styles.servicesList}>
      {category.services.map((service, index) => (
        <View
          key={`${category.id}-service-${index}`}
          style={styles.serviceItem}
        >
          <Ionicons
            name="checkmark-circle"
            size={16}
            color={COLORS.accent}
            accessibilityIgnoresInvertColors
          />
          <ThemedText style={styles.serviceText}>{service}</ThemedText>
        </View>
      ))}
    </View>
  </View>
);

// Reusable Contact Item Component
const ContactItem = ({
  icon,
  text,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  text: string;
}) => (
  <View style={styles.contactItem}>
    <Ionicons
      name={icon}
      size={20}
      color={COLORS.primary}
      accessibilityIgnoresInvertColors
    />
    <ThemedText style={styles.contactText}>{text}</ThemedText>
  </View>
);

const styles = StyleSheet.create({
  // Layout
  outerContainer: {
    flex: 1,
    backgroundColor: COLORS.primary, // Match header color for status bar area
  },
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingTop:
      Platform.OS === "ios"
        ? 40 // Reduced from 50 to 40
        : StatusBar.currentHeight
        ? StatusBar.currentHeight + 5 // Reduced padding
        : 20, // Reduced from 30 to 20
    paddingBottom: 12, // Reduced from 16 to 12
    backgroundColor: COLORS.primary,
    zIndex: 2,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.2,
        shadowRadius: 4,
      },
      android: {
        elevation: 4,
      },
    }),
  },
  backButton: {
    padding: 8,
  },
  headerTitle: {
    fontSize: 18,
    color: COLORS.light,
    fontWeight: "700",
    textAlign: "center",
  },
  headerSpacer: {
    width: 40,
  },
  scrollView: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  contentContainer: {
    flexGrow: 1,
    paddingBottom: 40, // Increased from 20 to 40
    paddingTop: 0, // Explicit zero padding at the top
  },

  // Hero Section
  heroContainer: {
    height: 85, // Reduced from 220 to 180
    position: "relative",
  },
  heroImage: {
    width: "100%",
    height: "100%",
  },
  heroOverlay: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: COLORS.overlay,
    padding: 20,
  },
  heroTitle: {
    fontSize: 22,
    fontWeight: "700",
    color: COLORS.light,
    marginBottom: 4,
  },
  heroSubtitle: {
    fontSize: 16,
    color: "rgba(255, 255, 255, 0.9)",
  },

  // Main Content
  mainContent: {
    paddingHorizontal: 16,
  },

  // Sections
  introSection: {
    marginVertical: 16, // Reduced from 24 to 16
  },
  servicesSection: {
    marginBottom: 16, // Reduced from 24 to 16
  },
  contactSection: {
    marginBottom: 40, // Increased from 20 to 40
    paddingBottom: 20, // Added padding for better visibility
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: "700",
    color: COLORS.primary,
    marginBottom: 12,
  },
  sectionDescription: {
    fontSize: 16,
    color: COLORS.text,
    lineHeight: 24,
  },

  // Service Card
  serviceCard: {
    backgroundColor: COLORS.cardBg,
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 12,
  },
  cardIcon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "rgba(27, 94, 32, 0.1)",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 16,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: "600",
    color: COLORS.primary,
    flex: 1,
  },
  cardDescription: {
    fontSize: 15,
    color: COLORS.text,
    marginBottom: 16,
    lineHeight: 22,
  },

  // Services List
  servicesList: {
    marginTop: 8,
  },
  serviceItem: {
    flexDirection: "row",
    alignItems: "flex-start",
    marginBottom: 8,
  },
  serviceText: {
    fontSize: 14,
    color: COLORS.text,
    marginLeft: 8,
    flex: 1,
    lineHeight: 20,
  },

  // Contact Section
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 12,
  },
  contactText: {
    fontSize: 15,
    color: COLORS.text,
    marginLeft: 12,
    flex: 1,
    lineHeight: 22,
  },
});
