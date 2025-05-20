import {
  createContext,
  useContext,
  useState,
  useCallback,
  useEffect,
} from "react";
import { authService, LoginCredentials, RegisterData } from "@/services/auth";
import AsyncStorage from "@react-native-async-storage/async-storage";

type AuthContextType = {
  isLoading: boolean;
  user: any | null;
  isAuthenticated: boolean;
  isFarmer: boolean;
  isConsumer: boolean;
  userRole: number | null; // Add explicit userRole property
  login: (credentials: LoginCredentials) => Promise<any>;
  logout: () => Promise<void>;
  register: (userData: RegisterData) => Promise<any>;
  updateUserContext: (userData: any) => void;
};

// Storage keys
const USER_STORAGE_KEY = "@farmers_market_user";
const LEGACY_USER_KEY = "user_data"; // The key used in authService.js
const AUTH_TOKEN_KEY = "auth_token"; // Match the key in authService.js

// Role IDs based on your database schema
const ROLE_CONSUMER = 1;
const ROLE_FARMER = 2;
const ROLE_ADMIN = 3;
const ROLE_MANAGER = 4;
const ROLE_ORG_HEAD = 5;

export const AuthContext = createContext<AuthContextType>({
  isLoading: true,
  user: null,
  isAuthenticated: false,
  isFarmer: false,
  isConsumer: false,
  userRole: null, // Initialize userRole
  login: async () => {},
  logout: async () => {},
  register: async () => {},
  updateUserContext: () => {},
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true); // Start with loading true

  // Derived state for auth status and roles
  const isAuthenticated = !!user;
  const userRole = user?.role_id || null;

  // Role-specific boolean flags for easy access control
  const isFarmer = userRole === ROLE_FARMER;
  const isAdmin = userRole === ROLE_ADMIN;
  const isManager = userRole === ROLE_MANAGER;
  const isOrgHead = userRole === ROLE_ORG_HEAD;
  const isConsumer = userRole === ROLE_CONSUMER;

  // Check for existing session when the app launches
  useEffect(() => {
    const loadStoredUser = async () => {
      try {
        // First check token existence
        const token = await AsyncStorage.getItem(AUTH_TOKEN_KEY);
        if (!token) {
          console.log(
            "[Auth Context] No auth token found, user is not authenticated"
          );
          setIsLoading(false);
          return;
        }

        // If token exists, load user data
        const storedUser = await AsyncStorage.getItem(USER_STORAGE_KEY);

        if (storedUser) {
          setUser(JSON.parse(storedUser));
          console.log(
            "[Auth Context] Restored user session from primary storage"
          );
        } else {
          // If not found, check legacy storage
          const legacyUser = await AsyncStorage.getItem(LEGACY_USER_KEY);
          if (legacyUser) {
            const userData = JSON.parse(legacyUser);
            setUser(userData);
            // Also save to our primary storage for future use
            await AsyncStorage.setItem(USER_STORAGE_KEY, legacyUser);
            console.log(
              "[Auth Context] Restored user from legacy storage and saved to primary"
            );
          } else {
            console.log("[Auth Context] No stored user session found");
            // If no user data but token exists, create a minimal user object
            setUser({ isAuthenticated: true });
          }
        }
      } catch (error) {
        console.error(
          "[Auth Context] Failed to load user from storage:",
          error
        );
      } finally {
        setIsLoading(false);
      }
    };

    loadStoredUser();
  }, []);

  const login = useCallback(async (credentials: LoginCredentials) => {
    setIsLoading(true);
    try {
      const response = await authService.login(credentials);

      // Special handling for when the response doesn't match our expected format
      const userData = response.user || {
        // Fallback if the response format doesn't match
        email: credentials.email,
        user_id: Date.now(), // Just a placeholder until we get proper data
      };

      // Set user in state
      setUser(userData);

      // Store user data securely in both locations
      await AsyncStorage.setItem(USER_STORAGE_KEY, JSON.stringify(userData));
      await AsyncStorage.setItem(LEGACY_USER_KEY, JSON.stringify(userData));

      // Store auth token to match what authService.isAuthenticated() checks for
      await AsyncStorage.setItem(
        AUTH_TOKEN_KEY,
        response.token || "dummy_token"
      );

      console.log(
        "[Auth Context] User logged in and saved to storage:",
        userData
      );

      // Log the role for debugging
      console.log("[Auth Context] User role_id:", userData.role_id);

      return userData;
    } catch (error) {
      console.error("[Auth Context] Login error:", error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const register = useCallback(async (data: RegisterData) => {
    setIsLoading(true);
    try {
      const response = await authService.register(data);
      setUser(response.user);

      // Store user data securely
      await AsyncStorage.setItem(
        USER_STORAGE_KEY,
        JSON.stringify(response.user)
      );
      await AsyncStorage.setItem(
        LEGACY_USER_KEY,
        JSON.stringify(response.user)
      );

      // Store auth token for consistency with authService
      await AsyncStorage.setItem(
        AUTH_TOKEN_KEY,
        response.token || "dummy_token"
      );

      console.log("[Auth Context] User registered and saved to storage");
      return response.user;
    } catch (error) {
      console.error("[Auth Context] Registration error:", error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    setIsLoading(true);
    try {
      await authService.logout();
      setUser(null);

      // Clear ALL auth-related storage
      await AsyncStorage.removeItem(USER_STORAGE_KEY);
      await AsyncStorage.removeItem(LEGACY_USER_KEY);
      await AsyncStorage.removeItem(AUTH_TOKEN_KEY);

      console.log("[Auth Context] User logged out, storage cleared");
    } finally {
      setIsLoading(false);
    }
  }, []);

  const updateUserContext = useCallback((userData: any) => {
    setUser(userData);
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        login,
        register,
        logout,
        isLoading,
        isAuthenticated,
        isFarmer,
        isConsumer,
        userRole, // Expose userRole directly in the context
        updateUserContext,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};
