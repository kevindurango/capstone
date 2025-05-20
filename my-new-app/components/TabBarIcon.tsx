import React from "react";
import { Ionicons } from "@expo/vector-icons";

interface TabBarIconProps {
  name: React.ComponentProps<typeof Ionicons>["name"];
  color: string;
  size?: number;
}

export function TabBarIcon({ name, color, size = 24 }: TabBarIconProps) {
  return <Ionicons name={name} size={size} color={color} />;
}
