<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database connection
require_once '../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if it's a POST or DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Get data from POST or from DELETE request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json_data = file_get_contents('php://input');
            $data = json_decode($json_data, true);
        } else {
            parse_str(file_get_contents('php://input'), $data);
        }
        
        // Check required fields
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            throw new Exception("User ID is required");
        }
        
        if (!isset($data['product_id']) || empty($data['product_id'])) {
            throw new Exception("Product ID is required");
        }
        
        // Get parameters
        $user_id = intval($data['user_id']);
        $product_id = intval($data['product_id']);
        
        // Get database connection
        $conn = getConnection();
        
        // Start transaction manually (compatible with all MySQLi versions)
        $conn->query('START TRANSACTION');
        $transaction_started = true;
        
        // Check if product exists and belongs to the user
        $stmt = $conn->prepare("SELECT p.*, u.role_id FROM products p
                               JOIN users u ON p.farmer_id = u.user_id
                               WHERE p.product_id = ? AND p.farmer_id = ?");
        $stmt->bind_param("ii", $product_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found or you don't have permission to delete it");
        }
        
        $product = $result->fetch_assoc();
        if ($product['role_id'] !== 2) { // 2 is the role_id for 'Farmer'
            throw new Exception("Only farmers can delete products");
        }
        
        // Get the current image path
        $image_path = $product['image'];
        
        // Check if the product is used in any orders
        $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orderitems WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_check = $result->fetch_assoc();
        
        if ($order_check['order_count'] > 0) {
            throw new Exception("Cannot delete a product that has been ordered. Consider marking it as out of stock instead.");
        }
        
        // Delete product category mappings
        $stmt = $conn->prepare("DELETE FROM productcategorymapping WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete product category mappings: " . $stmt->error);
        }
        
        // Delete product seasons if applicable
        $stmt = $conn->prepare("DELETE FROM product_seasons WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Delete barangay_products entries if they exist
        $stmt = $conn->prepare("DELETE FROM barangay_products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete product: " . $stmt->error);
        }
        
        // Log activity
        $action = "Farmer ID: $user_id deleted product ID: $product_id";
        $log_stmt = $conn->prepare("INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $log_stmt->bind_param("is", $user_id, $action);
        $log_stmt->execute();
        
        // Commit transaction
        $conn->query('COMMIT');
        $transaction_started = false;
        
        // Delete product image if exists
        if ($image_path && file_exists('../../' . $image_path)) {
            unlink('../../' . $image_path);
        }
        
        // Set success response
        $response['success'] = true;
        $response['message'] = "Product deleted successfully";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($transaction_started) && $transaction_started) {
            $conn->query('ROLLBACK');
        }
        
        $response['message'] = $e->getMessage();
        error_log("Delete product error: " . $e->getMessage());
    } finally {
        // Close connection
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    $response['message'] = "Invalid request method";
}

// Return response
echo json_encode($response);
?>