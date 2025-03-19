<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

// Get manager's user ID from session
$manager_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$manager_user_id) {
    // Handle case where manager ID is not in session
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/User.php';
require_once '../../models/Log.php';

$userClass = new User();
$logClass = new Log();

// Get users excluding admin and manager roles
$users = $userClass->getUsersByRoles(['Farmer', 'Vendor', 'Customer']);
$roles = $userClass->getRolesByType(['Farmer', 'Vendor', 'Customer']);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['add_user']) && !empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['role_id'])) {
        // Managers should NOT be able to create Admin accounts. Enforce this restriction.
        if ($_POST['role_id'] == 1) { // Assuming role_id 1 is Admin. Check your roles table!
            echo "<script>alert('Managers cannot create Admin accounts.');</script>"; // Display a message. Consider a more user-friendly error display.
        } else {
            $userClass->addUser($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role_id'], $_POST['first_name'], $_POST['last_name'], $_POST['contact_number'], $_POST['address']);
            // Log the activity
            $logClass->logActivity($manager_user_id, "Added a new user: " . $_POST['username']);
            header("Location: manager-user-management.php");
            exit();
        }
    }

    if (isset($_POST['edit_user'])) {
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        // Managers should NOT be able to assign Admin roles. Enforce this restriction.
        if ($_POST['role_id'] == 1) { // Assuming role_id 1 is Admin. Check your roles table!
             echo "<script>alert('Managers cannot assign Admin roles.');</script>"; // Display a message. Consider a more user-friendly error display.
        } else {
            $userClass->updateUser($_POST['user_id'], $_POST['username'], $_POST['email'], $password, $_POST['role_id'], $_POST['first_name'], $_POST['last_name'], $_POST['contact_number'], $_POST['address']);
            // Log the activity
            $logClass->logActivity($manager_user_id, "Updated user: " . $_POST['username']);
            header("Location: manager-user-management.php");
            exit();
        }
    }

    if (isset($_POST['delete_user'])) {
        $userClass->deleteUser($_POST['user_id']);
        // Log the activity
        $logClass->logActivity($manager_user_id, "Deleted user with ID: " . $_POST['user_id']);
        header("Location: manager-user-management.php");
        exit();
    }

    if (isset($_POST['logout'])) {
        // Log the activity
        $logClass->logActivity($manager_user_id, "Manager logged out.");
        // Destroy session and log out the user
        session_unset();
        session_destroy();
        header("Location: manager-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/user-management.css">
</head>
<body>
    <!-- Add Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-people-fill"></i> USER MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Add Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">User Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-1">User Management</h1>
                        <p class="text-muted mb-0">Manage and monitor system users</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-success mr-2" onclick="exportUserReport()">
                            <i class="bi bi-file-earmark-text"></i> Export Report
                        </button>
                        <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addUserModal">
                            <i class="bi bi-plus-lg"></i> Add New User
                        </button>
                        <form method="POST" class="mb-0">
                            <button type="submit" name="logout" class="btn btn-danger logout-btn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Enhanced User Stats Cards -->
                <div class="row mb-4">
                    <?php
                    $roleStats = [
                        ['role' => 'Farmer', 'icon' => 'bi-person-lines-fill', 'color' => 'success'],
                        ['role' => 'Vendor', 'icon' => 'bi-shop', 'color' => 'primary'],
                        ['role' => 'Customer', 'icon' => 'bi-people', 'color' => 'info']
                    ];
                    
                    foreach ($roleStats as $stat): 
                        $count = $userClass->countUsersByRole($stat['role']);
                    ?>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <div class="card-icon text-<?= $stat['color'] ?>">
                                <i class="bi <?= $stat['icon'] ?>"></i>
                            </div>
                            <h3 class="mb-3"><?= $count ?></h3>
                            <p class="text-muted mb-0">Active <?= $stat['role'] ?>s</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Enhanced Search and Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="bi bi-search"></i> Search Users</label>
                                    <input type="text" id="searchUser" class="form-control" placeholder="Search by name, email...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="bi bi-funnel"></i> Filter by Role</label>
                                    <select id="roleFilter" class="form-control">
                                        <option value="">All Roles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= htmlspecialchars($role['role_name']) ?>">
                                                <?= htmlspecialchars($role['role_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="bi bi-calendar3"></i> Filter by Date</label>
                                    <select id="dateFilter" class="form-control">
                                        <option value="">All Time</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                                    <select id="sortFilter" class="form-control">
                                        <option value="newest">Newest First</option>
                                        <option value="oldest">Oldest First</option>
                                        <option value="name">Name A-Z</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced User Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User Info</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['user_id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar mr-3">
                                                    <i class="bi bi-person-circle"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $roleName = "";
                                            foreach ($roles as $role) {
                                                if ($role['role_id'] == $user['role_id']) {
                                                    $roleName = $role['role_name'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <span class="role-badge <?= $roleName ?>"><?= $roleName ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($user['contact_number']) ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($user['address']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Active</span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                        data-user-id="<?= $user['user_id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-role-id="<?= $user['role_id'] ?>"
                                                        data-first-name="<?= htmlspecialchars($user['first_name']) ?>"
                                                        data-last-name="<?= htmlspecialchars($user['last_name']) ?>"
                                                        data-contact-number="<?= htmlspecialchars($user['contact_number']) ?>"
                                                        data-address="<?= htmlspecialchars($user['address']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this user?');">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
                                    <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                                    <select name="role_id" class="form-control mb-3" required>
                                        <?php foreach ($roles as $role):
                                            // Managers should NOT be able to create admins
                                            if ($role['role_name'] != 'Admin'): ?>
                                                <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                            <?php endif;
                                        endforeach; ?>
                                    </select>

                                    <!-- Additional fields for personal information -->
                                    <input type="text" name="first_name" class="form-control mb-3" placeholder="First Name" required>
                                    <input type="text" name="last_name" class="form-control mb-3" placeholder="Last Name" required>
                                    <input type="text" name="contact_number" class="form-control mb-3" placeholder="Contact Number">
                                    <textarea name="address" class="form-control mb-3" placeholder="Address"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" id="edit_user_id">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" name="username" id="edit_username" class="form-control mb-3" placeholder="Username" required>
                                    <input type="email" name="email" id="edit_email" class="form-control mb-3" placeholder="Email" required>
                                    <input type="password" name="password" class="form-control mb-3" placeholder="Password (leave blank to keep current)">
                                    <select name="role_id" id="edit_role_id" class="form-control mb-3" required>
                                        <?php foreach ($roles as $role):
                                            // Managers should NOT be able to create admins
                                            if ($role['role_name'] != 'Admin'): ?>
                                                <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                            <?php endif;
                                        endforeach; ?>
                                    </select>

                                    <!-- Additional fields for personal information -->
                                    <input type="text" name="first_name" id="edit_first_name" class="form-control mb-3" placeholder="First Name" required>
                                    <input type="text" name="last_name" id="edit_last_name" class="form-control mb-3" placeholder="Last Name" required>
                                    <input type="text" name="contact_number" id="edit_contact_number" class="form-control mb-3" placeholder="Contact Number">
                                    <textarea name="address" id="edit_address" class="form-control mb-3" placeholder="Address"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit_user" class="btn btn-warning">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Enhanced search and filter functionality
        function filterUsers() {
            const searchTerm = $('#searchUser').val().toLowerCase();
            const roleFilter = $('#roleFilter').val();
            const dateFilter = $('#dateFilter').val();
            const sortFilter = $('#sortFilter').val();

            $('tbody tr').each(function() {
                const $row = $(this);
                const username = $row.find('td:eq(1)').text().toLowerCase();
                const email = $row.find('td:eq(2)').text().toLowerCase();
                const role = $row.find('td:eq(3)').text();
                const name = $row.find('td:eq(4)').text().toLowerCase() + 
                           $row.find('td:eq(5)').text().toLowerCase();

                const matchesSearch = username.includes(searchTerm) || 
                                    email.includes(searchTerm) || 
                                    name.includes(searchTerm);
                const matchesRole = !roleFilter || role === roleFilter;

                $row.toggle(matchesSearch && matchesRole);
            });
        }

        // Export functionality
        function exportUserReport() {
            const visible_rows = $('tbody tr:visible');
            let csv = 'Username,Email,Role,First Name,Last Name,Contact Number,Address\n';
            
            visible_rows.each(function() {
                const row = [];
                $(this).find('td').each(function(i) {
                    if (i < 7) { // Only export non-action columns
                        row.push(`"${$(this).text().trim()}"`);
                    }
                });
                csv += row.join(',') + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'user_report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Event listeners
        $(document).ready(function() {
            $('#searchUser, #roleFilter, #dateFilter, #sortFilter').on('input change', filterUsers);
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Add this new code for edit functionality
        $(document).ready(function() {
            // Edit button click handler
            $('.edit-btn').click(function() {
                // Get data from button attributes
                const userId = $(this).data('user-id');
                const username = $(this).data('username');
                const email = $(this).data('email');
                const roleId = $(this).data('role-id');
                const firstName = $(this).data('first-name');
                const lastName = $(this).data('last-name');
                const contactNumber = $(this).data('contact-number');
                const address = $(this).data('address');

                // Populate the edit modal fields
                $('#edit_user_id').val(userId);
                $('#edit_username').val(username);
                $('#edit_email').val(email);
                $('#edit_role_id').val(roleId);
                $('#edit_first_name').val(firstName);
                $('#edit_last_name').val(lastName);
                $('#edit_contact_number').val(contactNumber);
                $('#edit_address').val(address);

                // Show the modal
                $('#editUserModal').modal('show');
            });
        });
    </script>
</body>
</html>