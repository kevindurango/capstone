-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2025 at 09:33 AM
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
(147, 23, 'Updated order #1 status to completed', '2025-03-18 20:39:36'),
(148, 21, 'Admin logged in', '2025-03-18 21:27:22'),
(149, 23, 'Manager logged in.', '2025-03-18 21:28:40'),
(150, 23, 'Unauthorized access attempt to admin product management', '2025-03-19 03:50:35'),
(151, 21, 'Admin logged in', '2025-03-19 03:50:42'),
(152, 21, 'Added new user: Ricardo Milos', '2025-03-19 04:06:24'),
(153, 21, 'Edited user: Ricardo Milos', '2025-03-19 04:11:25'),
(154, 21, 'Edited user: Ricardo Milos', '2025-03-19 04:13:57'),
(155, 21, 'Edited user: Ricardo Milos', '2025-03-19 06:40:07'),
(156, 24, 'Organization Head logged in.', '2025-03-19 17:47:29'),
(157, 24, 'Organization Head logged in.', '2025-03-19 17:50:41'),
(158, 24, 'Organization Head logged in.', '2025-03-19 17:50:42'),
(159, 24, 'Organization Head logged in.', '2025-03-19 17:50:44'),
(160, 24, 'Organization Head logged in.', '2025-03-19 17:52:24'),
(161, 24, 'Organization Head logged in.', '2025-03-19 17:55:09'),
(162, 24, 'Organization Head logged in.', '2025-03-19 17:55:11'),
(163, 24, 'Organization Head logged in.', '2025-03-19 17:55:11'),
(164, 24, 'Organization Head logged in.', '2025-03-19 17:55:12'),
(165, 24, 'Organization Head logged in.', '2025-03-19 17:59:41'),
(166, 24, 'Organization Head logged in.', '2025-03-19 17:59:42'),
(167, 24, 'Organization Head logged in.', '2025-03-19 17:59:47'),
(168, 24, 'Organization Head logged in.', '2025-03-19 17:59:48'),
(169, 24, 'Organization Head logged in.', '2025-03-19 17:59:48'),
(170, 24, 'Organization Head logged in.', '2025-03-19 18:05:20'),
(171, 24, 'Organization Head logged in.', '2025-03-19 18:12:47'),
(172, 24, 'Organization Head logged in.', '2025-03-19 18:12:48'),
(173, 24, 'Organization Head logged in.', '2025-03-19 18:12:52'),
(174, 24, 'Organization Head logged in.', '2025-03-19 18:12:53'),
(175, 24, 'Organization Head logged in.', '2025-03-19 18:18:00'),
(176, 24, 'Organization Head logged in.', '2025-03-19 18:18:00'),
(177, 24, 'Organization Head logged in.', '2025-03-19 18:18:03'),
(178, 24, 'Organization Head logged in.', '2025-03-19 18:18:10'),
(179, 24, 'Organization Head logged in.', '2025-03-19 18:18:13'),
(180, 24, 'Organization Head logged in.', '2025-03-23 09:21:51'),
(181, 24, 'Organization Head logged in.', '2025-03-23 09:36:33'),
(182, 24, 'Organization Head logged in.', '2025-03-23 09:36:34'),
(183, 24, 'Organization Head logged in.', '2025-03-23 09:38:22'),
(184, 24, 'Organization Head logged in.', '2025-03-23 09:38:23'),
(185, 24, 'Organization Head logged in.', '2025-03-23 09:38:50'),
(186, 24, 'Organization Head logged in.', '2025-03-23 09:38:52'),
(187, 24, 'Organization Head logged in.', '2025-03-23 09:39:11'),
(188, 24, 'Organization Head logged in.', '2025-03-23 09:39:12'),
(189, 24, 'Organization Head logged in.', '2025-03-23 09:39:30'),
(190, 24, 'Organization Head logged in.', '2025-03-23 09:48:39'),
(191, 24, 'Organization Head logged in.', '2025-03-23 09:50:04'),
(192, 24, 'Organization Head logged in.', '2025-03-23 09:50:33'),
(193, 24, 'Organization Head logged in.', '2025-03-23 09:50:33'),
(194, 24, 'Organization Head logged in.', '2025-03-23 09:50:34'),
(195, 24, 'Organization Head logged in.', '2025-03-23 09:51:19'),
(196, 24, 'Organization Head logged in.', '2025-03-23 09:51:44'),
(197, 24, 'Organization Head logged in.', '2025-03-23 09:51:45'),
(198, 24, 'Organization Head logged in.', '2025-03-23 09:54:33'),
(199, 24, 'Organization Head logged in.', '2025-03-23 09:55:53'),
(200, 24, 'Organization Head logged in.', '2025-03-23 09:57:49'),
(201, 24, 'Organization Head logged in.', '2025-03-23 09:58:10'),
(202, 24, 'Organization Head logged in.', '2025-03-23 09:58:24'),
(203, 24, 'Organization Head logged in.', '2025-03-23 10:05:57'),
(204, 24, 'Organization Head logged in.', '2025-03-23 10:09:10'),
(205, 24, 'Organization Head logged in.', '2025-03-23 10:09:11'),
(206, 24, 'Organization Head logged in.', '2025-03-23 10:09:11'),
(207, 24, 'Organization Head logged in.', '2025-03-23 10:09:12'),
(208, 24, 'Organization Head logged in.', '2025-03-23 10:10:43'),
(209, 24, 'Organization Head logged in.', '2025-03-23 10:10:43'),
(210, 24, 'Organization Head logged in.', '2025-03-23 10:10:47'),
(211, 24, 'Organization Head logged in.', '2025-03-23 10:13:58'),
(212, 24, 'Organization Head logged in.', '2025-03-23 10:14:15'),
(213, 23, 'Manager logged in.', '2025-03-23 10:15:05'),
(214, 24, 'Organization Head logged in.', '2025-03-23 10:17:03'),
(215, 24, 'Organization Head logged in.', '2025-03-23 10:20:16'),
(216, 24, 'Organization Head logged in.', '2025-03-23 10:20:20'),
(217, 24, 'Organization Head logged in.', '2025-03-23 10:20:21'),
(218, 24, 'Organization Head logged in.', '2025-03-23 10:20:21'),
(219, 24, 'Organization Head logged in.', '2025-03-23 10:20:21'),
(220, 24, 'Organization Head logged in.', '2025-03-23 10:20:22'),
(221, 24, 'Organization Head logged in.', '2025-03-23 10:23:06'),
(222, 24, 'Organization Head logged in.', '2025-03-23 10:23:08'),
(223, 24, 'Organization Head logged in.', '2025-03-23 10:23:09'),
(224, 24, 'Organization Head logged in.', '2025-03-23 10:23:14'),
(225, 24, 'Organization Head logged in.', '2025-03-23 10:23:15'),
(226, 24, 'Organization Head logged in.', '2025-03-23 10:25:13'),
(227, 24, 'Organization Head logged in.', '2025-03-23 10:25:14'),
(228, 24, 'Organization Head logged in.', '2025-03-23 10:25:14'),
(229, 24, 'Organization Head logged in.', '2025-03-23 10:25:15'),
(230, 24, 'Organization Head logged in.', '2025-03-23 10:26:16'),
(231, 24, 'Organization Head logged in.', '2025-03-23 10:26:17'),
(232, 24, 'Organization Head logged in.', '2025-03-23 10:26:18'),
(233, 24, 'Organization Head logged in.', '2025-03-23 10:26:19'),
(234, 24, 'Organization Head logged in.', '2025-03-23 10:26:32'),
(235, 24, 'Organization Head logged in.', '2025-03-23 10:26:34'),
(236, 24, 'Organization Head logged in.', '2025-03-23 10:26:47'),
(237, 24, 'Organization Head logged in.', '2025-03-23 10:26:52'),
(238, 24, 'Organization Head logged in.', '2025-03-23 10:26:53'),
(239, 24, 'Organization Head logged in.', '2025-03-23 10:26:58'),
(240, 24, 'Organization Head logged in.', '2025-03-23 10:26:59'),
(241, 24, 'Organization Head logged in.', '2025-03-23 10:27:12'),
(242, 24, 'Organization Head logged in.', '2025-03-23 10:27:14'),
(243, 24, 'Organization Head logged in.', '2025-03-23 10:30:49'),
(244, 24, 'Organization Head logged in.', '2025-03-23 10:30:50'),
(245, 24, 'Organization Head logged in.', '2025-03-23 10:30:50'),
(246, 24, 'Organization Head logged in.', '2025-03-23 10:30:50'),
(247, 24, 'Organization Head logged in.', '2025-03-23 10:30:51'),
(248, 24, 'Organization Head logged in.', '2025-03-23 10:31:03'),
(249, 24, 'Organization Head logged in.', '2025-03-23 10:31:05'),
(250, 24, 'Organization Head logged in.', '2025-03-23 10:34:50'),
(251, 24, 'Organization Head logged in.', '2025-03-23 10:34:57'),
(252, 24, 'Organization Head logged in.', '2025-03-23 10:34:58'),
(253, 24, 'Organization Head logged in.', '2025-03-23 10:36:42'),
(254, 24, 'Organization Head logged in.', '2025-03-23 10:36:43'),
(255, 24, 'Organization Head logged in.', '2025-03-23 10:37:41'),
(256, 24, 'Organization Head logged in.', '2025-03-23 10:37:49'),
(257, 24, 'Organization Head logged in.', '2025-03-23 10:37:50'),
(258, 24, 'Organization Head logged in.', '2025-03-23 10:38:37'),
(259, 24, 'Organization Head logged in.', '2025-03-23 10:38:38'),
(260, 24, 'Organization Head logged in.', '2025-03-23 11:00:24'),
(261, 24, 'Organization Head logged in.', '2025-03-23 11:00:27'),
(262, 24, 'Organization Head logged in.', '2025-03-23 12:48:35'),
(263, 24, 'Organization Head logged in.', '2025-03-23 12:51:08'),
(264, 24, 'Organization Head logged in.', '2025-03-23 12:51:10'),
(265, 24, 'Organization Head logged in.', '2025-03-23 12:54:03'),
(266, 24, 'Organization Head logged in.', '2025-03-23 12:54:05'),
(267, 24, 'Organization Head logged in.', '2025-03-23 12:54:46'),
(268, 24, 'Organization Head logged in.', '2025-03-23 12:54:51'),
(269, 24, 'Organization Head logged in.', '2025-03-23 12:54:53'),
(270, 24, 'Organization Head logged in.', '2025-03-23 12:54:57'),
(271, 24, 'Organization Head logged in.', '2025-03-23 12:56:38'),
(272, 24, 'Viewed details for Order #1', '2025-03-23 12:56:41'),
(273, 24, 'Organization Head logged in.', '2025-03-23 13:03:05'),
(274, 24, 'Organization Head logged in.', '2025-03-23 13:03:06'),
(275, 24, 'Organization Head logged in.', '2025-03-23 13:04:31'),
(276, 24, 'Organization Head logged in.', '2025-03-23 13:04:34'),
(277, 24, 'Organization Head logged in.', '2025-03-23 13:06:56'),
(278, 24, 'Organization Head logged in.', '2025-03-23 13:07:01'),
(279, 24, 'Organization Head logged in.', '2025-03-23 13:07:03'),
(280, 24, 'Organization Head logged in.', '2025-03-23 13:08:20'),
(281, 24, 'Organization Head logged in.', '2025-03-23 13:08:30'),
(282, 24, 'Organization Head logged in.', '2025-03-23 13:08:33'),
(283, 24, 'Organization Head logged in.', '2025-03-23 13:09:53'),
(284, 24, 'Organization Head logged in.', '2025-03-23 13:09:55'),
(285, 24, 'Organization Head logged in.', '2025-03-23 13:12:35'),
(286, 21, 'Admin logged in', '2025-03-23 13:12:50'),
(287, 24, 'Organization Head logged in successfully', '2025-03-23 13:14:27'),
(288, 24, 'Organization Head logged in.', '2025-03-23 13:14:27'),
(289, 24, 'Organization Head logged in.', '2025-03-23 13:14:41'),
(290, 24, 'Organization Head logged in.', '2025-03-23 13:14:42'),
(291, 24, 'Organization Head logged in.', '2025-03-23 13:14:47'),
(292, 24, 'Organization Head logged in.', '2025-03-23 13:14:51'),
(293, 24, 'Assigned driver ID 26 to pickup ID 1.', '2025-03-23 13:35:24'),
(294, 24, 'Organization Head logged in.', '2025-03-23 13:35:48'),
(295, 24, 'Organization Head logged in.', '2025-03-23 13:38:01'),
(296, 24, 'Organization Head logged in.', '2025-03-23 13:38:09'),
(297, 24, 'Organization Head logged in.', '2025-03-23 13:38:12'),
(298, 24, 'Organization Head logged in.', '2025-03-23 13:38:16'),
(299, 24, 'Organization Head logged in.', '2025-03-23 13:38:21'),
(300, 24, 'Organization Head logged in.', '2025-03-23 13:38:54'),
(301, 24, 'Organization Head logged in.', '2025-03-23 13:38:55'),
(302, 24, 'Organization Head logged in.', '2025-03-23 13:39:02'),
(303, 24, 'Organization Head logged in.', '2025-03-23 13:39:06'),
(304, 24, 'Updated driver ID 26 status to busy', '2025-03-23 13:42:01'),
(305, 24, 'Updated driver ID 26 status to available', '2025-03-23 13:44:13'),
(306, 23, 'Manager logged in.', '2025-03-23 13:50:04'),
(307, 24, 'Organization Head logged in successfully', '2025-03-23 13:52:28'),
(308, 24, 'Organization Head logged in.', '2025-03-23 13:52:28'),
(309, 24, 'Organization Head logged in.', '2025-03-23 13:52:37'),
(310, 24, 'Organization Head logged in.', '2025-03-23 13:54:29'),
(311, 24, 'Organization Head logged in.', '2025-03-23 13:54:44'),
(312, 24, 'Edited product: kalamunggay', '2025-03-23 14:13:20'),
(313, 24, 'Added new product: Tubo (Sugar Cane)', '2025-03-23 14:16:04'),
(314, 24, 'Deleted product with ID: 37', '2025-03-23 14:16:32'),
(315, 21, 'Admin logged in', '2025-03-23 14:22:54'),
(316, 24, 'Organization Head logged in successfully', '2025-03-23 14:23:18'),
(317, 24, 'Organization Head logged in.', '2025-03-23 14:23:19'),
(318, 24, 'Organization Head logged in.', '2025-03-23 14:24:51'),
(319, 24, 'Organization Head logged out.', '2025-03-23 14:25:40'),
(320, 24, 'Organization Head logged in successfully', '2025-03-23 14:25:43'),
(321, 24, 'Organization Head logged in.', '2025-03-23 14:25:43'),
(322, 24, 'Organization Head logged out.', '2025-03-23 14:27:35'),
(323, 24, 'Organization Head logged in.', '2025-03-23 14:30:06'),
(324, 24, 'Organization Head logged in.', '2025-03-23 14:32:35'),
(325, 24, 'Organization Head logged in.', '2025-03-23 14:32:36'),
(326, 24, 'Viewed details for Order #1', '2025-03-23 14:32:39'),
(327, 24, 'Organization Head logged in.', '2025-03-23 14:32:46'),
(328, 24, 'Organization Head logged in.', '2025-03-24 01:56:49'),
(329, 24, 'Organization Head logged in.', '2025-03-24 02:06:46'),
(330, 24, 'Organization Head logged in.', '2025-03-24 02:07:12'),
(331, 24, 'Organization Head logged in.', '2025-03-24 02:07:14'),
(332, 24, 'Organization Head logged in.', '2025-03-24 02:07:23'),
(333, 24, 'Organization Head logged in.', '2025-03-24 02:07:25'),
(334, 24, 'Organization Head logged in.', '2025-03-24 02:12:49'),
(335, 24, 'Organization Head logged in.', '2025-03-24 02:51:17'),
(336, 24, 'Organization Head logged in.', '2025-03-24 02:51:21'),
(337, 24, 'Organization Head logged in.', '2025-03-24 02:51:23'),
(338, 24, 'Organization Head logged in.', '2025-03-24 03:05:17'),
(339, 24, 'Organization Head logged in.', '2025-03-24 03:05:18'),
(340, 24, 'Organization Head logged in.', '2025-03-24 03:05:21'),
(341, 24, 'Organization Head logged in.', '2025-03-24 03:05:23'),
(342, 24, 'Organization Head logged in.', '2025-03-24 03:05:25'),
(343, 24, 'Organization Head logged in.', '2025-03-24 03:05:32'),
(344, 24, 'Organization Head logged in.', '2025-03-24 03:05:33'),
(345, 24, 'Organization Head logged in.', '2025-03-24 03:05:40'),
(346, 24, 'Organization Head logged in.', '2025-03-24 03:05:41'),
(347, 24, 'Organization Head logged in.', '2025-03-24 03:05:41'),
(348, 24, 'Organization Head logged in.', '2025-03-24 03:05:48'),
(349, 24, 'Organization Head logged in.', '2025-03-24 03:05:50'),
(350, 24, 'Organization Head logged in.', '2025-03-24 03:18:35'),
(351, 24, 'Organization Head logged in.', '2025-03-24 03:18:36'),
(352, 24, 'Organization Head logged in.', '2025-03-24 03:18:37'),
(353, 24, 'Organization Head logged in.', '2025-03-24 03:18:38'),
(354, 24, 'Organization Head logged in.', '2025-03-24 03:18:38'),
(355, 24, 'Organization Head logged in.', '2025-03-24 03:18:41'),
(356, 24, 'Organization Head logged in.', '2025-03-24 03:18:42'),
(357, 24, 'Organization Head logged in.', '2025-03-24 03:18:43'),
(358, 24, 'Organization Head logged in.', '2025-03-24 03:30:03'),
(359, 24, 'Organization Head logged in.', '2025-03-24 03:30:04'),
(360, 24, 'Organization Head logged in.', '2025-03-24 03:30:05'),
(361, 24, 'Organization Head logged in.', '2025-03-24 03:30:06'),
(362, 24, 'Organization Head logged in.', '2025-03-24 03:30:07'),
(363, 24, 'Organization Head logged in.', '2025-03-24 03:30:20'),
(364, 24, 'Organization Head logged in.', '2025-03-24 03:30:23'),
(365, 24, 'Organization Head logged in.', '2025-03-24 03:30:26'),
(366, 24, 'Organization Head logged in.', '2025-03-24 03:32:33'),
(367, 24, 'Organization Head logged in.', '2025-03-24 03:32:34'),
(368, 24, 'Organization Head logged in.', '2025-03-24 03:32:35'),
(369, 24, 'Organization Head logged in.', '2025-03-24 03:32:37'),
(370, 24, 'Organization Head logged in.', '2025-03-24 03:32:39'),
(371, 24, 'Organization Head logged in.', '2025-03-24 03:32:40'),
(372, 24, 'Organization Head logged in.', '2025-03-24 03:32:41'),
(373, 24, 'Organization Head logged in.', '2025-03-24 03:32:42'),
(374, 24, 'Organization Head logged in.', '2025-03-24 03:32:43'),
(375, 24, 'Organization Head logged in.', '2025-03-24 03:32:43'),
(376, 24, 'Organization Head logged in.', '2025-03-24 03:32:43'),
(377, 24, 'Organization Head logged in.', '2025-03-24 03:32:44'),
(378, 24, 'Organization Head logged out.', '2025-03-24 03:32:44'),
(379, 24, 'Organization Head logged in successfully', '2025-03-24 03:32:48'),
(380, 24, 'Organization Head logged in.', '2025-03-24 03:32:48'),
(381, 24, 'Organization Head logged in.', '2025-03-24 03:32:49'),
(382, 24, 'Organization Head logged in.', '2025-03-24 03:32:50'),
(383, 24, 'Organization Head logged in.', '2025-03-24 03:32:50'),
(384, 24, 'Organization Head logged in.', '2025-03-24 03:32:51'),
(385, 24, 'Organization Head logged in.', '2025-03-24 03:32:52'),
(386, 24, 'Organization Head logged in.', '2025-03-24 03:32:53'),
(387, 24, 'Organization Head logged in.', '2025-03-24 03:32:53'),
(388, 24, 'Organization Head logged in.', '2025-03-24 03:32:53'),
(389, 24, 'Organization Head logged in.', '2025-03-24 03:32:54'),
(390, 24, 'Organization Head logged in.', '2025-03-24 03:32:55'),
(391, 24, 'Organization Head logged in.', '2025-03-24 03:32:55'),
(392, 24, 'Organization Head logged in.', '2025-03-24 03:32:56'),
(393, 24, 'Organization Head logged in.', '2025-03-24 03:32:56'),
(394, 24, 'Organization Head logged in.', '2025-03-24 03:35:46'),
(395, 24, 'Organization Head logged in.', '2025-03-24 03:35:47'),
(396, 24, 'Organization Head logged in.', '2025-03-24 03:35:48'),
(397, 24, 'Organization Head logged in.', '2025-03-24 03:35:48'),
(398, 24, 'Organization Head logged in.', '2025-03-24 03:35:49'),
(399, 24, 'Organization Head logged in.', '2025-03-24 03:35:51'),
(400, 24, 'Organization Head logged in.', '2025-03-24 03:35:52'),
(401, 24, 'Organization Head logged in.', '2025-03-24 03:35:53'),
(402, 24, 'Organization Head logged in.', '2025-03-24 03:36:20'),
(403, 24, 'Organization Head logged in.', '2025-03-24 03:36:24'),
(404, 24, 'Organization Head logged in.', '2025-03-24 04:57:04'),
(405, 24, 'Organization Head logged in.', '2025-03-24 04:57:05'),
(406, 24, 'Organization Head logged in.', '2025-03-24 05:07:02'),
(407, 24, 'Organization Head logged in.', '2025-03-24 05:07:26'),
(408, 24, 'Organization Head logged in.', '2025-03-24 05:08:08'),
(409, 24, 'Organization Head logged in.', '2025-03-24 05:08:10'),
(410, 24, 'Organization Head logged in.', '2025-03-24 05:19:13'),
(411, 24, 'Organization Head logged in.', '2025-03-24 05:19:17'),
(412, 24, 'Organization Head logged in.', '2025-03-24 05:19:22'),
(413, 24, 'Organization Head logged in.', '2025-03-24 05:20:46'),
(414, 24, 'Organization Head logged in.', '2025-03-24 05:30:36'),
(415, 24, 'Organization Head logged in.', '2025-03-24 05:30:38'),
(416, 24, 'Organization Head logged in.', '2025-03-24 09:50:54'),
(417, 24, 'Organization Head logged in.', '2025-03-24 09:50:57'),
(418, 24, 'Organization Head logged in.', '2025-03-24 09:51:39'),
(419, 24, 'Organization Head logged in.', '2025-03-24 09:51:40'),
(420, 24, 'Organization Head logged in.', '2025-03-24 09:51:56'),
(421, 24, 'Organization Head logged in.', '2025-03-24 10:03:47'),
(422, 24, 'Organization Head logged in.', '2025-03-24 10:08:07'),
(423, 24, 'Organization Head logged in.', '2025-03-24 10:14:48'),
(424, 24, 'Organization Head logged in.', '2025-03-24 10:14:49'),
(425, 24, 'Organization Head logged in.', '2025-03-24 10:14:50'),
(426, 24, 'Organization Head logged in.', '2025-03-24 10:14:51'),
(427, 24, 'Organization Head logged in.', '2025-03-24 10:14:52'),
(428, 24, 'Organization Head logged in.', '2025-03-24 10:14:52'),
(429, 24, 'Organization Head logged in.', '2025-03-24 10:14:53'),
(430, 24, 'Organization Head logged in.', '2025-03-24 10:14:53'),
(431, 24, 'Organization Head logged in.', '2025-03-24 10:14:55'),
(432, 24, 'Organization Head logged in.', '2025-03-24 10:14:56'),
(433, 24, 'Organization Head logged in.', '2025-03-24 10:14:56'),
(434, 24, 'Organization Head logged in.', '2025-03-24 10:30:59'),
(435, 21, 'Admin logged in', '2025-03-24 10:32:09'),
(436, 21, 'Unauthorized access attempt to organization head product management', '2025-03-24 10:36:04'),
(437, 24, 'Organization Head logged in successfully', '2025-03-24 10:36:26'),
(438, 24, 'Organization Head logged in.', '2025-03-24 10:36:26'),
(439, NULL, 'Unauthorized access attempt to organization head product management', '2025-03-24 10:36:27'),
(440, 24, 'Organization Head logged in.', '2025-03-24 10:36:31'),
(441, NULL, 'Unauthorized access attempt to organization head product management', '2025-03-24 10:36:32'),
(442, 24, 'Organization Head logged in.', '2025-03-24 10:36:37'),
(443, 24, 'Organization Head performed batch approve on 2 products', '2025-03-24 11:02:07'),
(444, 24, 'Organization Head rejected product ID: 11. Notes: yes', '2025-03-24 11:05:51'),
(445, 24, 'Organization Head performed batch approve on 11 products', '2025-03-24 11:09:13'),
(446, 24, 'Organization Head logged out', '2025-03-24 11:09:30'),
(447, 23, 'Manager logged in.', '2025-03-24 11:09:56'),
(448, 23, 'Manager logged in.', '2025-03-24 13:52:47'),
(449, 23, 'Updated product ID: 22', '2025-03-24 14:30:28'),
(450, 23, 'Updated product ID: 22', '2025-03-24 14:30:42'),
(451, 23, 'Added new product: Ginger', '2025-03-24 14:47:08'),
(452, 23, 'Updated product ID: 40', '2025-03-24 14:47:28'),
(453, 23, 'Changed product ID: 38 status to rejected. Notes: ', '2025-03-24 14:48:38'),
(454, 23, 'Changed product ID: 40 status to rejected. Notes: ', '2025-03-24 14:48:52'),
(455, 23, 'Manager logged out', '2025-03-24 14:49:11'),
(456, 24, 'Organization Head logged in successfully', '2025-03-24 14:49:15'),
(457, 24, 'Organization Head logged in.', '2025-03-24 14:49:16'),
(458, 24, 'Updated order # status to processing', '2025-03-24 15:11:50'),
(459, 24, 'Updated order #1 status to processing', '2025-03-24 15:11:58'),
(460, 24, 'Organization Head logged in.', '2025-03-24 15:12:41'),
(461, 24, 'Organization Head logged in.', '2025-03-24 15:12:44'),
(462, 24, 'Organization Head logged in.', '2025-03-24 15:12:45'),
(463, 24, 'Organization Head logged in.', '2025-03-24 15:12:59'),
(464, 24, 'Organization Head logged in.', '2025-03-24 15:13:17'),
(465, 24, 'Updated order #1 status to processing', '2025-03-24 15:14:03'),
(466, 24, 'Updated order #1 status to pending', '2025-03-24 15:21:56'),
(467, 24, 'Updated order #1 status to completed', '2025-03-24 15:22:01'),
(468, 24, 'Updated order #1 status to pending', '2025-03-24 15:22:06'),
(469, 24, 'Updated farmer details for user ID: 19', '2025-03-24 15:46:22'),
(470, 24, 'Updated farmer details for user ID: 19', '2025-03-24 15:46:40'),
(471, 24, 'Organization Head logged in.', '2025-03-24 15:51:57'),
(472, 24, 'Updated farmer details for user ID: 20', '2025-03-24 15:55:12'),
(473, 24, 'Organization Head logged in.', '2025-03-24 15:55:33'),
(474, 24, 'Organization Head logged in.', '2025-03-24 16:05:04'),
(475, 24, 'Organization Head logged in.', '2025-03-24 16:05:13'),
(476, 24, 'Organization Head logged in.', '2025-03-24 16:43:00'),
(477, 24, 'Organization Head logged in.', '2025-03-24 16:55:47'),
(478, 24, 'Organization Head logged in.', '2025-03-24 16:55:56'),
(479, 24, 'Organization Head logged in.', '2025-03-24 16:58:29'),
(480, 24, 'Assigned driver ID 26 to pickup ID 2.', '2025-03-24 16:58:50'),
(481, 24, 'Organization Head logged in.', '2025-03-24 16:58:58'),
(482, 24, 'Updated driver ID 26 status to busy', '2025-03-24 16:59:11'),
(483, 24, 'Organization Head logged in.', '2025-03-24 16:59:16'),
(484, 24, 'Organization Head logged in.', '2025-03-24 16:59:39'),
(485, 24, 'Organization Head logged in.', '2025-03-24 16:59:40'),
(486, 24, 'Organization Head logged out.', '2025-03-24 16:59:40'),
(487, 21, 'Admin logged in', '2025-03-24 16:59:51'),
(488, 21, 'Deleted user with ID: 16', '2025-03-24 17:00:17'),
(489, 21, 'Deleted user with ID: 17', '2025-03-24 17:00:24'),
(490, 21, 'Deleted user with ID: 17', '2025-03-24 17:00:30'),
(491, 21, 'Deleted user with ID: 17', '2025-03-24 17:00:34'),
(492, 21, 'Deleted user with ID: 17', '2025-03-24 17:00:39'),
(493, 21, 'Deleted user with ID: 17', '2025-03-24 17:00:42'),
(494, 21, 'Deleted user with ID: 18', '2025-03-25 06:14:04'),
(495, 21, 'Added new user: Dylan', '2025-03-25 06:55:39'),
(496, 21, 'Deleted user with ID: 28', '2025-03-25 07:00:59'),
(497, 21, 'Added new user: Dylan', '2025-03-25 07:02:00'),
(498, 21, 'Deleted user with ID: 29', '2025-03-25 07:03:37'),
(499, 21, 'Deleted user with ID: 30', '2025-03-25 07:16:36'),
(500, 21, 'Deleted user with ID: 31', '2025-03-25 07:22:19'),
(501, 21, 'Added new user: Dylan', '2025-03-25 08:19:45'),
(502, 21, 'Edited user: Dylan', '2025-03-25 08:19:57'),
(503, 21, 'Edited user: Dylan', '2025-03-25 08:20:02'),
(504, 21, 'Admin logged out', '2025-03-25 08:20:08'),
(505, 23, 'Manager logged in.', '2025-03-25 08:20:15'),
(506, 23, 'Updated order #1 status to processing', '2025-03-25 17:12:12'),
(507, 21, 'Admin logged in', '2025-03-25 19:05:32');

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
-- Table structure for table `driver_details`
--

CREATE TABLE `driver_details` (
  `detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `availability_status` enum('available','busy','offline') DEFAULT 'offline',
  `max_load_capacity` decimal(10,2) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `completed_pickups` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_details`
--

INSERT INTO `driver_details` (`detail_id`, `user_id`, `vehicle_type`, `license_number`, `vehicle_plate`, `availability_status`, `max_load_capacity`, `current_location`, `contact_number`, `rating`, `completed_pickups`) VALUES
(2, 26, 'Motorcyle', '88392', 'HJL 839', 'available', 200.00, 'Sibulan', '09887782345', 0.00, 0),
(3, 32, 'Motorcyle', '88393', 'HJL 839', 'available', 1000.00, 'valencia', '091231234', 0.00, 0);

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
(6, 20, 11, 'The mangos from Palinpinon are good, but I\'ve had better. A bit overripe.', 3, '2025-03-14 16:52:28', 'pending'),
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
(5, 3, 11, 3, 120.00),
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
(5, 20, 'completed', '2025-03-18 01:00:00', 'Regular pickup'),
(6, 19, 'pending', '2025-03-19 08:45:00', 'Urgent pickup needed');

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
  `pickup_location` varchar(255) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `pickup_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`pickup_id`, `order_id`, `pickup_status`, `pickup_date`, `pickup_location`, `assigned_to`, `pickup_notes`) VALUES
(1, 1, 'pending', '2025-02-19 19:00:00', 'Valencia ', '26', 'Please bring ID for verification\r\n\r\n\r\n'),
(2, 1, 'pending', '2025-02-19 19:00:00', 'Valencia Market', '32', 'Please bring ID for verification'),
(3, 1, 'pending', '2025-03-19 11:00:00', 'Valencia Market', NULL, 'Please handle with care'),
(4, 2, 'assigned', '2025-03-19 13:30:00', 'Sibulan Market', '26', 'Call upon arrival'),
(5, 3, 'pending', '2025-03-19 16:15:00', 'Central Market', NULL, 'Fragile items'),
(6, 4, 'completed', '2025-03-18 10:00:00', 'Dumaguete Market', '26', 'Regular pickup completed'),
(7, 5, 'pending', '2025-03-19 17:45:00', 'Valencia Market', NULL, 'Priority pickup');

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
(6, 'Mango', 'Sweet and juicy Carabao mangoes grown in the heart of Dumaguete.', 120.00, NULL, 'approved', '2024-12-12 06:45:15', '2025-03-24 11:09:13', 'Array', 0),
(7, 'Calamansi', 'Fresh green calamansi, perfect for making refreshing juices or for cooking.', 40.00, 17, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 0),
(8, 'Ayungon Rice', 'Locally grown organic rice from Ayungon, known for its soft texture and aroma.', 90.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', '', 30),
(9, 'Lemon Basil', 'Fresh lemon basil harvested from local gardens, great for cooking or herbal tea.', 60.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', NULL, 0),
(10, 'Organic Sweet Potatoes', 'Sweet, organic sweet potatoes grown without pesticides, perfect for stews or baking.', 80.00, 20, 'approved', '2024-12-12 06:45:15', '2025-03-18 09:12:09', NULL, 0),
(11, 'mango', 'Mangos from Palinpinon\r\n\r\n', 120.00, 22, 'approved', '2024-12-16 09:41:12', '2025-03-24 11:09:13', 'uploads/2', 20),
(13, 'kalamunggay', 'fresh kalamunggay from brangay lunga valencia\r\n', 20.00, NULL, 'approved', '2025-02-05 14:34:31', '2025-03-24 11:09:13', 'uploads/kalamunggay.png', 0),
(19, 'kamote', 'root crop', 30.00, 22, 'approved', '2025-02-05 15:01:51', '2025-03-24 11:09:13', 'uploads/IMG_0041.JPG', 0),
(22, 'kangkong', 'Unknown\r\n', 21.00, 22, 'pending', '2025-02-10 10:14:24', '2025-03-24 14:30:42', 'uploads/kangkong.png', 10),
(34, 'kamatis', 'veggies', 21.00, 22, 'approved', '2025-02-10 11:21:22', '2025-03-18 17:45:05', 'uploads/Kamatis.jpg', 5),
(38, 'Sugar Cane', 'From the farms of Mabinay - Bais.', 20.00, NULL, 'rejected', '2025-03-24 05:43:30', '2025-03-24 14:48:38', 'Array', 50),
(40, 'Ginger', 'Root crop', 100.00, 22, 'rejected', '2025-03-24 14:47:08', '2025-03-24 14:48:52', '', 50);

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
(3, 1, '123 Main St', 'Valencia', 'Negros Oriental', '6210', 'Philippines', 'pending'),
(4, 1, '123 Valencia St', 'Valencia', 'Negros Oriental', '6215', 'Philippines', 'pending'),
(5, 2, '456 Sibulan Road', 'Sibulan', 'Negros Oriental', '6201', 'Philippines', 'pending'),
(6, 3, '789 Central Avenue', 'Dumaguete', 'Negros Oriental', '6200', 'Philippines', 'pending'),
(7, 4, '321 Market Street', 'Dumaguete', 'Negros Oriental', '6200', 'Philippines', 'delivered'),
(8, 5, '567 Valencia Road', 'Valencia', 'Negros Oriental', '6215', 'Philippines', 'pending');

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
(17, 'michael_white', 'michaelPass456', 'michael.white@example.com', 3, '2024-12-11 15:15:06', '2025-02-04 10:35:58', 'Michael', 'White', '123123', 'Valencia\r\n'),
(19, 'anna_lee', 'annaPass321', 'anna.lee@example.com', 2, '2024-12-11 15:15:06', '2025-03-03 08:08:57', 'anna', 'lee', '03023402394', 'Dumaguete\r\n'),
(20, 'david_moore', 'davidPass654', 'david.moore@example.com', 2, '2024-12-11 15:15:06', '2025-02-19 01:32:04', 'david', 'moore', '012391203', 'Boston'),
(21, 'kevin_chris', '$2y$10$1vtHXjlZN4nMC5rFGL3pMOuSM2BhFBEAX5w6wnUn.TtcSo5RSlkjy', 'kchris.kd@gmail.com', 3, '2025-02-02 16:10:36', '2025-02-04 10:38:23', 'kevin', 'Durango', '098123447812', 'Sibulan\r\n'),
(22, 'Dayn', '$2y$10$6ElsrbEeo2eehqZWh6cAjeRxuH87cHa42nMfryRclqr5he.v0h8B2', 'dayn@gmail.com', 2, '2025-02-04 07:16:08', '2025-03-18 17:33:56', 'Dayn', 'Durango', '09123124412', 'Sibulan\r\n'),
(23, 'angeline', '$2y$10$OFmOhK20MAY5iBnyjcKgZul90bB5cvvdJD46VYbV41lVEvjY1kTHq', 'angeline@gmail.com', 4, '2025-02-04 10:39:34', '2025-02-18 08:50:18', 'angeline', 'largo', '09123124512', 'Bolocboloc\r\n'),
(24, 'John', '$2y$10$jDhBWKeR6keXyrSR3Z6LguaAep4isunG0JqiTPiFV//BYN/O6K.KS', 'John@gmail.com', 5, '2025-03-11 07:36:36', '2025-03-11 07:36:36', 'John', 'Grime', '09987890987', 'Texas\r\n'),
(25, 'Kyle', '$2y$10$DE8HdGyIu4xMzYY3B2UET.EY0rWHZKI8hapj7.PRqhX5rFFCLxdjy', 'kyle@gmail.com', 3, '2025-03-18 03:52:45', '2025-03-18 03:52:59', 'kyle', 'wrymyr', '09312038401', 'Basay\r\n'),
(26, 'Ricardo Milos', '$2y$10$inT9bkEfJNPut3IYd2hKOeB6dQOB2/zz0Q1eiBWCGUH.X8/oqOkym', 'Ricardo@gmail.com', 6, '2025-03-19 03:59:48', '2025-03-19 06:40:07', 'Ricardo', 'Milos', '09887782345', 'Sibulan'),
(32, 'Dylan', '$2y$10$bsne97rRKHL39iQqZ5T.7eJdJsjeyUXJrEY4qsaARex.J4z8w50wy', 'Dylan@gmail.com', 6, '2025-03-25 08:19:45', '2025-03-25 08:20:02', 'dylan', 'durango', '091231234', 'valencia');

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
-- Indexes for table `driver_details`
--
ALTER TABLE `driver_details`
  ADD PRIMARY KEY (`detail_id`),
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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=508;

--
-- AUTO_INCREMENT for table `audittrail`
--
ALTER TABLE `audittrail`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_details`
--
ALTER TABLE `driver_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shippinginfo`
--
ALTER TABLE `shippinginfo`
  MODIFY `shipping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
-- Constraints for table `driver_details`
--
ALTER TABLE `driver_details`
  ADD CONSTRAINT `driver_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
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
