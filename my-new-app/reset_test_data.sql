-- Reset Test Data Script
-- Created: April 27, 2025
-- This script resets product stock levels to their baseline values
-- and removes all consumer transactions (orders, payments, pickups)

-- Step 1: Start a transaction
START TRANSACTION;

-- Step 2: Log the reset operation
INSERT INTO `activitylogs` (`user_id`, `action`, `action_date`) 
VALUES (21, 'Database reset initiated: clearing orders, pickups, payments and resetting stock levels', NOW());

-- Step 3: First handle payment_status_history (child table with FK to payments)
DELETE FROM `payment_status_history`;

-- Step 4: Delete from payment_retries (if any exist with FK to payments)
DELETE FROM `payment_retries`;

-- Step 5: Delete from payment_credit_cards (if any exist with FK to payments)
DELETE FROM `payment_credit_cards`;

-- Step 6: Clear pickup records (they reference both orders and payments)
UPDATE `pickups` SET `payment_id` = NULL;
DELETE FROM `pickups`;

-- Step 7: Delete payment records
DELETE FROM `payments`;

-- Step 8: Delete order items (they reference orders)
DELETE FROM `orderitems`;

-- Step 9: Delete all orders
DELETE FROM `orders`;

-- Step 10: Reset auto-increment counters
ALTER TABLE `orderitems` AUTO_INCREMENT = 1;
ALTER TABLE `orders` AUTO_INCREMENT = 1;
ALTER TABLE `pickups` AUTO_INCREMENT = 1;
ALTER TABLE `payment_status_history` AUTO_INCREMENT = 1;
ALTER TABLE `payment_credit_cards` AUTO_INCREMENT = 1;
ALTER TABLE `payment_retries` AUTO_INCREMENT = 1;
ALTER TABLE `payments` AUTO_INCREMENT = 1;

-- Step 11: Reset product stock levels to baseline values
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 6;  -- Mango
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 7;   -- Calamansi
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 8;  -- Ayungon Rice
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 9;   -- Lemon Basil
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 10; -- Organic Sweet Potatoes
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 19;  -- kamote
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 22;  -- kangkong
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 34;  -- kamatis
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 38;  -- Sugar Cane
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 41; -- Kamote (Sweet Potato)
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 42;  -- Ube (Purple Yam)
UPDATE `products` SET `stock` = 75 WHERE `product_id` = 43;  -- Gabi (Taro)
UPDATE `products` SET `stock` = 80 WHERE `product_id` = 44;  -- Kangkong (Water Spinach)
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 45; -- Malunggay (Moringa)
UPDATE `products` SET `stock` = 90 WHERE `product_id` = 46;  -- Pechay (Bok Choy)
UPDATE `products` SET `stock` = 100 WHERE `product_id` = 47; -- Saging Saba (Cooking Banana)
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 48;  -- Santol
UPDATE `products` SET `stock` = 30 WHERE `product_id` = 49;  -- Lanzones
UPDATE `products` SET `stock` = 70 WHERE `product_id` = 50;  -- Talong (Eggplant)
UPDATE `products` SET `stock` = 65 WHERE `product_id` = 51;  -- Okra
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 52;  -- Ampalaya (Bitter Gourd)
UPDATE `products` SET `stock` = 60 WHERE `product_id` = 53;  -- Kalamansi
UPDATE `products` SET `stock` = 60 WHERE `product_id` = 54;  -- Dalandan
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 55;  -- Dayap (Key Lime)
UPDATE `products` SET `stock` = 75 WHERE `product_id` = 56;  -- Sayote (Chayote)
UPDATE `products` SET `stock` = 50 WHERE `product_id` = 57;  -- Repolyo (Cabbage)
UPDATE `products` SET `stock` = 60 WHERE `product_id` = 58;  -- Carrots
UPDATE `products` SET `stock` = 150 WHERE `product_id` = 59; -- Tanglad (Lemongrass)
UPDATE `products` SET `stock` = 60 WHERE `product_id` = 60;  -- Luya (Ginger)
UPDATE `products` SET `stock` = 200 WHERE `product_id` = 61; -- Dahon ng Sili (Chili Leaves)

-- Step 12: Add a record of completion
INSERT INTO `activitylogs` (`user_id`, `action`, `action_date`) 
VALUES (21, 'Successfully reset database: cleared transactions and restored product stock levels', NOW());

-- Step 13: Commit the transaction
COMMIT;

-- If you encounter any errors, you can rollback with: ROLLBACK;
