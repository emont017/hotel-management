-- Sample payment data for your actual bookings
-- Based on your existing booking IDs: 10, 16, 17

-- Payments for booking #10 (Guest ID 12, $220.00 total)
INSERT INTO `payments` (`booking_id`, `amount`, `payment_method`, `transaction_id`, `notes`, `recorded_by`) VALUES
(10, 220.00, 'Credit Card', 'CC_2025_001', 'Full payment for 1-night suite stay', 3),
(10, 35.00, 'Credit Card', 'CC_2025_002', 'Room service and amenities', 3);

-- Payments for booking #16 (Guest ID 14, $120.00 total)  
INSERT INTO `payments` (`booking_id`, `amount`, `payment_method`, `transaction_id`, `notes`, `recorded_by`) VALUES
(16, 120.00, 'Cash', 'CASH_001', 'Full payment for double room', 3),
(16, 25.00, 'Cash', 'CASH_002', 'Parking fee', 3);

-- Payments for booking #17 (Guest ID 14, $360.00 total)
INSERT INTO `payments` (`booking_id`, `amount`, `payment_method`, `transaction_id`, `notes`, `recorded_by`) VALUES
(17, 300.00, 'Credit Card', 'CC_2025_003', 'Partial payment at check-in', 3),
(17, 60.00, 'Credit Card', 'CC_2025_004', 'Remaining balance and extras', 3);

-- This will give Guest #12 total spending: $255.00
-- This will give Guest #14 total spending: $505.00 (Silver tier!) 