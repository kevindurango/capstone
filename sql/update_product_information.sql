-- SQL Script to update products with more realistic information
-- This will update existing products with more accurate descriptions, pricing, and categorization

-- Update Rice (product_id 8)
UPDATE products SET 
    name = 'Ayungon Red Rice',
    description = 'Organic red rice grown in the highlands of Ayungon. Rich in antioxidants with a nutty flavor and slightly chewy texture. Harvested using traditional methods by local farmers.',
    price = 85.00,
    stock = 350,
    unit_type = 'kilogram'
WHERE product_id = 8;

-- Update Lemon Basil (product_id 9)
UPDATE products SET 
    name = 'Lemon Basil (Sangig)',
    description = 'Locally grown aromatic lemon basil, perfect for salads, teas, and Filipino dishes. The leaves have a strong citrus scent and distinctive flavor that enhances both savory and sweet recipes.',
    price = 15.00,
    stock = 100,
    unit_type = 'bunch'
WHERE product_id = 9;

-- Update Sweet Potatoes (product_id 10)
UPDATE products SET 
    name = 'Valencia Purple Sweet Potatoes (Kamote)',
    description = 'Nutrient-rich purple sweet potatoes grown in the volcanic soil of Valencia. These purple-fleshed varieties have higher antioxidant content than regular varieties, with a sweet flavor perfect for both savory dishes and desserts.',
    price = 45.00,
    stock = 180,
    unit_type = 'kilogram'
WHERE product_id = 10;

-- Update Sugar Cane (product_id 38)
UPDATE products SET 
    name = 'Mabinay Sugar Cane',
    description = 'Naturally sweet sugar cane stalks harvested from the Mabinay-Bais area. Perfect for extracting fresh juice or chewing as a healthy natural snack. Contains essential minerals and natural sugars.',
    price = 25.00,
    stock = 120,
    unit_type = 'bundle'
WHERE product_id = 38;

-- Update Malunggay (product_id 45)
UPDATE products SET 
    name = 'Fresh Malunggay (Moringa)',
    description = 'Highly nutritious moringa leaves harvested from Palinpinon farms. Known locally as the "miracle tree" due to its exceptional nutrient profile. Perfect for soups, stews, and as a health supplement.',
    price = 12.50,
    stock = 250,
    unit_type = 'bunch'
WHERE product_id = 45;

-- Update Santol (product_id 48)
UPDATE products SET 
    name = 'Balili Santol (Cotton Fruit)',
    description = 'Sweet and tangy santol fruits from the orchards of Balili. These medium-sized fruits have a perfect balance of sweet and sour flavors. The white pulp can be eaten fresh or made into preserves and candies.',
    price = 75.00,
    stock = 120,
    unit_type = 'kilogram'
WHERE product_id = 48;

-- Update Kalamansi (product_id 53)
UPDATE products SET 
    name = 'Organic Kalamansi (Philippine Lime)',
    description = 'Small, fragrant citrus fruits essential to Filipino cuisine. These organically grown kalamansi from Valencia are more flavorful than commercial varieties. Used for juices, marinades, and as a natural cleaning agent.',
    price = 60.00,
    stock = 200,
    unit_type = 'kilogram'
WHERE product_id = 53;

-- Update Dalandan (product_id 54)
UPDATE products SET 
    name = 'Cambucad Dalandan (Philippine Orange)',
    description = 'Sweet and juicy local oranges harvested from Cambucad area. These green-skinned citrus fruits have a refreshing sweet-tart flavor, more juice content and thinner skin than imported varieties. Perfect for fresh juice.',
    price = 80.00,
    stock = 150,
    unit_type = 'kilogram'
WHERE product_id = 54;

-- Update Sayote (product_id 56)
UPDATE products SET 
    name = 'Highland Sayote (Chayote)',
    description = 'Crisp, pale green squash grown in the cooler highland regions of Valencia. These versatile vegetables have a mild flavor that absorbs the taste of whatever they\'re cooked with. Popular in soups, stir-fries, and Filipino vegetable dishes.',
    price = 35.00,
    stock = 230,
    unit_type = 'kilogram'
WHERE product_id = 56;

-- Update Repolyo (product_id 57)
UPDATE products SET 
    name = 'Mountain-Grown Repolyo (Cabbage)',
    description = 'Fresh, compact cabbage heads grown in the cool mountain farms of Sagbang. These cabbages have tightly packed, crisp leaves perfect for salads, soups, and traditional Filipino dishes like lumpia and pancit.',
    price = 50.00,
    stock = 180,
    unit_type = 'kilogram'
WHERE product_id = 57;

-- Update Carrots (product_id 58)
UPDATE products SET 
    name = 'Organic Carrots',
    description = 'Sweet, crunchy carrots organically grown in nutrient-rich soil from the highland farms of Jawa. These bright orange root vegetables have exceptional flavor and are perfect for soups, stews, and salads.',
    price = 65.00,
    stock = 210,
    unit_type = 'kilogram'
WHERE product_id = 58;

-- Add regional information to product descriptions if it doesn't exist
UPDATE products SET 
    description = CONCAT(description, ' Grown by local farmers in Valencia, Negros Oriental.')
WHERE description NOT LIKE '%Valencia%' 
AND description NOT LIKE '%Negros Oriental%'
AND product_id IN (8, 9, 10, 45, 48, 53, 54, 56, 57, 58);

-- Update category mappings for more accurate categorization
-- First delete existing mappings
DELETE FROM productcategorymapping 
WHERE product_id IN (8, 9, 10, 38, 45, 48, 53, 54, 56, 57, 58);

-- Now insert accurate categories
-- Rice
INSERT INTO productcategorymapping (product_id, category_id) VALUES (8, 10); -- Rice Varieties
INSERT INTO productcategorymapping (product_id, category_id) VALUES (8, 3);  -- Grain

-- Lemon Basil
INSERT INTO productcategorymapping (product_id, category_id) VALUES (9, 4);  -- Herb
INSERT INTO productcategorymapping (product_id, category_id) VALUES (9, 14); -- Local Herbs

-- Sweet Potatoes
INSERT INTO productcategorymapping (product_id, category_id) VALUES (10, 5); -- Root Crops
INSERT INTO productcategorymapping (product_id, category_id) VALUES (10, 15); -- Root Tubers

-- Sugar Cane
INSERT INTO productcategorymapping (product_id, category_id) VALUES (38, 21); -- Commercial Crops

-- Malunggay
INSERT INTO productcategorymapping (product_id, category_id) VALUES (45, 6);  -- Leafy Vegetables
INSERT INTO productcategorymapping (product_id, category_id) VALUES (45, 18); -- Medicinal Plants

-- Santol
INSERT INTO productcategorymapping (product_id, category_id) VALUES (48, 17); -- Native Fruits
INSERT INTO productcategorymapping (product_id, category_id) VALUES (48, 26); -- Tree Fruits

-- Kalamansi
INSERT INTO productcategorymapping (product_id, category_id) VALUES (53, 13); -- Citrus
INSERT INTO productcategorymapping (product_id, category_id) VALUES (53, 1);  -- Fruit

-- Dalandan
INSERT INTO productcategorymapping (product_id, category_id) VALUES (54, 13); -- Citrus
INSERT INTO productcategorymapping (product_id, category_id) VALUES (54, 1);  -- Fruit

-- Sayote
INSERT INTO productcategorymapping (product_id, category_id) VALUES (56, 8);  -- Lowland Vegetables
INSERT INTO productcategorymapping (product_id, category_id) VALUES (56, 2);  -- Vegetable

-- Repolyo
INSERT INTO productcategorymapping (product_id, category_id) VALUES (57, 8);  -- Lowland Vegetables
INSERT INTO productcategorymapping (product_id, category_id) VALUES (57, 2);  -- Vegetable

-- Carrots
INSERT INTO productcategorymapping (product_id, category_id) VALUES (58, 5);  -- Root Crops
INSERT INTO productcategorymapping (product_id, category_id) VALUES (58, 2);  -- Vegetable