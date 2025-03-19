<?php
session_start();
require_once '../../models/Product.php';

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $newStock = isset($_POST['new_stock']) ? (int)$_POST['new_stock'] : null;

    if ($productId && $newStock !== null) {
        try {
            $product = new Product();
            $success = $product->updateStock($productId, $newStock);
            
            if ($success) {
                error_log("Stock updated successfully for product ID: $productId, New stock: $newStock");
            } else {
                error_log("Failed to update stock for product ID: $productId");
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Stock updated successfully' : 'Failed to update stock'
            ]);
            exit();
        } catch (Exception $e) {
            error_log("Error in update-stock.php: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error updating stock: ' . $e->getMessage()
            ]);
            exit();
        }
    } else {
        error_log("Invalid request parameters - Product ID: $productId, New Stock: $newStock");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request parameters'
        ]);
        exit();
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit();
?>
