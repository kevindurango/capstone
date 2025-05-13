-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2025 at 06:46 PM
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
(22, 23, 'Updated product ID: 58 (despite error flag)', '2025-04-12 02:20:40'),
(23, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:14:25'),
(24, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:17:05'),
(25, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:19:01'),
(26, 23, 'Failed to update product ID: 56 - No database changes', '2025-04-12 04:19:11'),
(27, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:21:27'),
(28, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:25:27'),
(29, 23, 'Failed to update product ID: 61 - No database changes', '2025-04-12 04:31:52'),
(30, NULL, 'Unauthorized access attempt to manager product management', '2025-04-12 05:22:23'),
(31, 23, 'Manager logged in.', '2025-04-12 05:22:27'),
(32, 23, 'Updated user: Dayn Cristofer', '2025-04-12 06:41:32'),
(33, 23, 'Updated user: david_moorer', '2025-04-12 06:41:39'),
(34, 23, 'Updated user: david_moorer', '2025-04-12 06:41:43'),
(35, 23, 'Manager logged out', '2025-04-12 07:05:03'),
(36, 23, 'Manager logged in.', '2025-04-12 07:05:06'),
(37, 23, 'Updated stock for product ID: 13 to 3', '2025-04-12 07:05:11'),
(38, 23, 'Manager logged in.', '2025-04-12 08:41:03'),
(39, 23, 'Manager logged in.', '2025-04-12 08:47:28'),
(40, 23, 'Manager pendinged product ID: 60. Notes: need time to approve\r\n', '2025-04-12 15:23:15'),
(41, 23, 'Changed product ID: 60 status to pending. Notes: need time to approve\r\n', '2025-04-12 15:23:15'),
(42, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:23:27'),
(43, 23, 'Changed product ID: 61 status to rejected. Notes: ', '2025-04-12 15:23:27'),
(44, 23, 'Updated stock for product ID: 61 to 200', '2025-04-12 15:23:40'),
(45, 23, 'Updated stock for product ID: 61 to 200', '2025-04-12 15:23:45'),
(46, 23, 'Manager approveded product ID: 61', '2025-04-12 15:31:42'),
(47, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:31:42'),
(48, 23, 'Manager approveded product ID: 61', '2025-04-12 15:31:45'),
(49, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:31:45'),
(50, 23, 'Manager approveded product ID: 61', '2025-04-12 15:33:14'),
(51, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:33:14'),
(52, 23, 'Manager approveded product ID: 61', '2025-04-12 15:42:56'),
(53, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:42:56'),
(54, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:43:04'),
(55, 23, 'Changed product ID: 61 status to rejected. Notes: ', '2025-04-12 15:43:04'),
(56, 23, 'Manager approveded product ID: 61', '2025-04-12 15:43:13'),
(57, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:43:13'),
(58, 23, 'Manager approveded product ID: 61', '2025-04-12 15:43:18'),
(59, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:43:18'),
(60, 23, 'Manager approveded product ID: 61', '2025-04-12 15:43:25'),
(61, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:43:25'),
(62, 23, 'Manager approveded product ID: 61', '2025-04-12 15:43:38'),
(63, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:43:38'),
(64, 23, 'Manager approveded product ID: 61', '2025-04-12 15:43:52'),
(65, 23, 'Changed product ID: 61 status to approved. Notes: ', '2025-04-12 15:43:52'),
(66, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:43:57'),
(67, 23, 'Changed product ID: 61 status to rejected. Notes: ', '2025-04-12 15:43:57'),
(68, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:44:01'),
(69, 23, 'Changed product ID: 61 status to rejected. Notes: ', '2025-04-12 15:44:01'),
(70, 23, 'Manager approveded product ID: 61', '2025-04-12 15:44:59'),
(71, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:44:59'),
(72, 23, 'Manager approveded product ID: 61', '2025-04-12 15:45:00'),
(73, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:45:00'),
(74, 23, 'Manager approveded product ID: 61', '2025-04-12 15:45:01'),
(75, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:45:01'),
(76, 23, 'Manager approveded product ID: 61', '2025-04-12 15:45:01'),
(77, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:45:01'),
(78, 23, 'Manager approveded product ID: 61', '2025-04-12 15:45:02'),
(79, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:45:02'),
(80, 23, 'Manager approveded product ID: 61', '2025-04-12 15:46:09'),
(81, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:46:09'),
(82, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:46:45'),
(83, 23, 'Manager updated product ID: 61 status to rejected. Notes: ', '2025-04-12 15:46:45'),
(84, 23, 'Manager approveded product ID: 61', '2025-04-12 15:46:54'),
(85, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:46:54'),
(86, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:47:03'),
(87, 23, 'Manager updated product ID: 61 status to rejected. Notes: ', '2025-04-12 15:47:03'),
(88, 23, 'Manager approveded product ID: 61', '2025-04-12 15:53:49'),
(89, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 15:53:49'),
(90, 23, 'Manager rejecteded product ID: 61', '2025-04-12 15:53:54'),
(91, 23, 'Manager updated product ID: 61 status to rejected. Notes: ', '2025-04-12 15:53:54'),
(92, 23, 'Updated stock for product ID: 61 to 205', '2025-04-12 15:54:07'),
(93, 23, 'Manager approveded product ID: 61', '2025-04-12 16:09:05'),
(94, 23, 'Manager updated product ID: 61 status to approved. Notes: ', '2025-04-12 16:09:05'),
(95, 23, 'Manager approveded product ID: 61. Notes: good ', '2025-04-12 16:10:12'),
(96, 23, 'Manager updated product ID: 61 status to approved. Notes: good ', '2025-04-12 16:10:12'),
(97, 23, 'Updated product ID: 61 details - Name: Dahon ng Sili (Chili Leave), Price: 100, Stock: 205, Unit: kilogram', '2025-04-12 16:13:28'),
(98, 23, 'Updated product ID: 61 details - Name: Dahon ng Sili (Chili Leave), Price: 100, Stock: 205, Unit: liter', '2025-04-12 16:13:35'),
(99, 23, 'Updated product ID: 61 details - Name: Dahon ng Sili (Chili Leave), Price: 100, Stock: 205, Unit: piece', '2025-04-12 16:13:42'),
(100, 23, 'Updated product ID: 13 details - Name: kalamunggay, Price: 20, Stock: 3, Unit: piece', '2025-04-12 23:36:00'),
(101, 23, 'Updated product ID: 13 details - Name: kalamunggay, Price: 20, Stock: 3, Unit: piece', '2025-04-12 23:36:20'),
(102, 23, 'Updated product ID: 19 details - Name: kamote, Price: 30, Stock: 0, Unit: piece', '2025-04-13 00:27:17'),
(103, 23, 'Manager logged in.', '2025-04-14 05:07:11'),
(104, 23, 'Updated stock for product ID: 19 to 0', '2025-04-14 06:47:56'),
(105, 23, 'Manager approveded product ID: 60', '2025-04-14 07:05:22'),
(106, 23, 'Updated product #60 status to approved', '2025-04-14 07:05:22'),
(107, 23, 'Manager approveded product ID: 60', '2025-04-14 07:05:26'),
(108, 23, 'Updated product #60 status to approved', '2025-04-14 07:05:26'),
(109, 23, 'Manager approveded product ID: 60', '2025-04-14 07:05:29'),
(110, 23, 'Updated product #60 status to approved', '2025-04-14 07:05:29'),
(111, 23, 'Manager approveded product ID: 60', '2025-04-14 07:08:22'),
(112, 23, 'Updated product #60 status to approved', '2025-04-14 07:08:22'),
(113, 23, 'Manager approveded product ID: 59', '2025-04-14 07:08:32'),
(114, 23, 'Updated product #59 status to approved', '2025-04-14 07:08:32'),
(115, 23, 'Manager approveded product ID: 59', '2025-04-14 07:08:36'),
(116, 23, 'Updated product #59 status to approved', '2025-04-14 07:08:36'),
(117, 23, 'Manager approveded product ID: 59', '2025-04-14 07:08:39'),
(118, 23, 'Updated product #59 status to approved', '2025-04-14 07:08:39'),
(119, 23, 'Manager approveded product ID: 59', '2025-04-14 07:08:53'),
(120, 23, 'Updated product #59 status to approved', '2025-04-14 07:08:53'),
(121, 23, 'Manager logged out', '2025-04-14 09:20:49'),
(122, 21, 'Admin logged in', '2025-04-14 09:21:07'),
(124, 21, 'Admin logged in', '2025-04-16 00:17:04'),
(125, 21, 'Rejected product with ID: 59', '2025-04-16 03:15:19'),
(126, 21, 'Approved product with ID: 59', '2025-04-16 03:15:21'),
(128, 21, 'Admin logged in', '2025-04-16 03:16:17'),
(129, 23, 'Failed login attempt.', '2025-04-16 04:24:35'),
(130, 23, 'Manager logged in.', '2025-04-16 04:24:43'),
(131, 23, 'Unauthorized access attempt to product management', '2025-04-16 04:35:15'),
(132, 21, 'Admin logged in', '2025-04-16 04:35:19'),
(133, 21, 'Rejected product with ID: 61', '2025-04-16 04:35:26'),
(134, 21, 'Approved product with ID: 61', '2025-04-16 04:36:02'),
(135, 21, 'Rejected product with ID: 61', '2025-04-16 04:36:05'),
(136, 21, 'Admin logged in', '2025-04-16 10:53:41'),
(138, 21, 'Updated full details for product #61', '2025-04-16 11:15:37'),
(139, 21, 'Updated full details for product #61', '2025-04-16 11:15:45'),
(140, 21, 'Updated full details for product #58', '2025-04-16 11:19:05'),
(141, 21, 'Updated full details for product #58', '2025-04-16 11:19:08'),
(142, 21, 'Updated full details for product #58', '2025-04-16 11:19:20'),
(143, 21, 'Updated full details for product #58', '2025-04-16 11:19:28'),
(144, 21, 'Updated full details for product #45', '2025-04-16 11:19:56'),
(145, 21, 'Updated full details for product #45', '2025-04-16 11:20:22'),
(146, 21, 'Approved product with ID: 61', '2025-04-16 11:22:40'),
(147, 21, 'Approved product with ID: 48', '2025-04-16 11:22:44'),
(148, 21, 'Rejected product with ID: 57', '2025-04-16 11:22:48'),
(149, 21, 'Updated full details for product #61', '2025-04-16 11:23:15'),
(150, 21, 'Rejected product with ID: 61', '2025-04-16 11:25:03'),
(151, 21, 'Rejected product with ID: 60', '2025-04-16 11:25:05'),
(152, 21, 'Approved product with ID: 58', '2025-04-16 11:25:07'),
(153, 21, 'Approved product with ID: 57', '2025-04-16 11:25:11'),
(154, 21, 'Approved product with ID: 60', '2025-04-16 11:25:13'),
(155, 21, 'Approved product with ID: 61', '2025-04-16 11:25:15'),
(156, 21, 'Updated full details for product #48', '2025-04-16 11:38:51'),
(157, 21, 'Admin rejecteded product ID: 61. Notes: did not pass standards', '2025-04-16 11:50:30'),
(158, 21, 'Rejected product with ID: 61', '2025-04-16 11:50:30'),
(159, 21, 'Admin rejecteded product ID: 61. Notes: did not pass standards', '2025-04-16 11:52:25'),
(160, 21, 'Rejected product with ID: 61', '2025-04-16 11:52:25'),
(161, 21, 'Updated full details for product #61', '2025-04-16 11:52:57'),
(162, 21, 'Updated full details for product #10', '2025-04-16 13:36:10'),
(163, 21, 'Updated full details for product #10', '2025-04-16 13:36:27'),
(164, 21, 'Admin approveded product ID: 61', '2025-04-16 13:36:47'),
(165, 21, 'Approved product with ID: 61', '2025-04-16 13:36:47'),
(166, 21, 'Admin approveded product ID: 54', '2025-04-16 13:55:02'),
(167, 21, 'Approved product with ID: 54', '2025-04-16 13:55:02'),
(168, 21, 'Admin approveded product ID: 55', '2025-04-16 13:55:09'),
(169, 21, 'Approved product with ID: 55', '2025-04-16 13:55:09'),
(170, 21, 'Updated full details for product #57', '2025-04-17 04:44:27'),
(171, 21, 'Updated full details for product #61', '2025-04-17 04:44:43'),
(172, 21, 'Updated full details for product #61', '2025-04-17 04:44:49'),
(173, 21, 'Updated full details for product #61', '2025-04-17 04:44:56'),
(174, 21, 'Added new user: Alina', '2025-04-17 04:47:48'),
(175, 21, 'Admin logged out', '2025-04-17 04:47:52'),
(176, 39, 'Admin logged in', '2025-04-17 04:47:55'),
(177, 21, 'Unauthorized access attempt.', '2025-04-17 04:48:24'),
(178, 23, 'Manager logged in.', '2025-04-17 04:48:28'),
(179, NULL, 'Failed login attempt for Organization Head account: kevinchris@gmail.com', '2025-04-17 04:53:10'),
(180, 24, 'Organization Head logged in successfully', '2025-04-17 04:53:13'),
(181, 24, 'Organization Head logged in.', '2025-04-17 04:53:13'),
(182, 24, 'Organization Head logged in.', '2025-04-17 07:49:26'),
(183, 24, 'Organization Head logged in.', '2025-04-17 07:49:31'),
(184, 24, 'Organization Head logged in.', '2025-04-17 07:55:02'),
(185, 24, 'Organization Head logged in.', '2025-04-17 08:01:19'),
(186, 24, 'Organization Head logged in.', '2025-04-17 08:07:03'),
(187, 24, 'Organization Head logged in.', '2025-04-17 08:09:33'),
(188, 24, 'Organization Head logged in.', '2025-04-17 08:09:47'),
(189, 24, 'Organization Head logged in.', '2025-04-17 08:09:51'),
(190, 24, 'Organization Head logged in.', '2025-04-17 08:09:52'),
(191, 24, 'Organization Head logged in.', '2025-04-17 08:10:07'),
(192, 24, 'Organization Head logged in.', '2025-04-17 08:10:25'),
(193, 24, 'Organization Head logged in.', '2025-04-17 08:10:56'),
(194, 24, 'Viewed details for Order #6', '2025-04-17 08:18:55'),
(195, 24, 'Viewed details for Order #3', '2025-04-17 08:19:00'),
(196, 24, 'Viewed details for Order #5', '2025-04-17 08:19:03'),
(197, 24, 'Organization Head logged in.', '2025-04-17 08:34:42'),
(198, 24, 'Viewed details for Order #6', '2025-04-17 08:34:44'),
(199, 24, 'Viewed details for Order #4', '2025-04-17 08:34:46'),
(200, 24, 'Organization Head logged in.', '2025-04-17 08:56:50'),
(201, 24, 'Organization Head logged in.', '2025-04-17 08:56:59'),
(202, 24, 'Organization Head logged in.', '2025-04-17 08:57:00'),
(203, 24, 'Organization Head logged in.', '2025-04-17 08:58:54'),
(204, 24, 'Organization Head logged in.', '2025-04-17 08:59:00'),
(205, 24, 'Organization Head logged in.', '2025-04-17 08:59:05'),
(206, 24, 'Organization Head logged in.', '2025-04-17 08:59:31'),
(207, 24, 'Organization Head logged in.', '2025-04-17 08:59:32'),
(208, 24, 'Viewed details for Order #6', '2025-04-17 08:59:35'),
(209, 24, 'Organization Head logged in.', '2025-04-17 09:05:02'),
(210, 24, 'Organization Head logged in.', '2025-04-17 09:05:10'),
(211, 24, 'Organization Head logged in.', '2025-04-17 09:05:26'),
(212, 24, 'Viewed details for Order #6', '2025-04-17 09:05:30'),
(213, 24, 'Updated farmer details for user ID: 20', '2025-04-17 09:33:18'),
(214, 24, 'Organization Head logged in.', '2025-04-17 09:33:35'),
(215, 24, 'Organization Head logged out.', '2025-04-17 09:33:48'),
(216, 23, 'Manager logged in.', '2025-04-21 09:00:40'),
(217, 23, 'Manager approveded product ID: 56', '2025-04-21 09:00:54'),
(218, 23, 'Updated product #56 status to approved', '2025-04-21 09:00:54'),
(219, 38, 'User logged in.', '2025-04-22 10:19:03'),
(220, 23, 'Manager logged in.', '2025-04-22 12:56:26'),
(221, 23, 'Manager logged out', '2025-04-23 02:16:39'),
(222, 21, 'Admin logged in', '2025-04-23 02:16:51'),
(223, 21, 'Admin approveded product ID: 9', '2025-04-23 02:22:29'),
(224, 21, 'Approved product with ID: 9', '2025-04-23 02:22:29'),
(225, 21, 'Admin approveded product ID: 45', '2025-04-23 02:22:34'),
(226, 21, 'Approved product with ID: 45', '2025-04-23 02:22:34'),
(227, 38, 'Payment processed for order #38 using credit_card. Status: completed', '2025-04-24 07:29:02'),
(228, 38, 'Payment processed for order #42 using cash_on_pickup. Status: pending', '2025-04-24 12:45:42'),
(229, 38, 'Payment processed for order #43 using cash_on_pickup. Status: pending', '2025-04-24 12:58:31'),
(230, 38, 'Payment processed for order #43 using cash_on_pickup. Status: pending', '2025-04-24 13:01:05'),
(231, 38, 'Payment processed for order #44 using cash_on_pickup. Status: pending', '2025-04-24 13:01:17'),
(232, 21, 'Admin logged in', '2025-04-24 13:05:29'),
(233, 21, 'Updated full details for product #61', '2025-04-24 13:05:46'),
(234, 21, 'Updated full details for product #8', '2025-04-24 13:06:48'),
(235, 38, 'Payment processed for order #45 using cash_on_pickup. Status: pending', '2025-04-24 13:07:13'),
(236, 38, 'Payment processed for order #46 using cash_on_pickup. Status: pending', '2025-04-24 13:11:15'),
(237, 21, 'Updated full details for product #8', '2025-04-24 13:31:18'),
(238, 21, 'Admin deleted product ID: 13', '2025-04-24 23:57:19'),
(239, 21, 'Updated full details for product #57', '2025-04-25 01:26:58'),
(240, 21, 'Updated full details for product #56', '2025-04-25 01:29:33'),
(241, 21, 'Updated full details for product #55', '2025-04-25 01:32:14'),
(242, 38, 'User logged in.', '2025-04-25 02:45:15'),
(243, 38, 'User logged in.', '2025-04-25 02:45:33'),
(244, 40, 'User registered: largoangelinegrime', '2025-04-25 08:25:55'),
(245, 40, 'User logged in.', '2025-04-25 08:26:08'),
(246, 40, 'User logged in.', '2025-04-25 08:26:19'),
(247, 21, 'Updated full details for product #48', '2025-04-25 09:58:36'),
(248, 21, 'Updated full details for product #45', '2025-04-25 10:07:00'),
(249, 21, 'Updated full details for product #57', '2025-04-25 10:07:39'),
(250, 38, 'Test payment processed for order #60 using cash_on_pickup. Status: pending', '2025-04-26 01:41:35'),
(251, 38, 'Test payment processed for order #60 using cash_on_pickup. Status: pending', '2025-04-26 01:43:49'),
(252, 38, 'Test payment processed for order #60 using cash_on_pickup. Status: pending', '2025-04-26 01:44:26'),
(253, 38, 'Test payment processed for order #60 using cash_on_pickup. Status: pending', '2025-04-26 01:44:33'),
(254, 38, 'Payment processed for order #63 using cash_on_pickup. Status: pending', '2025-04-26 04:31:26'),
(255, 38, 'Payment processed for order #70 using cash_on_pickup. Status: pending', '2025-04-26 04:58:31'),
(256, 38, 'Payment processed for order #75 using cash_on_pickup. Status: pending', '2025-04-26 05:16:22'),
(257, 38, 'Payment processed for order #85 using cash_on_pickup. Status: pending', '2025-04-26 11:18:10'),
(258, 38, 'Payment processed for order #86 using cash_on_pickup. Status: pending', '2025-04-26 11:19:57'),
(259, 38, 'Payment processed for order #87 using cash_on_pickup. Status: pending', '2025-04-26 11:46:38'),
(260, 40, 'User logged in.', '2025-04-26 11:48:11'),
(261, 40, 'User logged in.', '2025-04-26 11:48:19'),
(262, 38, 'Payment processed for order #88 using cash_on_pickup. Status: pending', '2025-04-26 11:50:16'),
(263, 38, 'Payment processed for order #89 using cash_on_pickup. Status: pending', '2025-04-26 11:51:01'),
(264, 40, 'User logged in.', '2025-04-26 11:55:33'),
(265, 40, 'User logged in.', '2025-04-26 11:55:41'),
(266, 40, 'User logged in.', '2025-04-27 06:34:40'),
(267, 40, 'User logged in.', '2025-04-27 06:34:40'),
(268, 40, 'Payment processed for order #90 using cash_on_pickup. Status: pending', '2025-04-27 06:42:50'),
(269, 21, 'Database reset initiated: clearing orders, pickups, payments and resetting stock levels', '2025-04-27 07:28:33'),
(270, 21, 'Successfully reset database: cleared transactions and restored product stock levels', '2025-04-27 07:28:33'),
(271, 40, 'Payment processed for order #1 using cash_on_pickup. Status: pending', '2025-04-27 07:29:05'),
(272, 40, 'Payment processed for order #1 using cash_on_pickup. Status: pending', '2025-04-27 07:29:25'),
(273, 40, 'Payment processed for order #2 using cash_on_pickup. Status: pending', '2025-04-27 07:30:46'),
(274, 40, 'Payment processed for order #3 using cash_on_pickup. Status: pending', '2025-04-27 07:33:27'),
(275, 40, 'Payment processed for order #3 using cash_on_pickup. Status: pending', '2025-04-27 07:33:49'),
(276, 40, 'Payment processed for order #4 using cash_on_pickup. Status: pending', '2025-04-27 07:34:14'),
(277, 38, 'User logged in.', '2025-04-27 07:35:03'),
(278, 38, 'User logged in.', '2025-04-27 07:35:03'),
(279, 38, 'Payment processed for order #5 using cash_on_pickup. Status: pending', '2025-04-27 07:35:19'),
(280, 38, 'Payment processed for order #6 using cash_on_pickup. Status: pending', '2025-04-27 07:38:42'),
(281, 21, 'Admin logged in', '2025-04-27 07:56:17'),
(282, 38, 'Payment processed for order #7 using cash_on_pickup. Status: pending', '2025-04-27 09:13:38'),
(283, 38, 'User logged in.', '2025-04-27 10:08:24'),
(284, 38, 'User logged in.', '2025-04-27 10:08:24'),
(285, 38, 'User logged in.', '2025-04-27 14:39:20'),
(286, 38, 'User logged in.', '2025-04-27 14:39:20'),
(287, 38, 'User logged in.', '2025-04-27 15:44:18'),
(288, 38, 'User logged in.', '2025-04-27 15:44:19');

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
(3, 20, '', 'Vegetable Farm', '', NULL, NULL, 20.00, NULL, 'Palinpinon');

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
  `status` varchar(20) DEFAULT 'pending',
  `farmer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `product_id`, `feedback_text`, `rating`, `created_at`, `status`, `farmer_id`) VALUES
(1, 19, 6, 'The mangoes were incredibly sweet and juicy. Will definitely buy again!', 5, '2025-03-09 16:52:28', 'pending', NULL),
(2, 19, 7, 'Fresh calamansi, perfect for my recipes. Very satisfied with the quality.', 4, '2025-03-10 16:52:28', 'pending', NULL),
(3, 19, 8, 'The rice has a wonderful aroma and cooks perfectly. Excellent quality!', 5, '2025-03-11 16:52:28', 'responded', NULL),
(4, 19, 9, 'The lemon basil wasn\'t as fresh as I expected. Slightly wilted upon arrival.', 3, '2025-03-12 16:52:28', 'responded', NULL),
(5, 19, 10, 'These sweet potatoes are amazing! Great flavor and perfect for my stews.', 5, '2025-03-13 16:52:28', 'pending', NULL),
(6, 20, NULL, 'The mangos from Palinpinon are good, but I\'ve had better. A bit overripe.', 3, '2025-03-14 16:52:28', 'pending', NULL),
(7, 20, NULL, 'Fresh kalamunggay, great for soup. Very green and crisp.', 4, '2025-03-15 16:52:28', 'responded', NULL),
(8, 20, 22, 'The kangkong was very fresh and clean. Good value for money.', 5, '2025-03-16 16:52:28', 'pending', NULL),
(9, 20, 34, 'Kamatis was a bit too ripe for my liking, but still usable.', 3, '2025-03-17 16:52:28', 'pending', NULL),
(10, 19, NULL, 'Overall, I love shopping at the Farmers Market. Great selection of fresh produce!', 5, '2025-03-18 16:52:28', 'responded', NULL),
(11, 20, NULL, 'The website is user-friendly, but checkout could be improved.', 4, '2025-03-19 16:52:28', 'pending', NULL),
(12, 19, NULL, 'Delivery was faster than expected. Products were well-packaged.', 5, '2025-03-20 16:52:28', 'pending', NULL),
(13, 20, NULL, 'Some products were missing from my order. Please improve accuracy.', 2, '2025-03-21 16:52:28', 'responded', NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `is_read`, `created_at`, `type`, `reference_id`) VALUES
(1, 19, 'Your product \"Dahon ng Sili (Chili Leaves)\" has been rejected. Reason: did not pass standards', 0, '2025-04-16 11:52:25', NULL, NULL),
(2, 19, 'Good news! Your product \"Dahon ng Sili (Chili Leaves)\" has been approved and is now available in the marketplace.', 0, '2025-04-16 13:36:47', 'product_approved', 61),
(3, 19, 'Good news! Your product \"Dalandan\" has been approved and is now available in the marketplace.', 0, '2025-04-16 13:55:02', 'product_approved', 54),
(4, 19, 'Good news! Your product \"Dayap (Key Lime)\" has been approved and is now available in the marketplace.', 0, '2025-04-16 13:55:09', 'product_approved', 55),
(5, 20, 'Good news! Your product \"Lemon Basil\" has been approved and is now available in the marketplace.', 0, '2025-04-23 02:22:29', 'product_approved', 9),
(6, 19, 'Good news! Your product \"Malunggay (Moringa)\" has been approved and is now available in the marketplace.', 0, '2025-04-23 02:22:34', 'product_approved', 45);

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
(1, 1, 45, 2, 10.00),
(2, 2, 45, 1, 10.00),
(3, 2, 48, 1, 60.00),
(4, 2, 54, 1, 60.00),
(5, 2, 55, 1, 50.00),
(6, 3, 45, 1, 10.00),
(7, 4, 48, 1, 60.00),
(8, 5, 45, 1, 10.00),
(9, 5, 48, 1, 60.00),
(10, 5, 54, 1, 60.00),
(11, 6, 58, 1, 45.04),
(12, 6, 59, 1, 15.00),
(13, 6, 60, 1, 60.00),
(14, 6, 56, 1, 35.00),
(15, 6, 57, 1, 40.00),
(16, 7, 48, 1, 60.00),
(17, 7, 56, 1, 35.00),
(18, 7, 57, 1, 40.00),
(19, 7, 58, 1, 45.04),
(20, 7, 59, 1, 15.00);

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
(1, 40, 'pending', '2025-04-27 07:29:02', 'Municipal Agriculture Office'),
(2, 40, 'pending', '2025-04-27 07:29:54', 'Municipal Agriculture Office'),
(3, 40, 'pending', '2025-04-27 07:33:24', 'Municipal Agriculture Office'),
(4, 40, 'pending', '2025-04-27 07:34:12', 'Municipal Agriculture Office'),
(5, 38, 'pending', '2025-04-27 07:35:17', 'Municipal Agriculture Office'),
(6, 38, 'pending', '2025-04-27 07:38:39', 'Municipal Agriculture Office'),
(7, 38, 'pending', '2025-04-27 09:13:35', 'Municipal Agriculture Office');

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
  `payment_method` enum('credit_card','paypal','bank_transfer','cash_on_pickup') NOT NULL,
  `method_id` int(11) NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `last_error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `method_id`, `payment_status`, `payment_date`, `user_id`, `amount`, `transaction_reference`, `payment_notes`, `retry_count`, `last_error`) VALUES
(1, 1, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:29:05', 40, 20.00, 'CP-1-20250427-89F9', NULL, 0, NULL),
(2, 1, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:29:25', 40, 20.00, 'CP-1-20250427-9D3E', NULL, 0, NULL),
(3, 2, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:30:46', 40, 180.00, 'CP-2-20250427-A2AA', NULL, 0, NULL),
(4, 3, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:33:27', 40, 10.00, 'CP-3-20250427-A993', NULL, 0, NULL),
(5, 3, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:33:49', 40, 10.00, 'CP-3-20250427-92ED', NULL, 0, NULL),
(6, 4, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:34:14', 40, 60.00, 'CP-4-20250427-958F', NULL, 0, NULL),
(7, 5, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:35:19', 38, 130.00, 'CP-5-20250427-4ACD', NULL, 0, NULL),
(8, 6, 'cash_on_pickup', 4, 'pending', '2025-04-27 07:38:42', 38, 195.00, 'CP-6-20250427-7270', NULL, 0, NULL),
(9, 7, 'cash_on_pickup', 4, 'pending', '2025-04-27 09:13:38', 38, 195.00, 'CP-7-20250427-5121', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_credit_cards`
--

CREATE TABLE `payment_credit_cards` (
  `card_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `card_last_four` varchar(4) NOT NULL,
  `card_brand` varchar(30) NOT NULL,
  `card_expiry_month` tinyint(4) NOT NULL,
  `card_expiry_year` smallint(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `method_id` int(11) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`method_id`, `method_name`, `is_active`, `created_at`) VALUES
(1, 'credit_card', 1, '2025-04-24 06:32:38'),
(2, 'paypal', 1, '2025-04-24 06:32:38'),
(3, 'bank_transfer', 1, '2025-04-24 06:32:38'),
(4, 'cash_on_pickup', 1, '2025-04-24 06:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `payment_retries`
--

CREATE TABLE `payment_retries` (
  `retry_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `error_message` text DEFAULT NULL,
  `timeout_ms` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_status_history`
--

CREATE TABLE `payment_status_history` (
  `history_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_status_history`
--

INSERT INTO `payment_status_history` (`history_id`, `payment_id`, `status`, `notes`, `created_at`) VALUES
(1, 1, 'pending', NULL, '2025-04-27 07:29:05'),
(2, 2, 'pending', NULL, '2025-04-27 07:29:25'),
(3, 3, 'pending', NULL, '2025-04-27 07:30:46'),
(4, 4, 'pending', NULL, '2025-04-27 07:33:27'),
(5, 5, 'pending', NULL, '2025-04-27 07:33:49'),
(6, 6, 'pending', NULL, '2025-04-27 07:34:14'),
(7, 7, 'pending', NULL, '2025-04-27 07:35:19'),
(8, 8, 'pending', NULL, '2025-04-27 07:38:42'),
(9, 9, 'pending', NULL, '2025-04-27 09:13:38');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `pickup_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
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

INSERT INTO `pickups` (`pickup_id`, `order_id`, `payment_id`, `pickup_status`, `pickup_date`, `pickup_location`, `pickup_notes`, `office_location`, `contact_person`) VALUES
(1, 1, 2, 'pending', '2025-04-30 02:00:00', 'Municipal Agriculture Office', NULL, 'Municipal Agriculture Office', NULL),
(2, 2, 3, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(3, 3, 5, 'pending', '2025-04-30 02:00:00', 'Municipal Agriculture Office', NULL, 'Municipal Agriculture Office', NULL),
(4, 4, 6, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(5, 5, 7, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(6, 6, 8, 'pending', '2025-05-08 08:00:00', 'Municipal Agriculture Office', 'DALA UG BAG IMO \r\n', 'Municipal Agriculture Office', ''),
(7, 7, 9, 'pending', '2025-05-10 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL);

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
(8, 10),
(9, 13),
(10, 8),
(22, 2),
(34, 9),
(43, 8),
(45, 9),
(48, 17),
(52, 2),
(55, 13),
(56, 8),
(57, 6),
(58, 8),
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
(6, 'Mango', 'Sweet and juicy Carabao mangoes grown in the heart of Dumaguete.', 120.00, 22, 'pending', '2024-12-12 06:45:15', '2025-04-14 08:53:31', NULL, 100, 'piece'),
(7, 'Calamansi', 'Fresh green calamansi, perfect for making refreshing juices or for cooking.', 40.00, 22, 'pending', '2024-12-12 06:45:15', '2025-04-27 07:28:33', NULL, 50, 'piece'),
(8, 'Ayungon Rice', 'Locally grown organic rice from Ayungon, known for its soft texture and aroma.', 60.00, 20, 'approved', '2024-12-12 06:45:15', '2025-04-27 07:28:33', 'uploads/products/680a3d2670179_rice.png', 100, 'kilogram'),
(9, 'Lemon Basil', 'Fresh lemon basil harvested from local gardens, great for cooking or herbal tea.', 60.00, 20, 'approved', '2024-12-12 06:45:15', '2025-04-27 07:28:33', NULL, 50, 'piece'),
(10, 'Organic Sweet Potatoes', 'Sweet, organic sweet potatoes grown without pesticides, perfect for stews or baking.', 80.00, 20, 'approved', '2024-12-12 06:45:15', '2025-04-27 07:28:33', 'uploads/products/67ffb25bb3be3_IMG_0041.JPG', 100, 'piece'),
(19, 'kamote', 'root crop', 30.00, 22, 'approved', '2025-02-05 15:01:51', '2025-04-27 07:28:33', NULL, 50, 'piece'),
(22, 'kangkong', 'Unknown\r\n', 21.00, 22, 'pending', '2025-02-10 10:14:24', '2025-04-27 07:28:33', 'uploads/products/67f70289536df_kangkong.png', 50, 'piece'),
(34, 'kamatis', 'veggies', 21.00, 22, 'pending', '2025-02-10 11:21:22', '2025-04-27 07:28:33', 'uploads/products/67f7027828f32_Kamatis.jpg', 50, 'piece'),
(38, 'Sugar Cane', 'From the farms of Mabinay - Bais.', 20.00, NULL, 'approved', '2025-03-24 05:43:30', '2025-04-27 07:28:33', NULL, 50, 'piece'),
(41, 'Kamote (Sweet Potato)', 'Freshly harvested sweet potatoes from local farms. Rich in vitamins and nutrients.', 45.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'kilogram'),
(42, 'Ube (Purple Yam)', 'Premium quality purple yam. Perfect for traditional Filipino desserts.', 70.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 50, 'kilogram'),
(43, 'Gabi (Taro)', 'Fresh taro roots. Great for stews and traditional Filipino dishes.', 60.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-14 08:53:38', NULL, 75, 'kilogram'),
(44, 'Kangkong (Water Spinach)', 'Fresh water spinach harvested from clean water sources. Great for stir-fry dishes.', 25.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 80, 'bunch'),
(45, 'Malunggay (Moringa)', 'Highly nutritious moringa leaves. Known for health benefits and great for soups.', 10.00, 19, 'approved', '2025-04-09 15:39:55', '2025-04-27 07:35:17', 'uploads/products/67ff925ccd2bf_kalamunggay.png', 95, 'bunch'),
(46, 'Pechay (Bok Choy)', 'Crisp and fresh bok choy. Excellent for stir-fry and soups.', 30.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 90, 'bunch'),
(47, 'Saging Saba (Cooking Banana)', 'Traditional cooking bananas. Perfect for turon and other Filipino desserts.', 50.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'bunch'),
(48, 'Santol', 'Sweet and tangy santol fruits. Great for eating fresh or making preserves.', 60.00, 20, 'approved', '2025-04-09 15:39:55', '2025-04-27 09:13:35', 'uploads/products/680b5ccc98dd2_santol.png', 46, 'kilogram'),
(49, 'Lanzones', 'Sweet and fragrant lanzones from Camiguin. Limited seasonal availability.', 120.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 30, 'kilogram'),
(50, 'Talong (Eggplant)', 'Long, purple eggplants. Perfect for tortang talong and other Filipino dishes.', 40.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 70, 'kilogram'),
(51, 'Okra', 'Young and tender okra. Great for sinigang and other soups.', 35.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 65, 'kilogram'),
(52, 'Ampalaya (Bitter Gourd)', 'Fresh bitter gourd. Known for health benefits and distinctive flavor.', 45.00, 22, 'pending', '2025-04-09 15:39:55', '2025-04-14 08:53:34', NULL, 50, 'bunch'),
(53, 'Kalamansi', 'Small, tangy Filipino citrus fruits. Perfect for juices and flavoring dishes.', 40.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-11 14:54:12', 'uploads/products/67f92d14676cb_calamansi.png', 60, 'kilogram'),
(54, 'Dalandan', 'Sweet and tangy local oranges. Great for fresh juice.', 60.00, 19, 'approved', '2025-04-09 15:39:55', '2025-04-27 07:35:17', NULL, 58, 'kilogram'),
(55, 'Dayap (Key Lime)', 'Aromatic key limes. Perfect for desserts and beverages.', 50.00, 22, 'approved', '2025-04-09 15:39:55', '2025-04-27 07:29:54', 'uploads/products/680ae61e4d3bd_dayap.png', 49, 'kilogram'),
(56, 'Sayote (Chayote)', 'Mild-flavored squash. Versatile for many Filipino dishes.', 35.00, 20, 'approved', '2025-04-09 15:39:55', '2025-04-27 09:13:35', 'uploads/products/680ae57d79969_sayote.png', 73, 'kilogram'),
(57, 'Repolyo (Cabbage)', 'Fresh, crisp cabbage heads from cool mountain farms.', 40.00, 20, 'approved', '2025-04-09 15:39:55', '2025-04-27 09:13:35', 'uploads/products/680ae4e249743_cabbage.png', 48, 'kilogram'),
(58, 'Carrots', 'Sweet, crunchy carrots from highland farms. Great for soups and stews.', 45.04, 20, 'approved', '2025-04-09 15:39:55', '2025-04-27 09:13:35', 'uploads/products/67ff9240ab00e_67f9cdf83a9bf_carrots.png', 58, 'kilogram'),
(59, 'Tanglad (Lemongrass)', 'Aromatic lemongrass stalks. Perfect for teas and flavoring dishes.', 15.00, 22, 'approved', '2025-04-09 15:39:55', '2025-04-27 09:13:35', 'uploads/products/67f8f502b861c_tanglad.png', 148, 'bunch'),
(60, 'Luya (Ginger)', 'Fresh, aromatic ginger root. Essential for many Filipino dishes.', 60.00, 22, 'approved', '2025-04-09 15:39:55', '2025-04-27 07:38:39', 'uploads/products/67f6ff250ccd2_Ginger.png', 59, 'kilogram'),
(61, 'Dahon ng Sili (Chili Leaves)', 'Flavorful chili leaves. Great for tinola and other soups.', 50.00, 22, 'approved', '2025-04-09 15:39:55', '2025-04-26 05:06:01', 'uploads/products/67f6fec7ef775_chili.png', 200, 'bunch');

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
(20, 'david_moorer', 'davidPass654', 'david.moore@example.com', 2, '2024-12-11 15:15:06', '2025-04-12 06:41:43', 'david', 'moore', '012391203', 'Boston'),
(21, 'kevin_chris', '$2y$10$1vtHXjlZN4nMC5rFGL3pMOuSM2BhFBEAX5w6wnUn.TtcSo5RSlkjy', 'kchris.kd@gmail.com', 3, '2025-02-02 16:10:36', '2025-02-04 10:38:23', 'kevin', 'Durango', '098123447812', 'Sibulan\r\n'),
(22, 'Dayn Cristofer', '$2y$10$6ElsrbEeo2eehqZWh6cAjeRxuH87cHa42nMfryRclqr5he.v0h8B2', 'dayn@gmail.com', 2, '2025-02-04 07:16:08', '2025-04-12 06:41:32', 'Dayn Cristofer', 'Durango', '09123124412', 'Sibulan\r\n'),
(23, 'angeline', '$2y$10$OFmOhK20MAY5iBnyjcKgZul90bB5cvvdJD46VYbV41lVEvjY1kTHq', 'angeline@gmail.com', 4, '2025-02-04 10:39:34', '2025-02-18 08:50:18', 'angeline', 'largo', '09123124512', 'Bolocboloc\r\n'),
(24, 'John', '$2y$10$jDhBWKeR6keXyrSR3Z6LguaAep4isunG0JqiTPiFV//BYN/O6K.KS', 'John@gmail.com', 5, '2025-03-11 07:36:36', '2025-03-11 07:36:36', 'John', 'Grime', '09987890987', 'Texas\r\n'),
(25, 'Kyle', '$2y$10$DE8HdGyIu4xMzYY3B2UET.EY0rWHZKI8hapj7.PRqhX5rFFCLxdjy', 'kyle@gmail.com', 3, '2025-03-18 03:52:45', '2025-03-18 03:52:59', 'kyle', 'wrymyr', '09312038401', 'Basay\r\n'),
(34, 'chris.doe@example.com', '$2y$10$PTOWxTJS3mBdsUWzwjPshuNwZh1nQOrRi3u1xUNLPNf7baF4JbgXe', 'chris.doe@example.com', 1, '2025-03-27 12:44:52', '2025-03-27 12:44:52', 'Chris', 'Doe', NULL, NULL),
(35, 'johndoe@example.com', '$2y$10$/YPt38XfM8xFwVuTyx4iAOeNGayBNsZKHGT8KKhgeTfAT7YsadsHe', 'johndoe@example.com', 1, '2025-03-28 12:35:07', '2025-03-28 12:35:07', 'John', 'Doe', '1234567890', '123 Main St'),
(36, 'kevin@gmail.com', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'kevin@gmail.com', 1, '2025-03-28 13:06:07', '2025-03-28 13:06:07', 'Kevin', 'Durango', '9516063243', 'Sibulan'),
(37, 'remie', '$2y$10$e/.3ZsV7yaOYxLLVuRcr3.lGzSj7GnRtIlBP3XpaZ6NE3JxLlah3m', 'remie@gmail.com', 1, '2025-04-01 16:46:47', '2025-04-01 16:46:47', 'Remie', '', '9516063243', 'Sibulan'),
(38, 'fely', '$2y$10$LYlLavAqGTVPpj8K15/52eL/uyuNKsizWkQ/QJhFcjpaey69fb.DK', 'fely@gmail.com', 1, '2025-04-07 07:48:24', '2025-04-07 07:48:24', 'Fely', '', '92629595995', 'Mabinay'),
(39, 'Alina', '$2y$10$5DCd0RGpjnwwxxqDv1sKte81JxzR8aE0.FsPaFIeH4p7PLR/mfxvO', 'Alina@gmail.com', 3, '2025-04-17 04:47:47', '2025-04-17 04:47:47', 'alina', 'thea', '0978877872910', 'Bacong'),
(40, 'largoangelinegrime', '$2y$10$RkHuMaRi2QcAn.MFSZg9eeSh7yYikahk1xPvjiYzpo9HsYKMN3u42', 'largoangelinegrime@gmail.com', 1, '2025-04-25 08:25:55', '2025-04-25 08:25:55', 'Angeline', 'Largo', '9516063243', 'Sibulan');

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
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_feedback_farmer` (`farmer_id`);

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
  ADD KEY `consumer_id` (`consumer_id`),
  ADD KEY `idx_orders_status` (`order_status`);

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
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_payments_order_id` (`order_id`),
  ADD KEY `idx_payments_status` (`payment_status`),
  ADD KEY `idx_payments_method` (`method_id`);

--
-- Indexes for table `payment_credit_cards`
--
ALTER TABLE `payment_credit_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD KEY `idx_payment_cards` (`payment_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`method_id`);

--
-- Indexes for table `payment_retries`
--
ALTER TABLE `payment_retries`
  ADD PRIMARY KEY (`retry_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `payment_status_history`
--
ALTER TABLE `payment_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_payment_status_history` (`payment_id`,`created_at`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`pickup_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_pickups_payment` (`payment_id`);

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
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_products_status` (`status`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

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
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_credit_cards`
--
ALTER TABLE `payment_credit_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_retries`
--
ALTER TABLE `payment_retries`
  MODIFY `retry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_status_history`
--
ALTER TABLE `payment_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  ADD CONSTRAINT `fk_feedback_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
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
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`method_id`);

--
-- Constraints for table `payment_credit_cards`
--
ALTER TABLE `payment_credit_cards`
  ADD CONSTRAINT `payment_credit_cards_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_retries`
--
ALTER TABLE `payment_retries`
  ADD CONSTRAINT `payment_retries_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_status_history`
--
ALTER TABLE `payment_status_history`
  ADD CONSTRAINT `payment_status_history_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `fk_pickups_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `productcategorymapping`
--
ALTER TABLE `productcategorymapping`
  ADD CONSTRAINT `fk_productcategorymapping_category` FOREIGN KEY (`category_id`) REFERENCES `productcategories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_productcategorymapping_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
