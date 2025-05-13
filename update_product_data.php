<?php
// Script to update product information in the database

require_once 'models/Database.php';
require_once 'models/Log.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();
$log = new Log();

// Helper function to execute SQL files
function executeSqlFile($conn, $filepath) {
    echo "Executing SQL file: $filepath\n";
    
    if (!file_exists($filepath)) {
        echo "Error: File does not exist: $filepath\n";
        return false;
    }
    
    $sql = file_get_contents($filepath);
    if (!$sql) {
        echo "Error: Could not read file: $filepath\n";
        return false;
    }
    
    // Split into individual statements
    $statements = explode(';', $sql);
    $count = 0;
    
    try {
        $conn->beginTransaction();
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $conn->exec($statement);
                $count++;
            }
        }
        
        $conn->commit();
        echo "Successfully executed $count statements from $filepath\n";
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Update existing products with more accurate information
echo "Updating existing product information...\n";
$updateSuccess = executeSqlFile($conn, 'sql/update_product_information.sql');

if ($updateSuccess) {
    $log->logActivity(null, "Updated product information with more realistic data");
    echo "Successfully updated existing product information.\n";
} else {
    $log->logError("Failed to update product information");
    echo "Failed to update product information.\n";
}

// Add new local agricultural products
echo "\nAdding new local agricultural products...\n";
$addSuccess = executeSqlFile($conn, 'sql/add_new_local_products.sql');

if ($addSuccess) {
    $log->logActivity(null, "Added new local agricultural products to the database");
    echo "Successfully added new local agricultural products.\n";
} else {
    $log->logError("Failed to add new local agricultural products");
    echo "Failed to add new local agricultural products.\n";
}

echo "\nProduct database update complete.\n";
?>