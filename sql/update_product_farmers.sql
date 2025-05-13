-- SQL Script to properly associate products with farmers
-- This ensures all products have a farmer assigned to them based on
-- the product type and growing location 

-- First, let's see if any of our newly added products don't have farmer_id values
UPDATE products 
SET farmer_id = 42 -- Maria Santos (Balayagmanok farmer)
WHERE name IN ('Lanzones (Lansium)', 'Tanglad (Lemongrass)')
AND (farmer_id IS NULL OR farmer_id = 0);

UPDATE products 
SET farmer_id = 43 -- Pedro Reyes (Malabo farmer)
WHERE name IN ('Duhat (Java Plum)', 'Bataw (Hyacinth Bean)', 'Luyang Dilaw (Turmeric)')
AND (farmer_id IS NULL OR farmer_id = 0);

UPDATE products 
SET farmer_id = 44 -- Juan Dela Cruz (Sagbang farmer)
WHERE name IN ('Native Lakatan Banana', 'Gabi (Taro)', 'Valencia Arabica Coffee Beans')
AND (farmer_id IS NULL OR farmer_id = 0);

UPDATE products 
SET farmer_id = 45 -- Teresa Gomez (Jawa farmer)
WHERE name IN ('Tagabang (Winged Bean)', 'Ube (Purple Yam)', 'Balayagmanok Cacao Beans')
AND (farmer_id IS NULL OR farmer_id = 0);

-- Update previously existing products with missing farmer associations
UPDATE products 
SET farmer_id = 42 -- Maria Santos
WHERE product_id = 38 -- Sugar Cane 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Assign Alugbati to Maria Santos since it mentions West Balabag
UPDATE products 
SET farmer_id = 42
WHERE name = 'Alugbati (Malabar Spinach)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Make sure our updated existing products are correctly linked to farmers
-- Rice from Ayungon - assign to farmer in Palinpinon area who might distribute it
UPDATE products 
SET farmer_id = 20
WHERE product_id = 8 AND name = 'Ayungon Red Rice' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- For Lemon Basil (Sangig) - assign to David Moore who specializes in herbs
UPDATE products 
SET farmer_id = 20
WHERE product_id = 9 AND name = 'Lemon Basil (Sangig)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Valencia Sweet Potatoes - assign to David Moore since it's a root crop
UPDATE products 
SET farmer_id = 20
WHERE product_id = 10 AND name = 'Valencia Purple Sweet Potatoes (Kamote)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Mabinay Sugar Cane - assign to Maria Santos (product 38)
UPDATE products 
SET farmer_id = 42
WHERE product_id = 38 AND name = 'Mabinay Sugar Cane' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Fresh Malunggay from Palinpinon farms - assign to Anna Lee
UPDATE products 
SET farmer_id = 19
WHERE product_id = 45 AND name = 'Fresh Malunggay (Moringa)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Santol from Balili - assign to David Moore
UPDATE products 
SET farmer_id = 20
WHERE product_id = 48 AND name = 'Balili Santol (Cotton Fruit)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Kalamansi - assign to Anna Lee
UPDATE products 
SET farmer_id = 19
WHERE product_id = 53 AND name = 'Organic Kalamansi (Philippine Lime)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Dalandan from Cambucad - assign to Anna Lee
UPDATE products 
SET farmer_id = 19
WHERE product_id = 54 AND name = 'Cambucad Dalandan (Philippine Orange)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Sayote from highland regions - assign to David Moore
UPDATE products 
SET farmer_id = 20
WHERE product_id = 56 AND name = 'Highland Sayote (Chayote)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Repolyo from Sagbang - assign to David Moore
UPDATE products 
SET farmer_id = 20
WHERE product_id = 57 AND name = 'Mountain-Grown Repolyo (Cabbage)' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Organic Carrots from Jawa - assign to David Moore
UPDATE products 
SET farmer_id = 20
WHERE product_id = 58 AND name = 'Organic Carrots' 
AND (farmer_id IS NULL OR farmer_id = 0);

-- Update the most recent barangay_products entries to match the farmer's barangay
-- This ensures consistency between product origins and farmer locations
UPDATE barangay_products bp
JOIN products p ON bp.product_id = p.product_id
JOIN farmer_details fd ON p.farmer_id = fd.user_id
SET bp.barangay_id = fd.barangay_id
WHERE fd.barangay_id IS NOT NULL AND bp.id > 120;