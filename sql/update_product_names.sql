-- SQL script to update product names by removing barangay/location qualifiers

-- Update "Valencia Red Rice" -> "Red Rice"
UPDATE `products` 
SET `name` = 'Red Rice' 
WHERE `product_id` = 8;

-- Update "Cambucad Dalandan (Philippine Orange)" -> "Dalandan (Philippine Orange)"
UPDATE `products` 
SET `name` = 'Dalandan (Philippine Orange)' 
WHERE `product_id` = 54;

-- Update "Highland Sayote (Chayote)" -> "Sayote (Chayote)"
UPDATE `products` 
SET `name` = 'Sayote (Chayote)' 
WHERE `product_id` = 56;

-- Update "Balili Santol (Cotton Fruit)" -> "Santol (Cotton Fruit)"
UPDATE `products` 
SET `name` = 'Santol (Cotton Fruit)' 
WHERE `product_id` = 48;

-- Update "Mountain-Grown Repolyo (Cabbage)" -> "Repolyo (Cabbage)"
UPDATE `products` 
SET `name` = 'Repolyo (Cabbage)' 
WHERE `product_id` = 57;

-- Update "Valencia Purple Sweet Potatoes (Kamote)" -> "Purple Sweet Potatoes (Kamote)"
UPDATE `products` 
SET `name` = 'Purple Sweet Potatoes (Kamote)' 
WHERE `product_id` = 10;

-- Update "Native Lakatan Banana" -> "Lakatan Banana" 
UPDATE `products` 
SET `name` = 'Lakatan Banana' 
WHERE `product_id` = 64;

-- Update "Valencia Arabica Coffee Beans" -> "Arabica Coffee Beans"
UPDATE `products` 
SET `name` = 'Arabica Coffee Beans' 
WHERE `product_id` = 72;

-- Update "Balayagmanok Cacao Beans" -> "Cacao Beans"
UPDATE `products` 
SET `name` = 'Cacao Beans' 
WHERE `product_id` = 73;