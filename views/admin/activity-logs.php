<?php
// Start session to check if user is logged in
session_start();

// Include necessary files
require_once '../../controllers/UserController.php';  // For user controller functions (like logout)
require_once '../../models/Log.php';  // For fetching logs

// Create instances of required classes
$userController = new UserController();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location:admin-login.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($searchTerm)) {
    $logs = Log::searchActivityLogs($searchTerm, $offset, $limit);
    $totalLogs = Log::getSearchLogsCount($searchTerm);
} else {
    $logs = Log::getActivityLogs($offset, $limit);
    $totalLogs = Log::getTotalLogsCount();
}

$totalPages = ceil($totalLogs / $limit);

// Handle logout action
if (isset($_GET['logout'])) {
    $userController->logout();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Activity Logs</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css"> 
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Activity Logs</h1>
                    
                    <!-- Logout Button -->
                    <a href="?logout=true" class="btn btn-danger">Logout</a> <!-- Logout Button -->
                </div>

                <!-- Search Bar -->
                <form method="GET" action="" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-sm-2" placeholder="Search logs..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>

                <!-- Activity Logs Section -->
                <section id="activity-logs">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Recent Activity Logs</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>User</th>
                                                    <th>Activity</th>
                                                    <th>Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($logs) > 0): ?>
                                                    <?php foreach ($logs as $log): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($log['log_id']) ?></td>
                                                            <td><?= htmlspecialchars($log['username']) ?></td>
                                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                                            <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($log['action_date']))) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No logs found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
                                    <nav>
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
