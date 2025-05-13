<?php
// Script to update product-farmer associations

require_once 'models/Database.php';
require_once 'models/Log.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();
$log = new Log();

try {
    echo "Starting product-farmer association update...\n";
    
    // Count products without farmer associations
    $countBefore = $conn->query("SELECT COUNT(*) FROM products WHERE farmer_id IS NULL OR farmer_id = 0")->fetchColumn();
    echo "Found {$countBefore} products without farmer associations.\n";
    
    // Execute the SQL file
    $sql = file_get_contents('sql/update_product_farmers.sql');
    $statements = explode(';', $sql);
    $successCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
            $successCount++;
        }
    }
    
    // Count products still without farmer associations
    $countAfter = $conn->query("SELECT COUNT(*) FROM products WHERE farmer_id IS NULL OR farmer_id = 0")->fetchColumn();
    $updated = $countBefore - $countAfter;
    
    echo "Update completed successfully!\n";
    echo "Updated {$updated} products with farmer associations.\n";
    echo "Products still without farmer associations: {$countAfter}\n";
    echo "SQL statements executed: {$successCount}\n";
    
    // Log the activity
    $log->logActivity(null, "Updated farmer associations for {$updated} products");
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $log->logError("Error updating product-farmer associations: " . $e->getMessage());
}
?>