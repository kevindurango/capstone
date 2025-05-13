import { Stack, useRouter } from "expo-router";
import { TouchableOpacity } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { COLORS } from "@/constants/Colors";

export default function AppLayout() {
  const router = useRouter();

  return (
    <Stack
      screenOptions={{
        headerStyle: {
          backgroundColor: COLORS.dark,
        },
        headerTintColor: COLORS.light,
        headerRight: () => (
          <TouchableOpacity
            onPress={() => router.push("/settings")}
            style={{ marginRight: 15 }}
          >
            <Ionicons name="settings-outline" size={24} color={COLORS.light} />
          </TouchableOpacity>
        ),
      }}
    >
      {/* Define your app screens here */}
      <Stack.Screen name="index" />
      {/* Add other app screens as needed */}
    </Stack>
  );
}
