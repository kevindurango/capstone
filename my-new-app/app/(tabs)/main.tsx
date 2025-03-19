import {
  StyleSheet,
  View,
  TouchableOpacity,
  ScrollView,
  Image,
} from "react-native";
import { useRouter } from "expo-router";
import { ThemedText } from "@/components/ThemedText";
import { ThemedView } from "@/components/ThemedView";
import { Ionicons } from "@expo/vector-icons";
import { useState } from "react";
import Sidebar from "@/components/Sidebar";

// Keep color scheme consistent with welcome screen
const COLORS = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  cardBg: "#F9FBF7",
  shadow: "#000000",
};

export default function MainScreen() {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState("home");
  const [isSidebarVisible, setIsSidebarVisible] = useState(false);

  const navigateToSection = (
    section:
      | "profile"
      | "about"
      | "services"
      | "programs"
      | "market"
      | "news"
      | "register"
      | "login"
      | "explore-services" // Added new section
  ) => {
    // These would navigate to other screens once you create them
    router.push(`/(tabs)/${section}` as any);
  };

  return (
    <View style={styles.container}>
      {/* Add Sidebar */}
      <Sidebar
        isVisible={isSidebarVisible}
        onClose={() => setIsSidebarVisible(false)}
        router={router}
        colors={COLORS}
      />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.menuButton}
          onPress={() => setIsSidebarVisible(true)}
        >
          <Ionicons name="menu" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>
          Municipal Agriculture Office
        </ThemedText>
        <TouchableOpacity
          style={styles.profileButton}
          onPress={() => navigateToSection("profile")}
        >
          <Ionicons name="person-circle" size={32} color={COLORS.light} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
      >
        {/* Welcome Banner */}
        <View style={styles.welcomeBanner}>
          <View style={styles.welcomeText}>
            <ThemedText style={styles.welcomeTitle}>
              Welcome to Negros Oriental
            </ThemedText>
            <ThemedText style={styles.welcomeSubtitle}>
              Supporting our farmers and communities
            </ThemedText>
          </View>
          <View style={styles.welcomeImageContainer}>
            <View style={styles.welcomeImagePlaceholder}>
              <Ionicons name="leaf" size={36} color={COLORS.primary} />
            </View>
          </View>
        </View>

        {/* Quick Actions */}
        <View style={styles.quickActionsContainer}>
          <ThemedText style={styles.sectionTitle}>
            Services & Resources
          </ThemedText>

          <View style={styles.quickActions}>
            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("about")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons
                  name="information-circle"
                  size={32}
                  color={COLORS.primary}
                />
              </View>
              <ThemedText style={styles.actionTitle}>
                About Our Office
              </ThemedText>
              <ThemedText style={styles.actionDescription}>
                Learn about our mission and team
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("explore-services")} // Updated to navigate to explore-services
            >
              <View style={styles.actionIconContainer}>
                <Ionicons name="list" size={32} color={COLORS.primary} />
              </View>
              <ThemedText style={styles.actionTitle}>Services</ThemedText>
              <ThemedText style={styles.actionDescription}>
                View available services for farmers
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("programs")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons name="star" size={32} color={COLORS.primary} />
              </View>
              <ThemedText style={styles.actionTitle}>Programs</ThemedText>
              <ThemedText style={styles.actionDescription}>
                Explore active agriculture programs
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionCard}
              onPress={() => navigateToSection("market")}
            >
              <View style={styles.actionIconContainer}>
                <Ionicons name="basket" size={32} color={COLORS.primary} />
              </View>
              <ThemedText style={styles.actionTitle}>Farmers Market</ThemedText>
              <ThemedText style={styles.actionDescription}>
                Connect with local markets
              </ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* News and Updates */}
        <View style={styles.newsContainer}>
          <View style={styles.sectionTitleContainer}>
            <ThemedText style={styles.sectionTitle}>Latest Updates</ThemedText>
            <TouchableOpacity onPress={() => navigateToSection("news")}>
              <ThemedText style={styles.viewAllLink}>View All</ThemedText>
            </TouchableOpacity>
          </View>

          <View style={styles.newsCard}>
            <View style={styles.newsImagePlaceholder}>
              <Ionicons name="calendar" size={32} color={COLORS.primary} />
            </View>
            <View style={styles.newsContent}>
              <ThemedText style={styles.newsTitle}>
                Farmer Training Program
              </ThemedText>
              <ThemedText style={styles.newsDate}>June 15, 2025</ThemedText>
              <ThemedText style={styles.newsDescription}>
                Join our upcoming training on sustainable farming techniques.
              </ThemedText>
            </View>
          </View>

          <View style={styles.newsCard}>
            <View style={styles.newsImagePlaceholder}>
              <Ionicons name="water" size={32} color={COLORS.primary} />
            </View>
            <View style={styles.newsContent}>
              <ThemedText style={styles.newsTitle}>
                Irrigation System Updates
              </ThemedText>
              <ThemedText style={styles.newsDate}>June 10, 2025</ThemedText>
              <ThemedText style={styles.newsDescription}>
                New irrigation systems being installed in eastern districts.
              </ThemedText>
            </View>
          </View>
        </View>

        {/* Registration/Login Section */}
        <View style={styles.loginContainer}>
          <ThemedText style={styles.loginTitle}>
            Create your Farmer Account
          </ThemedText>
          <ThemedText style={styles.loginDescription}>
            Register to access exclusive resources, apply for programs, and
            connect with agricultural specialists
          </ThemedText>
          <View style={styles.loginButtons}>
            <TouchableOpacity
              style={[styles.loginButton, styles.registerButton]}
              onPress={() => navigateToSection("register")}
            >
              <ThemedText style={styles.loginButtonText}>Register</ThemedText>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.loginButton}
              onPress={() => navigateToSection("login")}
            >
              <ThemedText style={styles.loginButtonText}>Login</ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* Contact Section */}
        <View style={styles.contactContainer}>
          <ThemedText style={styles.contactTitle}>Contact Us</ThemedText>
          <View style={styles.contactInfo}>
            <View style={styles.contactItem}>
              <Ionicons name="location" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                Capitol Area, Dumaguete City, Negros Oriental
              </ThemedText>
            </View>
            <View style={styles.contactItem}>
              <Ionicons name="call" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>(035) 225-0000</ThemedText>
            </View>
            <View style={styles.contactItem}>
              <Ionicons name="mail" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                agriculture@negor.gov.ph
              </ThemedText>
            </View>
          </View>
        </View>
      </ScrollView>

      {/* Bottom Navigation */}
      <View style={styles.bottomNav}>
        <TouchableOpacity
          style={styles.navItem}
          onPress={() => setActiveTab("home")}
        >
          <Ionicons
            name={activeTab === "home" ? "home" : "home-outline"}
            size={26}
            color={activeTab === "home" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              { color: activeTab === "home" ? COLORS.primary : COLORS.muted },
            ]}
          >
            Home
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.navItem}
          onPress={() => setActiveTab("services")}
        >
          <Ionicons
            name={activeTab === "services" ? "list" : "list-outline"}
            size={26}
            color={activeTab === "services" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              {
                color: activeTab === "services" ? COLORS.primary : COLORS.muted,
              },
            ]}
          >
            Services
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.navItem}
          onPress={() => setActiveTab("programs")}
        >
          <Ionicons
            name={activeTab === "programs" ? "star" : "star-outline"}
            size={26}
            color={activeTab === "programs" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              {
                color: activeTab === "programs" ? COLORS.primary : COLORS.muted,
              },
            ]}
          >
            Programs
          </ThemedText>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.navItem}
          onPress={() => setActiveTab("market")}
        >
          <Ionicons
            name={activeTab === "market" ? "basket" : "basket-outline"}
            size={26}
            color={activeTab === "market" ? COLORS.primary : COLORS.muted}
          />
          <ThemedText
            style={[
              styles.navText,
              { color: activeTab === "market" ? COLORS.primary : COLORS.muted },
            ]}
          >
            Market
          </ThemedText>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
    paddingTop: 40, // Increased padding top to add more space
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    backgroundColor: COLORS.primary,
    elevation: 4,
    shadowColor: COLORS.shadow,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
  },
  headerTitle: {
    fontSize: 20,
    color: COLORS.light,
    fontWeight: "700",
  },
  profileButton: {
    padding: 8,
  },
  menuButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
  },
  welcomeBanner: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  welcomeText: {
    flex: 1,
  },
  welcomeTitle: {
    fontSize: 24,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "700",
  },
  welcomeSubtitle: {
    fontSize: 16,
    color: COLORS.text,
  },
  welcomeImageContainer: {
    justifyContent: "center",
    alignItems: "center",
  },
  welcomeImagePlaceholder: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: COLORS.light,
    justifyContent: "center",
    alignItems: "center",
  },
  quickActionsContainer: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  quickActions: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
  },
  actionCard: {
    width: "48%",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    alignItems: "center",
  },
  actionIconContainer: {
    marginBottom: 8,
  },
  actionTitle: {
    fontSize: 16,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "600",
  },
  actionDescription: {
    fontSize: 14,
    color: COLORS.text,
    textAlign: "center",
  },
  newsContainer: {
    marginBottom: 16,
  },
  sectionTitleContainer: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  viewAllLink: {
    fontSize: 14,
    color: COLORS.accent,
    fontWeight: "600",
  },
  newsCard: {
    flexDirection: "row",
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  newsImagePlaceholder: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: COLORS.light,
    justifyContent: "center",
    alignItems: "center",
    marginRight: 16,
  },
  newsContent: {
    flex: 1,
  },
  newsTitle: {
    fontSize: 16,
    color: COLORS.primary,
    marginBottom: 4,
    fontWeight: "600",
  },
  newsDate: {
    fontSize: 14,
    color: COLORS.muted,
    marginBottom: 4,
  },
  newsDescription: {
    fontSize: 14,
    color: COLORS.text,
  },
  loginContainer: {
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    alignItems: "center",
  },
  loginTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  loginDescription: {
    fontSize: 14,
    color: COLORS.text,
    textAlign: "center",
    marginBottom: 16,
  },
  loginButtons: {
    flexDirection: "row",
    justifyContent: "space-between",
    width: "100%",
  },
  loginButton: {
    flex: 1,
    padding: 12,
    borderRadius: 8,
    alignItems: "center",
    backgroundColor: COLORS.primary,
    marginHorizontal: 4,
  },
  registerButton: {
    backgroundColor: COLORS.secondary,
  },
  loginButtonText: {
    fontSize: 16,
    color: COLORS.light,
    fontWeight: "600",
  },
  contactContainer: {
    backgroundColor: COLORS.cardBg,
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  contactTitle: {
    fontSize: 18,
    color: COLORS.primary,
    marginBottom: 8,
    fontWeight: "700",
  },
  contactInfo: {
    marginTop: 8,
  },
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  contactText: {
    fontSize: 14,
    color: COLORS.text,
    marginLeft: 8,
  },
  bottomNav: {
    flexDirection: "row",
    justifyContent: "space-around",
    alignItems: "center",
    padding: 16,
    backgroundColor: COLORS.light,
    borderTopWidth: 1,
    borderTopColor: COLORS.muted,
  },
  navItem: {
    alignItems: "center",
    justifyContent: "center",
  },
  navText: {
    fontSize: 12,
    marginTop: 4,
  },
});
