<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    'orders' => []
];

// Process based on request type
try {
    // Get database connection
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Handle both GET and POST requests for fetching orders
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get farmer ID either from query parameter or POST data
        $farmer_id = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['farmer_id'])) {
            $farmer_id = intval($_GET['farmer_id']);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['farmer_id'])) {
                $farmer_id = intval($input['farmer_id']);
            }
        }
        
        if (!$farmer_id) {
            throw new Exception('Missing required parameter: farmer_id');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user exists and is a farmer
            $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2"; // role_id 2 is for farmers
            $stmt = $conn->prepare($user_check_query);
            $stmt->bind_param('i', $farmer_id);
            $stmt->execute();
            $user_result = $stmt->get_result();

            if ($user_result->num_rows === 0) {
                throw new Exception('User is not found or not a farmer');
            }

            // Get orders containing products from this farmer
            $orders_query = "
                SELECT DISTINCT 
                    o.order_id, 
                    o.consumer_id,
                    o.order_status,
                    o.order_date,
                    o.pickup_details,
                    u.first_name as consumer_first_name,
                    u.last_name as consumer_last_name,
                    u.contact_number as consumer_contact
                FROM orders o
                JOIN orderitems oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                JOIN users u ON o.consumer_id = u.user_id
                WHERE p.farmer_id = ?
                ORDER BY o.order_date DESC
            ";

            $stmt = $conn->prepare($orders_query);
            $stmt->bind_param('i', $farmer_id);
            $stmt->execute();
            $orders_result = $stmt->get_result();

            $orders = [];
            $processed_order_ids = [];

            while ($order_row = $orders_result->fetch_assoc()) {
                $order_id = $order_row['order_id'];
                
                // Skip if we've already processed this order
                if (in_array($order_id, $processed_order_ids)) {
                    continue;
                }
                
                $processed_order_ids[] = $order_id;
                
                // Get order items for this farmer's products
                $items_query = "
                    SELECT 
                        oi.order_item_id, 
                        oi.product_id, 
                        p.name as product_name, 
                        oi.quantity, 
                        oi.price,
                        p.unit_type,
                        p.image
                    FROM orderitems oi
                    JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = ? AND p.farmer_id = ?
                ";
                
                $items_stmt = $conn->prepare($items_query);
                $items_stmt->bind_param('ii', $order_id, $farmer_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                $items = [];
                $total_amount = 0;
                
                while ($item_row = $items_result->fetch_assoc()) {
                    // Normalize image path if it exists
                    $image_path = $item_row['image'];
                    if (!empty($image_path)) {
                        // Remove any leading slashes
                        $image_path = ltrim($image_path, '/');
                        
                        // If path doesn't have correct format, extract just the filename
                        if (!preg_match('#^uploads/products/[^/]+$#', $image_path)) {
                            $filename = basename($image_path);
                            $image_path = 'uploads/products/' . $filename;
                        }
                    }
                    
                    $item_total = $item_row['price'] * $item_row['quantity'];
                    $total_amount += $item_total;
                    
                    $items[] = [
                        'order_item_id' => (int)$item_row['order_item_id'],
                        'product_id' => (int)$item_row['product_id'],
                        'product_name' => $item_row['product_name'],
                        'quantity' => (int)$item_row['quantity'],
                        'price' => (float)$item_row['price'],
                        'total' => (float)$item_total,
                        'unit_type' => $item_row['unit_type'],
                        'image' => $image_path
                    ];
                }
                
                // Get payment information
                $payment_query = "
                    SELECT 
                        payment_id, 
                        payment_method, 
                        payment_status, 
                        payment_date,
                        amount,
                        transaction_reference
                    FROM payments 
                    WHERE order_id = ?
                    ORDER BY payment_date DESC 
                    LIMIT 1
                ";
                
                $payment_stmt = $conn->prepare($payment_query);
                $payment_stmt->bind_param('i', $order_id);
                $payment_stmt->execute();
                $payment_result = $payment_stmt->get_result();
                $payment_info = $payment_result->fetch_assoc();
                
                // Get pickup information
                $pickup_query = "
                    SELECT 
                        pickup_id, 
                        pickup_status, 
                        pickup_date, 
                        pickup_location,
                        pickup_notes,
                        office_location,
                        contact_person
                    FROM pickups 
                    WHERE order_id = ?
                ";
                
                $pickup_stmt = $conn->prepare($pickup_query);
                $pickup_stmt->bind_param('i', $order_id);
                $pickup_stmt->execute();
                $pickup_result = $pickup_stmt->get_result();
                $pickup_info = $pickup_result->fetch_assoc();
                
                $orders[] = [
                    'order_id' => (int)$order_row['order_id'],
                    'consumer_id' => (int)$order_row['consumer_id'],
                    'consumer_name' => $order_row['consumer_first_name'] . ' ' . $order_row['consumer_last_name'],
                    'consumer_contact' => $order_row['consumer_contact'],
                    'status' => $order_row['order_status'],
                    'order_date' => $order_row['order_date'],
                    'pickup_details' => $order_row['pickup_details'],
                    'total_amount' => (float)$total_amount,
                    'items' => $items,
                    'payment' => $payment_info ? [
                        'payment_id' => (int)$payment_info['payment_id'],
                        'method' => $payment_info['payment_method'],
                        'status' => $payment_info['payment_status'],
                        'date' => $payment_info['payment_date'],
                        'amount' => (float)$payment_info['amount'],
                        'reference' => $payment_info['transaction_reference']
                    ] : null,
                    'pickup' => $pickup_info ? [
                        'pickup_id' => (int)$pickup_info['pickup_id'],
                        'status' => $pickup_info['pickup_status'],
                        'date' => $pickup_info['pickup_date'],
                        'location' => $pickup_info['pickup_location'],
                        'notes' => $pickup_info['pickup_notes'],
                        'office_location' => $pickup_info['office_location'],
                        'contact_person' => $pickup_info['contact_person']
                    ] : null
                ];
            }
            
            // Log activity for audit trail
            $action = "Farmer ID: $farmer_id viewed their orders";
            $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $farmer_id, $action);
            $log_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Always set success to true even if no orders found
            $response['success'] = true;
            
            if (count($orders) > 0) {
                $response['message'] = 'Orders retrieved successfully';
                $response['orders'] = $orders;
            } else {
                $response['message'] = 'No orders found for your products';
                $response['orders'] = [];
            }
            
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Farmer orders API error: " . $e->getMessage());
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
?>