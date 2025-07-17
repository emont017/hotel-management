-- CRM Database Update for Guest Preferences
-- Add this to your hotel_management database

-- Table structure for guest preferences
CREATE TABLE `guest_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `preferred_room_type` varchar(50) DEFAULT NULL,
  `floor_preference` varchar(20) DEFAULT NULL,
  `bed_preference` enum('single','double','queen','king','twin','no-preference') DEFAULT 'no-preference',
  `amenities_requested` text DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `dietary_restrictions` text DEFAULT NULL,
  `accessibility_needs` text DEFAULT NULL,
  `marketing_consent` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_guest_preferences_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Insert some sample guest preferences for existing guests
INSERT INTO `guest_preferences` (`user_id`, `preferred_room_type`, `floor_preference`, `bed_preference`, `amenities_requested`, `special_requests`, `dietary_restrictions`, `marketing_consent`, `notes`) VALUES
(6, 'Double Room', 'high', 'queen', 'WiFi, Coffee Machine', 'Late checkout preferred', 'Vegetarian meals', 1, 'Regular customer, very satisfied with service'),
(7, 'Executive Suite', 'low', 'king', 'Balcony, Room Service', 'Extra towels', NULL, 0, 'Business traveler'),
(8, 'Suite with Balcony', 'high', 'double', 'Ocean view, Mini-bar', 'Quiet room', 'Gluten-free options', 1, 'Honeymoon couple'); 