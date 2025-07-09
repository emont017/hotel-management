-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 06:25 PM
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

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `total_price`, `status`, `confirmation_number`, `source`, `created_at`) VALUES
(6, 3, 4, '2025-07-09', '2025-07-10', 120.00, 'checked-in', 'FIU-20250708-F237', 'Online', '2025-07-08 17:20:29'),
(7, 3, 5, '2025-07-09', '2025-07-10', 180.00, 'confirmed', 'FIU-20250708-B66F', 'Online', '2025-07-08 17:24:30'),
(8, 6, 6, '2025-07-09', '2025-07-10', 220.00, 'confirmed', 'FIU-20250708-5E44', 'Online', '2025-07-08 17:37:00'),
(9, 7, 4, '2025-07-10', '2025-07-11', 120.00, 'confirmed', 'FIU-20250708-7F52', 'Online', '2025-07-08 17:39:48');

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

--
-- Dumping data for table `folios`
--

INSERT INTO `folios` (`id`, `booking_id`, `status`, `balance`, `created_at`, `updated_at`) VALUES
(1, 6, 'open', 120.00, '2025-07-08 17:20:29', '2025-07-08 17:20:29'),
(2, 7, 'open', 180.00, '2025-07-08 17:24:30', '2025-07-08 17:24:30'),
(3, 8, 'open', 220.00, '2025-07-08 17:37:00', '2025-07-08 17:37:00'),
(4, 9, 'open', 120.00, '2025-07-08 17:39:48', '2025-07-08 17:39:48');

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

--
-- Dumping data for table `folio_items`
--

INSERT INTO `folio_items` (`id`, `folio_id`, `description`, `amount`, `timestamp`) VALUES
(1, 1, 'Room & Tax Charge (1 nights)', 120.00, '2025-07-08 17:20:29'),
(2, 2, 'Room & Tax Charge (1 nights)', 180.00, '2025-07-08 17:24:30'),
(3, 3, 'Room & Tax Charge (1 nights)', 220.00, '2025-07-08 17:37:00'),
(4, 4, 'Room & Tax Charge (1 nights)', 120.00, '2025-07-08 17:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_logs`
--

CREATE TABLE `housekeeping_logs` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` enum('clean','dirty','maintenance') NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
(4, 'Double Room', '101', 2, 'img/rooms/double.jpg', 'available', 'occupied'),
(5, 'Executive Suite', '201', 4, 'img/rooms/suite.jpg', 'available', 'clean'),
(6, 'Suite with Balcony', '301', 4, 'img/rooms/balcony.jpg', 'available', 'clean');

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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guest','friend') NOT NULL DEFAULT 'friend',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `phone`) VALUES
(3, 'Test2', '$2y$10$uRNDFZIxAPnD0isgrnYtEu38o1/s1GB6RINfk9or7ef0kKb6vGGli', 'admin', NULL, NULL, NULL),
(6, 'ed611', '$2y$10$fBB96sDq1LOOtLcgXQOf3..86Oo71LpO3v/SPIWdDZIkgGytOAM1.', 'guest', 'ed', 'test118911651751715782223@gmail.com', '3053950000'),
(7, 'ed589', '$2y$10$hi2WhwxAnv353lOPA5ZRbOf5XOUVvkN3HUQIVnA7stB.sPEhyu3pO', 'guest', 'ed', 'test118911651751715782223@gmail.com', '3053950000');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `folios`
--
ALTER TABLE `folios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `folio_items`
--
ALTER TABLE `folio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
