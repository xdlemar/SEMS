-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 05:15 AM
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
-- Database: `sems`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` bigint(20) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `rfid_uid` varchar(32) NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `tap_time` datetime NOT NULL,
  `verification_status` enum('PENDING','MATCH','MISMATCH','UNVERIFIED') DEFAULT 'UNVERIFIED',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `pair_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `employee_id`, `device_id`, `rfid_uid`, `type`, `tap_time`, `verification_status`, `approved_by`, `approved_at`, `reject_reason`, `pair_id`, `created_at`) VALUES
(1, 1, 1, '16E6965F', 'IN', '2025-09-15 21:17:43', 'MATCH', 2, '2025-09-15 21:20:49', NULL, NULL, '2025-09-15 13:17:43'),
(2, 1, 1, '16E6965F', 'OUT', '2025-09-15 21:21:07', 'UNVERIFIED', NULL, NULL, NULL, 1, '2025-09-15 13:21:07'),
(3, 1, 1, '16E6965F', 'IN', '2025-09-15 21:26:27', 'MATCH', 2, '2025-09-15 21:29:35', NULL, NULL, '2025-09-15 13:26:27'),
(4, 1, 1, '16E6965F', 'OUT', '2025-09-15 21:30:20', 'UNVERIFIED', NULL, NULL, NULL, 3, '2025-09-15 13:30:21'),
(5, 1, 1, '16E6965F', 'IN', '2025-09-15 21:41:23', 'MATCH', 2, '2025-09-15 21:50:54', NULL, NULL, '2025-09-15 13:41:23'),
(6, 1, 1, '16E6965F', 'OUT', '2025-09-15 21:50:20', 'UNVERIFIED', NULL, NULL, NULL, 5, '2025-09-15 13:50:20'),
(7, 1, 1, '16E6965F', 'IN', '2025-09-15 21:50:25', 'MATCH', 2, '2025-09-15 21:51:06', NULL, NULL, '2025-09-15 13:50:25'),
(8, 1, 1, '16E6965F', 'OUT', '2025-09-15 21:51:10', 'UNVERIFIED', NULL, NULL, NULL, 7, '2025-09-15 13:51:10'),
(9, 1, 1, '16E6965F', 'IN', '2025-09-15 21:51:48', 'MATCH', 2, '2025-09-15 22:05:22', NULL, NULL, '2025-09-15 13:51:49'),
(10, 1, 1, '16E6965F', 'OUT', '2025-09-15 21:54:01', 'UNVERIFIED', NULL, NULL, NULL, 9, '2025-09-15 13:54:01'),
(11, 1, 1, '16E6965F', 'IN', '2025-09-15 22:04:57', 'MATCH', 2, '2025-09-15 22:19:56', NULL, NULL, '2025-09-15 14:05:01'),
(12, 2, 1, '5697194E', 'IN', '2025-09-15 22:19:31', 'MATCH', 2, '2025-09-15 22:19:54', NULL, NULL, '2025-09-15 14:19:31'),
(13, 2, 1, '5697194E', 'OUT', '2025-09-15 22:19:59', 'UNVERIFIED', NULL, NULL, NULL, 12, '2025-09-15 14:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_photos`
--

CREATE TABLE `attendance_photos` (
  `id` bigint(20) NOT NULL,
  `attendance_id` bigint(20) NOT NULL,
  `path` varchar(255) NOT NULL,
  `captured_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_photos`
--

INSERT INTO `attendance_photos` (`id`, `attendance_id`, `path`, `captured_at`) VALUES
(1, 7, '/sems/public/uploads/attendance/20250915_155030_16E6965F.jpg', '2025-09-15 21:50:30'),
(2, 9, '/sems/public/uploads/attendance/20250915_155151_16E6965F.jpg', '2025-09-15 21:51:51'),
(3, 9, '/sems/public/uploads/attendance/20250915_160001_16E6965F.jpg', '2025-09-15 22:00:01'),
(4, 11, '/sems/public/uploads/attendance/20250915_160503_16E6965F.jpg', '2025-09-15 22:05:03'),
(5, 11, '/sems/public/uploads/attendance/20250915_161159_16E6965F.jpg', '2025-09-15 22:11:59'),
(6, 11, '/sems/public/uploads/attendance/20250915_161231_16E6965F.jpg', '2025-09-15 22:12:31'),
(7, 12, '/sems/public/uploads/attendance/20250915_161933_5697194E.jpg', '2025-09-15 22:19:33'),
(8, 12, '/sems/public/uploads/attendance/20250915_162002_5697194E.jpg', '2025-09-15 22:20:02'),
(9, 12, '/sems/public/uploads/attendance/20250915_163342_5697194E.jpg', '2025-09-15 22:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `serial_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `serial_no`, `name`, `location`, `active`) VALUES
(1, 'DEV001', 'Lobby Reader', 'HQ', 1);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `emp_no` varchar(30) NOT NULL,
  `fname` varchar(60) NOT NULL,
  `lname` varchar(60) NOT NULL,
  `position` varchar(80) DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `pay_group` enum('WEEKLY','SEMI_MONTHLY','MONTHLY') DEFAULT 'SEMI_MONTHLY',
  `monthly_rate` decimal(10,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `emp_no`, `fname`, `lname`, `position`, `department`, `pay_group`, `monthly_rate`, `hourly_rate`, `email`, `phone`, `address`, `photo_path`, `active`, `created_at`) VALUES
(1, 'EMP-250915-A2J2L', 'Lemar', 'Abad', 'Admin', 'DPWH', 'SEMI_MONTHLY', 65000.00, 0.00, 'lemar@company.com', '0952593333', 'Caloocan City', '/sems/public/uploads/profile/1_dd12fb.png', 1, '2025-09-15 12:44:47'),
(2, 'EMP-250915-OXTZK', 'MARTIN', 'AGUIRE', 'HR Staff', 'SARADISCAYA', 'SEMI_MONTHLY', 25000.00, 0.00, 'martin@company.com', '0912133654444', 'caloocan', '/sems/public/uploads/profile/2_2f2d91.png', 1, '2025-09-15 14:18:45');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--

CREATE TABLE `payroll_runs` (
  `id` bigint(20) NOT NULL,
  `run_label` varchar(80) DEFAULT NULL,
  `pay_group` enum('WEEKLY','SEMI_MONTHLY','MONTHLY') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_runs`
--

INSERT INTO `payroll_runs` (`id`, `run_label`, `pay_group`, `period_start`, `period_end`, `created_by`, `created_at`) VALUES
(1, '2025-09-01 ~ 2025-09-30 (SEMI_MONTHLY)', 'SEMI_MONTHLY', '2025-09-01', '2025-09-30', 2, '2025-09-15 22:40:57'),
(2, '2025-09-01 ~ 2025-09-30 (SEMI_MONTHLY)', 'SEMI_MONTHLY', '2025-09-01', '2025-09-30', 2, '2025-09-15 23:18:34'),
(3, '2025-09-01 ~ 2025-09-30 (SEMI_MONTHLY)', 'SEMI_MONTHLY', '2025-09-01', '2025-09-30', 2, '2025-09-15 23:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` bigint(20) NOT NULL,
  `run_id` bigint(20) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `total_hours` decimal(10,2) NOT NULL,
  `days_worked` int(11) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `monthly_rate` decimal(10,2) NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `run_id`, `employee_id`, `total_hours`, `days_worked`, `hourly_rate`, `monthly_rate`, `gross_pay`, `deductions`, `net_pay`, `generated_at`) VALUES
(1, 1, 1, 0.32, 1, 369.32, 65000.00, 118.18, 0.00, 118.18, '2025-09-15 22:40:57'),
(2, 1, 2, 0.01, 1, 142.05, 25000.00, 1.42, 0.00, 1.42, '2025-09-15 22:40:58'),
(3, 2, 1, 0.00, 1, 369.32, 65000.00, 0.00, 0.00, 0.00, '2025-09-15 23:18:34'),
(4, 2, 2, 0.00, 1, 142.05, 25000.00, 0.00, 0.00, 0.00, '2025-09-15 23:18:34'),
(5, 3, 1, 0.32, 1, 369.32, 65000.00, 118.18, 0.00, 118.18, '2025-09-15 23:19:17'),
(6, 3, 2, 0.01, 1, 142.05, 25000.00, 1.42, 0.00, 1.42, '2025-09-15 23:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_cards`
--

CREATE TABLE `rfid_cards` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `uid_hex` varchar(32) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `issued_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfid_cards`
--

INSERT INTO `rfid_cards` (`id`, `employee_id`, `uid_hex`, `active`, `issued_at`) VALUES
(1, 1, '16E6965F', 1, '2025-09-15 21:16:58'),
(2, 2, '5697194E', 1, '2025-09-15 22:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_tap_events`
--

CREATE TABLE `rfid_tap_events` (
  `id` bigint(20) NOT NULL,
  `device_serial` varchar(50) NOT NULL,
  `rfid_uid` varchar(32) NOT NULL,
  `registered` tinyint(1) NOT NULL DEFAULT 0,
  `seen_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfid_tap_events`
--

INSERT INTO `rfid_tap_events` (`id`, `device_serial`, `rfid_uid`, `registered`, `seen_at`) VALUES
(1, 'DEV001', '5697194E', 0, '2025-09-15 15:00:47'),
(2, 'DEV001', '16E6965F', 0, '2025-09-15 15:01:44'),
(3, 'DEV001', '5697194E', 0, '2025-09-15 15:16:34'),
(4, 'DEV001', '16E6965F', 0, '2025-09-15 15:16:48'),
(5, 'DEV001', '5697194E', 0, '2025-09-15 15:17:15'),
(6, 'DEV001', '16E6965F', 1, '2025-09-15 15:17:43'),
(7, 'DEV001', '16E6965F', 1, '2025-09-15 15:21:07'),
(8, 'DEV001', '16E6965F', 1, '2025-09-15 15:26:27'),
(9, 'DEV001', '16E6965F', 1, '2025-09-15 15:30:20'),
(10, 'DEV001', '16E6965F', 1, '2025-09-15 15:41:23'),
(11, 'DEV001', '16E6965F', 1, '2025-09-15 15:50:20'),
(12, 'DEV001', '16E6965F', 1, '2025-09-15 15:50:25'),
(13, 'DEV001', '16E6965F', 1, '2025-09-15 15:51:10'),
(14, 'DEV001', '16E6965F', 1, '2025-09-15 15:51:49'),
(15, 'DEV001', '16E6965F', 1, '2025-09-15 15:54:01'),
(16, 'DEV001', '16E6965F', 1, '2025-09-15 16:05:00'),
(17, 'DEV001', '5697194E', 0, '2025-09-15 16:19:05'),
(18, 'DEV001', '5697194E', 1, '2025-09-15 16:19:31'),
(19, 'DEV001', '5697194E', 1, '2025-09-15 16:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','HR','MANAGER','EMPLOYEE') NOT NULL,
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `employee_id`) VALUES
(1, 'admin', 'f865b53623b121fd34ee5426c792e5c33af8c227', 'ADMIN', NULL),
(2, 'XDLEMAR', '$2y$10$f4O0BbrHTyS8AqHmeHDG9Ortw13EdOD/C/2iMGmSpYNCb5Pnl93VK', 'ADMIN', 1),
(3, 'EMPlemar', '$2y$10$FoRJjv4C4Rh/12pKeAaM6uVJQSZ4cO5Osmc0hOpFvXsQCco/hjHhO', 'EMPLOYEE', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance_photos`
--
ALTER TABLE `attendance_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_id` (`attendance_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_no` (`serial_no`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_no` (`emp_no`),
  ADD UNIQUE KEY `emp_no_unique` (`emp_no`),
  ADD UNIQUE KEY `uniq_emp_no` (`emp_no`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payrun_creator` (`created_by`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `run_id` (`run_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid_hex` (`uid_hex`),
  ADD UNIQUE KEY `uniq_uid` (`uid_hex`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `rfid_tap_events`
--
ALTER TABLE `rfid_tap_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dev_seen` (`device_serial`,`seen_at`),
  ADD KEY `idx_uid_seen` (`rfid_uid`,`seen_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_employee` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `attendance_photos`
--
ALTER TABLE `attendance_photos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rfid_tap_events`
--
ALTER TABLE `rfid_tap_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_att_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_att_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_photos`
--
ALTER TABLE `attendance_photos`
  ADD CONSTRAINT `fk_photo_att` FOREIGN KEY (`attendance_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `fk_payrun_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `fk_payslip_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payslip_run` FOREIGN KEY (`run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD CONSTRAINT `fk_card_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
