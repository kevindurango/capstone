import { api } from "./api";

export interface Product {
  product_id: number;
  name: string;
  description: string;
  price: number;
  farmer_id: number;
  status: "pending" | "approved" | "rejected";
  image: string;
  stock: number;
}

export const productService = {
  async getProducts() {
    return api.fetch<Product[]>("/products");
  },

  async getProduct(id: number) {
    return api.fetch<Product>(`/products/${id}`);
  },

  async createProduct(data: Partial<Product>) {
    return api.fetch<Product>("/products", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  // Add other product-related methods
};
