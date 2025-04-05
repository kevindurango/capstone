<?php
session_start();
require_once '../models/Database.php';
require_once '../models/Order.php';
require_once '../models/Log.php';

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
    
    case 'bulkSchedulePickups':
        bulkSchedulePickups();
        break;
    
    case 'getPickupDetails':
        getPickupDetails();
        break;
        
    case 'getDriverDetails':
        getDriverDetails();
        break;
        
    case 'getDriverActivePickups':
        getDriverActivePickups();
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
    $pickup_id = isset($_POST['pickup_id']) ? (int)$_POST['pickup_id'] : 0;
    
    if (!$order_id && !$pickup_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID or Pickup ID is required']);
        return;
    }
    
    try {
        // If we have a pickup ID but no order ID, get the order ID from pickup
        if ($pickup_id && !$order_id) {
            $pickupQuery = "SELECT order_id FROM pickups WHERE pickup_id = :pickup_id";
            $stmt = $conn->prepare($pickupQuery);
            $stmt->execute(['pickup_id' => $pickup_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $order_id = $result['order_id'];
            }
        }
        
        // Get order details
        $query = "SELECT o.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as consumer_name,
                 u.contact_number, u.email,
                 s.address, s.city, s.state, s.zip_code, s.country, s.shipping_status
                 FROM orders o
                 JOIN users u ON o.consumer_id = u.user_id
                 LEFT JOIN shippinginfo s ON o.order_id = s.order_id
                 WHERE o.order_id = :order_id";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute(['order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo '<div class="alert alert-warning">Order not found</div>';
            return;
        }
        
        // Get pickup details if we have a pickup ID
        $pickup = null;
        if ($pickup_id) {
            $pickupQuery = "SELECT p.*, 
                          CONCAT(d.first_name, ' ', d.last_name) as driver_name,
                          dd.contact_number as driver_contact,
                          dd.vehicle_type, dd.vehicle_plate
                          FROM pickups p
                          LEFT JOIN users d ON p.assigned_to = d.user_id
                          LEFT JOIN driver_details dd ON d.user_id = dd.user_id
                          WHERE p.pickup_id = :pickup_id";
            $stmt = $conn->prepare($pickupQuery);
            $stmt->execute(['pickup_id' => $pickup_id]);
            $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get order items
        $itemsQuery = "SELECT oi.*, p.name, p.price as unit_price
                      FROM orderitems oi
                      JOIN products p ON oi.product_id = p.product_id
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
        echo '<div class="alert alert-danger">Error loading order details. Please try again.</div>';
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
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Contact:</div>
                        <div class="col-7">' . htmlspecialchars($order['contact_number']) . '</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Email:</div>
                        <div class="col-7">' . htmlspecialchars($order['email']) . '</div>
                    </div>
                </div>
            </div>';
    
    // Shipping Information
    $html .= '<div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-geo-alt"></i> Shipping Information
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Address:</div>
                        <div class="col-7">' . htmlspecialchars($order['address'] ?? 'N/A') . '</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">City/State:</div>
                        <div class="col-7">' . 
                            htmlspecialchars(($order['city'] ?? '') . ', ' . ($order['state'] ?? '')) . 
                        '</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Zip/Country:</div>
                        <div class="col-7">' . 
                            htmlspecialchars(($order['zip_code'] ?? '') . ' ' . ($order['country'] ?? '')) . 
                        '</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Shipping Status:</div>
                        <div class="col-7"><span class="badge badge-' . 
                            ($order['shipping_status'] === 'delivered' ? 'success' : 
                            ($order['shipping_status'] === 'shipped' ? 'info' : 'warning')) . 
                            '">' . ucfirst($order['shipping_status'] ?? 'pending') . '</span></div>
                    </div>
                </div>
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
                                ($pickup['pickup_status'] === 'in transit' ? 'primary' : 'info'))) . 
                                '">' . ucfirst($pickup['pickup_status']) . '</span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Scheduled Date:</div>
                            <div class="col-7">' . date('M j, Y g:i A', strtotime($pickup['pickup_date'])) . '</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Location:</div>
                            <div class="col-7">' . htmlspecialchars($pickup['pickup_location']) . '</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Driver:</div>
                            <div class="col-7">' . htmlspecialchars($pickup['driver_name'] ?? 'Unassigned') . '</div>
                        </div>';
                        
        if (isset($pickup['driver_contact'])) {
            $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Driver Contact:</div>
                        <div class="col-7">' . htmlspecialchars($pickup['driver_contact']) . '</div>
                      </div>';
        }
        
        if (isset($pickup['vehicle_type'])) {
            $html .= '<div class="row mb-2">
                        <div class="col-5 text-muted">Vehicle:</div>
                        <div class="col-7">' . 
                            htmlspecialchars($pickup['vehicle_type'] . ' (' . $pickup['vehicle_plate'] . ')') . 
                        '</div>
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
            $html .= '<tr>
                        <td>' . htmlspecialchars($item['name']) . '</td>
                        <td class="text-right">₱' . number_format($item['unit_price'], 2) . '</td>
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
    
    $html .= '</div>';
    
    echo $html;
}

/**
 * Update pickup status
 */
function updatePickupStatus() {
    global $conn, $user_id, $logClass;
    
    $pickup_id = (int)($_POST['pickup_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    // Validate input
    if (!$pickup_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Invalid pickup ID or status']);
        return;
    }
    
    // Validate status
    $validStatuses = ['pending', 'scheduled', 'in transit', 'completed', 'canceled'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get current pickup info
        $query = "SELECT p.*, o.order_id, CONCAT(d.first_name, ' ', d.last_name) as driver_name 
                 FROM pickups p
                 JOIN orders o ON p.order_id = o.order_id
                 LEFT JOIN users d ON p.assigned_to = d.user_id
                 WHERE p.pickup_id = :pickup_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['pickup_id' => $pickup_id]);
        $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pickup) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Pickup not found']);
            return;
        }
        
        // Update pickup status
        $updateQuery = "UPDATE pickups SET pickup_status = :status WHERE pickup_id = :pickup_id";
        $stmt = $conn->prepare($updateQuery);
        $result = $stmt->execute([
            'status' => $status,
            'pickup_id' => $pickup_id
        ]);
        
        if (!$result) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to update pickup status']);
            return;
        }
        
        // If status is completed, update driver availability to available if needed
        if ($status === 'completed' && !empty($pickup['assigned_to'])) {
            $updateDriverQuery = "UPDATE driver_details SET availability_status = 'available'
                              WHERE user_id = :driver_id AND availability_status = 'busy'";
            $stmt = $conn->prepare($updateDriverQuery);
            $stmt->execute(['driver_id' => $pickup['assigned_to']]);
        }
        
        // Log activity
        if ($user_id) {
            $logMessage = "Updated pickup #$pickup_id status to $status";
            $logClass->logActivity($user_id, $logMessage);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pickup status updated successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error']);
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
    $driverId = $_POST['driver_id'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupLocation = $_POST['pickup_location'] ?? '';
    
    // Validate inputs
    if (empty($orderIds) || empty($driverId) || empty($pickupDate) || empty($pickupLocation)) {
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
            $insertQuery = "INSERT INTO pickups (order_id, assigned_to, pickup_date, pickup_location, pickup_status) 
                           VALUES (:order_id, :driver_id, :pickup_date, :pickup_location, 'scheduled')";
            $stmt = $conn->prepare($insertQuery);
            
            if ($stmt->execute([
                'order_id' => $orderId,
                'driver_id' => (string)$driverId,
                'pickup_date' => $pickupDate,
                'pickup_location' => $pickupLocation
            ])) {
                $scheduled++;
            }
        }
        
        // Update driver status to busy if at least one pickup was scheduled
        if ($scheduled > 0) {
            $updateDriverQuery = "UPDATE driver_details SET availability_status = 'busy' 
                                WHERE user_id = :driver_id";
            $stmt = $conn->prepare($updateDriverQuery);
            $stmt->execute(['driver_id' => $driverId]);
            
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
        // Get pickup details with driver and order information
        $query = "SELECT 
            p.pickup_id, 
            p.order_id, 
            p.pickup_status, 
            p.pickup_date, 
            p.pickup_location, 
            p.pickup_notes, 
            CONCAT(d.first_name, ' ', d.last_name) AS driver_name, 
            d.contact_number AS driver_phone,
            d.email AS driver_email,
            dd.vehicle_type,
            dd.vehicle_plate,
            dd.rating AS driver_rating
        FROM pickups p
        LEFT JOIN users d ON p.assigned_to = d.user_id
        LEFT JOIN driver_details dd ON d.user_id = dd.user_id
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
            CONCAT(c.first_name, ' ', c.last_name) AS consumer_name,
            c.contact_number AS consumer_phone,
            c.email AS consumer_email
        FROM orders o
        JOIN users c ON o.consumer_id = c.user_id
        WHERE o.order_id = :order_id";
        
        $stmt = $conn->prepare($orderQuery);
        $stmt->execute(['order_id' => $pickup['order_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get shipping details if available
        $shippingQuery = "SELECT 
            address, 
            city, 
            state,
            zip_code,
            country
        FROM shippinginfo
        WHERE order_id = :order_id";
        
        $stmt = $conn->prepare($shippingQuery);
        $stmt->execute(['order_id' => $pickup['order_id']]);
        $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get products in the order
        $productsQuery = "SELECT 
            oi.quantity,
            oi.price AS unit_price,
            p.name AS product_name,
            p.description
        FROM orderitems oi
        JOIN products p ON oi.product_id = p.product_id
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
            case 'scheduled': $statusClass = 'badge-scheduled'; break;
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
        
        $html .= '<p><strong>Total Amount:</strong> ₱' . number_format($totalAmount, 2) . '</p>
                </div>
            </div>';
                
        if (!empty($pickup['pickup_notes'])) {
            $html .= '<div class="mt-3 p-3 bg-light rounded">
                <strong>Notes:</strong><br>' . nl2br(htmlspecialchars($pickup['pickup_notes'])) . '
            </div>';
        }
                
        $html .= '</div></div>';
        
        // Driver information section
        $html .= '<div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Driver Information</h5>
            </div>
            <div class="card-body">';
        
        if (!empty($pickup['driver_name'])) {
            $html .= '<div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> ' . htmlspecialchars($pickup['driver_name']) . '</p>
                    <p><strong>Phone:</strong> <a href="tel:' . htmlspecialchars($pickup['driver_phone']) . '">' . htmlspecialchars($pickup['driver_phone']) . '</a></p>
                    <p><strong>Email:</strong> <a href="mailto:' . htmlspecialchars($pickup['driver_email']) . '">' . htmlspecialchars($pickup['driver_email']) . '</a></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Vehicle Type:</strong> ' . htmlspecialchars($pickup['vehicle_type'] ?? 'N/A') . '</p>
                    <p><strong>License Plate:</strong> ' . htmlspecialchars($pickup['vehicle_plate'] ?? 'N/A') . '</p>
                    <p><strong>Rating:</strong> ' . (!empty($pickup['driver_rating']) ? number_format($pickup['driver_rating'], 1) . '/5.0' : 'Not Rated') . '</p>
                </div>
            </div>';
        } else {
            $html .= '<div class="text-center py-3">
                <i class="bi bi-person-x text-muted" style="font-size: 2rem;"></i>
                <p class="mt-2 text-muted">No driver assigned to this pickup yet.</p>
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
            
            if ($shipping) {
                $html .= '<div class="col-md-6">
                    <p><strong>Address:</strong> ' . htmlspecialchars($shipping['address'] ?? 'N/A') . '</p>
                    <p><strong>City/State:</strong> ' . 
                        htmlspecialchars(($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? '')) . 
                    '</p>
                    <p><strong>ZIP/Country:</strong> ' . 
                        htmlspecialchars(($shipping['zip_code'] ?? '') . ' ' . ($shipping['country'] ?? '')) . 
                    '</p>
                </div>';
            }
            
            $html .= '</div></div></div>';
        }
        
        // Products section
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
                $html .= '<tr>
                    <td>' . htmlspecialchars($product['product_name']) . '</td>
                    <td>' . htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : '') . '</td>
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
 * Get Driver Details
 * This function fetches comprehensive details about a driver
 */
function getDriverDetails() {
    if (!isset($_POST['driver_id'])) {
        echo "<div class='alert alert-danger'>Error: No driver ID provided.</div>";
        return;
    }
    
    $driver_id = $_POST['driver_id'];
    
    // Connect to database
    global $conn;
    
    try {
        // Get driver details
        $query = "SELECT 
            u.user_id, 
            u.first_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.address,
            u.created_at,
            dd.vehicle_type,
            dd.vehicle_plate,
            dd.license_number,
            dd.availability_status,
            dd.max_load_capacity,
            dd.current_location,
            dd.rating,
            dd.completed_pickups
        FROM users u
        JOIN driver_details dd ON u.user_id = dd.user_id
        WHERE u.user_id = :driver_id AND u.role_id = 6";
        
        $stmt = $conn->prepare($query);
        $stmt->execute(['driver_id' => $driver_id]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$driver) {
            echo "<div class='alert alert-warning'>No driver found with the given ID.</div>";
            return;
        }
        
        // Get recent pickups by this driver
        $historyQuery = "SELECT 
            p.pickup_id,
            p.order_id,
            p.pickup_status,
            p.pickup_date,
            p.pickup_location
        FROM pickups p
        WHERE p.assigned_to = :driver_id
        ORDER BY p.pickup_date DESC
        LIMIT 5";
        
        $stmt = $conn->prepare($historyQuery);
        $stmt->execute(['driver_id' => $driver_id]);
        $recentPickups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format date for display
        $joinDate = new DateTime($driver['created_at']);
        $now = new DateTime();
        $interval = $joinDate->diff($now);
        $daysActive = $interval->days;
        
        // Build HTML response
        $html = '<div class="driver-details">';
        
        // Personal Information
        $html .= '<div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> ' . htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) . '</p>
                        <p><strong>Email:</strong> <a href="mailto:' . htmlspecialchars($driver['email']) . '">' . htmlspecialchars($driver['email']) . '</a></p>
                        <p><strong>Phone:</strong> <a href="tel:' . htmlspecialchars($driver['contact_number']) . '">' . htmlspecialchars($driver['contact_number']) . '</a></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Address:</strong> ' . htmlspecialchars($driver['address']) . '</p>
                        <p><strong>Driver Since:</strong> ' . $joinDate->format('M j, Y') . ' (' . $daysActive . ' days)</p>
                        <p><strong>Current Status:</strong> <span class="badge badge-' . getStatusBadgeClass($driver['availability_status']) . '">' . ucfirst($driver['availability_status']) . '</span></p>
                    </div>
                </div>
            </div>
        </div>';
        
        // Vehicle Information
        $html .= '<div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-truck"></i> Vehicle Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Vehicle Type:</strong> ' . htmlspecialchars($driver['vehicle_type']) . '</p>
                        <p><strong>License Plate:</strong> ' . htmlspecialchars($driver['vehicle_plate']) . '</p>
                        <p><strong>License Number:</strong> ' . htmlspecialchars($driver['license_number']) . '</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Max Load Capacity:</strong> ' . ($driver['max_load_capacity'] ? htmlspecialchars($driver['max_load_capacity']) . ' kg' : 'N/A') . '</p>
                        <p><strong>Current Location:</strong> ' . (empty($driver['current_location']) ? 'Not specified' : htmlspecialchars($driver['current_location'])) . '</p>
                    </div>
                </div>
            </div>
        </div>';
        
        // Performance Metrics
        $html .= '<div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Performance Metrics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Completed Pickups:</strong> ' . $driver['completed_pickups'] . '</p>
                        <p><strong>Efficiency Rate:</strong> ' . ($driver['completed_pickups'] > 0 ? '95%' : 'N/A') . '</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Rating:</strong> ';
        
        // Generate star rating
        if ($driver['rating']) {
            $rating = number_format($driver['rating'], 1);
            $html .= '<span class="text-warning">';
            $fullStars = floor($rating);
            for ($i = 0; $i < $fullStars; $i++) {
                $html .= '<i class="bi bi-star-fill"></i> ';
            }
            if ($rating - $fullStars >= 0.5) {
                $html .= '<i class="bi bi-star-half"></i> ';
            }
            $emptyStars = 5 - $fullStars - ($rating - $fullStars >= 0.5 ? 1 : 0);
            for ($i = 0; $i < $emptyStars; $i++) {
                $html .= '<i class="bi bi-star"></i> ';
            }
            $html .= '</span> (' . $rating . '/5.0)';
        } else {
            $html .= 'Not yet rated';
        }
        
        $html .= '</p>
                        <p><strong>Average Completion Time:</strong> ' . ($driver['completed_pickups'] > 0 ? '1.5 hours' : 'N/A') . '</p>
                    </div>
                </div>
            </div>
        </div>';
        
        // Recent Pickups
        $html .= '<div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Pickup History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        if (!empty($recentPickups)) {
            foreach ($recentPickups as $pickup) {
                $html .= '<tr>
                    <td>#' . $pickup['pickup_id'] . '</td>
                    <td>' . date('M j, Y g:i A', strtotime($pickup['pickup_date'])) . '</td>
                    <td>' . htmlspecialchars($pickup['pickup_location']) . '</td>
                    <td><span class="badge badge-' . getStatusBadgeClass($pickup['pickup_status']) . '">' . 
                    ucfirst($pickup['pickup_status']) . '</span></td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="4" class="text-center">No recent pickups found.</td></tr>';
        }
        
        $html .= '</tbody></table>
                </div>
            </div>
        </div>';
        
        $html .= '</div>';
        
        echo $html;
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Driver details error: " . $e->getMessage());
    }
}

/**
 * Get Driver's Active Pickups
 * This function fetches current active pickups assigned to a driver
 */
function getDriverActivePickups() {
    if (!isset($_POST['driver_id'])) {
        echo "<div class='alert alert-danger'>Error: No driver ID provided.</div>";
        return;
    }
    
    $driver_id = $_POST['driver_id'];
    
    // Connect to database
    global $conn;
    
    try {
        // Get active pickups for this driver
        $query = "SELECT 
            p.pickup_id, 
            p.order_id,
            p.pickup_status,
            p.pickup_date,
            p.pickup_location,
            p.pickup_notes
        FROM pickups p
        WHERE p.assigned_to = :driver_id
        AND p.pickup_status IN ('scheduled', 'in transit')
        ORDER BY p.pickup_date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute(['driver_id' => $driver_id]);
        $pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pickups)) {
            echo '<div class="alert alert-info">This driver has no active pickups at the moment.</div>';
            return;
        }
        
        // Build HTML response
        $html = '<div class="pickup-list">';
        
        foreach ($pickups as $pickup) {
            $html .= '<div class="pickup-card">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1">Pickup #' . $pickup['pickup_id'] . ' <small class="text-muted">(Order #' . $pickup['order_id'] . ')</small></h6>
                    <span class="badge badge-' . getStatusBadgeClass($pickup['pickup_status']) . '">' . ucfirst($pickup['pickup_status']) . '</span>
                </div>
                <p class="pickup-time mb-1"><i class="bi bi-calendar-check"></i> ' . date('M j, Y g:i A', strtotime($pickup['pickup_date'])) . '</p>
                <p class="mb-1"><i class="bi bi-geo-alt"></i> ' . htmlspecialchars($pickup['pickup_location']) . '</p>';
                
                if (!empty($pickup['pickup_notes'])) {
                    $html .= '<div class="small text-muted mt-2"><strong>Notes:</strong> ' . htmlspecialchars($pickup['pickup_notes']) . '</div>';
                }
                
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        echo $html;
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Active pickups error: " . $e->getMessage());
    }
}

/**
 * Helper function for status badges - Same as in the view files
 */
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'available':
            return 'success';
        case 'busy':
            return 'warning';
        case 'offline':
            return 'secondary';
        case 'pending':
            return 'pending';
        case 'scheduled':
            return 'scheduled';
        case 'in transit':
        case 'in-transit':
            return 'in-transit';
        case 'completed':
            return 'completed';
        case 'canceled':
        case 'cancelled':
            return 'canceled';
        default:
            return 'info';
    }
}
?>