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
        // Improved log type detection with more comprehensive patterns
        $logType = 'view';
        $action = strtolower($log['action']);
        
        if (strpos($action, 'login') !== false || strpos($action, 'logged in') !== false || 
            strpos($action, 'log in') !== false || strpos($action, 'signin') !== false) {
            $logType = 'login';
        } 
        elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false || 
                strpos($action, 'chang') !== false || strpos($action, 'modif') !== false) {
            $logType = 'update';
        }
        elseif (strpos($action, 'delet') !== false || strpos($action, 'remov') !== false || 
                strpos($action, 'cancel') !== false) {
            $logType = 'delete';
        }
        
        echo "<tr class='fadeIn' data-log-type='{$logType}'>
                <td><span class='badge badge-pill badge-light'>" . htmlspecialchars($log['log_id']) . "</span></td>
                <td><span class='log-username'>" . htmlspecialchars($log['username']) . "</span></td>
                <td class='log-message' data-type='{$logType}' title='" . htmlspecialchars($log['action']) . "'>
                    <span class='log-content'>" . htmlspecialchars($log['action']) . "</span>
                </td>
                <td>
                    <span class='log-timestamp' data-time='" . htmlspecialchars($log['action_date']) . "'>
                        <i class='bi bi-clock'></i> " . time_elapsed_string($log['action_date']) . "
                    </span>
                </td>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/activity-logs.css">
    <style>
        /* Add new admin header styling */
        .admin-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 10px 0;
        }
        .admin-badge {
            background-color: #6a11cb;
            color: white;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }
        /* Update page header styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }
        .btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Add Admin Header -->
    <div class="admin-header text-center">
        <h2><i class="bi bi-shield-lock"></i> ADMIN CONTROL PANEL <span class="admin-badge">Restricted Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 activity-logs-page">
                <!-- Add breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Activity Logs</li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-clock-history"></i> Activity Logs</h1>
                    <div class="d-flex">
                        <button class="btn btn-success mr-2" id="exportLogs">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        <button class="btn btn-outline-secondary mr-2" id="refreshLogs">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <a href="?logout=true" class="btn btn-danger" onclick="return confirmLogout();">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Search and Filter Area -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="search-container">
                            <form method="GET" action="" class="d-flex" id="search-form">
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Search activity logs..." value="<?= htmlspecialchars($searchTerm) ?>">
                                <i class="bi bi-search search-icon"></i>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
                        <div class="filter-badges">
                            <span class="filter-badge active" data-filter="all">All Activities <span class="count"></span></span>
                            <span class="filter-badge" data-filter="login">Logins <span class="count"></span></span>
                            <span class="filter-badge" data-filter="update">Updates <span class="count"></span></span>
                            <span class="filter-badge" data-filter="delete">Deletions <span class="count"></span></span>
                            <span class="filter-badge" data-filter="view">Views <span class="count"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Activity Logs Section -->
                <section id="activity-logs">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-list-check"></i> Recent Activities</h5>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">ID</th>
                                                <th style="width: 15%">User</th>
                                                <th style="width: 60%">Activity</th>
                                                <th style="width: 20%">Timestamp</th>
                                            </tr>
                                        </thead>
                                        <tbody id="logs-table">
                                            <?php if (count($logs) > 0) : ?>
                                                <?php foreach ($logs as $log) : 
                                                    // Improved log type detection - use the same exact algorithm as AJAX
                                                    $logType = 'view';
                                                    $action = strtolower($log['action']);
                                                    
                                                    if (strpos($action, 'login') !== false || strpos($action, 'logged in') !== false || 
                                                        strpos($action, 'log in') !== false || strpos($action, 'signin') !== false) {
                                                        $logType = 'login';
                                                    } 
                                                    elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false || 
                                                            strpos($action, 'chang') !== false || strpos($action, 'modif') !== false) {
                                                        $logType = 'update';
                                                    }
                                                    elseif (strpos($action, 'delet') !== false || strpos($action, 'remov') !== false || 
                                                            strpos($action, 'cancel') !== false) {
                                                        $logType = 'delete';
                                                    }
                                                ?>
                                                    <tr class="fadeIn" data-log-type="<?= $logType ?>">
                                                        <td><span class="badge badge-pill badge-light"><?= htmlspecialchars($log['log_id']) ?></span></td>
                                                        <td><span class="log-username"><?= htmlspecialchars($log['username']) ?></span></td>
                                                        <td class="log-message" data-type="<?= $logType ?>" 
                                                            title="<?= htmlspecialchars($log['action']) ?>">
                                                            <span class="log-content"><?= htmlspecialchars($log['action']) ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="log-timestamp" data-time="<?= htmlspecialchars($log['action_date']) ?>">
                                                                <i class="bi bi-clock"></i>
                                                                <?= time_elapsed_string($log['action_date']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php elseif (!empty($searchTerm)) : ?>
                                                <tr>
                                                    <td colspan="4" class="no-logs-message">
                                                        <i class="bi bi-search"></i>
                                                        No logs found matching "<?= htmlspecialchars($searchTerm) ?>".
                                                    </td>
                                                </tr>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="4" class="no-logs-message">
                                                        <i class="bi bi-calendar-x"></i>
                                                        No activity logs have been recorded yet.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- No results message container -->
                                <div id="no-results-message" class="text-center p-4" style="display: none;">
                                    <i class="bi bi-filter-circle" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No logs match the selected filter.</p>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div class="pagination-container">
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=1&search=<?= urlencode($searchTerm) ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php
                                        // Show limited page numbers with ellipsis
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                        
                                        if ($start > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($searchTerm) . '">1</a></li>';
                                            if ($start > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        
                                        for ($i = $start; $i <= $end; $i++) : 
                                        ?>
                                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                            </li>
                                        <?php 
                                        endfor;
                                        
                                        if ($end < $totalPages) {
                                            if ($end < $totalPages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($searchTerm) . '">' . $totalPages . '</a></li>';
                                        }
                                        ?>
                                        
                                        <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($searchTerm) ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
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

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Activity Logs</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="exportFilter">Activity Type:</label>
                        <select id="exportFilter" class="form-control">
                            <option value="all">All Activities</option>
                            <option value="login">Logins</option>
                            <option value="update">Updates</option>
                            <option value="delete">Deletions</option>
                            <option value="view">Views</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exportFormat">Export Format:</label>
                        <select id="exportFormat" class="form-control">
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exportStartDate">Start Date:</label>
                        <input type="date" id="exportStartDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="exportEndDate">End Date:</label>
                        <input type="date" id="exportEndDate" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmExport">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Global variables to track state
        let currentFilter = 'all';
        
        // Initialize when document is ready
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Initialize filters
            initializeFilters();
            
            // First-time filter application and counter update
            updateFilterCounters();
            applyCurrentFilter();
            
            // Refresh button functionality
            $('#refreshLogs').on('click', function() {
                location.reload();
            });
            
            // Export button functionality
            $('#exportLogs').on('click', function() {
                // Set export modal filter to match current view filter
                $('#exportFilter').val(currentFilter);
                $('#exportModal').modal('show');
            });
            
            // Handle export confirmation
            $('#confirmExport').on('click', function() {
                const filter = $('#exportFilter').val();
                const format = $('#exportFormat').val();
                const startDate = $('#exportStartDate').val();
                const endDate = $('#exportEndDate').val();
                
                // Validate dates if provided
                if ((startDate && !endDate) || (!startDate && endDate)) {
                    alert('Please provide both start and end dates or leave both empty.');
                    return;
                }
                
                if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                    alert('Start date cannot be after end date.');
                    return;
                }
                
                // Create date range text for logging/display
                let dateRangeText = '';
                if (startDate && endDate) {
                    const formattedStartDate = new Date(startDate).toLocaleDateString();
                    const formattedEndDate = new Date(endDate).toLocaleDateString();
                    dateRangeText = ` from ${formattedStartDate} to ${formattedEndDate}`;
                }
                
                // Build export URL with parameters
                let exportUrl = 'export-logs.php?format=' + format + '&filter=' + filter;
                if (startDate && endDate) {
                    exportUrl += '&start_date=' + startDate + '&end_date=' + endDate;
                }
                
                // In a real implementation, you would redirect to this URL or open in a new tab
                window.open(exportUrl, '_blank');
                
                // Log the export action
                console.log(`Exporting ${filter} logs${dateRangeText} in ${format.toUpperCase()} format`);
                
                // Close the modal
                $('#exportModal').modal('hide');
            });
        });
        
        // Logout Confirmation
        function confirmLogout() {
            return confirm('Are you sure you want to log out?');
        }

        // Real-time Search with AJAX - improved with proper jQuery
        $('#search').on('keyup', function() {
            const searchTerm = $(this).val();
            
            $.ajax({
                url: '?search=' + encodeURIComponent(searchTerm) + '&ajax=true',
                method: 'GET',
                beforeSend: function() {
                    $('#logs-table').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></td></tr>');
                    // Hide the no results message while searching
                    $('#no-results-message').hide();
                },
                success: function(response) {
                    $('#logs-table').html(response);
                    
                    // Update filter counters and reapply current filter
                    setTimeout(function() {
                        updateFilterCounters();
                        applyCurrentFilter();
                    }, 100);
                },
                error: function() {
                    $('#logs-table').html('<tr><td colspan="4" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle-fill"></i> An error occurred while searching. Please try again.</td></tr>');
                }
            });
        });

        // Initialize filter functionality
        function initializeFilters() {
            $('.filter-badge').on('click', function() {
                const filter = $(this).data('filter');
                
                // Update active state visually
                $('.filter-badge').removeClass('active');
                $(this).addClass('active');
                
                // Update current filter and apply
                currentFilter = filter;
                applyCurrentFilter();
            });
        }
        
        // Apply the current filter - improved implementation
        function applyCurrentFilter() {
            const filter = currentFilter;
            console.log('Applying filter:', filter);
            
            // Get all log rows
            const $rows = $('#logs-table tr');
            let visibleCount = 0;
            
            // Apply filtering
            $rows.each(function() {
                const $row = $(this);
                const rowType = $row.data('log-type');
                
                // Skip rows without the data-log-type attribute (like "loading" indicators)
                if (rowType === undefined) {
                    return;
                }
                
                if (filter === 'all' || rowType === filter) {
                    $row.show();
                    visibleCount++;
                } else {
                    $row.hide();
                }
            });
            
            // Show/hide the "no results" message
            if (visibleCount === 0 && $rows.length > 0) {
                $('#no-results-message').show();
            } else {
                $('#no-results-message').hide();
            }
            
            console.log(`Filter "${filter}" applied: ${visibleCount} rows visible`);
        }
        
        // Update the counters in filter badges
        function updateFilterCounters() {
            // Count for "all" filter
            const totalCount = $('#logs-table tr[data-log-type]').length;
            $('.filter-badge[data-filter="all"] .count').text(`(${totalCount})`);
            
            // Count for each specific type
            ['login', 'update', 'delete', 'view'].forEach(type => {
                const count = $('#logs-table tr[data-log-type="' + type + '"]').length;
                $('.filter-badge[data-filter="' + type + '"] .count').text(`(${count})`);
            });
        }

        // Real-time Timestamps Update - keep the existing implementation
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

        function updateTimestamps() {
            document.querySelectorAll('.log-timestamp').forEach(function (timestampElement) {
                const timestamp = new Date(timestampElement.getAttribute('data-time'));
                const timeText = timeAgo(timestamp);
                // Keep the icon when updating timestamp
                timestampElement.innerHTML = '<i class="bi bi-clock"></i> ' + timeText;
            });
        }

        // Call updateTimestamps every minute
        setInterval(updateTimestamps, 60000);
        
        // Call updateTimestamps once when the page loads
        updateTimestamps();
    </script>
</body>
</html>
