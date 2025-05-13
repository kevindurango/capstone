<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('../config/database.php');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'seasons' => []
];

// Check connection
if (!$conn) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

// Process based on request type
try {
    // GET request - fetch crop seasons data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['season_id'])) {
            // Get specific season by ID
            $season_id = intval($_GET['season_id']);
            $query = "SELECT season_id, season_name, start_month, end_month, description, planting_recommendations 
                      FROM crop_seasons WHERE season_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $season_id);
        } elseif (isset($_GET['product_id'])) {
            // Get seasons for a specific product
            $product_id = intval($_GET['product_id']);
            $query = "SELECT cs.season_id, cs.season_name, cs.start_month, cs.end_month, 
                      cs.description, cs.planting_recommendations 
                      FROM crop_seasons cs 
                      JOIN product_seasons ps ON cs.season_id = ps.season_id 
                      WHERE ps.product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $product_id);
        } elseif (isset($_GET['barangay_id'])) {
            // Get seasons for a specific barangay
            $barangay_id = intval($_GET['barangay_id']);
            $query = "SELECT DISTINCT cs.season_id, cs.season_name, cs.start_month, cs.end_month, 
                      cs.description, cs.planting_recommendations 
                      FROM crop_seasons cs 
                      JOIN barangay_products bp ON cs.season_id = bp.season_id 
                      WHERE bp.barangay_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $barangay_id);
        } else {
            // Get all seasons
            $query = "SELECT season_id, season_name, start_month, end_month, description, planting_recommendations 
                      FROM crop_seasons ORDER BY start_month";
            $stmt = $conn->prepare($query);
        }

        // Execute query
        $stmt->execute();
        $result = $stmt->get_result();
        $seasons = [];

        while ($row = $result->fetch_assoc()) {
            // Format season data
            $seasons[] = [
                'season_id' => (int)$row['season_id'],
                'season_name' => $row['season_name'],
                'start_month' => (int)$row['start_month'],
                'end_month' => (int)$row['end_month'],
                'description' => $row['description'],
                'planting_recommendations' => $row['planting_recommendations']
            ];
        }

        if (count($seasons) > 0) {
            $response['success'] = true;
            $response['message'] = 'Crop seasons retrieved successfully';
            $response['seasons'] = $seasons;
        } else {
            $response['success'] = true; // Still success but with empty data
            $response['message'] = 'No crop seasons found';
            $response['seasons'] = [];
        }
    } 
    // POST request - create new crop season
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Parse the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['season_name']) || !isset($data['start_month']) || !isset($data['end_month'])) {
            $response['message'] = 'Missing required fields: season_name, start_month, and end_month are required';
            echo json_encode($response);
            exit;
        }
        
        $season_name = trim($data['season_name']);
        $start_month = intval($data['start_month']);
        $end_month = intval($data['end_month']);
        $description = isset($data['description']) ? trim($data['description']) : '';
        $planting_recommendations = isset($data['planting_recommendations']) ? trim($data['planting_recommendations']) : '';
        
        // Validate month ranges
        if ($start_month < 1 || $start_month > 12 || $end_month < 1 || $end_month > 12) {
            $response['message'] = 'Invalid month values. Months must be between 1 and 12';
            echo json_encode($response);
            exit;
        }
        
        // Check if season name already exists
        $check_query = "SELECT season_id FROM crop_seasons WHERE season_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('s', $season_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $response['message'] = 'A crop season with this name already exists';
            echo json_encode($response);
            exit;
        }
        
        // Insert new season
        $insert_query = "INSERT INTO crop_seasons (season_name, start_month, end_month, description, planting_recommendations) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('siiss', $season_name, $start_month, $end_month, $description, $planting_recommendations);
        
        if ($insert_stmt->execute()) {
            $new_season_id = $conn->insert_id;
            $response['success'] = true;
            $response['message'] = 'Crop season created successfully';
            $response['season'] = [
                'season_id' => $new_season_id,
                'season_name' => $season_name,
                'start_month' => $start_month,
                'end_month' => $end_month,
                'description' => $description,
                'planting_recommendations' => $planting_recommendations
            ];
            
            // Log activity
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
            if ($user_id) {
                $action = "Created new crop season: $season_name (ID: $new_season_id)";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param('is', $user_id, $action);
                $log_stmt->execute();
            }
        } else {
            $response['message'] = 'Failed to create crop season: ' . $conn->error;
        }
    } 
    // PUT request - update existing crop season
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Parse the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['season_id'])) {
            $response['message'] = 'Missing required field: season_id';
            echo json_encode($response);
            exit;
        }
        
        $season_id = intval($data['season_id']);
        
        // Check if the season exists
        $check_query = "SELECT season_id FROM crop_seasons WHERE season_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $season_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $response['message'] = 'Crop season not found';
            echo json_encode($response);
            exit;
        }
        
        // Prepare update data
        $fields = [];
        $params = [];
        $types = '';
        
        if (isset($data['season_name'])) {
            $fields[] = 'season_name = ?';
            $params[] = trim($data['season_name']);
            $types .= 's';
        }
        
        if (isset($data['start_month'])) {
            $start_month = intval($data['start_month']);
            if ($start_month < 1 || $start_month > 12) {
                $response['message'] = 'Invalid start_month. Must be between 1 and 12';
                echo json_encode($response);
                exit;
            }
            $fields[] = 'start_month = ?';
            $params[] = $start_month;
            $types .= 'i';
        }
        
        if (isset($data['end_month'])) {
            $end_month = intval($data['end_month']);
            if ($end_month < 1 || $end_month > 12) {
                $response['message'] = 'Invalid end_month. Must be between 1 and 12';
                echo json_encode($response);
                exit;
            }
            $fields[] = 'end_month = ?';
            $params[] = $end_month;
            $types .= 'i';
        }
        
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = trim($data['description']);
            $types .= 's';
        }
        
        if (isset($data['planting_recommendations'])) {
            $fields[] = 'planting_recommendations = ?';
            $params[] = trim($data['planting_recommendations']);
            $types .= 's';
        }
        
        // If no fields to update
        if (empty($fields)) {
            $response['message'] = 'No fields to update';
            echo json_encode($response);
            exit;
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Build and execute update query
            $update_query = "UPDATE crop_seasons SET " . implode(", ", $fields) . " WHERE season_id = ?";
            $params[] = $season_id;
            $types .= 'i';
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param($types, ...$params);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update crop season: " . $conn->error);
            }
            
            // Log activity
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : null;
            if ($user_id) {
                $action = "Updated crop season ID: $season_id";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param('is', $user_id, $action);
                $log_stmt->execute();
            }
            
            // Fetch updated season data
            $fetch_query = "SELECT season_id, season_name, start_month, end_month, description, planting_recommendations 
                           FROM crop_seasons WHERE season_id = ?";
            $fetch_stmt = $conn->prepare($fetch_query);
            $fetch_stmt->bind_param('i', $season_id);
            $fetch_stmt->execute();
            $fetch_result = $fetch_stmt->get_result();
            $updated_season = $fetch_result->fetch_assoc();
            
            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Crop season updated successfully';
            $response['season'] = [
                'season_id' => (int)$updated_season['season_id'],
                'season_name' => $updated_season['season_name'],
                'start_month' => (int)$updated_season['start_month'],
                'end_month' => (int)$updated_season['end_month'],
                'description' => $updated_season['description'],
                'planting_recommendations' => $updated_season['planting_recommendations']
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = $e->getMessage();
        }
    } 
    // DELETE request - delete crop season
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['season_id'])) {
            $response['message'] = 'Missing required parameter: season_id';
            echo json_encode($response);
            exit;
        }
        
        $season_id = intval($_GET['season_id']);
        
        // Begin transaction to handle dependencies
        $conn->begin_transaction();
        
        try {
            // First check if season is in use in barangay_products
            $usage_check = "SELECT COUNT(*) as count FROM barangay_products WHERE season_id = ?";
            $check_stmt = $conn->prepare($usage_check);
            $check_stmt->bind_param('i', $season_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            
            if ($check_row['count'] > 0) {
                throw new Exception("Cannot delete this season as it is associated with " . $check_row['count'] . " barangay products");
            }
            
            // Check if season is used in product_seasons
            $product_check = "SELECT COUNT(*) as count FROM product_seasons WHERE season_id = ?";
            $product_stmt = $conn->prepare($product_check);
            $product_stmt->bind_param('i', $season_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product_row = $product_result->fetch_assoc();
            
            if ($product_row['count'] > 0) {
                throw new Exception("Cannot delete this season as it is associated with " . $product_row['count'] . " products");
            }
            
            // Now safe to delete the season
            $delete_query = "DELETE FROM crop_seasons WHERE season_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $season_id);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to delete crop season: " . $conn->error);
            }
            
            if ($delete_stmt->affected_rows === 0) {
                throw new Exception("Crop season not found");
            }
            
            // Log activity
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
            if ($user_id) {
                $action = "Deleted crop season ID: $season_id";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param('is', $user_id, $action);
                $log_stmt->execute();
            }
            
            // Commit the transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Crop season deleted successfully';
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = 'Method not allowed';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    // Close connections
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);