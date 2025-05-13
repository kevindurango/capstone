<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Handle GET request to fetch feedback
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $feedback = [];
        $query = "";
        
        // Check if request is for specific product
        if (isset($_GET['product_id'])) {
            $product_id = filter_var($_GET['product_id'], FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT f.*, 
                        u.username, u.first_name, u.last_name,
                        p.name as product_name, p.image as product_image,
                        o.order_id, 
                        CONCAT('Order #', o.order_id) as order_reference
                    FROM feedback f
                    LEFT JOIN users u ON f.user_id = u.user_id
                    LEFT JOIN products p ON f.product_id = p.product_id
                    LEFT JOIN orders o ON f.order_id = o.order_id
                    WHERE f.product_id = ?
                    ORDER BY f.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $product_id);
        } 
        // Check if request is for specific order
        elseif (isset($_GET['order_id'])) {
            $order_id = filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT f.*, 
                        u.username, u.first_name, u.last_name,
                        p.name as product_name, p.image as product_image,
                        o.order_id,
                        CONCAT('Order #', o.order_id) as order_reference
                    FROM feedback f
                    LEFT JOIN users u ON f.user_id = u.user_id
                    LEFT JOIN products p ON f.product_id = p.product_id
                    LEFT JOIN orders o ON f.order_id = o.order_id
                    WHERE f.order_id = ?
                    ORDER BY f.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $order_id);
        }
        // Check if request is for farmer's products feedback
        elseif (isset($_GET['farmer_id'])) {
            $farmer_id = filter_var($_GET['farmer_id'], FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT f.*, 
                        u.username, u.first_name, u.last_name,
                        p.name as product_name, p.image as product_image,
                        o.order_id,
                        CONCAT('Order #', o.order_id) as order_reference
                    FROM feedback f
                    LEFT JOIN users u ON f.user_id = u.user_id
                    LEFT JOIN products p ON f.product_id = p.product_id
                    LEFT JOIN orders o ON f.order_id = o.order_id
                    WHERE p.farmer_id = ?
                    ORDER BY f.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $farmer_id);
        } 
        // Check if request is for eligible orders (completed orders without feedback)
        elseif (isset($_GET['eligible_orders'])) {
            $user_id = filter_var($_GET['eligible_orders'], FILTER_SANITIZE_NUMBER_INT);
            
            // Query to get completed orders that don't have feedback yet
            $query = "SELECT o.order_id, o.order_date, 
                      CONCAT('Order #', o.order_id) as order_reference,
                      oi.product_id, p.name as product_name
                      FROM orders o
                      JOIN orderitems oi ON o.order_id = oi.order_id
                      JOIN products p ON oi.product_id = p.product_id
                      LEFT JOIN feedback f ON (o.order_id = f.order_id AND oi.product_id = f.product_id)
                      WHERE o.consumer_id = ? 
                      AND o.order_status = 'completed' 
                      AND f.feedback_id IS NULL
                      GROUP BY o.order_id, oi.product_id
                      ORDER BY o.order_date DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $orders = [];
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'orders' => $orders
                ]);
                exit;
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch eligible orders',
                    'orders' => []
                ]);
                exit;
            }
        }
        // Fetch all feedback
        else {
            $query = "SELECT f.*, 
                        u.username, u.first_name, u.last_name,
                        p.name as product_name, p.image as product_image,
                        o.order_id,
                        CONCAT('Order #', o.order_id) as order_reference
                    FROM feedback f
                    LEFT JOIN users u ON f.user_id = u.user_id
                    LEFT JOIN products p ON f.product_id = p.product_id
                    LEFT JOIN orders o ON f.order_id = o.order_id
                    ORDER BY f.created_at DESC";
            
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Get responses for this feedback
                $responses_query = "SELECT fr.*, 
                                      u.username, u.first_name, u.last_name
                                  FROM feedback_responses fr
                                  LEFT JOIN users u ON fr.responded_by = u.user_id
                                  WHERE fr.feedback_id = ?
                                  ORDER BY fr.response_date ASC";
                                  
                $responses_stmt = $conn->prepare($responses_query);
                $responses_stmt->bind_param('i', $row['feedback_id']);
                $responses_stmt->execute();
                $responses_result = $responses_stmt->get_result();
                
                $responses = [];
                while ($response_row = $responses_result->fetch_assoc()) {
                    $responses[] = [
                        'response_id' => $response_row['response_id'],
                        'feedback_id' => $response_row['feedback_id'],
                        'response_text' => $response_row['response_text'],
                        'responded_by' => $response_row['responded_by'],
                        'response_date' => $response_row['response_date'],
                        'username' => $response_row['username'],
                        'first_name' => $response_row['first_name'],
                        'last_name' => $response_row['last_name']
                    ];
                }
                
                // Add responses to feedback item
                $row['responses'] = $responses;
                $feedback[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'feedback' => $feedback
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch feedback'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
// Handle POST request to submit feedback
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data);
        
        // Validate required fields
        if (!isset($data->user_id) || !isset($data->product_id) || 
            !isset($data->feedback_text) || !isset($data->rating)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            exit;
        }
        
        // Sanitize inputs
        $user_id = filter_var($data->user_id, FILTER_SANITIZE_NUMBER_INT);
        $product_id = filter_var($data->product_id, FILTER_SANITIZE_NUMBER_INT);
        $feedback_text = htmlspecialchars(strip_tags($data->feedback_text));
        $rating = filter_var($data->rating, FILTER_SANITIZE_NUMBER_INT);
        
        // Get order_id if provided
        $order_id = null;
        if (isset($data->order_id)) {
            $order_id = filter_var($data->order_id, FILTER_SANITIZE_NUMBER_INT);
        }
        
        // Validate rating (1-5)
        if ($rating < 1 || $rating > 5) {
            echo json_encode([
                'success' => false,
                'message' => 'Rating must be between 1 and 5'
            ]);
            exit;
        }
        
        // Check if this user has already submitted feedback for this product in this order
        if ($order_id) {
            $check_query = "SELECT feedback_id FROM feedback 
                           WHERE user_id = ? AND product_id = ? AND order_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('iii', $user_id, $product_id, $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You have already submitted feedback for this product in this order'
                ]);
                exit;
            }
        }
        
        // Determine the farmer ID if not provided
        $farmer_id = null;
        $farmer_query = "SELECT farmer_id FROM products WHERE product_id = ?";
        $farmer_stmt = $conn->prepare($farmer_query);
        $farmer_stmt->bind_param('i', $product_id);
        $farmer_stmt->execute();
        $farmer_result = $farmer_stmt->get_result();
        
        if ($farmer_result->num_rows > 0) {
            $farmer_row = $farmer_result->fetch_assoc();
            $farmer_id = $farmer_row['farmer_id'];
        }
        
        // Insert feedback
        if ($order_id) {
            $query = "INSERT INTO feedback 
                    (user_id, product_id, order_id, feedback_text, rating, created_at, status, farmer_id) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'pending', ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iiisii', $user_id, $product_id, $order_id, $feedback_text, $rating, $farmer_id);
        } else {
            $query = "INSERT INTO feedback 
                    (user_id, product_id, feedback_text, rating, created_at, status, farmer_id) 
                    VALUES (?, ?, ?, ?, NOW(), 'pending', ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iisii', $user_id, $product_id, $feedback_text, $rating, $farmer_id);
        }
        
        if ($stmt->execute()) {
            $feedback_id = $stmt->insert_id;
            
            // Create a notification for the farmer
            if ($farmer_id) {
                $notification_message = "You've received new feedback for product #".$product_id;
                if ($order_id) {
                    $notification_message .= " from Order #".$order_id;
                }
                
                $notification_query = "INSERT INTO notifications 
                                       (user_id, message, is_read, created_at, type, reference_id) 
                                       VALUES (?, ?, 0, NOW(), 'new_feedback', ?)";
                $notification_stmt = $conn->prepare($notification_query);
                $notification_stmt->bind_param('isi', $farmer_id, $notification_message, $feedback_id);
                $notification_stmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedback_id' => $feedback_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit feedback'
            ]);
        }
    } catch (Exception $e) {
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