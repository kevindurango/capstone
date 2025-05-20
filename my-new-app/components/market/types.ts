export interface Product {
  id: number;
  name: string;
  description?: string;
  price: number;
  quantity_available: number;
  unit: string;
  category?: string;
  farm_name?: string;
  farmer?: string;
  contact?: string;
  // Support both image naming conventions that might be used
  image?: string | null;
  image_url?: string | null;
  // Add other properties as needed
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface PaymentMethod {
  method_id: number;
  method_name: string;
  is_active: boolean;
}

export interface CardDetails {
  card_id?: number;
  payment_id: number;
  card_last_four: string;
  card_brand: string;
  card_expiry_month: number;
  card_expiry_year: number;
}

export interface PaymentStatus {
  history_id?: number;
  payment_id: number;
  status: "pending" | "completed" | "failed";
  status_date?: string;
  status_notes?: string;
}

export interface Payment {
  payment_id: number;
  order_id: number;
  method_id: number;
  user_id: number;
  amount: number;
  transaction_reference?: string;
  payment_notes?: string;
  current_status: "pending" | "completed" | "failed";
  payment_date: string;
  card_details?: CardDetails;
  status_history?: PaymentStatus[];
}

// Categories for filter - updated to match the database
export const CATEGORIES = [
  { id: "", name: "All" },
  { id: "Fruit", name: "Fruit" },
  { id: "Vegetable", name: "Vegetable" },
  { id: "Grain", name: "Grain" },
  { id: "Herb", name: "Herb" },
  { id: "Root Crops", name: "Root Crops" },
  { id: "Leafy Vegetables", name: "Leafy Vegetables" },
  { id: "Tropical Fruits", name: "Tropical Fruits" },
  { id: "Lowland Vegetables", name: "Lowland Vegetables" },
  { id: "Highland Vegetables", name: "Highland Vegetables" },
  { id: "Rice Varieties", name: "Rice Varieties" },
  { id: "Coconut Products", name: "Coconut Products" },
  { id: "Indigenous Crops", name: "Indigenous Crops" },
  { id: "Citrus", name: "Citrus" },
  { id: "Local Herbs", name: "Local Herbs" },
  { id: "Root Tubers", name: "Root Tubers" },
  { id: "Legumes", name: "Legumes" },
  { id: "Native Fruits", name: "Native Fruits" },
  { id: "Medicinal Plants", name: "Medicinal Plants" },
  { id: "Organic Produce", name: "Organic Produce" },
  { id: "Banana Varieties", name: "Banana Varieties" },
  { id: "Commercial Crops", name: "Commercial Crops" },
  { id: "Spices", name: "Spices" },
  { id: "Mushrooms", name: "Mushrooms" },
  { id: "Aquaculture", name: "Aquaculture" },
  { id: "Fermented Foods", name: "Fermented Foods" },
  { id: "Tree Fruits", name: "Tree Fruits" },
  { id: "Local Beans", name: "Local Beans" },
  { id: "Native Nuts", name: "Native Nuts" },
  { id: "Fibers and Craft Materials", name: "Fibers and Craft Materials" },
];
