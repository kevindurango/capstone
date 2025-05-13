-- SQL Script to add new local agricultural products
-- These products are common in the Valencia/Negros Oriental region

-- Add more indigenous/local products
INSERT INTO products (name, description, price, farmer_id, status, created_at, updated_at, stock, unit_type) VALUES
-- Native Fruits
('Lanzones (Lansium)', 'Sweet and juicy lanzones grown in the highlands of Valencia. These yellow-brown skinned fruits have translucent, sweet flesh arranged in segments. Seasonally available during late summer to early fall.', 120.00, 42, 'approved', NOW(), NOW(), 80, 'kilogram'),

('Duhat (Java Plum)', 'Dark purple to black berries with sweet-tart flesh harvested from trees in Dobdob. Rich in antioxidants and has cooling properties according to traditional medicine. Available seasonally from May to July.', 90.00, 43, 'approved', NOW(), NOW(), 60, 'kilogram'),

('Native Lakatan Banana', 'Sweet, aromatic Lakatan bananas grown in Palinpinon. These golden yellow bananas have firmer flesh than regular varieties with a distinct sweet flavor and aroma. Harvested at optimal ripeness.', 65.00, 44, 'approved', NOW(), NOW(), 150, 'bunch'),

-- Local Vegetables
('Tagabang (Winged Bean)', 'Locally grown winged beans with distinctive four-angled edges. The entire plant is edible - young pods, mature seeds, shoots, flowers, and tubers. Rich in protein and commonly used in local dishes.', 40.00, 45, 'approved', NOW(), NOW(), 85, 'kilogram'),

('Alugbati (Malabar Spinach)', 'Glossy, thick leaves with a mild flavor harvested from vines in West Balabag. This heat-loving green vegetable is rich in vitamins and minerals. Used in soups, stir-fries, and blanched as a side dish.', 30.00, 42, 'approved', NOW(), NOW(), 120, 'bunch'),

('Bataw (Hyacinth Bean)', 'Purple-tinged flat bean pods grown in Balili area. Young pods are tender and delicious while mature seeds can be dried and used in soups and stews. A traditional vegetable in local cuisine.', 35.00, 43, 'approved', NOW(), NOW(), 90, 'kilogram'),

-- Root Crops
('Gabi (Taro)', 'Starchy taro corms with nutty flavor harvested in Lunga. The underground corm has brown skin and white to lavender flesh. Used in both savory dishes and desserts like ginataang gabi.', 55.00, 44, 'approved', NOW(), NOW(), 100, 'kilogram'),

('Ube (Purple Yam)', 'Vibrant purple yams grown in volcanic soil of Puhagan. These root crops have an intensely sweet, nutty flavor and vivid purple color. Perfect for traditional Filipino desserts and pastries.', 95.00, 45, 'approved', NOW(), NOW(), 75, 'kilogram'),

-- Spices and Herbs
('Tanglad (Lemongrass)', 'Aromatic lemongrass stalks from Liptong farms. This fragrant herb has a subtle citrus flavor and is used in teas, soups, and as a flavoring for rice and meat dishes. Also valued for medicinal properties.', 15.00, 42, 'approved', NOW(), NOW(), 200, 'bundle'),

('Luyang Dilaw (Turmeric)', 'Fresh turmeric rhizomes with bright orange flesh grown in Caidiocan. This aromatic spice has earthy, peppery flavor and powerful anti-inflammatory properties. Used in cooking and traditional medicine.', 70.00, 43, 'approved', NOW(), NOW(), 60, 'kilogram'),

-- Commercial Crops
('Valencia Arabica Coffee Beans', 'Shade-grown Arabica coffee beans from the highlands of Valencia. These carefully processed beans have complex flavor notes of chocolate, citrus, and caramel. Grown at higher elevations for superior quality.', 350.00, 44, 'approved', NOW(), NOW(), 40, 'kilogram'),

('Balayagmanok Cacao Beans', 'Fermented and dried cacao beans from Balayagmanok farms. These premium beans have rich chocolate flavor with fruity notes. Perfect for making artisanal chocolate or traditional tablea for hot chocolate.', 280.00, 45, 'approved', NOW(), NOW(), 55, 'kilogram');

-- Add category mappings for new products
-- Lanzones
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Lanzones (Lansium)'), 17); -- Native Fruits
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Lanzones (Lansium)'), 1);  -- Fruit

-- Duhat
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Duhat (Java Plum)'), 17); -- Native Fruits
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Duhat (Java Plum)'), 1);  -- Fruit

-- Native Lakatan Banana
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Native Lakatan Banana'), 20); -- Banana Varieties
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Native Lakatan Banana'), 1);  -- Fruit

-- Tagabang
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Tagabang (Winged Bean)'), 27); -- Local Beans
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Tagabang (Winged Bean)'), 2);  -- Vegetable

-- Alugbati
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Alugbati (Malabar Spinach)'), 6);  -- Leafy Vegetables
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Alugbati (Malabar Spinach)'), 12); -- Indigenous Crops

-- Bataw
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Bataw (Hyacinth Bean)'), 16); -- Legumes
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Bataw (Hyacinth Bean)'), 27); -- Local Beans

-- Gabi
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Gabi (Taro)'), 5);  -- Root Crops
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Gabi (Taro)'), 15); -- Root Tubers

-- Ube
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Ube (Purple Yam)'), 5);  -- Root Crops
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Ube (Purple Yam)'), 15); -- Root Tubers

-- Tanglad
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Tanglad (Lemongrass)'), 4);  -- Herb
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Tanglad (Lemongrass)'), 22); -- Spices

-- Luyang Dilaw
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Luyang Dilaw (Turmeric)'), 22); -- Spices
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Luyang Dilaw (Turmeric)'), 18); -- Medicinal Plants

-- Valencia Arabica Coffee Beans
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Valencia Arabica Coffee Beans'), 21); -- Commercial Crops

-- Balayagmanok Cacao Beans
INSERT INTO productcategorymapping (product_id, category_id) 
VALUES ((SELECT product_id FROM products WHERE name = 'Balayagmanok Cacao Beans'), 21); -- Commercial Crops