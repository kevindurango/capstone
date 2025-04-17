-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2025 at 04:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `farmersmarketdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activitylogs`
--

CREATE TABLE `activitylogs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activitylogs`
--

INSERT INTO `activitylogs` (`log_id`, `user_id`, `action`, `action_date`) VALUES
(1, 23, 'Manager approveded product ID: 61. Notes: nice\r\n', '2025-04-11 10:53:22'),
(2, 23, 'Changed product ID: 61 status to approved. Notes: nice\n', '2025-04-11 10:53:22'),
(3, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-11 10:53:50'),
(4, 23, 'Manager logged in.', '2025-04-11 10:54:47'),
(5, 23, 'Updated product ID: 59 (despite error flag)', '2025-04-11 10:54:58'),
(6, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-11 10:56:42'),
(7, 23, 'Updated product ID: 57 (despite error flag)', '2025-04-11 11:00:11'),
(8, 23, 'Updated product ID: 53 (despite error flag)', '2025-04-11 14:24:37'),
(9, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-11 14:32:31'),
(10, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-11 14:32:42'),
(11, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-11 14:32:55'),
(12, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-11 14:33:17'),
(13, 23, 'Updated product ID: 53 (despite error flag)', '2025-04-11 14:54:12'),
(14, NULL, 'Unauthorized access attempt to manager product management', '2025-04-12 02:06:10'),
(15, 23, 'Manager logged in.', '2025-04-12 02:06:19'),
(16, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-12 02:06:32'),
(17, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-12 02:06:41'),
(18, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-12 02:12:37'),
(19, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-12 02:13:43'),
(20, 23, 'Updated product ID: 61 (despite error flag)', '2025-04-12 02:18:00'),
(21, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-12 02:19:54'),
(22, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-12 02:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `audittrail`
--

CREATE TABLE `audittrail` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `action_type` enum('insert','update','delete') DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmer_details`
--

CREATE TABLE `farmer_details` (
  `detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_name` varchar(255) DEFAULT NULL,
  `farm_type` varchar(100) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `crop_varieties` text DEFAULT NULL,
  `machinery_used` text DEFAULT NULL,
  `farm_size` decimal(10,2) DEFAULT NULL,
  `income` decimal(15,2) DEFAULT NULL,
  `farm_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_details`
--

INSERT INTO `farmer_details` (`detail_id`, `user_id`, `farm_name`, `farm_type`, `certifications`, `crop_varieties`, `machinery_used`, `farm_size`, `income`, `farm_location`) VALUES
(1, 22, 'Bayanihan Farms', 'Agri-Organic', 'Organic Certifications', 'Palay, Mais, Kamote, Talong,Singkamas', 'Tractor, Harrow, Rice Mill', 15.00, 36000.00, 'Palinpinon'),
(2, 19, 'Anna\'s Organic Farm Produce', 'Vegetable Farm', '', NULL, NULL, 100.00, NULL, 'Bais'),
(3, 20, '', 'Vegetable Farm', '', NULL, NULL, 0.00, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `feedback_text` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `product_id`, `feedback_text`, `rating`, `created_at`, `status`) VALUES
(1, 19, 6, 'The mangoes were incredibly sweet and juicy. Will definitely buy again!', 5, '2025-03-09 16:52:28', 'pending'),
(2, 19, 7, 'Fresh calamansi, perfect for my recipes. Very satisfied with the quality.', 4, '2025-03-10 16:52:28', 'pending'),
(3, 19, 8, 'The rice has a wonderful aroma and cooks perfectly. Excellent quality!', 5, '2025-03-11 16:52:28', 'responded'),
(4, 19, 9, 'The lemon basil wasn\'t as fresh as I expected. Slightly wilted upon arrival.', 3, '2025-03-12 16:52:28', 'responded'),
(5, 19, 10, 'These sweet potatoes are amazing! Great flavor and perfect for my stews.', 5, '2025-03-13 16:52:28', 'pending'),
(6, 20, NULL, 'The mangos from Palinpinon are good, but I\'ve had better. A bit overripe.', 3, '2025-03-14 16:52:28', 'pending'),
(7, 20, 13, 'Fresh kalamunggay, great for soup. Very green and crisp.', 4, '2025-03-15 16:52:28', 'responded'),
(8, 20, 22, 'The kangkong was very fresh and clean. Good value for money.', 5, '2025-03-16 16:52:28', 'pending'),
(9, 20, 34, 'Kamatis was a bit too ripe for my liking, but still usable.', 3, '2025-03-17 16:52:28', 'pending'),
(10, 19, NULL, 'Overall, I love shopping at the Farmers Market. Great selection of fresh produce!', 5, '2025-03-18 16:52:28', 'responded'),
(11, 20, NULL, 'The website is user-friendly, but checkout could be improved.', 4, '2025-03-19 16:52:28', 'pending'),
(12, 19, NULL, 'Delivery was faster than expected. Products were well-packaged.', 5, '2025-03-20 16:52:28', 'pending'),
(13, 20, NULL, 'Some products were missing from my order. Please improve accuracy.', 2, '2025-03-21 16:52:28', 'responded');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_responses`
--

CREATE TABLE `feedback_responses` (
  `response_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `responded_by` int(11) NOT NULL,
  `response_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_responses`
--

INSERT INTO `feedback_responses` (`response_id`, `feedback_id`, `response_text`, `responded_by`, `response_date`) VALUES
(1, 3, 'Thank you for your positive feedback! We take pride in the quality of our rice products. Please check out our other rice varieties as well!', 24, '2025-03-12 16:52:28'),
(2, 4, 'We apologize that the lemon basil didn\'t meet your expectations. We\'ve notified our farmers to improve the freshness. Please let us know if you\'d like a replacement.', 24, '2025-03-13 16:52:28'),
(3, 7, 'We appreciate your feedback on our kalamunggay. The farmer will be pleased to hear your comments!', 24, '2025-03-16 16:52:28'),
(4, 10, 'Thank you for your kind words! We\'re happy to hear you enjoy shopping with us.', 24, '2025-03-19 16:52:28'),
(5, 13, 'We sincerely apologize for the missing items. Our team has been notified, and we\'ll work to improve our order fulfillment process. Please contact customer service for a refund or replacement.', 24, '2025-03-22 16:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE `orderitems` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 6, 5, 120.00),
(2, 1, 7, 3, 40.00),
(3, 2, 8, 2, 90.00),
(4, 2, 34, 4, 21.00),
(5, 3, NULL, 3, 120.00),
(6, 4, 13, 6, 20.00),
(7, 5, 22, 5, 21.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `order_status` enum('pending','completed','canceled') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `consumer_id`, `order_status`, `order_date`, `pickup_details`) VALUES
(1, 19, 'pending', '2025-02-10 07:01:13', ''),
(2, 19, 'pending', '2025-03-19 02:00:00', 'Pickup at Valencia Market'),
(3, 19, 'pending', '2025-03-19 03:30:00', 'Pickup at Sibulan Market'),
(4, 20, 'pending', '2025-03-19 06:15:00', 'Pickup at Central Market'),
(5, 20, 'pending', '2025-03-18 01:00:00', 'Regular pickup'),
(6, 19, '', '2025-03-19 08:45:00', 'Urgent pickup needed');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`organization_id`, `name`, `description`, `address`, `contact_number`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Default Organization', 'Default organization for the system', NULL, NULL, NULL, '2025-03-23 14:30:06', '2025-03-23 14:30:06');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` enum('credit_card','paypal','bank_transfer') NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `pickup_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `pickup_status` varchar(50) DEFAULT 'pending',
  `pickup_date` datetime DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT 'Municipal Agriculture Office',
  `pickup_notes` text DEFAULT NULL,
  `office_location` varchar(255) DEFAULT 'Municipal Agriculture Office',
  `contact_person` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`pickup_id`, `order_id`, `pickup_status`, `pickup_date`, `pickup_location`, `pickup_notes`, `office_location`, `contact_person`) VALUES
(1, 1, 'pending', '2025-02-19 19:00:00', 'Municipal Agriculture Office', 'Please bring ID for verification\r\n\r\n\r\n', 'Municipal Agriculture Office', NULL),
(2, 1, 'pending', '2025-02-19 11:00:00', 'Municipal Agriculture Office', 'Please bring ID for verifications\r\n', 'Municipal Agriculture Office', ''),
(3, 1, 'pending', '2025-03-19 11:00:00', 'Municipal Agriculture Office', 'Please handle with care', 'Municipal Agriculture Office', NULL),
(4, 2, 'assigned', '2025-03-19 13:30:00', 'Municipal Agriculture Office', 'Call upon arrival', 'Municipal Agriculture Office', NULL),
(5, 3, 'pending', '2025-03-19 08:15:00', 'Municipal Agriculture Office', 'Fragile items', 'Municipal Agriculture Office', 'kevin'),
(6, 4, 'completed', '2025-03-18 02:00:00', 'Municipal Agriculture Office', 'Regular pickup completed', 'Municipal Agriculture Office', 'kevin'),
(7, 5, 'assigned', '2025-03-19 17:45:00', 'Municipal Agriculture Office', 'Priority pickup', 'Municipal Agriculture Office', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `productcategories`
--

CREATE TABLE `productcategories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcategories`
--

INSERT INTO `productcategories` (`category_id`, `category_name`) VALUES
(24, 'Aquaculture'),
(20, 'Banana Varieties'),
(13, 'Citrus'),
(11, 'Coconut Products'),
(21, 'Commercial Crops'),
(25, 'Fermented Foods'),
(29, 'Fibers and Craft Materials'),
(1, 'Fruit'),
(3, 'Grain'),
(4, 'Herb'),
(9, 'Highland Vegetables'),
(12, 'Indigenous Crops'),
(6, 'Leafy Vegetables'),
(16, 'Legumes'),
(27, 'Local Beans'),
(14, 'Local Herbs'),
(8, 'Lowland Vegetables'),
(18, 'Medicinal Plants'),
(23, 'Mushrooms'),
(17, 'Native Fruits'),
(28, 'Native Nuts'),
(19, 'Organic Produce'),
(10, 'Rice Varieties'),
(5, 'Root Crops'),
(15, 'Root Tubers'),
(22, 'Spices'),
(26, 'Tree Fruits'),
(7, 'Tropical Fruits'),
(2, 'Vegetable');

-- --------------------------------------------------------

--
-- Table structure for table `productcategorymapping`
--

CREATE TABLE `productcategorymapping` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcategorymapping`
--

INSERT INTO `productcategorymapping` (`product_id`, `category_id`) VALUES
(6, 26),
(7, 13),
(9, 13),
(22, 2),
(34, 9),
(43, 8),
(52, 2),
(57, 8),
(59, 14),
(60, 8),
(61, 22);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `unit_type` varchar(20) DEFAULT 'piece' COMMENT 'The unit of measurement (e.g., kilogram, piece, bunch)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `farmer_id`, `status`, `created_at`, `updated_at`, `image`, `stock`, `unit_type`) VALUES
(6, 'Mango', 'Sweet and juicy Carabao mangoes grown in the heart of Dumaguete.', 120.00, 22, 'pending', '2024-12-12 06:45:15', '2025-04-10 00:03:05', '', 100, 'piece'),
(7, 'Calamansi', 'Fresh green calamansi, perfect for making refreshing juices or for cooking.', 40.00, 22, 'pending', '2024-12-12 06:45:15', '2025-04-10 00:03:21', '', 1, 'piece'),
(8, 'Ayungon Rice', 'Locally grown organic rice from Ayungon, known for its soft texture and aroma.', 90.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 30, 'piece'),
(9, 'Lemon Basil', 'Fresh lemon basil harvested from local gardens, great for cooking or herbal tea.', 60.00, 20, 'pending', '2024-12-12 06:45:15', '2025-04-09 15:22:00', '', 10, 'piece'),
(10, 'Organic Sweet Potatoes', 'Sweet, organic sweet potatoes grown without pesticides, perfect for stews or baking.', 80.00, 20, 'approved', '2024-12-12 06:45:15', '2025-04-10 08:39:47', NULL, 100, 'piece'),
(13, 'kalamunggay', 'fresh kalamunggay from brangay lunga valencia\r\n', 20.00, NULL, 'approved', '2025-02-05 14:34:31', '2025-03-24 11:09:13', 'uploads/kalamunggay.png', 0, 'piece'),
(19, 'kamote', 'root crop', 30.00, 22, 'approved', '2025-02-05 15:01:51', '2025-03-24 11:09:13', 'uploads/IMG_0041.JPG', 0, 'piece'),
(22, 'kangkong', 'Unknown\r\n', 21.00, 22, 'pending', '2025-02-10 10:14:24', '2025-04-09 23:28:09', 'uploads/products/67f70289536df_kangkong.png', 10, 'piece'),
(34, 'kamatis', 'veggies', 21.00, 22, 'pending', '2025-02-10 11:21:22', '2025-04-09 23:27:52', 'uploads/products/67f7027828f32_Kamatis.jpg', 5, 'piece'),
(38, 'Sugar Cane', 'From the farms of Mabinay - Bais.', 20.00, NULL, 'approved', '2025-03-24 05:43:30', '2025-03-27 12:38:56', 'Array', 50, 'piece'),
(41, 'Kamote (Sweet Potato)', 'Freshly harvested sweet potatoes from local farms. Rich in vitamins and nutrients.', 45.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'kilogram'),
(42, 'Ube (Purple Yam)', 'Premium quality purple yam. Perfect for traditional Filipino desserts.', 70.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 50, 'kilogram'),
(43, 'Gabi (Taro)', 'Fresh taro roots. Great for stews and traditional Filipino dishes.', 60.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 22:25:26', 'pending', 75, 'kilogram'),
(44, 'Kangkong (Water Spinach)', 'Fresh water spinach harvested from clean water sources. Great for stir-fry dishes.', 25.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 80, 'bunch'),
(45, 'Malunggay (Moringa)', 'Highly nutritious moringa leaves. Known for health benefits and great for soups.', 15.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'bunch'),
(46, 'Pechay (Bok Choy)', 'Crisp and fresh bok choy. Excellent for stir-fry and soups.', 30.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 90, 'bunch'),
(47, 'Saging Saba (Cooking Banana)', 'Traditional cooking bananas. Perfect for turon and other Filipino desserts.', 50.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'bunch'),
(48, 'Santol', 'Sweet and tangy santol fruits. Great for eating fresh or making preserves.', 60.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 40, 'kilogram'),
(49, 'Lanzones', 'Sweet and fragrant lanzones from Camiguin. Limited seasonal availability.', 120.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 30, 'kilogram'),
(50, 'Talong (Eggplant)', 'Long, purple eggplants. Perfect for tortang talong and other Filipino dishes.', 40.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 70, 'kilogram'),
(51, 'Okra', 'Young and tender okra. Great for sinigang and other soups.', 35.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 65, 'kilogram'),
(52, 'Ampalaya (Bitter Gourd)', 'Fresh bitter gourd. Known for health benefits and distinctive flavor.', 45.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-10 00:02:26', '', 50, 'bunch'),
(53, 'Kalamansi', 'Small, tangy Filipino citrus fruits. Perfect for juices and flavoring dishes.', 40.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-11 14:54:12', 'uploads/products/67f92d14676cb_calamansi.png', 60, 'kilogram'),
(54, 'Dalandan', 'Sweet and tangy local oranges. Great for fresh juice.', 60.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 40, 'kilogram'),
(55, 'Dayap (Key Lime)', 'Aromatic key limes. Perfect for desserts and beverages.', 50.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 30, 'kilogram'),
(56, 'Sayote (Chayote)', 'Mild-flavored squash. Versatile for many Filipino dishes.', 35.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 80, 'kilogram'),
(57, 'Repolyo (Cabbage)', 'Fresh, crisp cabbage heads from cool mountain farms', 50.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-11 11:00:11', 'uploads/products/67f8f63b4bc81_tanglad.png', 40, 'piece'),
(58, 'Carrots', 'Sweet, crunchy carrots from highland farms. Great for soups and stews.', 45.04, 20, 'pending', '2025-04-09 15:39:55', '2025-04-11 14:33:17', 'uploads/products/67f8f56a16cdf_tanglad.png', 70, 'kilogram'),
(59, 'Tanglad (Lemongrass)', 'Aromatic lemongrass stalks. Perfect for teas and flavoring dishes.', 15.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-11 10:54:58', 'uploads/products/67f8f502b861c_tanglad.png', 150, 'bunch'),
(60, 'Luya (Ginger)', 'Fresh, aromatic ginger root. Essential for many Filipino dishes.', 60.00, 22, 'approved', '2025-04-09 15:39:55', '2025-04-10 01:47:28', 'uploads/products/67f6ff250ccd2_Ginger.png', 50, 'kilogram'),
(61, 'Dahon ng Sili (Chili Leave)', 'Flavorful chili leaves. Great for tinola and other soups.', 100.00, 19, 'approved', '2025-04-09 15:39:55', '2025-04-12 02:13:43', 'uploads/products/67f6fec7ef775_chili.png', 100, 'bunch');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'Admin'),
(2, 'Farmer'),
(4, 'Manager'),
(5, 'Organization Head'),
(1, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('wGOIVGxpKGhF65lWGGMpL62gbQLuE8tOXDBQeOj8', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiY0szR0NRS2VPbFl0YmQ1QnRsczFjUjBsWUR3Q09HcTJjM0FHWFBaTSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1741538526);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role_id`, `created_at`, `updated_at`, `first_name`, `last_name`, `contact_number`, `address`) VALUES
(17, 'michael_white', 'michaelPass456', 'michael.white@example.com', 3, '2024-12-11 15:15:06', '2025-03-27 09:17:05', 'Michael', 'White', '12312', 'Valencia\r\n'),
(19, 'anna_lee', 'annaPass321', 'anna.lee@example.com', 2, '2024-12-11 15:15:06', '2025-03-03 08:08:57', 'anna', 'lee', '03023402394', 'Dumaguete\r\n'),
(20, 'david_moore', 'davidPass654', 'david.moore@example.com', 2, '2024-12-11 15:15:06', '2025-02-19 01:32:04', 'david', 'moore', '012391203', 'Boston'),
(21, 'kevin_chris', '$2y$10$1vtHXjlZN4nMC5rFGL3pMOuSM2BhFBEAX5w6wnUn.TtcSo5RSlkjy', 'kchris.kd@gmail.com', 3, '2025-02-02 16:10:36', '2025-02-04 10:38:23', 'kevin', 'Durango', '098123447812', 'Sibulan\r\n'),
(22, 'Dayn', '$2y$10$6ElsrbEeo2eehqZWh6cAjeRxuH87cHa42nMfryRclqr5he.v0h8B2', 'dayn@gmail.com', 2, '2025-02-04 07:16:08', '2025-04-10 08:14:19', 'Dayn Cristofer', 'Durango', '09123124412', 'Sibulan\r\n'),
(23, 'angeline', '$2y$10$OFmOhK20MAY5iBnyjcKgZul90bB5cvvdJD46VYbV41lVEvjY1kTHq', 'angeline@gmail.com', 4, '2025-02-04 10:39:34', '2025-02-18 08:50:18', 'angeline', 'largo', '09123124512', 'Bolocboloc\r\n'),
(24, 'John', '$2y$10$jDhBWKeR6keXyrSR3Z6LguaAep4isunG0JqiTPiFV//BYN/O6K.KS', 'John@gmail.com', 5, '2025-03-11 07:36:36', '2025-03-11 07:36:36', 'John', 'Grime', '09987890987', 'Texas\r\n'),
(25, 'Kyle', '$2y$10$DE8HdGyIu4xMzYY3B2UET.EY0rWHZKI8hapj7.PRqhX5rFFCLxdjy', 'kyle@gmail.com', 3, '2025-03-18 03:52:45', '2025-03-18 03:52:59', 'kyle', 'wrymyr', '09312038401', 'Basay\r\n'),
(34, 'chris.doe@example.com', '$2y$10$PTOWxTJS3mBdsUWzwjPshuNwZh1nQOrRi3u1xUNLPNf7baF4JbgXe', 'chris.doe@example.com', 1, '2025-03-27 12:44:52', '2025-03-27 12:44:52', 'Chris', 'Doe', NULL, NULL),
(35, 'johndoe@example.com', '$2y$10$/YPt38XfM8xFwVuTyx4iAOeNGayBNsZKHGT8KKhgeTfAT7YsadsHe', 'johndoe@example.com', 1, '2025-03-28 12:35:07', '2025-03-28 12:35:07', 'John', 'Doe', '1234567890', '123 Main St'),
(36, 'kevin@gmail.com', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'kevin@gmail.com', 1, '2025-03-28 13:06:07', '2025-03-28 13:06:07', 'Kevin', 'Durango', '9516063243', 'Sibulan'),
(37, 'remie', '$2y$10$e/.3ZsV7yaOYxLLVuRcr3.lGzSj7GnRtIlBP3XpaZ6NE3JxLlah3m', 'remie@gmail.com', 1, '2025-04-01 16:46:47', '2025-04-01 16:46:47', 'Remie', '', '9516063243', 'Sibulan'),
(38, 'fely', '$2y$10$LYlLavAqGTVPpj8K15/52eL/uyuNKsizWkQ/QJhFcjpaey69fb.DK', 'fely@gmail.com', 1, '2025-04-07 07:48:24', '2025-04-07 07:48:24', 'Fely', '', '92629595995', 'Mabinay');

-- --------------------------------------------------------

--
-- Table structure for table `user_organizations`
--

CREATE TABLE `user_organizations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_organizations`
--

INSERT INTO `user_organizations` (`id`, `user_id`, `organization_id`, `created_at`, `updated_at`) VALUES
(1, 24, 1, '2025-03-23 14:30:06', '2025-03-23 14:30:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activitylogs`
--
ALTER TABLE `activitylogs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audittrail`
--
ALTER TABLE `audittrail`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`pickup_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `productcategories`
--
ALTER TABLE `productcategories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `productcategorymapping`
--
ALTER TABLE `productcategorymapping`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_organizations`
--
ALTER TABLE `user_organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_org` (`user_id`,`organization_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylogs`
--
ALTER TABLE `activitylogs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `audittrail`
--
ALTER TABLE `audittrail`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmer_details`
--
ALTER TABLE `farmer_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `productcategories`
--
ALTER TABLE `productcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user_organizations`
--
ALTER TABLE `user_organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activitylogs`
--
ALTER TABLE `activitylogs`
  ADD CONSTRAINT `activitylogs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `audittrail`
--
ALTER TABLE `audittrail`
  ADD CONSTRAINT `audittrail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD CONSTRAINT `farmer_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_feedback_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  ADD CONSTRAINT `feedback_responses_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_responses_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `orderitems_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `orderitems_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `productcategorymapping`
--
ALTER TABLE `productcategorymapping`
  ADD CONSTRAINT `fk_productcategorymapping_category` FOREIGN KEY (`category_id`) REFERENCES `productcategories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_productcategorymapping_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `productcategorymapping_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `productcategorymapping_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `productcategories` (`category_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
