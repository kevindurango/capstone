export interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  original_price: number;
  discount: number;
  stock: number;
  unit?: string;
  image_url: string | null;
  category_id?: number;
  category_name?: string;
  seller_id?: number;
  seller_name?: string;
  rating?: number;
  created_at?: string;
  updated_at?: string;
}
