/**
 * Below are the colors that are used in the app. The colors are defined in the light and dark mode.
 * There are many other ways to style your app. For example, [Nativewind](https://www.nativewind.dev/), [Tamagui](https://tamagui.dev/), [unistyles](https://reactnativeunistyles.vercel.app), etc.
 */

const tintColorLight = "#0a7ea4";
const tintColorDark = "#fff";

export const Colors = {
  light: {
    text: "#11181C",
    background: "#fff",
    tint: tintColorLight,
    icon: "#687076",
    tabIconDefault: "#687076",
    tabIconSelected: tintColorLight,
  },
  dark: {
    text: "#ECEDEE",
    background: "#151718",
    tint: tintColorDark,
    icon: "#9BA1A6",
    tabIconDefault: "#9BA1A6",
    tabIconSelected: tintColorDark,
  },
};

export const COLORS = {
  primary: "#1B5E20",
  secondary: "#FFC107",
  accent: "#E65100",
  light: "#FFFFFF",
  dark: "#1B5E20",
  text: "#263238",
  muted: "#78909C",
  gradient: ["#2E7D32", "#1B5E20", "#0D3010"] as const,
  shadow: "#000000",
} as const;

export type ColorKeys = keyof typeof COLORS;
