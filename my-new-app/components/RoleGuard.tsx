import React, { useEffect, useState } from "react";
import { useRouter, useSegments } from "expo-router";
import { View, ActivityIndicator, StyleSheet, Text } from "react-native";
import { useAuth } from "@/contexts/AuthContext";
import { Colors } from "@/constants/Colors";

interface RoleGuardProps {
  children: React.ReactNode;
  allowedRoles: number[];
  fallbackPath: string;
}

/**
 * A component that restricts access to routes based on user roles
 *
 * @param children The content to render if role check passes
 * @param allowedRoles Array of role IDs allowed to access this route
 * @param fallbackPath Where to redirect if role check fails
 */
export default function RoleGuard({
  children,
  allowedRoles,
  fallbackPath,
}: RoleGuardProps) {
  const { isAuthenticated, user, isLoading } = useAuth();
  const router = useRouter();
  const segments = useSegments();
  const currentPath = "/" + segments.join("/");
  const [isChecking, setIsChecking] = useState(true);
  const [hasAccess, setHasAccess] = useState(false);

  useEffect(() => {
    // Only check after authentication state is loaded
    if (isLoading) {
      return;
    }

    let shouldContinue = true;

    // Helper function to safely navigate
    const safeNavigate = (path: string) => {
      if (shouldContinue) {
        // Small timeout to prevent race conditions in navigation
        setTimeout(() => {
          router.replace({
            pathname: path as any,
          });
        }, 100);
      }
    };

    // First check if user is authenticated at all
    if (!isAuthenticated) {
      console.log(
        `[RoleGuard] Not authenticated, redirecting from ${currentPath} to /(auth)/login`
      );
      safeNavigate("/(auth)/login");
      setHasAccess(false);
      setIsChecking(false);
      return;
    }

    // Get the user's role from the user object
    const userRole = user?.role_id;

    // Then check if the user's role is available
    if (userRole === null || userRole === undefined) {
      console.log(`[RoleGuard] User role is not defined yet, waiting...`);
      return; // Wait for role to be loaded
    }

    if (!allowedRoles.includes(userRole)) {
      console.log(
        `[RoleGuard] Role ${userRole} not allowed at ${currentPath}, redirecting to ${fallbackPath}`
      );
      safeNavigate(fallbackPath);
      setHasAccess(false);
    } else {
      console.log(
        `[RoleGuard] Access granted for role ${userRole} at ${currentPath}`
      );
      setHasAccess(true);
    }

    setIsChecking(false);

    return () => {
      shouldContinue = false; // Prevent navigation if component unmounts
    };
  }, [
    isAuthenticated,
    user,
    isLoading,
    currentPath,
    fallbackPath,
    allowedRoles,
  ]);

  // Show loading state while checking
  if (isLoading || isChecking) {
    return (
      <View style={styles.container}>
        <ActivityIndicator size="large" color={Colors.light.tint} />
        <Text style={styles.text}>Checking permissions...</Text>
      </View>
    );
  }

  // Only render children if user has access
  return hasAccess ? <>{children}</> : null;
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
  },
  text: {
    marginTop: 16,
    fontSize: 16,
    color: Colors.light.tint,
  },
});
