import { Stack } from "expo-router";

export default function SettingsLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      {/* List all screens in this Stack */}
      <Stack.Screen name="index" />
      {/* Add other settings screens if they exist */}
    </Stack>
  );
}
