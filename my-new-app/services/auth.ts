import { api } from "./api";

export interface LoginCredentials {
  email: string;
  password: string;
  login_type?: "farmer" | "consumer"; // Added login_type for farmer/consumer distinction
}

export interface RegisterData {
  username: string;
  email: string;
  password: string;
  first_name: string;
  last_name: string;
  contact_number?: string;
  address?: string;
  role_id: number;
}

export interface ProfileUpdateData {
  user_id: number;
  first_name: string;
  last_name: string;
  email: string;
  username?: string;
  contact_number?: string;
  address?: string;
}

export const authService = {
  async login(credentials: LoginCredentials) {
    return api.fetch<{ token: string; user: any }>("/login.php", {
      method: "POST",
      body: JSON.stringify(credentials),
    });
  },

  async register(data: RegisterData) {
    return api.fetch<{ token: string; user: any }>("/register.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  async logout() {
    return api.fetch("/logout.php", {
      method: "POST",
    });
  },

  async updateProfile(data: ProfileUpdateData) {
    return api.fetch<{ status: string; message: string; user: any }>(
      "/update_profile.php",
      {
        method: "POST",
        body: JSON.stringify(data),
      }
    );
  },
};
