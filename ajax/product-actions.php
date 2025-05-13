<?php
session_start();
require_once '../controllers/ProductController.php';

// Check authorization - allow both Manager and Admin roles
$isManager = isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true && $_SESSION['role'] === 'Manager';
$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';

if (!$isManager && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize product controller
$productController = new ProductController();

// Set header to JSON
header('Content-Type: application/json');

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            // Get form data
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $stock = $_POST['stock'] ?? 0;
            $farmer_id = $_POST['farmer_id'] ?? null;
            $category_id = $_POST['category_id'] ?? null;
            
            // Handle image upload
            $image = $_FILES['image'] ?? null;
            
            // Validate required fields
            if (empty($name) || empty($price)) {
                echo json_encode(['success' => false, 'message' => 'Product name and price are required']);
                exit();
            }
            
            // Add product
            $result = $productController->addProduct($name, $description, $price, $stock, $farmer_id, $image);
            
            if ($result) {
                // If category is selected, assign category
                if (!empty($category_id)) {
                    $productController->assignProductCategory($result, $category_id);
                }
                
                // Get the newly added product
                $product = $productController->getProductById($result);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added successfully',
                    'product' => $product
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product']);
            }
            break;
            
        case 'update_product':
            // Get form data
            $product_id = $_POST['product_id'] ?? '';
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $stock = $_POST['stock'] ?? 0;
            $farmer_id = $_POST['farmer_id'] ?? null;
            $category_id = $_POST['category_id'] ?? null;
            $current_image = $_POST['current_image'] ?? '';
            
            // Handle image upload
            $image = $_FILES['image'] ?? null;
            
            // Validate required fields
            if (empty($product_id) || empty($name) || empty($price)) {
                echo json_encode(['success' => false, 'message' => 'Product ID, name, and price are required']);
                exit();
            }
            
            // Update product
            $result = $productController->editProduct($product_id, $name, $description, $price, $stock, $farmer_id, $image, $current_image);
            
            if ($result) {
                // If category is selected, update category
                if (!empty($category_id)) {
                    $productController->updateProductCategory($product_id, $category_id);
                }
                
                // Get the updated product
                $product = $productController->getProductById($product_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'product' => $product
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update product']);
            }
            break;
            
        case 'delete_product':
            $product_id = $_POST['product_id'] ?? '';
            
            if (empty($product_id)) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit();
            }
            
            $result = $productController->deleteProduct($product_id);
            
            if ($result) {
                // Log activity based on user role
                if (isset($_SESSION['user_id'])) {
                    require_once '../models/Log.php';
                    $logClass = new Log();
                    $role = $isAdmin ? 'Admin' : 'Manager';
                    $logClass->logActivity($_SESSION['user_id'], "$role deleted product ID: $product_id");
                }
                
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $productController->getLastError()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
