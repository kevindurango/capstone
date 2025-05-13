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
    'message' => 'Invalid request'
];

// Check connection
if (!$conn) {
    $response['message'] = 'Database connection failed: ' . mysqli_connect_error();
    echo json_encode($response);
    exit;
}

// Process based on request type
try {
    // GET - Retrieve product season associations
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if retrieving for a specific product or season
        if (isset($_GET['product_id'])) {
            $product_id = intval($_GET['product_id']);
            
            // Get all seasons associated with this product
            $query = "SELECT ps.product_season_id, ps.product_id, ps.season_id, ps.notes,
                      cs.season_name, cs.start_month, cs.end_month, cs.description, cs.planting_recommendations,
                      p.name as product_name, p.unit_type
                      FROM product_seasons ps
                      JOIN products p ON ps.product_id = p.product_id
                      JOIN crop_seasons cs ON ps.season_id = cs.season_id
                      WHERE ps.product_id = ?
                      ORDER BY cs.start_month";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $product_id);
            
        } elseif (isset($_GET['season_id'])) {
            $season_id = intval($_GET['season_id']);
            
            // Get all products associated with this season
            $query = "SELECT ps.product_season_id, ps.product_id, ps.season_id, ps.notes,
                      p.name as product_name, p.description as product_description, 
                      p.price, p.unit_type, p.image,
                      cs.season_name
                      FROM product_seasons ps
                      JOIN products p ON ps.product_id = p.product_id
                      JOIN crop_seasons cs ON ps.season_id = cs.season_id
                      WHERE ps.season_id = ?
                      ORDER BY p.name";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $season_id);
            
        } elseif (isset($_GET['association_id'])) {
            $association_id = intval($_GET['association_id']);
            
            // Get a specific association
            $query = "SELECT ps.product_season_id, ps.product_id, ps.season_id, ps.notes,
                      p.name as product_name, p.description as product_description, 
                      p.price, p.unit_type, p.image,
                      cs.season_name, cs.start_month, cs.end_month, cs.description, cs.planting_recommendations
                      FROM product_seasons ps
                      JOIN products p ON ps.product_id = p.product_id
                      JOIN crop_seasons cs ON ps.season_id = cs.season_id
                      WHERE ps.product_season_id = ?";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $association_id);
            
        } else {
            // Get all product-season associations
            $query = "SELECT ps.product_season_id, ps.product_id, ps.season_id, ps.notes,
                      p.name as product_name, 
                      cs.season_name
                      FROM product_seasons ps
                      JOIN products p ON ps.product_id = p.product_id
                      JOIN crop_seasons cs ON ps.season_id = cs.season_id
                      ORDER BY p.name, cs.start_month";
                      
            $stmt = $conn->prepare($query);
        }
        
        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();
        $associations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Format the association data
            $association = [
                'product_season_id' => (int)$row['product_season_id'],
                'product_id' => (int)$row['product_id'],
                'season_id' => (int)$row['season_id'],
                'product_name' => $row['product_name'],
                'season_name' => $row['season_name'],
                'notes' => $row['notes']
            ];
            
            // Add additional fields if they exist
            if (isset($row['start_month'])) {
                $association['start_month'] = (int)$row['start_month'];
            }
            
            if (isset($row['end_month'])) {
                $association['end_month'] = (int)$row['end_month'];
            }
            
            if (isset($row['description'])) {
                $association['season_description'] = $row['description'];
            }
            
            if (isset($row['planting_recommendations'])) {
                $association['planting_recommendations'] = $row['planting_recommendations'];
            }
            
            if (isset($row['product_description'])) {
                $association['product_description'] = $row['product_description'];
            }
            
            if (isset($row['price'])) {
                $association['price'] = (float)$row['price'];
            }
            
            if (isset($row['unit_type'])) {
                $association['unit_type'] = $row['unit_type'];
            }
            
            if (isset($row['image'])) {
                $association['image'] = $row['image'];
            }
            
            $associations[] = $association;
        }
        
        if (count($associations) > 0) {
            $response['success'] = true;
            $response['message'] = 'Product season associations retrieved successfully';
            $response['associations'] = $associations;
        } else {
            $response['success'] = true; // Still success but with empty data
            $response['message'] = 'No product season associations found';
            $response['associations'] = [];
        }
    } 
    // POST - Create a new product-season association
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Parse input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['product_id']) || !isset($data['season_id'])) {
            $response['message'] = 'Missing required fields: product_id and season_id are required';
            echo json_encode($response);
            exit;
        }
        
        $product_id = intval($data['product_id']);
        $season_id = intval($data['season_id']);
        $notes = isset($data['notes']) ? trim($data['notes']) : null;
        
        // Check if product exists
        $product_check = "SELECT product_id FROM products WHERE product_id = ?";
        $prod_stmt = $conn->prepare($product_check);
        $prod_stmt->bind_param('i', $product_id);
        $prod_stmt->execute();
        $prod_result = $prod_stmt->get_result();
        
        if ($prod_result->num_rows === 0) {
            $response['message'] = 'Product not found';
            echo json_encode($response);
            exit;
        }
        
        // Check if season exists
        $season_check = "SELECT season_id FROM crop_seasons WHERE season_id = ?";
        $season_stmt = $conn->prepare($season_check);
        $season_stmt->bind_param('i', $season_id);
        $season_stmt->execute();
        $season_result = $season_stmt->get_result();
        
        if ($season_result->num_rows === 0) {
            $response['message'] = 'Crop season not found';
            echo json_encode($response);
            exit;
        }
        
        // Check if association already exists
        $assoc_check = "SELECT product_season_id FROM product_seasons WHERE product_id = ? AND season_id = ?";
        $assoc_stmt = $conn->prepare($assoc_check);
        $assoc_stmt->bind_param('ii', $product_id, $season_id);
        $assoc_stmt->execute();
        $assoc_result = $assoc_stmt->get_result();
        
        if ($assoc_result->num_rows > 0) {
            $response['message'] = 'This product is already associated with this season';
            echo json_encode($response);
            exit;
        }
        
        // Insert new association
        $insert_query = "INSERT INTO product_seasons (product_id, season_id, notes) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('iis', $product_id, $season_id, $notes);
        
        if ($insert_stmt->execute()) {
            $new_id = $conn->insert_id;
            
            // Get product and season names for the response
            $names_query = "SELECT p.name as product_name, cs.season_name 
                           FROM products p, crop_seasons cs 
                           WHERE p.product_id = ? AND cs.season_id = ?";
            $names_stmt = $conn->prepare($names_query);
            $names_stmt->bind_param('ii', $product_id, $season_id);
            $names_stmt->execute();
            $names_result = $names_stmt->get_result();
            $names = $names_result->fetch_assoc();
            
            $response['success'] = true;
            $response['message'] = 'Product-season association created successfully';
            $response['association'] = [
                'product_season_id' => $new_id,
                'product_id' => $product_id,
                'season_id' => $season_id,
                'product_name' => $names['product_name'],
                'season_name' => $names['season_name'],
                'notes' => $notes
            ];
        } else {
            $response['message'] = 'Failed to create product-season association: ' . $conn->error;
        }
    } 
    // PUT - Update existing association
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Parse input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['product_season_id'])) {
            $response['message'] = 'Missing required field: product_season_id';
            echo json_encode($response);
            exit;
        }
        
        $association_id = intval($data['product_season_id']);
        
        // Check if the association exists
        $check_query = "SELECT * FROM product_seasons WHERE product_season_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $association_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $response['message'] = 'Association not found';
            echo json_encode($response);
            exit;
        }
        
        // Only notes can be updated for existing associations
        if (isset($data['notes'])) {
            $notes = trim($data['notes']);
            
            $update_query = "UPDATE product_seasons SET notes = ? WHERE product_season_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('si', $notes, $association_id);
            
            if ($update_stmt->execute()) {
                // Get updated association data
                $fetch_query = "SELECT ps.*, p.name as product_name, cs.season_name 
                               FROM product_seasons ps
                               JOIN products p ON ps.product_id = p.product_id
                               JOIN crop_seasons cs ON ps.season_id = cs.season_id
                               WHERE ps.product_season_id = ?";
                $fetch_stmt = $conn->prepare($fetch_query);
                $fetch_stmt->bind_param('i', $association_id);
                $fetch_stmt->execute();
                $fetch_result = $fetch_stmt->get_result();
                $updated = $fetch_result->fetch_assoc();
                
                $response['success'] = true;
                $response['message'] = 'Product-season association updated successfully';
                $response['association'] = [
                    'product_season_id' => (int)$updated['product_season_id'],
                    'product_id' => (int)$updated['product_id'],
                    'season_id' => (int)$updated['season_id'],
                    'product_name' => $updated['product_name'],
                    'season_name' => $updated['season_name'],
                    'notes' => $updated['notes']
                ];
            } else {
                $response['message'] = 'Failed to update association: ' . $conn->error;
            }
        } else {
            $response['message'] = 'No data to update. Only notes can be modified for existing associations.';
        }
    } 
    // DELETE - Remove a product-season association
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['association_id'])) {
            $response['message'] = 'Missing required parameter: association_id';
            echo json_encode($response);
            exit;
        }
        
        $association_id = intval($_GET['association_id']);
        
        // Check if association exists
        $check_query = "SELECT * FROM product_seasons WHERE product_season_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $association_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $response['message'] = 'Association not found';
            echo json_encode($response);
            exit;
        }
        
        // Delete the association
        $delete_query = "DELETE FROM product_seasons WHERE product_season_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param('i', $association_id);
        
        if ($delete_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Product-season association deleted successfully';
        } else {
            $response['message'] = 'Failed to delete association: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Method not allowed';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    // Close connections
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);