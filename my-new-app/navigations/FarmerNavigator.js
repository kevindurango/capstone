import React from "react";
import { createStackNavigator } from "@react-navigation/stack";
import FarmerDashboard from "../screens/FarmerDashboard";
import OtherScreen from "../screens/OtherScreen";

const Stack = createStackNavigator();

const FarmerNavigator = () => {
  return (
    <Stack.Navigator>
      <Stack.Screen
        name="dashboard"
        component={FarmerDashboard}
        options={{
          title: "Farmer Dashboard",
          headerLeft: () => null, // This removes the back button
        }}
      />
      <Stack.Screen
        name="other"
        component={OtherScreen}
        options={{ title: "Other Screen" }}
      />
    </Stack.Navigator>
  );
};

export default FarmerNavigator;
