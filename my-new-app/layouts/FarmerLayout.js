import React from "react";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import FarmerDashboard from "../screens/FarmerDashboard";

const Stack = createNativeStackNavigator();

const FarmerLayout = () => {
  return (
    <Stack.Navigator>
      <Stack.Screen
        name="dashboard"
        component={FarmerDashboard}
        options={{
          title: "Farmer Dashboard",
          headerLeft: () => null, // Remove back button
          headerBackVisible: false, // Explicitly hide back button
          headerBackButtonMenuEnabled: false, // Disable back button menu
        }}
      />
    </Stack.Navigator>
  );
};

export default FarmerLayout;
