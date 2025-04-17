<?php
session_start();

// Check if the manager is logged in
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Include necessary models
require_once '../../models/Product.php';
require_once '../../models/Log.php';

// Check if it's a POST request with required fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['new_stock'])) {
    $productId = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $newStock = filter_var($_POST['new_stock'], FILTER_VALIDATE_INT);
    
    // Validate inputs
    if (!$productId || $newStock === false || $newStock < 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input parameters'
        ]);
        exit();
    }
    
    // Update stock
    try {
        $product = new Product();
        // Use the updateProductStock method from your schema
        $result = $product->updateProductStock($productId, $newStock);
        
        if ($result) {
            // Log the activity in activitylogs table
            $logClass = new Log();
            $manager_id = $_SESSION['user_id'] ?? null;
            $logClass->logActivity($manager_id, "Updated stock for product ID: $productId to $newStock");
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Stock updated successfully'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update stock: ' . $product->getLastError()
            ]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    // Invalid request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
