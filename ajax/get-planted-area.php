<?php
/**
 * AJAX handler to fetch planted area data for a specific product
 * Used by the planted area management feature
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

// Check if user is logged in and has appropriate role - making this more flexible
$allowAccess = isset($_SESSION['user_id']) && 
              (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Manager', 'Organization Head']));

if (!$allowAccess) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get product ID from request
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    $log = new Log();
    
    // First check if the product exists
    $productQuery = "SELECT product_id, name FROM products WHERE product_id = :product_id";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $productStmt->execute();
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Check if the barangay_products table has the expected columns
    try {
        $columnCheckQuery = "SHOW COLUMNS FROM barangay_products";
        $columnStmt = $conn->query($columnCheckQuery);
        $columns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Log the columns for debugging
        error_log("Columns in barangay_products: " . implode(", ", $columns));
        
        // Verify all required columns exist
        $requiredColumns = ['id', 'barangay_id', 'product_id', 'estimated_production', 
                           'production_unit', 'year', 'season_id', 'planted_area', 'area_unit'];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            error_log("Missing columns in barangay_products: " . implode(", ", $missingColumns));
        }
    } catch (PDOException $e) {
        error_log("Error checking table structure: " . $e->getMessage());
    }
    
    // Query with explicit columns and proper LEFT JOINs, using dynamic column checking
    $query = "SELECT 
                bp.id, 
                b.barangay_name, 
                cs.season_name";
    
    if (in_array('estimated_production', $columns)) {
        $query .= ", bp.estimated_production";
    } else {
        $query .= ", 0 as estimated_production";
    }
    
    if (in_array('production_unit', $columns)) {
        $query .= ", bp.production_unit";
    } else {
        $query .= ", 'kilogram' as production_unit";
    }
    
    if (in_array('planted_area', $columns)) {
        $query .= ", bp.planted_area";
    } else {
        $query .= ", 0 as planted_area";
    }
    
    if (in_array('area_unit', $columns)) {
        $query .= ", bp.area_unit";
    } else {
        $query .= ", 'hectare' as area_unit";
    }
    
    $query .= " FROM barangay_products bp
              LEFT JOIN barangays b ON bp.barangay_id = b.barangay_id
              LEFT JOIN crop_seasons cs ON bp.season_id = cs.season_id
              WHERE bp.product_id = :product_id
              ORDER BY b.barangay_name, cs.season_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    $plantedAreaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no data found but product exists, create default entry
    if (empty($plantedAreaData)) {
        try {
            // Get all barangays
            $barangaysQuery = "SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name LIMIT 1";
            $barangaysStmt = $conn->query($barangaysQuery);
            $barangays = $barangaysStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all seasons
            $seasonsQuery = "SELECT season_id, season_name FROM crop_seasons LIMIT 1";
            $seasonsStmt = $conn->query($seasonsQuery);
            $seasons = $seasonsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $plantedAreaData = [];
            
            // Create a default entry for the first barangay and season
            if (!empty($barangays) && !empty($seasons)) {
                $barangay = $barangays[0];
                $season = $seasons[0];
                
                // Build the insert query dynamically based on available columns
                $insertQueryFields = "barangay_id, product_id";
                $insertQueryValues = ":barangay_id, :product_id";
                $insertParams = [
                    ':barangay_id' => $barangay['barangay_id'],
                    ':product_id' => $productId
                ];
                
                if (in_array('estimated_production', $columns)) {
                    $insertQueryFields .= ", estimated_production";
                    $insertQueryValues .= ", :estimated_production";
                    $insertParams[':estimated_production'] = 0;
                }
                
                if (in_array('production_unit', $columns)) {
                    $insertQueryFields .= ", production_unit";
                    $insertQueryValues .= ", :production_unit";
                    $insertParams[':production_unit'] = 'kilogram';
                }
                
                if (in_array('year', $columns)) {
                    $insertQueryFields .= ", year";
                    $insertQueryValues .= ", :year";
                    $insertParams[':year'] = date('Y');
                }
                
                if (in_array('season_id', $columns)) {
                    $insertQueryFields .= ", season_id";
                    $insertQueryValues .= ", :season_id";
                    $insertParams[':season_id'] = $season['season_id'];
                }
                
                if (in_array('planted_area', $columns)) {
                    $insertQueryFields .= ", planted_area";
                    $insertQueryValues .= ", :planted_area";
                    $insertParams[':planted_area'] = 0;
                }
                
                if (in_array('area_unit', $columns)) {
                    $insertQueryFields .= ", area_unit";
                    $insertQueryValues .= ", :area_unit";
                    $insertParams[':area_unit'] = 'hectare';
                }
                
                // Build and execute the insert query
                $insertQuery = "INSERT INTO barangay_products ($insertQueryFields) VALUES ($insertQueryValues)";
                
                $insertStmt = $conn->prepare($insertQuery);
                foreach ($insertParams as $param => $value) {
                    if (strpos($param, 'id') !== false) {
                        $insertStmt->bindValue($param, $value, PDO::PARAM_INT);
                    } else {
                        $insertStmt->bindValue($param, $value, PDO::PARAM_STR);
                    }
                }
                
                $result = $insertStmt->execute();
                
                if ($result) {
                    $newId = $conn->lastInsertId();
                    
                    // Add the newly created entry to the response
                    $plantedAreaData[] = [
                        'id' => $newId,
                        'barangay_name' => $barangay['barangay_name'],
                        'season_name' => $season['season_name'],
                        'estimated_production' => 0,
                        'production_unit' => 'kilogram',
                        'planted_area' => 0,
                        'area_unit' => 'hectare'
                    ];
                }
            }
        } catch (Exception $e) {
            // Just log the error but continue - we'll return empty data
            error_log("Error creating default planted area: " . $e->getMessage());
        }
    }
    
    // Normalize data to ensure all fields are properly set
    foreach ($plantedAreaData as &$item) {
        // Convert to float or set to 0 if missing/null
        $item['estimated_production'] = isset($item['estimated_production']) && !is_null($item['estimated_production']) ? 
            floatval($item['estimated_production']) : 0.00;
        $item['production_unit'] = isset($item['production_unit']) && !empty($item['production_unit']) ? 
            $item['production_unit'] : 'kilogram';
        $item['planted_area'] = isset($item['planted_area']) && !is_null($item['planted_area']) ? 
            floatval($item['planted_area']) : 0.00;
        $item['area_unit'] = isset($item['area_unit']) && !empty($item['area_unit']) ? 
            $item['area_unit'] : 'hectare';
            
        // Format numbers to 2 decimal places for display
        $item['estimated_production'] = number_format($item['estimated_production'], 2, '.', '');
        $item['planted_area'] = number_format($item['planted_area'], 2, '.', '');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $plantedAreaData,
        'product' => $product
    ]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Database error in get-planted-area.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("General error in get-planted-area.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving the data',
        'error' => $e->getMessage()
    ]);
}
?>