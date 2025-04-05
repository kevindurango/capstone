import { api } from "./api";

export interface LoginCredentials {
  email: string;
  password: string;
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

export const authService = {
  async login(credentials: LoginCredentials) {
    return api.fetch<{ token: string; user: any }>("/login", {
      method: "POST",
      body: JSON.stringify(credentials),
    });
  },

  async register(data: RegisterData) {
    return api.fetch<{ token: string; user: any }>("/register", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  async logout() {
    return api.fetch("/logout", {
      method: "POST",
    });
  },
};
