<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('../config/database.php');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'products' => [],
    'stats' => []
];

// Get database connection
$conn = getConnection();

// Check connection
if (!$conn) {
    $response['message'] = 'Database connection failed: ' . mysqli_connect_error();
    echo json_encode($response);
    exit;
}

// Process based on request type
try {
    // Require user_id parameter
    if (!isset($_GET['user_id'])) {
        $response['message'] = 'Missing required parameter: user_id';
        echo json_encode($response);
        exit;
    }

    $user_id = intval($_GET['user_id']);

    // Check if user exists and is a farmer
    $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2"; // role_id 2 is for farmers
    $stmt = $conn->prepare($user_check_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 0) {
        $response['message'] = 'User is not found or not a farmer';
        echo json_encode($response);
        exit;
    }

    // If stats flag is set, return product statistics
    if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
        // Get product statistics for the farmer
        $stats_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM products 
            WHERE farmer_id = ?";

        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();

        if ($stats_result && $stats_row = $stats_result->fetch_assoc()) {
            $response['success'] = true;
            $response['message'] = 'Product statistics retrieved successfully';
            $response['stats'] = [
                'total' => (int)$stats_row['total'],
                'pending' => (int)$stats_row['pending'],
                'approved' => (int)$stats_row['approved'],
                'rejected' => (int)$stats_row['rejected']
            ];
        } else {
            $response['success'] = true; // Changed to true to avoid frontend error
            $response['message'] = 'No product statistics available';
            $response['stats'] = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
        }
    } else {
        // Get all products for the farmer with their categories
        $products_query = "SELECT p.*, 
                          GROUP_CONCAT(pc.category_name SEPARATOR ',') as categories,
                          GROUP_CONCAT(pc.category_id SEPARATOR ',') as category_ids
                          FROM products p 
                          LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                          LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
                          WHERE p.farmer_id = ? 
                          GROUP BY p.product_id
                          ORDER BY p.created_at DESC";

        $stmt = $conn->prepare($products_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $products_result = $stmt->get_result();

        $products = [];
        while ($row = $products_result->fetch_assoc()) {
            // Format the product data
            $products[] = [
                'product_id' => (int)$row['product_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'image' => $row['image'],
                'stock' => (int)$row['stock'],
                'unit_type' => $row['unit_type'],
                'categories' => $row['categories'],
                'category_ids' => $row['category_ids']
            ];
        }

        // Always set success to true even if no products found
        $response['success'] = true;
        
        if (count($products) > 0) {
            $response['message'] = 'Products retrieved successfully';
            $response['products'] = $products;
        } else {
            $response['message'] = 'No products found for this farmer';
            $response['products'] = [];
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);