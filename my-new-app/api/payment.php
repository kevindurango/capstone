<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Increase execution time limit to prevent timeouts
set_time_limit(60);

// Ensure no output before JSON response
ob_start();

// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin, Authorization');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// For ping/connectivity testing
if (isset($_GET['action']) && $_GET['action'] === 'ping') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => 'Payment API is reachable',
        'timestamp' => time()
    ]);
    exit();
}

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

// Start tracking execution time
$start_time = microtime(true);
function log_timing($label) {
    global $start_time;
    $current_time = microtime(true);
    $elapsed = $current_time - $start_time;
    error_log("[TIMING][$label] " . number_format($elapsed, 4) . " seconds");
}

// Debug log function for payment processing
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/payment_debug.php';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if ($data !== null) {
        // Safely encode any data we want to log
        if (is_array($data) || is_object($data)) {
            $log_message .= " - " . json_encode($data, JSON_UNESCAPED_SLASHES);
        } else {
            $log_message .= " - " . $data;
        }
    }
    
    error_log($log_message . PHP_EOL, 3, $log_file);
}

// Include database connection file
try {
    require_once 'config/database.php';
    log_timing("Database include");
} catch (Exception $e) {
    error_log("[ERROR] Database connection failed: " . $e->getMessage());
    sendErrorResponse("Database connection error. Please check server logs.");
}

error_log("[DEBUG] Starting payment process");

try {
    // Check if database connection is established properly
    if (!isset($conn) || $conn->connect_error) {
        error_log("[ERROR] Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
        sendErrorResponse("Database connection error. Please check server logs.");
    }
    log_timing("Connection check");

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse("Only POST requests are allowed", 405);
    }

    // Get request body
    $requestBody = file_get_contents('php://input');
    
    // Parse JSON data
    $paymentData = json_decode($requestBody, true);
    
    error_log("[DEBUG] Received payment data: " . json_encode($paymentData));
    log_timing("Request parsing");
    
    // Validate payment data
    if (empty($paymentData) || !isset($paymentData['order_id']) || !isset($paymentData['payment_method'])) {
        sendErrorResponse("Invalid payment data. Order ID and payment method are required.", 400);
    }
    
    $orderId = (int) $paymentData['order_id'];
    $paymentMethod = $paymentData['payment_method'];
    $userId = isset($paymentData['user_id']) ? (int) $paymentData['user_id'] : null;
    
    // Extract additional payment details
    $amount = isset($paymentData['amount']) ? (float) $paymentData['amount'] : 0;
    $cardLastFour = null;
    $cardBrand = null;
    $cardExpiryMonth = null;
    $cardExpiryYear = null;
    $transactionReference = isset($paymentData['transaction_reference']) ? $paymentData['transaction_reference'] : null;
    $paymentNotes = isset($paymentData['payment_notes']) ? $paymentData['payment_notes'] : null;
    
    // Validate payment method
    $allowedMethods = ['credit_card', 'paypal', 'bank_transfer', 'cash_on_pickup'];
    if (!in_array($paymentMethod, $allowedMethods)) {
        sendErrorResponse("Invalid payment method. Allowed methods: credit_card, paypal, bank_transfer, cash_on_pickup", 400);
    }
    log_timing("Data validation");
    
    // For credit card payments, extract card details securely
    if ($paymentMethod === 'credit_card' && isset($paymentData['card_details'])) {
        $cardDetails = $paymentData['card_details'];
        
        // Only store last four digits of card number
        if (isset($cardDetails['card_number']) && strlen($cardDetails['card_number']) >= 4) {
            $cardLastFour = substr($cardDetails['card_number'], -4);
        }
        
        // Determine card brand from card number prefix
        if (isset($cardDetails['card_number'])) {
            $cardFirstDigit = substr($cardDetails['card_number'], 0, 1);
            $cardFirstTwoDigits = substr($cardDetails['card_number'], 0, 2);
            
            if ($cardFirstDigit === '4') {
                $cardBrand = 'Visa';
            } elseif ($cardFirstTwoDigits >= '51' && $cardFirstTwoDigits <= '55') {
                $cardBrand = 'Mastercard';
            } elseif ($cardFirstDigit === '3' && (substr($cardDetails['card_number'], 1, 1) === '4' || substr($cardDetails['card_number'], 1, 1) === '7')) {
                $cardBrand = 'American Express';
            } elseif ($cardFirstTwoDigits === '35') {
                $cardBrand = 'JCB';
            } else {
                $cardBrand = 'Other';
            }
        }
        
        // Extract expiry month/year if provided
        if (isset($cardDetails['expiry_date']) && preg_match('/^(\d{1,2})\/(\d{2})$/', $cardDetails['expiry_date'], $matches)) {
            $cardExpiryMonth = (int)$matches[1];
            $cardExpiryYear = (int)$matches[2];
            // Convert 2-digit year to 4-digit year
            $cardExpiryYear = $cardExpiryYear + 2000;
        }
    }
    log_timing("Card processing");
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if order exists
        $orderCheckQuery = "SELECT o.order_id FROM orders o WHERE o.order_id = ?";
        $orderCheckStmt = $conn->prepare($orderCheckQuery);
        
        if (!$orderCheckStmt) {
            error_log("[ERROR] Failed to prepare order check statement: " . $conn->error);
            throw new Exception("Failed to verify order");
        }
        
        $orderCheckStmt->bind_param("i", $orderId);
        
        if (!$orderCheckStmt->execute()) {
            error_log("[ERROR] Failed to check order: " . $orderCheckStmt->error);
            throw new Exception("Failed to verify order");
        }
        
        $orderResult = $orderCheckStmt->get_result();
        
        if ($orderResult->num_rows === 0) {
            error_log("[ERROR] Order not found: " . $orderId);
            throw new Exception("Order not found");
        }
        
        $orderCheckStmt->close();
        log_timing("Order verification");

        // Calculate order total if not provided
        if ($amount <= 0) {
            $amountQuery = "SELECT SUM(price * quantity) as total FROM orderitems WHERE order_id = ?";
            $amountStmt = $conn->prepare($amountQuery);
            
            if ($amountStmt) {
                $amountStmt->bind_param("i", $orderId);
                if ($amountStmt->execute()) {
                    $amountResult = $amountStmt->get_result();
                    if ($amountResult->num_rows > 0) {
                        $amountRow = $amountResult->fetch_assoc();
                        $amount = $amountRow['total'];
                    }
                    $amountStmt->close();
                }
            }
        }
        log_timing("Amount calculation");
        
        // Handle different payment methods
        $paymentStatus = 'pending'; // Default status
        
        // Generate a transaction reference if not provided
        if (!$transactionReference) {
            // Create a unique transaction reference with prefix based on payment method
            $prefix = '';
            switch ($paymentMethod) {
                case 'credit_card':
                    $prefix = 'CC';
                    break;
                case 'paypal':
                    $prefix = 'PP';
                    break;
                case 'bank_transfer':
                    $prefix = 'BT';
                    break;
                case 'cash_on_pickup':
                    $prefix = 'CP';
                    break;
                default:
                    $prefix = 'TX';
            }
            
            // Format: PREFIX-ORDERID-TIMESTAMP-RANDOM (e.g., CC-123-20250426-AB12)
            $timestamp = date('Ymd');
            $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
            $transactionReference = "$prefix-$orderId-$timestamp-$random";
            error_log("[DEBUG] Generated transaction reference: $transactionReference");
        }
        
        if ($paymentMethod === 'cash_on_pickup') {
            // For cash on pickup, we just record the payment intent
            $paymentStatus = 'pending';
        } else if ($paymentMethod === 'credit_card') {
            // In a real app, you would integrate with a payment gateway here
            // For now, we'll simulate a successful payment
            $paymentStatus = 'completed';
        } else {
            // For bank_transfer or paypal, payment needs verification
            $paymentStatus = 'pending';
        }
        
        // Get payment method ID
        $methodId = null;
        
        // Check if payment method exists in the database
        $methodQuery = "SELECT method_id FROM payment_methods WHERE method_name = ?";
        $methodStmt = $conn->prepare($methodQuery);
        
        if (!$methodStmt) {
            error_log("[ERROR] Failed to prepare method query: " . $conn->error);
            throw new Exception("Failed to verify payment method");
        }
        
        $methodStmt->bind_param("s", $paymentMethod);
        
        if (!$methodStmt->execute()) {
            error_log("[ERROR] Failed to check payment method: " . $methodStmt->error);
            throw new Exception("Failed to verify payment method");
        }
        
        $methodResult = $methodStmt->get_result();
        
        if ($methodResult->num_rows > 0) {
            $methodRow = $methodResult->fetch_assoc();
            $methodId = $methodRow['method_id'];
        } else {
            // Insert the payment method if it doesn't exist
            $insertMethodQuery = "INSERT INTO payment_methods (method_name, is_active) VALUES (?, 1)";
            $insertMethodStmt = $conn->prepare($insertMethodQuery);
            
            if (!$insertMethodStmt) {
                error_log("[ERROR] Failed to prepare method insert: " . $conn->error);
                throw new Exception("Failed to create payment method");
            }
            
            $insertMethodStmt->bind_param("s", $paymentMethod);
            
            if (!$insertMethodStmt->execute()) {
                error_log("[ERROR] Failed to insert payment method: " . $insertMethodStmt->error);
                throw new Exception("Failed to create payment method");
            }
            
            $methodId = $conn->insert_id;
            $insertMethodStmt->close();
        }
        
        $methodStmt->close();
        log_timing("Payment method verification");

        // Insert payment record
        // Fix the query to match the table structure and include all required fields
        $paymentQuery = "INSERT INTO payments (
            order_id, 
            payment_method,
            method_id,
            payment_status,
            amount,
            user_id,
            transaction_reference,
            payment_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $paymentStmt = $conn->prepare($paymentQuery);
        
        if (!$paymentStmt) {
            error_log("[ERROR] Failed to prepare payment statement: " . $conn->error);
            throw new Exception("Failed to process payment");
        }
        
        $paymentStmt->bind_param(
            "isisidss", 
            $orderId, 
            $paymentMethod,
            $methodId,
            $paymentStatus, 
            $amount,
            $userId,
            $transactionReference,
            $paymentNotes
        );
        
        if (!$paymentStmt->execute()) {
            error_log("[ERROR] Failed to create payment record: " . $paymentStmt->error . " - SQL: " . $paymentQuery);
            throw new Exception("Failed to process payment: Database error - " . $paymentStmt->error);
        }
        
        $paymentId = $conn->insert_id;
        $paymentStmt->close();
        log_timing("Payment record created");
        
        // If it's a credit card payment, store card details
        if ($paymentMethod === 'credit_card' && $cardLastFour) {
            try {
                // Try simplified version first that's guaranteed to work
                $cardQuery = "INSERT INTO payment_credit_cards (payment_id) VALUES (?)";
                $cardStmt = $conn->prepare($cardQuery);
                
                if ($cardStmt) {
                    $cardStmt->bind_param("i", $paymentId);
                    $cardStmt->execute();
                    $cardStmt->close();
                    
                    // Now try to update with additional details if possible
                    $updateQuery = "UPDATE payment_credit_cards SET 
                        card_last_four = ?, 
                        card_brand = ? 
                        WHERE payment_id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    
                    if ($updateStmt) {
                        $updateStmt->bind_param("ssi", $cardLastFour, $cardBrand, $paymentId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                } else {
                    debug_log("Could not insert into payment_credit_cards: " . $conn->error);
                }
            } catch (Exception $e) {
                // Log but continue - card details are not critical
                debug_log("Credit card details storage failed, continuing payment process", $e->getMessage());
            }
        }
        
        // Add payment status history entry - simplified approach
        try {
            $historyQuery = "INSERT INTO payment_status_history (payment_id, status) VALUES (?, ?)";
            $historyStmt = $conn->prepare($historyQuery);
            
            if ($historyStmt) {
                $historyStmt->bind_param("is", $paymentId, $paymentStatus);
                $historyStmt->execute();
                $historyStmt->close();
            }
        } catch (Exception $e) {
            // Log but continue - history is helpful but not critical
            debug_log("Payment history creation failed, continuing payment process", $e->getMessage());
        }
        
        log_timing("Payment history created");
        
        // Update order status based on payment status
        $orderStatus = ($paymentStatus === 'completed') ? 'completed' : 'pending';
        
        $orderUpdateQuery = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $orderUpdateStmt = $conn->prepare($orderUpdateQuery);
        
        if (!$orderUpdateStmt) {
            error_log("[ERROR] Failed to prepare order update statement: " . $conn->error);
            throw new Exception("Failed to update order status");
        }
        
        $orderUpdateStmt->bind_param("si", $orderStatus, $orderId);
        
        if (!$orderUpdateStmt->execute()) {
            error_log("[ERROR] Failed to update order status: " . $orderUpdateStmt->error);
            throw new Exception("Failed to update order status");
        }
        
        $orderUpdateStmt->close();
        log_timing("Order status updated");
        
        // Log the activity
        if ($userId) {
            $action = "Payment processed for order #$orderId using $paymentMethod. Status: $paymentStatus";
            
            $logQuery = "INSERT INTO activitylogs (user_id, action) VALUES (?, ?)";
            $logStmt = $conn->prepare($logQuery);
            
            if ($logStmt) {
                $logStmt->bind_param("is", $userId, $action);
                $logStmt->execute();
                $logStmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        log_timing("Transaction committed");
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment processed successfully',
            'data' => [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'amount' => $amount,
                'transaction_reference' => $transactionReference
            ]
        ]);
        log_timing("Response sent");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("[ERROR] Payment transaction failed: " . $e->getMessage());
        sendErrorResponse('Payment processing failed: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("[ERROR] Payment process failed: " . $e->getMessage());
    sendErrorResponse('Failed to process payment: ' . $e->getMessage());
}
?>