<?php
// Start session to check if user is logged in
session_start();

// Include necessary files
require_once '../../controllers/UserController.php';  // For user controller functions (like logout)
require_once '../../models/Log.php';  // For fetching logs

// Create instances of required classes
$userController = new UserController();
$logClass = new Log();  // Instantiate Log class

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin-login.php");
    exit();
}

// Pagination setup
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;  // Allow for dynamic limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$searchTerm = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';

// Fetch logs and total count
$logs = []; // Initialize to avoid undefined variable warning
$totalLogs = 0;

if (!empty($searchTerm)) {
    $logs = $logClass->searchActivityLogs($searchTerm, $offset, $limit);  // Use Log Class
    $totalLogs = $logClass->getSearchLogsCount($searchTerm); // Use Log Class
} else {
    $logs = $logClass->getActivityLogs($offset, $limit); // Use Log Class
    $totalLogs = $logClass->getTotalLogsCount(); // Use Log Class
}

$totalPages = ceil($totalLogs / $limit);

// Handle logout action
if (isset($_GET['logout'])) {
    $userController->logout();
}

// Function to format the timestamp to a more friendly format
function time_elapsed_string($datetime, $full = false): string
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// If the page is requested for AJAX (to return the HTML only for logs)
if (isset($_GET['ajax'])) {
    foreach ($logs as $log) {
        echo "<tr>
                <td>" . htmlspecialchars($log['log_id']) . "</td>
                <td>" . htmlspecialchars($log['username']) . "</td>
                <td class='log-message' title='" . htmlspecialchars($log['action']) . "'>" . htmlspecialchars($log['action']) . "</td>
                <td><span class='log-timestamp' data-time='" . htmlspecialchars($log['action_date']) . "'>" . time_elapsed_string($log['action_date']) . "</span></td>
              </tr>";
    }
    exit(); // End script after returning the logs as HTML
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .log-message {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .no-logs-message {
            font-size: 18px;
            color: #d9534f;
            font-weight: bold;
        }
    </style>
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
                    <a href="?logout=true" class="btn btn-danger" onclick="return confirmLogout();">Logout</a>
                </div>

                <!-- Search Bar -->
                <form method="GET" action="" class="form-inline mb-3" id="search-form">
                    <input type="text" name="search" id="search" class="form-control mr-sm-2" placeholder="Search logs..." value="<?= htmlspecialchars($searchTerm) ?>">
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
                                                    <th>Activity <i class="bi bi-info-circle" data-toggle="tooltip" title="Description of the activity that the user performed"></i></th>
                                                    <th>Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody id="logs-table">
                                                <?php if (count($logs) > 0) : ?>
                                                    <?php foreach ($logs as $log) : ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($log['log_id']) ?></td>
                                                            <td><?= htmlspecialchars($log['username']) ?></td>
                                                            <td class="log-message" title="<?= htmlspecialchars($log['action']) ?>"><?= htmlspecialchars($log['action']) ?></td>
                                                            <td>
                                                                <span class="log-timestamp" data-time="<?= htmlspecialchars($log['action_date']) ?>">
                                                                    <?= time_elapsed_string($log['action_date']) ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php elseif (!empty($searchTerm)) : ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center no-logs-message">No logs found for the search term.</td>
                                                    </tr>
                                                <?php else : ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center no-logs-message">No logs found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
                                    <nav>
                                        <ul class="pagination justify-content-center mt-3">
                                            <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=1&search=<?= urlencode($searchTerm) ?>">First</a>
                                            </li>
                                            <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>">Previous</a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>">Next</a>
                                            </li>
                                            <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($searchTerm) ?>">Last</a>
                                            </li>
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

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="d-none text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Tooltip Initialization
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Logout Confirmation
        function confirmLogout() {
            return confirm('Are you sure you want to log out?');
        }

        // Real-time Search with AJAX
        $('#search').on('keyup', function() {
            var searchTerm = $(this).val();
            $.ajax({
                url: '?search=' + searchTerm + '&ajax=true',
                method: 'GET',
                beforeSend: function() {
                    $('#logs-table').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
                },
                success: function(response) {
                    $('#logs-table').html(response);
                }
            });
        });

        // Real-time Timestamps Update
        function timeAgo(date) {
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            const intervals = [
                { name: 'year', seconds: 31536000 },
                { name: 'month', seconds: 2592000 },
                { name: 'day', seconds: 86400 },
                { name: 'hour', seconds: 3600 },
                { name: 'minute', seconds: 60 },
                { name: 'second', seconds: 1 },
            ];

            for (let i = 0; i < intervals.length; i++) {
                const interval = intervals[i];
                const time = Math.floor(seconds / interval.seconds);
                if (time > 0) {
                    return time + ' ' + interval.name + (time > 1 ? 's' : '') + ' ago';
                }
            }

            return 'just now';
        }

        // Update all timestamps on the page
        function updateTimestamps() {
            document.querySelectorAll('.log-timestamp').forEach(function (timestampElement) {
                const timestamp = new Date(timestampElement.getAttribute('data-time'));
                timestampElement.innerText = timeAgo(timestamp);
            });
        }

        // Call updateTimestamps every minute (60000ms)
        setInterval(updateTimestamps, 60000);

        // Call updateTimestamps once when the page loads to set the initial time
        updateTimestamps();
    </script>
</body>

</html>
