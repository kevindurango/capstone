<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include database connection
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
    'fields' => []
];

// Process based on request type
try {
    // Get database connection
    $conn = getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get farmer ID from query parameter
        if (!isset($_GET['farmer_id'])) {
            throw new Exception('Missing required parameter: farmer_id');
        }

        $farmer_id = intval($_GET['farmer_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user is a farmer
            $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2"; // role_id 2 is for farmers
            $stmt = $conn->prepare($user_check_query);
            $stmt->bind_param('i', $farmer_id);
            $stmt->execute();
            $user_result = $stmt->get_result();

            if ($user_result->num_rows === 0) {
                throw new Exception('User is not found or not a farmer');
            }

            // Query to get all fields for the farmer with barangay info
            $query = "SELECT ff.*, b.barangay_name 
                     FROM farmer_fields ff
                     LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                     WHERE ff.farmer_id = ?";
                     
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $farmer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $fields = [];
            while ($row = $result->fetch_assoc()) {
                $fields[] = [
                    'field_id' => (int)$row['field_id'],
                    'farmer_id' => (int)$row['farmer_id'],
                    'field_name' => $row['field_name'],
                    'field_size' => (float)$row['field_size'],
                    'field_type' => $row['field_type'],
                    'barangay_id' => (int)$row['barangay_id'],
                    'barangay_name' => $row['barangay_name'],
                    'notes' => $row['notes'],
                    'coordinates' => $row['coordinates'],
                    'created_at' => $row['created_at']
                ];
            }
            
            // If specific field_id is requested, filter for just that field
            if (isset($_GET['field_id'])) {
                $field_id = intval($_GET['field_id']);
                $fields = array_filter($fields, function($field) use ($field_id) {
                    return $field['field_id'] === $field_id;
                });
                $fields = array_values($fields); // Reset array keys
            }
            
            $response['success'] = true;
            $response['message'] = count($fields) > 0 ? 'Fields retrieved successfully' : 'No fields found for this farmer';
            $response['fields'] = $fields;
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add a new field
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['farmer_id']) || !isset($data['field_name']) || !isset($data['barangay_id'])) {
            throw new Exception('Missing required fields: farmer_id, field_name, and barangay_id are required');
        }
        
        $farmer_id = intval($data['farmer_id']);
        $field_name = trim($data['field_name']);
        $field_size = isset($data['field_size']) ? floatval($data['field_size']) : 0;
        $field_type = isset($data['field_type']) ? trim($data['field_type']) : '';
        $barangay_id = intval($data['barangay_id']);
        $notes = isset($data['notes']) ? trim($data['notes']) : '';
        $coordinates = isset($data['coordinates']) ? trim($data['coordinates']) : null;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user is a farmer
            $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2";
            $stmt = $conn->prepare($user_check_query);
            $stmt->bind_param('i', $farmer_id);
            $stmt->execute();
            $user_result = $stmt->get_result();

            if ($user_result->num_rows === 0) {
                throw new Exception('User is not found or not a farmer');
            }

            // Validate barangay exists
            $barangay_check = "SELECT barangay_id FROM barangays WHERE barangay_id = ?";
            $stmt = $conn->prepare($barangay_check);
            $stmt->bind_param('i', $barangay_id);
            $stmt->execute();
            $barangay_result = $stmt->get_result();

            if ($barangay_result->num_rows === 0) {
                throw new Exception('Invalid barangay selected');
            }

            // Insert field
            $query = "INSERT INTO farmer_fields (farmer_id, field_name, field_size, field_type, barangay_id, notes, coordinates) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isdsiss', $farmer_id, $field_name, $field_size, $field_type, $barangay_id, $notes, $coordinates);
            $result = $stmt->execute();
            
            if ($result) {
                $field_id = $conn->insert_id;
                
                // Get the inserted field with barangay info
                $query = "SELECT ff.*, b.barangay_name 
                         FROM farmer_fields ff
                         LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                         WHERE ff.field_id = ?";
                         
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $field_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $field = $result->fetch_assoc();
                
                $response['success'] = true;
                $response['message'] = 'Field created successfully';
                $response['fields'] = [[
                    'field_id' => (int)$field['field_id'],
                    'farmer_id' => (int)$field['farmer_id'],
                    'field_name' => $field['field_name'],
                    'field_size' => (float)$field['field_size'],
                    'field_type' => $field['field_type'],
                    'barangay_id' => (int)$field['barangay_id'],
                    'barangay_name' => $field['barangay_name'],
                    'notes' => $field['notes'],
                    'coordinates' => $field['coordinates'],
                    'created_at' => $field['created_at']
                ]];
                
                // Commit transaction
                $conn->commit();
            } else {
                throw new Exception('Failed to create field: ' . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update an existing field
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['field_id']) || !isset($data['farmer_id'])) {
            throw new Exception('Missing required fields: field_id and farmer_id are required');
        }
        
        $field_id = intval($data['field_id']);
        $farmer_id = intval($data['farmer_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if field exists and belongs to farmer
            $check_query = "SELECT ff.* FROM farmer_fields ff WHERE ff.field_id = ? AND ff.farmer_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('ii', $field_id, $farmer_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception('Field not found or does not belong to this farmer');
            }
            
            // Build update query based on provided fields
            $update_fields = [];
            $types = '';
            $params = [];
            
            if (isset($data['field_name'])) {
                $update_fields[] = 'field_name = ?';
                $types .= 's';
                $params[] = trim($data['field_name']);
            }
            
            if (isset($data['field_size'])) {
                $update_fields[] = 'field_size = ?';
                $types .= 'd';
                $params[] = floatval($data['field_size']);
            }
            
            if (isset($data['field_type'])) {
                $update_fields[] = 'field_type = ?';
                $types .= 's';
                $params[] = trim($data['field_type']);
            }
            
            if (isset($data['barangay_id'])) {
                $barangay_id = intval($data['barangay_id']);
                
                // Validate barangay exists
                $barangay_check = "SELECT barangay_id FROM barangays WHERE barangay_id = ?";
                $stmt = $conn->prepare($barangay_check);
                $stmt->bind_param('i', $barangay_id);
                $stmt->execute();
                $barangay_result = $stmt->get_result();
    
                if ($barangay_result->num_rows === 0) {
                    throw new Exception('Invalid barangay selected');
                }
                
                $update_fields[] = 'barangay_id = ?';
                $types .= 'i';
                $params[] = $barangay_id;
            }
            
            if (isset($data['notes'])) {
                $update_fields[] = 'notes = ?';
                $types .= 's';
                $params[] = trim($data['notes']);
            }
            
            if (isset($data['coordinates'])) {
                $update_fields[] = 'coordinates = ?';
                $types .= 's';
                $params[] = trim($data['coordinates']);
            }
            
            if (empty($update_fields)) {
                throw new Exception('No fields to update');
            }
            
            // Append field_id and farmer_id to params
            $types .= 'ii';
            $params[] = $field_id;
            $params[] = $farmer_id;
            
            // Create and execute update query
            $query = "UPDATE farmer_fields SET " . implode(', ', $update_fields) . " WHERE field_id = ? AND farmer_id = ?";
            $stmt = $conn->prepare($query);
            
            // Dynamically bind parameters
            $bind_params = array($types);
            foreach ($params as $key => $value) {
                $bind_params[] = &$params[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Get the updated field with barangay info
                $query = "SELECT ff.*, b.barangay_name 
                         FROM farmer_fields ff
                         LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                         WHERE ff.field_id = ?";
                         
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $field_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $field = $result->fetch_assoc();
                
                $response['success'] = true;
                $response['message'] = 'Field updated successfully';
                $response['fields'] = [[
                    'field_id' => (int)$field['field_id'],
                    'farmer_id' => (int)$field['farmer_id'],
                    'field_name' => $field['field_name'],
                    'field_size' => (float)$field['field_size'],
                    'field_type' => $field['field_type'],
                    'barangay_id' => (int)$field['barangay_id'],
                    'barangay_name' => $field['barangay_name'],
                    'notes' => $field['notes'],
                    'coordinates' => $field['coordinates'],
                    'created_at' => $field['created_at']
                ]];
                
                // Commit transaction
                $conn->commit();
            } else {
                throw new Exception('Failed to update field: ' . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete a field
        
        // Get farmer_id and field_id from query params
        if (!isset($_GET['farmer_id']) || !isset($_GET['field_id'])) {
            throw new Exception('Missing required parameters: farmer_id and field_id are required');
        }
        
        $farmer_id = intval($_GET['farmer_id']);
        $field_id = intval($_GET['field_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if field exists and belongs to farmer
            $check_query = "SELECT * FROM farmer_fields WHERE field_id = ? AND farmer_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('ii', $field_id, $farmer_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception('Field not found or does not belong to this farmer');
            }
            
            // Delete field
            $query = "DELETE FROM farmer_fields WHERE field_id = ? AND farmer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $field_id, $farmer_id);
            $result = $stmt->execute();
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Field deleted successfully';
                
                // Commit transaction
                $conn->commit();
            } else {
                throw new Exception('Failed to delete field: ' . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['fields'] = [];
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    echo json_encode($response);
}
?>