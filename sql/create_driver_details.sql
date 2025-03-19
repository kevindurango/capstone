CREATE TABLE IF NOT EXISTS `driver_details` (
  `detail_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `vehicle_type` VARCHAR(100),
  `license_number` VARCHAR(50),
  `vehicle_plate` VARCHAR(20),
  `availability_status` ENUM('available','busy','offline') DEFAULT 'offline',
  `max_load_capacity` DECIMAL(10,2),
  `current_location` VARCHAR(255),
  `contact_number` VARCHAR(20),
  `rating` DECIMAL(3,2) DEFAULT 0,
  `completed_pickups` INT DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
