declare module "expo-linear-gradient" {
  import { ComponentType } from "react";
  import { ViewProps } from "react-native";

  export interface LinearGradientProps extends ViewProps {
    colors: string[];
    start?: [number, number];
    end?: [number, number];
    locations?: number[];
  }

  // Support for named import: import { LinearGradient } from "expo-linear-gradient"
  export const LinearGradient: ComponentType<LinearGradientProps>;

  // Provide proper type support for default import: import LinearGradient from "expo-linear-gradient"
  declare const LinearGradientComponent: ComponentType<LinearGradientProps>;
  export default LinearGradientComponent;
}
