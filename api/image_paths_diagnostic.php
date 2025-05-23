<?php
// Diagnostic endpoint to check product image paths in database
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Include database connection
require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();

try {
    // Get products with focus on image paths
    $stmt = $conn->prepare("SELECT product_id, name, image FROM products WHERE image IS NOT NULL LIMIT 50");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process products to add image URLs for testing
    $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $baseUrl .= $_SERVER['HTTP_HOST'];
    $baseUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    
    $processedProducts = [];
    
    foreach ($products as $product) {
        // Different URL formats to try
        $imagePath = $product['image'];
        $urls = [
            'original' => $imagePath,
            'with_prefix' => 'uploads/products/' . basename($imagePath),
            'full_path_1' => $baseUrl . $imagePath,
            'full_path_2' => $baseUrl . 'public/' . $imagePath,
            'full_path_3' => $baseUrl . 'uploads/products/' . basename($imagePath)
        ];
        
        $processedProducts[] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'image_path' => $imagePath,
            'test_urls' => $urls
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'products' => $processedProducts,
        'base_url' => $baseUrl,
        'server_path' => $_SERVER['DOCUMENT_ROOT']
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
