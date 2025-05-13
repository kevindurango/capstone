<?php
// Script to update planted area values in barangay_products table
require_once 'models/Database.php';
require_once 'models/Log.php';

// Initialize database connection and logger
$db = new Database();
$conn = $db->connect();
$log = new Log();

try {
    echo "Starting planted area update process...\n";
    
    // First check how many records have zero planted_area
    $countBefore = $conn->query("SELECT COUNT(*) FROM barangay_products WHERE planted_area = 0")->fetchColumn();
    echo "Found {$countBefore} records with zero planted area values.\n";
    
    // Read and execute the SQL script
    $sqlScript = file_get_contents('sql/update_planted_area.sql');
    $statements = explode(';', $sqlScript);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
        }
    }
    
    // Check how many records still have zero planted_area
    $countAfter = $conn->query("SELECT COUNT(*) FROM barangay_products WHERE planted_area = 0")->fetchColumn();
    $updated = $countBefore - $countAfter;
    
    echo "Update completed successfully!\n";
    echo "Updated {$updated} records with proper planted area values.\n";
    echo "Remaining records with zero planted area: {$countAfter}\n";
    
    // Log the activity
    $log->logActivity(null, "Updated planted area values in {$updated} barangay_products records");
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $log->logError("Error updating planted area values: " . $e->getMessage());
}
?>