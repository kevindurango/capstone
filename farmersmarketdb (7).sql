-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2025 at 10:26 PM
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
(1, 21, 'Admin logged in', '2025-02-27 09:37:12'),
(2, 21, 'Edited user: anna_lee', '2025-02-27 09:42:23'),
(3, 21, 'Approved product with ID: 6', '2025-02-27 09:47:02'),
(4, 21, 'Approved product with ID: 7', '2025-02-27 09:47:03'),
(5, 21, 'Approved product with ID: 19', '2025-02-27 09:50:23'),
(6, 21, 'Edited user: admin_elliot', '2025-02-27 09:55:28'),
(7, 21, 'Admin logged in', '2025-02-27 13:41:40'),
(8, 21, 'Admin logged in', '2025-03-01 09:40:05'),
(9, 21, 'Admin logged in', '2025-03-01 09:43:03'),
(10, 21, 'Admin logged in', '2025-03-01 09:43:14'),
(11, 21, 'Admin logged in', '2025-03-01 09:44:52'),
(12, 21, 'Admin logged in', '2025-03-01 09:45:03'),
(13, 21, 'Admin logged in', '2025-03-01 09:45:25'),
(14, 21, 'Admin logged in', '2025-03-01 09:50:39'),
(15, 21, 'Admin logged in', '2025-03-01 09:51:00'),
(16, 21, 'Admin logged in', '2025-03-01 09:51:13'),
(17, 21, 'Admin logged in', '2025-03-01 10:09:04'),
(18, 21, 'Admin logged out', '2025-03-01 10:15:21'),
(19, 21, 'Admin logged in', '2025-03-01 10:15:24'),
(20, 21, 'Admin logged in', '2025-03-01 17:12:23'),
(21, 21, 'Rejected product with ID: 6', '2025-03-01 17:12:39'),
(22, 21, 'Rejected product with ID: 7', '2025-03-01 17:12:40'),
(23, 21, 'Rejected product with ID: 8', '2025-03-01 17:12:40'),
(24, 21, 'Rejected product with ID: 9', '2025-03-01 17:12:41'),
(25, 21, 'Approved product with ID: 7', '2025-03-01 17:51:12'),
(26, 21, 'Admin logged in', '2025-03-01 17:57:07'),
(27, 21, 'Admin logged in', '2025-03-01 17:57:25'),
(28, 21, 'Admin logged in', '2025-03-02 22:20:29'),
(29, 21, 'Admin logged in', '2025-03-02 22:26:32'),
(30, 21, 'Admin logged in', '2025-03-03 02:53:23'),
(31, 21, 'Admin logged in', '2025-03-03 02:59:09'),
(32, 21, 'Admin logged in', '2025-03-03 03:41:08'),
(33, 21, 'Admin logged in', '2025-03-03 04:30:36'),
(34, 21, 'Admin logged in', '2025-03-03 07:29:44'),
(35, 23, 'Manager logged in.', '2025-03-03 08:05:55'),
(36, 21, 'Admin logged in', '2025-03-03 08:06:01'),
(37, 23, 'Manager logged in.', '2025-03-03 08:06:12'),
(38, 23, 'Updated user: anna_lee', '2025-03-03 08:08:57'),
(39, 21, 'Admin logged in', '2025-03-04 05:57:11'),
(40, 21, 'Admin logged in', '2025-03-04 05:58:55'),
(41, 21, 'Unauthorized access attempt.', '2025-03-04 06:21:22'),
(42, 23, 'Manager logged in.', '2025-03-04 06:26:29'),
(43, 21, 'Admin logged in', '2025-03-04 06:29:20'),
(44, 23, 'Manager logged in.', '2025-03-04 06:36:13'),
(45, 21, 'Admin logged in', '2025-03-04 15:44:01'),
(46, 23, 'Manager logged in.', '2025-03-04 16:13:30'),
(47, 21, 'Admin logged in', '2025-03-09 11:19:53'),
(48, 21, 'Added new user: John', '2025-03-11 07:36:36'),
(49, 24, 'Organization Head logged in.', '2025-03-11 07:39:13'),
(50, 24, 'Organization Head logged in.', '2025-03-11 07:41:01'),
(51, 24, 'Organization Head logged in.', '2025-03-11 07:41:13'),
(52, 24, 'Organization Head logged in.', '2025-03-11 07:43:39'),
(53, 24, 'Organization Head logged in.', '2025-03-11 07:43:39'),
(54, 24, 'Organization Head logged in.', '2025-03-11 07:49:12'),
(55, 24, 'Organization Head logged in.', '2025-03-11 07:49:13'),
(56, 24, 'Organization Head logged in.', '2025-03-11 07:49:13'),
(57, 24, 'Organization Head logged in.', '2025-03-11 07:49:13'),
(58, 24, 'Organization Head logged in.', '2025-03-11 07:49:13'),
(59, 24, 'Organization Head logged in.', '2025-03-11 07:50:14'),
(60, 24, 'Organization Head logged in.', '2025-03-11 07:53:09'),
(61, 24, 'Organization Head logged in.', '2025-03-11 07:53:10'),
(62, 24, 'Organization Head logged in.', '2025-03-11 07:55:19'),
(63, 24, 'Organization Head logged in.', '2025-03-11 07:55:19'),
(64, 24, 'Organization Head logged in.', '2025-03-11 07:55:19'),
(65, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(66, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(67, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(68, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(69, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(70, 24, 'Organization Head logged in.', '2025-03-11 07:55:20'),
(71, 24, 'Organization Head logged in.', '2025-03-11 07:55:21'),
(72, 24, 'Organization Head logged in.', '2025-03-11 07:55:22'),
(73, 21, 'Admin logged in', '2025-03-11 08:23:38'),
(74, 24, 'Organization Head logged in.', '2025-03-11 08:24:41'),
(75, 24, 'Organization Head logged in.', '2025-03-11 08:29:56'),
(76, 24, 'Organization Head logged in.', '2025-03-11 08:31:23'),
(77, 21, 'Admin logged in', '2025-03-11 23:06:01'),
(78, 24, 'Organization Head logged in.', '2025-03-12 12:30:29'),
(79, 24, 'Organization Head logged in.', '2025-03-12 12:31:58'),
(80, 24, 'Organization Head logged in.', '2025-03-12 12:34:18'),
(81, 24, 'Organization Head logged in.', '2025-03-12 12:34:44'),
(82, 24, 'Organization Head logged in.', '2025-03-12 12:37:03'),
(83, 24, 'Organization Head logged in.', '2025-03-12 12:37:04'),
(84, 24, 'Organization Head logged in.', '2025-03-12 12:37:04'),
(85, 24, 'Organization Head logged in.', '2025-03-12 12:39:39'),
(86, 24, 'Organization Head logged in.', '2025-03-12 12:43:00'),
(87, 21, 'Admin logged in', '2025-03-14 05:54:25'),
(88, 21, 'Admin logged in', '2025-03-15 13:33:36'),
(89, 24, 'Organization Head logged in.', '2025-03-15 13:45:19'),
(90, 24, 'Organization Head logged in.', '2025-03-15 13:45:25'),
(91, 24, 'Organization Head logged in.', '2025-03-15 13:45:29'),
(92, 24, 'Organization Head logged in.', '2025-03-15 13:45:54'),
(93, 24, 'Organization Head logged in.', '2025-03-15 14:56:13'),
(94, 24, 'Organization Head logged in.', '2025-03-15 14:56:46'),
(95, 24, 'Organization Head logged in.', '2025-03-15 14:58:00'),
(96, 21, 'Admin logged in', '2025-03-15 14:58:23'),
(97, 24, 'Organization Head logged in.', '2025-03-15 14:58:47'),
(98, 24, 'Organization Head logged in.', '2025-03-15 14:58:51'),
(99, 24, 'Organization Head logged in.', '2025-03-15 15:10:11'),
(100, 21, 'Admin logged in', '2025-03-17 08:34:20'),
(101, 24, 'Organization Head logged in.', '2025-03-17 08:34:38'),
(102, 24, 'Organization Head logged in.', '2025-03-17 08:36:23'),
(103, 24, 'Organization Head logged in.', '2025-03-17 08:36:23'),
(104, 24, 'Organization Head logged in.', '2025-03-17 08:36:25'),
(105, 21, 'Admin logged in', '2025-03-17 09:25:49'),
(106, 21, 'Admin logged in', '2025-03-17 14:33:40'),
(107, 21, 'Admin logged in', '2025-03-17 14:35:28'),
(108, 21, 'Admin logged in', '2025-03-17 14:53:29'),
(109, 21, 'Admin logged in', '2025-03-17 14:53:37'),
(110, 23, 'Manager logged in.', '2025-03-17 15:27:21'),
(111, 23, 'Approved product with ID: 6', '2025-03-17 15:47:35'),
(112, 23, 'Rejected product with ID: 6', '2025-03-17 15:47:37'),
(113, 21, 'Admin logged in', '2025-03-17 15:47:56'),
(114, 21, 'Admin logged out', '2025-03-17 19:31:10'),
(115, 21, 'Admin logged in', '2025-03-17 19:31:15'),
(116, 21, 'Admin logged out', '2025-03-17 19:34:07'),
(117, 21, 'Admin logged in', '2025-03-17 19:34:11'),
(118, 21, 'Edited user: sarah_brown', '2025-03-18 03:52:18'),
(119, 21, 'Added new user: Kyle', '2025-03-18 03:52:45'),
(120, 21, 'Edited user: Kyle', '2025-03-18 03:52:59'),
(121, 23, 'Manager logged in.', '2025-03-18 04:16:04'),
(122, 21, 'Admin logged in', '2025-03-18 04:18:57'),
(123, 23, 'Manager logged in.', '2025-03-18 04:20:42'),
(124, 23, 'Approved product with ID: 34', '2025-03-18 06:03:37'),
(125, 23, 'Unauthorized access attempt to admin product management', '2025-03-18 06:42:53'),
(126, 21, 'Admin logged in', '2025-03-18 06:42:57'),
(127, 21, 'Admin approved product ID: 22. Notes: Approved by admin', '2025-03-18 06:44:03'),
(128, 23, 'Manager logged in.', '2025-03-18 08:54:33'),
(129, 23, 'Unauthorized access attempt to admin product management', '2025-03-18 08:54:55'),
(130, 21, 'Admin logged in', '2025-03-18 08:54:59'),
(131, 21, 'Admin performed batch reject on 10 products', '2025-03-18 09:12:04'),
(132, 21, 'Admin performed batch approve on 10 products', '2025-03-18 09:12:09'),
(133, 21, 'Admin performed batch approve on 10 products', '2025-03-18 09:12:13'),
(134, 21, 'Rejected product with ID: 19', '2025-03-18 09:12:24'),
(135, 23, 'Manager logged in.', '2025-03-18 10:14:11'),
(136, 23, 'Unauthorized access attempt to admin product management', '2025-03-18 10:16:36'),
(137, 21, 'Admin logged in', '2025-03-18 10:16:40'),
(138, 23, 'Manager logged in.', '2025-03-18 14:49:46'),
(139, 21, 'Admin logged in', '2025-03-18 14:57:02'),
(140, 23, 'Manager logged in.', '2025-03-18 15:00:34'),
(141, 23, 'Unauthorized access attempt to admin product management', '2025-03-18 15:05:33'),
(142, 21, 'Admin logged in', '2025-03-18 15:05:37'),
(143, 23, 'Manager logged in.', '2025-03-18 15:05:52'),
(144, 23, 'Manager logged in.', '2025-03-18 16:50:10'),
(145, 23, 'Updated user: Dayn', '2025-03-18 17:33:52'),
(146, 23, 'Updated user: Dayn', '2025-03-18 17:33:56'),
(147, 23, 'Updated order #1 status to completed', '2025-03-18 20:39:36');

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
(1, 22, 'Bayanihan Farms', 'Agri-Organic', 'Organic Certifications', 'Palay, Mais, Kamote, Talong,Singkamas', 'Tractor, Harrow, Rice Mill', 15.00, 36000.00, 'Palinpinon');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 19, 'pending', '2025-02-10 07:01:13', '');

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
  `pickup_status` enum('pending','assigned','in_transit','completed','cancelled') DEFAULT 'pending',
  `pickup_date` datetime DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `pickup_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`pickup_id`, `order_id`, `pickup_status`, `pickup_date`, `pickup_location`, `assigned_to`, `pickup_notes`) VALUES
(1, 1, '', '2025-02-22 03:00:00', 'Valencia ', NULL, 'Please bring ID for verification\r\n\r\n\r\n');

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
(1, 'Fruit'),
(3, 'Grain'),
(4, 'Herb'),
(2, 'Vegetable');

-- --------------------------------------------------------

--
-- Table structure for table `productcategorymapping`
--

CREATE TABLE `productcategorymapping` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `farmer_id`, `status`, `created_at`, `updated_at`, `image`, `stock`) VALUES
(6, 'Mango', 'Sweet and juicy Carabao mangoes grown in the heart of Dumaguete.', 120.00, 17, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 0),
(7, 'Calamansi', 'Fresh green calamansi, perfect for making refreshing juices or for cooking.', 40.00, 17, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 0),
(8, 'Ayungon Rice', 'Locally grown organic rice from Ayungon, known for its soft texture and aroma.', 90.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 30),
(9, 'Lemon Basil', 'Fresh lemon basil harvested from local gardens, great for cooking or herbal tea.', 60.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', NULL, 0),
(10, 'Organic Sweet Potatoes', 'Sweet, organic sweet potatoes grown without pesticides, perfect for stews or baking.', 80.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', NULL, 0),
(11, 'mango', 'Mangos from Palinpinon\r\n\r\n', 120.00, 22, 'approved', '2024-12-16 09:41:12', '2025-03-18 09:12:09', 'uploads/2', 20),
(13, 'kalamunggay', 'balo baya', 20.00, 22, 'approved', '2025-02-05 14:34:31', '2025-03-18 09:12:09', 'uploads/kalamunggay.png', 0),
(19, 'kamote', 'root crop', 30.00, 22, 'rejected', '2025-02-05 15:01:51', '2025-03-18 09:12:24', 'uploads/IMG_0041.JPG', 0),
(22, 'kangkong', 'ala wa balo', 21.00, 22, 'approved', '2025-02-10 10:14:24', '2025-03-18 09:12:09', 'uploads/kangkong.png', 0),
(34, 'kamatis', 'veggies', 21.00, 22, 'approved', '2025-02-10 11:21:22', '2025-03-18 17:45:05', 'uploads/Kamatis.jpg', 5);

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
(6, 'Driver'),
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
-- Table structure for table `shippinginfo`
--

CREATE TABLE `shippinginfo` (
  `shipping_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `shipping_status` enum('pending','shipped','delivered') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shippinginfo`
--

INSERT INTO `shippinginfo` (`shipping_id`, `order_id`, `address`, `city`, `state`, `zip_code`, `country`, `shipping_status`) VALUES
(3, 1, '123 Main St', 'Valencia', 'Negros Oriental', '6210', 'Philippines', 'pending');

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
(16, 'sarah_brown', 'sarahPass123', 'sarah.brown@example.com', 3, '2024-12-11 15:15:06', '2025-03-18 03:52:18', 'sarah', 'brownie', '123123', 'Sibulan'),
(17, 'michael_white', 'michaelPass456', 'michael.white@example.com', 3, '2024-12-11 15:15:06', '2025-02-04 10:35:58', 'Michael', 'White', '123123', 'Valencia\r\n'),
(18, 'admin_elliot', 'adminPass789', 'elliot.admin@example.com', 3, '2024-12-11 15:15:06', '2025-02-27 09:55:28', 'elliot', 'elliot', '03842398432', 'sibulan'),
(19, 'anna_lee', 'annaPass321', 'anna.lee@example.com', 2, '2024-12-11 15:15:06', '2025-03-03 08:08:57', 'anna', 'lee', '03023402394', 'Dumaguete\r\n'),
(20, 'david_moore', 'davidPass654', 'david.moore@example.com', 2, '2024-12-11 15:15:06', '2025-02-19 01:32:04', 'david', 'moore', '012391203', 'Boston'),
(21, 'kevin_chris', '$2y$10$1vtHXjlZN4nMC5rFGL3pMOuSM2BhFBEAX5w6wnUn.TtcSo5RSlkjy', 'kchris.kd@gmail.com', 3, '2025-02-02 16:10:36', '2025-02-04 10:38:23', 'kevin', 'Durango', '098123447812', 'Sibulan\r\n'),
(22, 'Dayn', '$2y$10$6ElsrbEeo2eehqZWh6cAjeRxuH87cHa42nMfryRclqr5he.v0h8B2', 'dayn@gmail.com', 2, '2025-02-04 07:16:08', '2025-03-18 17:33:56', 'Dayn', 'Durango', '09123124412', 'Sibulan\r\n'),
(23, 'angeline', '$2y$10$OFmOhK20MAY5iBnyjcKgZul90bB5cvvdJD46VYbV41lVEvjY1kTHq', 'angeline@gmail.com', 4, '2025-02-04 10:39:34', '2025-02-18 08:50:18', 'angeline', 'largo', '09123124512', 'Bolocboloc\r\n'),
(24, 'John', '$2y$10$jDhBWKeR6keXyrSR3Z6LguaAep4isunG0JqiTPiFV//BYN/O6K.KS', 'John@gmail.com', 5, '2025-03-11 07:36:36', '2025-03-11 07:36:36', 'John', 'Grime', '09987890987', 'Texas\r\n'),
(25, 'Kyle', '$2y$10$DE8HdGyIu4xMzYY3B2UET.EY0rWHZKI8hapj7.PRqhX5rFFCLxdjy', 'kyle@gmail.com', 3, '2025-03-18 03:52:45', '2025-03-18 03:52:59', 'kyle', 'wrymyr', '09312038401', 'Basay\r\n');

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
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_pickup_driver` (`assigned_to`);

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
-- Indexes for table `shippinginfo`
--
ALTER TABLE `shippinginfo`
  ADD PRIMARY KEY (`shipping_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylogs`
--
ALTER TABLE `activitylogs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `audittrail`
--
ALTER TABLE `audittrail`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmer_details`
--
ALTER TABLE `farmer_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `productcategories`
--
ALTER TABLE `productcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shippinginfo`
--
ALTER TABLE `shippinginfo`
  MODIFY `shipping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

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
  ADD CONSTRAINT `orderitems_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

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
  ADD CONSTRAINT `fk_pickup_driver` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
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
-- Constraints for table `shippinginfo`
--
ALTER TABLE `shippinginfo`
  ADD CONSTRAINT `shippinginfo_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
