<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

require_once '../../models/Database.php';

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$totalOrders = 0;

// Database Connection
$database = new Database();
$conn = $database->connect();

// Fetch Orders with Pagination and Search
if (!empty($search)) {
    $query = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, o.pickup_details
              FROM orders AS o
              JOIN users AS u ON o.consumer_id = u.user_id
              WHERE u.username LIKE :search OR o.order_status LIKE :search
              ORDER BY o.order_date DESC
              LIMIT :limit OFFSET :offset";

    $countQuery = "SELECT COUNT(*) AS total 
                   FROM orders AS o
                   JOIN users AS u ON o.consumer_id = u.user_id
                   WHERE u.username LIKE :search OR o.order_status LIKE :search";
} else {
    $query = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, o.pickup_details
              FROM orders AS o
              JOIN users AS u ON o.consumer_id = u.user_id
              ORDER BY o.order_date DESC
              LIMIT :limit OFFSET :offset";

    $countQuery = "SELECT COUNT(*) AS total FROM orders";
}

// Prepare and Execute Count Query
$countStmt = $conn->prepare($countQuery);
if (!empty($search)) {
    $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$countStmt->execute();
$totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $limit);

// Prepare and Execute Main Query
$stmt = $conn->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Oversight - Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/sidebar.css"> 
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Order Oversight</h1>
                    <form method="POST" class="ml-3">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Search Bar -->
                <form method="GET" action="" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-success">Search</button>
                </form>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Consumer</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Pickup Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($order['order_status'])) ?></td>
                                        <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                        <td><?= htmlspecialchars($order['pickup_details'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
