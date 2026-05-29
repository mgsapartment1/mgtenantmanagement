-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2026 at 07:11 AM
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
-- Database: `onlinetenantmanagement`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `property_id`, `title`, `content`, `expiry_date`, `created_at`) VALUES
(1, NULL, '𝐒𝐂𝐇𝐄𝐃𝐔𝐋𝐄𝐃 𝐏𝐎𝐖𝐄𝐑 𝐈𝐍𝐓𝐄𝐑𝐑𝐔𝐏𝐓𝐈𝐎𝐍 𝗗𝗮𝘁𝗲: May 6, 2026, Wednesday 𝗧𝗶𝗺𝗲: 7:00 AM to 5:00 PM', '𝗔𝗳𝗳𝗲𝗰𝘁𝗲𝗱 𝗔𝗿𝗲𝗮𝘀:\r\nAll barangays of:\r\nTalavera, Sto. Domingo, Aliaga, Quezon, Licab, Munoz, Guimba, Talugtug, Lupao and Carranglan\r\n(lahat ng nasasakupan ng NEECO II - Area 1)\r\n𝗔𝗰𝘁𝗶𝘃𝗶𝘁𝗶𝗲𝘀:\r\n-To implement wood pole replacement program along Cabanatuan - San Luis 69kV line.\r\n-To implement wood pole replacement program along Cabanatuan - Fatima 69kV line.\r\n-Correction of oil leak of 300MVA, Transformer no. 1 (8Z-XF01CBN) at Cabanatuan SS.\r\n-Installation of 13.8kV, surge arresters at station service no. 1 at Cabanatuan SS.\r\n-Installation of 13.8kV, surge arresters at station service no. 1 at Cabanatuan SS.\r\n-Preventive maintenance of relay protection.\r\n-General maintenance of high voltage equipment.\r\nPaalala:\r\nAng power interruption na ito ay 𝗵𝗶𝗻𝗱𝗶 𝘀𝗮𝗸𝗹𝗮𝘄 𝗻𝗴 𝗸𝗼𝗻𝘁𝗿𝗼𝗹 ng ating kooperatiba. Ito ay sa bahagi ng 𝘁𝗿𝗮𝗻𝘀𝗺𝗶𝘀𝘀𝗶𝗼𝗻 𝗹𝗶𝗻𝗲𝘀 na pinangangasiwaan ng 𝗡𝗮𝘁𝗶𝗼𝗻𝗮𝗹 𝗚𝗿𝗶𝗱 𝗖𝗼𝗿𝗽𝗼𝗿𝗮𝘁𝗶𝗼𝗻 𝗼𝗳 𝘁𝗵𝗲 𝗣𝗵𝗶𝗹𝗶𝗽𝗽𝗶𝗻𝗲𝘀. Wala po tayong direktang kontrol dito dahil ang 𝗮𝘁𝗶𝗻𝗴 𝘀𝘂𝗽𝗹𝗮𝘆 𝗻𝗴 𝗸𝘂𝗿𝘆𝗲𝗻𝘁𝗲 𝗮𝘆 𝗱𝘂𝗺𝗮𝗱𝗮𝗮𝗻 𝗺𝘂𝗻𝗮 𝘀𝗮 𝗸𝗮𝗻𝗶𝗹𝗮𝗻𝗴 𝘁𝗿𝗮𝗻𝘀𝗺𝗶𝘀𝘀𝗶𝗼𝗻 𝗹𝗶𝗻𝗲𝘀 𝗯𝗮𝗴𝗼 𝗺𝗮𝗸𝗮𝗿𝗮𝘁𝗶𝗻𝗴 𝘀𝗮 𝗮𝘁𝗶𝗻𝗴 𝗺𝗴𝗮 𝘀𝘂𝗯𝘀𝘁𝗮𝘁𝗶𝗼𝗻𝘀.\r\n𝑳𝒊𝒏𝒆 𝒘𝒐𝒓𝒌𝒔 𝒎𝒂𝒚𝒃𝒆 𝒇𝒊𝒏𝒊𝒔𝒉𝒆𝒅 𝒆𝒂𝒓𝒍𝒊𝒆𝒓 𝒐𝒓 𝒍𝒂𝒕𝒆𝒓 𝒕𝒉𝒂𝒏 𝒔𝒄𝒉𝒆𝒅𝒖𝒍𝒆𝒅 𝒔𝒐 𝒄𝒐𝒏𝒔𝒊𝒅𝒆𝒓 𝒐𝒖𝒓 𝒍𝒊𝒏𝒆𝒔 𝒆𝒏𝒆𝒓𝒈𝒊𝒛𝒆𝒅 𝒂𝒕 𝒂𝒍𝒍 𝒕𝒊𝒎𝒆𝒔', '2026-05-06', '2026-05-06 11:34:37');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(255) DEFAULT NULL,
  `action` text NOT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `admin_id`, `admin_name`, `action`, `affected_table`, `created_at`) VALUES
(8, 1, 'MG Landlord', 'Approved payment for Security Deposit (ID: 1)', 'bills', '2026-05-29 04:23:15'),
(9, 1, 'MG Landlord', 'Approved payment for Advance Rent (ID: 2)', 'bills', '2026-05-29 04:25:58'),
(10, 0, 'MG Landlord', 'Added Rent bill for User ID 6 (Amount: 6800)', 'bills', '2026-05-29 05:05:52');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bill_type` enum('Rent','Water','Electricity','Security Deposit','Advance Rent') DEFAULT NULL,
  `previous_reading` decimal(10,2) DEFAULT 0.00,
  `current_reading` decimal(10,2) DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `date_paid` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `penalty` decimal(10,2) DEFAULT 0.00,
  `notified_5days` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `property_id`, `user_id`, `bill_type`, `previous_reading`, `current_reading`, `rate`, `amount`, `due_date`, `status`, `proof_of_payment`, `date_paid`, `payment_method`, `reference_no`, `receipt_image`, `penalty`, `notified_5days`) VALUES
(1, NULL, 5, 'Security Deposit', 0.00, 0.00, 0.00, 7000.00, '2026-05-29', 'Paid', '1780028148_1.jpg', '2026-05-29 12:23:11', 'GCash', '0000000000001', NULL, 0.00, 0),
(2, NULL, 5, 'Advance Rent', 0.00, 0.00, 0.00, 7000.00, '2026-05-29', 'Paid', NULL, '2026-05-29 12:25:53', 'Cash', 'CASH-6A1915496EA45', NULL, 0.00, 0),
(3, NULL, 6, 'Security Deposit', 0.00, 0.00, 0.00, 6800.00, '2026-05-29', 'Unpaid', NULL, NULL, NULL, NULL, NULL, 0.00, 0),
(4, NULL, 6, 'Advance Rent', 0.00, 0.00, 0.00, 6800.00, '2026-05-29', 'Unpaid', NULL, NULL, NULL, NULL, NULL, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `lease_requests`
--

CREATE TABLE `lease_requests` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `request_type` enum('Renew','Move-out') NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `preferred_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `transaction_type` enum('rent','utility_water','utility_electric','penalty','payment') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partially_paid','paid','overdue') DEFAULT NULL,
  `calculated_via` enum('manual','cron_job_monthly','cron_job_penalty') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `issue_details` text DEFAULT NULL,
  `issue_image` varchar(255) DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Low',
  `status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `admin_remarks` text DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `status`, `created_at`) VALUES
(9, 3, 'Application Approved', 'Congratulations Erika Pinto! Your application for Unit S104 has been APPROVED.', 'read', '2026-05-29 11:34:59'),
(10, 4, 'Application Approved', 'Congratulations Julius Mangahas! Your application for Unit S103 has been APPROVED.', 'unread', '2026-05-29 11:47:36'),
(11, 4, 'Application Approved', 'Congratulations Julius Mangahas! Your application for Unit S103 has been APPROVED.', 'unread', '2026-05-29 11:51:34'),
(12, 5, 'Application Approved', 'Congratulations Erika Pinto! Your application for Unit S104 has been APPROVED.', 'read', '2026-05-29 12:12:06'),
(13, 5, 'Payment Approved ₱7,000.00', 'Your payment for Security Deposit (₱7,000.00) via GCash is now APPROVED. You can now view and download your Official Receipt.', 'read', '2026-05-29 12:23:11'),
(14, 5, 'Payment Approved ₱7,000.00', 'Your payment for Advance Rent (₱7,000.00) via Cash is now APPROVED. You can now view and download your Official Receipt.', 'read', '2026-05-29 12:25:53'),
(15, 6, 'Application Approved', 'Congratulations Julius Mangahas! Your application for Unit S103 has been APPROVED.', 'read', '2026-05-29 12:58:58'),
(16, 6, NULL, 'New Bill: Mayroon kang bagong billing para sa Rent (₱6,800.00). Due date: May 31, 2026', 'read', '2026-05-29 13:05:52');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `property_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `ref_no` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `date_paid` datetime DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `user_id`, `bill_id`, `amount`, `method`, `ref_no`, `receipt_image`, `date_paid`, `payment_date`) VALUES
(3, 5, 1, 7000.00, 'GCash', '0000000000001', '1780028148_1.jpg', '2026-05-29 12:23:11', '2026-05-29 04:23:11'),
(4, 5, 2, 7000.00, 'Cash', 'CASH-6A1915496EA45', '', '2026-05-29 12:25:53', '2026-05-29 04:25:53');

-- --------------------------------------------------------

--
-- Table structure for table `rent_requests`
--

CREATE TABLE `rent_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `lease_start` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_requests`
--

INSERT INTO `rent_requests` (`id`, `user_id`, `unit_id`, `request_date`, `status`, `lease_start`) VALUES
(1, 5, 4, '2026-05-29 04:11:55', 'Approved', '2026-05-29'),
(2, 6, 3, '2026-05-29 04:58:46', 'Approved', '2026-05-29');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `property_id`, `setting_key`, `setting_value`) VALUES
(1, 1, 'house_rules', '<h2><strong>OFFICIAL HOUSE RULES &amp; RENTAL POLICIES</strong></h2><h3><strong>I. RENTAL &amp; FINANCIAL TERMS</strong></h3><p><strong>MONTHLY RENT:</strong> Must be settled on or before the agreed due date.</p><p><strong>PENALTY:</strong> A late fee of <strong>₱100.00</strong> will be applied automatically for payments made after the grace period.</p><h3><strong>II. BUILDING POLICIES</strong></h3><p><strong>ALCOHOL &amp; DRUNKENNESS:</strong> Strictly <strong>NO DRINKING</strong> of alcoholic beverages within the apartment premises (units and common areas).</p><p><strong>ACCESS &amp; NO CURFEW:</strong> Tenants have 24/7 access to the building. Please ensure the main gate is locked at all times for security.</p><p><strong>QUIET HOURS:</strong> Minimize noise from <strong>10:00 PM to 6:00 AM</strong>.</p><h3><strong>III. PRE-APPROVAL REQUIREMENTS</strong></h3><p><strong>Note:</strong> Applicants must provide clear copies of the following documents for verification before the application can be approved:</p><p><strong>Valid Government ID</strong> (e.g., UMID, Passport, Driver\'s License)</p><p><strong>Latest Police Clearance</strong></p><p><strong>Barangay Clearance</strong></p><p><strong>NBI Clearance</strong></p><p><i>Failure to comply with these rules may result in lease termination.</i></p>');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `unit_no` varchar(50) DEFAULT NULL,
  `unit_type` enum('Studio','1BR','2BR','3BR','Commercial') DEFAULT NULL,
  `floor_area` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `monthly_rent` decimal(10,2) DEFAULT NULL,
  `amenities` varchar(255) DEFAULT NULL,
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `unit_image` varchar(255) DEFAULT NULL,
  `image_name` varchar(100) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `property_id`, `unit_no`, `unit_type`, `floor_area`, `description`, `monthly_rent`, `amenities`, `status`, `unit_image`, `image_name`) VALUES
(1, NULL, 'S101', 'Studio', '18sqm', 'Cozy studio unit perfect for solo living.', 6500.00, 'Free WiFi, CCTV, Sub-metered Electric', 'Available', NULL, 'S101.png'),
(2, NULL, 'S102', 'Studio', '18sqm', 'Standard studio unit with basic kitchen sink.', 6500.00, 'Free WiFi, CCTV, Sub-metered Electric', 'Available', NULL, 'S102.png'),
(3, NULL, 'S103', 'Studio', '18sqm', 'Near the window, well-ventilated studio.', 6800.00, 'Free WiFi, CCTV, Better Ventilation', 'Occupied', NULL, 'S103.png'),
(4, NULL, 'S104', 'Studio', '20sqm', 'Slightly larger studio unit near the entrance.', 7000.00, 'Free WiFi, CCTV, Larger Space', 'Occupied', NULL, 'S104.png'),
(5, NULL, 'S105', 'Studio', '18sqm', 'Quiet studio unit at the end of the hallway.', 6500.00, 'Free WiFi, CCTV, Privacy', 'Maintenance', NULL, 'S105.png'),
(6, NULL, 'S106', 'Studio', '18sqm', 'Budget-friendly studio unit.', 6300.00, 'Free WiFi, CCTV', 'Maintenance', NULL, 'S106.png'),
(7, NULL, 'B201', '1BR', '', '', 10500.00, 'Own Electric Meter, Aircon Ready', 'Maintenance', NULL, 'B201.png'),
(8, NULL, 'B202', '1BR', '30sqm', 'Modern 1-bedroom unit with built-in cabinet.', 11000.00, 'Own Electric Meter, Cabinets included', 'Maintenance', NULL, 'B202.png'),
(9, NULL, 'F301', '2BR', '45sqm', 'Family unit with 2 bedrooms and private balcony.', 15000.00, 'Balcony, Own Gate, Parking Space', 'Maintenance', NULL, 'F301.png'),
(10, NULL, 'F302', '2BR', '45sqm', 'Large 2-bedroom unit with spacious dining area.', 15000.00, 'Own Gate, Parking Space, Pet Friendly', 'Maintenance', NULL, 'F302.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tenant') DEFAULT 'tenant',
  `assigned_unit_id` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `valid_id` varchar(255) DEFAULT NULL,
  `police_clearance` varchar(255) DEFAULT NULL,
  `brgy_clearance` varchar(255) DEFAULT NULL,
  `nbi_clearance` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `rules_accepted` tinyint(1) DEFAULT 0,
  `contract_signed_at` datetime DEFAULT NULL,
  `contract_signed_date` datetime DEFAULT NULL,
  `digital_signature` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_preference` varchar(10) DEFAULT 'light',
  `contract_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `property_id`, `full_name`, `email`, `contact`, `emergency_contact`, `password`, `role`, `assigned_unit_id`, `profile_pic`, `valid_id`, `police_clearance`, `brgy_clearance`, `nbi_clearance`, `is_archived`, `rules_accepted`, `contract_signed_at`, `contract_signed_date`, `digital_signature`, `created_at`, `theme_preference`, `contract_end_date`) VALUES
(1, NULL, 'MG Landlord', 'admin@gmail.com', '09123456789', NULL, '$2y$10$86asR/s4.S6k1zS.L7Gvhu7A3G6mH6Yx3GzZ.F7G8H9I0J1K2L3M4', 'admin', NULL, 'default.png', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-04-27 10:38:20', 'light', NULL),
(2, NULL, 'System Admin', 'superadmin@gmail.com', NULL, NULL, '$2y$10$IbT8U38wM.4lVfy0/bJf8uWbyFIUUl0dkiXJZibRmd4SaXXeoVVRe', 'admin', NULL, 'default.png', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-05-06 06:29:14', 'light', NULL),
(5, NULL, 'Erika Pinto', 'pintoerika0200@gmail.com', '09484191469', 'Julius Mangahas - 09123870910', '$2y$10$3U8UNhdebrWV.IPkyn8e/.kN/2RsLgqiSZwDfq3ZCr64CM2wwMAya', 'tenant', 4, '1780027915_prof_476586851_1709435766302457_678948275851531109_n.jpg', '1780027915_id_476599504_3865713960351378_7349066084518617761_n.jpg', '1780027915_police_475965602_1638000580127292_4248564717065263580_n.jpg', '1780027915_brgy_475832738_666046052459853_1745233391935633105_n.jpg', '1780027915_nbi_baby.png', 0, 1, NULL, '2026-05-29 06:12:43', 'data:image/png;base64,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', '2026-05-29 04:11:55', 'light', NULL),
(6, NULL, 'Julius Mangahas', 'anonymousking01234@gmail.com', '09123870910', 'Erika 09453473990', '$2y$10$CC6MdnPGuFw4vJ5t83.Pm.ykPFj2dM4al31a6xk65blAWSx9z2sMO', 'tenant', 3, '1780030726_prof_476053842_28628225553489239_7210494042197029309_n.jpg', '1780030726_id_476599504_3865713960351378_7349066084518617761_n.jpg', '1780030726_police_475832738_666046052459853_1745233391935633105_n.jpg', '1780030726_brgy_476053842_28628225553489239_7210494042197029309_n.jpg', '1780030726_nbi_us.png', 0, 1, NULL, '2026-05-29 06:59:36', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcIAAABuCAYAAAC0sRNkAAAQAElEQVR4AezdS8h9X13H8VNkVCQpGBR0hSRrVLMiw6KJjXRQ+W9UgfOEkiIH1aAbChqN/gOxIEjIgY6sUYmBNugC0RWjKxUpWCBeUNTv6+G3HteznnOe37nss8/e53we9vdZa6/rd73X77c+z9pn732+fJOfEAiBEAiBELhhAhHCG578DD0EQiAEQmCziRDe0r+CjDUEQiAEQuARgQjhIyRJCIEQCIEQuCUCEcJbmu2M9ZYIZKwhEAJ7EogQ7gkqxUIgBEIgBK6TQITwOuc1owqBELglAhnrSQQihCfhS+UQCIEQCIG1E4gQrn0G438IhEAIhMBJBFYmhCeNNZVDIARCIARC4BGBCOEjJEkIgRAIgRC4JQIRwlua7ZWNNe6GQAiEwBwEIoRzUE4fIRACIRACiyUQIVzs1MSxELglAhlrCFyOQITwcuzTcwiEQAiEwAIIRAgXMAlxIQRCIARuicDSxhohXNqMxJ8QCIEQCIFZCUQIZ8WdzkIgBEIgBJZGIEJ4zhlJ2yEQAiEQAosnECFc/BTFwRAIgRAIgXMSiBCek27aviUCGWsIhMBKCUQIVzpxcTsEQiAEQmAaAhHCaTimlRAIgVsikLFeFYEI4VVNZwYTAiEQAiFwKIEI4aHEUj4EQiAEQuCqCDxHCK9qrBlMCIRACIRACDwiECF8hCQJIRACIRACt0QgQnhLs/2csSY7BEIgBG6RQITwFmc9Yw6BEAiBELgnECG8R5FICBxM4NuqxnvLPl72prIVHXE1BEKgEYgQNhIJQ+D5BF6oIn9a9vdlXyj7l7LXlb2s7K1lOUIgBFZIIEK4wkmLy7MTsPP7k+r1D8peU/aqsvH41zEh5yGwFALx42kCEcKn+SQ3BH66ENj5/VCF40H8frUSv6zslWU5QiAEVkggQrjCSYvLsxEgfu/a0dsnKv3by36lLEcIhMCKCVyXEK54IuL6Igm4HLrNsf+rxJeW5QiBELgCAhHCK5jEDOEsBLbtBF0K/Znq7eVlOUIgBK6EQITwSibyBodxziG7OcZng30fRPCHK+F3y3KEQAhcEYEI4RVNZoZyMAGC53NAosd83if8/S0t2QkSwy1ZSQqBEFgzgQjhmmcvvu8iQOCImsubnvdj7vxs9ldVsaX5HFA59suVLvyBCsdDuf+uRG0oQzCJaCXlODuBdBACZyQQITwj3DQ9OwHi9KnqlVgRNed1encQx2bfc5dy+K9vqCra0C4xJI76Ekqr7BwhEAJrIxAhXNuMxd9dBAgT+6qhgMuZ3gbD+izpo/X5+8YJo52hvu1C962XciEQAtsJzJ4aIZwdeTqcmAAh2rYjc1OLz/U86+cmF+bB92bSR5OnzujimytButAD9NoehbWKbOxCXT4ljM5jIRACKyAQIVzBJMXFJwnYifXC8+Eq3QSNYNXpQYdd4ljhFZWgrbdVaNdHFJuwElPiWFl3h8unXsV2d5JfIRACyycQIbzgHKXrkwmMImiX9v0ntqoNb43pm3lDf7JH/M/2KJMiIRACCyEQIVzIRMSNowj0N6gQMLu0oxoaKv3FcO7ya+urxe0MXZJ1s4xLoqrYTfLhx53EQiAE1kEgQriOebo2L4mJy5nEhaAwcSavt11jV7bPI0D9+SlxbRO1vo2318mHygifnSjxM4ZK2ijr8qjLpAR58/gnKSEQAkslECFc6sys0y8C9upy/R1lPlN7f4We2fP9fR+t+GfKxuf3CAojLozQ9Kb8aB6RULaauzv0dReZ6Bdh+8DQlu8c/L5naf9TIeEjvj6PJIDEvJJzhEAIrI1AhHBtM3Z5f4mdHZOF36XBXqQI2AfLxZ8t+6my15Z5Zs/397nh5CvrfIpjfETivyZo1Lj6MfF/W7NeuP1iZRDf7P4KRI7HBJKyLgIRwnXN1yW8JXp2eESC6BE7uzG7uHZp8BJ+9X2+pT/ZI070mDE1MTeufkx2hb9Xbdn9VXB/2Bkqp5762rnPTCQEQmB9BCKE65uzOT0mfETPDs/if86+P1aNs/+s8M/LXJp8X4W/VeYy5G9X6Dk+jy5U9P54aldGpBjBJuiEi4ARPWZM8jRG+LSlr3a5U51vrMz3lI2HdtXXjmcHx/ych0AIrITAaUK4kkHGzaMIEI1jKtpBERVGWP6hGvnrMpcSiUwzguYzNp+vEZ6vrzLsmyv0WRyBen3Ff7GML2+q0HN82qzo/fH/FZNPsIkcI+CMSDFp8gmXdqvKhn/Np+aHUFub4cddoD9ZacqrV9EHh2cHtf8gMSchEALrIBAhXMc8XcLLUXB6Hz5XJ58t6w8CQdTsoISMsHxXFfreMsJHZJoRFX2oV9l7HXZhdmmf7kq/ruIETjqRY5X04LDLJMhEmB+El3/i/OHHgwpbTt5dacobk3bq9MGhf4L7IDEnIRACyycQIVz+HF3KQ+Jg4SdUdnm9H19RJy8pa4e7OH2epmxLmyIkfISKtR0e0Rtvlun74gOhYkSL6NllEmTtEOC+/KFx7WuHkL5zqEyE7UD5PWTlNARCYKkEIoRLnZll+EU0LPhtl0cct3n21ZVIoAhBRY8+CIg2CA1BYdplTzVKhJvo8Vd9tsvfp9raN48gvrEKE9wK7g9jyM7wHkciIbB8AhHC5c/RUjy08BMbQmPx97nf6NuhwkM0XFIkWkSPERHCJ29s3zk/3Dgj3uxbK3Jo31VlkoPvePSN8d0NNMYin7j3+cuPx8MQuCECEcIbmuyJhkqI7BQ/MrTnZpIhaespkXhv5bRLnW4yeUr4quhGn8SGCDM3zvTCp83NBX+IHb98FtnccAMNATQ2Y3yhZSQMgRBYFoEI4bLmYw3e/GY5aef2YxX2x2/UCUGoYOtBrAiCum5w2VroWWITPuLnMz4io23pz4ps5LW4tu0s2/klQr7t+mOAf76Rwlt2LuFb+gyBpwjcfF6E8Ob/CRwEgJD9wo4aFnu7H4Il3oo5J35sl1gREcLGeuFTt7UzhnaErKXru8UvFfrWCaLtJiNjsXPuffGWHbvEPi3xEAiBCxOIEF54AlbUPRHshcyrxtqC3w+DIHnNGpFy+dN5L4zKuoTojkufOe4rfOqN5iaZlqaPp4SzlTt3SNQJIF/+eEtn8rckJykEQuBSBG5KCC8F+Qr6ddNHL4KeyXt5jast+HZB/QL/TZX3mrLxsEsifh5ncMclsRzLHHKuft+v94P2fh7S1jnKulzct2vsvb99XuIhEAIXIhAhvBD4lXTrMh4RFDaXiY9n8tq50OLudWjiu0wZeeoLpzBtEtfWll2hHWjvb8ubO7SD5k/r17hZO08YAiGwEAIRwoVMxALdcFPMKIJEx65mdNei732kY3p/ThSIlM8KlZ9KrOxK+dX60s+7NpvNJXeGLouO/buMvMlPCITA8ghECJc3J5f2iJAQwPGmGAJogR/9U7Yt+nZoFnyXSonTth2Q9pVXjyiKj20eer5NDInuFG0f4ouxGZe++3pYYNOnJR4CIbAQAhHChUzEQtywSyNOwuaSB+cJ2yhqbdFvZS30xJIoiRNN5+oSgrG+9rVhd6hP5Vtb8g4x/elXP62etgnSXGKoPyI4jsG4ja35lfAMBNJkCJxCIEJ4Cr3rqmsRZ/2ovMHFC7MJTZ9u0fdcXFv0P1yZBG8sV8kbaYTgxc1m4zk7jxhU9MGhPaKlf29k+cPK/fky6RXsdbR+ej/U/52q7TJvBWc7iDAx11/fCRH0x0CflngIhMDCCEQIFzYhF3CHmFnEha17CzhB8QaXltaHRMtXJbU0b1GxsyN4TJyoaZd5jIJwsle3SjtCbXlY/62Vr65vuWD/W+f/VPb+Mn3Y6TF+9wJEEImPsIpuvrZ+ucyrTl+ukk8+9G9s7lYdG7M75ceYnvMQCIGTCExfOUI4PdM1tdgEqxeItoA3Idk2ng9UoucIK7g71CcKBJKJ9wKlLY9c/FGVtnvq7T2V5vLr+A0XlXx3+KYL5rsKX1kpry3TB99ZE1yC1Eya9lgVvzvU+VDF3lF26mG8+tD/2JaxEkDCO+blPARCYIEEIoQLnJQZXLLT+4/qh2BVcHccsoATMs8Rehie2T26SYaICgmBtN48cvGj1ZP83nzprcuv7Rsu1JWvD2Z36gH8f666245PVKIv51WGoNbphlDZsdpdbrof5+5utcP0ujNChgHR7oo9GVXWTlU4FtS/MfN5zMt5CITAQglECBc6MZvN5hyeEQg7mbdX4x56r+DuIDinLOBEVBt2QUJCII3ddbDnL+XV1QYxZITRA/jfUW3wkRFc5Srp7tLn11XEeLzCTBt2mW+uNPWZc+mVdHfYYSpLBIkhJsRNXNo2kWvslL1rZPjFJ4I+JOc0BEJg6QQihEufoen8s8Bb7PtF3uVKQkMspuvpfC0RM0Zw+U0U+d5EUc8Eq33G6HIo+9vKUE55N/bU6f3x6Wcx9TAihsQOK6G+CLPznt2zaht92xUrt8lPCITA+ghECNc3Z4d6bPG2oFvg+7p2SS5XWsj79DXFiSKRaqIotDNrYyJujBhiwIzPZ5xC1r7t/mN14o7Wvi526m67GaaKb/THxE+z1A6BELgYgQjhxdDP0rFdisXfgt53aPH22VyftvY4USRixmx8dn92gYRSuvERRZ8dtveg9jf8vKIKuKNVmV+vuN1yBU8e/rjAV2g3OXJ+snIyQyAElkEgQriMeZjaC4u5Bdpupm+bWBCJJgx93rXFjZUIEkNj3iaML9syaOx+qdJfVTYe7kL9aJeoLPEjgsQQc5dQhQRZXlc80RC4I5BfCyMQIVzYhEzgjsXXYizsmyMKxOAWRLAfd4tvE0YC6aUB8lq5beHnK/Hvynzt009UiGN/CbaS7o8mjv4IIYjmgihKvy+USAiEwHIIRAiXMxdTeOKRAItv35ZF3oJvZ9Sn33qcMPnsz+MU4j0PzyN+rkvw/+S769yD+fj+TcV/pMxnjd6Wg+2uPzC03UTRrnH8A6WayRECIXBJAv6Dn63/NDwbAQusxdsjAX2nFme7F2GffqtxomR3hhVBc0mzsfAHg10eXv5fvKQyxD2G4caiOr0/vK3G54kEztty8Hcp1R2pbrixe7wv/Cyib/3pV/kI4jMwCULg0gT8h7+0D+n/eAIWVou6cGzFom4nOKbf4jkRIoAuUxKvnkEvgMo4b/nib6sTNxZ5RIIw2v0RxvFmGg/ruxGHQNo9VrWdh/kihkQxgrgTUzJCYB4CEcJ5OE/di4XdImoxHdt+ZyVYsC3qFZ3rWFw/GGFA/NgogP5QwIkpt88ACKPPWgmjR0/U9ceGu0w/sk8DXRn+EcE2j8677ERDIATmIhAhnIv0dP1YMC3sFtHWqkufdip2LW+sRAt2BTd54EPYMCJ+zhsIXAggTso4b3nHhOpj/5aq7D2o2iWO5sJNOF65VlnPPewQI4iPMTUu/Rw+LpWUEDiRQITwRIAXqN7vAi3EdiTMTuUC7hzUpQVtChs71SZhI36MeNErSAAABYNJREFUALYyGBE/AsWUa3nnCPVnLrzP1SvX+vl6qj9jsPD/ZRVSt4LlHjN5hp0/+NhMXaabWyQQIVzPrFsofXtCWxTsNizsdiRPjeKFylSnmcW2xYXO9zUiMpbVhu8OlMcsXswOx+eXvRGpKaxv81M1Pm0SP4zq9O5w40oTQH4RqLuMmX/9e9cfH+waf7DS3le27fAyc++CNUbjwpLhjjXbVu+a0/xhcc3jy9guTCBCeOEJOKB7C72bMVoVd4j2C6XFnvgw6RZS5q5Gac0sqi0udL6v8WEsqw3fHSiPWbDZXAt2e0Va49JCrH6uTqb42qVq5ujDIxqtsucQxd1Z+vqK+EOGWFf0wfHJOiOahB1LhjvWzLyaY+acyWf+HTB1zAHTTjW5aeFm4T/8NzZubuMjPTYbgevvKEK4njn+t3LVVw5VcH9Y2Cx4jAhZ9Jh0Cylzd6Pdo51jb/KatfT+vMWF8oVMnLW4kHnrivR75xYQ8ZiD5wQxmdsdu2SCZS5a3xb4FhfiJo0g9q97+5rKdLlbupD53NHOiPWctW98/g0w/w4YUSSQjKjwRfjxalsonSnXjC/aYNrUdhU/+lC/mfa0q49mrV9+8MlXg/GT/8Zo3Moe7UAqhsA+BCKE+1BaRhkLwkvLFZfWmEXS4sj81SxkFo+Wr4y7G31WJb03ec1aen/e4kL5QibOWlzIfJ+gdH0zaaPJZ/xs1nwXNnOjCWGt4T447JT+sVJcbvRHQf/QeyXvPCyqOzMnyGiLvTmyoFvM7ZL7pp/ywVdIja970yahVI8RwMYMw5Ftfy6/t1YP118rp+xMtV3RjX6aSBGgUZyMhRnXLmtC1vKVZ+1cqIy29WGXzPSrf77wyR3Pntv074fPxr3JTwicm0CE8EuE1xazeFgcmQVYyJayePBvNL4xfjZrvgubvaEmw3N5FTw47JS+s1K+pcxuz/cKVnTnYTdsUd1ZYIIMb/Ox0DOLvIV9bNaOnMi3dGXa7sgu6IMt41mIG07PTp8bKN+bur011m7C8VwkzoSmiSURxYmJM3nKNCNUfZt9nIPOWxljZa2utph2+z6cS1eOT4x/2ouFwGwEIoSzoU5HBxB48YCyY1GCYFG2ENsNj/lTntvR+Kz2eW0qY0dkl8SIZtsd2Q2O9QnDmDbXOX4MwyagQiLFr23Wi5l8ZZl6TFtMu3ONI/2EwN4EIoR7o0rBGQlYRO0WmIW1X0wtqESOudTHLMSs7TbEtbHb5WlyzrGw890Yp/EwrYRACDyXQITwuYhS4EIEiAwjgsSQQDQjcsylPkY42Nyu8s+jEO4C9Znlsf37PNTlUy/wvsQ4jvU79ULgKghECK9iGjOICxIggsTQjUyEmmgTb+8jJZT93aCjm/KVd6ORG5rePRbI+SQE0kgIPEkgQvgknmSGwEEE7OaIIHHzPlKXdj0gL2SEslm7jKv8QZ2kcAiEwLQEIoTT8kxrIbCNgJ0fI5TNtpVLWgiEwKkEjqgfITwCWqqEQAiEQAhcD4EI4fXMZUYSAiEQAiFwBIEI4RHQllElXoRACIRACExBIEI4BcW0EQIhEAIhsFoCEcLVTl0cvyUCGWsIhMD5CEQIz8c2LYdACIRACKyAQIRwBZMUF0MgBG6JQMY6N4EI4dzE018IhEAIhMCiCEQIFzUdcSYEQiAEQmBuApcUwrnHmv5CIARCIARC4BGBCOEjJEkIgRAIgRC4JQIRwlua7UuONX2HQAiEwEIJRAgXOjFxKwRCIARCYB4CEcJ5OKeXELglAhlrCKyKQIRwVdMVZ0MgBEIgBKYmECGcmmjaC4EQCIFbInAFY40QXsEkZgghEAIhEALHE4gQHs8uNUMgBEIgBK6AQIRw70lMwRAIgRAIgWsk8EUAAAD//1Ia4W8AAAAGSURBVAMA/+7QCqr2vDQAAAAASUVORK5CYII=', '2026-04-29 04:58:46', 'light', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lease_requests`
--
ALTER TABLE `lease_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rent_requests`
--
ALTER TABLE `rent_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lease_requests`
--
ALTER TABLE `lease_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rent_requests`
--
ALTER TABLE `rent_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lease_requests`
--
ALTER TABLE `lease_requests`
  ADD CONSTRAINT `lease_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lease_requests_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `rent_requests`
--
ALTER TABLE `rent_requests`
  ADD CONSTRAINT `rent_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rent_requests_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
