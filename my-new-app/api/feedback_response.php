<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data);
        
        // Validate required fields
        if (!isset($data->feedback_id) || !isset($data->response_text) || !isset($data->responded_by)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            exit;
        }
        
        // Sanitize inputs
        $feedback_id = filter_var($data->feedback_id, FILTER_SANITIZE_NUMBER_INT);
        $response_text = htmlspecialchars(strip_tags($data->response_text));
        $responded_by = filter_var($data->responded_by, FILTER_SANITIZE_NUMBER_INT);
        
        // Begin transaction
        $conn->begin_transaction();
        
        // First, check if this feedback already has a response
        $check_query = "SELECT COUNT(*) as response_count FROM feedback_responses WHERE feedback_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $feedback_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['response_count'] > 0) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'This feedback already has a response'
            ]);
            exit;
        }
        
        // Insert feedback response
        $insert_query = "INSERT INTO feedback_responses 
                        (feedback_id, response_text, responded_by, response_date) 
                        VALUES (?, ?, ?, NOW())";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('isi', $feedback_id, $response_text, $responded_by);
        
        if ($insert_stmt->execute()) {
            $response_id = $insert_stmt->insert_id;
            
            // Update feedback status to 'responded'
            $update_query = "UPDATE feedback SET status = 'responded' WHERE feedback_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('i', $feedback_id);
            
            if ($update_stmt->execute()) {
                // Log the activity
                $activity_query = "INSERT INTO activitylogs (user_id, action, action_date) 
                                VALUES (?, CONCAT('Responded to feedback ID: ', ?), NOW())";
                $activity_stmt = $conn->prepare($activity_query);
                $activity_stmt->bind_param('ii', $responded_by, $feedback_id);
                $activity_stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Response submitted successfully',
                    'response_id' => $response_id
                ]);
            } else {
                $conn->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update feedback status'
                ]);
            }
        } else {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit response'
            ]);
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Close database connection
$conn->close();
?>