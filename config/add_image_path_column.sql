-- Add image_path column to room_types table if it doesn't exist
-- Run this in phpMyAdmin or MySQL command line

USE hotel_reservation_system;

-- Check if column exists and add it if it doesn't
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = 'hotel_reservation_system' 
AND table_name = 'room_types' 
AND column_name = 'image_path';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE room_types ADD COLUMN image_path VARCHAR(255) NULL AFTER max_occupancy', 
    'SELECT "Column image_path already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
