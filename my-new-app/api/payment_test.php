<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'config/database.php';

echo "<h2>Payment Insertion Test</h2>";

// Define test payment data
$orderId = 60; // Use an existing order ID from your database
$paymentMethod = 'cash_on_pickup';
$userId = 38; // Use an existing user ID from your database
$amount = 260.00; // Example amount
$transactionReference = 'TEST-' . date('YmdHis');

try {
    // Start transaction
    $conn->begin_transaction();
    
    echo "<p>Starting transaction...</p>";
    
    // 1. Get method_id for the payment method
    $methodQuery = "SELECT method_id FROM payment_methods WHERE method_name = ?";
    $methodStmt = $conn->prepare($methodQuery);
    
    if (!$methodStmt) {
        throw new Exception("Failed to prepare method query: " . $conn->error);
    }
    
    $methodStmt->bind_param("s", $paymentMethod);
    
    if (!$methodStmt->execute()) {
        throw new Exception("Failed to check payment method: " . $methodStmt->error);
    }
    
    $methodResult = $methodStmt->get_result();
    
    if ($methodResult->num_rows > 0) {
        $methodRow = $methodResult->fetch_assoc();
        $methodId = $methodRow['method_id'];
        echo "<p>Found payment method ID: {$methodId}</p>";
    } else {
        // Insert the payment method if it doesn't exist
        $insertMethodQuery = "INSERT INTO payment_methods (method_name, is_active) VALUES (?, 1)";
        $insertMethodStmt = $conn->prepare($insertMethodQuery);
        
        if (!$insertMethodStmt) {
            throw new Exception("Failed to prepare method insert: " . $conn->error);
        }
        
        $insertMethodStmt->bind_param("s", $paymentMethod);
        
        if (!$insertMethodStmt->execute()) {
            throw new Exception("Failed to insert payment method: " . $insertMethodStmt->error);
        }
        
        $methodId = $conn->insert_id;
        echo "<p>Created new payment method with ID: {$methodId}</p>";
        $insertMethodStmt->close();
    }
    
    $methodStmt->close();
    
    // 2. Insert payment record
    $paymentStatus = 'pending';
    
    $paymentQuery = "INSERT INTO payments (
        order_id, 
        payment_method,
        method_id,
        payment_status,
        amount,
        user_id,
        transaction_reference
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $paymentStmt = $conn->prepare($paymentQuery);
    
    if (!$paymentStmt) {
        throw new Exception("Failed to prepare payment statement: " . $conn->error);
    }
    
    $paymentStmt->bind_param(
        "isisids", 
        $orderId, 
        $paymentMethod,
        $methodId,
        $paymentStatus, 
        $amount,
        $userId,
        $transactionReference
    );
    
    if (!$paymentStmt->execute()) {
        throw new Exception("Failed to create payment record: " . $paymentStmt->error);
    }
    
    $paymentId = $conn->insert_id;
    $paymentStmt->close();
    
    echo "<p>Successfully inserted payment record with ID: {$paymentId}</p>";
    
    // 3. Add payment status history entry
    $historyQuery = "INSERT INTO payment_status_history (payment_id, status, notes) VALUES (?, ?, ?)";
    $historyStmt = $conn->prepare($historyQuery);
    
    if ($historyStmt) {
        $notes = "Test payment inserted directly";
        $historyStmt->bind_param("iss", $paymentId, $paymentStatus, $notes);
        $historyStmt->execute();
        $historyStmt->close();
        echo "<p>Added payment history record</p>";
    }
    
    // 4. Update order status if needed
    $orderStatus = 'pending';
    $orderUpdateQuery = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $orderUpdateStmt = $conn->prepare($orderUpdateQuery);
    
    if ($orderUpdateStmt) {
        $orderUpdateStmt->bind_param("si", $orderStatus, $orderId);
        $orderUpdateStmt->execute();
        $orderUpdateStmt->close();
        echo "<p>Updated order status to: {$orderStatus}</p>";
    }
    
    // 5. Log the activity
    $action = "Test payment processed for order #{$orderId} using {$paymentMethod}. Status: {$paymentStatus}";
    $logQuery = "INSERT INTO activitylogs (user_id, action) VALUES (?, ?)";
    $logStmt = $conn->prepare($logQuery);
    
    if ($logStmt) {
        $logStmt->bind_param("is", $userId, $action);
        $logStmt->execute();
        $logStmt->close();
        echo "<p>Added activity log entry</p>";
    }
    
    // Commit transaction
    $conn->commit();
    echo "<p style='color:green; font-weight:bold;'>Transaction committed successfully!</p>";
    
    // Show payment details 
    echo "<h3>Payment Details:</h3>";
    echo "<ul>";
    echo "<li>Payment ID: {$paymentId}</li>";
    echo "<li>Order ID: {$orderId}</li>";
    echo "<li>Payment Method: {$paymentMethod}</li>";
    echo "<li>Method ID: {$methodId}</li>";
    echo "<li>Amount: ₱{$amount}</li>";
    echo "<li>Status: {$paymentStatus}</li>";
    echo "<li>Transaction Reference: {$transactionReference}</li>";
    echo "<li>User ID: {$userId}</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "<p style='color:red; font-weight:bold;'>Error: " . $e->getMessage() . "</p>";
}

// Show all payments in the database
echo "<h3>All Payments in Database:</h3>";
try {
    $result = $conn->query("SELECT p.payment_id, p.order_id, p.payment_method, p.method_id, p.payment_status, p.amount, 
                           p.user_id, p.transaction_reference, p.payment_date 
                           FROM payments p ORDER BY p.payment_id DESC LIMIT 10");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Order</th><th>Method</th><th>Method ID</th><th>Status</th><th>Amount</th><th>User</th><th>Reference</th><th>Date</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['payment_id'] . "</td>";
            echo "<td>" . $row['order_id'] . "</td>";
            echo "<td>" . $row['payment_method'] . "</td>";
            echo "<td>" . $row['method_id'] . "</td>";
            echo "<td>" . $row['payment_status'] . "</td>";
            echo "<td>₱" . $row['amount'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['transaction_reference'] . "</td>";
            echo "<td>" . $row['payment_date'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No payments found in the database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error fetching payments: " . $e->getMessage() . "</p>";
}
?>