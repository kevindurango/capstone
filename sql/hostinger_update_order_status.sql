-- Hostinger-compatible update_order_status procedure
-- Remove the DEFINER clause to avoid permission issues

-- Drop the existing procedure first
DROP PROCEDURE IF EXISTS update_order_status;

DELIMITER $$

CREATE PROCEDURE `update_order_status` (IN `p_order_id` INT, IN `p_new_status` VARCHAR(50))
BEGIN
    DECLARE valid_status BOOLEAN;
    
    -- Check if status is valid
    IF p_new_status IN ('pending', 'processing', 'ready', 'completed', 'canceled') THEN
        SET valid_status = TRUE;
    ELSE
        SET valid_status = FALSE;
    END IF;
    
    -- Update status if valid
    IF valid_status THEN
        UPDATE orders
        SET order_status = p_new_status
        WHERE order_id = p_order_id;
        
        SELECT CONCAT('Order #', p_order_id, ' status updated to ', p_new_status) AS result;
        
        -- Log the change
        INSERT INTO activitylogs (action, action_date)
        VALUES (CONCAT('System updated order #', p_order_id, ' status to ', p_new_status), NOW());
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Invalid order status. Must be: pending, processing, ready, completed, or canceled';
    END IF;
END$$

DELIMITER ;
