-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2025 at 05:06 PM
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
-- Database: `hotel_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('confirmed','checked-in','checked-out','cancelled') NOT NULL DEFAULT 'confirmed',
  `confirmation_number` varchar(20) NOT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'Online',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `folios`
--

CREATE TABLE `folios` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `folio_items`
--

CREATE TABLE `folio_items` (
  `id` int(11) NOT NULL,
  `folio_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_logs`
--

CREATE TABLE `housekeeping_logs` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` enum('clean','dirty','maintenance') NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `housekeeping_logs`
--

INSERT INTO `housekeeping_logs` (`id`, `room_id`, `status`, `updated_by`, `updated_at`, `notes`) VALUES
(1, 3, 'clean', 3, '2025-07-15 13:30:52', 'Status changed by Test2'),
(2, 9, 'clean', 3, '2025-07-15 13:30:57', 'Status changed by Test2'),
(3, 10, 'clean', 3, '2025-07-15 13:31:01', 'Status changed by Test2'),
(4, 1, 'dirty', 3, '2025-07-15 13:40:26', 'Status changed by Test2'),
(5, 1, 'clean', 3, '2025-07-15 13:48:34', 'Status changed by Test2'),
(6, 1, 'dirty', 3, '2025-07-15 13:48:47', 'Status changed by Test2'),
(7, 1, 'clean', 3, '2025-07-15 13:50:24', 'Status changed by Test2');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_tasks`
--

CREATE TABLE `housekeeping_tasks` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) NOT NULL,
  `task_date` date NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `housekeeping_tasks`
--

INSERT INTO `housekeeping_tasks` (`id`, `room_id`, `assigned_to_user_id`, `task_date`, `status`, `notes`, `created_at`) VALUES
(1, 1, 9, '2025-07-15', 'pending', NULL, '2025-07-15 13:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `date_range_start` date DEFAULT NULL,
  `date_range_end` date DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `capacity` int(11) DEFAULT 2,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `housekeeping_status` enum('clean','dirty','maintenance','occupied') NOT NULL DEFAULT 'dirty'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_type`, `room_number`, `capacity`, `image_path`, `status`, `housekeeping_status`) VALUES
(1, 'Double Room', '101', 2, 'assets/images/room_double.jpg', 'available', 'clean'),
(2, 'Double Room', '102', 2, 'assets/images/room_double.jpg', 'available', 'clean'),
(3, 'Double Room', '103', 2, 'assets/images/room_double.jpg', 'available', 'clean'),
(4, 'Double Room', '104', 2, 'assets/images/room_double.jpg', 'available', 'clean'),
(5, 'Double Room', '105', 2, 'assets/images/room_double.jpg', 'available', 'clean'),
(6, 'Executive Suite', '201', 4, 'assets/images/room_executive.jpg', 'available', 'clean'),
(7, 'Executive Suite', '202', 4, 'assets/images/room_executive.jpg', 'available', 'clean'),
(8, 'Executive Suite', '203', 4, 'assets/images/room_executive.jpg', 'available', 'clean'),
(9, 'Executive Suite', '204', 4, 'assets/images/room_executive.jpg', 'available', 'clean'),
(10, 'Executive Suite', '205', 4, 'assets/images/room_executive.jpg', 'available', 'clean'),
(11, 'Suite with Balcony', '301', 4, 'assets/images/room_balcony.jpg', 'available', 'clean'),
(12, 'Suite with Balcony', '302', 4, 'assets/images/room_balcony.jpg', 'available', 'clean'),
(13, 'Suite with Balcony', '303', 4, 'assets/images/room_balcony.jpg', 'available', 'clean'),
(14, 'Suite with Balcony', '304', 4, 'assets/images/room_balcony.jpg', 'available', 'clean'),
(15, 'Suite with Balcony', '305', 4, 'assets/images/room_balcony.jpg', 'available', 'clean');

-- --------------------------------------------------------

--
-- Table structure for table `room_rates`
--

CREATE TABLE `room_rates` (
  `id` int(11) NOT NULL,
  `room_type` varchar(100) NOT NULL,
  `rate_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `room_rates`
--

INSERT INTO `room_rates` (`id`, `room_type`, `rate_name`, `price`, `date_start`, `date_end`) VALUES
(1, 'Double Room', 'Standard Rate', 120.00, '2024-01-01', '2029-12-31'),
(2, 'Executive Suite', 'Standard Rate', 180.00, '2024-01-01', '2029-12-31'),
(3, 'Suite with Balcony', 'Standard Rate', 220.00, '2024-01-01', '2029-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `pay_date` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`) VALUES
(1, 'business_date', '2025-07-14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','front_desk','housekeeping','accountant','guest','friend') NOT NULL DEFAULT 'guest',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `phone`, `is_active`) VALUES
(3, 'Test2', '$2y$10$uRNDFZIxAPnD0isgrnYtEu38o1/s1GB6RINfk9or7ef0kKb6vGGli', 'admin', NULL, NULL, NULL, 1),
(6, 'ed611', '$2y$10$fBB96sDq1LOOtLcgXQOf3..86Oo71LpO3v/SPIWdDZIkgGytOAM1.', 'guest', 'ed', 'test118911651751715782223@gmail.com', '3053950000', 1),
(7, 'ed589', '$2y$10$hi2WhwxAnv353lOPA5ZRbOf5XOUVvkN3HUQIVnA7stB.sPEhyu3pO', 'guest', 'ed', 'test118911651751715782223@gmail.com', '3053950000', 1),
(8, 'ed271', '$2y$10$E48MpvmCoh3VXTbbZqEIZO/j7Jvvwo1yFYvd8Ot3zLBoOoVSE9w/C', 'guest', 'Ed', 'test118911651751715782223@gmail.com', '3053950000', 1),
(9, 'Housekeeper1', '$2y$10$7hyZKat.edBNHQzW75yAiuWoUQi7uNI/FGCPzm93/bI3OP1bGiLni', 'housekeeping', 'Housekeeper One', '', 'N/A', 1),
(10, 'Accountant1', '$2y$10$Z7C9.10uIcENRxK67VSJLOw8thUs7chpxZmA4r2VbNi8KhjhEKzYy', 'accountant', 'Accountant1', '', '', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `folios`
--
ALTER TABLE `folios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `folio_items`
--
ALTER TABLE `folio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_folio_items_folio_id` (`folio_id`);

--
-- Indexes for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hktasks_room` (`room_id`),
  ADD KEY `fk_hktasks_user` (`assigned_to_user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `fk_payments_recorded_by` (`recorded_by`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_rates`
--
ALTER TABLE `room_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `folios`
--
ALTER TABLE `folios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `folio_items`
--
ALTER TABLE `folio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `room_rates`
--
ALTER TABLE `room_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `folios`
--
ALTER TABLE `folios`
  ADD CONSTRAINT `fk_folios_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folio_items`
--
ALTER TABLE `folio_items`
  ADD CONSTRAINT `fk_folio_items_folio_id` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  ADD CONSTRAINT `housekeeping_logs_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `housekeeping_logs_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  ADD CONSTRAINT `fk_hktasks_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hktasks_user` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
