import React, { useEffect, useState } from "react";
import { View, ActivityIndicator, Text } from "react-native";
import { useRouter } from "expo-router";
import { authService } from "@/services/authService";

const AuthCheck = ({ children, redirectTo = "/" }) => {
  const [isChecking, setIsChecking] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const router = useRouter();

  useEffect(() => {
    let isMounted = true;

    const checkAuth = async () => {
      try {
        console.log("[AuthCheck] Verifying authentication...");
        const authStatus = await authService.isAuthenticated();
        console.log("[AuthCheck] Auth status:", authStatus);

        if (!isMounted) return;

        // Only update state if component is still mounted
        setIsAuthenticated(authStatus);

        if (!authStatus) {
          console.log(
            "[AuthCheck] Not authenticated, redirecting to:",
            redirectTo
          );
          // Use setTimeout to avoid immediate redirect which can cause render issues
          setTimeout(() => {
            if (isMounted) {
              router.replace(redirectTo);
            }
          }, 100);
        }
      } catch (error) {
        console.error("[AuthCheck] Auth check error:", error);
        if (isMounted) {
          setIsAuthenticated(false);
          setTimeout(() => {
            if (isMounted) {
              router.replace(redirectTo);
            }
          }, 100);
        }
      } finally {
        if (isMounted) {
          setIsChecking(false);
        }
      }
    };

    checkAuth();

    // Cleanup function to handle unmounting
    return () => {
      isMounted = false;
    };
  }, [router, redirectTo]);

  // Always render a loading state initially
  if (isChecking) {
    return (
      <View
        style={{
          flex: 1,
          justifyContent: "center",
          alignItems: "center",
          backgroundColor: "#FFFFFF",
        }}
      >
        <ActivityIndicator size="large" color="#1B5E20" />
        <Text style={{ marginTop: 10, color: "#1B5E20", fontWeight: "500" }}>
          Verifying authentication...
        </Text>
      </View>
    );
  }

  // If we're past the checking state, either render children or nothing
  // The redirect will have been triggered in the useEffect
  return isAuthenticated ? (
    children
  ) : (
    <View
      style={{
        flex: 1,
        justifyContent: "center",
        alignItems: "center",
        backgroundColor: "#FFFFFF",
      }}
    >
      <Text style={{ color: "#1B5E20" }}>Redirecting to login...</Text>
    </View>
  );
};

export default AuthCheck;
