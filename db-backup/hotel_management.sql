-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 27, 2025 at 05:26 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `confirmation_number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `total_price`, `confirmation_number`, `created_at`) VALUES
(1, 3, 4, '2025-05-27', '2025-05-30', 360.00, '', '2025-05-26 21:44:07'),
(2, 4, 5, '2025-05-27', '2025-05-30', 540.00, '', '2025-05-26 23:01:05'),
(3, 5, 6, '2025-05-28', '2025-05-29', 220.00, '', '2025-05-26 23:08:49'),
(4, 5, 6, '2025-05-29', '2025-05-30', 220.00, '', '2025-05-26 23:10:30'),
(5, 5, 4, '2025-06-01', '2025-06-03', 240.00, 'FIU-20250527-A516', '2025-05-26 23:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(11) DEFAULT 2,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_type`, `room_number`, `price`, `capacity`, `image_path`, `status`) VALUES
(4, 'Double Room', '101', 120.00, 2, 'img/rooms/double.jpg', 'available'),
(5, 'Executive Suite', '201', 180.00, 4, 'img/rooms/suite.jpg', 'available'),
(6, 'Suite with Balcony', '301', 220.00, 4, 'img/rooms/balcony.jpg', 'available');

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
(2, 'Test1', '$2y$10$1Pucng1JBKYKlihWQdl8AujEvDVJf1LQ3Wldr4NUgR5xf4CjbKK12', 'admin', NULL, NULL, NULL),
(3, 'Test2', '$2y$10$uRNDFZIxAPnD0isgrnYtEu38o1/s1GB6RINfk9or7ef0kKb6vGGli', 'guest', NULL, NULL, NULL),
(4, 'ed2023', '$2y$10$sKCwV4utAbjZfDEmopEZleQSBIU2yewHg.Cj5Y3UpNtV4zHTcGk3.', 'guest', 'Ed', 'test1@gmail.com', '3053480001'),
(5, 'ed1587', '$2y$10$06ztYvTPIhwD/7xG2lueLOBBH/ix1Bj33qHAQgxN1bxFjav2YTw9y', 'friend', 'ed', 'emont017@gmail.com', '30553480002');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
