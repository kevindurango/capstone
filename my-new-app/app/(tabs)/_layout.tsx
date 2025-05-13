import React, { useEffect, useState } from "react";
import { Tabs, useRouter } from "expo-router";
import { View, Text } from "react-native";
import { FontAwesome } from "@expo/vector-icons";
import { useAuth } from "@/contexts/AuthContext";
import { Colors } from "@/constants/Colors";
import { useColorScheme } from "@/hooks/useColorScheme";

/**
 * This is the layout for the main tab navigation
 * We redirect role-specific functionality to their respective areas
 */
function TabBarIcon(props: {
  name: React.ComponentProps<typeof FontAwesome>["name"];
  color: string;
}) {
  return <FontAwesome size={24} style={{ marginBottom: -3 }} {...props} />;
}

export default function TabLayout() {
  const colorScheme = useColorScheme();
  const router = useRouter();
  const { isAuthenticated, isFarmer, isConsumer, isLoading } = useAuth();
  const [navigationAttempted, setNavigationAttempted] = useState(false);

  // Debug information
  console.log("[TabLayout] Auth status:", {
    isAuthenticated,
    isFarmer,
    isConsumer,
    isLoading,
    navigationAttempted,
  });

  // Redirect authenticated users to their appropriate dashboards
  // but only attempt navigation once to avoid navigation loops
  useEffect(() => {
    if (navigationAttempted || isLoading) {
      return;
    }

    const redirectUser = () => {
      if (isAuthenticated) {
        console.log(
          "[TabLayout] User is authenticated, role:",
          isFarmer ? "farmer" : "consumer"
        );

        // Only try navigation once to avoid loops
        setNavigationAttempted(true);

        try {
          // Use a setTimeout to delay navigation until after render cycle
          setTimeout(() => {
            if (isFarmer) {
              console.log("[TabLayout] Redirecting to farmer dashboard");
              router.replace("/farmer/dashboard");
            } else if (isConsumer) {
              console.log("[TabLayout] Redirecting to consumer dashboard");
              router.replace("/consumer/dashboard");
            }
          }, 100);
        } catch (error) {
          console.error("[TabLayout] Navigation error:", error);
        }
      } else {
        console.log("[TabLayout] User is not authenticated, showing tabs");
      }
    };

    if (!isLoading) {
      redirectUser();
    }
  }, [
    isAuthenticated,
    isFarmer,
    isConsumer,
    isLoading,
    router,
    navigationAttempted,
  ]);

  // Show loading indicator while checking authentication
  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
        <Text>Loading...</Text>
      </View>
    );
  }

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: Colors[colorScheme ?? "light"].tint,
        tabBarStyle: {
          paddingBottom: 5,
          paddingTop: 5,
          height: 60,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: "Home",
          tabBarIcon: ({ color }) => <TabBarIcon name="home" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="intro"
        options={{
          title: "Introduction",
          tabBarIcon: ({ color }) => (
            <TabBarIcon name="info-circle" color={color} />
          ),
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="market"
        options={{
          title: "Market",
          tabBarIcon: ({ color }) => (
            <TabBarIcon name="shopping-basket" color={color} />
          ),
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="profile"
        options={{
          title: "Profile",
          tabBarIcon: ({ color }) => <TabBarIcon name="user" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="main"
        options={{
          title: "Get Started",
          tabBarIcon: ({ color }) => <TabBarIcon name="rocket" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="about"
        options={{
          title: "About",
          tabBarIcon: ({ color }) => <TabBarIcon name="info" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="services"
        options={{
          title: "Services",
          tabBarIcon: ({ color }) => <TabBarIcon name="list" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="settings"
        options={{
          title: "Settings",
          tabBarIcon: ({ color }) => <TabBarIcon name="gear" color={color} />,
          headerShown: false,
        }}
      />

      <Tabs.Screen
        name="explore"
        options={{
          title: "Explore",
          tabBarIcon: ({ color }) => (
            <TabBarIcon name="compass" color={color} />
          ),
          headerShown: false,
        }}
      />
    </Tabs>
  );
}
