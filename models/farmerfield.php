<?php
/**
 * FarmerField Model
 * Handles CRUD operations for farmer fields
 */
class FarmerField {
    private $conn;
    
    /**
     * Constructor - establish database connection
     */
    public function __construct() {
        require_once 'database.php';
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Get all fields belonging to a specific farmer
     * 
     * @param int $farmer_id The farmer's user ID
     * @return array Array of field records
     */
    public function getFieldsByFarmerId($farmer_id) {
        try {
            $query = "SELECT ff.*, b.barangay_name, 
                     (SELECT COUNT(*) FROM barangay_products bp WHERE bp.field_id = ff.field_id) as crop_count
                     FROM farmer_fields ff
                     LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                     WHERE ff.farmer_id = :farmer_id
                     ORDER BY ff.field_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching farmer fields: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a specific field by ID
     * 
     * @param int $field_id The field ID to retrieve
     * @return array|null Field data or null if not found
     */
    public function getFieldById($field_id) {
        try {
            $query = "SELECT ff.*, b.barangay_name
                     FROM farmer_fields ff
                     LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                     WHERE ff.field_id = :field_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':field_id', $field_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching field by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new field for a farmer
     * 
     * @param array $fieldData Field data to insert
     * @return int|bool The new field ID on success, false on failure
     */
    public function createField($fieldData) {
        try {
            $query = "INSERT INTO farmer_fields (farmer_id, barangay_id, field_name, field_size, 
                      field_type, notes, coordinates) 
                      VALUES (:farmer_id, :barangay_id, :field_name, :field_size, 
                      :field_type, :notes, :coordinates)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $fieldData['farmer_id'], PDO::PARAM_INT);
            $stmt->bindParam(':barangay_id', $fieldData['barangay_id'], PDO::PARAM_INT);
            $stmt->bindParam(':field_name', $fieldData['field_name'], PDO::PARAM_STR);
            $stmt->bindParam(':field_size', $fieldData['field_size'], PDO::PARAM_STR);
            $stmt->bindParam(':field_type', $fieldData['field_type'], PDO::PARAM_STR);
            $stmt->bindParam(':notes', $fieldData['notes'], PDO::PARAM_STR);
            $stmt->bindParam(':coordinates', $fieldData['coordinates'], PDO::PARAM_STR);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error creating farmer field: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing field
     * 
     * @param array $fieldData Field data to update
     * @return bool True on success, false on failure
     */
    public function updateField($fieldData) {
        try {
            $query = "UPDATE farmer_fields 
                      SET barangay_id = :barangay_id,
                          field_name = :field_name, 
                          field_size = :field_size,
                          field_type = :field_type,
                          notes = :notes,
                          coordinates = :coordinates
                      WHERE field_id = :field_id AND farmer_id = :farmer_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':field_id', $fieldData['field_id'], PDO::PARAM_INT);
            $stmt->bindParam(':farmer_id', $fieldData['farmer_id'], PDO::PARAM_INT);
            $stmt->bindParam(':barangay_id', $fieldData['barangay_id'], PDO::PARAM_INT);
            $stmt->bindParam(':field_name', $fieldData['field_name'], PDO::PARAM_STR);
            $stmt->bindParam(':field_size', $fieldData['field_size'], PDO::PARAM_STR);
            $stmt->bindParam(':field_type', $fieldData['field_type'], PDO::PARAM_STR);
            $stmt->bindParam(':notes', $fieldData['notes'], PDO::PARAM_STR);
            $stmt->bindParam(':coordinates', $fieldData['coordinates'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating farmer field: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a field
     * 
     * @param int $field_id The field ID to delete
     * @param int $farmer_id The farmer ID (for security)
     * @return bool True on success, false on failure
     */
    public function deleteField($field_id, $farmer_id) {
        try {
            // First update any barangay_products to remove the field_id reference
            $query = "UPDATE barangay_products SET field_id = NULL WHERE field_id = :field_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':field_id', $field_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Now delete the field
            $query = "DELETE FROM farmer_fields 
                      WHERE field_id = :field_id AND farmer_id = :farmer_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':field_id', $field_id, PDO::PARAM_INT);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting farmer field: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all products planted in a specific field
     * 
     * @param int $field_id The field ID
     * @return array Array of products planted in the field
     */
    public function getProductsByFieldId($field_id) {
        try {
            $query = "SELECT bp.*, p.name as product_name, cs.season_name, b.barangay_name
                     FROM barangay_products bp
                     JOIN products p ON bp.product_id = p.product_id
                     JOIN barangays b ON bp.barangay_id = b.barangay_id
                     LEFT JOIN crop_seasons cs ON bp.season_id = cs.season_id
                     WHERE bp.field_id = :field_id
                     ORDER BY p.name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':field_id', $field_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching products by field ID: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Assign a product to a specific field
     * 
     * @param int $barangay_product_id The barangay_products.id
     * @param int $field_id The field_id to assign
     * @return bool True on success, false on failure
     */
    public function assignProductToField($barangay_product_id, $field_id) {
        try {
            $query = "UPDATE barangay_products 
                      SET field_id = :field_id
                      WHERE id = :barangay_product_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':barangay_product_id', $barangay_product_id, PDO::PARAM_INT);
            $stmt->bindParam(':field_id', $field_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning product to field: " . $e->getMessage());
            return false;
        }
    }
}
?>