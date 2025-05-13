import React from "react";
import { View, StyleSheet, TouchableOpacity, Text } from "react-native";
import { MaterialTopTabBarProps } from "@react-navigation/material-top-tabs";

/**
 * A custom tab bar component for Material Top Tabs that correctly handles
 * the key prop to avoid React warnings about spreading keys in JSX.
 */
const SafeTopTabBar = ({
  state,
  descriptors,
  navigation,
  position,
}: MaterialTopTabBarProps) => {
  return (
    <View style={styles.tabBar}>
      {state.routes.map((route, index) => {
        const { options } = descriptors[route.key];
        const label =
          options.tabBarLabel !== undefined
            ? options.tabBarLabel
            : options.title !== undefined
              ? options.title
              : route.name;

        const isFocused = state.index === index;

        // Handle the onPress event
        const onPress = () => {
          const event = navigation.emit({
            type: "tabPress",
            target: route.key,
            canPreventDefault: true,
          });

          if (!isFocused && !event.defaultPrevented) {
            // The `merge: true` option makes sure that the params inside the tab screen are preserved
            navigation.navigate({ name: route.name, merge: true } as any);
          }
        };

        // Handle on long press
        const onLongPress = () => {
          navigation.emit({
            type: "tabLongPress",
            target: route.key,
          });
        };

        // Get the active color from options or use a default
        const activeColor = options.tabBarActiveTintColor || "#0066cc";
        const inactiveColor = options.tabBarInactiveTintColor || "#666";

        // Get the tab style - merge default with custom style
        const tabBarItemStyle = options.tabBarItemStyle || {};

        return (
          <TouchableOpacity
            // Key prop is properly passed directly to the JSX element
            key={route.key}
            accessibilityRole="tab"
            accessibilityState={isFocused ? { selected: true } : {}}
            accessibilityLabel={options.tabBarAccessibilityLabel}
            testID={options.tabBarTestID}
            onPress={onPress}
            onLongPress={onLongPress}
            style={[styles.tabItem, tabBarItemStyle]}
          >
            <Text
              style={[
                styles.tabText,
                { color: isFocused ? activeColor : inactiveColor },
                options.tabBarLabelStyle,
              ]}
            >
              {typeof label === "string" ? label : (label as any)?.toString()}
            </Text>
            {isFocused && (
              <View
                style={[styles.indicator, { backgroundColor: activeColor }]}
              />
            )}
          </TouchableOpacity>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  tabBar: {
    flexDirection: "row",
    backgroundColor: "#fff",
  },
  tabItem: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 16,
    position: "relative",
  },
  tabText: {
    fontWeight: "bold",
    fontSize: 14,
  },
  indicator: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    height: 3,
  },
});

export default SafeTopTabBar;
