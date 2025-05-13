import React from "react";
import {
  StyleSheet,
  View,
  ScrollView,
  Image,
  TouchableOpacity,
} from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { ThemedText } from "@/components/ThemedText";

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

export default function AboutScreen() {
  const router = useRouter();

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.push("/(tabs)/main")}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.light} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>About Our Office</ThemedText>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
      >
        {/* Disclaimer Banner */}
        <View style={styles.disclaimerBanner}>
          <Ionicons
            name="information-circle"
            size={24}
            color={COLORS.secondary}
          />
          <ThemedText style={styles.disclaimerText}>
            Note: For accurate information about the Municipal Agriculture
            Office, please contact them directly at (035) 225-0000 or visit
            their office at Palinpinon, Negros Oriental. Information can also be
            found at the provincial government website.
          </ThemedText>
        </View>

        {/* Hero Section */}
        <View style={styles.heroSection}>
          <Image
            source={{
              uri: "https://images.unsplash.com/photo-1500651230702-0e2d8a49d4ad?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80",
            }}
            style={styles.heroImage}
            resizeMode="cover"
          />
          <View style={styles.overlay} />
          <ThemedText style={styles.heroTitle}>
            Municipal Agriculture Office
          </ThemedText>
        </View>

        {/* Mission Section */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Our Mission</ThemedText>
          <ThemedText style={styles.sectionText}>
            [Placeholder] The Municipal Agriculture Office is dedicated to
            promoting sustainable agricultural development by providing
            essential support services to farmers, fishermen, and other
            stakeholders. We aim to improve food security, increase agricultural
            productivity, and enhance the economic well-being of our farming
            communities through innovative programs and technology.
          </ThemedText>
        </View>

        {/* Vision Section */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Our Vision</ThemedText>
          <ThemedText style={styles.sectionText}>
            [Placeholder] We envision a vibrant agricultural community where
            farmers thrive with sustainable farming practices, have access to
            modern technologies, and are resilient to climate change challenges.
            Our goal is to establish our municipality as a leading example of
            agricultural excellence in the region.
          </ThemedText>
        </View>

        {/* Core Services Section */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Core Services</ThemedText>

          <View style={styles.serviceCard}>
            <Ionicons name="leaf" size={32} color={COLORS.primary} />
            <View style={styles.serviceTextContainer}>
              <ThemedText style={styles.serviceTitle}>
                Agricultural Extension
              </ThemedText>
              <ThemedText style={styles.serviceDescription}>
                We provide technical assistance, training, and advisory services
                to farmers on modern farming techniques and technologies.
              </ThemedText>
            </View>
          </View>

          <View style={styles.serviceCard}>
            <Ionicons name="water" size={32} color={COLORS.primary} />
            <View style={styles.serviceTextContainer}>
              <ThemedText style={styles.serviceTitle}>
                Irrigation Support
              </ThemedText>
              <ThemedText style={styles.serviceDescription}>
                Assistance in irrigation system development and maintenance to
                ensure adequate water supply for agricultural activities.
              </ThemedText>
            </View>
          </View>

          <View style={styles.serviceCard}>
            <Ionicons name="flask" size={32} color={COLORS.primary} />
            <View style={styles.serviceTextContainer}>
              <ThemedText style={styles.serviceTitle}>
                Soil Health Management
              </ThemedText>
              <ThemedText style={styles.serviceDescription}>
                Soil testing and recommendations for appropriate fertilizer
                application and soil conservation practices.
              </ThemedText>
            </View>
          </View>

          <View style={styles.serviceCard}>
            <Ionicons name="cloud" size={32} color={COLORS.primary} />
            <View style={styles.serviceTextContainer}>
              <ThemedText style={styles.serviceTitle}>
                Climate-Resilient Agriculture
              </ThemedText>
              <ThemedText style={styles.serviceDescription}>
                Promotion of climate-smart agricultural practices to help
                farmers adapt to changing weather patterns.
              </ThemedText>
            </View>
          </View>
        </View>

        {/* Leadership Section */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Our Leadership</ThemedText>
          <ThemedText style={styles.placeholderNote}>
            This section contains placeholder information. Please replace with
            details of actual leadership personnel.
          </ThemedText>
          <View style={styles.leaderCard}>
            <View style={styles.leaderImageContainer}>
              <Ionicons name="person-circle" size={80} color={COLORS.muted} />
            </View>
            <View style={styles.leaderInfo}>
              <ThemedText style={styles.leaderName}>
                Dr. Maria A. Santos
              </ThemedText>
              <ThemedText style={styles.leaderTitle}>
                Municipal Agriculture Officer
              </ThemedText>
              <ThemedText style={styles.leaderBio}>
                Dr. Santos has over 15 years of experience in agricultural
                extension and rural development. She holds a PhD in Agricultural
                Sciences and has implemented numerous successful agricultural
                programs in the region.
              </ThemedText>
            </View>
          </View>
        </View>

        {/* Location Section */}
        <View style={styles.section}>
          <ThemedText style={styles.sectionTitle}>Visit Our Office</ThemedText>
          <ThemedText style={styles.placeholderNote}>
            Please verify the contact information below with the actual
            Municipal Agriculture Office.
          </ThemedText>
          <View style={styles.contactInfo}>
            <View style={styles.contactItem}>
              <Ionicons name="location" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                Palinpinon, Negros Oriental
              </ThemedText>
            </View>
            <View style={styles.contactItem}>
              <Ionicons name="time" size={24} color={COLORS.primary} />
              <ThemedText style={styles.contactText}>
                Monday - Friday: 8:00 AM - 5:00 PM
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
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.light,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 16,
    backgroundColor: COLORS.primary,
    paddingTop: 40,
  },
  headerTitle: {
    fontSize: 20,
    color: COLORS.light,
    fontWeight: "700",
  },
  backButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    padding: 0,
  },
  disclaimerBanner: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(255, 193, 7, 0.2)",
    padding: 12,
    marginBottom: 16,
    borderRadius: 6,
  },
  disclaimerText: {
    fontSize: 14,
    color: COLORS.dark,
    flex: 1,
    marginLeft: 8,
    fontStyle: "italic",
  },
  heroSection: {
    width: "100%",
    height: 200,
    position: "relative",
  },
  heroImage: {
    width: "100%",
    height: "100%",
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(27, 94, 32, 0.5)",
  },
  heroTitle: {
    position: "absolute",
    bottom: 20,
    left: 0,
    right: 0,
    textAlign: "center",
    color: COLORS.light,
    fontSize: 24,
    fontWeight: "bold",
  },
  section: {
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: "#E0E0E0",
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: "700",
    color: COLORS.primary,
    marginBottom: 15,
  },
  sectionText: {
    fontSize: 16,
    lineHeight: 24,
    color: COLORS.text,
  },
  placeholderNote: {
    fontSize: 14,
    color: COLORS.accent,
    fontStyle: "italic",
    marginBottom: 12,
  },
  serviceCard: {
    flexDirection: "row",
    backgroundColor: COLORS.cardBg,
    borderRadius: 8,
    padding: 15,
    marginBottom: 15,
    alignItems: "flex-start",
  },
  serviceTextContainer: {
    marginLeft: 15,
    flex: 1,
  },
  serviceTitle: {
    fontSize: 18,
    fontWeight: "600",
    color: COLORS.primary,
    marginBottom: 5,
  },
  serviceDescription: {
    fontSize: 14,
    color: COLORS.text,
    lineHeight: 20,
  },
  leaderCard: {
    backgroundColor: COLORS.cardBg,
    borderRadius: 8,
    padding: 20,
    alignItems: "center",
  },
  leaderImageContainer: {
    marginBottom: 15,
  },
  leaderInfo: {
    alignItems: "center",
    paddingHorizontal: 10,
  },
  leaderName: {
    fontSize: 18,
    fontWeight: "700",
    color: COLORS.primary,
    marginBottom: 5,
  },
  leaderTitle: {
    fontSize: 16,
    color: COLORS.accent,
    fontStyle: "italic",
    marginBottom: 10,
  },
  leaderBio: {
    fontSize: 14,
    color: COLORS.text,
    textAlign: "center",
    lineHeight: 20,
  },
  contactInfo: {
    backgroundColor: COLORS.cardBg,
    borderRadius: 8,
    padding: 15,
  },
  contactItem: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 12,
  },
  contactText: {
    fontSize: 16,
    color: COLORS.text,
    marginLeft: 15,
  },
});
