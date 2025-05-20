<?php
// Enable error reporting for debugging but log to file instead of output
ini_set('display_errors', 0); // Turn off error display
ini_set('log_errors', 1); // Turn on error logging
ini_set('error_log', 'pickup_errors.log'); // Log to file
error_reporting(E_ALL);

// Ensure no output before JSON response
ob_start();

// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin, Authorization');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean(); // Clean buffer before sending response
    exit();
}

// Function to send proper error response
function sendErrorResponse($message, $statusCode = 500) {
    // Clean any previous output that might have been generated
    if (ob_get_length()) ob_clean();
    
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

// Create a debug log function
function debugLog($message) {
    error_log("[PICKUP_DEBUG] " . $message);
}

debugLog("Processing pickup request: " . $_SERVER['REQUEST_METHOD']);

// Include database connection file
try {
    require_once 'config/database.php';
    debugLog("Database connection successful");
} catch (Exception $e) {
    debugLog("Database connection failed: " . $e->getMessage());
    sendErrorResponse("Database connection error. Please check server logs.");
}

// If accessed directly without parameters, provide API usage information
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters',
        'usage' => [
            'GET' => [
                'description' => 'Retrieve pickup information',
                'required_parameters' => 'Either user_id or order_id is required',
                'example' => '/pickup.php?user_id=123 or /pickup.php?order_id=456'
            ],
            'POST' => [
                'description' => 'Schedule or update a pickup',
                'required_parameters' => 'JSON body with order_id and pickup_date',
                'example' => '{"order_id": 123, "pickup_date": "2025-04-30 14:00:00"}'
            ],
            'PUT' => [
                'description' => 'Update pickup status',
                'required_parameters' => 'JSON body with pickup_id and pickup_status',
                'example' => '{"pickup_id": 123, "pickup_status": "completed"}'
            ]
        ]
    ]);
    exit();
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get pickups for a user
            debugLog("Handling GET request");
            handleGetPickups();
            break;

        case 'POST':
            // Schedule or update a pickup
            debugLog("Handling POST request");
            handleCreateOrUpdatePickup();
            break;
            
        case 'PUT':
            // Update pickup status or details
            debugLog("Handling PUT request");
            handleUpdatePickupStatus();
            break;

        default:
            sendErrorResponse("Method not allowed", 405);
    }
} catch (Exception $e) {
    debugLog("Uncaught exception: " . $e->getMessage());
    sendErrorResponse("Server error: " . $e->getMessage());
}

// Function to handle GET requests - retrieve pickups
function handleGetPickups() {
    global $conn;
    
    // Check if we have a user ID parameter
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
    
    debugLog("GET params - user_id: " . ($userId ? $userId : "none") . ", order_id: " . ($orderId ? $orderId : "none"));
    
    // If neither parameter is provided, return error with usage instructions
    if (!$userId && !$orderId) {
        sendErrorResponse("Missing required parameters. Please provide either user_id or order_id", 400);
    }
    
    try {
        // Build the query based on provided parameters
        if ($userId) {
            // CRITICAL CHECK: Verify the user ID is actually being used
            debugLog("FILTERING PICKUPS FOR USER ID: " . $userId);
            
            // Get orders directly for this user first to confirm orders data
            $orderCheck = "SELECT order_id, consumer_id FROM orders WHERE consumer_id = ?";
            $orderStmt = $conn->prepare($orderCheck);
            $orderStmt->bind_param("i", $userId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            
            $orderIdsFound = [];
            while ($orderRow = $orderResult->fetch_assoc()) {
                $orderIdsFound[] = $orderRow['order_id'];
            }
            
            debugLog("Found " . count($orderIdsFound) . " orders for user_id: " . $userId . ". Order IDs: " . implode(",", $orderIdsFound));
            
            // If no orders found for this user, return empty array early
            if (empty($orderIdsFound)) {
                debugLog("No orders found for user_id: " . $userId . ", returning empty pickup list");
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'No pickups found because user has no orders',
                    'data' => []
                ]);
                exit();
            }
            
            // Only select pickups that match these orders
            if (!empty($orderIdsFound)) {
                $placeholders = str_repeat("?,", count($orderIdsFound) - 1) . "?";
                $sql = "SELECT p.*, o.order_status, o.order_date, o.consumer_id, py.payment_method
                        FROM pickups p
                        JOIN orders o ON p.order_id = o.order_id
                        LEFT JOIN payments py ON p.payment_id = py.payment_id
                        WHERE p.order_id IN (" . $placeholders . ")
                        AND o.consumer_id = ?
                        ORDER BY p.pickup_date DESC";
                        
                $stmt = $conn->prepare($sql);
                
                // Create array of parameters for bind_param
                $paramTypes = str_repeat("i", count($orderIdsFound)) . "i";
                $bindParams = array_merge($orderIdsFound, [$userId]);
                
                // Use reflection to bind parameters dynamically
                $bindParamsRef = [];
                $bindParamsRef[] = &$paramTypes;
                foreach ($bindParams as &$param) {
                    $bindParamsRef[] = &$param;
                }
                
                call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
                
                debugLog("Executing pickup query with " . count($orderIdsFound) . " order IDs for user_id: " . $userId);
            }
            
            if (!$stmt->execute()) {
                debugLog("Query execution failed: " . $stmt->error);
                throw new Exception("Database error, please try again");
            }
            
            $result = $stmt->get_result();
            $pickups = [];
            
            // Format pickup data
            while ($row = $result->fetch_assoc()) {
                // Debug output to check what's being fetched - VERIFY CONSUMER_ID MATCHES USER_ID
                debugLog("Found pickup - ID: " . $row['pickup_id'] . ", Order ID: " . $row['order_id'] . ", Consumer ID: " . $row['consumer_id']);
                
                // Convert pickup date to more usable format
                $row['pickup_date_formatted'] = $row['pickup_date'] ? date('M d, Y h:i A', strtotime($row['pickup_date'])) : 'Not scheduled';
                
                // Add pickup window (30 minutes by default)
                if ($row['pickup_date']) {
                    $pickupTime = new DateTime($row['pickup_date']);
                    $endTime = clone $pickupTime;
                    $endTime->modify('+30 minutes');
                    $row['pickup_window'] = $pickupTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                } else {
                    $row['pickup_window'] = 'Not scheduled';
                }
                
                // Get order items for this order
                $orderItemsSql = "SELECT oi.*, p.name AS product_name, p.unit_type 
                                 FROM orderitems oi 
                                 LEFT JOIN products p ON oi.product_id = p.product_id
                                 WHERE oi.order_id = ?";
                $orderItemsStmt = $conn->prepare($orderItemsSql);
                $orderItemsStmt->bind_param("i", $row['order_id']);
                $orderItemsStmt->execute();
                $orderItemsResult = $orderItemsStmt->get_result();
                
                $orderItems = [];
                $totalAmount = 0;
                
                while ($itemRow = $orderItemsResult->fetch_assoc()) {
                    $itemTotal = $itemRow['quantity'] * $itemRow['price'];
                    $totalAmount += $itemTotal;
                    
                    $orderItems[] = [
                        'order_item_id' => $itemRow['order_item_id'],
                        'product_id' => $itemRow['product_id'],
                        'product_name' => $itemRow['product_name'] ?? 'Unknown Product',
                        'quantity' => $itemRow['quantity'],
                        'price' => $itemRow['price'],
                        'unit_type' => $itemRow['unit_type'] ?? 'piece',
                        'subtotal' => $itemTotal
                    ];
                }
                
                // Add order items to pickup data
                $row['order_items'] = $orderItems;
                $row['order_total'] = $totalAmount;
                $row['item_count'] = count($orderItems);
                
                $pickups[] = $row;
            }
            
            debugLog("Found " . count($pickups) . " pickups for user_id: " . $userId);
            
            // If no pickups found, provide a clear message but with empty data array
            if (empty($pickups)) {
                debugLog("No pickups found for user_id: " . $userId . ", returning empty array");
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'No pickups found',
                    'data' => []
                ]);
                exit();
            }
            
            // Return response with pickups
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $pickups
            ]);
            exit(); // Ensure we exit after sending response
            
        } elseif ($orderId) {
            // Check if pickups table exists
            $tablesQuery = "SHOW TABLES LIKE 'pickups'";
            $tablesResult = $conn->query($tablesQuery);
            
            if ($tablesResult->num_rows == 0) {
                debugLog("Pickups table doesn't exist - providing mock data for order_id: " . $orderId);
                // Table doesn't exist - provide mock data for this specific order
                http_response_code(200);
                
                $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day'));
                
                $mockData = [
                    [
                        'pickup_id' => 2000 + $orderId,
                        'order_id' => $orderId,
                        'pickup_status' => 'pending',
                        'pickup_date' => $tomorrow,
                        'pickup_notes' => "Order #$orderId scheduled for pickup",
                        'pickup_location' => 'Municipal Agriculture Office',
                        'pickup_date_formatted' => date('M d, Y h:i A', strtotime($tomorrow)),
                        'pickup_window' => date('h:i A', strtotime($tomorrow)) . ' - ' . date('h:i A', strtotime($tomorrow . ' +30 minutes')),
                        'order_items' => [
                            [
                                'product_name' => 'Sample Product',
                                'quantity' => 2,
                                'price' => 50.00,
                                'subtotal' => 100.00
                            ]
                        ],
                        'order_total' => 100.00,
                        'item_count' => 1
                    ]
                ];
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Using demo data for order',
                    'data' => $mockData
                ]);
                exit();
            }
            
            // Use JOIN to verify the user has access to this order
            if (isset($_GET['user_id'])) {
                $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
                $sql = "SELECT p.*, o.order_status, o.order_date, o.consumer_id, py.payment_method
                        FROM pickups p
                        JOIN orders o ON p.order_id = o.order_id
                        LEFT JOIN payments py ON p.payment_id = py.payment_id
                        WHERE p.order_id = ? AND o.consumer_id = ?";
                        
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $orderId, $userId);
                debugLog("SQL query prepared for order_id: " . $orderId . " and user_id: " . $userId);
            } else {
                $sql = "SELECT p.*, o.order_status, o.order_date, o.consumer_id, py.payment_method
                        FROM pickups p
                        JOIN orders o ON p.order_id = o.order_id
                        LEFT JOIN payments py ON p.payment_id = py.payment_id
                        WHERE p.order_id = ?";
                        
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $orderId);
                debugLog("SQL query prepared for order_id: " . $orderId);
            }
            
            // Execute query
            if (!$stmt->execute()) {
                debugLog("Query execution failed: " . $stmt->error);
                throw new Exception("Database error, please try again");
            }
            
            $result = $stmt->get_result();
            $pickups = [];
            
            // Format pickup data
            while ($row = $result->fetch_assoc()) {
                // Convert pickup date to more usable format
                $row['pickup_date_formatted'] = $row['pickup_date'] ? date('M d, Y h:i A', strtotime($row['pickup_date'])) : 'Not scheduled';
                
                // Add pickup window (30 minutes by default)
                if ($row['pickup_date']) {
                    $pickupTime = new DateTime($row['pickup_date']);
                    $endTime = clone $pickupTime;
                    $endTime->modify('+30 minutes');
                    $row['pickup_window'] = $pickupTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                } else {
                    $row['pickup_window'] = 'Not scheduled';
                }
                
                // Get order items for this order
                $orderItemsSql = "SELECT oi.*, p.name AS product_name, p.unit_type 
                                 FROM orderitems oi 
                                 LEFT JOIN products p ON oi.product_id = p.product_id
                                 WHERE oi.order_id = ?";
                $orderItemsStmt = $conn->prepare($orderItemsSql);
                $orderItemsStmt->bind_param("i", $row['order_id']);
                $orderItemsStmt->execute();
                $orderItemsResult = $orderItemsStmt->get_result();
                
                $orderItems = [];
                $totalAmount = 0;
                
                while ($itemRow = $orderItemsResult->fetch_assoc()) {
                    $itemTotal = $itemRow['quantity'] * $itemRow['price'];
                    $totalAmount += $itemTotal;
                    
                    $orderItems[] = [
                        'order_item_id' => $itemRow['order_item_id'],
                        'product_id' => $itemRow['product_id'],
                        'product_name' => $itemRow['product_name'] ?? 'Unknown Product',
                        'quantity' => $itemRow['quantity'],
                        'price' => $itemRow['price'],
                        'unit_type' => $itemRow['unit_type'] ?? 'piece',
                        'subtotal' => $itemTotal
                    ];
                }
                
                // Add order items to pickup data
                $row['order_items'] = $orderItems;
                $row['order_total'] = $totalAmount;
                $row['item_count'] = count($orderItems);
                
                $pickups[] = $row;
            }
            
            debugLog("Found " . count($pickups) . " pickups");
            
            // If no pickups found, provide a clear message but with empty data array
            if (empty($pickups)) {
                debugLog("No pickups found, returning empty array");
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'No pickups found',
                    'data' => []
                ]);
                exit();
            }
            
            // Return response with pickups
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $pickups
            ]);
            exit();
        }
        
    } catch (Exception $e) {
        debugLog("Error in handleGetPickups: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        sendErrorResponse("Failed to retrieve pickup information: " . $e->getMessage());
    }
}

// Function to handle POST requests - create or update a pickup
function handleCreateOrUpdatePickup() {
    global $conn;
    
    // Get request body
    $rawInput = file_get_contents('php://input');
    debugLog("Received POST data: " . $rawInput);
    
    if (empty($rawInput)) {
        sendErrorResponse("No data provided. Please send pickup details in JSON format", 400);
    }
    
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse("Invalid JSON data format", 400);
    }
    
    // Validate required fields
    if (empty($data) || !isset($data['order_id']) || !isset($data['pickup_date'])) {
        debugLog("Missing required fields in POST data");
        sendErrorResponse("Missing required fields. Please provide order_id and pickup_date", 400);
    }
    
    try {
        // Check if pickups table exists
        $tablesQuery = "SHOW TABLES LIKE 'pickups'";
        $tablesResult = $conn->query($tablesQuery);
        
        if ($tablesResult->num_rows == 0) {
            debugLog("Pickups table doesn't exist - returning mock success");
            // Table doesn't exist - return mock success
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Pickup scheduled successfully (demo)',
                'pickup_id' => rand(1000, 9999)
            ]);
            exit();
        }
    
        // Check if pickup entry already exists
        $checkSql = "SELECT pickup_id FROM pickups WHERE order_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $data['order_id']);
        $checkStmt->execute();
        $existingResult = $checkStmt->get_result();
        
        if ($existingResult->num_rows > 0) {
            // Update existing pickup
            $row = $existingResult->fetch_assoc();
            $pickupId = $row['pickup_id'];
            
            $updateSql = "UPDATE pickups SET 
                         pickup_date = ?, 
                         pickup_notes = ?, 
                         contact_person = ?";
            
            // Add payment_id to the update if provided
            $params = [$data['pickup_date']];
            $types = "s";
            
            if (isset($data['pickup_notes'])) {
                $notes = $data['pickup_notes'];
            } else {
                $notes = null;
            }
            $params[] = $notes;
            $types .= "s";
            
            if (isset($data['contact_person'])) {
                $contact = $data['contact_person'];
            } else {
                $contact = null;
            }
            $params[] = $contact;
            $types .= "s";
            
            // Add payment_id to the update if provided
            if (isset($data['payment_id'])) {
                $updateSql .= ", payment_id = ?";
                $params[] = $data['payment_id'];
                $types .= "i";
            }
            
            $updateSql .= " WHERE pickup_id = ?";
            $params[] = $pickupId;
            $types .= "i";
            
            $updateStmt = $conn->prepare($updateSql);
            
            if (!$updateStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $updateStmt->bind_param($types, ...$params);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update pickup record: " . $updateStmt->error);
            }
            
            $response = [
                'status' => 'success',
                'message' => 'Pickup updated successfully',
                'pickup_id' => $pickupId
            ];
            
            debugLog("Pickup updated successfully: " . $pickupId);
        } else {
            // Insert new pickup
            $insertSql = "INSERT INTO pickups (
                          order_id, 
                          pickup_status,
                          pickup_date, 
                          pickup_notes,
                          contact_person";
                          
            // Add payment_id field if provided
            if (isset($data['payment_id'])) {
                $insertSql .= ", payment_id";
            }
                          
            $insertSql .= ") VALUES (?, ?, ?, ?, ?";
            
            // Add value placeholder for payment_id if provided
            if (isset($data['payment_id'])) {
                $insertSql .= ", ?";
            }
            
            $insertSql .= ")";
                          
            $status = 'pending';
            $notes = isset($data['pickup_notes']) ? $data['pickup_notes'] : null;
            $contact = isset($data['contact_person']) ? $data['contact_person'] : null;
            
            $params = [
                $data['order_id'],
                $status,
                $data['pickup_date'],
                $notes,
                $contact
            ];
            
            $types = "issss";
            
            // Add payment_id to parameters if provided
            if (isset($data['payment_id'])) {
                $params[] = $data['payment_id'];
                $types .= "i";
            }
            
            $insertStmt = $conn->prepare($insertSql);
            
            if (!$insertStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $insertStmt->bind_param($types, ...$params);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Failed to create pickup record: " . $insertStmt->error);
            }
            
            $pickupId = $conn->insert_id;
            $response = [
                'status' => 'success',
                'message' => 'Pickup scheduled successfully',
                'pickup_id' => $pickupId
            ];
            
            debugLog("New pickup created: " . $pickupId);
        }
        
        // Return success response
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        debugLog("Error in handleCreateOrUpdatePickup: " . $e->getMessage());
        sendErrorResponse("Failed to schedule pickup: " . $e->getMessage());
    }
}

// Function to handle PUT requests - update pickup status
function handleUpdatePickupStatus() {
    global $conn;
    
    // Get request body
    $rawInput = file_get_contents('php://input');
    debugLog("Received PUT data: " . $rawInput);
    
    if (empty($rawInput)) {
        sendErrorResponse("No data provided. Please send pickup status details in JSON format", 400);
    }
    
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse("Invalid JSON data format", 400);
    }
    
    // Validate required fields
    if (empty($data) || !isset($data['pickup_id']) || !isset($data['pickup_status'])) {
        debugLog("Missing required fields in PUT data");
        sendErrorResponse("Missing required fields. Please provide pickup_id and pickup_status", 400);
    }
    
    try {
        // Check if pickups table exists
        $tablesQuery = "SHOW TABLES LIKE 'pickups'";
        $tablesResult = $conn->query($tablesQuery);
        
        if ($tablesResult->num_rows == 0) {
            debugLog("Pickups table doesn't exist - returning mock success");
            // Table doesn't exist - return mock success
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Pickup status updated successfully (demo)'
            ]);
            exit();
        }
    
        // Validate pickup status
        $validStatuses = ['pending', 'assigned', 'ready', 'completed', 'canceled'];
        if (!in_array($data['pickup_status'], $validStatuses)) {
            debugLog("Invalid pickup status: " . $data['pickup_status']);
            sendErrorResponse("Invalid pickup status", 400);
        }
        
        $updateSql = "UPDATE pickups SET pickup_status = ? WHERE pickup_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $data['pickup_status'], $data['pickup_id']);
        
        if (!$stmt->execute()) {
            debugLog("Failed to update pickup status: " . $stmt->error);
            throw new Exception("Failed to update pickup status: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            debugLog("Pickup not found: " . $data['pickup_id']);
            sendErrorResponse("Pickup not found", 404);
        }
        
        debugLog("Pickup status updated: " . $data['pickup_id'] . " to " . $data['pickup_status']);
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Pickup status updated successfully'
        ]);
        
    } catch (Exception $e) {
        debugLog("Error in handleUpdatePickupStatus: " . $e->getMessage());
        sendErrorResponse("Failed to update pickup status: " . $e->getMessage());
    }
}

// End the script with a clean exit to avoid any trailing whitespace or errors
exit();
?>