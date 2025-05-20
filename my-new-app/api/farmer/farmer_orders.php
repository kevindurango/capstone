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
    
    // STATS REQUEST - Handle order statistics request
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['farmer_id']) && isset($_GET['stats'])) {
        $farmer_id = intval($_GET['farmer_id']);
        
        // Verify user exists and is a farmer
        $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2";
        $stmt = $conn->prepare($user_check_query);
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            throw new Exception('User is not found or not a farmer');
        }
        
        // Get count of orders by status - updated to match all valid status values
        $stats_query = "
            SELECT 
                COUNT(DISTINCT o.order_id) AS total,
                SUM(CASE WHEN o.order_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN o.order_status = 'processing' THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN o.order_status = 'ready' THEN 1 ELSE 0 END) AS ready,
                SUM(CASE WHEN o.order_status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN o.order_status = 'canceled' THEN 1 ELSE 0 END) AS canceled
            FROM orders o
            JOIN orderitems oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE p.farmer_id = ?
        ";
        
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        $stats_data = $stats_result->fetch_assoc();
        
        // Convert string values to integers
        $stats = [
            'total' => (int)$stats_data['total'],
            'pending' => (int)$stats_data['pending'],
            'processing' => (int)$stats_data['processing'],
            'ready' => (int)$stats_data['ready'],
            'completed' => (int)$stats_data['completed'],
            'canceled' => (int)$stats_data['canceled']
        ];
        
        // Log activity for audit trail
        $action = "Farmer ID: $farmer_id viewed their order statistics";
        $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $farmer_id, $action);
        $log_stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Order statistics retrieved successfully';
        $response['stats'] = $stats;
        
        echo json_encode($response);
        exit;
    }
    
    // ORDER DETAIL REQUEST - Handle specific order details request
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_id']) && isset($_GET['details'])) {
        $order_id = intval($_GET['order_id']);
        
        // Get order details - products that belong to this farmer in the order
        $details_query = "
            SELECT 
                oi.order_item_id as order_detail_id, 
                p.name as product_name, 
                oi.quantity, 
                oi.price as unit_price,
                (oi.quantity * oi.price) as subtotal,
                p.unit_type
            FROM orderitems oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.order_item_id
        ";
        
        $stmt = $conn->prepare($details_query);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        
        $order_details = [];
        while ($row = $details_result->fetch_assoc()) {
            $order_details[] = [
                'order_detail_id' => (int)$row['order_detail_id'],
                'product_name' => $row['product_name'],
                'quantity' => (int)$row['quantity'],
                'unit_price' => (float)$row['unit_price'],
                'subtotal' => (float)$row['subtotal'],
                'unit_type' => $row['unit_type']
            ];
        }
        
        $response['success'] = true;
        $response['message'] = 'Order details retrieved successfully';
        $response['order_details'] = $order_details;
        
        echo json_encode($response);
        exit;
    }
    
    // STATUS UPDATE REQUEST - Handle order status update via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action']) && $input['action'] === 'update_status' && 
            isset($input['order_id']) && isset($input['status'])) {
            
            $order_id = intval($input['order_id']);
            $status = $input['status'];
            $farmer_id = isset($input['farmer_id']) ? intval($input['farmer_id']) : null;
            
            // Update valid statuses to match database enum values
            $valid_statuses = ['pending', 'processing', 'ready', 'completed', 'canceled'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('Invalid status. Valid values are: pending, processing, ready, completed, canceled');
            }
            
            // Check if order exists and contains products from this farmer
            $check_query = "
                SELECT DISTINCT o.order_id
                FROM orders o
                JOIN orderitems oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE o.order_id = ? AND p.farmer_id = ?
            ";
            
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('ii', $order_id, $farmer_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception('Order not found or does not contain your products');
            }
            
            // Update order status
            $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $status, $order_id);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception('Failed to update order status');
            }
            
            // Log the status update
            if ($farmer_id) {
                $action = "Updated order #$order_id status to $status";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("is", $farmer_id, $action);
                $log_stmt->execute();
            }
            
            $response['success'] = true;
            $response['message'] = "Order #$order_id status updated to $status";
            
            echo json_encode($response);
            exit;
        }
    }
    
    // ORDERS LIST REQUEST - Handle orders list request (original functionality)
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
                
                // Get payment status for the front-end display
                $payment_status = $payment_info ? $payment_info['payment_status'] : 'pending';
                
                // Calculate items count
                $items_count = count($items);
                
                // Format pickup date and time for the front-end
                $pickup_date = null;
                $pickup_time = null;
                
                if ($pickup_info && $pickup_info['pickup_date']) {
                    $pickup_datetime = new DateTime($pickup_info['pickup_date']);
                    $pickup_date = $pickup_datetime->format('Y-m-d');
                    $pickup_time = $pickup_datetime->format('H:i');
                }
                
                $orders[] = [
                    'order_id' => (int)$order_row['order_id'],
                    'consumer_id' => (int)$order_row['consumer_id'],
                    'customer_name' => $order_row['consumer_first_name'] . ' ' . $order_row['consumer_last_name'],
                    'consumer_contact' => $order_row['consumer_contact'],
                    'status' => $order_row['order_status'],
                    'order_date' => $order_row['order_date'],
                    'pickup_details' => $order_row['pickup_details'],
                    'total_amount' => (float)$total_amount,
                    'payment_status' => $payment_status,
                    'items_count' => $items_count,
                    'pickup_date' => $pickup_date,
                    'pickup_time' => $pickup_time,
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