<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set maximum execution time to 60 seconds
set_time_limit(60);

// Track execution time
$start_time = microtime(true);

// Set proper headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Include database connection
require_once 'config/database.php';

// Function to log timing information
function log_timing($label) {
    global $start_time;
    $current_time = microtime(true);
    $elapsed = $current_time - $start_time;
    error_log("[$label] Time: " . number_format($elapsed, 4) . " seconds");
    return $elapsed;
}

// Initialize response
$response = [
    'status' => 'success',
    'message' => 'Payment debug test completed',
    'timing' => [],
    'database' => [],
    'server' => [
        'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'php_version' => phpversion(),
    ]
];

try {
    log_timing('Start');
    
    // Test database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? "Unknown error"));
    }
    
    $response['timing']['database_connect'] = log_timing('Database Connect');
    
    // Test a simple query (should be fast)
    $sql = "SELECT 1 as test";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Simple query failed: " . $conn->error);
    }
    
    $response['timing']['simple_query'] = log_timing('Simple Query');
    
    // Test a complex query that resembles what payment.php does
    $sql = "SELECT o.order_id, SUM(oi.price * oi.quantity) as total_amount 
           FROM orders o
           JOIN orderitems oi ON o.order_id = oi.order_id
           GROUP BY o.order_id
           LIMIT 1";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Complex query failed: " . $conn->error);
    }
    
    $response['timing']['complex_query'] = log_timing('Complex Query');
    
    // Test if payments table exists and check its structure
    $sql = "SHOW TABLES LIKE 'payments'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $response['database']['payments_table_exists'] = true;
        
        // Check payments table structure
        $sql = "SHOW COLUMNS FROM payments";
        $result = $conn->query($sql);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $response['database']['payments_columns'] = $columns;
        } else {
            $response['database']['payments_columns_error'] = $conn->error;
        }
    } else {
        $response['database']['payments_table_exists'] = false;
    }
    
    $response['timing']['table_check'] = log_timing('Table Check');
    
    // Check if there are any existing payments
    $sql = "SELECT COUNT(*) as payment_count FROM payments";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $response['database']['payment_count'] = $row['payment_count'];
    } else {
        $response['database']['payment_count_error'] = $conn->error;
    }
    
    $response['timing']['payment_count'] = log_timing('Payment Count');
    
    // Check if payment_methods table exists and its contents
    $sql = "SHOW TABLES LIKE 'payment_methods'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $response['database']['payment_methods_table_exists'] = true;
        
        // Check payment methods
        $sql = "SELECT * FROM payment_methods";
        $result = $conn->query($sql);
        
        if ($result) {
            $methods = [];
            while ($row = $result->fetch_assoc()) {
                $methods[] = $row;
            }
            $response['database']['payment_methods'] = $methods;
        }
    }
    
    // Check payment_card_details table
    $sql = "SHOW TABLES LIKE 'payment_card_details'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $response['database']['payment_card_details_table_exists'] = true;
        
        // Check structure
        $sql = "SHOW COLUMNS FROM payment_card_details";
        $result = $conn->query($sql);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $response['database']['payment_card_details_columns'] = $columns;
        }
    }
    
    // Check payment_status_history table
    $sql = "SHOW TABLES LIKE 'payment_status_history'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $response['database']['payment_status_history_table_exists'] = true;
        
        // Get the most recent status changes
        $sql = "SELECT * FROM payment_status_history ORDER BY status_date DESC LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result) {
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            $response['database']['recent_status_changes'] = $history;
        }
    }
    
    // All done
    $response['timing']['total'] = log_timing('Complete');
    
    // Return success response
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    $response['timing']['error'] = log_timing('Error');
    
    // Return error response
    http_response_code(500);
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>