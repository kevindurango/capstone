<?php
// Error handling - prevent PHP notices from breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include database connection
require_once('config/database.php');

// Simple response
$response = [
    'success' => true,
    'message' => 'Test successful',
    'test_time' => date('Y-m-d H:i:s'),
    'connection_status' => $conn ? 'Connected' : 'Failed'
];

// Try a simple barangay query
if ($conn) {
    try {
        $query = "SELECT COUNT(*) as count FROM barangays";
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $response['barangay_count'] = (int)$row['count'];
        } else {
            $response['query_error'] = $conn->error;
        }
    } catch (Exception $e) {
        $response['exception'] = $e->getMessage();
    }
}

// Return clean JSON response
echo json_encode($response);
?>