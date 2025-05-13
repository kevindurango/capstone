<?php
/**
 * API Connectivity Test Endpoint
 * 
 * This file provides a comprehensive endpoint to verify API connectivity
 * Used for testing network connections from the mobile app
 */

// Allow CORS for development testing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Platform');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Test database connection
function testDatabaseConnection() {
    try {
        // Include database configuration
        require_once __DIR__ . '/config/database.php';
        
        // Create connection using the function from database.php
        $conn = getConnection();
        
        // Check connection
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $conn->connect_error,
            ];
        }
        
        // Simple query to test connection
        $result = $conn->query("SELECT 1 as test");
        $data = $result->fetch_assoc();
        
        $conn->close();
        
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'data' => $data,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database connection error: ' . $e->getMessage(),
        ];
    }
}

// Get diagnostics of common directories
function getDirectoryPermissions() {
    $directories = [
        'api' => __DIR__,
        'config' => __DIR__ . '/config',
        'farmer' => __DIR__ . '/farmer',
    ];
    
    $results = [];
    foreach ($directories as $name => $path) {
        $results[$name] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path),
            'writable' => is_writable($path),
        ];
    }
    
    return $results;
}

// Get server time for timestamp
$timestamp = date('Y-m-d H:i:s');

// Get client information
$clientPlatform = isset($_SERVER['HTTP_X_CLIENT_PLATFORM']) ? $_SERVER['HTTP_X_CLIENT_PLATFORM'] : 'Unknown';
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

// Run database connection test
$dbTest = testDatabaseConnection();

// Simple connectivity test response with server status
$response = [
    'success' => true,
    'message' => 'API connection successful',
    'timestamp' => $timestamp,
    'request' => [
        'client_platform' => $clientPlatform,
        'user_agent' => $userAgent,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    ],
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    ],
    'database' => $dbTest,
    'directories' => getDirectoryPermissions()
];

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);
?>