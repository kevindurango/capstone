<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log incoming request data
file_put_contents('order_debug.log', 
    date('Y-m-d H:i:s') . ": " . 
    file_get_contents('php://input') . 
    "\n\n", 
    FILE_APPEND
);

// Ensure no output before JSON response
ob_start();

// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin, Authorization');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to send proper error response
function sendErrorResponse($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

// Include database connection file
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    error_log("[ERROR] Database connection failed: " . $e->getMessage());
    sendErrorResponse("Database connection error. Please check server logs.");
}

error_log("[DEBUG] Starting order process");

try {
    // Check if database connection is established properly
    if (!isset($conn) || $conn->connect_error) {
        error_log("[ERROR] Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
        sendErrorResponse("Database connection error. Please check server logs.");
    }

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse("Only POST requests are allowed", 405);
    }

    // Get request body
    $requestBody = file_get_contents('php://input');
    
    // Parse JSON data
    $orderData = json_decode($requestBody, true);
    
    error_log("[DEBUG] Received order data: " . json_encode($orderData));
    
    // Check for order status update action
    if (isset($orderData['action']) && $orderData['action'] === 'update_status') {
        // Validate required fields for status update
        if (empty($orderData) || !isset($orderData['order_id']) || !isset($orderData['status'])) {
            sendErrorResponse("Missing required fields for status update: order_id and status are required", 400);
        }
        
        $order_id = (int) $orderData['order_id'];
        $status = $orderData['status'];
        
        // Debug log the input for troubleshooting
        error_log("[DEBUG] Order status update request: " . json_encode([
            'order_id' => $order_id,
            'status' => $status,
            'has_items' => isset($orderData['items'])
        ]));
        
        // Validate status
        $validStatuses = ['pending', 'completed', 'canceled'];
        if (!in_array($status, $validStatuses)) {
            sendErrorResponse("Invalid order status. Valid statuses are: pending, completed, canceled", 400);
        }
        
        try {
            // Check if order exists
            $checkQuery = "SELECT order_id FROM orders WHERE order_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            
            if (!$checkStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $checkStmt->bind_param("i", $order_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                sendErrorResponse("Order not found with ID: " . $order_id, 404);
            }
            
            // Update order status
            $updateQuery = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            
            if (!$updateStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $updateStmt->bind_param("si", $status, $order_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update order status: " . $updateStmt->error);
            }
            
            // Log the status update
            error_log("[DEBUG] Updated order #$order_id status to $status");
            
            // Return success response
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => "Order status updated successfully to $status",
                'data' => [
                    'order_id' => $order_id,
                    'status' => $status
                ]
            ]);
            exit(); // Exit here after handling the status update
        } catch (Exception $e) {
            error_log("[ERROR] Order status update failed: " . $e->getMessage());
            sendErrorResponse("Failed to update order status: " . $e->getMessage());
        }
    }
    
    // Check for pickup scheduling action
    if (isset($orderData['action']) && $orderData['action'] === 'schedule_pickup') {
        // Schedule a new pickup or update an existing one
        $order_id = isset($orderData['order_id']) ? (int)$orderData['order_id'] : null;
        $pickup_date = isset($orderData['pickup_date']) ? $orderData['pickup_date'] : null;
        $payment_id = isset($orderData['payment_id']) ? $orderData['payment_id'] : null;
        
        error_log("[DEBUG] Processing pickup schedule: order_id=$order_id, date=$pickup_date, payment=$payment_id");
        
        // Validate input
        if (!$order_id || !$pickup_date) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: order_id and pickup_date are required'
            ]);
            exit;
        }
        
        try {
            // Check if a pickup record already exists for this order
            $checkQuery = "SELECT pickup_id FROM pickups WHERE order_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            
            if (!$checkStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $checkStmt->bind_param("i", $order_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing pickup record
                $row = $result->fetch_assoc();
                $pickup_id = $row['pickup_id'];
                
                $updateQuery = "UPDATE pickups SET 
                    pickup_date = ?, 
                    pickup_status = 'pending'";
                
                // Add payment_id to the update if provided
                $params = [$pickup_date];
                $types = "s";
                
                if ($payment_id) {
                    $updateQuery .= ", payment_id = ?";
                    $params[] = $payment_id;
                    $types .= "i";
                }
                
                $updateQuery .= " WHERE pickup_id = ?";
                $params[] = $pickup_id;
                $types .= "i";
                
                $updateStmt = $conn->prepare($updateQuery);
                
                if (!$updateStmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $updateStmt->bind_param($types, ...$params);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update pickup record: " . $updateStmt->error);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Pickup date updated',
                    'pickup_id' => $pickup_id
                ]);
            } else {
                // Create a new pickup record
                $insertQuery = "INSERT INTO pickups 
                    (order_id, pickup_date, pickup_status, pickup_location";
                
                $params = [$order_id, $pickup_date, 'pending', 'Municipal Agriculture Office'];
                $types = "isss";
                
                // Add payment_id to the insert if provided
                if ($payment_id) {
                    $insertQuery .= ", payment_id";
                    $params[] = $payment_id;
                    $types .= "i";
                }
                
                $insertQuery .= ") VALUES (?, ?, ?, ?";
                
                // Add placeholder for payment_id if provided
                if ($payment_id) {
                    $insertQuery .= ", ?";
                }
                
                $insertQuery .= ")";
                
                $insertStmt = $conn->prepare($insertQuery);
                
                if (!$insertStmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $insertStmt->bind_param($types, ...$params);
                
                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to create pickup record: " . $insertStmt->error);
                }
                
                $pickup_id = $conn->insert_id;
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Pickup scheduled successfully',
                    'pickup_id' => $pickup_id
                ]);
            }
            exit; // Exit here after handling the pickup scheduling
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Validate order data for regular order creation
    if (empty($orderData) || !isset($orderData['items']) || !is_array($orderData['items']) || empty($orderData['items'])) {
        sendErrorResponse("Invalid order data. Items array is required.", 400);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user ID from request data
        // IMPROVED USER ID HANDLING:
        // 1. Check for 'user_id' first (backward compatibility)
        // 2. Look for alternative fields that might contain the user ID
        // 3. Validate the user exists in the database
        $consumer_id = null;
        
        if (isset($orderData['user_id']) && !empty($orderData['user_id'])) {
            $consumer_id = (int) $orderData['user_id'];
            error_log("[DEBUG] Found user_id in request: " . $consumer_id);
        } else if (isset($orderData['consumer_id']) && !empty($orderData['consumer_id'])) {
            $consumer_id = (int) $orderData['consumer_id'];
            error_log("[DEBUG] Found consumer_id in request: " . $consumer_id);
        } else if (isset($orderData['userId']) && !empty($orderData['userId'])) {
            $consumer_id = (int) $orderData['userId'];
            error_log("[DEBUG] Found userId in request: " . $consumer_id);
        }
        
        // Validate the user ID exists
        if (!$consumer_id) {
            error_log("[ERROR] No user ID provided in order request");
            throw new Exception("No user ID provided. Please log in before placing an order.");
        }
        
        // Verify the user exists in the database
        $userCheckQuery = "SELECT user_id, username FROM users WHERE user_id = ?";
        $userCheckStmt = $conn->prepare($userCheckQuery);
        
        if (!$userCheckStmt) {
            error_log("[ERROR] Failed to prepare user check statement: " . $conn->error);
            throw new Exception("Failed to verify user information");
        }
        
        $userCheckStmt->bind_param("i", $consumer_id);
        
        if (!$userCheckStmt->execute()) {
            error_log("[ERROR] Failed to execute user check: " . $userCheckStmt->error);
            throw new Exception("Failed to verify user information");
        }
        
        $userResult = $userCheckStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            error_log("[ERROR] User ID not found: " . $consumer_id);
            throw new Exception("Invalid user ID. Please log in again.");
        }
        
        $userData = $userResult->fetch_assoc();
        error_log("[DEBUG] Verified user for order: " . $userData['username'] . " (ID: " . $consumer_id . ")");
        $userCheckStmt->close();
        
        error_log("[DEBUG] Processing order for user ID: " . $consumer_id);
        
        // Get pickup details if provided, or use default
        $pickup_details = isset($orderData['pickup_details']) ? $orderData['pickup_details'] : 'Municipal Agriculture Office';
        
        // Create a new order
        $orderQuery = "INSERT INTO orders (consumer_id, order_status, pickup_details) VALUES (?, 'pending', ?)";
        $orderStmt = $conn->prepare($orderQuery);
        
        if (!$orderStmt) {
            error_log("[ERROR] Failed to prepare order statement: " . $conn->error);
            throw new Exception("Failed to prepare order statement");
        }
        
        $orderStmt->bind_param("is", $consumer_id, $pickup_details);
        
        if (!$orderStmt->execute()) {
            error_log("[ERROR] Failed to create order: " . $orderStmt->error);
            throw new Exception("Failed to create order");
        }
        
        // Get the newly created order ID
        $order_id = $conn->insert_id;
        $orderStmt->close();
        
        error_log("[DEBUG] Created new order with ID: " . $order_id);
        
        // Create order items
        $itemsQuery = "INSERT INTO orderitems (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $itemsStmt = $conn->prepare($itemsQuery);
        
        if (!$itemsStmt) {
            error_log("[ERROR] Failed to prepare order items statement: " . $conn->error);
            throw new Exception("Failed to prepare order items statement");
        }
        
        // Process each order item
        foreach ($orderData['items'] as $item) {
            // Validate item data
            if (!isset($item['product_id']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
                error_log("[ERROR] Invalid item data: " . json_encode($item));
                throw new Exception("Invalid item data: Product ID and positive quantity required");
            }
            
            // Get product price from database
            $productQuery = "SELECT price FROM products WHERE product_id = ?";
            $productStmt = $conn->prepare($productQuery);
            
            if (!$productStmt) {
                error_log("[ERROR] Failed to prepare product query: " . $conn->error);
                throw new Exception("Failed to retrieve product information");
            }
            
            $productStmt->bind_param("i", $item['product_id']);
            
            if (!$productStmt->execute()) {
                error_log("[ERROR] Failed to execute product query: " . $productStmt->error);
                throw new Exception("Failed to retrieve product information");
            }
            
            $productResult = $productStmt->get_result();
            
            if ($productResult->num_rows === 0) {
                error_log("[ERROR] Product not found: " . $item['product_id']);
                throw new Exception("Product not found: ID " . $item['product_id']);
            }
            
            $productData = $productResult->fetch_assoc();
            $productStmt->close();
            
            // Get the price
            $price = $productData['price'];
            
            // Insert order item
            $itemsStmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $price);
            
            if (!$itemsStmt->execute()) {
                error_log("[ERROR] Failed to create order item: " . $itemsStmt->error);
                throw new Exception("Failed to create order item");
            }
            
            // Update product stock
            $updateStockQuery = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
            $updateStockStmt = $conn->prepare($updateStockQuery);
            
            if (!$updateStockStmt) {
                error_log("[ERROR] Failed to prepare stock update: " . $conn->error);
                throw new Exception("Failed to update product stock");
            }
            
            $updateStockStmt->bind_param("ii", $item['quantity'], $item['product_id']);
            
            if (!$updateStockStmt->execute()) {
                error_log("[ERROR] Failed to update product stock: " . $updateStockStmt->error);
                throw new Exception("Failed to update product stock");
            }
            
            $updateStockStmt->close();
        }
        
        $itemsStmt->close();
        
        // Create pickup record
        $pickupQuery = "INSERT INTO pickups (order_id, pickup_status, pickup_location, pickup_notes, office_location) VALUES (?, 'pending', 'Municipal Agriculture Office', ?, 'Municipal Agriculture Office')";
        $pickupStmt = $conn->prepare($pickupQuery);
        
        if (!$pickupStmt) {
            error_log("[ERROR] Failed to prepare pickup statement: " . $conn->error);
            throw new Exception("Failed to create pickup record");
        }
        
        $pickupStmt->bind_param("is", $order_id, $pickup_details);
        
        if (!$pickupStmt->execute()) {
            error_log("[ERROR] Failed to create pickup record: " . $pickupStmt->error);
            throw new Exception("Failed to create pickup record");
        }
        
        $pickupStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => [
                'order_id' => $order_id,
                'consumer_id' => $consumer_id
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("[ERROR] Order transaction failed: " . $e->getMessage());
        sendErrorResponse('Order creation failed: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("[ERROR] Order process failed: " . $e->getMessage());
    sendErrorResponse('Failed to process order: ' . $e->getMessage());
}
?>