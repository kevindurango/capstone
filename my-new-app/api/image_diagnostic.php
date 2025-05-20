<?php
// image_diagnostic.php - API endpoint to diagnose image loading issues

// Enable CORS for development
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");

// Include database connection
require_once '../includes/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Handle diagnostic requests for image paths
 * This endpoint helps the app find the correct image URL when there are loading issues
 */
function handleImageDiagnostic() {
    // Get the image path from request
    $path = isset($_GET['path']) ? $_GET['path'] : null;
    
    if (!$path) {
        echo json_encode([
            'success' => false,
            'message' => 'No image path provided',
            'data' => null
        ]);
        return;
    }
    
    // Clean the path
    $path = trim($path);
    
    // Remove any encoded query parameters
    if (strpos($path, '%3f') !== false || strpos($path, '%3F') !== false) {
        $parts = preg_split('/%3[fF]/', $path);
        $path = $parts[0];
    }
    
    // Extract filename
    $filename = basename($path);
    
    // Server paths to check
    $serverPaths = [
        // Standard paths
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/capstone/uploads/products/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/capstone/public/uploads/products/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/products/' . $filename,
        
        // Exact path from request
        $_SERVER['DOCUMENT_ROOT'] . '/' . $path,
        
        // Try without 'public' prefix
        $_SERVER['DOCUMENT_ROOT'] . '/' . str_replace('public/', '', $path),
        
        // Try with 'public' prefix
        $_SERVER['DOCUMENT_ROOT'] . '/public/' . str_replace('public/', '', $path),
        
        // Try full app path
        $_SERVER['DOCUMENT_ROOT'] . '/capstone/my-new-app/' . $path,
    ];
    
    // URL paths to recommend
    $urlPaths = [
        // Standard paths
        'http://' . $_SERVER['HTTP_HOST'] . '/uploads/products/' . $filename,
        'http://' . $_SERVER['HTTP_HOST'] . '/capstone/uploads/products/' . $filename,
        'http://' . $_SERVER['HTTP_HOST'] . '/capstone/public/uploads/products/' . $filename,
        'http://' . $_SERVER['HTTP_HOST'] . '/public/uploads/products/' . $filename,
        
        // Exact path from request
        'http://' . $_SERVER['HTTP_HOST'] . '/' . $path,
        
        // Try API image endpoint
        'http://' . $_SERVER['HTTP_HOST'] . '/capstone/api/image.php?path=' . urlencode($filename),
    ];
    
    // Special handling for product_* filenames (like the problematic product 75)
    if (strpos($filename, 'product_') === 0) {
        // Add paths that match the format of working products (product 69/73)
        $extraPaths = [
            'http://' . $_SERVER['HTTP_HOST'] . '/capstone/uploads/' . $filename,
            'http://' . $_SERVER['HTTP_HOST'] . '/capstone/public/uploads/' . $filename,
            'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $filename,
            // Check if file exists in other product directories
            'http://' . $_SERVER['HTTP_HOST'] . '/capstone/uploads/product-images/' . $filename,
        ];
        
        // Add these at the beginning for priority
        $urlPaths = array_merge($extraPaths, $urlPaths);
        
        // Look for the file in the filesystem directly
        $query = "SELECT file_path FROM product_images WHERE file_name LIKE '%" . basename($filename, '.jpeg') . "%'";
        if (function_exists('mysqli_connect')) {
            $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            if ($conn) {
                $result = mysqli_query($conn, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    // Add this path to our checks
                    array_unshift($urlPaths, 'http://' . $_SERVER['HTTP_HOST'] . '/' . $row['file_path']);
                }
                mysqli_close($conn);
            }
        }
    }
    
    // Results
    $foundPaths = [];
    $workingUrls = [];
    
    // Check server paths
    foreach ($serverPaths as $serverPath) {
        if (file_exists($serverPath)) {
            $foundPaths[] = $serverPath;
        }
    }
    
    // Check URLs
    foreach ($urlPaths as $urlPath) {
        $headers = get_headers($urlPath, 1);
        $status = substr($headers[0], 9, 3);
        
        if ($status >= 200 && $status < 400) {
            $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';
            if (is_array($contentType)) {
                $contentType = $contentType[0];
            }
            
            if (strpos($contentType, 'image/') !== false) {
                $workingUrls[] = $urlPath;
            }
        }
    }
    
    // Add timestamp to the recommended URL to prevent caching
    $recommendedUrls = array_map(function($url) {
        return $url . (strpos($url, '?') !== false ? '&' : '?') . 't=' . time();
    }, $workingUrls);
    
    // Send response
    echo json_encode([
        'success' => true,
        'message' => count($foundPaths) > 0 ? 'Found ' . count($foundPaths) . ' physical files and ' . count($workingUrls) . ' accessible URLs' : 'No physical files found',
        'data' => [
            'originalPath' => $path,
            'filename' => $filename,
            'serverPaths' => $foundPaths,
            'directUrl' => count($recommendedUrls) > 0 ? $recommendedUrls[0] : null,
            'recommendedUrls' => $recommendedUrls
        ]
    ]);
}

// Handle the request
handleImageDiagnostic();
