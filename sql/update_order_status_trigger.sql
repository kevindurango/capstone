-- SQL Script to update the order status trigger to match the enum values
-- Created on: May 17, 2025

-- Drop the existing trigger first
DROP TRIGGER IF EXISTS validate_order_status;

-- Create the updated trigger with enum values matching the table structure
DELIMITER $$

CREATE TRIGGER `validate_order_status` BEFORE UPDATE ON `orders` FOR EACH ROW 
BEGIN
    IF NEW.order_status NOT IN ('pending', 'processing', 'ready', 'completed', 'canceled') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Invalid order status. Must be: pending, processing, ready, completed, or canceled';
    END IF;
END$$

DELIMITER ;

-- Log the change
INSERT INTO activitylogs (action, action_date)
VALUES ('Updated order status validation trigger to match table enum values', NOW());
