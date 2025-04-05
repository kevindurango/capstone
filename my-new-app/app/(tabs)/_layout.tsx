import { Tabs } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: COLORS.accent,
        headerShown: false, // Disable header for tab routes
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: "Home",
          tabBarIcon: ({ color }) => (
            <Ionicons name="home" size={24} color={color} />
          ),
        }}
      />

      {/* Fix for the login and register routes */}
      <Tabs.Screen
        name="login"
        options={{
          title: "Login",
          tabBarIcon: ({ color }) => (
            <Ionicons name="log-in-outline" size={24} color={color} />
          ),
          // Use href to redirect to the auth folder
          href: "/(auth)/login",
        }}
      />

      <Tabs.Screen
        name="register"
        options={{
          title: "Register",
          tabBarIcon: ({ color }) => (
            <Ionicons name="person-add-outline" size={24} color={color} />
          ),
          href: "/(auth)/register",
        }}
      />

      <Tabs.Screen
        name="main"
        options={{
          title: "Market",
          tabBarIcon: ({ color }) => (
            <Ionicons name="basket-outline" size={24} color={color} />
          ),
        }}
      />
    </Tabs>
  );
}
