-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2026 at 09:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `freehub`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `content_type` enum('image','html','banner') NOT NULL DEFAULT 'image',
  `content` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `target_url` varchar(500) DEFAULT NULL,
  `placement` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `device_target` enum('all','desktop','mobile') NOT NULL DEFAULT 'all',
  `ad_width` smallint(5) UNSIGNED DEFAULT NULL,
  `ad_height` smallint(5) UNSIGNED DEFAULT NULL,
  `position_after` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `impressions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `clicks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `title`, `content_type`, `content`, `image_url`, `target_url`, `placement`, `device_target`, `ad_width`, `ad_height`, `position_after`, `impressions`, `clicks`, `is_active`, `start_date`, `end_date`, `sort_order`, `created_at`) VALUES
(7, 'Ad-1', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'17261656843adbea9a41e9a6f81ccb5b\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 60,\r\n    \'width\' : 468,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/17261656843adbea9a41e9a6f81ccb5b/invoke.js\"></script>', NULL, '', 0, 'all', NULL, NULL, 1, 56, 0, 1, NULL, NULL, 0, '2026-05-26 21:04:20'),
(8, 'Ad-2', 'html', '<script async=\"async\" data-cfasync=\"false\" src=\"https://pl29559323.effectivecpmnetwork.com/9de934434d18248755cac0727e30a400/invoke.js\"></script>\r\n<div id=\"container-9de934434d18248755cac0727e30a400\"></div>', NULL, '', 0, 'all', NULL, NULL, 1, 331, 0, 1, NULL, NULL, 0, '2026-05-27 02:45:47'),
(9, 'Ad-3 = tanding for mobile', 'html', '<script async=\"async\" data-cfasync=\"false\" src=\"https://pl29559323.effectivecpmnetwork.com/9de934434d18248755cac0727e30a400/invoke.js\"></script>\r\n<div id=\"container-9de934434d18248755cac0727e30a400\"></div>', NULL, '', 0, 'all', NULL, NULL, 1, 164, 0, 1, NULL, NULL, 0, '2026-05-27 03:43:15'),
(10, 'Home Page Mobile Top Banner', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'5c61c3b77f68bf88bd17de600ee33bd8\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 50,\r\n    \'width\' : 320,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/5c61c3b77f68bf88bd17de600ee33bd8/invoke.js\"></script>', NULL, '', 0, 'all', 350, 58, 1, 262, 0, 1, NULL, NULL, 0, '2026-05-27 16:38:13'),
(11, 'Below Player', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'17261656843adbea9a41e9a6f81ccb5b\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 60,\r\n    \'width\' : 468,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/17261656843adbea9a41e9a6f81ccb5b/invoke.js\"></script>', NULL, '', 0, 'all', NULL, NULL, 1, 25, 0, 1, NULL, NULL, 0, '2026-05-28 01:05:38'),
(12, 'watch below player desktop', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'bd08f84111ff3eae661131191df1f8fe\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 90,\r\n    \'width\' : 728,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/bd08f84111ff3eae661131191df1f8fe/invoke.js\"></script>', NULL, '', 0, 'desktop', NULL, NULL, 1, 14, 0, 1, NULL, NULL, 0, '2026-05-28 14:59:20'),
(13, 'up to next', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'d666207656165251edfbc93686230eb6\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 250,\r\n    \'width\' : 300,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/d666207656165251edfbc93686230eb6/invoke.js\"></script>', NULL, '', 0, 'all', NULL, NULL, 1, 13, 0, 1, NULL, NULL, 0, '2026-05-28 15:21:00'),
(14, 'footer', 'html', '<script>\r\n  atOptions = {\r\n    \'key\' : \'bd08f84111ff3eae661131191df1f8fe\',\r\n    \'format\' : \'iframe\',\r\n    \'height\' : 90,\r\n    \'width\' : 728,\r\n    \'params\' : {}\r\n  };\r\n</script>\r\n<script src=\"https://www.highperformanceformat.com/bd08f84111ff3eae661131191df1f8fe/invoke.js\"></script>', NULL, '', 0, 'all', NULL, NULL, 1, 10, 0, 1, NULL, NULL, 0, '2026-05-28 16:35:15');

-- --------------------------------------------------------

--
-- Table structure for table `ad_logs`
--

CREATE TABLE `ad_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED DEFAULT NULL,
  `viewer_id` int(10) UNSIGNED DEFAULT NULL,
  `creator_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('impression','click') NOT NULL,
  `ip_hash` varchar(64) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `earnings_viewer` decimal(12,6) DEFAULT 0.000000,
  `earnings_creator` decimal(12,6) DEFAULT 0.000000,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_logs`
--

INSERT INTO `ad_logs` (`id`, `ad_id`, `video_id`, `viewer_id`, `creator_id`, `type`, `ip_hash`, `user_agent`, `earnings_viewer`, `earnings_creator`, `created_at`) VALUES
(1, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-27 20:57:02'),
(2, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-27 20:57:02'),
(3, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-27 20:57:02'),
(4, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000500, 0.001000, '2026-05-27 21:07:59'),
(5, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-27 21:08:51'),
(6, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-27 21:08:51'),
(7, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 00:01:14'),
(8, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 00:01:14'),
(9, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 00:01:14'),
(10, 10, 21, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000100, 0.000500, '2026-05-28 00:13:28'),
(11, 7, 21, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000100, 0.000500, '2026-05-28 00:13:29'),
(12, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000000, 0.000000, '2026-05-28 00:13:33'),
(13, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000000, 0.000000, '2026-05-28 00:13:33'),
(14, 10, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000000, 0.000000, '2026-05-28 00:23:39'),
(15, 8, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000000, 0.000000, '2026-05-28 00:23:39'),
(16, 9, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000000, 0.000000, '2026-05-28 00:23:39'),
(17, 10, 20, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 00:34:04'),
(18, 8, 20, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 00:35:03'),
(19, 9, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 00:42:26'),
(20, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 00:44:11'),
(21, 8, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 00:47:07'),
(22, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 00:54:24'),
(23, 8, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:01:08'),
(24, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:05:11'),
(25, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:06:15'),
(26, 8, 22, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', 0.000100, 0.000500, '2026-05-28 01:11:09'),
(27, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 0.000000, 0.000000, '2026-05-28 01:12:24'),
(28, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:15:21'),
(29, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:19:41'),
(30, 8, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:22:38'),
(31, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 01:24:01'),
(32, 10, 21, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:25:25'),
(33, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 01:32:58'),
(34, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 01:36:11'),
(35, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 01:38:30'),
(36, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:38:38'),
(37, 8, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:44:03'),
(38, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 01:46:23'),
(39, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:49:29'),
(40, 8, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 01:55:30'),
(41, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 01:56:34'),
(42, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 01:57:32'),
(43, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 02:01:52'),
(44, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 02:17:17'),
(45, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 02:17:19'),
(46, 8, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 02:20:31'),
(47, 9, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 02:20:31'),
(48, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 03:30:44'),
(49, 11, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 03:31:10'),
(50, 8, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 03:31:15'),
(51, 9, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 0.000000, 0.000000, '2026-05-28 03:38:57'),
(52, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 03:40:54'),
(53, 11, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 03:41:31'),
(54, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 03:41:45'),
(55, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:14:07'),
(56, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:14:07'),
(57, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:14:07'),
(58, 11, 20, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 05:16:13'),
(59, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:30:45'),
(60, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:30:45'),
(61, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:30:45'),
(62, 11, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 05:32:47'),
(63, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 05:40:58'),
(64, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:41:09'),
(65, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 05:41:09'),
(66, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 05:51:08'),
(67, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:01:18'),
(68, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 06:04:42'),
(69, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 06:04:42'),
(70, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:11:28'),
(71, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:21:38'),
(72, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:38:55'),
(73, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:49:05'),
(74, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 06:59:15'),
(75, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 07:09:29'),
(76, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 07:19:40'),
(77, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 0.000000, 0.000000, '2026-05-28 07:29:50'),
(78, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 14:53:53'),
(79, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 14:53:53'),
(80, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 14:53:53'),
(81, 11, 23, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 14:54:00'),
(82, 12, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:00:54'),
(83, 10, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:04:23'),
(84, 11, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:04:23'),
(85, 10, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:16:48'),
(86, 12, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:16:48'),
(87, 11, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:16:48'),
(88, 13, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:22:52'),
(89, 10, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:27:41'),
(90, 11, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:30:30'),
(91, 12, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:30:30'),
(92, 13, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:33:14'),
(93, 8, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:33:34'),
(94, 10, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:43:17'),
(95, 11, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:43:17'),
(96, 12, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:43:18'),
(97, 13, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:43:18'),
(98, 8, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:43:56'),
(99, 10, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:54:34'),
(100, 11, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:54:34'),
(101, 12, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:54:34'),
(102, 13, 22, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 15:54:34'),
(103, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:07:00'),
(104, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:07:33'),
(105, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:07:33'),
(106, 12, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:12:06'),
(107, 11, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:12:06'),
(108, 13, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:12:06'),
(109, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:17:33'),
(110, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:21:32'),
(111, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:21:32'),
(112, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:26:32'),
(113, 12, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:26:32'),
(114, 13, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:26:32'),
(115, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:30:47'),
(116, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:32:29'),
(117, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:32:29'),
(118, 14, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:35:44'),
(119, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:40:55'),
(120, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:42:33'),
(121, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:42:33'),
(122, 14, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:46:02'),
(123, 11, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:46:19'),
(124, 12, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:46:19'),
(125, 13, 20, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:46:19'),
(126, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:54:41'),
(127, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:54:41'),
(128, 10, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:54:41'),
(129, 14, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 16:56:29'),
(130, 11, 19, 11, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:58:40'),
(131, 12, 19, 11, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:58:41'),
(132, 13, 19, 11, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 16:58:41'),
(133, 8, NULL, NULL, NULL, 'impression', 'fa32cede6adf7be23d51358713f58dfcbff67ca4cf9b10dcc4dbe38f400a8e20', NULL, 0.000000, 0.000000, '2026-05-28 17:03:27'),
(134, 9, NULL, NULL, NULL, 'impression', 'fa32cede6adf7be23d51358713f58dfcbff67ca4cf9b10dcc4dbe38f400a8e20', NULL, 0.000000, 0.000000, '2026-05-28 17:03:27'),
(135, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:05:13'),
(136, 14, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:06:33'),
(137, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:08:54'),
(138, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:08:54'),
(139, 11, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:09:56'),
(140, 13, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:09:56'),
(141, 12, 19, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:09:56'),
(142, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:15:46'),
(143, 14, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:24:14'),
(144, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:24:48'),
(145, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:24:48'),
(146, 11, 21, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:25:01'),
(147, 12, 21, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:25:01'),
(148, 13, 21, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:25:01'),
(149, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:25:48'),
(150, 14, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:34:15'),
(151, 11, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:35:11'),
(152, 12, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:35:11'),
(153, 13, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:35:11'),
(154, 10, 19, 1, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:36:11'),
(155, 8, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:36:31'),
(156, 9, NULL, 1, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:36:31'),
(157, 14, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:44:32'),
(158, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:46:24'),
(159, 8, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:46:31'),
(160, 9, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:46:31'),
(161, 11, 23, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:47:49'),
(162, 12, 23, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:47:49'),
(163, 13, 23, NULL, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:47:49'),
(164, 14, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:55:18'),
(165, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:58:15'),
(166, 11, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:58:23'),
(167, 12, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:58:23'),
(168, 13, 22, 10, 10, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000100, 0.000500, '2026-05-28 17:58:23'),
(169, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:59:06'),
(170, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 17:59:06'),
(171, 14, NULL, NULL, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:06:43'),
(172, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:10:55'),
(173, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:10:55'),
(174, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:10:55');
INSERT INTO `ad_logs` (`id`, `ad_id`, `video_id`, `viewer_id`, `creator_id`, `type`, `ip_hash`, `user_agent`, `earnings_viewer`, `earnings_creator`, `created_at`) VALUES
(175, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:30:46'),
(176, 8, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:30:49'),
(177, 9, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:30:49'),
(178, 14, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:30:49'),
(179, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:41:18'),
(180, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 18:52:07'),
(181, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 19:02:17'),
(182, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 21:55:38'),
(183, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 22:15:24'),
(184, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 22:25:33'),
(185, 10, NULL, 10, NULL, 'impression', 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36 Edg/148.0.0.0', 0.000000, 0.000000, '2026-05-28 23:10:50');

-- --------------------------------------------------------

--
-- Table structure for table `ad_placements`
--

CREATE TABLE `ad_placements` (
  `id` int(10) UNSIGNED NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `device_target` enum('all','desktop','mobile') NOT NULL DEFAULT 'all',
  `ad_width` smallint(5) UNSIGNED DEFAULT NULL,
  `ad_height` smallint(5) UNSIGNED DEFAULT NULL,
  `reload_interval` int(10) UNSIGNED DEFAULT NULL,
  `ad_display_duration` smallint(5) UNSIGNED DEFAULT NULL,
  `ad_trigger_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `assigned_ad_id` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_placements`
--

INSERT INTO `ad_placements` (`id`, `key_name`, `device_target`, `ad_width`, `ad_height`, `reload_interval`, `ad_display_duration`, `ad_trigger_count`, `name`, `assigned_ad_id`, `updated_at`) VALUES
(1, 'landing_trending', 'desktop', NULL, NULL, NULL, NULL, NULL, 'Landing Page - Trending Now Grid (Last Card)', 8, '2026-05-27 03:43:49'),
(2, 'landing_latest', 'all', NULL, NULL, NULL, NULL, NULL, 'Landing Page - Latest Uploads Grid (Last Card)', 8, '2026-05-27 03:08:40'),
(3, 'search_grid', 'all', NULL, NULL, NULL, NULL, NULL, 'Search Results Grid (Last Card)', 8, '2026-05-27 03:06:12'),
(4, 'category_grid', 'all', NULL, NULL, NULL, NULL, NULL, 'Category Videos Grid (Last Card)', NULL, '2026-05-27 03:40:12'),
(5, 'landing_trending', 'mobile', NULL, NULL, NULL, NULL, NULL, 'Copy of Landing Page - Trending Now Grid (Last Card)', 9, '2026-05-27 03:43:31'),
(6, 'landing_trending_header', 'mobile', NULL, NULL, NULL, NULL, NULL, 'Landing Page - Trending Now Header Banner', 7, '2026-05-27 05:07:19'),
(7, 'between_sections_1', 'all', NULL, NULL, NULL, NULL, NULL, 'Landing Page - Between Sections 1 (Banner)', NULL, '2026-05-27 05:15:02'),
(8, 'between_sections_2', 'all', NULL, NULL, NULL, NULL, NULL, 'Landing Page - Between Sections 2 (Banner)', NULL, '2026-05-27 05:15:02'),
(10, 'home_mobile_top', 'mobile', NULL, NULL, 60, NULL, NULL, 'Home Page Mobile Top Banner', 10, '2026-05-28 17:34:14'),
(11, 'watch_sidebar', 'all', NULL, NULL, NULL, NULL, NULL, 'Watch Page Sidebar Banner', NULL, '2026-05-27 20:51:55'),
(12, 'watch_below_player', 'mobile', NULL, 58, NULL, NULL, NULL, 'Watch Page Below Player Banner', 11, '2026-05-28 14:58:11'),
(13, 'video_player_overlay', 'all', NULL, NULL, 5, 15, 5, 'Video Player Overlay Ad', 8, '2026-05-28 01:51:57'),
(14, 'watch_below_player', 'desktop', NULL, NULL, NULL, NULL, NULL, 'Copy of Watch Page Below Player Banner', 12, '2026-05-28 17:35:06'),
(15, 'watch_up_next', 'all', 800, 250, NULL, NULL, NULL, 'Watch Page Up Next Banner', 13, '2026-05-28 15:32:02'),
(16, 'above_footer', 'all', 737, 95, NULL, NULL, NULL, 'Above Footer Banner', 14, '2026-05-28 17:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_clicks`
--

CREATE TABLE `affiliate_clicks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `affiliate_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_hash` varchar(64) NOT NULL,
  `ref_code` varchar(20) NOT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `country` varchar(3) DEFAULT NULL,
  `device` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affiliate_clicks`
--

INSERT INTO `affiliate_clicks` (`id`, `affiliate_id`, `video_id`, `ip_hash`, `ref_code`, `converted`, `country`, `device`, `created_at`) VALUES
(668, 10, 13, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:21:03'),
(669, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:21:05'),
(670, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:26:52'),
(671, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:28:08'),
(672, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:35'),
(673, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:41'),
(674, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:45'),
(675, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:50'),
(676, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:53'),
(677, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:28:56'),
(678, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:29:04'),
(679, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:29:11'),
(680, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:29:16'),
(681, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:30:14'),
(682, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:30:20'),
(683, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:31:13'),
(684, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:34:56'),
(685, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:39:36'),
(686, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:39:38'),
(687, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:39:59'),
(688, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:40:12'),
(689, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:40:27'),
(690, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:40:43'),
(691, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:40:48'),
(692, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:41:05'),
(693, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:41:42'),
(694, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:41:54'),
(695, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:42:00'),
(696, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:42:15'),
(697, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:43:16'),
(698, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:43:22'),
(699, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:43:31'),
(700, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:43:49'),
(701, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:43:57'),
(702, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:44:34'),
(703, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 03:44:43'),
(704, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 03:51:23'),
(705, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:07:40'),
(706, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:11:29'),
(707, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:13:15'),
(708, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:15:05'),
(709, 10, 13, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:15:15'),
(710, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:15:39'),
(711, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:17:41'),
(712, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:17:46'),
(713, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:17:52'),
(714, 10, 13, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:18:35'),
(715, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:18:38'),
(716, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:18:42'),
(717, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:18:44'),
(718, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:18:53'),
(719, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:19:36'),
(720, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:21:19'),
(721, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:21:38'),
(722, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:29:22'),
(723, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:29:37'),
(724, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:32:09'),
(725, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:32:55'),
(726, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:33:03'),
(727, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:33:08'),
(728, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:33:11'),
(729, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:34:03'),
(730, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:34:09'),
(731, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:49:18'),
(732, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:58:01'),
(733, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:58:13'),
(734, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:59:23'),
(735, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:59:31'),
(736, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:59:41'),
(737, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:59:44'),
(738, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 04:59:49'),
(739, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 04:59:59'),
(740, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:00:29'),
(741, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:00:34'),
(742, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:00:37'),
(743, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:00:45'),
(744, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:00:50'),
(745, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:01:18'),
(746, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:01:37'),
(747, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:05:20'),
(748, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:05:24'),
(749, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:05:31'),
(750, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:05:37'),
(751, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:06:43'),
(752, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:07:20'),
(753, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:07:24'),
(754, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:07:43'),
(755, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:11:09'),
(756, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:11:11'),
(757, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:11:14'),
(758, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:11:20'),
(759, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:11:28'),
(760, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:13:16'),
(761, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:13:27'),
(762, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:13:40'),
(763, 10, 16, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:14:20'),
(764, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:14:41'),
(765, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:15:13'),
(766, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:15:50'),
(767, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:15:54'),
(768, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:16:03'),
(769, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:16:06'),
(770, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:16:10'),
(771, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:16:13'),
(772, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:16:26'),
(773, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:05'),
(774, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:21'),
(775, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:31'),
(776, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:36'),
(777, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:39'),
(778, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:17:41'),
(779, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:18:28'),
(780, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:18:46'),
(781, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:32:44'),
(782, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:46:54'),
(783, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:47:49'),
(784, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:47:54'),
(785, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:47:58'),
(786, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:48:22'),
(787, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:49:04'),
(788, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:49:11'),
(789, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:49:13'),
(790, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 05:49:23'),
(791, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:50:05'),
(792, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 05:50:13'),
(793, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-27 06:02:54'),
(794, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 07:26:32'),
(795, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 07:27:17'),
(796, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 07:27:22'),
(797, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 07:27:42'),
(798, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-27 07:28:34'),
(799, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 02:21:30'),
(800, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 02:22:39'),
(801, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:31:06'),
(802, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:41:31'),
(803, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:41:36'),
(804, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:41:40'),
(805, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:41:45'),
(806, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:41:57'),
(807, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:46:59'),
(808, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 03:47:03'),
(809, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 03:47:10'),
(810, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 03:47:13'),
(811, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 03:47:36'),
(812, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 03:47:40'),
(813, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 03:47:42'),
(814, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:14:06'),
(815, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:14:24'),
(816, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:14:31'),
(817, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:14:33'),
(818, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:14:37'),
(819, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:15:19'),
(820, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:15:22'),
(821, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:16:06'),
(822, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:16:08'),
(823, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:16:13'),
(824, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:17:43'),
(825, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:17:48'),
(826, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:02'),
(827, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:14'),
(828, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:19'),
(829, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:37'),
(830, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:45'),
(831, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:48'),
(832, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:18:55'),
(833, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:19:01'),
(834, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:19:38'),
(835, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:19:46'),
(836, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:19:50'),
(837, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:12'),
(838, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:15'),
(839, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:16'),
(840, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:18'),
(841, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:19'),
(842, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:21'),
(843, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:23'),
(844, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:20:25'),
(845, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:21:10'),
(846, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:21:13'),
(847, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:21:15'),
(848, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:21:36'),
(849, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:21:38'),
(850, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:13'),
(851, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:15'),
(852, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:36'),
(853, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:45'),
(854, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:47'),
(855, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:56'),
(856, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:57'),
(857, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:30:58'),
(858, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:00'),
(859, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:02'),
(860, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:03'),
(861, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:05'),
(862, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:10'),
(863, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:54'),
(864, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:31:55'),
(865, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:29'),
(866, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:31'),
(867, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:33'),
(868, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:34'),
(869, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:36'),
(870, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:37'),
(871, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:38'),
(872, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:40'),
(873, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:43'),
(874, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:32:47'),
(875, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:35:01'),
(876, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:35:04'),
(877, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:35:08'),
(878, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:35:11'),
(879, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:35:30'),
(880, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:31'),
(881, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:32'),
(882, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:44'),
(883, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:45'),
(884, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:46'),
(885, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:47'),
(886, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:52'),
(887, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:37:57'),
(888, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:18'),
(889, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:20'),
(890, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:21'),
(891, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:22'),
(892, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:23'),
(893, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:24'),
(894, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:32'),
(895, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:33'),
(896, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:34'),
(897, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:35'),
(898, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:37'),
(899, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:39'),
(900, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:38:41'),
(901, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:40:56'),
(902, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:40:57'),
(903, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:40:59'),
(904, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:00'),
(905, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:01'),
(906, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:02'),
(907, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:03'),
(908, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:04'),
(909, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:06'),
(910, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:07'),
(911, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:09'),
(912, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:29'),
(913, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:31'),
(914, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:34'),
(915, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:37'),
(916, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:55'),
(917, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:57'),
(918, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:41:59'),
(919, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:13'),
(920, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:15'),
(921, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:16'),
(922, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:17'),
(923, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:18'),
(924, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:19'),
(925, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:20'),
(926, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:21'),
(927, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:22'),
(928, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:44:23'),
(929, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:45:40'),
(930, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:45:42'),
(931, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:45:58'),
(932, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:45:59'),
(933, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 05:47:50'),
(934, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:00'),
(935, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:09'),
(936, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:21'),
(937, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:31'),
(938, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:35'),
(939, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:42'),
(940, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:49:50'),
(941, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:55:28'),
(942, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:55:40'),
(943, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:55:47'),
(944, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:55:49'),
(945, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:56:01'),
(946, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:56:02'),
(947, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:56:13'),
(948, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:58:01'),
(949, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:58:10'),
(950, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:58:33'),
(951, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:58:47'),
(952, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:59:02'),
(953, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:59:24'),
(954, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:59:34'),
(955, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 05:59:41'),
(956, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:00:15'),
(957, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:00:23'),
(958, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:01:39'),
(959, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:02:09'),
(960, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:03:10'),
(961, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:03:16'),
(962, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:04:35'),
(963, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:04:42'),
(964, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:04:45'),
(965, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:04:48'),
(966, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:04:53'),
(967, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:05:03'),
(968, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:05:09'),
(969, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:31'),
(970, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:36'),
(971, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:39'),
(972, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:41'),
(973, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:43'),
(974, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:10:46'),
(975, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:11:34'),
(976, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:12:13'),
(977, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:12:23'),
(978, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:12:26'),
(979, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:12:29'),
(980, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:12:31'),
(981, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:13:01'),
(982, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:13:05'),
(983, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:13:09'),
(984, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:13:30'),
(985, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:13:45'),
(986, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:14:38'),
(987, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:14:41'),
(988, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:14:42'),
(989, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:14:45'),
(990, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:14:47'),
(991, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:17:15'),
(992, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:17:39'),
(993, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:18:38'),
(994, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:19:21'),
(995, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:19:24'),
(996, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:20:08'),
(997, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:22:17'),
(998, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:23:43'),
(999, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:24:42'),
(1000, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:24:54'),
(1001, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:38:51'),
(1002, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:40:48'),
(1003, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:40:50'),
(1004, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:00'),
(1005, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:01'),
(1006, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:03'),
(1007, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:03'),
(1008, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:03'),
(1009, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:04'),
(1010, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:42:04'),
(1011, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:43:06'),
(1012, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 06:44:15'),
(1013, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 06:44:16'),
(1014, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 06:44:26'),
(1015, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:45:29'),
(1016, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:45:36'),
(1017, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:45:44'),
(1018, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:24'),
(1019, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:28'),
(1020, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:40'),
(1021, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:49'),
(1022, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:55'),
(1023, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:57'),
(1024, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:46:59'),
(1025, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:47:00'),
(1026, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 06:57:50'),
(1027, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:01:10'),
(1028, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:01:13'),
(1029, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:01:14'),
(1030, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:18:19'),
(1031, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:18:47'),
(1032, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:18:58'),
(1033, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:19:27'),
(1034, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:19:39'),
(1035, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:19:48'),
(1036, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:19:56');
INSERT INTO `affiliate_clicks` (`id`, `affiliate_id`, `video_id`, `ip_hash`, `ref_code`, `converted`, `country`, `device`, `created_at`) VALUES
(1037, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:28:10'),
(1038, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 07:30:54'),
(1039, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:53:52'),
(1040, 10, 23, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:54:00'),
(1041, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:55:29'),
(1042, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:57:36'),
(1043, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:57:47'),
(1044, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:58:11'),
(1045, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:58:16'),
(1046, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:59:20'),
(1047, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:59:24'),
(1048, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:59:29'),
(1049, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 14:59:34'),
(1050, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:00:04'),
(1051, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:00:45'),
(1052, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:00:51'),
(1053, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:00:54'),
(1054, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:01:19'),
(1055, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:01:28'),
(1056, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:02:26'),
(1057, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:02:31'),
(1058, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:02:43'),
(1059, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:02:48'),
(1060, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:02:58'),
(1061, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:03:24'),
(1062, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:03:28'),
(1063, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:04:23'),
(1064, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:05:21'),
(1065, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:16:48'),
(1066, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:17:12'),
(1067, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:17:22'),
(1068, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:18:19'),
(1069, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:18:23'),
(1070, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:19:02'),
(1071, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:19:09'),
(1072, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:19:31'),
(1073, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:19:34'),
(1074, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:20:16'),
(1075, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:20:38'),
(1076, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:21:00'),
(1077, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:21:41'),
(1078, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:21:50'),
(1079, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:21:54'),
(1080, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:22:26'),
(1081, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:22:29'),
(1082, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:22:49'),
(1083, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:22:52'),
(1084, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:23:04'),
(1085, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:23:06'),
(1086, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:23:35'),
(1087, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:23:38'),
(1088, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:30:30'),
(1089, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:31:02'),
(1090, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:31:11'),
(1091, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:31:20'),
(1092, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:31:28'),
(1093, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:32:02'),
(1094, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:32:05'),
(1095, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:33:13'),
(1096, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:43:17'),
(1097, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:43:41'),
(1098, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:43:52'),
(1099, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:43:57'),
(1100, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:44:02'),
(1101, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:44:06'),
(1102, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:44:31'),
(1103, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:45:02'),
(1104, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:48:59'),
(1105, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:49:24'),
(1106, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:49:34'),
(1107, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 15:51:35'),
(1108, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:52:15'),
(1109, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:54:33'),
(1110, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:55:50'),
(1111, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:57:35'),
(1112, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:58:26'),
(1113, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:58:55'),
(1114, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 15:59:06'),
(1115, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:06:25'),
(1116, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:06:29'),
(1117, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:06:40'),
(1118, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:06:49'),
(1119, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:06:59'),
(1120, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:07:30'),
(1121, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:07:33'),
(1122, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:10:01'),
(1123, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:11:57'),
(1124, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:12:05'),
(1125, 10, 23, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:12:36'),
(1126, 10, 23, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:12:49'),
(1127, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:13:20'),
(1128, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:13:24'),
(1129, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:13:30'),
(1130, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:21:15'),
(1131, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:21:32'),
(1132, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:21:36'),
(1133, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:22:23'),
(1134, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:23:31'),
(1135, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:23:38'),
(1136, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:23:56'),
(1137, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:24:29'),
(1138, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:24:35'),
(1139, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:24:40'),
(1140, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:25:52'),
(1141, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:26:32'),
(1142, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:30:47'),
(1143, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:32:29'),
(1144, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:32:49'),
(1145, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:33:39'),
(1146, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:33:47'),
(1147, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:35:15'),
(1148, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:35:18'),
(1149, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:35:44'),
(1150, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:35:49'),
(1151, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:36:10'),
(1152, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:36:51'),
(1153, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:36:54'),
(1154, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:37:00'),
(1155, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:37:06'),
(1156, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:37:25'),
(1157, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:39:37'),
(1158, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:39:54'),
(1159, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:40:34'),
(1160, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 16:40:49'),
(1161, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:40:57'),
(1162, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:42:33'),
(1163, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:43:55'),
(1164, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:44:03'),
(1165, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:44:05'),
(1166, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:46:02'),
(1167, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:46:19'),
(1168, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:54:41'),
(1169, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:54:49'),
(1170, 10, 20, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:54:59'),
(1171, 10, 23, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:55:05'),
(1172, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:55:14'),
(1173, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:55:37'),
(1174, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:56:01'),
(1175, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:56:29'),
(1176, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:57:03'),
(1177, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:57:10'),
(1178, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:58:02'),
(1179, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 16:58:38'),
(1180, 11, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 16:58:40'),
(1181, 11, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 16:59:05'),
(1182, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 16:59:35'),
(1183, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 16:59:42'),
(1184, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 16:59:52'),
(1185, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:04:03'),
(1186, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:04:25'),
(1187, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:05:13'),
(1188, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:05:27'),
(1189, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:05:35'),
(1190, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:05:37'),
(1191, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:05:39'),
(1192, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:05:40'),
(1193, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:06:32'),
(1194, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'desktop', '2026-05-28 17:06:37'),
(1195, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:08:54'),
(1196, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:09:00'),
(1197, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:09:06'),
(1198, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:09:15'),
(1199, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:09:22'),
(1200, 11, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', 'FA46C8BE', 0, NULL, 'mobile', '2026-05-28 17:09:54'),
(1201, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:09:56'),
(1202, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:10:10'),
(1203, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:10:20'),
(1204, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:10:25'),
(1205, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:10:40'),
(1206, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:10:48'),
(1207, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:15:45'),
(1208, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:24:14'),
(1209, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:24:31'),
(1210, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:24:48'),
(1211, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:01'),
(1212, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:06'),
(1213, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:29'),
(1214, 10, 21, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:32'),
(1215, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:35'),
(1216, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:48'),
(1217, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:25:55'),
(1218, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:26:24'),
(1219, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:26:42'),
(1220, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:26:46'),
(1221, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:26:49'),
(1222, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:27:09'),
(1223, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:30:17'),
(1224, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:30:22'),
(1225, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:30:27'),
(1226, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:30:53'),
(1227, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:31:32'),
(1228, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:31:33'),
(1229, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:32:34'),
(1230, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:32:50'),
(1231, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:33:17'),
(1232, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:33:26'),
(1233, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:34:14'),
(1234, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:34:18'),
(1235, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:35:06'),
(1236, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:35:10'),
(1237, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:36:30'),
(1238, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:36:35'),
(1239, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:36:42'),
(1240, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:37:18'),
(1241, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:37:23'),
(1242, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:37:38'),
(1243, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:39:51'),
(1244, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:39:55'),
(1245, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:40:14'),
(1246, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:43:17'),
(1247, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:43:49'),
(1248, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:44:32'),
(1249, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:44:45'),
(1250, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:45:49'),
(1251, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:46:02'),
(1252, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:46:24'),
(1253, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:46:31'),
(1254, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:47:10'),
(1255, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:47:20'),
(1256, 10, 23, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:47:49'),
(1257, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:47:51'),
(1258, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:47:54'),
(1259, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:48:43'),
(1260, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:48:45'),
(1261, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:48:56'),
(1262, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:49:39'),
(1263, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:49:50'),
(1264, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:49:53'),
(1265, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:50:21'),
(1266, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:50:26'),
(1267, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'desktop', '2026-05-28 17:50:33'),
(1268, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:53:29'),
(1269, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:53:39'),
(1270, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:54:18'),
(1271, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:55:18'),
(1272, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:58:09'),
(1273, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:58:14'),
(1274, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:58:22'),
(1275, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:58:53'),
(1276, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:59:00'),
(1277, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:59:06'),
(1278, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:59:08'),
(1279, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:59:14'),
(1280, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 17:59:30'),
(1281, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:02:45'),
(1282, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:02:47'),
(1283, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:02:51'),
(1284, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:02:54'),
(1285, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:03:02'),
(1286, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:03:19'),
(1287, 10, 19, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:03:22'),
(1288, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:03:24'),
(1289, 10, 22, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:03:36'),
(1290, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:06:42'),
(1291, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:06:45'),
(1292, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:06:56'),
(1293, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:07:00'),
(1294, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:07:02'),
(1295, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:10:55'),
(1296, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:30:49'),
(1297, 10, 0, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', '9A9B9CAB', 0, NULL, 'mobile', '2026-05-28 18:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6366f1',
  `description` text DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `color`, `description`, `sort_order`, `is_active`, `created_at`, `image`) VALUES
(13, 'South Indian Movies', 'south-indian-movies', 'play', '#6366f1', '', 0, 1, '2026-05-27 20:48:47', NULL),
(14, 'Indian Movies', 'indian-movies', 'play', '#4b4d00', '', 0, 1, '2026-05-27 20:49:01', NULL),
(15, 'Indian Punjabi Movies', 'indian-punjabi-movies', 'play', '#ff0000', '', 0, 1, '2026-05-27 20:49:34', NULL),
(16, 'Pakistani Movies', 'pakistani-movies', 'play', '#00660c', '', 0, 1, '2026-05-27 20:50:06', NULL),
(17, 'Songs', 'songs', 'play', '#ff0000', '', 0, 1, '2026-05-27 20:50:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('visible','hidden','spam') NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `video_id`, `user_id`, `parent_id`, `content`, `likes`, `is_pinned`, `status`, `created_at`) VALUES
(3, 22, 1, NULL, 'djf;dfjsdkfj', 0, 0, 'visible', '2026-05-28 15:44:24'),
(4, 22, 1, NULL, 'dfkdslf', 0, 0, 'visible', '2026-05-28 15:49:47'),
(5, 22, 1, NULL, 'dsfsdf', 0, 0, 'visible', '2026-05-28 15:49:48'),
(6, 19, 11, NULL, 'kjkjlj', 0, 0, 'visible', '2026-05-28 16:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `earnings`
--

CREATE TABLE `earnings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('video_view','affiliate_click','affiliate_view','payout','bonus','watch_time','ad_revenue','referral','ad_impression','ad_click') NOT NULL,
  `amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `earnings`
--

INSERT INTO `earnings` (`id`, `user_id`, `type`, `amount`, `reference_id`, `description`, `status`, `created_at`) VALUES
(13, 10, '', 0.0010, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-27 21:07:59'),
(14, 10, '', 0.0005, 10, 'Creator ad impression on video #21 (Ad #10)', 'approved', '2026-05-28 00:13:28'),
(15, 10, '', 0.0005, 7, 'Creator ad impression on video #21 (Ad #7)', 'approved', '2026-05-28 00:13:29'),
(16, 10, '', 0.0005, 10, 'Creator ad impression on video #20 (Ad #10)', 'approved', '2026-05-28 00:34:04'),
(17, 10, '', 0.0005, 8, 'Creator ad impression on video #20 (Ad #8)', 'approved', '2026-05-28 00:35:03'),
(18, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 00:44:11'),
(19, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 00:47:07'),
(20, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 00:54:24'),
(21, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 01:01:08'),
(22, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 01:05:11'),
(23, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 01:06:15'),
(24, 10, '', 0.0005, 8, 'Creator ad impression on video #22 (Ad #8)', 'approved', '2026-05-28 01:11:09'),
(25, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 01:15:21'),
(26, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 01:19:41'),
(27, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 01:22:38'),
(28, 10, '', 0.0005, 10, 'Creator ad impression on video #21 (Ad #10)', 'approved', '2026-05-28 01:25:25'),
(29, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 01:38:38'),
(30, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 01:44:03'),
(31, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 01:49:29'),
(32, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 01:55:30'),
(33, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 02:01:52'),
(34, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 02:17:17'),
(35, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 02:17:19'),
(36, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 03:31:10'),
(37, 10, '', 0.0001, 11, 'Viewer ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 03:31:10'),
(38, 10, '', 0.0005, 8, 'Creator ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 03:31:15'),
(39, 10, '', 0.0001, 8, 'Viewer ad impression on video #19 (Ad #8)', 'approved', '2026-05-28 03:31:15'),
(40, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 03:41:31'),
(41, 10, '', 0.0001, 11, 'Viewer ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 03:41:31'),
(42, 10, '', 0.0005, 11, 'Creator ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 05:16:13'),
(43, 10, '', 0.0001, 11, 'Viewer ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 05:16:13'),
(44, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 05:32:47'),
(45, 10, '', 0.0001, 11, 'Viewer ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 05:32:47'),
(46, 10, '', 0.0005, 11, 'Creator ad impression on video #23 (Ad #11)', 'approved', '2026-05-28 14:54:00'),
(47, 10, '', 0.0005, 12, 'Creator ad impression on video #20 (Ad #12)', 'approved', '2026-05-28 15:00:54'),
(48, 10, '', 0.0005, 10, 'Creator ad impression on video #20 (Ad #10)', 'approved', '2026-05-28 15:04:23'),
(49, 10, '', 0.0005, 11, 'Creator ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 15:04:23'),
(50, 10, '', 0.0005, 10, 'Creator ad impression on video #20 (Ad #10)', 'approved', '2026-05-28 15:16:48'),
(51, 10, '', 0.0005, 12, 'Creator ad impression on video #20 (Ad #12)', 'approved', '2026-05-28 15:16:48'),
(52, 10, '', 0.0005, 11, 'Creator ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 15:16:48'),
(53, 10, '', 0.0005, 13, 'Creator ad impression on video #20 (Ad #13)', 'approved', '2026-05-28 15:22:52'),
(54, 10, '', 0.0005, 10, 'Creator ad impression on video #20 (Ad #10)', 'approved', '2026-05-28 15:27:41'),
(55, 10, '', 0.0005, 11, 'Creator ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 15:30:30'),
(56, 10, '', 0.0005, 12, 'Creator ad impression on video #20 (Ad #12)', 'approved', '2026-05-28 15:30:30'),
(57, 10, '', 0.0005, 13, 'Creator ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 15:33:14'),
(58, 10, '', 0.0005, 8, 'Creator ad impression on video #22 (Ad #8)', 'approved', '2026-05-28 15:33:34'),
(59, 10, '', 0.0005, 10, 'Creator ad impression on video #22 (Ad #10)', 'approved', '2026-05-28 15:43:17'),
(60, 10, '', 0.0005, 11, 'Creator ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 15:43:17'),
(61, 10, '', 0.0005, 12, 'Creator ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 15:43:18'),
(62, 10, '', 0.0005, 13, 'Creator ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 15:43:18'),
(63, 10, '', 0.0005, 8, 'Creator ad impression on video #22 (Ad #8)', 'approved', '2026-05-28 15:43:56'),
(64, 10, '', 0.0005, 10, 'Creator ad impression on video #22 (Ad #10)', 'approved', '2026-05-28 15:54:34'),
(65, 10, '', 0.0005, 11, 'Creator ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 15:54:34'),
(66, 10, '', 0.0005, 12, 'Creator ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 15:54:34'),
(67, 10, '', 0.0005, 13, 'Creator ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 15:54:34'),
(68, 10, '', 0.0005, 12, 'Creator ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 16:12:06'),
(69, 10, '', 0.0001, 12, 'Viewer ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 16:12:06'),
(70, 10, '', 0.0005, 11, 'Creator ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 16:12:06'),
(71, 10, '', 0.0001, 11, 'Viewer ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 16:12:06'),
(72, 10, '', 0.0005, 13, 'Creator ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 16:12:06'),
(73, 10, '', 0.0001, 13, 'Viewer ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 16:12:06'),
(74, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 16:17:33'),
(75, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 16:26:32'),
(76, 10, '', 0.0005, 12, 'Creator ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 16:26:32'),
(77, 10, '', 0.0005, 13, 'Creator ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 16:26:32'),
(78, 10, '', 0.0005, 11, 'Creator ad impression on video #20 (Ad #11)', 'approved', '2026-05-28 16:46:19'),
(79, 10, '', 0.0005, 12, 'Creator ad impression on video #20 (Ad #12)', 'approved', '2026-05-28 16:46:19'),
(80, 10, '', 0.0005, 13, 'Creator ad impression on video #20 (Ad #13)', 'approved', '2026-05-28 16:46:19'),
(81, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 16:58:40'),
(82, 11, '', 0.0001, 11, 'Viewer ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 16:58:40'),
(83, 10, '', 0.0005, 12, 'Creator ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 16:58:41'),
(84, 11, '', 0.0001, 12, 'Viewer ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 16:58:41'),
(85, 10, '', 0.0005, 13, 'Creator ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 16:58:41'),
(86, 11, '', 0.0001, 13, 'Viewer ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 16:58:41'),
(87, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 17:09:56'),
(88, 10, '', 0.0001, 11, 'Viewer ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 17:09:56'),
(89, 10, '', 0.0005, 13, 'Creator ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 17:09:56'),
(90, 10, '', 0.0001, 13, 'Viewer ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 17:09:56'),
(91, 10, '', 0.0005, 12, 'Creator ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 17:09:56'),
(92, 10, '', 0.0001, 12, 'Viewer ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 17:09:56'),
(93, 10, '', 0.0005, 11, 'Creator ad impression on video #21 (Ad #11)', 'approved', '2026-05-28 17:25:01'),
(94, 10, '', 0.0001, 11, 'Viewer ad impression on video #21 (Ad #11)', 'approved', '2026-05-28 17:25:01'),
(95, 10, '', 0.0005, 12, 'Creator ad impression on video #21 (Ad #12)', 'approved', '2026-05-28 17:25:01'),
(96, 10, '', 0.0001, 12, 'Viewer ad impression on video #21 (Ad #12)', 'approved', '2026-05-28 17:25:01'),
(97, 10, '', 0.0005, 13, 'Creator ad impression on video #21 (Ad #13)', 'approved', '2026-05-28 17:25:01'),
(98, 10, '', 0.0001, 13, 'Viewer ad impression on video #21 (Ad #13)', 'approved', '2026-05-28 17:25:01'),
(99, 10, '', 0.0005, 11, 'Creator ad impression on video #19 (Ad #11)', 'approved', '2026-05-28 17:35:11'),
(100, 10, '', 0.0005, 12, 'Creator ad impression on video #19 (Ad #12)', 'approved', '2026-05-28 17:35:11'),
(101, 10, '', 0.0005, 13, 'Creator ad impression on video #19 (Ad #13)', 'approved', '2026-05-28 17:35:11'),
(102, 10, '', 0.0005, 10, 'Creator ad impression on video #19 (Ad #10)', 'approved', '2026-05-28 17:36:11'),
(103, 10, '', 0.0005, 11, 'Creator ad impression on video #23 (Ad #11)', 'approved', '2026-05-28 17:47:49'),
(104, 10, '', 0.0005, 12, 'Creator ad impression on video #23 (Ad #12)', 'approved', '2026-05-28 17:47:49'),
(105, 10, '', 0.0005, 13, 'Creator ad impression on video #23 (Ad #13)', 'approved', '2026-05-28 17:47:49'),
(106, 10, '', 0.0005, 11, 'Creator ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 17:58:23'),
(107, 10, '', 0.0001, 11, 'Viewer ad impression on video #22 (Ad #11)', 'approved', '2026-05-28 17:58:23'),
(108, 10, '', 0.0005, 12, 'Creator ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 17:58:23'),
(109, 10, '', 0.0001, 12, 'Viewer ad impression on video #22 (Ad #12)', 'approved', '2026-05-28 17:58:23'),
(110, 10, '', 0.0005, 13, 'Creator ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 17:58:23'),
(111, 10, '', 0.0001, 13, 'Viewer ad impression on video #22 (Ad #13)', 'approved', '2026-05-28 17:58:23');

-- --------------------------------------------------------

--
-- Table structure for table `footer_sections`
--

CREATE TABLE `footer_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `footer_sections`
--

INSERT INTO `footer_sections` (`id`, `name`, `sort_order`, `created_at`) VALUES
(1, 'Platform', 1, '2026-05-28 16:48:13'),
(2, 'Programs & Guidelines', 2, '2026-05-28 16:48:13'),
(3, 'Legal & Policies', 3, '2026-05-28 16:48:13');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(40) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_desc` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `status` enum('published','draft','private','scheduled') NOT NULL DEFAULT 'published',
  `publish_at` datetime DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `footer_section_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_desc`, `meta_keywords`, `status`, `publish_at`, `is_published`, `footer_section_id`, `created_at`, `updated_at`) VALUES
(1, 'Home', 'home', '<h1>Welcome to FreeHub</h1><p>Watch. Share. Earn.</p><p>FreeHub is the premier next-generation video sharing platform where creators and viewers connect, collaborate, and share rewards. Explore trending topics, support your favorite video creators, or broadcast your own channel to start building your audience today.</p>', NULL, NULL, NULL, 'published', NULL, 1, NULL, '2026-05-27 18:29:05', '2026-05-27 18:29:05'),
(2, 'About Us', 'about-us', '<h1>About Us</h1><p>Welcome to FreeHub! We are a dynamic, community-driven video sharing platform designed to empower content creators and engage audiences around the globe.</p><h2>Our Mission</h2><p>Our mission is simple: to democratize online entertainment and monetization. We believe that everyone who contributes to the platform—whether by creating compelling content or actively viewing videos—deserves to share in its success.</p><h2>How It Works</h2><ul><li><strong>Creators:</strong> Upload high-definition videos, engage with subscribers, and monetize content directly based on watch duration and advertising engagement.</li><li><strong>Viewers:</strong> Watch interesting videos, discover new channels, and earn active viewing rewards.</li></ul>', NULL, NULL, NULL, 'published', NULL, 1, 1, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(3, 'Privacy Policy', 'privacy-policy', '<h1>Privacy Policy</h1><p>Last updated: May 2026</p><p>Your privacy is of paramount importance to us. This Privacy Policy details the types of personal data we collect, how we use it, and the strict security measures we implement to protect your information.</p><h2>1. Information We Collect</h2><p>We collect information to provide better services to all our users, including account details, viewing histories, interaction records, and preferred payment preferences.</p><h2>2. How We Use Information</h2><p>We use the collected information to manage user authentication, accurately calculate viewer rewards and creator earnings, process secure withdrawals, and prevent fraudulent activity on the platform.</p>', NULL, NULL, NULL, 'published', NULL, 1, 3, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(4, 'Disclaimer', 'disclaimer', '<h1>Disclaimer</h1><p>Please read this disclaimer carefully before using the platform.</p><h2>No Earnings Guarantees</h2><p>Any earning statistics, rate tables, or success stories displayed on the platform are illustrative examples of potential outcomes. Actual creator and viewer earnings are not guaranteed and will vary based on user engagement, geographic location, adherence to community rules, and overall platform ad revenue.</p><h2>Third-Party Ads</h2><p>We display third-party advertisements. We are not responsible for the contents, products, or claims made in these external advertisements.</p>', NULL, NULL, NULL, 'published', NULL, 1, 3, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(5, 'Contact Us', 'contact-us', '<h1>Contact Us</h1><p>Have questions, technical issues, or partnership proposals? The FreeHub support team is here to assist you.</p><h2>Get in Touch</h2><p>You can contact our support department directly by sending an email to:</p><p><strong>Email:</strong> support@freehub.live</p><p>Our business hours are Monday through Friday, 9:00 AM to 6:00 PM (EST). We aim to respond to all inquiries within 24 to 48 hours.</p>', NULL, NULL, NULL, 'published', NULL, 1, 1, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(6, 'Creator Page', 'creator-page', '<h1>Creator Program</h1><p>Welcome to the FreeHub Creator Program. Broadcast your passion, build a loyal fanbase, and generate competitive revenue from your content.</p><h2>How to Get Started</h2><ol><li><strong>Setup Channel:</strong> Register or update your account role to Creator and define your unique channel name.</li><li><strong>Upload Original Content:</strong> Upload videos in standard high-definition formats. Keep titles descriptive and thumbnails engaging.</li><li><strong>Promote:</strong> Share your videos across social channels using your custom referral link to drive initial traction.</li></ol><h2>Rules & Policies</h2><ul><li><strong>Originality:</strong> Only upload videos that you own or have full authorization to distribute. Plagiarism will lead to channel termination.</li><li><strong>Quality:</strong> Maintain clear audio and visual standards. Poor quality videos may be unlisted.</li><li><strong>Prohibited Content:</strong> Content displaying violence, harassment, hate speech, or explicit material is strictly forbidden.</li></ul>', NULL, NULL, NULL, 'published', NULL, 1, 2, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(7, 'Viewer Page', 'viewer-page', '<h1>Viewer Rewards</h1><p>FreeHub values your time and attention. That is why we pay you to watch videos!</p><h2>How to Watch & Earn</h2><ul><li><strong>Stay Active:</strong> Earn rewards for every minute you spend watching authorized videos on our platform.</li><li><strong>Refer Friends:</strong> Share your referral code. For every creator or viewer you introduce to FreeHub, you earn a percentage of their earnings for life!</li></ul><h2>Viewer Rules & Fair Play</h2><p>To keep the ecosystem fair for creators and advertisers, we enforce the following rules:</p><ul><li>No botting, scripting, automatic page reloads, or background playing tools.</li><li>Only watch one video at a time. Multi-tabbing to inflate watch time is disallowed.</li><li>Use a single account. Creating duplicate accounts to claim rewards will result in an immediate and permanent ban.</li></ul>', NULL, NULL, NULL, 'published', NULL, 1, 2, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(8, 'Payment & Payout Policy', 'payment-policy', '<h1>Payment & Payout Policy</h1><p>At FreeHub, we ensure secure, transparent, and timely payouts for all eligible creators and viewers.</p><h2>Withdrawal Guidelines</h2><table border=\\\"1\\\" style=\\\"border-collapse: collapse; width: 100%; border-color: var(--border);\\\"><thead><tr><th style=\\\"padding: 8px; text-align: left;\\\">Payment Parameter</th><th style=\\\"padding: 8px; text-align: left;\\\">Detail / Limit</th></tr></thead><tbody><tr><td style=\\\"padding: 8px;\\\">Minimum Threshold</td><td style=\\\"padding: 8px;\\\">$25.00 USD (or local currency equivalent)</td></tr><tr><td style=\\\"padding: 8px;\\\">Processing Time</td><td style=\\\"padding: 8px;\\\">Paid within 7 business days from approval date</td></tr><tr><td style=\\\"padding: 8px;\\\">Supported Channels</td><td style=\\\"padding: 8px;\\\">PayPal, Direct Bank Transfer, Cryptocurrency (USDT)</td></tr></tbody></table><p style=\\\"margin-top: 12px;\\\">All withdrawal requests undergo manual audit by the administration team to verify traffic authenticity and rule compliance.</p>', NULL, NULL, NULL, 'published', NULL, 1, 3, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(9, 'Terms & Conditions', 'terms-conditions', '<h1>Terms & Conditions</h1><p>These Terms and Conditions govern your access to and use of FreeHub. By creating an account or browsing the platform, you fully accept these terms.</p><h2>1. Account Registration</h2><p>You must provide accurate, complete, and up-to-date information during signup. You are solely responsible for maintaining account confidentiality.</p><h2>2. Intellectual Property</h2><p>All trademarks, logos, and system layouts remain the exclusive property of FreeHub. Uploaded content remains the property of the creator, who grants FreeHub a worldwide license to host and stream it.</p>', NULL, NULL, NULL, 'published', NULL, 1, 3, '2026-05-27 18:29:05', '2026-05-28 16:48:13'),
(10, 'Community Guidelines', 'community-guidelines', '<h1>Community Guidelines</h1><p>Our guidelines are designed to foster a safe, positive, and constructive environment for all users on FreeHub.</p><h2>Be Respectful</h2><p>We do not tolerate harassment, bullying, hate speech, or discriminatory language based on race, gender, religion, or orientation.</p><h2>Content Safety</h2><p>Keep content safe for our diverse audience. Avoid posting graphic violence, self-harm material, or illegal activities.</p>', NULL, NULL, NULL, 'published', NULL, 1, 2, '2026-05-27 18:29:05', '2026-05-28 16:48:13');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `visibility` enum('public','private','unlisted') NOT NULL DEFAULT 'public',
  `video_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_items`
--

CREATE TABLE `playlist_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `playlist_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_videos`
--

CREATE TABLE `playlist_videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `playlist_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referral_conversions`
--

CREATE TABLE `referral_conversions` (
  `id` int(10) UNSIGNED NOT NULL,
  `referrer_id` int(10) UNSIGNED NOT NULL,
  `referred_user_id` int(10) UNSIGNED NOT NULL,
  `ref_code` varchar(20) NOT NULL,
  `bonus_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `referral_conversions`
--

INSERT INTO `referral_conversions` (`id`, `referrer_id`, `referred_user_id`, `ref_code`, `bonus_paid`, `created_at`) VALUES
(1, 3, 4, 'AB2F42D3', 0, '2026-05-25 00:53:11'),
(2, 3, 5, 'AB2F42D3', 0, '2026-05-25 00:53:11'),
(3, 4, 6, '6FE73350', 0, '2026-05-25 00:53:11'),
(4, 4, 8, '6FE73350', 0, '2026-05-25 01:46:23');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(40) NOT NULL DEFAULT 'general',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `updated_at`) VALUES
(1, 'site_name', 'FreeHub', 'general', '2026-05-27 21:03:02'),
(2, 'site_tagline', 'Watch. Share. Earn.', 'general', '2026-05-23 05:15:51'),
(3, 'site_logo', '', 'general', '2026-05-23 05:15:51'),
(4, 'active_theme', 'dark-minimal', 'appearance', '2026-05-23 19:07:27'),
(5, 'primary_color', '#14a4c8', 'appearance', '2026-05-24 01:15:30'),
(6, 'affiliate_cpm', '0.50', 'earnings', '2026-05-23 05:15:51'),
(7, 'partner_rpm', '2.00', 'earnings', '2026-05-23 05:15:51'),
(8, 'min_payout', '25.00', 'earnings', '2026-05-24 06:02:36'),
(9, 'allow_register', '1', 'auth', '2026-05-23 05:15:51'),
(10, 'maintenance', '0', 'general', '2026-05-23 05:15:51'),
(30, 'dropdown_cat_limit', '50', 'appearance', '2026-05-23 21:34:21'),
(76, 'watch_time_rate_usd', '10', 'earnings', '2026-05-25 02:26:50'),
(77, 'min_withdrawal', '', 'earnings', '2026-05-25 02:26:50'),
(79, 'ad_revenue_per_click', '0', 'earnings', '2026-05-27 21:11:02'),
(80, 'currency_rates_json', '{\"USD\":1,\"INR\":83.5,\"PKR\":278,\"EUR\":0.92,\"GBP\":0.79,\"CAD\":1.36,\"AUD\":1.52,\"BDT\":110,\"AED\":3.67,\"SAR\":3.75}', 'earnings', '2026-05-24 03:27:18'),
(81, 'schema_version', '2', 'general', '2026-05-24 03:27:18'),
(3005, 'viewer_rate_usd', '10', 'earnings', '2026-05-25 02:26:50'),
(3006, 'creator_rate_usd', '20', 'earnings', '2026-05-25 02:26:50'),
(3012, 'video_approval_mode', 'manual', 'content', '2026-05-25 01:02:36'),
(3013, 'smtp_host', '', 'email', '2026-05-25 00:53:11'),
(3014, 'smtp_port', '587', 'email', '2026-05-25 00:53:11'),
(3015, 'smtp_user', 'admin@vidhost.local', 'email', '2026-05-25 02:30:36'),
(3016, 'smtp_pass', 'admin123', 'email', '2026-05-25 02:30:36'),
(3017, 'smtp_from_email', '', 'email', '2026-05-25 00:53:11'),
(3018, 'smtp_from_name', 'FreeHub', 'email', '2026-05-25 00:53:11'),
(3019, 'smtp_encryption', 'ssl', 'email', '2026-05-25 02:26:50'),
(3020, 'referral_bonus_usd', '0.5', 'earnings', '2026-05-27 21:11:02'),
(14542, 'meta_keywords', '', 'seo', '2026-05-25 04:23:01'),
(14543, 'meta_description', '', 'seo', '2026-05-25 04:23:01'),
(14544, 'google_analytics', '', 'seo', '2026-05-25 04:23:01'),
(14545, 'robots_txt', 'User-agent: *\r\nAllow: /\r\nSitemap: http://localhost/FreeHub.Live/sitemap.php', 'seo', '2026-05-25 04:23:01'),
(14546, 'og_image', '', 'seo', '2026-05-25 04:23:01'),
(52279, 'user_approval_mode', 'auto', 'content', '2026-05-27 19:26:18'),
(52280, 'creator_approval_mode', 'manual', 'content', '2026-05-27 19:26:18'),
(52712, 'adult_mode', '1', 'popup', '2026-05-27 19:39:43'),
(56456, 'creator_cpm', '0.5', 'earnings', '2026-05-27 21:11:02'),
(56457, 'creator_cpc', '5', 'earnings', '2026-05-27 21:11:02'),
(56458, 'viewer_cpm', '0.1', 'earnings', '2026-05-27 21:11:02'),
(56459, 'viewer_cpc', '2', 'earnings', '2026-05-27 21:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscriber_id` int(10) UNSIGNED NOT NULL,
  `channel_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `subscriber_id`, `channel_id`, `created_at`) VALUES
(4, 1, 10, '2026-05-28 16:13:35'),
(5, 11, 10, '2026-05-28 16:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `upload_sessions`
--

CREATE TABLE `upload_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `upload_sessions`
--

INSERT INTO `upload_sessions` (`id`, `video_id`, `user_id`, `token`, `created_at`) VALUES
(1, 23, 10, 'e2966e4f3ac6e38c44d2940f2e9f3799', '2026-05-28 07:18:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','affiliate','creator','viewer') NOT NULL DEFAULT 'viewer',
  `status` enum('active','suspended','pending','rejected') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `avatar` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `channel_name` varchar(100) DEFAULT NULL,
  `subscribers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_views` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_watch_seconds` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_ad_impressions` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_ad_clicks` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `lifetime_watch_earnings` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `lifetime_ad_earnings` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `balance` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `preferred_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `ref_code` varchar(20) DEFAULT NULL,
  `referred_by` int(10) UNSIGNED DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `first_name`, `last_name`, `phone`, `password`, `role`, `status`, `is_active`, `avatar`, `cover_image`, `bio`, `channel_name`, `subscribers`, `total_views`, `total_watch_seconds`, `total_ad_impressions`, `total_ad_clicks`, `lifetime_watch_earnings`, `lifetime_ad_earnings`, `balance`, `preferred_currency`, `ref_code`, `referred_by`, `email_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@freehub.live', 'zeeshan', 'Haider', NULL, '$2y$12$wyI9MD6oYQ/QHyidTl.6MOToDqCetxv4Pk0f.xYVqRxfb2i5hpy6q', 'admin', 'active', 1, 'd185846f4903e1ce54e13465e2a66fc0.jpg', '02d4f911b11c2b9277e420af3c4759d0.jpg', NULL, 'admin', 0, 0, 118, 104, 0, 0.0000, 0.0000, 0.0000, 'PKR', NULL, NULL, 1, '2026-05-28 14:32:33', '2026-05-23 05:15:51', '2026-05-28 17:36:31'),
(10, 'zeeshan', 'zeeshanh586@gmail.com', 'zeeshan', 'haider', '+923061881882', '$2y$12$/2zy/00u6EBdVSQnmzBnE.KWwhIHB5qUeo2qh.Qq2o0ooed85EKxu', 'creator', 'active', 1, '1ea2e180c76d9c37b068de37a2634a0d.jpg', '6c3b2543f99dc4cb95320d9c837b8c87.jpg', NULL, 'Zeeshan Haider', 2, 0, 0, 121, 0, 0.0000, 0.0417, 0.0417, 'USD', '9A9B9CAB', NULL, 0, '2026-05-28 15:06:56', '2026-05-26 21:08:44', '2026-05-28 23:10:50'),
(11, 'Viewer', 'viewer@gmail.ocm', NULL, NULL, '+923061881882', '$2y$12$ittedZDw9f7.P88KcEemgOrxbHPkTXI9OhqjuH/AidARIZYiPjB8G', 'viewer', 'active', 1, NULL, NULL, NULL, 'Viewer', 0, 0, 0, 3, 0, 0.0000, 0.0003, 0.0003, 'USD', 'FA46C8BE', NULL, 0, '2026-05-28 13:58:02', '2026-05-26 21:16:29', '2026-05-28 16:58:41');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `video_url` varchar(500) NOT NULL,
  `hls_url` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_size` bigint(20) UNSIGNED DEFAULT 0,
  `resolution` varchar(20) DEFAULT NULL,
  `status` enum('draft','pending','published','rejected','processing') NOT NULL DEFAULT 'pending',
  `approval_note` text DEFAULT NULL,
  `visibility` enum('public','unlisted','private') NOT NULL DEFAULT 'public',
  `views` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dislikes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `comments_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `watch_time` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `ad_impressions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ad_clicks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `revenue` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `user_id`, `category_id`, `title`, `slug`, `description`, `tags`, `thumbnail`, `video_url`, `hls_url`, `trailer_url`, `duration`, `file_size`, `resolution`, `status`, `approval_note`, `visibility`, `views`, `likes`, `dislikes`, `comments_count`, `watch_time`, `ad_impressions`, `ad_clicks`, `revenue`, `featured`, `allow_comments`, `scheduled_at`, `published_at`, `created_at`, `updated_at`) VALUES
(19, 10, 13, 'SATKAR - Blockbuster Hindi Dubbed Action Movie | Thalapathy Vijay, Katrina Kaif | New Hindi Dubbed', 'satkar-blockbuster-hindi-dubbed-action-movie-thalapathy-vijay-katrina-kaif-new-hindi-dubbed', '', '', '874c1351728d8b9325f1a210358d1e3b.jpg', 'https://www.youtube.com/watch?v=vNHvMznDbjQ', NULL, NULL, 0, 0, NULL, 'published', NULL, 'public', 1, 2, 0, 1, 0, 35, 0, 0.0000, 0, 1, NULL, '2026-05-27 17:56:22', '2026-05-27 20:50:58', '2026-05-28 17:36:11'),
(20, 10, 13, 'VIJAY - Full Movie in Hindi | Thalapathy Vijay | Sreeleela | Latest South Indian Action Movie 2026', 'vijay-full-movie-in-hindi-thalapathy-vijay-sreeleela-latest-south-indian-action-movie-2026', '', '', 'f96c3f88aeb80eeccc44236ad6431470.jpg', 'https://www.youtube.com/watch?v=231aDQmnjFw', NULL, NULL, 0, 0, NULL, 'published', NULL, 'public', 1, 0, 0, 0, 0, 16, 0, 0.0000, 0, 1, NULL, '2026-05-27 17:56:20', '2026-05-27 20:51:59', '2026-05-28 16:46:19'),
(21, 10, 13, 'CM VIJAY - full movie hindi Dubbed |Thalapathy vijay | pooja Hegade | New South movie 2026', 'cm-vijay-full-movie-hindi-dubbed-thalapathy-vijay-pooja-hegade-new-south-movie-2026', '', '', 'cd2ece2513a1f1b608c2dffbd1cae616.jpg', 'https://www.youtube.com/watch?v=kZ1SBMrX6dM', NULL, NULL, 0, 0, NULL, 'published', NULL, 'public', 1, 0, 0, 0, 0, 6, 0, 0.0000, 0, 1, NULL, '2026-05-27 17:56:17', '2026-05-27 20:53:03', '2026-05-28 17:25:01'),
(22, 10, NULL, 'Asuran | धनुष की ब्लॉकबस्टर साउथ एक्शन हिंदी फिल्म | Manju Warrier, Prakash Raj, Pasupathy', 'asuran-manju-warrier-prakash-raj-pasupathy', '', '', 'eec288d32f57794ba62e45105d940743.jpg', 'https://www.youtube.com/watch?v=zZ-_MD2f63U', NULL, NULL, 0, 0, NULL, 'published', NULL, 'public', 1, 1, 0, 3, 0, 18, 0, 0.0000, 0, 1, NULL, '2026-05-27 17:56:15', '2026-05-27 20:54:49', '2026-05-28 17:58:23'),
(23, 10, 13, 'Arbitrage__Free_Money_', 'arbitrage-free-money', '', 'FUNNY', '70677980412184423f5e4c64b6c9815c.jpg', '21e642409b4d0c0d801a7e4d5ace9952.mp4', NULL, NULL, 509, 61739783, NULL, 'published', NULL, 'public', 1, 0, 0, 0, 3, 4, 0, 0.0000, 0, 1, NULL, '2026-05-28 04:19:56', '2026-05-28 07:18:26', '2026-05-28 17:47:49');

-- --------------------------------------------------------

--
-- Table structure for table `video_categories`
--

CREATE TABLE `video_categories` (
  `video_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `video_categories`
--

INSERT INTO `video_categories` (`video_id`, `category_id`) VALUES
(19, 13),
(20, 13),
(21, 13);

-- --------------------------------------------------------

--
-- Table structure for table `video_reactions`
--

CREATE TABLE `video_reactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('like','dislike') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `video_reactions`
--

INSERT INTO `video_reactions` (`id`, `video_id`, `user_id`, `type`, `created_at`) VALUES
(6, 22, 1, 'like', '2026-05-28 15:59:18'),
(7, 19, 11, 'like', '2026-05-28 16:58:47'),
(8, 19, 10, 'like', '2026-05-28 17:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `video_views`
--

CREATE TABLE `video_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `affiliate_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_hash` varchar(64) NOT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `watch_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_unique` tinyint(1) NOT NULL DEFAULT 1,
  `ref_code` varchar(20) DEFAULT NULL,
  `country` varchar(3) DEFAULT NULL,
  `device` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `video_views`
--

INSERT INTO `video_views` (`id`, `video_id`, `user_id`, `affiliate_id`, `ip_hash`, `session_id`, `watch_seconds`, `is_unique`, `ref_code`, `country`, `device`, `created_at`) VALUES
(16, 21, 1, NULL, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 0, 1, NULL, NULL, 'mobile', '2026-05-27 21:04:42'),
(17, 19, 1, NULL, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 0, 1, NULL, NULL, 'mobile', '2026-05-27 21:06:17'),
(18, 22, 1, NULL, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 0, 1, NULL, NULL, 'desktop', '2026-05-28 00:01:44'),
(19, 20, 1, NULL, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 0, 1, '375057C6', NULL, 'desktop', '2026-05-28 00:03:10'),
(20, 23, 1, 10, 'af283e841ae11ccc0d7da03c318a86ec65cf20a8c6c169b80bbd71bf3cc31c2e', NULL, 3, 1, '9A9B9CAB', NULL, 'desktop', '2026-05-28 14:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `watch_history`
--

CREATE TABLE `watch_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `watch_position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_watched` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `watch_history`
--

INSERT INTO `watch_history` (`id`, `user_id`, `video_id`, `watch_position`, `last_watched`) VALUES
(62, 1, 23, 3, '2026-05-28 14:54:48');

-- --------------------------------------------------------

--
-- Table structure for table `watch_later`
--

CREATE TABLE `watch_later` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `video_id` int(10) UNSIGNED NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `watch_later`
--

INSERT INTO `watch_later` (`id`, `user_id`, `video_id`, `added_at`) VALUES
(1, 1, 22, '2026-05-28 15:34:09'),
(2, 1, 19, '2026-05-28 16:54:52'),
(3, 1, 20, '2026-05-28 16:55:02'),
(4, 1, 23, '2026-05-28 16:55:07'),
(5, 11, 19, '2026-05-28 16:58:45'),
(6, 10, 19, '2026-05-28 17:09:58'),
(7, 10, 22, '2026-05-28 17:10:12'),
(8, 10, 21, '2026-05-28 17:10:28');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,4) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(80) NOT NULL,
  `payment_details` text NOT NULL,
  `country` varchar(80) DEFAULT NULL,
  `status` enum('pending','processing','paid','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `due_by` date DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_placement` (`placement`),
  ADD KEY `idx_device` (`device_target`);

--
-- Indexes for table `ad_logs`
--
ALTER TABLE `ad_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ad` (`ad_id`),
  ADD KEY `idx_video` (`video_id`),
  ADD KEY `idx_viewer` (`viewer_id`),
  ADD KEY `idx_creator` (`creator_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_ip_date` (`ip_hash`,`created_at`);

--
-- Indexes for table `ad_placements`
--
ALTER TABLE `ad_placements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `affiliate_clicks`
--
ALTER TABLE `affiliate_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_affiliate` (`affiliate_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_video` (`video_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_comm_vid_status_created` (`video_id`,`status`,`created_at`);

--
-- Indexes for table `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `footer_sections`
--
ALTER TABLE `footer_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`,`is_read`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_pages_footer_section` (`footer_section_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_playlist_video` (`playlist_id`,`video_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pv` (`playlist_id`,`video_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `referral_conversions`
--
ALTER TABLE `referral_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_referrer` (`referrer_id`),
  ADD KEY `idx_referred` (`referred_user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sub` (`subscriber_id`,`channel_id`),
  ADD KEY `channel_id` (`channel_id`);

--
-- Indexes for table `upload_sessions`
--
ALTER TABLE `upload_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_video` (`video_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_ref` (`ref_code`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_views` (`views`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_vid_status_visibility_pub` (`status`,`visibility`,`published_at`),
  ADD KEY `idx_vid_status_visibility_views` (`status`,`visibility`,`views`),
  ADD KEY `idx_vid_feat_status_vis_pub` (`featured`,`status`,`visibility`,`published_at`),
  ADD KEY `idx_vid_cat_status_vis_views` (`category_id`,`status`,`visibility`,`views`);
ALTER TABLE `videos` ADD FULLTEXT KEY `ft_search` (`title`,`description`,`tags`);

--
-- Indexes for table `video_categories`
--
ALTER TABLE `video_categories`
  ADD PRIMARY KEY (`video_id`,`category_id`),
  ADD KEY `idx_vc_cat_vid` (`category_id`,`video_id`);

--
-- Indexes for table `video_reactions`
--
ALTER TABLE `video_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reaction` (`video_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `video_views`
--
ALTER TABLE `video_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_video` (`video_id`),
  ADD KEY `idx_affiliate` (`affiliate_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wh` (`user_id`,`video_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `watch_later`
--
ALTER TABLE `watch_later`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wl` (`user_id`,`video_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ad_logs`
--
ALTER TABLE `ad_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- AUTO_INCREMENT for table `ad_placements`
--
ALTER TABLE `ad_placements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `affiliate_clicks`
--
ALTER TABLE `affiliate_clicks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1298;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `earnings`
--
ALTER TABLE `earnings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `footer_sections`
--
ALTER TABLE `footer_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlist_items`
--
ALTER TABLE `playlist_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referral_conversions`
--
ALTER TABLE `referral_conversions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80065;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `upload_sessions`
--
ALTER TABLE `upload_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `video_reactions`
--
ALTER TABLE `video_reactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `video_views`
--
ALTER TABLE `video_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `watch_history`
--
ALTER TABLE `watch_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `watch_later`
--
ALTER TABLE `watch_later`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affiliate_clicks`
--
ALTER TABLE `affiliate_clicks`
  ADD CONSTRAINT `affiliate_clicks_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `earnings`
--
ALTER TABLE `earnings`
  ADD CONSTRAINT `earnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD CONSTRAINT `playlist_items_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_items_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  ADD CONSTRAINT `playlist_videos_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_videos_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `videos_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `video_categories`
--
ALTER TABLE `video_categories`
  ADD CONSTRAINT `video_categories_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_reactions`
--
ALTER TABLE `video_reactions`
  ADD CONSTRAINT `video_reactions_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_views`
--
ALTER TABLE `video_views`
  ADD CONSTRAINT `video_views_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD CONSTRAINT `watch_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_history_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_later`
--
ALTER TABLE `watch_later`
  ADD CONSTRAINT `watch_later_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_later_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
