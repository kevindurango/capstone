-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2025 at 09:33 AM
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
(1, NULL, 'System maintenance: Activity logs were cleared', '2025-05-04 06:59:40');

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
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_id` int(11) NOT NULL,
  `barangay_name` varchar(100) NOT NULL,
  `municipality` varchar(100) DEFAULT 'Valencia',
  `province` varchar(100) DEFAULT 'Negros Oriental',
  `geographic_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `barangay_name`, `municipality`, `province`, `geographic_data`) VALUES
(1, 'Balayagmanok', 'Valencia', 'Negros Oriental', NULL),
(2, 'Balili', 'Valencia', 'Negros Oriental', NULL),
(3, 'Bongbong Central', 'Valencia', 'Negros Oriental', NULL),
(4, 'Cambucad', 'Valencia', 'Negros Oriental', NULL),
(5, 'Caidiocan', 'Valencia', 'Negros Oriental', NULL),
(6, 'Dobdob', 'Valencia', 'Negros Oriental', NULL),
(7, 'Jawa', 'Valencia', 'Negros Oriental', NULL),
(8, 'Liptong', 'Valencia', 'Negros Oriental', NULL),
(9, 'Lunga', 'Valencia', 'Negros Oriental', NULL),
(10, 'Malabo', 'Valencia', 'Negros Oriental', NULL),
(11, 'Mampas', 'Valencia', 'Negros Oriental', NULL),
(12, 'Palinpinon Central', 'Valencia', 'Negros Oriental', NULL),
(13, 'Puhagan Central', 'Valencia', 'Negros Oriental', NULL),
(14, 'Sagbang', 'Valencia', 'Negros Oriental', NULL),
(15, 'West Balabag', 'Valencia', 'Negros Oriental', NULL),
(16, 'Apolong', 'Valencia', 'Negros Oriental', NULL),
(17, 'Balugo', 'Valencia', 'Negros Oriental', NULL),
(18, 'Bong-ao', 'Valencia', 'Negros Oriental', NULL),
(19, 'Bongbong Lower', 'Valencia', 'Negros Oriental', NULL),
(20, 'Bongbong Upper', 'Valencia', 'Negros Oriental', NULL),
(21, 'Castellano', 'Valencia', 'Negros Oriental', NULL),
(22, 'East Balabag', 'Valencia', 'Negros Oriental', NULL),
(23, 'Malaunay', 'Valencia', 'Negros Oriental', NULL),
(24, 'Palinpinon Lower', 'Valencia', 'Negros Oriental', NULL),
(25, 'Palinpinon Upper', 'Valencia', 'Negros Oriental', NULL),
(26, 'Poblacion', 'Valencia', 'Negros Oriental', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `barangay_products`
--

CREATE TABLE `barangay_products` (
  `id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `estimated_production` decimal(10,2) DEFAULT NULL,
  `production_unit` varchar(20) DEFAULT 'kilogram',
  `year` int(4) DEFAULT year(curdate()),
  `season_id` int(11) DEFAULT NULL,
  `planted_area` decimal(10,2) DEFAULT 0.00,
  `area_unit` varchar(20) DEFAULT 'hectare',
  `field_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_products`
--

INSERT INTO `barangay_products` (`id`, `barangay_id`, `product_id`, `estimated_production`, `production_unit`, `year`, `season_id`, `planted_area`, `area_unit`, `field_id`) VALUES
(1, 1, 8, 1250.00, 'kilogram', 2025, 1, 2.50, 'hectare', NULL),
(2, 1, 48, 850.00, 'kilogram', 2025, 2, 1.75, 'hectare', NULL),
(3, 1, 45, 320.00, 'bunch', 2025, 4, 0.80, 'hectare', NULL),
(4, 1, 56, 450.00, 'kilogram', 2025, 4, 1.20, 'hectare', NULL),
(5, 1, 57, 380.00, 'kilogram', 2025, 5, 0.95, 'hectare', NULL),
(6, 2, 8, 950.00, 'kilogram', 2025, 1, 2.10, 'hectare', 17),
(7, 2, 9, 120.00, 'piece', 2025, 2, 0.40, 'hectare', 17),
(8, 2, 54, 280.00, 'kilogram', 2025, 4, 0.60, 'hectare', NULL),
(9, 2, 45, 150.00, 'bunch', 2025, 5, 0.35, 'hectare', NULL),
(10, 3, 10, 750.00, 'piece', 2025, 1, 1.80, 'hectare', 4),
(11, 3, 58, 480.00, 'kilogram', 2025, 3, 1.20, 'hectare', 16),
(12, 3, 57, 520.00, 'kilogram', 2025, 4, 1.40, 'hectare', NULL),
(13, 3, 48, 320.00, 'kilogram', 2025, 2, 0.90, 'hectare', NULL),
(14, 3, 56, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(15, 4, 10, 680.00, 'piece', 2025, 3, 1.60, 'hectare', NULL),
(16, 4, 45, 180.00, 'bunch', 2025, 2, 0.45, 'hectare', NULL),
(17, 5, 8, 1350.00, 'kilogram', 2025, 1, 2.70, 'hectare', 3),
(18, 5, 9, 210.00, 'piece', 2025, 2, 0.55, 'hectare', NULL),
(19, 5, 57, 420.00, 'kilogram', 2025, 4, 1.10, 'hectare', NULL),
(20, 5, 58, 390.00, 'kilogram', 2025, 5, 0.95, 'hectare', NULL),
(21, 6, 56, 310.00, 'kilogram', 2025, 3, 0.80, 'hectare', NULL),
(22, 6, 54, 240.00, 'kilogram', 2025, 2, 0.60, 'hectare', NULL),
(23, 7, 48, 580.00, 'kilogram', 2025, 1, 1.50, 'hectare', 13),
(24, 7, 54, 320.00, 'kilogram', 2025, 2, 0.85, 'hectare', 15),
(25, 7, 45, 280.00, 'bunch', 2025, 3, 0.70, 'hectare', 15),
(26, 8, 8, 980.00, 'kilogram', 2025, 1, 2.20, 'hectare', NULL),
(27, 8, 58, 320.00, 'kilogram', 2025, 4, 0.80, 'hectare', NULL),
(28, 9, 10, 520.00, 'piece', 2025, 3, 1.40, 'hectare', NULL),
(29, 9, 57, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(30, 10, 8, 1120.00, 'kilogram', 2025, 1, 2.40, 'hectare', 8),
(31, 10, 45, 230.00, 'bunch', 2025, 2, 0.60, 'hectare', 8),
(32, 10, 56, 280.00, 'kilogram', 2025, 4, 0.75, 'hectare', NULL),
(33, 11, 58, 340.00, 'kilogram', 2025, 3, 0.85, 'hectare', NULL),
(34, 11, 9, 180.00, 'piece', 2025, 4, 0.50, 'hectare', NULL),
(35, 12, 8, 1080.00, 'kilogram', 2025, 1, 2.30, 'hectare', NULL),
(36, 12, 45, 320.00, 'bunch', 2025, 2, 0.80, 'hectare', 1),
(37, 12, 48, 420.00, 'kilogram', 2025, 3, 1.10, 'hectare', NULL),
(38, 12, 57, 380.00, 'kilogram', 2025, 4, 1.00, 'hectare', NULL),
(39, 12, 54, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(40, 13, 10, 620.00, 'piece', 2025, 3, 1.50, 'hectare', NULL),
(41, 13, 56, 270.00, 'kilogram', 2025, 5, 0.70, 'hectare', NULL),
(42, 14, 58, 390.00, 'kilogram', 2025, 2, 1.00, 'hectare', NULL),
(43, 14, 57, 420.00, 'kilogram', 2025, 4, 1.10, 'hectare', 5),
(44, 14, 45, 180.00, 'bunch', 2025, 5, 0.45, 'hectare', NULL),
(45, 15, 8, 950.00, 'kilogram', 2025, 1, 2.10, 'hectare', NULL),
(46, 15, 9, 160.00, 'piece', 2025, 2, 0.45, 'hectare', NULL),
(47, 15, 48, 380.00, 'kilogram', 2025, 3, 1.00, 'hectare', NULL),
(48, 1, 8, 1250.00, 'kilogram', 2025, 1, 2.50, 'hectare', NULL),
(49, 1, 48, 850.00, 'kilogram', 2025, 2, 1.75, 'hectare', NULL),
(50, 1, 45, 320.00, 'bunch', 2025, 4, 0.80, 'hectare', NULL),
(51, 1, 56, 450.00, 'kilogram', 2025, 4, 1.20, 'hectare', NULL),
(52, 1, 57, 380.00, 'kilogram', 2025, 5, 0.95, 'hectare', NULL),
(53, 2, 8, 950.00, 'kilogram', 2025, 1, 2.10, 'hectare', NULL),
(54, 2, 9, 120.00, 'piece', 2025, 2, 0.40, 'hectare', NULL),
(55, 2, 54, 280.00, 'kilogram', 2025, 4, 0.60, 'hectare', NULL),
(56, 2, 45, 150.00, 'bunch', 2025, 5, 0.35, 'hectare', NULL),
(57, 3, 10, 750.00, 'piece', 2025, 1, 1.80, 'hectare', 16),
(58, 3, 58, 480.00, 'kilogram', 2025, 3, 1.20, 'hectare', NULL),
(59, 3, 57, 520.00, 'kilogram', 2025, 4, 1.40, 'hectare', NULL),
(60, 3, 48, 320.00, 'kilogram', 2025, 2, 0.90, 'hectare', NULL),
(61, 3, 56, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(62, 4, 10, 680.00, 'piece', 2025, 3, 1.60, 'hectare', NULL),
(63, 4, 45, 180.00, 'bunch', 2025, 2, 0.45, 'hectare', NULL),
(64, 4, 8, 850.00, 'kilogram', 2025, 1, 1.80, 'hectare', NULL),
(65, 4, 9, 95.00, 'piece', 2025, 5, 0.35, 'hectare', NULL),
(66, 5, 8, 1350.00, 'kilogram', 2025, 1, 2.70, 'hectare', 3),
(67, 5, 9, 210.00, 'piece', 2025, 2, 0.55, 'hectare', NULL),
(68, 5, 57, 420.00, 'kilogram', 2025, 4, 1.10, 'hectare', NULL),
(69, 5, 58, 390.00, 'kilogram', 2025, 5, 0.95, 'hectare', NULL),
(70, 5, 54, 310.00, 'kilogram', 2025, 3, 0.80, 'hectare', NULL),
(71, 6, 56, 310.00, 'kilogram', 2025, 3, 0.80, 'hectare', NULL),
(72, 6, 54, 240.00, 'kilogram', 2025, 2, 0.60, 'hectare', NULL),
(73, 6, 45, 170.00, 'bunch', 2025, 1, 0.45, 'hectare', NULL),
(74, 6, 57, 360.00, 'kilogram', 2025, 5, 0.90, 'hectare', NULL),
(75, 6, 58, 285.00, 'kilogram', 2025, 4, 0.75, 'hectare', NULL),
(76, 7, 48, 580.00, 'kilogram', 2025, 1, 1.50, 'hectare', NULL),
(77, 7, 54, 320.00, 'kilogram', 2025, 2, 0.85, 'hectare', NULL),
(78, 7, 45, 280.00, 'bunch', 2025, 3, 0.70, 'hectare', NULL),
(79, 7, 10, 430.00, 'piece', 2025, 4, 1.10, 'hectare', NULL),
(80, 7, 56, 210.00, 'kilogram', 2025, 5, 0.60, 'hectare', NULL),
(81, 8, 8, 980.00, 'kilogram', 2025, 1, 2.20, 'hectare', NULL),
(82, 8, 58, 320.00, 'kilogram', 2025, 4, 0.80, 'hectare', NULL),
(83, 8, 54, 260.00, 'kilogram', 2025, 2, 0.70, 'hectare', NULL),
(84, 8, 45, 190.00, 'bunch', 2025, 3, 0.55, 'hectare', NULL),
(85, 8, 57, 340.00, 'kilogram', 2025, 5, 0.90, 'hectare', NULL),
(86, 9, 10, 520.00, 'piece', 2025, 3, 1.40, 'hectare', NULL),
(87, 9, 57, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(88, 9, 8, 890.00, 'kilogram', 2025, 1, 2.00, 'hectare', NULL),
(89, 9, 45, 160.00, 'bunch', 2025, 2, 0.40, 'hectare', NULL),
(90, 9, 56, 270.00, 'kilogram', 2025, 4, 0.70, 'hectare', NULL),
(91, 10, 8, 1120.00, 'kilogram', 2025, 1, 2.40, 'hectare', 8),
(92, 10, 45, 230.00, 'bunch', 2025, 2, 0.60, 'hectare', NULL),
(93, 10, 56, 280.00, 'kilogram', 2025, 4, 0.75, 'hectare', NULL),
(94, 10, 48, 350.00, 'kilogram', 2025, 3, 0.90, 'hectare', NULL),
(95, 10, 10, 490.00, 'piece', 2025, 5, 1.20, 'hectare', NULL),
(96, 11, 58, 340.00, 'kilogram', 2025, 3, 0.85, 'hectare', NULL),
(97, 11, 9, 180.00, 'piece', 2025, 4, 0.50, 'hectare', NULL),
(98, 11, 8, 920.00, 'kilogram', 2025, 1, 2.10, 'hectare', NULL),
(99, 11, 48, 330.00, 'kilogram', 2025, 2, 0.85, 'hectare', NULL),
(100, 11, 57, 275.00, 'kilogram', 2025, 5, 0.70, 'hectare', NULL),
(101, 12, 8, 1080.00, 'kilogram', 2025, 1, 2.30, 'hectare', NULL),
(102, 12, 45, 320.00, 'bunch', 2025, 2, 0.80, 'hectare', 1),
(103, 12, 48, 420.00, 'kilogram', 2025, 3, 1.10, 'hectare', NULL),
(104, 12, 57, 380.00, 'kilogram', 2025, 4, 1.00, 'hectare', NULL),
(105, 12, 54, 290.00, 'kilogram', 2025, 5, 0.75, 'hectare', NULL),
(106, 13, 10, 620.00, 'piece', 2025, 3, 1.50, 'hectare', NULL),
(107, 13, 56, 270.00, 'kilogram', 2025, 5, 0.70, 'hectare', NULL),
(108, 13, 8, 880.00, 'kilogram', 2025, 1, 1.90, 'hectare', NULL),
(109, 13, 45, 210.00, 'bunch', 2025, 2, 0.55, 'hectare', NULL),
(110, 13, 58, 315.00, 'kilogram', 2025, 4, 0.80, 'hectare', NULL),
(111, 14, 58, 390.00, 'kilogram', 2025, 2, 1.00, 'hectare', NULL),
(112, 14, 57, 420.00, 'kilogram', 2025, 4, 1.10, 'hectare', NULL),
(113, 14, 45, 180.00, 'bunch', 2025, 5, 0.45, 'hectare', NULL),
(114, 14, 8, 930.00, 'kilogram', 2025, 1, 2.00, 'hectare', NULL),
(115, 14, 48, 370.00, 'kilogram', 2025, 3, 0.95, 'hectare', NULL),
(116, 15, 8, 950.00, 'kilogram', 2025, 1, 2.10, 'hectare', NULL),
(117, 15, 9, 160.00, 'piece', 2025, 2, 0.45, 'hectare', NULL),
(118, 15, 48, 380.00, 'kilogram', 2025, 3, 1.00, 'hectare', NULL),
(119, 15, 57, 340.00, 'kilogram', 2025, 4, 0.90, 'hectare', NULL),
(120, 15, 56, 260.00, 'kilogram', 2025, 5, 0.70, 'hectare', NULL),
(121, 1, 70, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(122, 1, 69, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(123, 1, 73, 100.00, 'kilogram', 2025, 1, 20.00, 'hectare', NULL),
(124, 1, 72, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(125, 1, 71, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(126, 1, 68, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(127, 1, 64, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(128, 1, 65, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(129, 1, 66, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(131, 1, 46, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(132, 1, 44, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(133, 1, 47, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', NULL),
(134, 1, 62, 0.00, 'kilogram', 2025, 1, 0.00, 'hectare', 6),
(138, 2, 62, 420.00, 'kilogram', 2025, 3, 1.20, 'hectare', NULL),
(139, 7, 62, 380.00, 'kilogram', 2025, 3, 1.00, 'hectare', NULL),
(140, 13, 62, 350.00, 'kilogram', 2025, 3, 0.90, 'hectare', NULL),
(141, 6, 63, 310.00, 'kilogram', 2025, 2, 0.75, 'hectare', 9),
(142, 9, 63, 290.00, 'kilogram', 2025, 2, 0.70, 'hectare', NULL),
(143, 1, 64, 850.00, 'bunch', 2025, 1, 1.70, 'hectare', NULL),
(144, 12, 64, 780.00, 'bunch', 2025, 2, 1.50, 'hectare', 12),
(145, 14, 64, 660.00, 'bunch', 2025, 4, 1.30, 'hectare', NULL),
(146, 4, 65, 280.00, 'kilogram', 2025, 2, 0.60, 'hectare', NULL),
(147, 11, 65, 320.00, 'kilogram', 2025, 3, 0.70, 'hectare', NULL),
(148, 8, 66, 180.00, 'bunch', 2025, 1, 0.40, 'hectare', NULL),
(149, 15, 66, 210.00, 'bunch', 2025, 2, 0.50, 'hectare', 7),
(150, 2, 67, 260.00, 'kilogram', 2025, 3, 0.60, 'hectare', NULL),
(151, 7, 67, 290.00, 'kilogram', 2025, 4, 0.70, 'hectare', NULL),
(152, 5, 68, 650.00, 'kilogram', 2025, 2, 0.90, 'hectare', NULL),
(153, 10, 68, 580.00, 'kilogram', 2025, 3, 0.80, 'hectare', NULL),
(154, 14, 68, 620.00, 'kilogram', 2025, 4, 0.85, 'hectare', 11),
(155, 13, 69, 720.00, 'kilogram', 2025, 3, 1.20, 'hectare', 14),
(156, 15, 69, 690.00, 'kilogram', 2025, 4, 1.10, 'hectare', NULL),
(157, 3, 70, 240.00, 'bundle', 2025, 1, 0.30, 'hectare', NULL),
(158, 8, 70, 260.00, 'bundle', 2025, 2, 0.35, 'hectare', NULL),
(159, 4, 71, 180.00, 'kilogram', 2025, 2, 0.40, 'hectare', NULL),
(160, 9, 71, 210.00, 'kilogram', 2025, 3, 0.50, 'hectare', 10),
(161, 6, 72, 380.00, 'kilogram', 2025, 3, 1.60, 'hectare', NULL),
(162, 11, 72, 420.00, 'kilogram', 2025, 3, 1.80, 'hectare', NULL),
(163, 1, 73, 310.00, 'kilogram', 2025, 2, 1.50, 'hectare', NULL),
(164, 12, 73, 290.00, 'kilogram', 2025, 3, 1.40, 'hectare', NULL),
(165, 7, 44, 220.00, 'bunch', 2025, 2, 0.30, 'hectare', NULL),
(166, 14, 44, 240.00, 'bunch', 2025, 3, 0.35, 'hectare', NULL),
(167, 3, 46, 280.00, 'bunch', 2025, 1, 0.40, 'hectare', NULL),
(168, 10, 46, 310.00, 'bunch', 2025, 2, 0.45, 'hectare', NULL),
(169, 5, 47, 580.00, 'bunch', 2025, 1, 1.20, 'hectare', NULL),
(170, 12, 47, 620.00, 'bunch', 2025, 3, 1.30, 'hectare', NULL),
(171, 8, 49, 390.00, 'kilogram', 2025, 3, 0.95, 'hectare', NULL),
(172, 15, 49, 420.00, 'kilogram', 2025, 3, 1.05, 'hectare', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crop_seasons`
--

CREATE TABLE `crop_seasons` (
  `season_id` int(11) NOT NULL,
  `season_name` varchar(50) NOT NULL,
  `start_month` int(2) NOT NULL,
  `end_month` int(2) NOT NULL,
  `description` text DEFAULT NULL,
  `planting_recommendations` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop_seasons`
--

INSERT INTO `crop_seasons` (`season_id`, `season_name`, `start_month`, `end_month`, `description`, `planting_recommendations`) VALUES
(1, 'Dry Season', 11, 4, 'November to April - Hot and dry period ideal for drought-resistant crops. Average temperature ranges from 26-33°C with minimal rainfall.', 'Best for: Rice (upland varieties), cassava, sweet potato, mung beans, peanuts, and drought-resistant vegetables.'),
(2, 'Wet Season', 5, 10, 'May to October - Rainy period with high humidity, suitable for moisture-loving crops. Average rainfall of 200-400mm per month.', 'Best for: Leafy vegetables, gourds, root crops, rice (lowland varieties), and tropical fruits.'),
(3, 'First Cropping', 11, 2, 'November to February - Early planting season after the wet season, moderate temperatures ideal for leafy vegetables and cool-season crops.', 'Best for: Cabbage, carrots, radish, onions, garlic, and other cool-weather crops.'),
(4, 'Second Cropping', 3, 6, 'March to June - Main growing season with increasing temperatures, ideal for heat-loving crops. Planting should be completed before peak summer.', 'Best for: Tomatoes, eggplant, peppers, okra, corn, and heat-loving fruits.'),
(5, 'Third Cropping', 7, 10, 'July to October - Late season planting during and after heavy rains, good for crops that benefit from high soil moisture and moderate temperatures.', 'Best for: Sweet potatoes, taro, squash, beans, and second rice crop.');

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
  `farm_location` varchar(255) DEFAULT NULL,
  `barangay_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_details`
--

INSERT INTO `farmer_details` (`detail_id`, `user_id`, `farm_name`, `farm_type`, `certifications`, `crop_varieties`, `machinery_used`, `farm_size`, `income`, `farm_location`, `barangay_id`) VALUES
(2, 19, 'Anna\'s Organic Farm Produce', 'Vegetable Farm', '', NULL, NULL, 100.00, NULL, 'Bais', 12),
(3, 20, '', 'Vegetable Farm', '', NULL, NULL, 20.00, NULL, 'Palinpinon', 5),
(4, 41, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3),
(5, 42, 'Santos Organic Farm', 'Mixed Crops', NULL, NULL, NULL, 35.50, NULL, 'Balayagmanok Area', 1),
(6, 43, 'Reyes Family Farm', 'Rice Farm', NULL, NULL, NULL, 28.00, NULL, 'Malabo Highway', 10),
(7, 44, 'Cruz Vegetable Gardens', 'Vegetable Farm', NULL, NULL, NULL, 15.20, NULL, 'Sagbang Area', 14),
(8, 45, 'Gomez Fruit Orchard', 'Fruit Farm', NULL, NULL, NULL, 22.80, NULL, 'Jawa Center', 7);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_fields`
--

CREATE TABLE `farmer_fields` (
  `field_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_size` decimal(10,2) DEFAULT NULL,
  `field_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `coordinates` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_fields`
--

INSERT INTO `farmer_fields` (`field_id`, `farmer_id`, `barangay_id`, `field_name`, `field_size`, `field_type`, `notes`, `coordinates`, `created_at`) VALUES
(1, 19, 12, 'Palinpinon Organic Plot', 3.50, 'Vegetable Farm', 'Main growing area for Malunggay and leafy vegetables', '9.2639,123.2255', '2025-04-01 00:30:00'),
(2, 19, 4, 'Cambucad Fruit Orchard', 2.80, 'Fruit Orchard', 'Citrus fruits growing area, good for Dalandan and Kalamansi', '9.2542,123.2150', '2025-04-02 01:15:00'),
(3, 20, 5, 'Caidiocan Rice Paddy', 5.25, 'Rice Field', 'Main area for Valencia Red Rice production', '9.2715,123.2345', '2025-04-01 02:45:00'),
(4, 20, 3, 'Bongbong Vegetable Garden', 2.40, 'Vegetable Farm', 'Mixed vegetables and herbs including basil', '9.2810,123.2420', '2025-04-03 06:20:00'),
(5, 20, 14, 'Sagbang Highland Plot', 3.75, 'Mixed Crop', 'Cool climate suitable for cabbage and highland vegetables', '9.2530,123.2280', '2025-04-05 03:10:00'),
(6, 42, 1, 'Balayagmanok Main Farm', 12.50, 'Mixed Crop', 'Primary cultivation area for diverse crops', '9.2825,123.2510', '2025-03-15 00:00:00'),
(7, 42, 15, 'West Balabag Herb Garden', 1.80, 'Herb Garden', 'Specialized area for herb cultivation including Tanglad', '9.2680,123.2290', '2025-03-16 07:30:00'),
(8, 43, 10, 'Malabo Rice Terraces', 10.25, 'Rice Field', 'Terraced rice fields with irrigation system', '9.2745,123.2490', '2025-03-19 23:45:00'),
(9, 43, 6, 'Dobdob Fruit Farm', 4.80, 'Fruit Orchard', 'Duhat trees and other fruits', '9.2690,123.2380', '2025-03-22 01:20:00'),
(10, 43, 9, 'Lunga Spice Garden', 2.40, 'Mixed Crop', 'Growing area for turmeric and other spices', '9.2780,123.2460', '2025-03-25 06:15:00'),
(11, 44, 14, 'Sagbang Vegetable Fields', 8.75, 'Vegetable Farm', 'Main vegetable production area', '9.2650,123.2340', '2025-03-10 00:30:00'),
(12, 44, 12, 'Palinpinon Banana Plantation', 4.50, 'Fruit Orchard', 'Dedicated to Lakatan banana production', '9.2720,123.2410', '2025-03-12 02:15:00'),
(13, 45, 7, 'Jawa Fruit Paradise', 9.30, 'Fruit Orchard', 'Primary orchard for fruit varieties', '9.2840,123.2530', '2025-03-05 01:00:00'),
(14, 45, 13, 'Puhagan Root Crop Field', 5.80, 'Root Crop Farm', 'Specialized in root crops like Ube', '9.2760,123.2480', '2025-03-08 03:30:00'),
(15, 45, 7, 'Jawa Vegetable Plots', 4.20, 'Vegetable Farm', 'Secondary growing area for vegetables', '9.2830,123.2520', '2025-03-09 06:45:00'),
(16, 41, 3, 'Bongbong Family Farm', 7.50, 'Mixed Crop', 'Traditional family-owned farming plot', '9.2790,123.2430', '2025-04-09 23:30:00'),
(17, 41, 2, 'Balili Riverside Field', 3.20, 'Vegetable Farm', 'Located near water source for easy irrigation', '9.2670,123.2370', '2025-04-12 01:45:00'),
(18, 19, 4, 'Cambucad Citrus Grove', 2.80, 'Fruit Orchard', 'Citrus fruits growing area, good for Dalandan and Kalamansi', '9.2542,123.2150', '2025-04-02 01:15:00');

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
  `farmer_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `product_id`, `feedback_text`, `rating`, `created_at`, `status`, `farmer_id`, `order_id`) VALUES
(1, 19, NULL, 'The mangoes were incredibly sweet and juicy. Will definitely buy again!', 5, '2025-03-09 16:52:28', 'pending', NULL, NULL),
(2, 19, NULL, 'Fresh calamansi, perfect for my recipes. Very satisfied with the quality.', 4, '2025-03-10 16:52:28', 'pending', NULL, NULL),
(3, 19, 8, 'The rice has a wonderful aroma and cooks perfectly. Excellent quality!', 5, '2025-03-11 16:52:28', 'responded', NULL, NULL),
(4, 19, 9, 'The lemon basil wasn\'t as fresh as I expected. Slightly wilted upon arrival.', 3, '2025-03-12 16:52:28', 'responded', NULL, NULL),
(5, 19, 10, 'These sweet potatoes are amazing! Great flavor and perfect for my stews.', 5, '2025-03-13 16:52:28', 'pending', NULL, NULL),
(6, 20, NULL, 'The mangos from Palinpinon are good, but I\'ve had better. A bit overripe.', 3, '2025-03-14 16:52:28', 'pending', NULL, NULL),
(7, 20, NULL, 'Fresh kalamunggay, great for soup. Very green and crisp.', 4, '2025-03-15 16:52:28', 'responded', NULL, NULL),
(8, 20, NULL, 'The kangkong was very fresh and clean. Good value for money.', 5, '2025-03-16 16:52:28', 'pending', NULL, NULL),
(9, 20, NULL, 'Kamatis was a bit too ripe for my liking, but still usable.', 3, '2025-03-17 16:52:28', 'pending', NULL, NULL),
(10, 19, NULL, 'Overall, I love shopping at the Farmers Market. Great selection of fresh produce!', 5, '2025-03-18 16:52:28', 'responded', NULL, NULL),
(11, 20, NULL, 'The website is user-friendly, but checkout could be improved.', 4, '2025-03-19 16:52:28', 'pending', NULL, NULL),
(12, 19, NULL, 'Delivery was faster than expected. Products were well-packaged.', 5, '2025-03-20 16:52:28', 'pending', NULL, NULL),
(13, 20, NULL, 'Some products were missing from my order. Please improve accuracy.', 2, '2025-03-21 16:52:28', 'responded', NULL, NULL);

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
(6, 19, 'Good news! Your product \"Malunggay (Moringa)\" has been approved and is now available in the marketplace.', 0, '2025-04-23 02:22:34', 'product_approved', 45),
(7, 19, 'Good news! Your product \"Kalamansi\" has been approved and is now available in the marketplace.', 0, '2025-05-02 08:16:01', 'product_approved', 53);

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
(5, 2, NULL, 1, 50.00),
(6, 3, 45, 1, 10.00),
(7, 4, 48, 1, 60.00),
(8, 5, 45, 1, 10.00),
(9, 5, 48, 1, 60.00),
(10, 5, 54, 1, 60.00),
(11, 6, 58, 1, 45.04),
(12, 6, NULL, 1, 15.00),
(13, 6, NULL, 1, 60.00),
(14, 6, 56, 1, 35.00),
(15, 6, 57, 1, 40.00),
(16, 7, 48, 1, 60.00),
(17, 7, 56, 1, 35.00),
(18, 7, 57, 1, 40.00),
(19, 7, 58, 1, 45.04),
(20, 7, NULL, 1, 15.00),
(21, 8, 58, 1, 45.04),
(22, 8, 45, 1, 10.00),
(23, 8, 48, 1, 60.00),
(24, 8, 54, 1, 60.00),
(25, 8, NULL, 2, 50.00),
(26, 9, 9, 1, 60.00),
(27, 10, 45, 1, 10.00),
(28, 10, 54, 1, 60.00),
(29, 10, NULL, 1, 50.00),
(30, 11, 45, 1, 10.00),
(31, 11, 48, 1, 60.00),
(32, 11, 54, 1, 60.00),
(33, 11, 56, 1, 35.00);

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
(7, 38, 'pending', '2025-04-27 09:13:35', 'Municipal Agriculture Office'),
(8, 40, 'pending', '2025-04-27 19:49:00', 'Municipal Agriculture Office'),
(9, 40, 'pending', '2025-04-29 04:29:02', 'Municipal Agriculture Office'),
(10, 40, 'pending', '2025-04-29 09:41:02', 'Municipal Agriculture Office'),
(11, 40, 'pending', '2025-04-29 13:03:14', 'Municipal Agriculture Office');

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
(9, 7, 'cash_on_pickup', 4, 'pending', '2025-04-27 09:13:38', 38, 195.00, 'CP-7-20250427-5121', NULL, 0, NULL),
(10, 8, 'cash_on_pickup', 4, 'pending', '2025-04-27 19:49:03', 40, 275.00, 'CP-8-20250427-1AAE', NULL, 0, NULL),
(11, 9, 'cash_on_pickup', 4, 'pending', '2025-04-29 04:29:04', 40, 60.00, 'CP-9-20250429-0712', NULL, 0, NULL),
(12, 10, 'cash_on_pickup', 4, 'pending', '2025-04-29 09:41:04', 40, 120.00, 'CP-10-20250429-2CD3', NULL, 0, NULL),
(13, 11, 'cash_on_pickup', 4, 'pending', '2025-04-29 13:03:18', 40, 165.00, 'CP-11-20250429-46DE', NULL, 0, NULL);

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
(9, 9, 'pending', NULL, '2025-04-27 09:13:38'),
(10, 10, 'pending', NULL, '2025-04-27 19:49:03'),
(11, 11, 'pending', NULL, '2025-04-29 04:29:04'),
(12, 12, 'pending', NULL, '2025-04-29 09:41:05'),
(13, 13, 'pending', NULL, '2025-04-29 13:03:18');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `pickup_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `pickup_status` enum('pending','assigned','ready','completed','cancelled','canceled') DEFAULT 'pending',
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
(7, 7, 9, 'pending', '2025-05-10 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(8, 8, 10, 'pending', '2025-04-28 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(9, 9, 11, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(10, 10, 12, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL),
(11, 11, 13, 'pending', '2025-04-30 00:00:00', 'Municipal Agriculture Office', 'Municipal Agriculture Office', 'Municipal Agriculture Office', NULL);

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
(8, 3),
(9, 4),
(9, 14),
(10, 5),
(10, 15),
(45, 6),
(45, 18),
(48, 17),
(48, 26),
(53, 1),
(53, 13),
(54, 1),
(54, 13),
(56, 2),
(57, 2),
(58, 2),
(58, 5),
(62, 1),
(62, 17),
(63, 1),
(63, 17),
(64, 1),
(64, 20),
(65, 2),
(65, 27),
(66, 6),
(66, 12),
(67, 16),
(67, 27),
(68, 5),
(68, 15),
(69, 5),
(69, 15),
(70, 4),
(70, 22),
(71, 18),
(71, 22),
(72, 21),
(73, 21);

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
(8, 'Valencia Red Rice', 'Organic red rice grown in the highlands of Ayungon. Rich in antioxidants with a nutty flavor and slightly chewy texture. Harvested using traditional methods by local farmers. Grown by local farmers in Valencia, Negros Oriental.', 85.00, 20, 'approved', '2024-12-12 06:45:15', '2025-05-02 10:06:40', 'uploads/products/680a3d2670179_rice.png', 350, 'kilogram'),
(9, 'Lemon Basil (Sangig)', 'Locally grown aromatic lemon basil, perfect for salads, teas, and Filipino dishes. The leaves have a strong citrus scent and distinctive flavor that enhances both savory and sweet recipes. Grown by local farmers in Valencia, Negros Oriental.', 15.00, 20, 'approved', '2024-12-12 06:45:15', '2025-05-02 10:05:33', NULL, 100, 'bunch'),
(10, 'Valencia Purple Sweet Potatoes (Kamote)', 'Nutrient-rich purple sweet potatoes grown in the volcanic soil of Valencia. These purple-fleshed varieties have higher antioxidant content than regular varieties, with a sweet flavor perfect for both savory dishes and desserts.', 45.00, 20, 'approved', '2024-12-12 06:45:15', '2025-05-02 10:05:33', 'uploads/products/67ffb25bb3be3_IMG_0041.JPG', 180, 'kilogram'),
(44, 'Kangkong (Water Spinach)', 'Fresh water spinach harvested from clean water sources. Great for stir-fry dishes.', 25.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 80, 'bunch'),
(45, 'Fresh Malunggay (Moringa)', 'Highly nutritious moringa leaves harvested from Palinpinon farms. Known locally as the \"miracle tree\" due to its exceptional nutrient profile. Perfect for soups, stews, and as a health supplement. Grown by local farmers in Valencia, Negros Oriental.', 12.50, 19, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:05:33', 'uploads/products/67ff925ccd2bf_kalamunggay.png', 250, 'bunch'),
(46, 'Pechay (Bok Choy)', 'Crisp and fresh bok choy. Excellent for stir-fry and soups.', 30.00, 19, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 90, 'bunch'),
(47, 'Saging Saba (Cooking Banana)', 'Traditional cooking bananas. Perfect for turon and other Filipino desserts.', 50.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 100, 'bunch'),
(48, 'Balili Santol (Cotton Fruit)', 'Sweet and tangy santol fruits from the orchards of Balili. These medium-sized fruits have a perfect balance of sweet and sour flavors. The white pulp can be eaten fresh or made into preserves and candies. Grown by local farmers in Valencia, Negros Oriental.', 75.00, 20, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:05:33', 'uploads/products/680b5ccc98dd2_santol.png', 120, 'kilogram'),
(49, 'Lanzones', 'Sweet and fragrant lanzones from Camiguin. Limited seasonal availability.', 120.00, 20, 'pending', '2025-04-09 15:39:55', '2025-04-09 15:39:55', NULL, 30, 'kilogram'),
(53, 'Organic Kalamansi (Philippine Lime)', 'Small, fragrant citrus fruits essential to Filipino cuisine. These organically grown kalamansi from Valencia are more flavorful than commercial varieties. Used for juices, marinades, and as a natural cleaning agent.', 60.00, 19, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:05:33', 'uploads/products/67f92d14676cb_calamansi.png', 200, 'kilogram'),
(54, 'Cambucad Dalandan (Philippine Orange)', 'Sweet and juicy local oranges harvested from Cambucad area. These green-skinned citrus fruits have a refreshing sweet-tart flavor, more juice content and thinner skin than imported varieties. Perfect for fresh juice. Grown by local farmers in Valencia, Negros Oriental.', 80.00, 19, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:05:33', NULL, 150, 'kilogram'),
(56, 'Highland Sayote (Chayote)', 'Crisp, pale green squash grown in the cooler highland regions of Valencia. These versatile vegetables have a mild flavor that absorbs the taste of whatever they&#039;re cooked with. Popular in soups, stir-fries, and Filipino vegetable dishes.', 35.00, 20, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:07:08', 'uploads/products/680ae57d79969_sayote.png', 230, 'kilogram'),
(57, 'Mountain-Grown Repolyo (Cabbage)', 'Fresh, compact cabbage heads grown in the cool mountain farms of Sagbang. These cabbages have tightly packed, crisp leaves perfect for salads, soups, and traditional Filipino dishes like lumpia and pancit. Grown by local farmers in Valencia, Negros Oriental.', 50.00, 20, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:07:02', 'uploads/products/680ae4e249743_cabbage.png', 180, 'kilogram'),
(58, 'Organic Carrots', 'Sweet, crunchy carrots organically grown in nutrient-rich soil from the highland farms of Jawa. These bright orange root vegetables have exceptional flavor and are perfect for soups, stews, and salads. Grown by local farmers in Valencia, Negros Oriental.', 65.00, 20, 'approved', '2025-04-09 15:39:55', '2025-05-02 10:05:33', 'uploads/products/67ff9240ab00e_67f9cdf83a9bf_carrots.png', 210, 'kilogram'),
(62, 'Lanzones (Lansium)', 'Sweet and juicy lanzones grown in the highlands of Valencia. These yellow-brown skinned fruits have translucent, sweet flesh arranged in segments. Seasonally available during late summer to early fall.', 120.00, 42, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 80, 'kilogram'),
(63, 'Duhat (Java Plum)', 'Dark purple to black berries with sweet-tart flesh harvested from trees in Dobdob. Rich in antioxidants and has cooling properties according to traditional medicine. Available seasonally from May to July.', 90.00, 43, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 60, 'kilogram'),
(64, 'Native Lakatan Banana', 'Sweet, aromatic Lakatan bananas grown in Palinpinon. These golden yellow bananas have firmer flesh than regular varieties with a distinct sweet flavor and aroma. Harvested at optimal ripeness.', 65.00, 44, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 150, 'bunch'),
(65, 'Tagabang (Winged Bean)', 'Locally grown winged beans with distinctive four-angled edges. The entire plant is edible - young pods, mature seeds, shoots, flowers, and tubers. Rich in protein and commonly used in local dishes.', 40.00, 45, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 85, 'kilogram'),
(66, 'Alugbati (Malabar Spinach)', 'Glossy, thick leaves with a mild flavor harvested from vines in West Balabag. This heat-loving green vegetable is rich in vitamins and minerals. Used in soups, stir-fries, and blanched as a side dish.', 30.00, 42, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 120, 'bunch'),
(67, 'Bataw (Hyacinth Bean)', 'Purple-tinged flat bean pods grown in Balili area. Young pods are tender and delicious while mature seeds can be dried and used in soups and stews. A traditional vegetable in local cuisine.', 35.00, 43, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 90, 'kilogram'),
(68, 'Gabi (Taro)', 'Starchy taro corms with nutty flavor harvested in Lunga. The underground corm has brown skin and white to lavender flesh. Used in both savory dishes and desserts like ginataang gabi.', 55.00, 44, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 100, 'kilogram'),
(69, 'Ube (Purple Yam)', 'Vibrant purple yams grown in volcanic soil of Puhagan. These root crops have an intensely sweet, nutty flavor and vivid purple color. Perfect for traditional Filipino desserts and pastries.', 95.00, 45, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 75, 'kilogram'),
(70, 'Tanglad (Lemongrass)', 'Aromatic lemongrass stalks from Liptong farms. This fragrant herb has a subtle citrus flavor and is used in teas, soups, and as a flavoring for rice and meat dishes. Also valued for medicinal properties.', 15.00, 42, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 200, 'bundle'),
(71, 'Luyang Dilaw (Turmeric)', 'Fresh turmeric rhizomes with bright orange flesh grown in Caidiocan. This aromatic spice has earthy, peppery flavor and powerful anti-inflammatory properties. Used in cooking and traditional medicine.', 70.00, 43, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 60, 'kilogram'),
(72, 'Valencia Arabica Coffee Beans', 'Shade-grown Arabica coffee beans from the highlands of Valencia. These carefully processed beans have complex flavor notes of chocolate, citrus, and caramel. Grown at higher elevations for superior quality.', 350.00, 44, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 40, 'kilogram'),
(73, 'Balayagmanok Cacao Beans', 'Fermented and dried cacao beans from Balayagmanok farms. These premium beans have rich chocolate flavor with fruity notes. Perfect for making artisanal chocolate or traditional tablea for hot chocolate.', 280.00, 45, 'approved', '2025-05-02 10:05:33', '2025-05-02 10:05:33', NULL, 55, 'kilogram');

-- --------------------------------------------------------

--
-- Table structure for table `product_seasons`
--

CREATE TABLE `product_seasons` (
  `product_season_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `yield_estimate` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(20, 'david_moorer', 'davidPass654', 'david.moore@example.com', 2, '2024-12-11 15:15:06', '2025-05-03 05:29:58', 'david', 'moore', '012391203', 'Lunga\r\n'),
(21, 'kevin_chris', '$2y$10$1vtHXjlZN4nMC5rFGL3pMOuSM2BhFBEAX5w6wnUn.TtcSo5RSlkjy', 'kchris.kd@gmail.com', 3, '2025-02-02 16:10:36', '2025-02-04 10:38:23', 'kevin', 'Durango', '098123447812', 'Sibulan\r\n'),
(23, 'angeline', '$2y$10$OFmOhK20MAY5iBnyjcKgZul90bB5cvvdJD46VYbV41lVEvjY1kTHq', 'angeline@gmail.com', 4, '2025-02-04 10:39:34', '2025-02-18 08:50:18', 'angeline', 'largo', '09123124512', 'Bolocboloc\r\n'),
(24, 'John', '$2y$10$jDhBWKeR6keXyrSR3Z6LguaAep4isunG0JqiTPiFV//BYN/O6K.KS', 'John@gmail.com', 5, '2025-03-11 07:36:36', '2025-03-11 07:36:36', 'John', 'Grime', '09987890987', 'Texas\r\n'),
(25, 'Kyle', '$2y$10$DE8HdGyIu4xMzYY3B2UET.EY0rWHZKI8hapj7.PRqhX5rFFCLxdjy', 'kyle@gmail.com', 3, '2025-03-18 03:52:45', '2025-03-18 03:52:59', 'kyle', 'wrymyr', '09312038401', 'Basay\r\n'),
(34, 'chris.doe@example.com', '$2y$10$PTOWxTJS3mBdsUWzwjPshuNwZh1nQOrRi3u1xUNLPNf7baF4JbgXe', 'chris.doe@example.com', 1, '2025-03-27 12:44:52', '2025-03-27 12:44:52', 'Chris', 'Doe', NULL, NULL),
(35, 'johndoe@example.com', '$2y$10$/YPt38XfM8xFwVuTyx4iAOeNGayBNsZKHGT8KKhgeTfAT7YsadsHe', 'johndoe@example.com', 1, '2025-03-28 12:35:07', '2025-03-28 12:35:07', 'John', 'Doe', '1234567890', '123 Main St'),
(36, 'kevin@gmail.com', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'kevin@gmail.com', 1, '2025-03-28 13:06:07', '2025-03-28 13:06:07', 'Kevin', 'Durango', '9516063243', 'Sibulan'),
(37, 'remie', '$2y$10$e/.3ZsV7yaOYxLLVuRcr3.lGzSj7GnRtIlBP3XpaZ6NE3JxLlah3m', 'remie@gmail.com', 1, '2025-04-01 16:46:47', '2025-04-01 16:46:47', 'Remie', '', '9516063243', 'Sibulan'),
(38, 'fely', '$2y$10$XaU33js.Z8TvagPHeaeTzOVzh7yu/.vzFCiBCUIoNxA9pzAaQqsjm', 'fely@gmail.com', 1, '2025-04-07 07:48:24', '2025-04-27 19:18:41', 'Fely', 'Durango', '92629595995', 'Mabinay'),
(39, 'Alina', '$2y$10$5DCd0RGpjnwwxxqDv1sKte81JxzR8aE0.FsPaFIeH4p7PLR/mfxvO', 'Alina@gmail.com', 3, '2025-04-17 04:47:47', '2025-04-17 04:47:47', 'alina', 'thea', '0978877872910', 'Bacong'),
(40, 'largoangelinegrime', '$2y$10$RkHuMaRi2QcAn.MFSZg9eeSh7yYikahk1xPvjiYzpo9HsYKMN3u42', 'largoangelinegrime@gmail.com', 1, '2025-04-25 08:25:55', '2025-04-29 07:05:47', 'Angeline', 'Largo', '9516063243', 'Sibulan'),
(41, 'federico', '$2y$10$Tg1YwwNjSjbrh67mqubKQukp7mBM3govzfRsEF6SluEgf0RQXENR6', 'federico@gmail.com', 2, '2025-04-28 15:14:09', '2025-05-03 05:30:11', 'Federico', 'Genciana', '09658892263', 'Balili\r\n'),
(42, 'maria_santos', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'maria.santos@example.com', 2, '2025-05-02 02:13:08', '2025-05-02 02:13:08', 'Maria', 'Santos', '09123456789', 'Valencia'),
(43, 'pedro_reyes', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'pedro.reyes@example.com', 2, '2025-05-02 02:13:08', '2025-05-02 02:13:08', 'Pedro', 'Reyes', '09234567890', 'Valencia'),
(44, 'juan_dela_cruz', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'juan.cruz@example.com', 2, '2025-05-02 02:13:08', '2025-05-02 02:13:08', 'Juan', 'Dela Cruz', '09345678901', 'Valencia'),
(45, 'teresa_gomez', '$2y$10$YojLIxMejrv/2ahwh48qcuVku5DciDYko1mhClXG9/YOQcJ43nSoS', 'teresa.gomez@example.com', 2, '2025-05-02 02:13:08', '2025-05-02 02:13:08', 'Teresa', 'Gomez', '09456789012', 'Valencia');

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

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_crops_per_barangay`
-- (See below for the actual view)
--
CREATE TABLE `view_crops_per_barangay` (
`barangay_id` int(11)
,`barangay_name` varchar(100)
,`product_id` int(11)
,`product_name` varchar(100)
,`category_name` varchar(50)
,`production_instances` bigint(21)
,`total_production` decimal(32,2)
,`production_unit` varchar(20)
,`total_planted_area` decimal(32,2)
,`area_unit` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_farmers_per_barangay`
-- (See below for the actual view)
--
CREATE TABLE `view_farmers_per_barangay` (
`barangay_id` int(11)
,`barangay_name` varchar(100)
,`farmer_count` bigint(21)
,`total_farm_area` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_seasonal_crops`
-- (See below for the actual view)
--
CREATE TABLE `view_seasonal_crops` (
`barangay_name` varchar(100)
,`product_name` varchar(100)
,`season_name` varchar(50)
,`start_month` int(2)
,`end_month` int(2)
,`total_production` decimal(32,2)
,`production_unit` varchar(20)
,`total_planted_area` decimal(32,2)
,`area_unit` varchar(20)
);

-- --------------------------------------------------------

--
-- Structure for view `view_crops_per_barangay`
--
DROP TABLE IF EXISTS `view_crops_per_barangay`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_crops_per_barangay`  AS SELECT `b`.`barangay_id` AS `barangay_id`, `b`.`barangay_name` AS `barangay_name`, `p`.`product_id` AS `product_id`, `p`.`name` AS `product_name`, `pc`.`category_name` AS `category_name`, count(distinct `bp`.`id`) AS `production_instances`, sum(`bp`.`estimated_production`) AS `total_production`, `bp`.`production_unit` AS `production_unit`, sum(`bp`.`planted_area`) AS `total_planted_area`, `bp`.`area_unit` AS `area_unit` FROM ((((`barangays` `b` join `barangay_products` `bp` on(`b`.`barangay_id` = `bp`.`barangay_id`)) join `products` `p` on(`bp`.`product_id` = `p`.`product_id`)) left join `productcategorymapping` `pcm` on(`p`.`product_id` = `pcm`.`product_id`)) left join `productcategories` `pc` on(`pcm`.`category_id` = `pc`.`category_id`)) GROUP BY `b`.`barangay_id`, `b`.`barangay_name`, `p`.`product_id`, `p`.`name`, `pc`.`category_name`, `bp`.`production_unit`, `bp`.`area_unit` ;

-- --------------------------------------------------------

--
-- Structure for view `view_farmers_per_barangay`
--
DROP TABLE IF EXISTS `view_farmers_per_barangay`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_farmers_per_barangay`  AS SELECT `b`.`barangay_id` AS `barangay_id`, `b`.`barangay_name` AS `barangay_name`, count(`fd`.`user_id`) AS `farmer_count`, sum(`fd`.`farm_size`) AS `total_farm_area` FROM ((`barangays` `b` left join `farmer_details` `fd` on(`b`.`barangay_id` = `fd`.`barangay_id`)) left join `users` `u` on(`fd`.`user_id` = `u`.`user_id` and `u`.`role_id` = 2)) GROUP BY `b`.`barangay_id`, `b`.`barangay_name` ;

-- --------------------------------------------------------

--
-- Structure for view `view_seasonal_crops`
--
DROP TABLE IF EXISTS `view_seasonal_crops`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_seasonal_crops`  AS SELECT `b`.`barangay_name` AS `barangay_name`, `p`.`name` AS `product_name`, `cs`.`season_name` AS `season_name`, `cs`.`start_month` AS `start_month`, `cs`.`end_month` AS `end_month`, sum(`bp`.`estimated_production`) AS `total_production`, `bp`.`production_unit` AS `production_unit`, sum(`bp`.`planted_area`) AS `total_planted_area`, `bp`.`area_unit` AS `area_unit` FROM (((`barangay_products` `bp` join `barangays` `b` on(`bp`.`barangay_id` = `b`.`barangay_id`)) join `products` `p` on(`bp`.`product_id` = `p`.`product_id`)) join `crop_seasons` `cs` on(`bp`.`season_id` = `cs`.`season_id`)) GROUP BY `b`.`barangay_name`, `p`.`name`, `cs`.`season_name`, `cs`.`start_month`, `cs`.`end_month`, `bp`.`production_unit`, `bp`.`area_unit` ORDER BY `b`.`barangay_name` ASC, `cs`.`start_month` ASC ;

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
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`);

--
-- Indexes for table `barangay_products`
--
ALTER TABLE `barangay_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barangay_id` (`barangay_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `fk_barangay_products_field` (`field_id`);

--
-- Indexes for table `crop_seasons`
--
ALTER TABLE `crop_seasons`
  ADD PRIMARY KEY (`season_id`);

--
-- Indexes for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_farmer_barangay` (`barangay_id`);

--
-- Indexes for table `farmer_fields`
--
ALTER TABLE `farmer_fields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_feedback_farmer` (`farmer_id`),
  ADD KEY `fk_feedback_order` (`order_id`);

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
-- Indexes for table `product_seasons`
--
ALTER TABLE `product_seasons`
  ADD PRIMARY KEY (`product_season_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `season_id` (`season_id`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audittrail`
--
ALTER TABLE `audittrail`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `barangay_products`
--
ALTER TABLE `barangay_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `crop_seasons`
--
ALTER TABLE `crop_seasons`
  MODIFY `season_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `farmer_details`
--
ALTER TABLE `farmer_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `farmer_fields`
--
ALTER TABLE `farmer_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `productcategories`
--
ALTER TABLE `productcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `product_seasons`
--
ALTER TABLE `product_seasons`
  MODIFY `product_season_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
-- Constraints for table `barangay_products`
--
ALTER TABLE `barangay_products`
  ADD CONSTRAINT `fk_barangay_products_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barangay_products_field` FOREIGN KEY (`field_id`) REFERENCES `farmer_fields` (`field_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_barangay_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barangay_products_season` FOREIGN KEY (`season_id`) REFERENCES `crop_seasons` (`season_id`) ON DELETE SET NULL;

--
-- Constraints for table `farmer_details`
--
ALTER TABLE `farmer_details`
  ADD CONSTRAINT `farmer_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_farmer_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`);

--
-- Constraints for table `farmer_fields`
--
ALTER TABLE `farmer_fields`
  ADD CONSTRAINT `fk_farmer_fields_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_farmer_fields_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_feedback_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
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
-- Constraints for table `product_seasons`
--
ALTER TABLE `product_seasons`
  ADD CONSTRAINT `fk_product_seasons_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_seasons_season` FOREIGN KEY (`season_id`) REFERENCES `crop_seasons` (`season_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
