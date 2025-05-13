declare module "expo-linear-gradient" {
  import { ComponentType } from "react";
  import { ViewProps } from "react-native";

  export interface LinearGradientProps extends ViewProps {
    colors: string[];
    start?: [number, number];
    end?: [number, number];
    locations?: number[];
  }

  const LinearGradient: ComponentType<LinearGradientProps>;

  export default LinearGradient;
}
