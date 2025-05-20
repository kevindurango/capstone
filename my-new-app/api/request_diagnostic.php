<?php
// request_diagnostic.php - API endpoint to diagnose issues with API requests

// Set headers to allow cross-origin requests and preflight
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400"); // 24 hours for preflight cache
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Response array
$response = [
    'success' => true,
    'timestamp' => date("Y-m-d H:i:s"),
    'method' => $_SERVER['REQUEST_METHOD'],
    'server_info' => [],
    'request_headers' => [],
    'request_body' => null,
    'php_info' => []
];

// Server information
$response['server_info'] = [
    'server_software' => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown",
    'document_root' => $_SERVER["DOCUMENT_ROOT"] ?? "Unknown",
    'server_protocol' => $_SERVER["SERVER_PROTOCOL"] ?? "Unknown",
    'request_time' => $_SERVER["REQUEST_TIME"] ?? "Unknown",
    'max_upload_size' => ini_get("upload_max_filesize"),
    'post_max_size' => ini_get("post_max_size"),
    'max_execution_time' => ini_get("max_execution_time"),
    'memory_limit' => ini_get("memory_limit")
];

// Request headers - helpful for debugging CORS and authentication issues
$response['request_headers'] = getallheaders();

// Process POST data differently based on content type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : "";
    
    if (strpos($contentType, "multipart/form-data") !== false) {
        // Handle multipart form data - common with file uploads
        $response['request_body'] = [
            'POST' => array_map(function($item) {
                // Don't include actual file data, just metadata
                if (is_array($item) && isset($item['name'])) {
                    return [
                        'name' => $item['name'],
                        'type' => $item['type'] ?? 'unknown',
                        'size' => $item['size'] ?? 0
                    ];
                }
                return $item;
            }, $_POST),
            'FILES' => array_map(function($item) {
                return [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'size' => $item['size'],
                    'error' => $item['error'],
                    'error_message' => uploadErrorMessage($item['error']),
                    'tmp_name_exists' => !empty($item['tmp_name']) && file_exists($item['tmp_name'])
                ];
            }, $_FILES)
        ];
    } else if (strpos($contentType, "application/json") !== false) {
        // Handle JSON input
        $input = file_get_contents('php://input');
        $response['request_body'] = [
            'raw' => substr($input, 0, 1000), // Limit the size for safety
            'parsed' => json_decode($input, true)
        ];
    } else {
        // Other content types
        $response['request_body'] = [
            'POST' => $_POST,
            'FILES' => array_map(function($item) {
                return [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'size' => $item['size']
                ];
            }, $_FILES),
            'raw' => substr(file_get_contents('php://input'), 0, 1000)
        ];
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response['request_body'] = $_GET;
}

// PHP environment info
$response['php_info'] = [
    'version' => phpversion(),
    'extensions' => get_loaded_extensions(),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'display_errors' => ini_get('display_errors'),
    'file_uploads' => ini_get('file_uploads')
];

// Helper function to translate upload error codes
function uploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_OK: return "No error";
        case UPLOAD_ERR_INI_SIZE: return "File exceeds upload_max_filesize directive";
        case UPLOAD_ERR_FORM_SIZE: return "File exceeds MAX_FILE_SIZE directive";
        case UPLOAD_ERR_PARTIAL: return "File was only partially uploaded";
        case UPLOAD_ERR_NO_FILE: return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR: return "Missing temporary folder";
        case UPLOAD_ERR_CANT_WRITE: return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION: return "A PHP extension stopped the upload";
        default: return "Unknown upload error";
    }
}

// Return the diagnostic information
echo json_encode($response, JSON_PRETTY_PRINT);
