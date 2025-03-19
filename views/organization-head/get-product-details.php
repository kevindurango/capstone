<?php
session_start();

// Check if the user is logged in as an Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once '../../controllers/ProductController.php';

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit();
}

$productController = new ProductController();
$product_id = intval($_GET['product_id']);

// Get product details
$product = $productController->getProductById($product_id);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit();
}

// Return product details as JSON
header('Content-Type: application/json');
echo json_encode($product);
exit();