import React from "react";
import { Slot } from "expo-router";
import { Stack } from "expo-router/stack";
import { StatusBar } from "expo-status-bar";

export default function FarmerLayout() {
  return (
    <>
      <StatusBar style="auto" />
      <Stack
        screenOptions={{
          headerShown: false,
          animation: "slide_from_right",
        }}
      >
        <Stack.Screen name="dashboard" />
        <Stack.Screen name="orders" />
        <Stack.Screen name="products" />
        <Stack.Screen name="profile" />
      </Stack>
    </>
  );
}
