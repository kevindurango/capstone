<?php
session_start();
require_once '../models/Database.php';
require_once '../models/Order.php';
require_once '../models/Log.php';
require_once '../models/Pickup.php';

// Check for valid session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for CSRF token in all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || 
    !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$database = new Database();
$conn = $database->connect();
$logClass = new Log();
$pickupClass = new Pickup();

// Get user ID from session for logging
$user_id = isset($_SESSION['organization_head_user_id']) ? $_SESSION['organization_head_user_id'] : null;

// Handle different AJAX actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'getOrderDetails':
        getOrderDetails();
        break;
    
    case 'updatePickupStatus':
        updatePickupStatus();
        break;
    
    case 'schedulePickup':
        schedulePickup();
        break;
    
    case 'getPickupDetails':
        getPickupDetails();
        break;
        
    case 'getProductDetails':
        getProductDetails();
        break;
        
    case 'updateProduct':
        updateProduct();
        break;
        
    case 'updateProductPrice':
        updateProductPrice();
        break;
        
    case 'addProduct':
        addProduct();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get detailed order information
 */
function getOrderDetails() {
    global $conn, $user_id, $logClass;
    
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }
    
    try {
        // Get order details
        $query = "SELECT o.*, 
                 u.username as consumer_name,
                 u.contact_number, 
                 u.email,
                 u.first_name,
                 u.last_name
                 FROM orders o
                 JOIN users u ON o.consumer_id = u.user_id
                 WHERE o.order_id = :order_id";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute(['order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo '<div class="alert alert-warning">Order not found</div>';
            return;
        }
        
        // Get pickup details for this order
        $pickupQuery = "SELECT p.*
                          FROM pickups p
                      WHERE p.order_id = :order_id";
            $stmt = $conn->prepare($pickupQuery);
        $stmt->execute(['order_id' => $order_id]);
            $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get order items
        $itemsQuery = "SELECT oi.*, p.name
                      FROM orderitems oi
                      LEFT JOIN products p ON oi.product_id = p.product_id
                      WHERE oi.order_id = :order_id";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute(['order_id' => $order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log activity
        if ($user_id) {
            $logClass->logActivity($user_id, "Viewed details for Order #$order_id");
        }
        
        // Show the order details
        outputOrderDetails($order, $pickup, $items);
        
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error loading order details: ' . $e->getMessage() . '</div>';
        error_log($e->getMessage());
    }
}

/**
 * Generate HTML for order details popup
 */
function outputOrderDetails($order, $pickup, $items) {
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += ($item['price'] * $item['quantity']);
    }
    
    $html = '<div class="order-details">';
    $html .= '<h4 class="mb-3">Order #' . $order['order_id'] . '</h4>';
    
    // Order information
    $html .= '<div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Order Information
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Status:</div>
                        <div class="col-7"><span class="badge badge-' . 
                            ($order['order_status'] === 'completed' ? 'success' : 
                            ($order['order_status'] === 'canceled' ? 'danger' : 'primary')) . 
                            '">' . ucfirst($order['order_status']) . '</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Date:</div>
                        <div class="col-7">' . date('M j, Y g:i A', strtotime($order['order_date'])) . '</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Consumer:</div>
                        <div class="col-7">' . htmlspecialchars($order['consumer_name']) . '</div>
                    </div>';
                    
    if (!empty($order['contact_number'])) {
        $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Contact:</div>
                        <div class="col-7">' . htmlspecialchars($order['contact_number']) . '</div>
                  </div>';
    }
    
    if (!empty($order['email'])) {
        $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Email:</div>
                        <div class="col-7">' . htmlspecialchars($order['email']) . '</div>
            </div>';
    }
                    
    $html .= '</div>
            </div>';
    
    // Pickup Details (if available)
    if ($pickup) {
        $html .= '<div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-truck"></i> Pickup Details
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Pickup ID:</div>
                            <div class="col-7">#' . $pickup['pickup_id'] . '</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Status:</div>
                            <div class="col-7"><span class="badge badge-' . 
                                ($pickup['pickup_status'] === 'completed' ? 'success' : 
                                ($pickup['pickup_status'] === 'pending' ? 'warning' : 
                                ($pickup['pickup_status'] === 'assigned' ? 'info' : 'primary'))) . 
                                '">' . ucfirst($pickup['pickup_status']) . '</span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Scheduled Date:</div>
                            <div class="col-7">' . date('M j, Y g:i A', strtotime($pickup['pickup_date'])) . '</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Location:</div>
                            <div class="col-7">' . htmlspecialchars($pickup['pickup_location']) . '</div>
                        </div>';
                        
        if (!empty($pickup['contact_person'])) {
            $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Contact Person:</div>
                        <div class="col-7">' . htmlspecialchars($pickup['contact_person']) . '</div>
                      </div>';
        }
        
        if (!empty($pickup['pickup_notes'])) {
            $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Notes:</div>
                        <div class="col-7">' . nl2br(htmlspecialchars($pickup['pickup_notes'])) . '</div>
                      </div>';
        }
        
        $html .= '</div></div>';
    }
    
    // Order Items
    $html .= '<div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-cart"></i> Order Items
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>';
    
    if (!empty($items)) {
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $productName = !empty($item['name']) ? $item['name'] : 'Product #' . $item['product_id'];
            $html .= '<tr>
                        <td>' . htmlspecialchars($productName) . '</td>
                        <td class="text-right">₱' . number_format($item['price'], 2) . '</td>
                        <td class="text-right">' . $item['quantity'] . '</td>
                        <td class="text-right">₱' . number_format($subtotal, 2) . '</td>
                      </tr>';
        }
    } else {
        $html .= '<tr><td colspan="4" class="text-center">No items found</td></tr>';
    }
    
    $html .= '</tbody>
              <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total Amount:</th>
                    <th class="text-right">₱' . number_format($totalAmount, 2) . '</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>';
    
    // Pickup Details from Order
    if (!empty($order['pickup_details'])) {
        $html .= '<div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-info-circle"></i> Additional Pickup Notes
                    </div>
                    <div class="card-body">
                        <p>' . nl2br(htmlspecialchars($order['pickup_details'])) . '</p>
                    </div>
                  </div>';
    }
    
    $html .= '</div>';
    
    echo $html;
}

/**
 * Update pickup status
 */
function updatePickupStatus() {
    global $conn, $user_id, $logClass, $pickupClass;
    
    $pickup_id = (int)($_POST['pickup_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    // Validate input
    if (!$pickup_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Invalid pickup ID or status']);
        return;
    }
    
    // Validate status
    $validStatuses = ['pending', 'assigned', 'completed', 'canceled'];
    if (!in_array($new_status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get current pickup info
        $query = "SELECT p.*, o.order_id FROM pickups p
                 JOIN orders o ON p.order_id = o.order_id
                 WHERE p.pickup_id = :pickup_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['pickup_id' => $pickup_id]);
        $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pickup) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Pickup not found']);
            return;
        }
        
        // Update pickup status using the Pickup model
        if ($pickupClass->updatePickupStatus($pickup['order_id'], $new_status)) {
        // Log activity
        if ($user_id) {
                $logMessage = "Updated pickup #$pickup_id status to $new_status";
            $logClass->logActivity($user_id, $logMessage);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pickup status updated successfully']);
        } else {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to update pickup status']);
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        error_log($e->getMessage());
    }
}

/**
 * Bulk schedule multiple pickups
 */
function bulkSchedulePickups() {
    global $conn, $user_id, $logClass;
    
    // Get POST data
    $orderIds = $_POST['order_ids'] ?? [];
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupLocation = $_POST['pickup_location'] ?? '';
    
    // Validate inputs
    if (empty($orderIds) || empty($pickupDate) || empty($pickupLocation)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Initialize counters
        $scheduled = 0;
        $skipped = 0;
        
        // Process each order
        foreach ($orderIds as $orderId) {
            // Check if pickup already exists for this order
            $checkQuery = "SELECT pickup_id FROM pickups WHERE order_id = :order_id";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute(['order_id' => $orderId]);
            
            if ($stmt->rowCount() > 0) {
                // Skip this order as it already has a pickup
                $skipped++;
                continue;
            }
            
            // Insert new pickup
            $insertQuery = "INSERT INTO pickups (order_id, pickup_date, pickup_location, pickup_status) 
                           VALUES (:order_id, :pickup_date, :pickup_location, 'scheduled')";
            $stmt = $conn->prepare($insertQuery);
            
            if ($stmt->execute([
                'order_id' => $orderId,
                'pickup_date' => $pickupDate,
                'pickup_location' => $pickupLocation
            ])) {
                $scheduled++;
            }
        }
        
        if ($scheduled > 0) {
            
            // Log activity
            if ($user_id) {
                $logClass->logActivity($user_id, "Bulk scheduled $scheduled pickups");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pickups scheduled successfully',
            'scheduled' => $scheduled,
            'skipped' => $skipped
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        error_log($e->getMessage());
    }
}

/**
 * Schedule a new pickup for an order
 */
function schedulePickup() {
    global $conn, $user_id, $logClass;
    
    // Get POST data
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $pickup_date = $_POST['pickup_date'] ?? '';
    $pickup_location = $_POST['pickup_location'] ?? '';
    $pickup_notes = $_POST['pickup_notes'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    
    // Validate required fields
    if (!$order_id || empty($pickup_date) || empty($pickup_location)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        // Check if order exists
        $orderQuery = "SELECT order_id FROM orders WHERE order_id = :order_id";
        $stmt = $conn->prepare($orderQuery);
        $stmt->execute(['order_id' => $order_id]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }
        
        // Check if pickup already exists for this order
        $checkQuery = "SELECT pickup_id FROM pickups WHERE order_id = :order_id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute(['order_id' => $order_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'A pickup already exists for this order']);
            return;
        }
        
        // Insert new pickup
        $insertQuery = "INSERT INTO pickups (order_id, pickup_date, pickup_location, pickup_notes, pickup_status, contact_person) 
                       VALUES (:order_id, :pickup_date, :pickup_location, :pickup_notes, 'pending', :contact_person)";
        $stmt = $conn->prepare($insertQuery);
        
        $result = $stmt->execute([
            'order_id' => $order_id,
            'pickup_date' => $pickup_date,
            'pickup_location' => $pickup_location,
            'pickup_notes' => $pickup_notes,
            'contact_person' => $contact_person
        ]);
        
        if ($result) {
            $pickup_id = $conn->lastInsertId();
            
            // Log activity
            if ($user_id) {
                $logClass->logActivity($user_id, "Scheduled new pickup #$pickup_id for order #$order_id");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pickup scheduled successfully',
                'pickup_id' => $pickup_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to schedule pickup']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        error_log($e->getMessage());
    }
}

/**
 * Get Pickup Details
 * This function fetches detailed information for a specific pickup
 */
function getPickupDetails() {
    if (!isset($_POST['pickup_id'])) {
        echo "<div class='alert alert-danger'>Error: No pickup ID provided.</div>";
        return;
    }
    
    $pickup_id = $_POST['pickup_id'];
    
    // Connect to database
    global $conn;
    
    try {
        // Get pickup details with order information
        $query = "SELECT 
            p.pickup_id, 
            p.order_id, 
            p.pickup_status, 
            p.pickup_date, 
            p.pickup_location, 
            p.pickup_notes
        FROM pickups p
        WHERE p.pickup_id = :pickup_id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute(['pickup_id' => $pickup_id]);
        $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pickup) {
            echo "<div class='alert alert-warning'>No pickup found with the given ID.</div>";
            return;
        }
        
        // Get order details for the pickup
        $orderQuery = "SELECT 
            o.order_date,
            o.order_status,
            o.pickup_details,
            CONCAT(c.first_name, ' ', c.last_name) AS consumer_name,
            c.contact_number AS consumer_phone,
            c.email AS consumer_email
        FROM orders o
        JOIN users c ON o.consumer_id = c.user_id
        WHERE o.order_id = :order_id";
        
        $stmt = $conn->prepare($orderQuery);
        $stmt->execute(['order_id' => $pickup['order_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get products in the order
        $productsQuery = "SELECT 
            oi.quantity,
            oi.price AS unit_price,
            p.name AS product_name,
            p.description
        FROM orderitems oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :order_id";
        
        $stmt = $conn->prepare($productsQuery);
        $stmt->execute(['order_id' => $pickup['order_id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total amount
        $totalAmount = 0;
        foreach ($products as $product) {
            $totalAmount += ($product['unit_price'] * $product['quantity']);
        }
        
        // Format the output HTML
        $html = '<div class="pickup-details">';
        
        // Pickup status badge
        $statusClass = '';
        switch(strtolower($pickup['pickup_status'])) {
            case 'pending': $statusClass = 'badge-pending'; break;
            case 'assigned': $statusClass = 'badge-scheduled'; break;
            case 'completed': $statusClass = 'badge-completed'; break;
            case 'canceled': $statusClass = 'badge-canceled'; break;
            default: $statusClass = 'badge-info';
        }
        
        // Pickup summary section
        $html .= '<div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Pickup Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Pickup ID:</strong> #' . htmlspecialchars($pickup['pickup_id']) . '</p>
                        <p><strong>Order ID:</strong> #' . htmlspecialchars($pickup['order_id']) . '</p>
                        <p><strong>Status:</strong> <span class="badge ' . $statusClass . '">' . htmlspecialchars(ucfirst($pickup['pickup_status'])) . '</span></p>
                        <p><strong>Location:</strong> <i class="bi bi-geo-alt"></i> ' . htmlspecialchars($pickup['pickup_location']) . '</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Scheduled Time:</strong> ' . date('M j, Y g:i A', strtotime($pickup['pickup_date'])) . '</p>';
        
        if ($order) {
            $html .= '<p><strong>Order Date:</strong> ' . date('M j, Y', strtotime($order['order_date'])) . '</p>';
        }
        
        $html .= '<p><strong>Total Amount:</strong> ₱' . number_format($totalAmount, 2) . '</p>';
        
        if (!empty($pickup['contact_person'])) {
            $html .= '<p><strong>Contact Person:</strong> ' . htmlspecialchars($pickup['contact_person']) . '</p>';
        }
                
        $html .= '</div></div>';
        
        if (!empty($pickup['pickup_notes'])) {
            $html .= '<div class="mt-3 p-3 bg-light rounded">
                <strong>Notes:</strong><br>' . nl2br(htmlspecialchars($pickup['pickup_notes'])) . '
            </div>';
        }
        
        $html .= '</div></div>';
        
        // Consumer information section
        if ($order) {
            $html .= '<div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Consumer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> ' . htmlspecialchars($order['consumer_name'] ?? 'N/A') . '</p>
                            <p><strong>Phone:</strong> <a href="tel:' . htmlspecialchars($order['consumer_phone'] ?? '') . '">' . htmlspecialchars($order['consumer_phone'] ?? 'N/A') . '</a></p>
                            <p><strong>Email:</strong> <a href="mailto:' . htmlspecialchars($order['consumer_email'] ?? '') . '">' . htmlspecialchars($order['consumer_email'] ?? 'N/A') . '</a></p>
                        </div>';
            
            if (!empty($order['pickup_details'])) {
                $html .= '<div class="col-md-6">
                    <p><strong>Additional Pickup Details:</strong></p>
                    <p>' . nl2br(htmlspecialchars($order['pickup_details'])) . '</p>
                </div>';
            }
            
            $html .= '</div></div></div>';
        }
        

        $html .= '<div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-box"></i> Order Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        if (!empty($products)) {
            foreach ($products as $product) {
                $productTotal = $product['quantity'] * $product['unit_price'];
                $productName = !empty($product['product_name']) ? $product['product_name'] : 'Unknown Product';
                $html .= '<tr>
                    <td>' . htmlspecialchars($productName) . '</td>
                    <td>' . htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . (strlen($product['description'] ?? '') > 50 ? '...' : '') . '</td>
                    <td>' . htmlspecialchars($product['quantity']) . '</td>
                    <td>₱' . number_format($product['unit_price'], 2) . '</td>
                    <td>₱' . number_format($productTotal, 2) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" class="text-center">No products found for this order.</td></tr>';
        }
        
        $html .= '</tbody></table>
                </div>
            </div>
        </div>';
        
        $html .= '</div>'; // Close pickup-details div
        
        echo $html;
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Pickup details error: " . $e->getMessage());
    }
}

/**
 * Get detailed product information for editing
 */
function getProductDetails() {
    global $conn, $user_id, $logClass;
    
    // Load the ProductController
    require_once '../controllers/ProductController.php';
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        // Initialize ProductController
        $productController = new ProductController();
        
        // Get product details
        $product = $productController->getProductWithDetails($product_id);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        // Log this activity
        $logClass->logActivity($user_id, "Retrieved product details for product ID: $product_id");
        
        // Return product data
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Update a product
 */
function updateProduct() {
    global $conn, $user_id, $logClass;
    
    // Load the ProductController
    require_once '../controllers/ProductController.php';
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        // Initialize ProductController
        $productController = new ProductController();
        
        // Prepare data for update
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'unit_type' => $_POST['unit_type'] ?? 'piece',
            'current_image' => $_POST['current_image'] ?? '',
            'image' => $_FILES['image'] ?? null
        ];
        
        // Update product
        $result = $productController->updateProduct($product_id, $data);
        
        if ($result) {
            // Log this activity
            $logClass->logActivity($user_id, "Updated product ID: $product_id");
            
            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to update product: ' . $productController->getLastError()
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Update just the price of a product
 */
function updateProductPrice() {
    global $conn, $user_id, $logClass;
    
    // Load the ProductController
    require_once '../controllers/ProductController.php';
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $new_price = isset($_POST['new_price']) ? (float)$_POST['new_price'] : 0;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if ($new_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price value']);
        return;
    }
    
    try {
        // Initialize ProductController
        $productController = new ProductController();
        
        // Get current product to store old price for logging
        $current_product = $productController->getProductById($product_id);
        $old_price = $current_product ? $current_product['price'] : 'unknown';
        
        // Update just the price
        $result = $productController->editProduct(
            $product_id,
            null,  // name (not changing)
            null,  // description (not changing)
            $new_price,
            null,  // stock (not changing)
            null,  // farmer_id (not changing)
            null,  // image (not changing)
            null,  // current_image (not needed for price update)
            null   // unit_type (not changing)
        );
        
        if ($result) {
            // Log this activity
            $logClass->logActivity($user_id, "Updated price for product ID: $product_id from ₱$old_price to ₱$new_price");
            
            echo json_encode([
                'success' => true,
                'message' => 'Price updated successfully',
                'new_price' => $new_price
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to update price: ' . $productController->getLastError()
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Add a new product
 */
function addProduct() {
    global $conn, $user_id, $logClass;
    
    // Load the ProductController
    require_once '../controllers/ProductController.php';
    
    try {
        // Initialize ProductController
        $productController = new ProductController();
        
        // Prepare data for product creation
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'unit_type' => $_POST['unit_type'] ?? 'piece',
            'image' => $_FILES['image'] ?? null
        ];
        
        // Validate product data
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'Product name is required']);
            return;
        }
        
        if ((float)$data['price'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Price must be greater than zero']);
            return;
        }
        
        // Add the product
        $product_id = $productController->addProduct($data);
        
        if ($product_id) {
            // Log this activity
            $logClass->logActivity($user_id, "Created new product: $data[name] (ID: $product_id)");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product created successfully',
                'product_id' => $product_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create product: ' . $productController->getLastError()
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>