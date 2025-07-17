-- Remove smoking preference from guest_preferences table
-- Execute this after removing smoking preference from the CRM interface

ALTER TABLE `guest_preferences` DROP COLUMN `smoking_preference`; 