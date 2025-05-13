<?php
/**
 * AJAX handler to update planted area data for products
     */

// Start the session before any output
session_start();

// Set JSON header before any output to prevent unexpected tokens
header('Content-Type: application/json');

// Enable error logging but prevent output to browser
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Include required files with error handling
try {
    require_once '../models/Database.php';
    require_once '../models/Log.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load required files',
        'error' => $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in and has appropriate role
$allowAccess = isset($_SESSION['user_id']) && 
              (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Manager', 'Organization Head']));

if (!$allowAccess) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get data from request
$recordId = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
$estimatedProduction = isset($_POST['estimated_production']) ? floatval($_POST['estimated_production']) : 0;
$productionUnit = isset($_POST['production_unit']) ? $_POST['production_unit'] : 'kilogram';
$plantedArea = isset($_POST['planted_area']) ? floatval($_POST['planted_area']) : 0;
$areaUnit = isset($_POST['area_unit']) ? $_POST['area_unit'] : 'hectare';

// Validate inputs
if ($recordId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid record ID'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    $log = new Log();
    
    // First check if the record exists
    $checkQuery = "SELECT * FROM barangay_products WHERE id = :record_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':record_id', $recordId, PDO::PARAM_INT);
    $checkStmt->execute();
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo json_encode([
            'success' => false,
            'message' => 'Record not found'
        ]);
        exit;
    }
    
    // Check which columns exist in the table
    $columnCheckQuery = "SHOW COLUMNS FROM barangay_products";
    $columnStmt = $conn->query($columnCheckQuery);
    $columns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build dynamic update query based on existing columns
    $updateQuery = "UPDATE barangay_products SET ";
    $updateParams = [];
    
    if (in_array('estimated_production', $columns)) {
        $updateQuery .= "estimated_production = :estimated_production, ";
        $updateParams[':estimated_production'] = $estimatedProduction;
    }
    
    if (in_array('production_unit', $columns)) {
        $updateQuery .= "production_unit = :production_unit, ";
        $updateParams[':production_unit'] = $productionUnit;
    }
    
    if (in_array('planted_area', $columns)) {
        $updateQuery .= "planted_area = :planted_area, ";
        $updateParams[':planted_area'] = $plantedArea;
    }
    
    if (in_array('area_unit', $columns)) {
        $updateQuery .= "area_unit = :area_unit, ";
        $updateParams[':area_unit'] = $areaUnit;
    }
    
    // Remove trailing comma and add WHERE clause
    $updateQuery = rtrim($updateQuery, ", ");
    $updateQuery .= " WHERE id = :record_id";
    $updateParams[':record_id'] = $recordId;
    
    // Execute the update
    $updateStmt = $conn->prepare($updateQuery);
    foreach ($updateParams as $param => $value) {
        if (strpos($param, 'area') !== false || strpos($param, 'production') !== false) {
            $updateStmt->bindValue($param, $value, PDO::PARAM_STR);
        } else if (strpos($param, 'id') !== false) {
            $updateStmt->bindValue($param, $value, PDO::PARAM_INT);
        } else {
            $updateStmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }
    
    $result = $updateStmt->execute();
    
    if ($result) {
        // Log the activity
        $log->logActivity(
            $_SESSION['user_id'], 
            "Updated planted area information for product ID: {$record['product_id']}"
        );
        
        // Fetch the updated record
        $fetchQuery = "SELECT 
                         bp.id, 
                         b.barangay_name, 
                         cs.season_name,
                         bp.estimated_production,
                         bp.production_unit,
                         bp.planted_area,
                         bp.area_unit
                       FROM barangay_products bp
                       LEFT JOIN barangays b ON bp.barangay_id = b.barangay_id
                       LEFT JOIN crop_seasons cs ON bp.season_id = cs.season_id
                       WHERE bp.id = :record_id";
        
        $fetchStmt = $conn->prepare($fetchQuery);
        $fetchStmt->bindParam(':record_id', $recordId, PDO::PARAM_INT);
        $fetchStmt->execute();
        
        $updatedRecord = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        // Normalize data
        if ($updatedRecord) {
            $updatedRecord['estimated_production'] = isset($updatedRecord['estimated_production']) ? floatval($updatedRecord['estimated_production']) : $estimatedProduction;
            $updatedRecord['production_unit'] = isset($updatedRecord['production_unit']) ? $updatedRecord['production_unit'] : $productionUnit;
            $updatedRecord['planted_area'] = isset($updatedRecord['planted_area']) ? floatval($updatedRecord['planted_area']) : $plantedArea;
            $updatedRecord['area_unit'] = isset($updatedRecord['area_unit']) ? $updatedRecord['area_unit'] : $areaUnit;
        } else {
            // Create a response with the data we know
            $updatedRecord = [
                'id' => $recordId,
                'estimated_production' => $estimatedProduction,
                'production_unit' => $productionUnit,
                'planted_area' => $plantedArea,
                'area_unit' => $areaUnit,
                'barangay_name' => 'Unknown', // We can't retrieve this if the JOIN failed
                'season_name' => 'Unknown'    // We can't retrieve this if the JOIN failed
            ];
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Planted area updated successfully',
            'data' => $updatedRecord
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update planted area. No changes made.',
            'error' => $updateStmt->errorInfo()
        ]);
    }
    
} catch (PDOException $e) {
    // Log error
    error_log("Database error in update-planted-area.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("General error in update-planted-area.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the data',
        'error' => $e->getMessage()
    ]);
}
?>