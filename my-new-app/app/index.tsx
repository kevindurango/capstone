import { Redirect } from "expo-router";
import { useAuth } from "@/contexts/AuthContext";

export default function Index() {
  const { isAuthenticated, isFarmer, isConsumer } = useAuth();

  // If user is already authenticated, redirect them directly to their dashboard
  if (isAuthenticated) {
    if (isFarmer) {
      return <Redirect href="/farmer/dashboard" />;
    } else if (isConsumer) {
      return <Redirect href="/consumer/dashboard" />;
    }
  }
  // Otherwise, for non-authenticated users, redirect to the index screen in tabs
  return <Redirect href="/(tabs)" />; // This redirects to the index file in the (tabs) folder
}
