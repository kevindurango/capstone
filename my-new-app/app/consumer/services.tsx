import React, { useState, useEffect } from "react";
import {
  StyleSheet,
  View,
  ScrollView,
  TouchableOpacity,
  Image,
  Platform,
  StatusBar,
  Dimensions,
} from "react-native";
import { useRouter } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { ThemedView } from "@/components/ThemedView";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import { COLORS } from "@/constants/Colors";
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withTiming,
  withDelay,
  FadeIn,
  FadeInDown,
  SlideInRight,
} from "react-native-reanimated";
import { Stack } from "expo-router";

const { width } = Dimensions.get("window");

// Constants for spacing and sizing
const SPACING = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  xxxl: 40,
};

// Define service interface
interface ServiceCategory {
  id: string;
  title: string;
  description: string;
  icon: keyof typeof Ionicons.glyphMap;
  services: string[];
}

// Service categories data
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

// Contact information
const CONTACT_INFO = [
  { icon: "location", text: "Palinpinon, Negros Oriental" },
  { icon: "call", text: "(035) 225-0000" },
  { icon: "mail", text: "agriculture@negor.gov.ph" },
  { icon: "time", text: "Office Hours: Monday to Friday, 8:00 AM - 5:00 PM" },
];

// Hero section props
const HERO_SECTION = {
  imageUrl:
    "https://images.unsplash.com/photo-1539177357530-51458dfc23c0?auto=format&fit=crop&q=80&w=870",
  title: "Supporting Negros Oriental Farmers",
  subtitle: "Our comprehensive services to help farmers succeed",
  altText: "Aerial view of agricultural fields in Negros Oriental",
};

// Reusable Service Card Component
const ServiceCard = ({
  category,
  index,
}: {
  category: ServiceCategory;
  index: number;
}) => {
  const [expanded, setExpanded] = useState(false);

  return (
    <Animated.View
      entering={FadeInDown.delay(100 * index).springify()}
      style={styles.serviceCard}
    >
      <TouchableOpacity
        style={styles.cardHeader}
        onPress={() => setExpanded(!expanded)}
        activeOpacity={0.7}
      >
        <View style={styles.cardIconContainer}>
          <Ionicons
            name={category.icon}
            size={28}
            color={COLORS.light}
            accessibilityLabel={`${category.title} icon`}
          />
        </View>
        <View style={styles.cardTitleContainer}>
          <ThemedText style={styles.cardTitle}>{category.title}</ThemedText>
          <ThemedText
            style={styles.cardDescription}
            numberOfLines={expanded ? 0 : 2}
          >
            {category.description}
          </ThemedText>
        </View>
        <Ionicons
          name={expanded ? "chevron-up" : "chevron-down"}
          size={24}
          color={COLORS.primary}
          style={styles.expandIcon}
        />
      </TouchableOpacity>

      {expanded && (
        <Animated.View
          entering={FadeIn.springify()}
          style={styles.servicesList}
        >
          {category.services.map((service, index) => (
            <View
              key={`${category.id}-service-${index}`}
              style={styles.serviceItem}
            >
              <View style={styles.checkmarkContainer}>
                <Ionicons
                  name="checkmark-circle"
                  size={16}
                  color={COLORS.accent}
                  accessibilityLabel="Service included"
                />
              </View>
              <ThemedText style={styles.serviceText}>{service}</ThemedText>
            </View>
          ))}
        </Animated.View>
      )}
    </Animated.View>
  );
};

// Reusable Contact Item Component
const ContactItem = ({
  icon,
  text,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  text: string;
}) => (
  <View style={styles.contactItem}>
    <View style={styles.contactIconContainer}>
      <Ionicons
        name={icon}
        size={20}
        color={COLORS.light}
        accessibilityLabel={`${icon} icon`}
      />
    </View>
    <ThemedText style={styles.contactText}>{text}</ThemedText>
  </View>
);

export default function ServicesScreen() {
  const router = useRouter();
  const scrollY = useSharedValue(0);

  return (
    <View style={styles.container}>
      {/* Use Stack.Screen to customize navigation header */}
      <Stack.Screen
        options={{
          title: "Agricultural Services",
          headerStyle: {
            backgroundColor: COLORS.primary,
          },
          headerTintColor: COLORS.light,
          headerShadowVisible: false,
        }}
      />
      <StatusBar barStyle="light-content" backgroundColor={COLORS.primary} />
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
        showsVerticalScrollIndicator={false}
        onScroll={(event) => {
          scrollY.value = event.nativeEvent.contentOffset.y;
        }}
        scrollEventThrottle={16}
      >
        {/* Hero Section */}
        <View style={styles.heroContainer}>
          <Image
            source={{ uri: HERO_SECTION.imageUrl }}
            style={styles.heroImage}
            resizeMode="cover"
            accessibilityLabel={HERO_SECTION.altText}
          />
          <View style={styles.heroOverlay}>
            <Animated.View entering={FadeInDown.delay(100).springify()}>
              <ThemedText style={styles.heroTitle}>
                {HERO_SECTION.title}
              </ThemedText>
            </Animated.View>
            <Animated.View entering={FadeInDown.delay(200).springify()}>
              <ThemedText style={styles.heroSubtitle}>
                {HERO_SECTION.subtitle}
              </ThemedText>
            </Animated.View>
          </View>
        </View>
        {/* Main Content */}
        <View style={styles.mainContent}>
          {/* Introduction */}
          <Animated.View
            entering={FadeInDown.delay(300).springify()}
            style={styles.introSection}
          >
            <ThemedText style={styles.sectionTitle}>
              Our Agricultural Services
            </ThemedText>
            <ThemedText style={styles.sectionDescription}>
              The Municipal Agriculture Office provides a range of services
              designed to support local farmers in production, marketing, and
              distribution of agricultural products.
            </ThemedText>
          </Animated.View>

          {/* Service Categories */}
          <View style={styles.servicesSection}>
            {SERVICE_CATEGORIES.map((category, index) => (
              <ServiceCard
                key={category.id}
                category={category}
                index={index}
              />
            ))}
          </View>

          {/* Contact Information */}
          <Animated.View
            entering={FadeInDown.delay(800).springify()}
            style={styles.contactSection}
          >
            <ThemedText style={styles.sectionTitle}>
              Contact Information
            </ThemedText>

            <View style={styles.contactCards}>
              {CONTACT_INFO.map((item, index) => (
                <ContactItem
                  key={`contact-${index}`}
                  icon={item.icon as keyof typeof Ionicons.glyphMap}
                  text={item.text}
                />
              ))}
            </View>
          </Animated.View>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f8f8f8",
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    flexGrow: 1,
    paddingBottom: SPACING.xxxl,
  },
  // Hero Section
  heroContainer: {
    height: 160,
    position: "relative",
    width: "100%",
    backgroundColor: COLORS.primary,
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
    backgroundColor: "rgba(27, 94, 32, 0.85)",
    paddingHorizontal: SPACING.xl,
    paddingTop: SPACING.xl,
    paddingBottom: SPACING.xxl,
  },
  heroTitle: {
    fontSize: 23,
    fontWeight: "700",
    color: COLORS.light,
    marginBottom: SPACING.sm,
  },
  heroSubtitle: {
    fontSize: 18,
    fontWeight: "500",
    color: COLORS.light,
    opacity: 0.9,
  },
  // Main Content
  mainContent: {
    padding: SPACING.lg,
    backgroundColor: "#f8f8f8",
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    marginTop: -20,
  },

  // Sections
  introSection: {
    marginTop: SPACING.lg,
    marginBottom: SPACING.lg,
  },
  servicesSection: {
    marginBottom: SPACING.xl,
  },
  contactSection: {
    marginBottom: SPACING.xxxl,
    paddingBottom: SPACING.xl,
  },
  sectionTitle: {
    fontSize: 22,
    fontWeight: "700",
    color: COLORS.primary,
    marginBottom: SPACING.md,
  },
  sectionDescription: {
    fontSize: 16,
    color: COLORS.text,
    lineHeight: 24,
  },

  // Service Card
  serviceCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    marginBottom: SPACING.lg,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
      },
      android: {
        elevation: 4,
      },
    }),
    overflow: "hidden",
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    padding: SPACING.lg,
  },
  cardIconContainer: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: COLORS.primary,
    justifyContent: "center",
    alignItems: "center",
    marginRight: SPACING.md,
  },
  cardTitleContainer: {
    flex: 1,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: "600",
    color: COLORS.primary,
    marginBottom: 4,
  },
  cardDescription: {
    fontSize: 14,
    color: COLORS.text,
    lineHeight: 20,
  },
  expandIcon: {
    marginLeft: SPACING.sm,
  },
  servicesList: {
    paddingHorizontal: SPACING.lg,
    paddingBottom: SPACING.lg,
  },
  serviceItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: SPACING.sm,
  },
  checkmarkContainer: {
    width: 24,
    height: 24,
    justifyContent: "center",
    alignItems: "center",
  },
  serviceText: {
    fontSize: 15,
    color: COLORS.text,
    marginLeft: 4,
    flex: 1,
    lineHeight: 20,
  },

  // Contact Items
  contactCards: {
    marginVertical: SPACING.md,
  },
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    padding: SPACING.md,
    marginBottom: SPACING.md,
    ...Platform.select({
      ios: {
        shadowColor: COLORS.shadow,
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
      },
      android: {
        elevation: 2,
      },
    }),
  },
  contactIconContainer: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.primary,
    justifyContent: "center",
    alignItems: "center",
    marginRight: SPACING.md,
  },
  contactText: {
    fontSize: 15,
    color: COLORS.text,
    flex: 1,
    lineHeight: 20,
  },

  // Inquiry Button
  inquiryButton: {
    backgroundColor: COLORS.accent,
    borderRadius: 12,
    padding: SPACING.md,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    marginTop: SPACING.lg,
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
  inquiryButtonText: {
    fontSize: 16,
    fontWeight: "600",
    color: COLORS.light,
  },
});
