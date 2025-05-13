import { StyleSheet, Dimensions, Platform } from "react-native";
import { COLORS } from "../Colors";

const { width, height } = Dimensions.get("window");

// Screen size breakpoints
export const SCREEN = {
  width,
  height,
  isSmall: width < 375,
  isMedium: width >= 375 && width < 414,
  isLarge: width >= 414,
};

// Consistent spacing system
export const SPACING = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 40,
  horizontalPadding: 24,
};

// Extended colors for missing values
export const EXTENDED_COLORS = {
  ...COLORS,
  error: "#F44336",
  success: "#4CAF50",
  info: "#2196F3",
  warning: "#FF9800",
  background: {
    default: COLORS.light,
    paper: "#F9FBF7",
    subtle: "#F5F7F5",
  },
  border: {
    light: "#E0E0E0",
    medium: "#BDBDBD",
    dark: "#9E9E9E",
  },
  textSecondary: "#757575",
  // Alpha transparency values (in hex)
  alpha: {
    5: "0D", // 5% opacity
    10: "1A", // 10% opacity
    20: "33", // 20% opacity
    30: "4D", // 30% opacity
    40: "66", // 40% opacity
    50: "80", // 50% opacity
    60: "99", // 60% opacity
    70: "B3", // 70% opacity
    80: "CC", // 80% opacity
    90: "E6", // 90% opacity
  },
  // Gradients
  gradients: {
    primary: COLORS.gradient,
    accent: ["#FF7043", "#E65100", "#BF360C"],
    secondary: ["#FFD54F", "#FFC107", "#FFA000"],
  },
};

// Typography system
export const TYPOGRAPHY = StyleSheet.create({
  h1: {
    fontSize: 32,
    fontWeight: "bold",
    color: COLORS.text,
    letterSpacing: 0.25,
  },
  h2: {
    fontSize: 24,
    fontWeight: "bold",
    color: COLORS.text,
    letterSpacing: 0.15,
  },
  h3: {
    fontSize: 20,
    fontWeight: "600",
    color: COLORS.text,
    letterSpacing: 0.15,
  },
  subtitle1: {
    fontSize: 18,
    fontWeight: "600",
    color: COLORS.text,
    letterSpacing: 0.15,
  },
  subtitle2: {
    fontSize: 16,
    fontWeight: "500",
    color: COLORS.text,
    letterSpacing: 0.1,
  },
  body1: {
    fontSize: 16,
    color: COLORS.text,
    letterSpacing: 0.5,
  },
  body2: {
    fontSize: 14,
    color: COLORS.text,
    letterSpacing: 0.25,
  },
  caption: {
    fontSize: 12,
    color: EXTENDED_COLORS.textSecondary,
    letterSpacing: 0.4,
  },
  button: {
    fontSize: 16,
    fontWeight: "600",
    letterSpacing: 1.25,
    textTransform: "uppercase",
  },
});

// Shadow styles for different elevations
export const SHADOWS = {
  small: Platform.select({
    ios: {
      shadowColor: COLORS.shadow,
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 3,
    },
    android: {
      elevation: 2,
    },
    default: {},
  }),
  medium: Platform.select({
    ios: {
      shadowColor: COLORS.shadow,
      shadowOffset: { width: 0, height: 3 },
      shadowOpacity: 0.15,
      shadowRadius: 5,
    },
    android: {
      elevation: 4,
    },
    default: {},
  }),
  large: Platform.select({
    ios: {
      shadowColor: COLORS.shadow,
      shadowOffset: { width: 0, height: 5 },
      shadowOpacity: 0.2,
      shadowRadius: 8,
    },
    android: {
      elevation: 8,
    },
    default: {},
  }),
};

// Border radius system
export const BORDER_RADIUS = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  pill: 500,
  circle: 999,
};

// Common UI component styles
export const UI_STYLES = StyleSheet.create({
  // Card styles
  card: {
    backgroundColor: EXTENDED_COLORS.background.paper,
    borderRadius: BORDER_RADIUS.md,
    padding: SPACING.md,
    ...SHADOWS.small,
  },
  cardElevated: {
    backgroundColor: EXTENDED_COLORS.background.paper,
    borderRadius: BORDER_RADIUS.md,
    padding: SPACING.md,
    ...SHADOWS.medium,
  },

  // Input field styles
  inputContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(255,255,255,0.08)",
    borderRadius: BORDER_RADIUS.md,
    marginBottom: SPACING.xs,
    height: 56,
  },
  input: {
    flex: 1,
    color: COLORS.light,
    fontSize: 16,
    paddingVertical: 15,
  },
  iconContainer: {
    paddingHorizontal: 16,
    justifyContent: "center",
    alignItems: "center",
  },

  // Button styles
  button: {
    height: 56,
    borderRadius: BORDER_RADIUS.md,
    justifyContent: "center",
    alignItems: "center",
    flexDirection: "row",
  },
  buttonPrimary: {
    backgroundColor: COLORS.primary,
    ...SHADOWS.small,
  },
  buttonSecondary: {
    backgroundColor: COLORS.secondary,
    ...SHADOWS.small,
  },
  buttonAccent: {
    backgroundColor: COLORS.accent,
    ...SHADOWS.small,
  },
  buttonText: {
    color: COLORS.light,
    textAlign: "center",
    fontSize: 16,
    fontWeight: "600",
  },
  buttonIcon: {
    marginLeft: 8,
  },

  // List item styles
  listItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderBottomColor: EXTENDED_COLORS.border.light,
  },

  // Screen container
  screenContainer: {
    flex: 1,
    backgroundColor: EXTENDED_COLORS.background.default,
  },

  // Form styles
  formContainer: {
    flex: 1,
    justifyContent: "flex-start",
    paddingHorizontal: SPACING.horizontalPadding,
    paddingTop: SPACING.md,
  },
  formSection: {
    marginBottom: SPACING.xl,
  },
  formLabel: {
    ...StyleSheet.flatten(TYPOGRAPHY.subtitle2),
    marginBottom: SPACING.xs,
  },

  // Error text
  errorText: {
    color: EXTENDED_COLORS.error,
    fontSize: 12,
    marginLeft: SPACING.md,
    marginBottom: SPACING.md,
  },
});

export default {
  SCREEN,
  SPACING,
  TYPOGRAPHY,
  SHADOWS,
  BORDER_RADIUS,
  UI_STYLES,
  EXTENDED_COLORS,
};
