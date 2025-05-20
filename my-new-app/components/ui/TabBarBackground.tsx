import { useBottomTabBarHeight } from "@react-navigation/bottom-tabs";
import { Platform, StyleSheet, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { COLORS } from "@/constants/Colors";

export default function TabBarBackground() {
  // On Android, we use a simple background color
  return (
    <View
      style={[
        StyleSheet.absoluteFill,
        {
          backgroundColor: COLORS.dark,
          opacity: 0.9,
        },
      ]}
    />
  );
}

export function useBottomTabOverflow() {
  const tabHeight = useBottomTabBarHeight();
  const { bottom } = useSafeAreaInsets();
  return tabHeight - bottom;
}
