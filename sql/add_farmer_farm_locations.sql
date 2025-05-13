-- Create farmer_farm_locations table
CREATE TABLE IF NOT EXISTS farmer_farm_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    barangay_id INT NOT NULL,
    land_size DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ownership_type VARCHAR(50) DEFAULT 'owned', -- owned, leased, shared, etc.
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id) ON DELETE CASCADE,
    UNIQUE KEY (farmer_id, barangay_id) -- Prevent duplicate entries
);
